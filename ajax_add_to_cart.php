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
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($productId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid product."
    ]);
    exit();
}

$productRes = $conn->query("SELECT * FROM products WHERE id = $productId LIMIT 1");
$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found."
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
    WHERE user_id = $userId AND product_id = $productId
    LIMIT 1
");

if ($cartCheck && $cartCheck->num_rows > 0) {
    $cartRow = $cartCheck->fetch_assoc();
    $cartId = (int)$cartRow['id'];
    $newQty = (int)$cartRow['quantity'] + 1;

    $updated = $conn->query("UPDATE cart SET quantity = $newQty WHERE id = $cartId");

    if (!$updated) {
        echo json_encode([
            "status" => "error",
            "message" => "Could not update cart."
        ]);
        exit();
    }
} else {
    $inserted = $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($userId, $productId, 1)
    ");

    if (!$inserted) {
        echo json_encode([
            "status" => "error",
            "message" => "Could not add product to cart."
        ]);
        exit();
    }
}

$totalCartRes = $conn->query("
    SELECT COALESCE(SUM(quantity), 0) AS total_items
    FROM cart
    WHERE user_id = $userId
");
$totalCart = $totalCartRes ? (int)$totalCartRes->fetch_assoc()['total_items'] : 0;

echo json_encode([
    "status" => "success",
    "message" => "Product added to cart.",
    "cart_count" => $totalCart
]);
exit();