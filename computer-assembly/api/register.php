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
if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || !isset($data['confirm_password'])) {
    apiResponse(false, 'Missing required fields', null, 400);
}

$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];
$confirm_password = $data['confirm_password'];
$full_name = isset($data['full_name']) ? trim($data['full_name']) : '';

// Validation
if (strlen($username) < 3) {
    apiResponse(false, 'Username must be at least 3 characters', null, 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiResponse(false, 'Invalid email format', null, 400);
}

if (strlen($password) < 6) {
    apiResponse(false, 'Password must be at least 6 characters', null, 400);
}

if ($password !== $confirm_password) {
    apiResponse(false, 'Passwords do not match', null, 400);
}

// Check if user exists
$check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    apiResponse(false, 'Username or email already exists', null, 400);
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert user
$insert_query = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    apiResponse(true, 'Registration successful', [
        'user_id' => $user_id,
        'username' => $username,
        'email' => $email
    ], 201);
} else {
    apiResponse(false, 'Registration failed: ' . $conn->error, null, 500);
}

$stmt->close();
$conn->close();
?>
