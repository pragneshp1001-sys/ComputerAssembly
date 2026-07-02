<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Invalid request method', null, 405);
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['username']) || !isset($data['password'])) {
    apiResponse(false, 'Missing username or password', null, 400);
}

$username = trim($data['username']);
$password = $data['password'];

// Get user — include role and status
$query = "SELECT id, username, email, password, full_name, role, status FROM users WHERE username = ? OR email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    apiResponse(false, 'Invalid username or password', null, 401);
}

$user = $result->fetch_assoc();

// Check account status
if ($user['status'] !== 'active') {
    apiResponse(false, 'Account is inactive', null, 401);
}

// Verify password
if (!password_verify($password, $user['password'])) {
    apiResponse(false, 'Invalid username or password', null, 401);
}

// Create token
$token = base64_encode($user['id'] . ':' . time() . ':' . md5($user['email']));

// Start session
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

apiResponse(true, 'Login successful', [
    'user_id'   => $user['id'],
    'username'  => $user['username'],
    'email'     => $user['email'],
    'full_name' => $user['full_name'],
    'role'      => $user['role'],
    'token'     => $token
]);

$stmt->close();
$conn->close();
?>