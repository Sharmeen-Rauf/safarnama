<?php
// api/products.php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/db.php';

// Route Guard: Verify Authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized access. Please log in."]);
    exit();
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// ==========================================
// 1. GET - Retrieve Listings (Marketplace or Farmer Specific)
// ==========================================
if ($method === 'GET') {
    // A. Fetch single product details
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if ($id) {
        $products = db_query(
            "SELECT p.*, u.full_name as farmer_name, u.mobile_number, u.address, u.city, u.province, c.name as category_name 
             FROM products p 
             JOIN users u ON p.farmer_id = u.id 
             JOIN categories c ON p.category_id = c.id
             WHERE p.id = ?", 
            [$id], 
            "i"
        );
        if ($products && count($products) > 0) {
            echo json_encode($products[0]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Product listing not found"]);
        }
        exit();
    }
    
    // B. Fetch specific farmer's products (for Farmer Dashboard)
    $farmerId = isset($_GET['farmerId']) ? (int)$_GET['farmerId'] : null;
    if ($farmerId) {
        $products = db_query(
            "SELECT p.*, c.name as category_name 
             FROM products p 
             JOIN categories c ON p.category_id = c.id 
             WHERE p.farmer_id = ? 
             ORDER BY p.id DESC", 
            [$farmerId], 
            "i"
        );
        echo json_encode($products ?: []);
        exit();
    }
    
    // C. General Marketplace Fetch (with Search, Category, and Location Filters)
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $category = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
    $city = isset($_GET['city']) && $_GET['city'] !== '' ? '%' . $_GET['city'] . '%' : '%';
    
    if ($category) {
        $products = db_query(
            "SELECT p.*, u.full_name as farmer_name, u.city, u.province, c.name as category_name 
             FROM products p 
             JOIN users u ON p.farmer_id = u.id 
             JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'Available' 
               AND p.category_id = ? 
               AND (p.title LIKE ? OR p.description LIKE ?) 
               AND u.city LIKE ?
             ORDER BY p.id DESC", 
            [$category, $search, $search, $city], 
            "isss"
        );
    } else {
        $products = db_query(
            "SELECT p.*, u.full_name as farmer_name, u.city, u.province, c.name as category_name 
             FROM products p 
             JOIN users u ON p.farmer_id = u.id 
             JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'Available' 
               AND (p.title LIKE ? OR p.description LIKE ?) 
               AND u.city LIKE ?
             ORDER BY p.id DESC", 
            [$search, $search, $city], 
            "sss"
        );
    }
    echo json_encode($products ?: []);
    exit();
}

// ==========================================
// Write Guard: Ensure user is a Farmer for mutations
// ==========================================
if ($userRole !== 'Farmer') {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden. Only Farmers can manage product listings."]);
    exit();
}

// ==========================================
// 2. POST - Add New Product Listing
// ==========================================
if ($method === 'POST' && $action === 'add') {
    $title       = isset($input['title']) ? trim($input['title']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $price       = isset($input['price']) ? (float)$input['price'] : 0.0;
    $quantity    = isset($input['quantity']) ? (float)$input['quantity'] : 0.0;
    $unit        = isset($input['unit']) ? trim($input['unit']) : 'kg';
    $categoryId  = isset($input['categoryId']) ? (int)$input['categoryId'] : 0;
    $image       = isset($input['image']) ? $input['image'] : ''; // Base64 encoding

    if (empty($title) || $price <= 0 || $quantity <= 0 || empty($unit) || $categoryId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Please fill in all required fields with valid values."]);
        exit();
    }

    $res = db_query(
        "INSERT INTO products (farmer_id, category_id, title, description, price, quantity, unit, image_url, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Available')",
        [$userId, $categoryId, $title, $description, $price, $quantity, $unit, $image],
        "iisdddss"
    );

    if ($res) {
        echo json_encode(["message" => "Crop listing added successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error occurred while adding the listing."]);
    }
    exit();
}

// ==========================================
// 3. POST/PUT - Edit Product Listing
// ==========================================
if (($method === 'POST' || $method === 'PUT') && $action === 'edit') {
    $productId   = isset($input['id']) ? (int)$input['id'] : 0;
    $title       = isset($input['title']) ? trim($input['title']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $price       = isset($input['price']) ? (float)$input['price'] : 0.0;
    $quantity    = isset($input['quantity']) ? (float)$input['quantity'] : 0.0;
    $unit        = isset($input['unit']) ? trim($input['unit']) : 'kg';
    $categoryId  = isset($input['categoryId']) ? (int)$input['categoryId'] : 0;
    $status      = isset($input['status']) ? trim($input['status']) : 'Available';
    $image       = isset($input['image']) ? $input['image'] : '';

    if ($productId <= 0 || empty($title) || $price <= 0 || $quantity <= 0 || empty($unit) || $categoryId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Please fill in all required fields with valid values."]);
        exit();
    }

    // Verify ownership
    $ownershipCheck = db_query("SELECT id FROM products WHERE id = ? AND farmer_id = ?", [$productId, $userId], "ii");
    if (!$ownershipCheck || count($ownershipCheck) === 0) {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. You do not own this listing."]);
        exit();
    }

    if (!empty($image)) {
        $res = db_query(
            "UPDATE products 
             SET category_id = ?, title = ?, description = ?, price = ?, quantity = ?, unit = ?, image_url = ?, status = ? 
             WHERE id = ? AND farmer_id = ?",
            [$categoryId, $title, $description, $price, $quantity, $unit, $image, $status, $productId, $userId],
            "iisdddssii"
        );
    } else {
        $res = db_query(
            "UPDATE products 
             SET category_id = ?, title = ?, description = ?, price = ?, quantity = ?, unit = ?, status = ? 
             WHERE id = ? AND farmer_id = ?",
            [$categoryId, $title, $description, $price, $quantity, $unit, $status, $productId, $userId],
            "iisdddssi"
        );
    }

    if ($res) {
        echo json_encode(["message" => "Crop listing updated successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error occurred while updating the listing."]);
    }
    exit();
}

// ==========================================
// 4. POST/DELETE - Delete Product Listing
// ==========================================
if (($method === 'POST' || $method === 'DELETE') && $action === 'delete') {
    $productId = isset($input['id']) ? (int)$input['id'] : 0;

    if ($productId <= 0) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid product ID."]);
        exit();
    }

    // Verify ownership
    $ownershipCheck = db_query("SELECT id FROM products WHERE id = ? AND farmer_id = ?", [$productId, $userId], "ii");
    if (!$ownershipCheck || count($ownershipCheck) === 0) {
        http_response_code(403);
        echo json_encode(["message" => "Unauthorized access. You do not own this listing."]);
        exit();
    }

    $res = db_query("DELETE FROM products WHERE id = ? AND farmer_id = ?", [$productId, $userId], "ii");
    
    if ($res) {
        echo json_encode(["message" => "Crop listing deleted successfully!"]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error occurred while deleting the listing."]);
    }
    exit();
}

http_response_code(400);
echo json_encode(["message" => "Invalid API request action."]);
