<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'jwt.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// POST: Add review
if ($method === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $productId = $data['product_id'] ?? 0;
    $rating = $data['rating'] ?? 0;
    $title = $data['title'] ?? '';
    $comment = $data['comment'] ?? '';
    
    // Validate
    if (!$productId || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }
    
    // Check if user has purchased this product
    $checkQuery = "SELECT COUNT(*) as count FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   WHERE oi.product_id = ? AND o.user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('ii', $productId, $userId);
    $checkStmt->execute();
    $purchaseCheck = $checkStmt->get_result()->fetch_assoc();
    $verifiedPurchase = $purchaseCheck['count'] > 0;
    
    // Check for existing review
    $existingQuery = "SELECT id FROM reviews WHERE product_id = ? AND user_id = ?";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bind_param('ii', $productId, $userId);
    $existingStmt->execute();
    
    if ($existingStmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'You already reviewed this product']);
        exit;
    }
    
    // Insert review
    $query = "INSERT INTO reviews (product_id, user_id, rating, title, comment, verified_purchase) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiissi', $productId, $userId, $rating, $title, $comment, $verifiedPurchase);
    
    if ($stmt->execute()) {
        // Update product average rating
        $updateQuery = "UPDATE products SET average_rating = (
                        SELECT AVG(rating) FROM reviews WHERE product_id = ?
                        ), review_count = (
                        SELECT COUNT(*) FROM reviews WHERE product_id = ?
                        ) WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('iii', $productId, $productId, $productId);
        $updateStmt->execute();
        
        echo json_encode(['success' => true, 'review_id' => $conn->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add review']);
    }
}

// GET: Get product reviews
elseif ($method === 'GET' && $action === 'list') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $filterRating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    $where = "WHERE r.product_id = ?";
    $params = [$productId];
    $types = 'i';
    
    if ($filterRating > 0) {
        $where .= " AND r.rating = ?";
        $params[] = $filterRating;
        $types .= 'i';
    }
    
    $orderBy = match($sortBy) {
        'highest' => 'r.rating DESC',
        'lowest' => 'r.rating ASC',
        'helpful' => 'r.helpful_count DESC',
        default => 'r.created_at DESC'
    };
    
    $query = "SELECT r.id, r.rating, r.title, r.comment, r.helpful_count, r.unhelpful_count,
              r.verified_purchase, r.created_at, u.username, u.profile_image
              FROM reviews r
              JOIN users u ON r.user_id = u.id
              $where
              ORDER BY $orderBy
              LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    // Get rating distribution
    $ratingQuery = "SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = ? GROUP BY rating";
    $ratingStmt = $conn->prepare($ratingQuery);
    $ratingStmt->bind_param('i', $productId);
    $ratingStmt->execute();
    $ratingDist = $ratingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'reviews' => $reviews,
        'rating_distribution' => $ratingDist,
        'total' => count($reviews)
    ]);
}

// POST: Mark review helpful
elseif ($method === 'POST' && $action === 'helpful') {
    $data = json_decode(file_get_contents('php://input'), true);
    $reviewId = $data['review_id'] ?? 0;
    $type = $data['type'] ?? 'helpful'; // 'helpful' or 'unhelpful'
    
    if (!$reviewId) {
        http_response_code(400);
        echo json_encode(['error' => 'Review ID required']);
        exit;
    }
    
    $field = $type === 'helpful' ? 'helpful_count' : 'unhelpful_count';
    $query = "UPDATE reviews SET $field = $field + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reviewId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update']);
    }
}

// PUT: Edit review
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $reviewId = $data['review_id'] ?? 0;
    $rating = $data['rating'] ?? 0;
    $title = $data['title'] ?? '';
    $comment = $data['comment'] ?? '';
    
    // Verify ownership
    $checkQuery = "SELECT user_id, product_id FROM reviews WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $reviewId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found']);
        exit;
    }
    
    $review = $result->fetch_assoc();
    if ($review['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $query = "UPDATE reviews SET rating = ?, title = ?, comment = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('issi', $rating, $title, $comment, $reviewId);
    
    if ($stmt->execute()) {
        // Update product rating
        $productId = $review['product_id'];
        $updateQuery = "UPDATE products SET average_rating = (
                        SELECT AVG(rating) FROM reviews WHERE product_id = ?
                        ) WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('ii', $productId, $productId);
        $updateStmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update']);
    }
}

// DELETE: Remove review
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $reviewId = $data['review_id'] ?? 0;
    
    // Verify ownership
    $checkQuery = "SELECT user_id, product_id FROM reviews WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $reviewId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Review not found']);
        exit;
    }
    
    $review = $result->fetch_assoc();
    if ($review['user_id'] != $userId) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $query = "DELETE FROM reviews WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $reviewId);
    
    if ($stmt->execute()) {
        // Update product rating
        $productId = $review['product_id'];
        $updateQuery = "UPDATE products SET average_rating = (
                        SELECT AVG(rating) FROM reviews WHERE product_id = ?
                        ), review_count = (
                        SELECT COUNT(*) FROM reviews WHERE product_id = ?
                        ) WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('iii', $productId, $productId, $productId);
        $updateStmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete']);
    }
}
