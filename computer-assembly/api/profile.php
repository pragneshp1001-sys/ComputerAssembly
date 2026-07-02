<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'jwt.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api/profile.php', '', $path);

// Get token from Authorization header
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $user = verifyToken($token);
    $userId = $user->id;
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// GET: Fetch user profile
if ($method === 'GET') {
    $query = "SELECT id, username, email, phone, address, city, state, postal_code, country, 
              profile_image, bio, date_of_birth, created_at 
              FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
}

// POST: Update user profile
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $allowedFields = ['phone', 'address', 'city', 'state', 'postal_code', 'country', 'bio', 'date_of_birth'];
    $updates = [];
    $params = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        exit;
    }
    
    $params[] = $userId;
    $query = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $types = str_repeat('s', count($updates)) . 'i';
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Fetch updated profile
        $query = "SELECT id, username, email, phone, address, city, state, postal_code, country, 
                  profile_image, bio, date_of_birth FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $result]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}

// PUT: Upload profile image
elseif ($method === 'PUT') {
    if (!isset($_FILES['profile_image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded']);
        exit;
    }
    
    $file = $_FILES['profile_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File too large']);
        exit;
    }
    
    $filename = 'profile_' . $userId . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filepath = __DIR__ . '/../uploads/profiles/' . $filename;
    
    if (!is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $query = "UPDATE users SET profile_image = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $dbPath = 'uploads/profiles/' . $filename;
        $stmt->bind_param('si', $dbPath, $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'image_path' => $dbPath]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update database']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload file']);
    }
}

// DELETE: Close account
elseif ($method === 'DELETE') {
    // Soft delete or anonymize account
    $query = "UPDATE users SET username = CONCAT('deleted_', id), email = CONCAT('deleted_', id, '@deleted.com'), 
              password = NULL WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account closed']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to close account']);
    }
}
