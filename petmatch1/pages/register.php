<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';

$pageTitle = "Registro - PetMatch";

// Si el usuario ya está logueado, redirigir a home
if (isLoggedIn()) {
    redirect('pages/home.php');
}

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    $role = sanitizeInput($_POST['role']);
    $city = sanitizeInput($_POST['city']);
    
    // Validaciones básicas
    $errors = [];
    
    if (empty($fullName)) {
        $errors[] = "El nombre completo es requerido.";
    }
    
    if (empty($username)) {
        $errors[] = "El nombre de usuario es requerido.";
    } elseif (strlen($username) < 4) {
        $errors[] = "El nombre de usuario debe tener al menos 4 caracteres.";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Por favor ingresa un email válido.";
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Las contraseñas no coinciden.";
    }
    
    if (empty($role) || !in_array($role, ['adopter', 'giver'])) {
        $errors[] = "Por favor selecciona un rol válido.";
    }
    
    // Verificar si el usuario o email ya existen
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "El nombre de usuario o email ya está en uso.";
    }
    
    // Si no hay errores, proceder con el registro
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $defaultAvatar = 'default.png';
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, city, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $email, $hashedPassword, $fullName, $role, $city, $defaultAvatar);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $username;
            $_SESSION['user_role'] = $role;
            $_SESSION['user_avatar'] = $defaultAvatar;
            
            $_SESSION['flash_message'] = "¡Registro exitoso! Bienvenido a PetMatch.";
            $_SESSION['flash_type'] = "success";
            
            // Redirigir según el rol
            redirect($role === 'giver' ? 'pages/pets/create.php' : 'pages/home.php');
        } else {
            $errors[] = "Error al registrar el usuario. Por favor intenta nuevamente.";
        }
    }
}

require_once __DIR__ . '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Regístrate en PetMatch</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nombre de usuario</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="city" class="form-label">Ciudad</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">¿Qué deseas hacer en PetMatch?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="role_adopter" value="adopter" checked>
                            <label class="form-check-label" for="role_adopter">
                                Quiero adoptar una mascota
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="role" id="role_giver" value="giver">
                            <label class="form-check-label" for="role_giver">
                                Quiero dar en adopción una mascota
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Registrarse</button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <p>¿Ya tienes una cuenta? <a href="<?php echo BASE_URL; ?>pages/login.php">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '../../includes/footer.php';
?>