<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Please login first."
    ]);
    exit();
}

if (!isset($_POST['product_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Product ID missing."
    ]);
    exit();
}

$productId = (int)$_POST['product_id'];
$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found."
    ]);
    exit();
}

$userId = (int)$user['id'];

$productRes = $conn->query("
    SELECT id, user_id, status
    FROM products
    WHERE id = $productId
    LIMIT 1
");
$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found."
    ]);
    exit();
}

if ((int)$product['user_id'] === $userId) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot buy your own listing."
    ]);
    exit();
}

if ($product['status'] === 'sold') {
    echo json_encode([
        "status" => "error",
        "message" => "This product is already sold."
    ]);
    exit();
}

$cartCheck = $conn->query("
    SELECT id, quantity
    FROM cart
    WHERE user_id = $userId
    AND product_id = $productId
    LIMIT 1
");

if ($cartCheck && $cartCheck->num_rows > 0) {
    $cartRow = $cartCheck->fetch_assoc();
    $cartId = (int)$cartRow['id'];
    $newQty = (int)$cartRow['quantity'] + 1;

    $conn->query("
        UPDATE cart
        SET quantity = $newQty
        WHERE id = $cartId
    ");
} else {
    $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($userId, $productId, 1)
    ");
}

$countRes = $conn->query("
    SELECT SUM(quantity) AS total_items
    FROM cart
    WHERE user_id = $userId
");
$countRow = $countRes ? $countRes->fetch_assoc() : ['total_items' => 0];

echo json_encode([
    "status" => "success",
    "message" => "Added to cart",
    "cart_count" => (int)($countRow['total_items'] ?? 0)
]);
exit();