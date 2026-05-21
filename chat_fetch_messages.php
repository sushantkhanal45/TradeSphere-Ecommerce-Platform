<?php
ob_start();
session_start();

include "config/db.php";

function send_json($data) {
    if (ob_get_length()) {
        ob_clean();
    }

    header("Content-Type: application/json");
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user'])) {
    send_json([
        "status" => "error",
        "message" => "Not logged in.",
        "messages" => []
    ]);
}

$roomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;

if ($roomId <= 0) {
    send_json([
        "status" => "error",
        "message" => "Invalid room.",
        "messages" => []
    ]);
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
    send_json([
        "status" => "error",
        "message" => "User not found.",
        "messages" => []
    ]);
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
    send_json([
        "status" => "error",
        "message" => "Chat room not found or access denied.",
        "messages" => []
    ]);
}

$conn->query("
    UPDATE chat_messages
    SET is_read = 1
    WHERE room_id = $roomId
    AND receiver_id = $userId
");

$msgRes = $conn->query("
    SELECT 
        m.*,
        u.name AS sender_name
    FROM chat_messages m
    INNER JOIN users u ON m.sender_id = u.id
    WHERE m.room_id = $roomId
    ORDER BY m.created_at ASC
");

if (!$msgRes) {
    send_json([
        "status" => "error",
        "message" => "Could not load messages: " . $conn->error,
        "messages" => []
    ]);
}

$messages = [];

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

        if ($offerRes && $offerRes->num_rows > 0) {
            $offerRow = $offerRes->fetch_assoc();

            $offerData = [
                "id" => (int)$offerRow['id'],
                "amount" => (float)$offerRow['offer_amount'],
                "status" => $offerRow['status'],
                "can_respond" => (
                    (int)$offerRow['seller_id'] === $userId &&
                    $offerRow['status'] === "pending"
                )
            ];
        }
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
        "offer" => $offerData
    ];
}

send_json([
    "status" => "success",
    "messages" => $messages
]);