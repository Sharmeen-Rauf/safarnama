<?php
// api/profile.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// Route Guard: Verify Authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access. Please log in."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed. Use POST or PUT."]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid JSON input"]);
    exit();
}

$userId   = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

$fullName     = isset($input['fullName']) ? trim($input['fullName']) : '';
$mobileNumber = isset($input['mobileNumber']) ? trim($input['mobileNumber']) : '';
$address      = isset($input['address']) ? trim($input['address']) : '';
$city         = isset($input['city']) ? trim($input['city']) : '';
$province     = isset($input['province']) ? trim($input['province']) : '';

// Farm Details (Farmer specific)
$farmName     = isset($input['farmName']) ? trim($input['farmName']) : '';
$farmLocation = isset($input['farmLocation']) ? trim($input['farmLocation']) : '';
$cropType     = isset($input['cropType']) ? trim($input['cropType']) : '';

// Validation
if (empty($fullName) || empty($mobileNumber) || empty($address) || empty($city) || empty($province)) {
    http_response_code(400);
    echo json_encode(["message" => "All personal details fields are required"]);
    exit();
}

if (!preg_match('/^\d{10,15}$/', preg_replace('/[-+ ]/', '', $mobileNumber))) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid mobile number format. (10-15 digits required)"]);
    exit();
}

if ($userRole === 'Farmer' && (empty($farmName) || empty($farmLocation) || empty($cropType))) {
    http_response_code(400);
    echo json_encode(["message" => "All farm information fields are required"]);
    exit();
}

try {
    // 1. Update users table (email is omitted as it remains read-only after registration)
    $updateUser = db_query(
        "UPDATE users SET full_name = ?, mobile_number = ?, address = ?, city = ?, province = ? WHERE id = ?",
        [$fullName, $mobileNumber, $address, $city, $province, $userId],
        "sssssi"
    );

    if ($updateUser === false) {
        throw new Exception("Failed to update users table");
    }

    // 2. Update/Insert farm if Farmer
    if ($userRole === 'Farmer') {
        // Check if farm record already exists for the user
        $existingFarm = db_query("SELECT id FROM farms WHERE user_id = ?", [$userId], "i");
        
        if ($existingFarm && count($existingFarm) > 0) {
            // Update
            $updateFarm = db_query(
                "UPDATE farms SET farm_name = ?, farm_location = ?, crop_type = ? WHERE user_id = ?",
                [$farmName, $farmLocation, $cropType, $userId],
                "sssi"
            );
            if ($updateFarm === false) {
                throw new Exception("Failed to update farms table");
            }
        } else {
            // Insert
            $insertFarm = db_query(
                "INSERT INTO farms (user_id, farm_name, farm_location, crop_type) VALUES (?, ?, ?, ?)",
                [$userId, $farmName, $farmLocation, $cropType],
                "isss"
            );
            if ($insertFarm === false) {
                throw new Exception("Failed to insert farm row");
            }
        }
    }

    // Update active session values
    $_SESSION['user']['fullName'] = $fullName;

    echo json_encode(["message" => "Profile updated successfully!"]);

} catch (Exception $e) {
    error_log("Profile Update API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Server error occurred during profile update."]);
}
