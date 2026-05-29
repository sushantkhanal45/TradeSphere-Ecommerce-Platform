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
    send_json([
        "status" => "login_required",
        "message" => "Please login first.",
        "redirect" => "login.php"
    ]);
}

$cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$action = $_POST['action'] ?? "";

if ($cartId <= 0 || !in_array($action, ["increase", "decrease"], true)) {
    send_json([
        "status" => "error",
        "message" => "Invalid cart request."
    ]);
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id
    FROM users
    WHERE email='$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    send_json([
        "status" => "error",
        "message" => "User not found."
    ]);
}

$userId = (int)$user['id'];

$cartRes = $conn->query("
    SELECT 
        cart.id AS cart_id,
        cart.quantity,
        p.id AS product_id,
        p.price,
        p.status,
        p.user_id AS seller_id,
        (
            SELECT po.offer_amount
            FROM product_offers po
            WHERE po.product_id = p.id
            AND po.buyer_id = $userId
            AND po.seller_id = p.user_id
            AND po.status = 'accepted'
            ORDER BY po.id DESC
            LIMIT 1
        ) AS accepted_offer_amount
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    WHERE cart.id = $cartId
    AND cart.user_id = $userId
    LIMIT 1
");

$item = $cartRes ? $cartRes->fetch_assoc() : null;

if (!$item) {
    send_json([
        "status" => "error",
        "message" => "Cart item not found."
    ]);
}

if ($item['status'] === 'sold') {
    send_json([
        "status" => "error",
        "message" => "This item is already sold."
    ]);
}

$currentQty = (int)$item['quantity'];
$newQty = $currentQty;

if ($action === "increase") {
    $newQty++;
}

if ($action === "decrease") {
    $newQty--;

    if ($newQty < 1) {
        $newQty = 1;
    }
}

$update = $conn->query("
    UPDATE cart
    SET quantity = $newQty
    WHERE id = $cartId
    AND user_id = $userId
");

if (!$update) {
    send_json([
        "status" => "error",
        "message" => "Could not update quantity."
    ]);
}

$unitPrice = !empty($item['accepted_offer_amount'])
    ? (float)$item['accepted_offer_amount']
    : (float)$item['price'];

$subtotal = $unitPrice * $newQty;

$totalRes = $conn->query("
    SELECT 
        cart.quantity,
        p.id AS product_id,
        p.price,
        p.status,
        p.user_id AS seller_id,
        (
            SELECT po.offer_amount
            FROM product_offers po
            WHERE po.product_id = p.id
            AND po.buyer_id = $userId
            AND po.seller_id = p.user_id
            AND po.status = 'accepted'
            ORDER BY po.id DESC
            LIMIT 1
        ) AS accepted_offer_amount
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    WHERE cart.user_id = $userId
");

$cartTotal = 0;
$cartCount = 0;

if ($totalRes) {
    while ($row = $totalRes->fetch_assoc()) {
        $qty = (int)$row['quantity'];
        $cartCount += $qty;

        if ($row['status'] !== 'sold') {
            $price = !empty($row['accepted_offer_amount'])
                ? (float)$row['accepted_offer_amount']
                : (float)$row['price'];

            $cartTotal += ($price * $qty);
        }
    }
}

send_json([
    "status" => "success",
    "message" => "Cart updated.",
    "quantity" => $newQty,
    "subtotal" => $subtotal,
    "cart_total" => $cartTotal,
    "cart_count" => $cartCount
]);