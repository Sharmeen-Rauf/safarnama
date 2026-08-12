<?php
// api/admin.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// Route Guard: Verify Authentication and Admin Role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(["message" => "Access Denied. Admin privilege required."]);
    exit();
}

// Auto-migration: Check and add 'is_blocked' column to live database if missing
global $conn;
if (!DB_MOCKED && $conn) {
    $columnCheck = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'is_blocked'");
    if ($columnCheck && mysqli_num_rows($columnCheck) === 0) {
        @mysqli_query($conn, "ALTER TABLE users ADD COLUMN is_blocked BOOLEAN DEFAULT FALSE");
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents("php://input"), true);
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// ==========================================
// 1. GET - Fetch Lists (Users or Listings)
// ==========================================
if ($method === 'GET') {
    try {
        if ($action === 'users') {
            // Retrieve all users
            $users = db_query("SELECT id, full_name, email, mobile_number, city, province, role, is_blocked, created_at FROM users WHERE role != 'Admin' ORDER BY id DESC");
            echo json_encode($users ?: []);
            exit();
        }

        if ($action === 'products') {
            // Retrieve all listings for moderation
            $products = db_query(
                "SELECT p.*, u.full_name as farmer_name, c.name as category_name 
                 FROM products p 
                 JOIN users u ON p.farmer_id = u.id 
                 JOIN categories c ON p.category_id = c.id 
                 ORDER BY p.id DESC"
            );
            echo json_encode($products ?: []);
            exit();
        }

        // Return dashboard statistics
        $userCount   = db_query("SELECT COUNT(*) as count FROM users WHERE role != 'Admin'");
        $productCount = db_query("SELECT COUNT(*) as count FROM products");
        $orderCount   = db_query("SELECT COUNT(*) as count FROM orders");
        $revenueVal  = db_query("SELECT SUM(total_price) as sum FROM orders WHERE status = 'Delivered'");

        echo json_encode([
            "stats" => [
                "users"    => $userCount ? $userCount[0]['count'] : 0,
                "products" => $productCount ? $productCount[0]['count'] : 0,
                "orders"   => $orderCount ? $orderCount[0]['count'] : 0,
                "revenue"  => ($revenueVal && $revenueVal[0]['sum']) ? (float)$revenueVal[0]['sum'] : 0.0
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while loading admin data."]);
    }
    exit();
}

// ==========================================
// 2. POST - Block/Unblock User
// ==========================================
if ($method === 'POST' && $action === 'toggle_block') {
    $targetUserId = isset($input['targetUserId']) ? (int)$input['targetUserId'] : 0;

    if ($targetUserId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid target user ID."]);
        exit();
    }

    try {
        // Retrieve current status
        $userRows = db_query("SELECT is_blocked, full_name FROM users WHERE id = ?", [$targetUserId], "i");
        if (!$userRows || count($userRows) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Target user not found."]);
            exit();
        }

        $targetUser = $userRows[0];
        $newBlockedStatus = $targetUser['is_blocked'] ? 0 : 1;

        $res = db_query("UPDATE users SET is_blocked = ? WHERE id = ?", [$newBlockedStatus, $targetUserId], "ii");

        if ($res) {
            $statusText = $newBlockedStatus ? "blocked" : "unblocked";
            echo json_encode(["message" => "User " . $targetUser['full_name'] . " has been successfully " . $statusText . "."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update user block status."]);
        }

    } catch (Exception $e) {
        error_log("Admin Block User Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred during user update status."]);
    }
    exit();
}

// ==========================================
// 3. POST - Delete/Moderate Crop Listing
// ==========================================
if ($method === 'POST' && $action === 'delete_product') {
    $productId = isset($input['productId']) ? (int)$input['productId'] : 0;

    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid product ID."]);
        exit();
    }

    try {
        // Fetch product info to notify farmer
        $productRows = db_query("SELECT title, farmer_id FROM products WHERE id = ?", [$productId], "i");
        if (!$productRows || count($productRows) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Listing already removed."]);
            exit();
        }

        $product = $productRows[0];
        $res = db_query("DELETE FROM products WHERE id = ?", [$productId], "i");

        if ($res) {
            // Notify the farmer that their crop listing was moderated/deleted
            $msg = "Your crop listing '" . $product['title'] . "' has been removed by the Admin due to platform policy moderation.";
            db_query("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Listing Removed by Admin', ?)", [$product['farmer_id'], $msg], "is");

            echo json_encode(["message" => "Listing has been successfully moderated and removed."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete listing."]);
        }

    } catch (Exception $e) {
        error_log("Admin Moderate Product Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while moderating listing."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid Admin API action."]);
