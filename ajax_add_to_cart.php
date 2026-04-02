<?php
session_start();
include "config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Login required"
    ]);
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found"
    ]);
    exit();
}

$userId = (int)$user['id'];
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid product"
    ]);
    exit();
}

$productCheck = $conn->query("SELECT * FROM products WHERE id=$productId LIMIT 1");
$product = $productCheck ? $productCheck->fetch_assoc() : null;

if (!$product) {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found"
    ]);
    exit();
}

if ($product['status'] === 'sold') {
    echo json_encode([
        "status" => "error",
        "message" => "Product is sold"
    ]);
    exit();
}

$existing = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND product_id=$productId LIMIT 1");

if ($existing && $existing->num_rows > 0) {
    $row = $existing->fetch_assoc();
    $newQty = (int)$row['quantity'] + 1;
    $conn->query("UPDATE cart SET quantity=$newQty WHERE id=" . (int)$row['id']);
} else {
    $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, 1)");
}

$countRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
$countRow = $countRes ? $countRes->fetch_assoc() : null;
$cartCount = ($countRow && $countRow['total_items']) ? (int)$countRow['total_items'] : 0;

echo json_encode([
    "status" => "success",
    "message" => "Added to cart",
    "cart_count" => $cartCount
]);