<?php
ob_start();
session_start();

include "config/db.php";
include "includes/rsa_helper.php";

function send_json($data) {
    if (ob_get_length()) {
        ob_clean();
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user'])) {
    send_json(["status" => "error", "message" => "Not logged in."]);
}

$roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$message = trim($_POST['message'] ?? '');
$messageType = trim($_POST['message_type'] ?? 'normal');

if ($roomId <= 0 || $message === '') {
    send_json(["status" => "error", "message" => "Message cannot be empty."]);
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id, name
    FROM users
    WHERE email = '$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    send_json(["status" => "error", "message" => "User not found."]);
}

$senderId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT cr.*, p.name AS product_name
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    WHERE cr.id = $roomId
    AND (cr.buyer_id = $senderId OR cr.seller_id = $senderId)
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    send_json(["status" => "error", "message" => "Chat room not found."]);
}

$receiverId = ((int)$room['buyer_id'] === $senderId)
    ? (int)$room['seller_id']
    : (int)$room['buyer_id'];

$allowedTypes = [
    "normal",
    "offer",
    "acceptance",
    "delivery_agreement",
    "cancellation_request"
];

if (!in_array($messageType, $allowedTypes, true)) {
    $messageType = "normal";
}

$signature = null;
$signedData = null;

$importantTypes = [
    "offer",
    "acceptance",
    "delivery_agreement",
    "cancellation_request"
];

if (in_array($messageType, $importantTypes, true)) {
    $signedData = json_encode([
        "action" => "chat_" . $messageType,
        "room_id" => $roomId,
        "sender_id" => $senderId,
        "receiver_id" => $receiverId,
        "product_id" => (int)$room['product_id'],
        "message" => $message,
        "message_type" => $messageType,
        "timestamp" => date("Y-m-d H:i:s")
    ]);

    $signature = signData($signedData);
}

$insert = insertEncryptedChatMessage(
    $conn,
    $roomId,
    $senderId,
    $receiverId,
    $message,
    $messageType,
    $signature,
    $signedData
);

if (!$insert) {
    send_json([
        "status" => "error",
        "message" => "Could not send message: " . $conn->error
    ]);
}

if ($signature && $signedData) {
    storeSignatureRecord(
        $conn,
        $senderId,
        "chat_" . $messageType,
        $roomId,
        $signedData,
        $signature
    );
}

$notificationMessage = "New message about " . $room['product_name'];
$safeNotification = $conn->real_escape_string($notificationMessage);

$conn->query("
    INSERT INTO notifications (user_id, order_id, message)
    VALUES ($receiverId, NULL, '$safeNotification')
");

send_json(["status" => "success", "message" => "Message sent."]);
?>