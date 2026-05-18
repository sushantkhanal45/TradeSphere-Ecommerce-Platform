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

$roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;

if ($roomId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid chat room."
    ]);
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id
    FROM users
    WHERE email = '$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found."
    ]);
    exit();
}

$buyerId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT cr.*, p.status AS product_status
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    WHERE cr.id = $roomId
    AND cr.buyer_id = $buyerId
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    echo json_encode([
        "status" => "error",
        "message" => "Only buyer can add this negotiated item."
    ]);
    exit();
}

$productId = (int)$room['product_id'];
$sellerId = (int)$room['seller_id'];

if ($room['product_status'] === 'sold') {
    echo json_encode([
        "status" => "error",
        "message" => "This product is already sold."
    ]);
    exit();
}

$offerRes = $conn->query("
    SELECT id, offer_amount
    FROM product_offers
    WHERE product_id = $productId
    AND buyer_id = $buyerId
    AND seller_id = $sellerId
    AND status = 'accepted'
    ORDER BY responded_at DESC
    LIMIT 1
");

$offer = $offerRes ? $offerRes->fetch_assoc() : null;

if (!$offer) {
    echo json_encode([
        "status" => "error",
        "message" => "No accepted offer found."
    ]);
    exit();
}

$checkCart = $conn->query("
    SELECT id, quantity
    FROM cart
    WHERE user_id = $buyerId
    AND product_id = $productId
    LIMIT 1
");

if ($checkCart && $checkCart->num_rows > 0) {
    $cartRow = $checkCart->fetch_assoc();
    $cartId = (int)$cartRow['id'];

    $conn->query("
        UPDATE cart
        SET quantity = 1
        WHERE id = $cartId
    ");
} else {
    $conn->query("
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES ($buyerId, $productId, 1)
    ");
}

echo json_encode([
    "status" => "success",
    "message" => "Negotiated item added to cart.",
    "offer_amount" => (float)$offer['offer_amount']
]);
?>