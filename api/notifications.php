<?php
// api/notifications.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// Route Guard: Verify Authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access. Please log in."]);
    exit();
}

$userId = $_SESSION['user']['id'];
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents("php://input"), true);
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// ==========================================
// 1. GET - Fetch Notifications List
// ==========================================
if ($method === 'GET') {
    try {
        $notifications = db_query(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 30",
            [$userId],
            "i"
        );
        echo json_encode($notifications ?: []);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while retrieving notifications."]);
    }
    exit();
}

// ==========================================
// 2. POST - Mark Notifications as Read
// ==========================================
if ($method === 'POST' && ($action === 'read' || $action === 'read_all')) {
    try {
        if ($action === 'read') {
            $notificationId = isset($input['notificationId']) ? (int)$input['notificationId'] : 0;
            if ($notificationId <= 0) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid notification ID."]);
                exit();
            }
            $res = db_query(
                "UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?",
                [$notificationId, $userId],
                "ii"
            );
        } else {
            // Read All
            $res = db_query(
                "UPDATE notifications SET is_read = TRUE WHERE user_id = ?",
                [$userId],
                "i"
            );
        }

        if ($res) {
            echo json_encode(["message" => "Notifications updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update notification status."]);
        }

    } catch (Exception $e) {
        error_log("Notifications Update API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while updating notifications."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid API request action."]);
