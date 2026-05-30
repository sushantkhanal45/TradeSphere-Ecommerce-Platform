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

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$action = $_POST['action'] ?? "";

if ($productId <= 0 || !in_array($action, ["increase", "decrease"], true)) {
    send_json([
        "status" => "error",
        "message" => "Invalid quantity request."
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
        "status" => "login_required",
        "message" => "Please login again.",
        "redirect" => "login.php"
    ]);
}

$userId = (int)$user['id'];

$productRes = $conn->query("
    SELECT id, user_id, status
    FROM products
    WHERE id=$productId
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

if ($product['status'] === "sold") {
    send_json([
        "status" => "error",
        "message" => "This product is already sold."
    ]);
}

$cartRes = $conn->query("
    SELECT id, quantity
    FROM cart
    WHERE user_id=$userId
    AND product_id=$productId
    LIMIT 1
");

$cart = $cartRes ? $cartRes->fetch_assoc() : null;

if (!$cart) {
    send_json([
        "status" => "error",
        "message" => "This product is not in your cart yet."
    ]);
}

$currentQty = (int)$cart['quantity'];
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

$cartId = (int)$cart['id'];

$update = $conn->query("
    UPDATE cart
    SET quantity=$newQty
    WHERE id=$cartId
    AND user_id=$userId
");

if (!$update) {
    send_json([
        "status" => "error",
        "message" => "Could not update quantity."
    ]);
}

$countRes = $conn->query("
    SELECT SUM(quantity) AS total_items
    FROM cart
    WHERE user_id=$userId
");

$countRow = $countRes ? $countRes->fetch_assoc() : ['total_items' => 0];

send_json([
    "status" => "success",
    "message" => "Cart quantity updated.",
    "quantity" => $newQty,
    "cart_count" => (int)($countRow['total_items'] ?? 0)
]);
