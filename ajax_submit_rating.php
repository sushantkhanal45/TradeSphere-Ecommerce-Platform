<?php
session_start();
include "config/db.php";
include "includes/rating_helper.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Please login first."
    ]);
    exit();
}

if (!isset($_POST['order_id'], $_POST['rating'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing rating data."
    ]);
    exit();
}

$orderId = (int)$_POST['order_id'];
$rating = (int)$_POST['rating'];
$reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : "";

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        "status" => "error",
        "message" => "Rating must be between 1 and 5."
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

$buyerUserId = (int)$user['id'];
$reviewTextEscaped = $conn->real_escape_string($reviewText);

$orderRes = $conn->query("
    SELECT 
        o.id,
        o.user_id,
        o.seller_user_id,
        o.product_id,
        o.payment_status,
        o.seller_delivery_status,
        o.buyer_received
    FROM orders o
    WHERE o.id = $orderId
    AND o.user_id = $buyerUserId
    LIMIT 1
");

$order = $orderRes ? $orderRes->fetch_assoc() : null;

if (!$order) {
    echo json_encode([
        "status" => "error",
        "message" => "Order not found."
    ]);
    exit();
}

$productId = (int)$order['product_id'];
$sellerUserId = (int)$order['seller_user_id'];

if ($buyerUserId === $sellerUserId) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot rate your own product."
    ]);
    exit();
}

if ($order['payment_status'] !== 'paid') {
    echo json_encode([
        "status" => "error",
        "message" => "Only paid orders can be rated."
    ]);
    exit();
}

if ($order['seller_delivery_status'] !== 'delivered' || (int)$order['buyer_received'] !== 1) {
    echo json_encode([
        "status" => "error",
        "message" => "You can rate only after confirming delivery."
    ]);
    exit();
}

$existingRes = $conn->query("
    SELECT id
    FROM product_ratings
    WHERE order_id = $orderId
    LIMIT 1
");

if ($existingRes && $existingRes->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You have already rated this order."
    ]);
    exit();
}

$insert = $conn->query("
    INSERT INTO product_ratings (
        order_id,
        product_id,
        buyer_user_id,
        seller_user_id,
        rating,
        review_text
    ) VALUES (
        $orderId,
        $productId,
        $buyerUserId,
        $sellerUserId,
        $rating,
        '$reviewTextEscaped'
    )
");

if (!$insert) {
    echo json_encode([
        "status" => "error",
        "message" => "Could not save rating."
    ]);
    exit();
}

updateProductRatingSummary($conn, $productId);

$productRes = $conn->query("
    SELECT average_rating, rating_count
    FROM products
    WHERE id = $productId
    LIMIT 1
");
$product = $productRes ? $productRes->fetch_assoc() : null;

echo json_encode([
    "status" => "success",
    "message" => "Rating submitted successfully.",
    "average_rating" => $product ? $product['average_rating'] : "0.00",
    "rating_count" => $product ? (int)$product['rating_count'] : 0
]);
exit();