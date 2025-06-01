<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

// Verificar si se está viendo el perfil propio o de otro usuario
$viewingOwnProfile = false;
$profileId = $_GET['id'] ?? $_SESSION['user_id'] ?? null;

if (!$profileId) {
    $_SESSION['flash_message'] = "Perfil no encontrado.";
    $_SESSION['flash_type'] = "danger";
    redirect('pages/home.php');
}

// Obtener información del perfil
$user = getUserById($profileId);

if (!$user) {
    $_SESSION['flash_message'] = "Perfil no encontrado.";
    $_SESSION['flash_type'] = "danger";
    redirect('pages/home.php');
}

// Verificar si es el perfil propio
if (isLoggedIn() && $_SESSION['user_id'] == $user['id']) {
    $viewingOwnProfile = true;
    $pageTitle = "Mi Perfil - PetMatch";
} else {
    $pageTitle = "Perfil de " . $user['username'] . " - PetMatch";
}

// Obtener mascotas publicadas por este usuario (si es dador)
$pets = [];
if ($user['role'] === 'giver') {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT p.*, pi.image_path as primary_image 
                           FROM pets p 
                           LEFT JOIN pet_images pi ON p.id = pi.pet_id AND pi.is_primary = TRUE 
                           WHERE p.user_id = ? AND p.status = 'available'
                           ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pets = $result->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo htmlspecialchars($user['avatar'] ?? 'default.png'); ?>" 
                     class="rounded-circle mb-3" width="150" height="150" alt="<?php echo htmlspecialchars($user['username']); ?>">
                
                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="text-muted"><?php echo htmlspecialchars($user['role'] === 'giver' ? 'Dador' : 'Adoptador'); ?></p>
                
                <?php if (!empty($user['full_name'])): ?>
                    <p><i class="bi bi-person"></i> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($user['city'])): ?>
                    <p><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($user['city']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($user['email']) && $viewingOwnProfile): ?>
                    <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($user['phone'])): ?>
                    <p><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                <?php endif; ?>
                
                <?php if ($viewingOwnProfile): ?>
                    <a href="<?php echo BASE_URL; ?>pages/profile/edit.php" class="btn btn-primary mt-2">Editar perfil</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>pages/messages/conversation.php?user_id=<?php echo $user['id']; ?>" class="btn btn-primary mt-2">Enviar mensaje</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($user['address']) && $viewingOwnProfile): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Dirección</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($user['address'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <?php if (!empty($user['bio'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Biografía</h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($user['role'] === 'giver'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mascotas publicadas</h5>
                    <?php if ($viewingOwnProfile): ?>
                        <a href="<?php echo BASE_URL; ?>pages/pets/create.php" class="btn btn-sm btn-primary">Publicar nueva</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($pets)): ?>
                        <div class="alert alert-info">
                            <?php echo $viewingOwnProfile ? 'No has publicado ninguna mascota aún.' : 'Este usuario no tiene mascotas publicadas.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($pets as $pet): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <img src="<?php echo BASE_URL; ?>uploads/pets/<?php echo $pet['primary_image'] ?? 'default-pet.jpg'; ?>" 
                                             class="card-img-top pet-card-img" 
                                             alt="<?php echo htmlspecialchars($pet['name']); ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($pet['name']); ?></h5>
                                            <p class="card-text">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($pet['species'] === 'dog' ? 'Perro' : 'Gato'); ?></span>
                                                <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($pet['size'])); ?></span>
                                            </p>
                                            <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($pet['city']); ?></small></p>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-primary">Ver detalles</a>
                                            <?php if ($viewingOwnProfile): ?>
                                                <a href="<?php echo BASE_URL; ?>pages/pets/edit.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '../../../includes/footer.php';
?>