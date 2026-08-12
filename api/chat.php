<?php
// api/chat.php
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
// 1. GET - Fetch Messages or Active Conversations
// ==========================================
if ($method === 'GET') {
    try {
        if ($action === 'conversations') {
            // Retrieve list of unique users the active session user has chatted with
            $conversations = db_query(
                "SELECT DISTINCT u.id, u.full_name, u.role 
                 FROM users u 
                 JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id) 
                 WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ? 
                 ORDER BY u.full_name ASC",
                [$userId, $userId, $userId],
                "iii"
            );
            echo json_encode($conversations ?: []);
            exit();
        }

        // Default: Fetch full chat history between logged in user and a specific chat partner
        $partnerId = isset($_GET['partnerId']) ? (int)$_GET['partnerId'] : 0;
        
        if ($partnerId <= 0) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid chat partner ID specified."]);
            exit();
        }

        $messages = db_query(
            "SELECT * FROM messages 
             WHERE (sender_id = ? AND receiver_id = ?) 
                OR (sender_id = ? AND receiver_id = ?) 
             ORDER BY id ASC",
            [$userId, $partnerId, $partnerId, $userId],
            "iiii"
        );
        echo json_encode($messages ?: []);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while fetching chat data."]);
    }
    exit();
}

// ==========================================
// 2. POST - Send Message
// ==========================================
if ($method === 'POST' && $action === 'send') {
    $receiverId = isset($input['receiverId']) ? (int)$input['receiverId'] : 0;
    $message    = isset($input['message']) ? trim($input['message']) : '';

    if ($receiverId <= 0 || empty($message)) {
        http_response_code(400);
        echo json_encode(["message" => "Receiver ID and message content are required."]);
        exit();
    }

    try {
        // Verify receiver exists
        $receiverExists = db_query("SELECT full_name FROM users WHERE id = ?", [$receiverId], "i");
        if (!$receiverExists || count($receiverExists) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Recipient user not found."]);
            exit();
        }

        // Insert Message into database
        $res = db_query(
            "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)",
            [$userId, $receiverId, $message],
            "iis"
        );

        if ($res) {
            // Trigger Notification for the Recipient (FR9)
            $senderName = $_SESSION['user']['fullName'];
            $notificationMsg = "New message from " . $senderName . ": \"" . (strlen($message) > 40 ? substr($message, 0, 40) . "..." : $message) . "\"";
            db_query(
                "INSERT INTO notifications (user_id, title, message) VALUES (?, 'New Chat Message', ?)",
                [$receiverId, $notificationMsg],
                "is"
            );

            echo json_encode(["message" => "Message sent successfully!", "messageId" => $res['insert_id']]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to save message in database."]);
        }

    } catch (Exception $e) {
        error_log("Send Message API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while sending message."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid API request action."]);
