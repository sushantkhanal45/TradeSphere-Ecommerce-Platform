<?php
ob_start();
session_start();

include "config/db.php";
include "includes/rsa_helper.php";

function send_json($data) {
    if (ob_get_length()) ob_clean();
    header("Content-Type: application/json");
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user'])) {
    send_json(["status" => "error", "message" => "Please login first."]);
}

$roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$offerAmount = isset($_POST['offer_amount']) ? (float)$_POST['offer_amount'] : 0;

if ($roomId <= 0 || $offerAmount <= 0) {
    send_json(["status" => "error", "message" => "Invalid offer amount."]);
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
    send_json(["status" => "error", "message" => "User not found."]);
}

$buyerId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT cr.*, p.name AS product_name, p.price AS product_price, p.status AS product_status
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    WHERE cr.id = $roomId
    AND cr.buyer_id = $buyerId
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    send_json(["status" => "error", "message" => "Only the buyer can make an offer."]);
}

if ($room['product_status'] === 'sold') {
    send_json(["status" => "error", "message" => "This product is already sold."]);
}

$productId = (int)$room['product_id'];
$sellerId = (int)$room['seller_id'];
$timestamp = date("Y-m-d H:i:s");

$conn->query("
    UPDATE product_offers
    SET status = 'expired'
    WHERE product_id = $productId
    AND buyer_id = $buyerId
    AND seller_id = $sellerId
    AND status = 'pending'
");

$signedData = json_encode([
    "action" => "offer_created",
    "product_id" => $productId,
    "buyer_id" => $buyerId,
    "seller_id" => $sellerId,
    "offer_amount" => $offerAmount,
    "timestamp" => $timestamp
]);

$buyerSignature = signData($signedData);

if (!$buyerSignature) {
    send_json(["status" => "error", "message" => "RSA signing failed for offer creation."]);
}

$safeSignature = $conn->real_escape_string($buyerSignature);
$safeSignedData = $conn->real_escape_string($signedData);

$insertOffer = $conn->query("
    INSERT INTO product_offers
    (
        product_id,
        buyer_id,
        seller_id,
        offer_amount,
        status,
        buyer_signature,
        buyer_signed_data
    )
    VALUES
    (
        $productId,
        $buyerId,
        $sellerId,
        $offerAmount,
        'pending',
        '$safeSignature',
        '$safeSignedData'
    )
");

if (!$insertOffer) {
    send_json(["status" => "error", "message" => "Could not create offer: " . $conn->error]);
}

$offerId = (int)$conn->insert_id;

$signatureSaved = storeSignatureRecord(
    $conn,
    $buyerId,
    "offer_created",
    $offerId,
    $signedData,
    $buyerSignature
);

if (!$signatureSaved) {
    send_json(["status" => "error", "message" => "Offer created but RSA audit insert failed: " . $conn->error]);
}

$message = "Buyer offered Rs " . number_format($offerAmount, 2) . " for " . $room['product_name'];

$insertMessage = insertEncryptedChatMessage(
    $conn,
    $roomId,
    $buyerId,
    $sellerId,
    $message,
    "offer",
    $buyerSignature,
    $signedData
);

if (!$insertMessage) {
    send_json(["status" => "error", "message" => "Offer was created, but chat message failed: " . $conn->error]);
}

$notification = "New offer received: Rs " . number_format($offerAmount, 2) . " for " . $room['product_name'];
$safeNotification = $conn->real_escape_string($notification);

$conn->query("
    INSERT INTO notifications (user_id, order_id, message)
    VALUES ($sellerId, NULL, '$safeNotification')
");

send_json([
    "status" => "success",
    "message" => "Offer sent successfully.",
    "offer_id" => $offerId
]);
?>