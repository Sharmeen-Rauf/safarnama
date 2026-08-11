<?php
// api/logout.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed. Use POST."]);
    exit();
}

if (isset($_SESSION['user'])) {
    // Unset all session values
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    echo json_encode(["message" => "Logged out successfully"]);
} else {
    echo json_encode(["message" => "Already logged out"]);
}
