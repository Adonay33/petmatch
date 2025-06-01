<?php
function isPetFavorite($userId, $petId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $userId, $petId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function toggleFavoritePet($userId, $petId) {
    $conn = getDbConnection();
    
    // Verificar si ya es favorito
    $isFavorite = isPetFavorite($userId, $petId);
    
    if ($isFavorite) {
        // Eliminar de favoritos
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND pet_id = ?");
        $stmt->bind_param("ii", $userId, $petId);
        $result = $stmt->execute();
        $message = "Mascota eliminada de favoritos";
    } else {
        // Agregar a favoritos
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, pet_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $petId);
        $result = $stmt->execute();
        $message = "Mascota agregada a favoritos";
    }
    
    return [
        'success' => $result,
        'is_favorite' => !$isFavorite,
        'message' => $message
    ];
}

function deletePet($petId, $userId) {
    $conn = getDbConnection();
    $conn->begin_transaction();
    
    try {
        // 1. Obtener imágenes para eliminarlas del sistema de archivos
        $stmt = $conn->prepare("SELECT image_path FROM pet_images WHERE pet_id = ?");
        $stmt->bind_param("i", $petId);
        $stmt->execute();
        $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // 2. Eliminar registros relacionados
        $tables = ['favorites', 'messages', 'pet_images', 'notifications'];
        foreach ($tables as $table) {
            $query = "DELETE FROM $table WHERE ";
            if ($table === 'messages' || $table === 'notifications') {
                $query .= "pet_id = ?";
            } else {
                $query .= "pet_id = ?";
            }
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $petId);
            $stmt->execute();
        }
        
        // 3. Eliminar la mascota
        $stmt = $conn->prepare("DELETE FROM pets WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $petId, $userId);
        $deleted = $stmt->execute();
        
        if ($deleted && $conn->affected_rows > 0) {
            // Eliminar archivos de imágenes
            foreach ($images as $image) {
                $filePath = UPLOAD_PET_DIR . $image['image_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $conn->commit();
            return ['success' => true, 'message' => 'Mascota eliminada correctamente'];
        } else {
            $conn->rollback();
            return ['success' => false, 'message' => 'No se pudo eliminar la mascota'];
        }
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Error al eliminar la mascota: ' . $e->getMessage()];
    }
}
?>