<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'jwt.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get token
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

// GET: List wishlist items
if ($method === 'GET' && $action === 'list') {
    $query = "SELECT w.id, w.added_at, p.* FROM wishlist w
              JOIN products p ON w.product_id = p.id
              WHERE w.user_id = ?
              ORDER BY w.added_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode(['items' => $items, 'total' => count($items)]);
}

// POST: Add to wishlist
elseif ($method === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    // Check if already in wishlist
    $checkQuery = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('ii', $userId, $productId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Already in wishlist']);
        exit;
    }
    
    $query = "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userId, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'wishlist_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add to wishlist']);
    }
}

// GET: Check if product in wishlist
elseif ($method === 'GET' && $action === 'check') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    $query = "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode(['in_wishlist' => $result->num_rows > 0]);
}

// DELETE: Remove from wishlist
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    $query = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userId, $productId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove from wishlist']);
    }
}

// POST: Clear wishlist
elseif ($method === 'POST' && $action === 'clear') {
    $query = "DELETE FROM wishlist WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to clear wishlist']);
    }
}

// POST: Move to cart
elseif ($method === 'POST' && $action === 'move_to_cart') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    // Check product exists and get price
    $productQuery = "SELECT id, price FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    
    if ($productResult->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    $product = $productResult->fetch_assoc();
    
    // Add to cart (you'll need to implement cart table and logic)
    // This is placeholder - assumes cart is stored in session/table
    
    // Remove from wishlist
    $deleteQuery = "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param('ii', $userId, $productId);
    
    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Moved to cart']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to move to cart']);
    }
}
