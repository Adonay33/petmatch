<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';

// Destruir toda la información de la sesión
$_SESSION = array();

// Borrar la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login con mensaje
$_SESSION['flash_message'] = "Has cerrado sesión correctamente";
$_SESSION['flash_type'] = "success";
header('Location: ' . BASE_URL . 'pages/login.php');
exit();
?>