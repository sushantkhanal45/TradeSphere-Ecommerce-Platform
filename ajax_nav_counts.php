<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "notifications" => 0,
        "messages" => 0
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
        "notifications" => 0,
        "messages" => 0
    ]);
    exit();
}

$userId = (int)$user['id'];

$notificationCount = 0;
$messageCount = 0;

$notiRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = $userId
    AND is_read = 0
");

if ($notiRes) {
    $notificationCount = (int)$notiRes->fetch_assoc()['total'];
}

$msgRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM chat_messages
    WHERE receiver_id = $userId
    AND is_read = 0
");

if ($msgRes) {
    $messageCount = (int)$msgRes->fetch_assoc()['total'];
}

echo json_encode([
    "status" => "success",
    "notifications" => $notificationCount,
    "messages" => $messageCount
]);
?>