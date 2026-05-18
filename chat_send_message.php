<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in."
    ]);
    exit();
}

$roomId = isset($_POST['room_id']) ? (int)$_POST['room_id'] : 0;
$message = trim($_POST['message'] ?? '');
$messageType = trim($_POST['message_type'] ?? 'normal');

if ($roomId <= 0 || $message === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Message cannot be empty."
    ]);
    exit();
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
    echo json_encode([
        "status" => "error",
        "message" => "User not found."
    ]);
    exit();
}

$senderId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT 
        cr.*,
        p.name AS product_name
    FROM chat_rooms cr
    INNER JOIN products p ON cr.product_id = p.id
    WHERE cr.id = $roomId
    AND (cr.buyer_id = $senderId OR cr.seller_id = $senderId)
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    echo json_encode([
        "status" => "error",
        "message" => "Chat room not found."
    ]);
    exit();
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

if (in_array($messageType, ["offer", "acceptance", "delivery_agreement", "cancellation_request"], true)) {
    $dataToSign = json_encode([
        "room_id" => $roomId,
        "sender_id" => $senderId,
        "receiver_id" => $receiverId,
        "product_id" => (int)$room['product_id'],
        "message" => $message,
        "message_type" => $messageType,
        "timestamp" => date("Y-m-d H:i:s")
    ]);

    $signature = signData($dataToSign);
}

$safeMessage = $conn->real_escape_string($message);
$safeType = $conn->real_escape_string($messageType);
$signatureSql = $signature ? "'" . $conn->real_escape_string($signature) . "'" : "NULL";

$insert = $conn->query("
    INSERT INTO chat_messages
    (room_id, sender_id, receiver_id, message_text, message_type, signature)
    VALUES
    ($roomId, $senderId, $receiverId, '$safeMessage', '$safeType', $signatureSql)
");

if ($insert) {
    $notificationMessage = "New message about " . $room['product_name'];

    $conn->query("
        INSERT INTO notifications (user_id, order_id, message)
        VALUES (
            $receiverId,
            NULL,
            '" . $conn->real_escape_string($notificationMessage) . "'
        )
    ");

    echo json_encode([
        "status" => "success",
        "message" => "Message sent."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not send message."
    ]);
}
?>