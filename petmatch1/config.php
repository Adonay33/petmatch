<?php
// Configuración básica del proyecto
define('BASE_URL', 'http://localhost/2025/petmatch1/');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '379FE3713D');
define('DB_NAME', 'petmatch1');

// Configuración de subida de archivos
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_PET_DIR', __DIR__ . '/uploads/pets/');
define('UPLOAD_AVATAR_DIR', __DIR__ . '/uploads/avatars/');

// Iniciar sesión
session_start();

// Otras configuraciones
date_default_timezone_set('America/El_Salvador');

header("Access-Control-Allow-Origin: " . BASE_URL);
header("Access-Control-Allow-Credentials: true");
?>