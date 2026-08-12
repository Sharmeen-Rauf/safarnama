<?php
// api/reviews.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// Route Guard: Verify Authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access. Please log in."]);
    exit();
}

$userId   = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents("php://input"), true);
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// ==========================================
// POST - Submit Review (Buyer Only)
// ==========================================
if ($method === 'POST' && $action === 'add') {
    if ($userRole !== 'Buyer') {
        http_response_code(403);
        echo json_encode(["message" => "Only buyers can rate and review orders."]);
        exit();
    }

    $orderId  = isset($input['orderId']) ? (int)$input['orderId'] : 0;
    $rating   = isset($input['rating']) ? (int)$input['rating'] : 0;
    $feedback = isset($input['feedback']) ? trim($input['feedback']) : '';

    if ($orderId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(["message" => "Please submit a valid rating between 1 and 5 stars."]);
        exit();
    }

    try {
        // Fetch order details and check ownership and delivery status
        $orderCheck = db_query(
            "SELECT o.*, p.farmer_id, p.title as product_title 
             FROM orders o 
             JOIN products p ON o.product_id = p.id 
             WHERE o.id = ? AND o.buyer_id = ?",
            [$orderId, $userId],
            "ii"
        );

        if (!$orderCheck || count($orderCheck) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Order not found or unauthorized to rate this transaction."]);
            exit();
        }

        $order = $orderCheck[0];
        if ($order['status'] !== 'Delivered') {
            http_response_code(400);
            echo json_encode(["message" => "Reviews can only be submitted for completed and 'Delivered' orders."]);
            exit();
        }

        // Check if review already exists
        $reviewCheck = db_query("SELECT id FROM reviews WHERE order_id = ?", [$orderId], "i");
        if ($reviewCheck && count($reviewCheck) > 0) {
            http_response_code(400);
            echo json_encode(["message" => "You have already rated and reviewed this order."]);
            exit();
        }

        // Insert Review
        $res = db_query(
            "INSERT INTO reviews (order_id, buyer_id, farmer_id, rating, feedback) VALUES (?, ?, ?, ?, ?)",
            [$orderId, $userId, $order['farmer_id'], $rating, $feedback],
            "iiiis"
        );

        if ($res) {
            // Trigger Notification for the Farmer (FR9)
            $buyerName = $_SESSION['user']['fullName'];
            $notificationMsg = $buyerName . " left you a " . $rating . "-star rating for your crop: " . $order['product_title'];
            db_query(
                "INSERT INTO notifications (user_id, title, message) VALUES (?, 'New Review Received', ?)",
                [$order['farmer_id'], $notificationMsg],
                "is"
            );

            echo json_encode(["message" => "Review and rating submitted successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database error occurred while saving the review."]);
        }

    } catch (Exception $e) {
        error_log("Reviews API Add Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while adding review."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid API request action."]);
