<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET: Personalized recommendations
if ($method === 'GET' && $action === 'personalized') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    // Get user's purchase history
    $purchaseQuery = "SELECT DISTINCT p.category_id FROM order_items oi
                      JOIN orders o ON oi.order_id = o.id
                      JOIN products p ON oi.product_id = p.id
                      WHERE o.user_id = ?
                      LIMIT 3";
    
    $stmt = $conn->prepare($purchaseQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($categories)) {
        // If no purchase history, recommend bestsellers
        $query = "SELECT p.* FROM products p
                  ORDER BY p.sales_count DESC
                  LIMIT 10";
    } else {
        $categoryIds = array_map(fn($c) => $c['category_id'], $categories);
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        
        // Recommend from similar categories
        $query = "SELECT p.* FROM products p
                  WHERE p.category_id IN ($placeholders)
                  AND p.id NOT IN (
                      SELECT DISTINCT product_id FROM order_items oi
                      JOIN orders o ON oi.order_id = o.id
                      WHERE o.user_id = ?
                  )
                  ORDER BY p.average_rating DESC, p.sales_count DESC
                  LIMIT 10";
        
        $stmt = $conn->prepare($query);
        $types = str_repeat('i', count($categoryIds)) . 'i';
        $params = array_merge($categoryIds, [$userId]);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }
    
    $result = isset($stmt) ? $stmt->get_result() : $conn->query($query);
    $recommendations = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['recommendations' => $recommendations]);
}

// GET: Related products
elseif ($method === 'GET' && $action === 'related') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    // Get product category
    $productQuery = "SELECT category_id FROM products WHERE id = ?";
    $productStmt = $conn->prepare($productQuery);
    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $product = $productStmt->get_result()->fetch_assoc();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    // Get related products from same category
    $query = "SELECT * FROM products 
              WHERE category_id = ? AND id != ?
              ORDER BY average_rating DESC, sales_count DESC
              LIMIT 8";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $product['category_id'], $productId);
    $stmt->execute();
    
    $related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['related_products' => $related]);
}

// GET: Trending products
elseif ($method === 'GET' && $action === 'trending') {
    $query = "SELECT * FROM products
              WHERE views_count > 100 OR sales_count > 50
              ORDER BY views_count DESC, sales_count DESC, average_rating DESC
              LIMIT 12";
    
    $result = $conn->query($query);
    $trending = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['trending_products' => $trending]);
}

// GET: New arrivals
elseif ($method === 'GET' && $action === 'new') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    
    $query = "SELECT * FROM products
              ORDER BY created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    
    $new = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['new_products' => $new]);
}

// GET: Best sellers
elseif ($method === 'GET' && $action === 'bestsellers') {
    $query = "SELECT * FROM products
              WHERE sales_count > 0
              ORDER BY sales_count DESC
              LIMIT 12";
    
    $result = $conn->query($query);
    $bestsellers = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['bestsellers' => $bestsellers]);
}

// GET: Top rated
elseif ($method === 'GET' && $action === 'toprated') {
    $query = "SELECT * FROM products
              WHERE average_rating > 0 AND review_count > 5
              ORDER BY average_rating DESC
              LIMIT 12";
    
    $result = $conn->query($query);
    $toprated = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['toprated_products' => $toprated]);
}

// POST: Record view
elseif ($method === 'POST' && $action === 'record_view') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['product_id'] ?? 0;
    $userId = $data['user_id'] ?? null;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    // Increment view count
    $query = "UPDATE products SET views_count = views_count + 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    
    // Log user activity
    if ($userId) {
        $activityQuery = "INSERT INTO user_activity (user_id, activity_type, product_id) 
                         VALUES (?, 'view', ?)";
        $activityStmt = $conn->prepare($activityQuery);
        $activityStmt->bind_param('ii', $userId, $productId);
        $activityStmt->execute();
    }
    
    echo json_encode(['success' => true]);
}

// GET: Frequently bought together
elseif ($method === 'GET' && $action === 'frequently_bought_together') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['error' => 'Product ID required']);
        exit;
    }
    
    $query = "SELECT p.* FROM products p
              JOIN order_items oi1 ON p.id = oi1.product_id
              WHERE oi1.order_id IN (
                  SELECT oi2.order_id FROM order_items oi2
                  WHERE oi2.product_id = ?
              )
              AND p.id != ?
              GROUP BY p.id
              ORDER BY COUNT(*) DESC
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $productId, $productId);
    $stmt->execute();
    
    $together = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['frequently_bought_together' => $together]);
}
