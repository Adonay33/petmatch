<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

if (!isLoggedIn() || !isGiver()) {
    $_SESSION['flash_message'] = "No tienes permiso para acceder a esta página.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/home.php');
}

$pageTitle = "Mis mascotas - PetMatch";

// Obtener mascotas del usuario actual
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT p.*, pi.image_path as primary_image 
                       FROM pets p 
                       LEFT JOIN pet_images pi ON p.id = pi.pet_id AND pi.is_primary = TRUE 
                       WHERE p.user_id = ?
                       ORDER BY p.status ASC, p.created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$pets = $result->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Mis mascotas</h1>
            <a href="<?php echo BASE_URL; ?>pages/pets/create.php" class="btn btn-primary">Publicar nueva mascota</a>
        </div>
    </div>
</div>

<div class="row">
    <?php if (empty($pets)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                No has publicado ninguna mascota aún. <a href="<?php echo BASE_URL; ?>pages/pets/create.php">Publica tu primera mascota</a>.
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Especie</th>
                            <th>Tamaño</th>
                            <th>Ubicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pets as $pet): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo BASE_URL; ?>uploads/pets/<?php echo $pet['primary_image'] ?? 'default-pet.jpg'; ?>" 
                                         class="rounded" width="50" height="50" alt="<?php echo htmlspecialchars($pet['name']); ?>">
                                </td>
                                <td><?php echo htmlspecialchars($pet['name']); ?></td>
                                <td>
                                    <span class="badge <?php echo $pet['status'] === 'available' ? 'bg-success' : ($pet['status'] === 'pending' ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo $pet['status'] === 'available' ? 'Disponible' : ($pet['status'] === 'pending' ? 'En proceso' : 'Adoptado'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($pet['species'] === 'dog' ? 'Perro' : ($pet['species'] === 'cat' ? 'Gato' : 'Otro')); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($pet['size'])); ?></td>
                                <td><?php echo htmlspecialchars($pet['city']); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>pages/pets/edit.php?id=<?php echo $pet['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <a href="<?php echo BASE_URL; ?>pages/pets/delete-pet.php?id=<?php echo $pet['id']; ?>" 
   class="btn btn-sm btn-danger"
   onclick="return confirm('¿Seguro que quieres eliminar esta mascota?')">
   <i class="bi bi-trash"></i> Eliminar
</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$additionalScripts = ['pets-list.js'];
require_once __DIR__ . '../../../includes/footer.php';
?>