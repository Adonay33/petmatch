<?php
require_once __DIR__ . '../../../config.php';
$additionalScripts = ['map.js'];
require_once __DIR__ . '../../../functions.php';

$pageTitle = "Publicar mascota - PetMatch";

// Verificar que el usuario esté logueado y sea dador
if (!isLoggedIn() || !isGiver()) {
    $_SESSION['flash_message'] = "Debes ser un dador registrado para publicar mascotas.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/home.php');
}

$errors = [];
$petData = [
    'name' => '',
    'species' => 'dog',
    'breed' => '',
    'age' => '',
    'size' => 'medium',
    'gender' => 'unknown',
    'description' => '',
    'city' => '',
    'address' => ''
];

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $petData = [
        'name' => sanitizeInput($_POST['name']),
        'species' => sanitizeInput($_POST['species']),
        'breed' => sanitizeInput($_POST['breed']),
        'age' => sanitizeInput($_POST['age']),
        'size' => sanitizeInput($_POST['size']),
        'gender' => sanitizeInput($_POST['gender']),
        'description' => sanitizeInput($_POST['description']),
        'city' => sanitizeInput($_POST['city']),
        'address' => sanitizeInput($_POST['address']),
        'latitude' => sanitizeInput($_POST['latitude']),
        'longitude' => sanitizeInput($_POST['longitude'])
    ];
    
    // Validaciones
    if (empty($petData['name'])) {
        $errors[] = "El nombre de la mascota es requerido.";
    }
    
    if (empty($petData['description']) || strlen($petData['description']) < 20) {
        $errors[] = "La descripción debe tener al menos 20 caracteres.";
    }
    
    if (empty($petData['city'])) {
        $errors[] = "La ciudad es requerida.";
    }
    
    // Validar imágenes
    $uploadedImages = [];
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $imageCount = count($_FILES['images']['name']);
        
        if ($imageCount > 5) {
            $errors[] = "Solo puedes subir hasta 5 imágenes.";
        } else {
            for ($i = 0; $i < $imageCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];
                    
                    $uploadResult = handleFileUpload($fileInfo, UPLOAD_PET_DIR, 'pet_');
                    
                    if ($uploadResult['success']) {
                        $uploadedImages[] = $uploadResult['filename'];
                    } else {
                        $errors[] = "Error al subir la imagen " . ($i + 1) . ": " . $uploadResult['error'];
                    }
                }
            }
        }
    } else {
        $errors[] = "Debes subir al menos una imagen de la mascota.";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errors)) {
        $conn = getDbConnection();
        
        // Insertar la mascota
        $stmt = $conn->prepare("INSERT INTO pets (user_id, name, species, breed, age, size, gender, description, city, address, latitude, longitude) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssisssssdd", $_SESSION['user_id'], $petData['name'], $petData['species'], 
                         $petData['breed'], $petData['age'], $petData['size'], $petData['gender'], 
                         $petData['description'], $petData['city'], $petData['address'], 
                         $petData['latitude'], $petData['longitude']);
        
        if ($stmt->execute()) {
            $petId = $stmt->insert_id;
            
            // Insertar las imágenes
            $primarySet = false;
            foreach ($uploadedImages as $index => $filename) {
                $isPrimary = !$primarySet; // La primera imagen es la principal
                $stmt = $conn->prepare("INSERT INTO pet_images (pet_id, image_path, is_primary) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $petId, $filename, $isPrimary);
                $stmt->execute();
                
                if ($isPrimary) $primarySet = true;
            }
            
            $_SESSION['flash_message'] = "¡Mascota publicada con éxito!";
            $_SESSION['flash_type'] = "success";
            redirect('pages/pets/view.php?id=' . $petId);
        } else {
            $errors[] = "Error al guardar la mascota. Por favor intenta nuevamente.";
        }
    }
}

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Publicar nueva mascota</h3>
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
                            <label for="name" class="form-label">Nombre de la mascota</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($petData['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="species" class="form-label">Especie</label>
                            <select class="form-select" id="species" name="species" required>
                                <option value="dog" <?php echo $petData['species'] === 'dog' ? 'selected' : ''; ?>>Perro</option>
                                <option value="cat" <?php echo $petData['species'] === 'cat' ? 'selected' : ''; ?>>Gato</option>
                                <option value="bird" <?php echo $petData['species'] === 'bird' ? 'selected' : ''; ?>>Ave</option>
                                <option value="other" <?php echo $petData['species'] === 'other' ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="breed" class="form-label">Raza (opcional)</label>
                            <input type="text" class="form-control" id="breed" name="breed" value="<?php echo htmlspecialchars($petData['breed']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="age" class="form-label">Edad (años)</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" max="30" value="<?php echo htmlspecialchars($petData['age']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="size" class="form-label">Tamaño</label>
                            <select class="form-select" id="size" name="size" required>
                                <option value="small" <?php echo $petData['size'] === 'small' ? 'selected' : ''; ?>>Pequeño</option>
                                <option value="medium" <?php echo $petData['size'] === 'medium' ? 'selected' : ''; ?>>Mediano</option>
                                <option value="large" <?php echo $petData['size'] === 'large' ? 'selected' : ''; ?>>Grande</option>
                                <option value="extra_large" <?php echo $petData['size'] === 'extra_large' ? 'selected' : ''; ?>>Muy grande</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Género</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" <?php echo $petData['gender'] === 'male' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_male">Macho</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female" <?php echo $petData['gender'] === 'female' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_female">Hembra</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_unknown" value="unknown" <?php echo $petData['gender'] === 'unknown' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_unknown">Desconocido</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="images" class="form-label">Imágenes de la mascota (máx. 5)</label>
                            <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                            <div class="form-text">La primera imagen será la principal.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($petData['description']); ?></textarea>
                        <div class="form-text">Describe su personalidad, necesidades especiales, etc.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">Ciudad</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($petData['city']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Dirección (opcional)</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($petData['address']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
    <label class="form-label">Ubicación en El Salvador</label>
    <div id="map" style="height: 400px; border: 1px solid #ddd; border-radius: 4px;"></div>
    <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($petData['latitude']) ? htmlspecialchars($petData['latitude']) : '13.7942'; ?>">
    <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($petData['longitude']) ? htmlspecialchars($petData['longitude']) : '-88.8965'; ?>">
    <div class="form-text mt-2">
        <i class="bi bi-info-circle"></i> Arrastra el marcador a la ubicación exacta donde se encuentra la mascota.
    </div>
</div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Publicar mascota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$additionalScripts = ['map.js'];
require_once __DIR__ . '../../../includes/footer.php';
?>