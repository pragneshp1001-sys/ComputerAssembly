<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || strlen($query) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Query too short']);
    exit;
}

// Log search query
$logQuery = "INSERT INTO search_queries (query, results_count, search_count) 
             SELECT ?, COUNT(*), 1 FROM products 
             WHERE name LIKE ? OR description LIKE ?
             ON DUPLICATE KEY UPDATE search_count = search_count + 1";

$searchTerm = "%$query%";
$logStmt = $conn->prepare($logQuery);
$logStmt->bind_param('sss', $query, $searchTerm, $searchTerm);
$logStmt->execute();

// Search products
$searchQuery = "SELECT * FROM products 
                WHERE name LIKE ? OR description LIKE ? OR category_id IN (
                    SELECT id FROM categories WHERE name LIKE ?
                )
                ORDER BY 
                    CASE 
                        WHEN name LIKE ? THEN 1
                        WHEN name LIKE ? THEN 2
                        ELSE 3
                    END,
                    sales_count DESC,
                    average_rating DESC
                LIMIT 50";

$exactMatch = $query;
$prefixMatch = $query . '%';
$stmt = $conn->prepare($searchQuery);
$stmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $exactMatch, $prefixMatch);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get autocomplete suggestions
$autocompleteQuery = "SELECT DISTINCT name FROM products 
                      WHERE name LIKE ?
                      UNION
                      SELECT DISTINCT query FROM search_queries 
                      WHERE query LIKE ?
                      ORDER BY search_count DESC
                      LIMIT 10";

$autocompleteStmt = $conn->prepare($autocompleteQuery);
$autocompleteStmt->bind_param('ss', $prefixMatch, $prefixMatch);
$autocompleteStmt->execute();
$suggestions = $autocompleteStmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'results' => $results,
    'suggestions' => array_map(fn($s) => $s['name'] ?? $s['query'], $suggestions),
    'total' => count($results)
]);
