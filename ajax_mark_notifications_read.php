<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in."
    ]);
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User not found."
    ]);
    exit();
}

$userId = (int)$user['id'];

if ($conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $userId")) {
    echo json_encode([
        "status" => "success",
        "message" => "Notifications marked as read."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Could not update notifications."
    ]);
}
?>