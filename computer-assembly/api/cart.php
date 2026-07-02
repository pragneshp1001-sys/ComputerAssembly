<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    apiResponse(false, 'Please login first', null, 401);
}

$user_id = $_SESSION['user_id'];

// Get cart items
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.user_id = ?
              ORDER BY c.added_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cart_items = array();
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $row['subtotal'] = $row['price'] * $row['quantity'];
        $total += $row['subtotal'];
        $cart_items[] = $row;
    }

    apiResponse(true, 'Cart retrieved', [
        'items' => $cart_items,
        'total' => $total,
        'item_count' => count($cart_items)
    ]);
}

// Add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['product_id']) || !isset($data['quantity'])) {
        apiResponse(false, 'Missing product_id or quantity', null, 400);
    }

    $product_id = (int)$data['product_id'];
    $quantity = (int)$data['quantity'];

    // Check if product exists
    $check_query = "SELECT id, stock_quantity FROM products WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        apiResponse(false, 'Product not found', null, 404);
    }

    $product = $result->fetch_assoc();

    if ($quantity > $product['stock_quantity']) {
        apiResponse(false, 'Insufficient stock', null, 400);
    }

    // Check if product already in cart
    $check_cart = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($check_cart);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update quantity
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        if ($new_quantity > $product['stock_quantity']) {
            apiResponse(false, 'Insufficient stock for update', null, 400);
        }

        $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
        $stmt->execute();
        apiResponse(true, 'Cart updated', ['cart_id' => $cart_item['id']]);
    } else {
        // Add new item
        $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        
        if ($stmt->execute()) {
            apiResponse(true, 'Item added to cart', ['cart_id' => $conn->insert_id]);
        } else {
            apiResponse(false, 'Failed to add item', null, 500);
        }
    }
}

// Update cart item
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['cart_id']) || !isset($data['quantity'])) {
        apiResponse(false, 'Missing cart_id or quantity', null, 400);
    }

    $cart_id = (int)$data['cart_id'];
    $quantity = (int)$data['quantity'];

    if ($quantity <= 0) {
        // Delete item
        $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        apiResponse(true, 'Item removed from cart');
    } else {
        $update_query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        
        if ($stmt->execute()) {
            apiResponse(true, 'Cart item updated');
        } else {
            apiResponse(false, 'Failed to update cart', null, 500);
        }
    }
}

// Remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['cart_id'])) {
        apiResponse(false, 'Missing cart_id', null, 400);
    }

    $cart_id = (int)$data['cart_id'];

    $delete_query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($stmt->execute()) {
        apiResponse(true, 'Item removed from cart');
    } else {
        apiResponse(false, 'Failed to remove item', null, 500);
    }
}

$conn->close();
?>
