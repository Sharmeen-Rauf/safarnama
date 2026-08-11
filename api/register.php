<?php
// api/register.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed. Use POST."]);
    exit();
}

// Get JSON Input
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON input"]);
    exit();
}

$role         = isset($input['role']) ? trim($input['role']) : '';
$fullName     = isset($input['fullName']) ? trim($input['fullName']) : '';
$email        = isset($input['email']) ? trim($input['email']) : '';
$mobileNumber = isset($input['mobileNumber']) ? trim($input['mobileNumber']) : '';
$password     = isset($input['password']) ? $input['password'] : '';
$address      = isset($input['address']) ? trim($input['address']) : '';
$city         = isset($input['city']) ? trim($input['city']) : '';
$province     = isset($input['province']) ? trim($input['province']) : '';

// Farm Specific Info
$farmName     = isset($input['farmName']) ? trim($input['farmName']) : '';
$farmLocation = isset($input['farmLocation']) ? trim($input['farmLocation']) : '';
$cropType     = isset($input['cropType']) ? trim($input['cropType']) : '';

// Validation
if (empty($role) || empty($fullName) || empty($email) || empty($mobileNumber) || empty($password) || empty($address) || empty($city) || empty($province)) {
    http_response_code(400);
    echo json_encode(["message" => "All mandatory fields are required"]);
    exit();
}

if ($role !== 'Farmer' && $role !== 'Buyer') {
    http_response_code(400);
    echo json_encode(["message" => "Invalid user role selected"]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid email address format"]);
    exit();
}

if (!preg_match('/^\d{10,15}$/', preg_replace('/[-+ ]/', '', $mobileNumber))) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid mobile number format. (10-15 digits required)"]);
    exit();
}

// Password Policy: min 8 chars, 1 letter, 1 number
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    http_response_code(400);
    echo json_encode(["message" => "Password must be at least 8 characters and contain both letters and numbers"]);
    exit();
}

if ($role === 'Farmer' && (empty($farmName) || empty($farmLocation) || empty($cropType))) {
    http_response_code(400);
    echo json_encode(["message" => "Farm details are required for Farmers"]);
    exit();
}

try {
    // Check if email already exists
    $existing = db_query("SELECT id FROM users WHERE email = ?", [$email], "s");
    if ($existing && count($existing) > 0) {
        http_response_code(400);
        echo json_encode(["message" => "Email address is already registered"]);
        exit();
    }

    // Securely hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Save in Database
    $insertResult = db_query(
        "INSERT INTO users (full_name, email, password, mobile_number, address, city, province, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$fullName, $email, $hashedPassword, $mobileNumber, $address, $city, $province, $role],
        "ssssssss"
    );

    if (!$insertResult) {
        throw new Exception("Failed to insert user row");
    }

    $userId = $insertResult['insert_id'];

    // Insert farmer specific details
    if ($role === 'Farmer') {
        $farmResult = db_query(
            "INSERT INTO farms (user_id, farm_name, farm_location, crop_type) VALUES (?, ?, ?, ?)",
            [$userId, $farmName, $farmLocation, $cropType],
            "isss"
        );
        if (!$farmResult) {
            throw new Exception("Failed to insert farm details");
        }
    }

    http_response_code(201);
    echo json_encode(["message" => "Registration successful! You can now log in."]);

} catch (Exception $e) {
    error_log("Registration API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Server error occurred during registration. Please try again later."]);
}
