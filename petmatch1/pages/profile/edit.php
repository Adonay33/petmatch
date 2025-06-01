<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

if (!isLoggedIn()) {
    $_SESSION['flash_message'] = "Debes iniciar sesión para editar tu perfil.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/login.php');
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId);
$pageTitle = "Editar perfil - PetMatch";

$errors = [];
$formData = [
    'full_name' => $user['full_name'],
    'username' => $user['username'],
    'email' => $user['email'],
    'bio' => $user['bio'],
    'city' => $user['city'],
    'address' => $user['address'],
    'phone' => $user['phone']
];

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name' => sanitizeInput($_POST['full_name']),
        'username' => sanitizeInput($_POST['username']),
        'email' => sanitizeInput($_POST['email']),
        'bio' => sanitizeInput($_POST['bio']),
        'city' => sanitizeInput($_POST['city']),
        'address' => sanitizeInput($_POST['address']),
        'phone' => sanitizeInput($_POST['phone'])
    ];
    
    // Validaciones
    if (empty($formData['full_name'])) {
        $errors[] = "El nombre completo es requerido.";
    }
    
    if (empty($formData['username'])) {
        $errors[] = "El nombre de usuario es requerido.";
    } elseif (strlen($formData['username']) < 4) {
        $errors[] = "El nombre de usuario debe tener al menos 4 caracteres.";
    }
    
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Por favor ingresa un email válido.";
    }
    
    // Verificar si el username o email ya existen (excluyendo al usuario actual)
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $formData['username'], $formData['email'], $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "El nombre de usuario o email ya está en uso por otra cuenta.";
    }
    
    // Procesar avatar si se subió
    $avatarFilename = $user['avatar'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['avatar'], UPLOAD_AVATAR_DIR, 'avatar_');
        
        if ($uploadResult['success']) {
            $avatarFilename = $uploadResult['filename'];
            
            // Eliminar el avatar anterior si no es el predeterminado
            if ($user['avatar'] !== 'default.png' && file_exists(UPLOAD_AVATAR_DIR . $user['avatar'])) {
                unlink(UPLOAD_AVATAR_DIR . $user['avatar']);
            }
        } else {
            $errors[] = $uploadResult['error'];
        }
    }
    
    // Si no hay errores, actualizar el perfil
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET 
                               full_name = ?, username = ?, email = ?, bio = ?, 
                               city = ?, address = ?, phone = ?, avatar = ?, 
                               updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ?");
        $stmt->bind_param("ssssssssi", 
            $formData['full_name'], $formData['username'], $formData['email'], 
            $formData['bio'], $formData['city'], $formData['address'], 
            $formData['phone'], $avatarFilename, $userId);
        
        if ($stmt->execute()) {
            // Actualizar datos de sesión
            $_SESSION['user_name'] = $formData['username'];
            $_SESSION['user_avatar'] = $avatarFilename;
            
            $_SESSION['flash_message'] = "Perfil actualizado con éxito!";
            $_SESSION['flash_type'] = "success";
            redirect('pages/profile/view.php');
        } else {
            $errors[] = "Error al actualizar el perfil. Por favor intenta nuevamente.";
        }
    }
}

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Editar perfil</h3>
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
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nombre de usuario</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Biografía</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($formData['bio']); ?></textarea>
                        <div class="form-text">Cuéntanos un poco sobre ti.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">Ciudad</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($formData['city']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($formData['phone']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección (opcional)</label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($formData['address']); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="avatar" class="form-label">Foto de perfil</label>
                        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                        <div class="form-text">Imagen cuadrada recomendada (máx. 5MB).</div>
                        
                        <?php if (!empty($user['avatar'])): ?>
                            <div class="mt-2">
                                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                     class="rounded-circle" width="100" height="100" alt="Avatar actual">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="<?php echo BASE_URL; ?>pages/profile/view.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '../../../includes/footer.php';
?>