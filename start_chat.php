<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($productId <= 0) {
    die("Invalid product.");
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$buyerId = (int)$user['id'];

$productRes = $conn->query("
    SELECT id, user_id, name
    FROM products
    WHERE id = $productId
    LIMIT 1
");

$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    die("Product not found.");
}

$sellerId = (int)$product['user_id'];

if ($buyerId === $sellerId) {
    die("You cannot chat with yourself about your own product.");
}

$roomRes = $conn->query("
    SELECT id
    FROM chat_rooms
    WHERE buyer_id = $buyerId
    AND seller_id = $sellerId
    AND product_id = $productId
    LIMIT 1
");

if ($roomRes && $roomRes->num_rows > 0) {
    $room = $roomRes->fetch_assoc();
    $roomId = (int)$room['id'];
} else {
    $conn->query("
        INSERT INTO chat_rooms (buyer_id, seller_id, product_id)
        VALUES ($buyerId, $sellerId, $productId)
    ");

    $roomId = (int)$conn->insert_id;
}

header("Location: chat.php?room_id=" . $roomId);
exit();
?>