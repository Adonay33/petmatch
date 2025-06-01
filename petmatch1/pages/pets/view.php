<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

// Verificar ID de mascota
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "Mascota no encontrada.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

$petId = (int)$_GET['id'];
$conn = getDbConnection();

// Obtener información de la mascota
$stmt = $conn->prepare("
    SELECT p.*, 
           u.id AS owner_id, u.username AS owner_username, 
           u.avatar AS owner_avatar, u.email AS owner_email,
           u.phone AS owner_phone, u.city AS owner_city,
           u.full_name AS owner_name
    FROM pets p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $petId);
$stmt->execute();
$result = $stmt->get_result();
$pet = $result->fetch_assoc();

if (!$pet) {
    $_SESSION['flash_message'] = "Mascota no encontrada.";
    $_SESSION['flash_type'] = "danger";
    header("Location: " . BASE_URL . "pages/home.php");
    exit;
}

// Obtener imágenes de la mascota
$stmt = $conn->prepare("SELECT * FROM pet_images WHERE pet_id = ?");
$stmt->bind_param("i", $petId);
$stmt->execute();
$imagesResult = $stmt->get_result();
$images = $imagesResult->fetch_all(MYSQLI_ASSOC);

// Si no hay imágenes, usar una por defecto
if (count($images) === 0) {
    $images[] = ['image_path' => 'default-pet.jpg', 'is_primary' => 1];
}

// Verificar si el usuario actual puede contactar al dueño
$canContact = false;
if (isLoggedIn() && $_SESSION['user_id'] != $pet['owner_id']) {
    $canContact = true;
}

$pageTitle = $pet['name'] . " - PetMatch";

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="container my-4">
    <!-- Botón para volver atrás -->
    <div class="mb-4">
        <a href="<?= BASE_URL ?>pages/home.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la búsqueda
        </a>
    </div>
    
    <div class="row">
        <!-- Galería de imágenes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div id="petGallery" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner rounded-top">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="<?= BASE_URL ?>uploads/pets/<?= $image['image_path'] ?>" 
                                         class="d-block w-100" 
                                         alt="<?= htmlspecialchars($pet['name']) ?> - Imagen <?= $index + 1 ?>"
                                         style="height: 400px; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#petGallery" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#petGallery" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Siguiente</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Miniaturas para navegación -->
                    <?php if (count($images) > 1): ?>
                        <div class="d-flex justify-content-center py-2 bg-light">
                            <div class="d-flex overflow-auto" style="max-width: 100%;">
                                <?php foreach ($images as $index => $image): ?>
                                    <button type="button" data-bs-target="#petGallery" data-bs-slide-to="<?= $index ?>" 
                                            class="btn p-1 <?= $index === 0 ? 'active' : '' ?>"
                                            aria-current="<?= $index === 0 ? 'true' : 'false' ?>" 
                                            aria-label="Slide <?= $index + 1 ?>">
                                        <img src="<?= BASE_URL ?>uploads/pets/<?= $image['image_path'] ?>" 
                                             class="thumbnail-img" 
                                             alt="Miniatura" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Información principal -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h1 class="h3 mb-0"><?= htmlspecialchars($pet['name']) ?></h1>
                </div>
                <div class="card-body">
                    <!-- Etiquetas de características -->
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-info">
                            <i class="bi bi-tag"></i> <?= htmlspecialchars(ucfirst($pet['species'])) ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="bi bi-rulers"></i> <?= htmlspecialchars(ucfirst($pet['size'])) ?>
                        </span>
                        <span class="badge bg-warning">
                            <i class="bi bi-calendar"></i> <?= $pet['age'] ?> años
                        </span>
                        <span class="badge bg-success">
                            <i class="bi bi-gender-<?= $pet['gender'] === 'male' ? 'male' : ($pet['gender'] === 'female' ? 'female' : 'ambiguous') ?>"></i> 
                            <?= htmlspecialchars(ucfirst($pet['gender'])) ?>
                        </span>
                    </div>
                    
                    <!-- Descripción -->
                    <div class="mb-4">
                        <h4><i class="bi bi-card-text me-2"></i> Descripción</h4>
                        <p class="lead"><?= nl2br(htmlspecialchars($pet['description'])) ?></p>
                    </div>
                    
                    <!-- Detalles adicionales -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h5><i class="bi bi-info-circle me-2"></i> Detalles</h5>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item bg-light border-0 px-0">
                                            <strong>Raza:</strong> <?= $pet['breed'] ? htmlspecialchars($pet['breed']) : 'No especificada' ?>
                                        </li>
                                        <li class="list-group-item bg-light border-0 px-0">
                                            <strong>Estado:</strong> 
                                            <span class="badge bg-<?= $pet['status'] === 'available' ? 'success' : ($pet['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($pet['status']) ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item bg-light border-0 px-0">
                                            <strong>Publicado:</strong> <?= date('d/m/Y', strtotime($pet['created_at'])) ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h5><i class="bi bi-geo-alt me-2"></i> Ubicación</h5>
                                    <p class="mb-2">
                                        <i class="bi bi-geo"></i> <?= htmlspecialchars($pet['city']) ?>
                                    </p>
                                    
                                    <!-- Mapa -->
                                    <?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
                                        <div id="petMap" style="height: 150px; width: 100%;" class="mt-2 rounded border"></div>
                                        <input type="hidden" id="latitude" value="<?= $pet['latitude'] ?>">
                                        <input type="hidden" id="longitude" value="<?= $pet['longitude'] ?>">
                                        <input type="hidden" id="petName" value="<?= htmlspecialchars($pet['name']) ?>">
                                    <?php else: ?>
                                        <p class="text-muted">Ubicación no especificada</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="d-flex gap-2 mt-4">
                        <?php if ($canContact): ?>
                            <button class="btn btn-primary btn-lg flex-grow-1" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#contactModal">
                                <i class="bi bi-chat-dots me-1"></i> Contactar al dueño
                            </button>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="bi bi-person me-1"></i> Iniciar sesión para contactar
                            </a>
                        <?php endif; ?>
                        
               <?php if (isLoggedIn() && isAdopter()): ?>
    <?php
    $isFavorite = checkIfFavorite($_SESSION['user_id'], $pet['id']);
    $currentPath = '/pages/pets/view.php?id=' . $pet['id']; // Ruta relativa consistente
    ?>
    
    <form action="<?= BASE_URL ?>pages/pets/toggle_favorite.php" method="post" class="d-inline">
        <input type="hidden" name="pet_id" value="<?= $pet['id'] ?>">
        <input type="hidden" name="action" value="<?= $isFavorite ? 'remove' : 'add' ?>">
        <input type="hidden" name="redirect_path" value="<?= $currentPath ?>">
        
        <button type="submit" class="btn btn-outline-danger">
            <i class="bi bi-heart<?= $isFavorite ? '-fill' : '' ?>"></i>
            <?= $isFavorite ? ' Quitar favorito' : ' Añadir a favoritos' ?>
        </button>
    </form>
<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información del dueño -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i> Publicado por</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= BASE_URL ?>uploads/avatars/<?= $pet['owner_avatar'] ?: 'default.png' ?>" 
                             class="rounded-circle me-3" 
                             width="80" 
                             height="80"
                             alt="Avatar de <?= htmlspecialchars($pet['owner_username']) ?>">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($pet['owner_username']) ?></h5>
                            <p class="text-muted mb-0"><?= $pet['owner_name'] ? htmlspecialchars($pet['owner_name']) : 'Usuario PetMatch' ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h6><i class="bi bi-info-circle me-2"></i> Información de contacto</h6>
                        <ul class="list-group list-group-flush">
                            <?php if ($pet['owner_email']): ?>
                                <li class="list-group-item border-0 px-0">
                                    <i class="bi bi-envelope me-2"></i>
                                    <?= htmlspecialchars($pet['owner_email']) ?>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($pet['owner_phone']): ?>
                                <li class="list-group-item border-0 px-0">
                                    <i class="bi bi-telephone me-2"></i>
                                    <?= htmlspecialchars($pet['owner_phone']) ?>
                                </li>
                            <?php endif; ?>
                            
                            <li class="list-group-item border-0 px-0">
                                <i class="bi bi-geo-alt me-2"></i>
                                <?= $pet['owner_city'] ? htmlspecialchars($pet['owner_city']) : 'Ubicación no especificada' ?>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>pages/profile/view.php?id=<?= $pet['owner_id'] ?>" 
                           class="btn btn-outline-primary">
                            Ver perfil completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recomendaciones de mascotas similares -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-stars me-2"></i> Mascotas similares</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        // Obtener mascotas similares (misma especie)
                        $stmt = $conn->prepare("
                            SELECT p.id, p.name, pi.image_path 
                            FROM pets p
                            LEFT JOIN pet_images pi ON p.id = pi.pet_id AND pi.is_primary = 1
                            WHERE p.species = ? AND p.id != ? AND p.status = 'available'
                            ORDER BY RAND() 
                            LIMIT 2
                        ");
                        $stmt->bind_param("si", $pet['species'], $petId);
                        $stmt->execute();
                        $similarPets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        if (count($similarPets) > 0):
                            foreach ($similarPets as $similarPet):
                        ?>
                        <div class="col-md-6 mb-3">
                            <a href="<?= BASE_URL ?>pages/pets/view.php?id=<?= $similarPet['id'] ?>" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm">
                                    <img src="<?= BASE_URL ?>uploads/pets/<?= $similarPet['image_path'] ?: 'default-pet.jpg' ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($similarPet['name']) ?>"
                                         style="height: 120px; object-fit: cover;">
                                    <div class="card-body">
                                        <h6 class="card-title text-dark"><?= htmlspecialchars($similarPet['name']) ?></h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted">No hay mascotas similares disponibles en este momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-2">
                        <a href="<?= BASE_URL ?>pages/home.php" class="btn btn-outline-primary btn-sm">
                            Ver más mascotas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Contacto -->
<?php if ($canContact): ?>
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Contactar a <?= htmlspecialchars($pet['owner_username']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>pages/pets/send_message.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="pet_id" value="<?= $petId ?>">
                    <input type="hidden" name="receiver_id" value="<?= $pet['owner_id'] ?>">
                    
                    <div class="mb-3">
                        <label for="messageText" class="form-label">Tu mensaje</label>
                        <textarea class="form-control" id="messageText" name="message" rows="4" required
                                  placeholder="Hola, estoy interesado en adoptar a <?= htmlspecialchars($pet['name']) ?>..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Tu mensaje será enviado al dueño de la mascota.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i> Enviar mensaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($pet['latitude']) && !empty($pet['longitude'])): ?>
<script>
    // Inicializar mapa para la mascota
    document.addEventListener('DOMContentLoaded', function() {
        const latitude = parseFloat(document.getElementById('latitude').value);
        const longitude = parseFloat(document.getElementById('longitude').value);
        const petName = document.getElementById('petName').value;
        
        const map = L.map('petMap').setView([latitude, longitude], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        
        L.marker([latitude, longitude]).addTo(map)
            .bindPopup(petName)
            .openPopup();
    });
</script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para mostrar notificaciones
    function showFlashMessage(message, type = 'success') {
        const flashDiv = document.createElement('div');
        flashDiv.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        flashDiv.style.zIndex = '1000';
        flashDiv.innerHTML = message;
        document.body.appendChild(flashDiv);
        
        setTimeout(() => flashDiv.remove(), 3000);
    }

    // Manejar clic en botones de favoritos
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.toggle-favorite')) {
            const button = e.target.closest('.toggle-favorite');
            const petId = button.getAttribute('data-pet-id');
            const icon = button.querySelector('i');
            const isFavorite = icon.classList.contains('bi-heart-fill');
            
            button.disabled = true;
            
            try {
                const response = await fetch('<?= BASE_URL ?>pages/pets/toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        pet_id: petId,
                        action: isFavorite ? 'remove' : 'add'
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Error en la respuesta del servidor');
                }

                if (data.success) {
                    // Actualizar icono y texto
                    icon.classList.toggle('bi-heart');
                    icon.classList.toggle('bi-heart-fill');
                    
                    // Actualizar texto del botón
                    const textSpan = button.querySelector('span');
                    if (textSpan) {
                        textSpan.textContent = data.action === 'add' ? ' Quitar' : ' Favorito';
                    }
                    
                    // Mostrar notificación
                    showFlashMessage(
                        data.action === 'add' 
                            ? '<i class="bi bi-heart-fill"></i> Añadido a favoritos' 
                            : '<i class="bi bi-heart"></i> Eliminado de favoritos'
                    );
                    
                    // Si estamos en la página de favoritos, recargar después de eliminar
                    if (data.action === 'remove' && window.location.pathname.includes('favorites.php')) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    throw new Error(data.message || 'Acción fallida');
                }
            } catch (error) {
                console.error('Error:', error);
                showFlashMessage(`<i class="bi bi-exclamation-triangle"></i> ${error.message || 'Error al procesar la solicitud'}`, 'danger');
            } finally {
                button.disabled = false;
            }
        }
    });
});
</script>
<?php
// Incluir Leaflet CSS y JS si hay mapa
if (!empty($pet['latitude']) && !empty($pet['longitude'])) {
    echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />';
    echo '<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>';
}

require_once __DIR__ . '../../../includes/footer.php';
?>