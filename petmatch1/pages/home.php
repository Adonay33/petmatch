<?php
require_once __DIR__ . '../../config.php';
require_once __DIR__ . '../../functions.php';
require_once __DIR__ . '../../includes/auth.php';

// Definir título de página
$pageTitle = "Inicio - PetMatch";

// Obtener mascotas disponibles
$conn = getDbConnection();
$query = "SELECT p.*, 
                 (SELECT image_path FROM pet_images WHERE pet_id = p.id AND is_primary = TRUE LIMIT 1) as primary_image,
                 u.username as owner_username
          FROM pets p
          JOIN users u ON p.user_id = u.id
          WHERE p.status = 'available'
          ORDER BY p.created_at DESC
          LIMIT 12";
$result = $conn->query($query);

// Verificar si hay resultados
if ($result === false) {
    die("Error en la consulta: " . $conn->error);
}

$pets = $result->fetch_all(MYSQLI_ASSOC);

// Incluir el encabezado
require_once __DIR__ . '../../includes/header.php';
?>

<div class="row mb-4">
  <div class="col-12">
    <div id="petCarousel" class="carousel slide" data-bs-ride="carousel">
        <!-- Indicadores -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#petCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#petCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#petCarousel" data-bs-slide-to="2"></button>
        </div>
        
        <!-- Slides con imágenes -->
        <div class="carousel-inner rounded-3 overflow-hidden shadow-lg">
            <!-- Slide 1 -->
            <div class="carousel-item active">
                <img src="<?php echo BASE_URL; ?>assets/images/imagen1.jpg" class="d-block w-100" alt="Perro jugando" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                    <h1 class="display-5 fw-bold">Encuentra a tu compañero perfecto</h1>
                    <p class="fs-5">PetMatch conecta mascotas que necesitan un hogar con personas dispuestas a darles amor.</p>
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-primary btn-lg me-2">Regístrate</a>
                        <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn btn-outline-light btn-lg">Inicia sesión</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Slide 2 -->
            <div class="carousel-item">
                <img src="<?php echo BASE_URL; ?>assets/images/perro.webp" class="d-block w-100" alt="Gato en adopción" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                    <h1 class="display-5 fw-bold">Adopta, no compres</h1>
                    <p class="fs-5">Cada adopción cambia una vida. Descubre mascotas esperando por un hogar amoroso.</p>
                    <a href="<?php echo BASE_URL; ?>pages/pets/" class="btn btn-success btn-lg">Ver mascotas disponibles</a>
                </div>
            </div>
            
            <!-- Slide 3 -->
            <div class="carousel-item">
                <img src="<?php echo BASE_URL; ?>assets/images/gato.png" class="d-block w-100" alt="Conejo para adopción" style="height: 500px; object-fit: cover;">
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-4">
                    <h1 class="display-5 fw-bold">¿Tienes mascotas para dar en adopción?</h1>
                    <p class="fs-5">Ayúdanos a encontrarles un hogar responsable.</p>
                    <?php if (isLoggedIn() && isGiver()): ?>
                        <a href="<?php echo BASE_URL; ?>pages/pets/create.php" class="btn btn-warning btn-lg">Publicar mascota</a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-info btn-lg">Regístrate como dador</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Controles -->
        <button class="carousel-control-prev" type="button" data-bs-target="#petCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
            <span class="visually-hidden">Anterior</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#petCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
            <span class="visually-hidden">Siguiente</span>
        </button>
    </div>
</div>

<style>
    #petCarousel {
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .carousel-caption {
        bottom: 30%;
        left: 10%;
        right: 10%;
        padding-top: 20px;
        padding-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .carousel-item img {
            height: 400px !important;
        }
        
        .carousel-caption {
            bottom: 20%;
        }
        
        .display-5 {
            font-size: 1.8rem;
        }
        
        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
    }
</style>
</div>

<h2 class="mb-4">Mascotas disponibles</h2>

<div class="row">
    <?php if (empty($pets)): ?>
        <div class="col-12">
            <div class="alert alert-info">No hay mascotas disponibles en este momento. Vuelve más tarde.</div>
        </div>
    <?php else: ?>
        <?php foreach ($pets as $pet): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="<?php echo BASE_URL; ?>uploads/pets/<?php echo !empty($pet['primary_image']) ? htmlspecialchars($pet['primary_image']) : 'default-pet.jpg'; ?>" 
                         class="card-img-top pet-card-img" 
                         alt="<?php echo htmlspecialchars($pet['name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($pet['name']); ?></h5>
                        <p class="card-text">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($pet['species'] === 'dog' ? 'Perro' : ($pet['species'] === 'cat' ? 'Gato' : 'Otro')); ?></span>
                            <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($pet['size'])); ?></span>
                            <span class="badge bg-warning"><?php echo htmlspecialchars($pet['age']); ?> años</span>
                        </p>
                        <p class="card-text"><?php echo substr(htmlspecialchars($pet['description']), 0, 100); ?>...</p>
                        <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($pet['city']); ?></small></p>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $pet['id']; ?>" class="btn btn-primary">Ver detalles</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($pets)): ?>
    <div class="text-center mt-4">
        <a href="<?php echo BASE_URL; ?>pages/pets/list.php" class="btn btn-outline-primary">Ver todas las mascotas</a>
    </div>
<?php endif; ?>

<?php
// Incluir el pie de página
require_once __DIR__ . '../../includes/footer.php';
?>