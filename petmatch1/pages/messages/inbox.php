<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión para ver tus mensajes.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/login.php');
}

$pageTitle = "Bandeja de entrada - PetMatch";

// Obtener conversaciones del usuario
$conn = getDbConnection();
$userId = $_SESSION['user_id'];

$query = "SELECT m.conversation_id, m.pet_id, p.name as pet_name, 
                 CASE 
                     WHEN m.sender_id = ? THEN m.recipient_id 
                     ELSE m.sender_id 
                 END as other_user_id,
                 u.username as other_user_name, u.avatar as other_user_avatar,
                 MAX(m.created_at) as last_message_time,
                 SUM(CASE WHEN m.recipient_id = ? AND m.is_read = FALSE THEN 1 ELSE 0 END) as unread_count
          FROM messages m
          JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
          JOIN pets p ON p.id = m.pet_id
          WHERE m.sender_id = ? OR m.recipient_id = ?
          GROUP BY m.conversation_id, m.pet_id, other_user_id, other_user_name, other_user_avatar
          ORDER BY last_message_time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$conversations = $result->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Bandeja de entrada</h1>
        
        <?php if (empty($conversations)): ?>
            <div class="alert alert-info">
                No tienes mensajes aún. Busca una mascota y contacta al dueño para empezar una conversación.
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($conversations as $conv): ?>
                    <a href="<?php echo BASE_URL; ?>pages/messages/conversation.php?conversation_id=<?php echo $conv['conversation_id']; ?>" 
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($conv['other_user_avatar'] ?? 'default.png'); ?>" 
                                 class="rounded-circle me-3" width="50" height="50" alt="<?php echo htmlspecialchars($conv['other_user_name']); ?>">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($conv['other_user_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($conv['pet_name']); ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted"><?php echo formatDate($conv['last_message_time']); ?></small>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2"><?php echo $conv['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '../../../includes/footer.php';
?>