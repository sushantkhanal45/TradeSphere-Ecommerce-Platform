<?php
ob_start();
session_start();
include "config/db.php";

function send_json($data) {
    if (ob_get_length()) {
        ob_clean();
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user'])) {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId > 0) {
        $_SESSION['pending_cart_product'] = $productId;
    }

    send_json([
        "status" => "login_required",
        "message" => "Please login first.",
        "redirect" => "login.php"
    ]);
}

if (!isset($_POST['product_id']) || (int)$_POST['product_id'] <= 0) {
    send_json([
        "status" => "error",
        "message" => "Product ID missing."
    ]);
}

$productId = (int)$_POST['product_id'];
$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    send_json([
        "status" => "login_required",
        "message" => "Please login again.",
        "redirect" => "login.php"
    ]);
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
    send_json([
        "status" => "error",
        "message" => "Product not found."
    ]);
}

if ((int)$product['user_id'] === $userId) {
    send_json([
        "status" => "error",
        "message" => "You cannot buy your own listing."
    ]);
}

if ($product['status'] === 'sold') {
    send_json([
        "status" => "error",
        "message" => "This product is already sold."
    ]);
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

    $update = $conn->query("
        UPDATE cart
        SET quantity = $newQty
        WHERE id = $cartId
    ");

    if (!$update) {
        send_json([
            "status" => "error",
            "message" => "Could not update cart."
        ]);
    }
} else {
    $insert = $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($userId, $productId, 1)
    ");

    if (!$insert) {
        send_json([
            "status" => "error",
            "message" => "Could not add to cart."
        ]);
    }
}

$countRes = $conn->query("
    SELECT SUM(quantity) AS total_items
    FROM cart
    WHERE user_id = $userId
");

$countRow = $countRes ? $countRes->fetch_assoc() : ['total_items' => 0];

send_json([
    "status" => "success",
    "message" => "Added to cart",
    "cart_count" => (int)($countRow['total_items'] ?? 0)
]);