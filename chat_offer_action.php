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

$offerId = isset($_POST['offer_id']) ? (int)$_POST['offer_id'] : 0;
$action = $_POST['action_type'] ?? "";

if ($offerId <= 0 || !in_array($action, ["accept", "reject"], true)) {
    send_json(["status" => "error", "message" => "Invalid action."]);
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

$sellerId = (int)$user['id'];

$offerRes = $conn->query("
    SELECT po.*, p.name AS product_name, cr.id AS room_id
    FROM product_offers po
    INNER JOIN products p ON po.product_id = p.id
    INNER JOIN chat_rooms cr
        ON cr.product_id = po.product_id
        AND cr.buyer_id = po.buyer_id
        AND cr.seller_id = po.seller_id
    WHERE po.id = $offerId
    AND po.seller_id = $sellerId
    AND po.status = 'pending'
    LIMIT 1
");

$offer = $offerRes ? $offerRes->fetch_assoc() : null;

if (!$offer) {
    send_json(["status" => "error", "message" => "Offer not found or already handled."]);
}

$newStatus = ($action === "accept") ? "accepted" : "rejected";
$timestamp = date("Y-m-d H:i:s");

$signedData = json_encode([
    "action" => "offer_" . $newStatus,
    "offer_id" => $offerId,
    "product_id" => (int)$offer['product_id'],
    "buyer_id" => (int)$offer['buyer_id'],
    "seller_id" => $sellerId,
    "offer_amount" => (float)$offer['offer_amount'],
    "timestamp" => $timestamp
]);

$sellerSignature = signData($signedData);

if (!$sellerSignature) {
    send_json(["status" => "error", "message" => "RSA signing failed for offer action."]);
}

$safeSignature = $conn->real_escape_string($sellerSignature);
$safeSignedData = $conn->real_escape_string($signedData);

$update = $conn->query("
    UPDATE product_offers
    SET status = '$newStatus',
        seller_signature = '$safeSignature',
        seller_signed_data = '$safeSignedData',
        responded_at = NOW()
    WHERE id = $offerId
");

if (!$update) {
    send_json(["status" => "error", "message" => "Could not update offer: " . $conn->error]);
}

$signatureSaved = storeSignatureRecord(
    $conn,
    $sellerId,
    "offer_" . $newStatus,
    $offerId,
    $signedData,
    $sellerSignature
);

if (!$signatureSaved) {
    send_json(["status" => "error", "message" => "Offer updated but RSA audit insert failed: " . $conn->error]);
}

$roomId = (int)$offer['room_id'];
$buyerId = (int)$offer['buyer_id'];
$offerAmount = (float)$offer['offer_amount'];

if ($action === "accept") {
    $message = "Seller accepted offer Rs " . number_format($offerAmount, 2) . " for " . $offer['product_name'];
    $notification = "Your offer was accepted for " . $offer['product_name'];
} else {
    $message = "Seller rejected offer Rs " . number_format($offerAmount, 2) . " for " . $offer['product_name'];
    $notification = "Your offer was rejected for " . $offer['product_name'];
}

$insertMessage = insertEncryptedChatMessage(
    $conn,
    $roomId,
    $sellerId,
    $buyerId,
    $message,
    $newStatus,
    $sellerSignature,
    $signedData
);

if (!$insertMessage) {
    send_json(["status" => "error", "message" => "Offer updated, but chat message failed: " . $conn->error]);
}

$safeNotification = $conn->real_escape_string($notification);

$conn->query("
    INSERT INTO notifications (user_id, order_id, message)
    VALUES ($buyerId, NULL, '$safeNotification')
");

send_json([
    "status" => "success",
    "message" => $message
]);
?>