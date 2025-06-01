<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';

// Redirigir si ya está logueado
if (isLoggedIn()) {
    $redirect = $_SESSION['redirect_url'] ?? 'pages/home.php';
    unset($_SESSION['redirect_url']);
    header("Location: " . BASE_URL . $redirect);
    exit();
}

$pageTitle = "Iniciar sesión - PetMatch";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, username, password, role, avatar FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_avatar'] = $user['avatar'];
            
            $redirect = $_SESSION['redirect_url'] ?? 'pages/home.php';
            unset($_SESSION['redirect_url']);
            
            $_SESSION['flash_message'] = "¡Bienvenido de nuevo, " . $user['username'] . "!";
            $_SESSION['flash_type'] = "success";
            header("Location: " . BASE_URL . $redirect);
            exit();
        }
    }
    
    $error = "Credenciales incorrectas. Por favor intenta nuevamente.";
}

require_once __DIR__ . '../../includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card mt-5">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="PetMatch" width="100">
                        <h3 class="mt-3">Iniciar sesión</h3>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Usuario o Email</label>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Ingresar</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo BASE_URL; ?>pages/register.php">¿No tienes cuenta? Regístrate</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '../../includes/footer.php';
?>