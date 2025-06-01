<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$notificationId = (int)$_POST['notification_id'];

// Verificar que la notificación pertenece al usuario actual
$conn = getDbConnection();
$stmt = $conn->prepare("
    DELETE FROM notifications 
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $notificationId, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['flash_message'] = "Notificación eliminada correctamente.";
    $_SESSION['flash_type'] = "success";
} else {
    $_SESSION['flash_message'] = "No se pudo eliminar la notificación o no existe.";
    $_SESSION['flash_type'] = "warning";
}

header("Location: " . BASE_URL . "pages/notifications.php");
exit;
?>