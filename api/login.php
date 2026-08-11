<?php
// api/login.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed. Use POST."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON input"]);
    exit();
}

$email    = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["message" => "Email and password are required"]);
    exit();
}

try {
    // Find user in database
    $users = db_query("SELECT * FROM users WHERE email = ?", [$email], "s");
    
    if (!$users || count($users) === 0) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid email or password"]);
        exit();
    }

    $user = $users[0];

    // Verify Password against stored hash
    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(["message" => "Invalid email or password"]);
        exit();
    }

    // Set User Session
    $_SESSION['user'] = [
        "id" => (int)$user['id'],
        "fullName" => $user['full_name'],
        "email" => $user['email'],
        "role" => $user['role']
    ];

    echo json_encode([
        "message" => "Login successful!",
        "user" => [
            "id" => (int)$user['id'],
            "fullName" => $user['full_name'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);

} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Server error occurred during login."]);
}
