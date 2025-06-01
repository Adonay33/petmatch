<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión para ver mensajes.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/login.php');
}

$userId = $_SESSION['user_id'];
$conversationId = $_GET['conversation_id'] ?? null;
$otherUserId = $_GET['user_id'] ?? null;
$petId = $_GET['pet_id'] ?? null;

// Validar parámetros
if (!$conversationId && (!$otherUserId || !$petId)) {
    $_SESSION['flash_message'] = "Parámetros inválidos para la conversación.";
    $_SESSION['flash_type'] = "danger";
    redirect('pages/messages/inbox.php');
}

$conn = getDbConnection();

// Si es una nueva conversación (sin conversation_id pero con user_id y pet_id)
if (!$conversationId) {
    // Verificar que el otro usuario exista y sea diferente al actual
    $otherUser = getUserById($otherUserId);
    if (!$otherUser || $otherUserId == $userId) {
        $_SESSION['flash_message'] = "Usuario inválido para la conversación.";
        $_SESSION['flash_type'] = "danger";
        redirect('pages/home.php');
    }
    
    // Verificar que la mascota exista y pertenezca al otro usuario
    $pet = getPetById($petId);
    if (!$pet || $pet['user_id'] != $otherUserId) {
        $_SESSION['flash_message'] = "Mascota inválida para la conversación.";
        $_SESSION['flash_type'] = "danger";
        redirect('pages/home.php');
    }
    
    // Crear un nuevo ID de conversación
    $conversationId = uniqid();
    $pageTitle = "Nuevo mensaje - PetMatch";
} else {
    // Cargar conversación existente
    $stmt = $conn->prepare("SELECT p.id as pet_id, p.name as pet_name, 
                           CASE 
                               WHEN m.sender_id = ? THEN m.recipient_id 
                               ELSE m.sender_id 
                           END as other_user_id,
                           u.username as other_user_name, u.avatar as other_user_avatar
                           FROM messages m
                           JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
                           JOIN pets p ON p.id = m.pet_id
                           WHERE m.conversation_id = ?
                           LIMIT 1");
    $stmt->bind_param("iss", $userId, $userId, $conversationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $conversationInfo = $result->fetch_assoc();
    
    if (!$conversationInfo) {
        $_SESSION['flash_message'] = "Conversación no encontrada.";
        $_SESSION['flash_type'] = "danger";
        redirect('pages/messages/inbox.php');
    }
    
    $petId = $conversationInfo['pet_id'];
    $otherUserId = $conversationInfo['other_user_id'];
    $pageTitle = "Conversación con " . $conversationInfo['other_user_name'] . " - PetMatch";
}

// Obtener información del otro usuario y la mascota
$otherUser = getUserById($otherUserId);
$pet = getPetById($petId);

// Marcar mensajes como leídos
$stmt = $conn->prepare("UPDATE messages SET is_read = TRUE 
                       WHERE conversation_id = ? AND recipient_id = ? AND is_read = FALSE");
$stmt->bind_param("si", $conversationId, $userId);
$stmt->execute();

// Obtener mensajes de la conversación
$stmt = $conn->prepare("SELECT m.*, u.username as sender_name, u.avatar as sender_avatar 
                       FROM messages m
                       JOIN users u ON u.id = m.sender_id
                       WHERE m.conversation_id = ?
                       ORDER BY m.created_at ASC");
$stmt->bind_param("s", $conversationId);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);

// Procesar nuevo mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, recipient_id, pet_id, content) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $conversationId, $userId, $otherUserId, $petId, $message);
        
        if ($stmt->execute()) {
            // Crear notificación para el destinatario
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, related_id, content) 
                                   VALUES (?, 'message', ?, ?)");
            $notificationContent = "Tienes un nuevo mensaje sobre " . $pet['name'];
            $stmt->bind_param("iis", $otherUserId, $petId, $notificationContent);
            $stmt->execute();
            
            // Redirigir para evitar reenvío del formulario
            redirect('pages/messages/conversation.php?conversation_id=' . $conversationId);
        } else {
            $error = "Error al enviar el mensaje. Por favor intenta nuevamente.";
        }
    } else {
        $error = "El mensaje no puede estar vacío.";
    }
}

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>
                Conversación sobre <?php echo htmlspecialchars($pet['name']); ?>
                <small class="text-muted">con <?php echo htmlspecialchars($otherUser['username']); ?></small>
            </h1>
            <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $petId; ?>" class="btn btn-outline-primary">
                Ver mascota
            </a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-4">
                        No hay mensajes en esta conversación. Envía el primero.
                    </div>
                <?php else: ?>
                    <div class="message-container">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] == $userId ? 'message-sent' : 'message-received'; ?>">
                                <div class="message-header">
                                    <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($message['sender_avatar'] ?? 'default.png'); ?>" 
                                         class="rounded-circle me-2" width="30" height="30" alt="<?php echo htmlspecialchars($message['sender_name']); ?>">
                                    <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong>
                                    <small class="text-muted ms-2"><?php echo formatDate($message['created_at']); ?></small>
                                </div>
                                <div class="message-body">
                                    <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="input-group mb-3">
                <textarea class="form-control" name="message" placeholder="Escribe tu mensaje..." rows="2" required></textarea>
                <button class="btn btn-primary" type="submit">Enviar</button>
            </div>
        </form>
    </div>
</div>

<?php
// Estilos específicos para la página de mensajes
echo '<style>
    .message {
        margin-bottom: 1.5rem;
        padding: 0.75rem;
        border-radius: 0.5rem;
        max-width: 70%;
    }
    
    .message-sent {
        background-color: #e3f2fd;
        margin-left: auto;
    }
    
    .message-received {
        background-color: #f5f5f5;
        margin-right: auto;
    }
    
    .message-header {
        display: flex;
        align-items: right;
        margin-bottom: 20 rem;
    }
    
    .message-body {
        white-space: normal;
    }
</style>';

require_once __DIR__ . '../../../includes/footer.php';
?>