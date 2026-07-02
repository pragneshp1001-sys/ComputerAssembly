<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Invalid request method', null, 405);
}

if (!isset($_SESSION['user_id'])) {
    apiResponse(false, 'Please login first', null, 401);
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['shipping_address']) || !isset($data['payment_method'])) {
    apiResponse(false, 'Missing required fields', null, 400);
}

$shipping_address = trim($data['shipping_address']);
$payment_method = trim($data['payment_method']);

// Get cart items
$cart_query = "SELECT c.*, p.price FROM cart c
               JOIN products p ON c.product_id = p.id
               WHERE c.user_id = ?";

$stmt = $conn->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_result = $stmt->get_result();

if ($cart_result->num_rows === 0) {
    apiResponse(false, 'Cart is empty', null, 400);
}

$total_amount = 0;
$cart_items = array();

while ($item = $cart_result->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $total_amount += $item_total;
    $cart_items[] = $item;
}

// Start transaction
$conn->begin_transaction();

try {
    // Create order
    $order_number = 'ORD-' . date('YmdHis') . '-' . $user_id;
    $order_query = "INSERT INTO orders (user_id, order_number, total_amount, payment_method, shipping_address, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("isd ss", $user_id, $order_number, $total_amount, $payment_method, $shipping_address);
    $stmt->execute();
    
    $order_id = $conn->insert_id;

    // Add order items
    $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($order_item_query);

    foreach ($cart_items as $item) {
        $stmt->bind_param("iiii", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    // Clear cart
    $delete_cart = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($delete_cart);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    apiResponse(true, 'Order created successfully', [
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total_amount' => $total_amount,
        'status' => 'pending'
    ], 201);

} catch (Exception $e) {
    $conn->rollback();
    apiResponse(false, 'Order creation failed: ' . $e->getMessage(), null, 500);
}

$conn->close();
?>
