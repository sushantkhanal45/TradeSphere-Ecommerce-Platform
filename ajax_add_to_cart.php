<?php
session_start();
include "config/db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Login required"]);
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes->fetch_assoc();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit();
}

$userId = (int)$user['id'];

$productId = (int)($_POST['product_id'] ?? 0);

$productCheck = $conn->query("SELECT * FROM products WHERE id=$productId LIMIT 1");
$product = $productCheck->fetch_assoc();

if (!$product) {
    echo json_encode(["status" => "error", "message" => "Product not found"]);
    exit();
}

if ($product['status'] === 'sold') {
    echo json_encode(["status" => "error", "message" => "Product is sold"]);
    exit();
}

$existing = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND product_id=$productId LIMIT 1");

if ($existing && $existing->num_rows > 0) {
    $row = $existing->fetch_assoc();
    $newQty = $row['quantity'] + 1;
    $conn->query("UPDATE cart SET quantity=$newQty WHERE id=".$row['id']);
} else {
    $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, 1)");
}

echo json_encode(["status" => "success"]);