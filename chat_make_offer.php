<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Please login first."]);
    exit();
}

$roomId = (int)($_POST['room_id'] ?? 0);
$offerAmount = (float)($_POST['offer_amount'] ?? 0);

if ($roomId <= 0 || $offerAmount <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid offer amount."]);
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}

$buyerId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT cr.*, p.name AS product_name
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    WHERE cr.id = $roomId
    AND cr.buyer_id = $buyerId
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    echo json_encode(["status" => "error", "message" => "Only buyer can make offer."]);
    exit();
}

$productId = (int)$room['product_id'];
$sellerId = (int)$room['seller_id'];

$conn->query("
    UPDATE product_offers
    SET status='expired'
    WHERE product_id=$productId
    AND buyer_id=$buyerId
    AND seller_id=$sellerId
    AND status='pending'
");

$signData = json_encode([
    "action" => "make_offer",
    "product_id" => $productId,
    "buyer_id" => $buyerId,
    "seller_id" => $sellerId,
    "offer_amount" => $offerAmount,
    "timestamp" => date("Y-m-d H:i:s")
]);

$buyerSignature = signData($signData);
$safeSignature = $conn->real_escape_string($buyerSignature ?? "");

$conn->query("
    INSERT INTO product_offers
    (product_id, buyer_id, seller_id, offer_amount, buyer_signature)
    VALUES
    ($productId, $buyerId, $sellerId, $offerAmount, '$safeSignature')
");

$offerId = (int)$conn->insert_id;

$message = "Buyer offered Rs " . number_format($offerAmount, 2) . " for " . $room['product_name'];
$safeMessage = $conn->real_escape_string($message);

$conn->query("
    INSERT INTO chat_messages
    (room_id, sender_id, receiver_id, message_text, message_type, signature)
    VALUES
    ($roomId, $buyerId, $sellerId, '$safeMessage', 'offer', '$safeSignature')
");

$conn->query("
    INSERT INTO notifications (user_id, order_id, message)
    VALUES (
        $sellerId,
        NULL,
        '" . $conn->real_escape_string("New offer received: Rs " . number_format($offerAmount, 2) . " for " . $room['product_name']) . "'
    )
");

echo json_encode([
    "status" => "success",
    "message" => "Offer sent successfully.",
    "offer_id" => $offerId
]);
?>