<?php
// api/orders.php
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
// 1. GET - Retrieve Orders (Role-based list)
// ==========================================
if ($method === 'GET') {
    try {
        if ($userRole === 'Farmer') {
            // Fetch incoming orders for the farmer
            $orders = db_query(
                "SELECT o.*, p.title as product_title, p.unit, p.price, u.full_name as buyer_name, u.mobile_number, u.address, u.city, u.province 
                 FROM orders o 
                 JOIN products p ON o.product_id = p.id 
                 JOIN users u ON o.buyer_id = u.id 
                 WHERE p.farmer_id = ? 
                 ORDER BY o.id DESC",
                [$userId],
                "i"
            );
        } else {
            // Fetch purchase history for the buyer
            $orders = db_query(
                "SELECT o.*, p.title as product_title, p.unit, p.price, u.full_name as farmer_name, u.mobile_number, r.rating, r.feedback 
                 FROM orders o 
                 JOIN products p ON o.product_id = p.id 
                 JOIN users u ON p.farmer_id = u.id 
                 LEFT JOIN reviews r ON r.order_id = o.id 
                 WHERE o.buyer_id = ? 
                 ORDER BY o.id DESC",
                [$userId],
                "i"
            );
        }
        
        echo json_encode($orders ?: []);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["message" => "Failed to retrieve orders."]);
    }
    exit();
}

// ==========================================
// 2. POST - Create/Place New Order (Buyer Only)
// ==========================================
if ($method === 'POST' && $action === 'create') {
    if ($userRole !== 'Buyer') {
        http_response_code(403);
        echo json_encode(["message" => "Only buyers can place orders."]);
        exit();
    }

    $productId     = isset($input['productId']) ? (int)$input['productId'] : 0;
    $quantity      = isset($input['quantity']) ? (float)$input['quantity'] : 0.0;
    $paymentMethod = isset($input['paymentMethod']) ? trim($input['paymentMethod']) : 'COD';

    if ($productId <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Please provide a valid product and quantity."]);
        exit();
    }

    try {
        // Retrieve crop listing details
        $productRows = db_query("SELECT price, quantity, farmer_id, title FROM products WHERE id = ?", [$productId], "i");
        if (!$productRows || count($productRows) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Crop listing not found."]);
            exit();
        }

        $product = $productRows[0];
        if ($product['quantity'] < $quantity) {
            http_response_code(400);
            echo json_encode(["message" => "Requested quantity exceeds available stock (" . $product['quantity'] . " left)."]);
            exit();
        }

        $totalPrice = $product['price'] * $quantity;

        // Insert Order
        $res = db_query(
            "INSERT INTO orders (buyer_id, product_id, quantity, total_price, payment_method, status) 
             VALUES (?, ?, ?, ?, ?, 'Pending')",
            [$userId, $productId, $quantity, $totalPrice, $paymentMethod],
            "iidds"
        );

        if ($res) {
            // Subtract quantity from product stock
            $newStock = $product['quantity'] - $quantity;
            db_query("UPDATE products SET quantity = ? WHERE id = ?", [$newStock, $productId], "di");

            // Notify the Farmer (FR9)
            $farmerNotificationMsg = "A buyer placed an order for " . $quantity . " units of your crop: " . $product['title'];
            db_query(
                "INSERT INTO notifications (user_id, title, message) VALUES (?, 'New Order Received', ?)",
                [$product['farmer_id'], $farmerNotificationMsg],
                "is"
            );

            echo json_encode(["message" => "Order placed successfully!"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to process order in database."]);
        }

    } catch (Exception $e) {
        error_log("Order Creation Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while placing order."]);
    }
    exit();
}

// ==========================================
// 3. POST - Update Order Status (Farmer or Buyer)
// ==========================================
if ($method === 'POST' && $action === 'update_status') {
    $orderId   = isset($input['orderId']) ? (int)$input['orderId'] : 0;
    $newStatus = isset($input['status']) ? trim($input['status']) : '';

    $allowedStatuses = ['Accepted', 'Rejected', 'Delivered', 'Cancelled'];

    if ($orderId <= 0 || !in_index($newStatus, $allowedStatuses)) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid order ID or status selection."]);
        exit();
    }

    try {
        // Fetch order details
        $orderRows = db_query(
            "SELECT o.*, p.farmer_id, p.title as product_title 
             FROM orders o 
             JOIN products p ON o.product_id = p.id 
             WHERE o.id = ?", 
            [$orderId], 
            "i"
        );

        if (!$orderRows || count($orderRows) === 0) {
            http_response_code(404);
            echo json_encode(["message" => "Order not found."]);
            exit();
        }

        $order = $orderRows[0];

        // Access Control checks
        if ($newStatus === 'Cancelled') {
            // Only the buyer can cancel their own order
            if ($order['buyer_id'] !== $userId) {
                http_response_code(403);
                echo json_encode(["message" => "Unauthorized. Only the buyer who placed this order can cancel it."]);
                exit();
            }
        } else {
            // Accepted, Rejected, Delivered: Only the farmer who owns the product can modify these
            if ($order['farmer_id'] !== $userId) {
                http_response_code(403);
                echo json_encode(["message" => "Unauthorized. Only the grower can update this status."]);
                exit();
            }
        }

        // Update Database status
        $res = db_query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId], "si");

        if ($res) {
            // Revert stock if order was Cancelled or Rejected
            if ($newStatus === 'Cancelled' || $newStatus === 'Rejected') {
                $productRows = db_query("SELECT quantity FROM products WHERE id = ?", [$order['product_id']], "i");
                if ($productRows && count($productRows) > 0) {
                    $revertedQty = $productRows[0]['quantity'] + $order['quantity'];
                    db_query("UPDATE products SET quantity = ? WHERE id = ?", [$revertedQty, $order['product_id']], "di");
                }
            }

            // Trigger Notifications (FR9)
            if ($newStatus === 'Cancelled') {
                // Notify the Farmer
                $msg = "Order #" . $orderId . " for your crop '" . $order['product_title'] . "' has been cancelled by the buyer.";
                db_query("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Order Cancelled', ?)", [$order['farmer_id'], $msg], "is");
            } else {
                // Notify the Buyer
                $msg = "Your order #" . $orderId . " for crop '" . $order['product_title'] . "' has been marked as " . strtolower($newStatus) . ".";
                db_query("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Order Status Updated', ?)", [$order['buyer_id'], $msg], "is");
            }

            echo json_encode(["message" => "Order status updated to '" . $newStatus . "' successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update order status."]);
        }

    } catch (Exception $e) {
        error_log("Order Update Status Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Server error occurred while updating order status."]);
    }
    exit();
}

// Helper function to check item in array
function in_index($item, $array) {
    return in_array($item, $array);
}

http_response_code(400);
echo json_encode(["message" => "Invalid API request action."]);
