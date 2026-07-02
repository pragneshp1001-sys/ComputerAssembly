<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    exit(0);
}

// Stripe payment processing
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['token', 'amount', 'email', 'order_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing field: $field"]);
            exit;
        }
    }
    
    // Simulate payment processing (for demo)
    $transactionId = 'txn_' . bin2hex(random_bytes(16));
    $status = rand(1, 100) > 10 ? 'captured' : 'failed'; // 90% success rate
    
    // In production, use actual Stripe library:
    // require_once 'vendor/autoload.php';
    // \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    
    if ($status === 'captured') {
        // Record transaction
        $query = "INSERT INTO payment_transactions (order_id, user_id, payment_gateway, 
                  transaction_id, amount, status, payment_method, card_last_four)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $userId = 1; // Get from authenticated user
        $gateway = 'stripe';
        $method = 'card';
        $lastFour = substr($data['token'], -4);
        
        $stmt->bind_param('iisdsss', $data['order_id'], $userId, $gateway, 
                         $transactionId, $data['amount'], $status, $method, $lastFour);
        $stmt->execute();
        
        // Update order status
        $updateQuery = "UPDATE orders SET payment_status = 'completed', order_status = 'processing' 
                       WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param('i', $data['order_id']);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'transaction_id' => $transactionId,
            'message' => 'Payment successful'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Payment failed',
            'transaction_id' => $transactionId
        ]);
    }
}
