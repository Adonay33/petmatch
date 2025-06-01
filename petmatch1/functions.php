<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db/connection.php';

// Funciones de ayuda generales
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function isGiver() {
    return getUserRole() === 'giver';
}

function isAdopter() {
    return getUserRole() === 'adopter';
}

// Funciones relacionadas con archivos
function handleFileUpload($file, $targetDir, $prefix = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error uploading file.'];
    }

    // Validar tipo de archivo
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'File type not allowed.'];
    }

    // Validar tamaño
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File too large.'];
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '.' . $extension;
    $targetPath = $targetDir . $filename;

    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file.'];
}

// Funciones de base de datos
function getUserById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getPetById($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT p.*, u.username, u.avatar as user_avatar FROM pets p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
// Función para obtener mascotas favoritas de un usuario
function getUserFavorites($userId, $limit = 10) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT p.*, pi.image_path as primary_image 
                           FROM favorites f
                           JOIN pets p ON f.pet_id = p.id
                           LEFT JOIN pet_images pi ON p.id = pi.pet_id AND pi.is_primary = TRUE
                           WHERE f.user_id = ?
                           ORDER BY f.created_at DESC
                           LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener conversaciones de un usuario
function getUserConversations($userId, $limit = 10) {
    $conn = getDbConnection();
    $query = "SELECT m.conversation_id, m.pet_id, p.name as pet_name, 
                     CASE 
                         WHEN m.sender_id = ? THEN m.recipient_id 
                         ELSE m.sender_id 
                     END as other_user_id,
                     u.username as other_user_name, u.avatar as other_user_avatar,
                     MAX(m.created_at) as last_message_time,
                     SUM(CASE WHEN m.recipient_id = ? AND m.is_read = FALSE THEN 1 ELSE 0 END) as unread_count
              FROM messages m
              JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.recipient_id ELSE m.sender_id END
              JOIN pets p ON p.id = m.pet_id
              WHERE m.sender_id = ? OR m.recipient_id = ?
              GROUP BY m.conversation_id, m.pet_id, other_user_id, other_user_name, other_user_avatar
              ORDER BY last_message_time DESC
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener mensajes de una conversación
function getConversationMessages($conversationId, $limit = 50) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT m.*, u.username as sender_name, u.avatar as sender_avatar 
                           FROM messages m
                           JOIN users u ON u.id = m.sender_id
                           WHERE m.conversation_id = ?
                           ORDER BY m.created_at DESC
                           LIMIT ?");
    $stmt->bind_param("si", $conversationId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        if ($diff->h == 0) {
            if ($diff->i < 1) {
                return 'ahora mismo';
            }
            return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
        }
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->days == 1) {
        return 'ayer a las ' . $date->format('H:i');
    } elseif ($diff->days < 7) {
        return 'hace ' . $diff->days . ' día' . ($diff->days > 1 ? 's' : '');
    } else {
        return $date->format('d/m/Y H:i');
    }
}
function logActivity($userId, $action, $details = '') {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO user_activities (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $action, $details);
    $stmt->execute();
}
function checkIfFavorite($userId, $petId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $userId, $petId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function petExists($petId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT 1 FROM pets WHERE id = ?");
    $stmt->bind_param("i", $petId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function setFlashMessage($message, $type) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function redirect1($path) {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}
// Más funciones según sea necesario...
?>