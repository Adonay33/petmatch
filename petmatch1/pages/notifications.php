<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';
require_once __DIR__ . '../../includes/notifications.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión para ver notificaciones.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/login.php');
}

$pageTitle = "Notificaciones - PetMatch";

// Obtener notificaciones del usuario
$notifications = getNotifications($_SESSION['user_id'], 20);

// Marcar todas como leídas
if (!empty($notifications)) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
}

require_once __DIR__ . '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Notificaciones</h1>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">
                No tienes notificaciones.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($notification['content']); ?>
                            </div>
                            <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                        </div>
                        <?php if ($notification['type'] === 'message' || $notification['type'] === 'favorite'): ?>
                            <div class="mt-2">
                                <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Ver mascota
                                </a>
                               <?php if ($notification['type'] === 'message'): ?>
    <?php
    // Obtener el conversation_id para esta notificación
    $conn = getDbConnection();
    $stmt = $conn->prepare("
        SELECT conversation_id 
        FROM messages 
        WHERE pet_id = ? 
        AND (sender_id = ? OR recipient_id = ?)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("iii", $notification['related_id'], $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversation = $result->fetch_assoc();
    ?>
    
    <a href="<?php echo BASE_URL; ?>pages/messages/conversation.php?conversation_id=<?php echo $conversation['conversation_id']; ?>&pet_id=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-primary">
        Responder
    </a>
    <form action="<?php echo BASE_URL; ?>pages/delete_noti.php" method="post" class="d-inline">
            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar notificación">
                <i class="fas fa-times"></i>
                Quitar
            </button>
        </form>
<?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '../../includes/footer.php';
?>