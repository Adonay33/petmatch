<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

// Verificar autenticación y permisos
if (!isLoggedIn() || !isAdopter()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión como adoptante";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = "Método no permitido";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

// Obtener y validar datos
$petId = (int)$_POST['pet_id'];
$action = $_POST['action']; // 'add' o 'remove'
$redirectPath = isset($_POST['redirect_path']) ? $_POST['redirect_path'] : '/pages/home.php';

// Validar ID de mascota
if ($petId <= 0) {
    $_SESSION['flash_message'] = "ID de mascota inválido";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

// Procesar en base de datos
$conn = getDbConnection();
try {
    // Verificar que la mascota existe
    $stmt = $conn->prepare("SELECT 1 FROM pets WHERE id = ?");
    $stmt->bind_param("i", $petId);
    $stmt->execute();
    
    if (!$stmt->get_result()->num_rows) {
        throw new Exception("La mascota no existe");
    }

    if ($action === 'add') {
        // Verificar si ya es favorito
        $stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND pet_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $petId);
        $stmt->execute();
        
        if (!$stmt->get_result()->num_rows) {
            $stmt = $conn->prepare("INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $_SESSION['user_id'], $petId);
            $stmt->execute();
            $_SESSION['flash_message'] = "❤️ Añadido a favoritos";
            $_SESSION['flash_type'] = "success";
        }
    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $petId);
        $stmt->execute();
        $_SESSION['flash_message'] = "💔 Eliminado de favoritos";
        $_SESSION['flash_type'] = "success";
    }
    
} catch (Exception $e) {
    $_SESSION['flash_message'] = "Error: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

// Redirección garantizada a la página correcta
$redirectUrl = rtrim(BASE_URL, '/') . $redirectPath;
header("Location: " . $redirectUrl);
exit;