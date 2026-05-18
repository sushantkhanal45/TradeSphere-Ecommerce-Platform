<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in.",
        "messages" => []
    ]);
    exit();
}

$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($roomId <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid room.",
        "messages" => []
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
        "message" => "User not found.",
        "messages" => []
    ]);
    exit();
}

$userId = (int)$user['id'];

$roomRes = $conn->query("
    SELECT *
    FROM chat_rooms
    WHERE id = $roomId
    AND (buyer_id = $userId OR seller_id = $userId)
    LIMIT 1
");

$room = $roomRes ? $roomRes->fetch_assoc() : null;

if (!$room) {
    echo json_encode([
        "status" => "error",
        "message" => "Chat room not found or access denied.",
        "messages" => []
    ]);
    exit();
}

/* Mark only chat messages as read when chat room is opened */
$conn->query("
    UPDATE chat_messages
    SET is_read = 1
    WHERE room_id = $roomId
    AND receiver_id = $userId
");
$conn->query("
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = $userId
    AND is_read = 0
    AND (
        message LIKE '%New message about%'
        OR message LIKE '%message%'
        OR message LIKE '%chat%'
    )
");


/*
    Do NOT mark notification table here.
    Normal messages are handled only by chat_messages.is_read and the 💬 icon.
*/

$msgRes = $conn->query("
    SELECT 
        m.*,
        u.name AS sender_name
    FROM chat_messages m
    INNER JOIN users u ON m.sender_id = u.id
    WHERE m.room_id = $roomId
    ORDER BY m.created_at ASC
");

$messages = [];

if ($msgRes) {
    while ($row = $msgRes->fetch_assoc()) {
        $offerData = null;

        if ($row['message_type'] === 'offer') {
            $offerRes = $conn->query("
                SELECT *
                FROM product_offers
                WHERE product_id = " . (int)$room['product_id'] . "
                AND buyer_id = " . (int)$room['buyer_id'] . "
                AND seller_id = " . (int)$room['seller_id'] . "
                ORDER BY id DESC
                LIMIT 1
            ");

            $offerData = $offerRes ? $offerRes->fetch_assoc() : null;
        }

        $messages[] = [
            "id" => (int)$row['id'],
            "sender_id" => (int)$row['sender_id'],
            "sender_name" => $row['sender_name'],
            "message_text" => $row['message_text'],
            "message_type" => $row['message_type'],
            "is_mine" => ((int)$row['sender_id'] === $userId),
            "is_signed" => !empty($row['signature']),
            "created_at" => $row['created_at'],
            "offer" => $offerData ? [
                "id" => (int)$offerData['id'],
                "amount" => (float)$offerData['offer_amount'],
                "status" => $offerData['status'],
                "can_respond" => (
                    (int)$offerData['seller_id'] === $userId &&
                    $offerData['status'] === 'pending'
                )
            ] : null
        ];
    }
}

echo json_encode([
    "status" => "success",
    "messages" => $messages
]);
?>