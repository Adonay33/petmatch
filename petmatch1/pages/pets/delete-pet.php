<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Debes iniciar sesión';
    $_SESSION['flash_type'] = 'danger';
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$petId = (int)($_GET['id'] ?? 0);

if ($petId <= 0) {
    $_SESSION['flash_message'] = 'ID inválido';
    $_SESSION['flash_type'] = 'danger';
    header("Location: " . BASE_URL . "pages/pets/list.php");
    exit;
}

$conn = getDbConnection();

// Verificar propiedad
$stmt = $conn->prepare("SELECT user_id FROM pets WHERE id = ?");
$stmt->bind_param("i", $petId);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();

if (!$pet || $pet['user_id'] != $_SESSION['user_id']) {
    $_SESSION['flash_message'] = 'No tienes permiso';
    $_SESSION['flash_type'] = 'danger';
    header("Location: " . BASE_URL . "pages/pets/list.php");
    exit;
}

// Nombres correctos de columnas (verifica los de tu BD)
$foreignKeys = [
    'favorites' => 'pet_id',  // o el nombre correcto en tu BD
    'messages' => 'pet_id',
    'pet_images' => 'pet_id',
    'notifications' => 'related_id' // ajusta según tu estructura
];

$conn->begin_transaction();

try {
    // 1. Obtener imágenes para eliminarlas después
    $stmt = $conn->prepare("SELECT image_path FROM pet_images WHERE pet_id = ?");
    $stmt->bind_param("i", $petId);
    $stmt->execute();
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 2. Eliminar registros relacionados
    foreach ($foreignKeys as $table => $column) {
        $query = "DELETE FROM $table WHERE $column = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $petId);
        if (!$stmt->execute()) {
            throw new Exception("Error al eliminar de $table: " . $conn->error);
        }
    }

    // 3. Eliminar la mascota
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ?");
    $stmt->bind_param("i", $petId);
    $stmt->execute();

    // 4. Eliminar archivos físicos
    foreach ($images as $image) {
        $filePath = UPLOAD_PET_DIR . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $conn->commit();
    $_SESSION['flash_message'] = 'Mascota eliminada correctamente';
    $_SESSION['flash_type'] = 'success';

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash_message'] = 'Error al eliminar: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
}

header("Location: " . BASE_URL . "pages/pets/list.php");
exit;
?>