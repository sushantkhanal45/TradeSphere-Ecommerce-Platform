<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error"]);
    exit();
}

$notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;

if ($notificationId <= 0) {
    echo json_encode(["status" => "error"]);
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
    echo json_encode(["status" => "error"]);
    exit();
}

$userId = (int)$user['id'];

$update = $conn->query("
    UPDATE notifications
    SET is_read = 1
    WHERE id = $notificationId
    AND user_id = $userId
");

if ($update) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error"]);
}
?>