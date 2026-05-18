<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Please login first."]);
    exit();
}

$offerId = (int)($_POST['offer_id'] ?? 0);
$action = $_POST['action_type'] ?? "";

if ($offerId <= 0 || !in_array($action, ["accept", "reject"], true)) {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
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
    echo json_encode(["status" => "error", "message" => "Offer not found or already handled."]);
    exit();
}

$newStatus = ($action === "accept") ? "accepted" : "rejected";

$signData = json_encode([
    "action" => $action . "_offer",
    "offer_id" => $offerId,
    "product_id" => (int)$offer['product_id'],
    "buyer_id" => (int)$offer['buyer_id'],
    "seller_id" => $sellerId,
    "offer_amount" => (float)$offer['offer_amount'],
    "timestamp" => date("Y-m-d H:i:s")
]);

$sellerSignature = signData($signData);
$safeSignature = $conn->real_escape_string($sellerSignature ?? "");

$conn->query("
    UPDATE product_offers
    SET status='$newStatus',
        seller_signature='$safeSignature',
        responded_at=NOW()
    WHERE id=$offerId
");

$roomId = (int)$offer['room_id'];
$buyerId = (int)$offer['buyer_id'];

if ($action === "accept") {
    $message = "Seller accepted offer Rs " . number_format((float)$offer['offer_amount'], 2) . " for " . $offer['product_name'];
    $noti = "Your offer was accepted for " . $offer['product_name'];
} else {
    $message = "Seller rejected offer Rs " . number_format((float)$offer['offer_amount'], 2) . " for " . $offer['product_name'];
    $noti = "Your offer was rejected for " . $offer['product_name'];
}

$conn->query("
    INSERT INTO chat_messages
    (room_id, sender_id, receiver_id, message_text, message_type, signature)
    VALUES
    ($roomId, $sellerId, $buyerId, '" . $conn->real_escape_string($message) . "', '$newStatus', '$safeSignature')
");

$conn->query("
    INSERT INTO notifications (user_id, order_id, message)
    VALUES ($buyerId, NULL, '" . $conn->real_escape_string($noti) . "')
");

echo json_encode(["status" => "success", "message" => $message]);
?>