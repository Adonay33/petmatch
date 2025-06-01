<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';

if (!isLoggedIn() || !isAdopter()) {
    $_SESSION['flash_message'] = "No tienes permiso para acceder a esta página.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/home.php');
}

$pageTitle = "Mis favoritos - PetMatch";

// Obtener mascotas favoritas del usuario
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT p.*, pi.image_path as primary_image, u.username as owner_username
                       FROM favorites f
                       JOIN pets p ON f.pet_id = p.id
                       LEFT JOIN pet_images pi ON p.id = pi.pet_id AND pi.is_primary = TRUE
                       JOIN users u ON p.user_id = u.id
                       WHERE f.user_id = ? AND p.status = 'available'
                       ORDER BY f.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>Mis mascotas favoritas</h1>
    </div>
</div>

<div class="row">
    <?php if (empty($favorites)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                No tienes mascotas favoritas aún. Marca como favoritas las mascotas que te interesen.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($favorites as $pet): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="<?php echo BASE_URL; ?>uploads/pets/<?php echo $pet['primary_image'] ?? 'default-pet.jpg'; ?>" 
                         class="card-img-top pet-card-img" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($pet['name']); ?></h5>
                        <p class="card-text">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($pet['species'] === 'dog' ? 'Perro' : 'Gato'); ?></span>
                            <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($pet['size'])); ?></span>
                            <span class="badge bg-warning"><?php echo htmlspecialchars($pet['age']); ?> años</span>
                        </p>
                        <p class="card-text"><small class="text-muted">Publicado por: <?php echo htmlspecialchars($pet['owner_username']); ?></small></p>
                    </div>
                    <!-- En tu archivo favorites.php -->
<div class="card-footer bg-white">
    <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary">Ver detalles</a>
    <form action="<?php echo BASE_URL; ?>pages/pets/toggle_favorite.php" method="post" class="d-inline">
        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="redirect" value="favorites.php">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-heart-fill"></i> Quitar
        </button>
    </form>
</div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '../../includes/footer.php';
?>