<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

// Verificar sesión
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión para enviar mensajes.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

// Verificar método y datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = "Método no permitido.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

// Validar datos
$required = ['pet_id', 'receiver_id', 'message'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['flash_message'] = "Faltan datos requeridos.";
        $_SESSION['flash_type'] = "danger";
        header("Location: " . BASE_URL . "pages/home.php");
        exit;
    }
}

$petId = (int)$_POST['pet_id'];
$receiverId = (int)$_POST['receiver_id'];
$message = trim($_POST['message']);

// Evitar auto-contacto
if ($_SESSION['user_id'] === $receiverId) {
    $_SESSION['flash_message'] = "No puedes enviarte mensajes a ti mismo.";
    $_SESSION['flash_type'] = "warning";
    header("Location: " . BASE_URL . "pages/pets/view.php?id=$petId");
    exit;
}

// Generar conversation_id único y consistente
$conversationId = generateConversationId($_SESSION['user_id'], $receiverId, $petId);

// Guardar el mensaje
$conn = getDbConnection();
$stmt = $conn->prepare("
    INSERT INTO messages (conversation_id, sender_id, recipient_id, pet_id, content, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("siiis", $conversationId, $_SESSION['user_id'], $receiverId, $petId, $message);

if ($stmt->execute()) {
    $_SESSION['flash_message'] = "Mensaje enviado correctamente!";
    $_SESSION['flash_type'] = "success";
    
    // Crear notificación para el receptor
    $notificationContent = "Tienes un nuevo mensaje sobre una mascota";
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, related_id, content, created_at)
        VALUES (?, 'message', ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $receiverId, $petId, $notificationContent);
    $stmt->execute();
} else {
    $_SESSION['flash_message'] = "Error al enviar el mensaje. Por favor intenta nuevamente.";
    $_SESSION['flash_type'] = "danger";
}

header("Location: " . BASE_URL . "pages/pets/view.php?id=$petId");
exit;

// Función para generar un ID de conversación consistente
function generateConversationId($userId1, $userId2, $petId) {
    // Ordenamos los IDs de usuario para garantizar consistencia
    $userIds = [$userId1, $userId2];
    sort($userIds);
    
    // Creamos un hash único basado en los IDs ordenados y el ID de la mascota
    return hash('sha256', implode('-', $userIds) . '-' . $petId);
}
?>