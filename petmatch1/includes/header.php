<?php
// Verificar si la constante BASE_URL está definida
if (!defined('BASE_URL')) {
    die('BASE_URL no está definida. Verifica el archivo config.php');
}

// Determinar el título de la página
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitles = [
    'home.php' => 'Inicio',
    'login.php' => 'Iniciar sesión',
    'register.php' => 'Registro',
    // Agrega más páginas según sea necesario
];
$pageTitle = $pageTitles[$currentPage] ?? 'PetMatch';

// Verificar si el usuario está logueado
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - PetMatch</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="PetMatch" height="70" class="d-inline-block align-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>pages/home.php">Inicio</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <?php if ($userRole === 'giver'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/pets/create.php">Publicar mascota</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>pages/pets/list.php">Mis mascotas</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>pages/messages/inbox.php">Mensajes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>pages/favorites.php">Favoritos</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo $_SESSION['user_avatar'] ?? 'default.png'; ?>" 
                                     class="rounded-circle" width="30" height="30" alt="Avatar">
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/profile/view.php">Perfil</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/profile/edit.php">Editar perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/logout.php">Cerrar sesión</a></li>
                            </ul>
                        </li>
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>pages/notifications.php">
                                <i class="bi bi-bell"></i>
                                <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $_SESSION['unread_notifications']; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>pages/login.php">Iniciar sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>pages/register.php">Registrarse</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

<script>
    // Definir constantes globales para JavaScript
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
</script>