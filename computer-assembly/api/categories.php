<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Get all categories
$query = "SELECT * FROM categories ORDER BY name ASC";
$result = $conn->query($query);

if (!$result) {
    apiResponse(false, 'Query error: ' . $conn->error, null, 500);
}

$categories = array();
while ($row = $result->fetch_assoc()) {
    // Get product count for each category
    $count_query = "SELECT COUNT(*) as product_count FROM products WHERE category_id = {$row['id']}";
    $count_result = $conn->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $row['product_count'] = $count_row['product_count'];
    
    $categories[] = $row;
}

apiResponse(true, 'Categories retrieved', $categories);

$conn->close();
?>
