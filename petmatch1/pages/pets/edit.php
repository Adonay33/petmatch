<?php
require_once __DIR__ . '../../../config.php';
require_once __DIR__ . '../../../functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = "Mascota no encontrada.";
    $_SESSION['flash_type'] = "danger";
    redirect('pages/pets/list.php');
}

$petId = (int)$_GET['id'];
$pet = getPetById($petId);

if (!$pet) {
    $_SESSION['flash_message'] = "Mascota no encontrada.";
    $_SESSION['flash_type'] = "danger";
    redirect('pages/pets/list.php');
}

// Verificar que el usuario sea el dueño de la mascota
if (!isLoggedIn() || $_SESSION['user_id'] != $pet['user_id']) {
    $_SESSION['flash_message'] = "No tienes permiso para editar esta mascota.";
    $_SESSION['flash_type'] = "warning";
    redirect('pages/home.php');
}

$pageTitle = "Editar " . $pet['name'] . " - PetMatch";

// Obtener imágenes de la mascota
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT * FROM pet_images WHERE pet_id = ? ORDER BY is_primary DESC");
$stmt->bind_param("i", $petId);
$stmt->execute();
$imagesResult = $stmt->get_result();
$images = $imagesResult->fetch_all(MYSQLI_ASSOC);

$errors = [];
$formData = [
    'name' => $pet['name'],
    'species' => $pet['species'],
    'breed' => $pet['breed'],
    'age' => $pet['age'],
    'size' => $pet['size'],
    'gender' => $pet['gender'],
    'description' => $pet['description'],
    'status' => $pet['status'],
    'city' => $pet['city'],
    'address' => $pet['address'],
    'latitude' => $pet['latitude'],
    'longitude' => $pet['longitude']
];

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => sanitizeInput($_POST['name']),
        'species' => sanitizeInput($_POST['species']),
        'breed' => sanitizeInput($_POST['breed']),
        'age' => sanitizeInput($_POST['age']),
        'size' => sanitizeInput($_POST['size']),
        'gender' => sanitizeInput($_POST['gender']),
        'description' => sanitizeInput($_POST['description']),
        'status' => sanitizeInput($_POST['status']),
        'city' => sanitizeInput($_POST['city']),
        'address' => sanitizeInput($_POST['address']),
        'latitude' => sanitizeInput($_POST['latitude']),
        'longitude' => sanitizeInput($_POST['longitude'])
    ];
    
    // Validaciones
    if (empty($formData['name'])) {
        $errors[] = "El nombre de la mascota es requerido.";
    }
    
    if (empty($formData['description']) || strlen($formData['description']) < 20) {
        $errors[] = "La descripción debe tener al menos 20 caracteres.";
    }
    
    if (empty($formData['city'])) {
        $errors[] = "La ciudad es requerida.";
    }
    
    // Procesar imágenes nuevas
    $newImages = [];
    if (isset($_FILES['new_images']) && is_array($_FILES['new_images']['name'])) {
        $imageCount = count($_FILES['new_images']['name']);
        
        if ($imageCount > 5) {
            $errors[] = "Solo puedes subir hasta 5 imágenes nuevas.";
        } else {
            for ($i = 0; $i < $imageCount; $i++) {
                if ($_FILES['new_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileInfo = [
                        'name' => $_FILES['new_images']['name'][$i],
                        'type' => $_FILES['new_images']['type'][$i],
                        'tmp_name' => $_FILES['new_images']['tmp_name'][$i],
                        'error' => $_FILES['new_images']['error'][$i],
                        'size' => $_FILES['new_images']['size'][$i]
                    ];
                    
                    $uploadResult = handleFileUpload($fileInfo, UPLOAD_PET_DIR, 'pet_');
                    
                    if ($uploadResult['success']) {
                        $newImages[] = $uploadResult['filename'];
                    } else {
                        $errors[] = "Error al subir la nueva imagen " . ($i + 1) . ": " . $uploadResult['error'];
                    }
                }
            }
        }
    }
    
    // Procesar imágenes existentes (para eliminar o cambiar principal)
    $existingImages = [];
    $primaryImageId = null;
    
    if (isset($_POST['existing_images']) && is_array($_POST['existing_images'])) {
        foreach ($_POST['existing_images'] as $imageId => $imageData) {
            $existingImages[$imageId] = [
                'keep' => isset($imageData['keep']),
                'primary' => isset($imageData['primary'])
            ];
            
            if (isset($imageData['primary'])) {
                $primaryImageId = $imageId;
            }
        }
    }
    
    // Si no hay errores, actualizar la mascota
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Actualizar información de la mascota
            $stmt = $conn->prepare("UPDATE pets SET 
                                  name = ?, species = ?, breed = ?, age = ?, size = ?, 
                                  gender = ?, description = ?, status = ?, city = ?, 
                                  address = ?, latitude = ?, longitude = ?, 
                                  updated_at = CURRENT_TIMESTAMP 
                                  WHERE id = ?");
            $stmt->bind_param("sssissssssddi", 
                $formData['name'], $formData['species'], $formData['breed'], 
                $formData['age'], $formData['size'], $formData['gender'], 
                $formData['description'], $formData['status'], $formData['city'], 
                $formData['address'], $formData['latitude'], $formData['longitude'], 
                $petId);
            $stmt->execute();
            
            // Manejar imágenes existentes
            foreach ($existingImages as $imageId => $options) {
                if ($options['keep']) {
                    // Actualizar si es principal
                    $isPrimary = ($imageId == $primaryImageId) ? 1 : 0;
                    $stmt = $conn->prepare("UPDATE pet_images SET is_primary = ? WHERE id = ?");
                    $stmt->bind_param("ii", $isPrimary, $imageId);
                    $stmt->execute();
                } else {
                    // Eliminar la imagen
                    $stmt = $conn->prepare("SELECT image_path FROM pet_images WHERE id = ?");
                    $stmt->bind_param("i", $imageId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $image = $result->fetch_assoc();
                    
                    if ($image && file_exists(UPLOAD_PET_DIR . $image['image_path'])) {
                        unlink(UPLOAD_PET_DIR . $image['image_path']);
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM pet_images WHERE id = ?");
                    $stmt->bind_param("i", $imageId);
                    $stmt->execute();
                }
            }
            
            // Agregar nuevas imágenes
            if (!empty($newImages)) {
                // Verificar si ya hay una imagen principal
                $hasPrimary = false;
                if ($primaryImageId || $conn->query("SELECT 1 FROM pet_images WHERE pet_id = $petId AND is_primary = 1")->num_rows > 0) {
                    $hasPrimary = true;
                }
                
                foreach ($newImages as $filename) {
                    $isPrimary = !$hasPrimary;
                    $stmt = $conn->prepare("INSERT INTO pet_images (pet_id, image_path, is_primary) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $petId, $filename, $isPrimary);
                    $stmt->execute();
                    
                    if ($isPrimary) $hasPrimary = true;
                }
            }
            
            $conn->commit();
            
            $_SESSION['flash_message'] = "Mascota actualizada con éxito!";
            $_SESSION['flash_type'] = "success";
            redirect('pages/pets/view.php?id=' . $petId);
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error al actualizar la mascota. Por favor intenta nuevamente.";
        }
    }
}

require_once __DIR__ . '../../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Editar <?php echo htmlspecialchars($pet['name']); ?></h3>
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
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="species" class="form-label">Especie</label>
                            <select class="form-select" id="species" name="species" required>
                                <option value="dog" <?php echo $formData['species'] === 'dog' ? 'selected' : ''; ?>>Perro</option>
                                <option value="cat" <?php echo $formData['species'] === 'cat' ? 'selected' : ''; ?>>Gato</option>
                                <option value="bird" <?php echo $formData['species'] === 'bird' ? 'selected' : ''; ?>>Ave</option>
                                <option value="other" <?php echo $formData['species'] === 'other' ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="breed" class="form-label">Raza (opcional)</label>
                            <input type="text" class="form-control" id="breed" name="breed" 
                                   value="<?php echo htmlspecialchars($formData['breed']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="age" class="form-label">Edad (años)</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" max="30" 
                                   value="<?php echo htmlspecialchars($formData['age']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="size" class="form-label">Tamaño</label>
                            <select class="form-select" id="size" name="size" required>
                                <option value="small" <?php echo $formData['size'] === 'small' ? 'selected' : ''; ?>>Pequeño</option>
                                <option value="medium" <?php echo $formData['size'] === 'medium' ? 'selected' : ''; ?>>Mediano</option>
                                <option value="large" <?php echo $formData['size'] === 'large' ? 'selected' : ''; ?>>Grande</option>
                                <option value="extra_large" <?php echo $formData['size'] === 'extra_large' ? 'selected' : ''; ?>>Muy grande</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Género</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_male" 
                                       value="male" <?php echo $formData['gender'] === 'male' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_male">Macho</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_female" 
                                       value="female" <?php echo $formData['gender'] === 'female' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_female">Hembra</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="gender_unknown" 
                                       value="unknown" <?php echo $formData['gender'] === 'unknown' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="gender_unknown">Desconocido</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="available" <?php echo $formData['status'] === 'available' ? 'selected' : ''; ?>>Disponible</option>
                                <option value="pending" <?php echo $formData['status'] === 'pending' ? 'selected' : ''; ?>>En proceso de adopción</option>
                                <option value="adopted" <?php echo $formData['status'] === 'adopted' ? 'selected' : ''; ?>>Adoptado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        <div class="form-text">Describe su personalidad, necesidades especiales, etc.</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">Ciudad</label>
                            <input type="text" class="form-control" id="city" name="city" 
                                   value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">Dirección (opcional)</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($formData['address']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Ubicación en mapa</label>
                        <div id="map" style="height: 300px; width: 100%;" class="mb-2"></div>
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($formData['latitude']); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($formData['longitude']); ?>">
                        <div class="form-text">Arrastra el marcador para actualizar la ubicación.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Imágenes actuales</label>
                        <div class="row">
                            <?php if (empty($images)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">No hay imágenes para esta mascota.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($images as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="<?php echo BASE_URL; ?>uploads/pets/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 class="card-img-top" alt="Imagen de mascota">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="existing_images[<?php echo $image['id']; ?>][keep]" 
                                                           id="keep_<?php echo $image['id']; ?>" checked>
                                                    <label class="form-check-label" for="keep_<?php echo $image['id']; ?>">Mantener</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="existing_images[<?php echo $image['id']; ?>][primary]" 
                                                           id="primary_<?php echo $image['id']; ?>" 
                                                           value="1" <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="primary_<?php echo $image['id']; ?>">Principal</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <label for="new_images" class="form-label">Agregar nuevas imágenes (máx. 5)</label>
                        <input type="file" class="form-control" id="new_images" name="new_images[]" multiple accept="image/*">
                        <div class="form-text">Puedes agregar hasta 5 imágenes adicionales.</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Guardar cambios</button>
                        <a href="<?php echo BASE_URL; ?>pages/pets/view.php?id=<?php echo $petId; ?>" class="btn btn-outline-secondary">Cancelar</a>
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