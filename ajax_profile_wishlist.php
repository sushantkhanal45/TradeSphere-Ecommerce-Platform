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
        "status" => "login_required",
        "message" => "Please login first.",
        "redirect" => "login.php"
    ]);
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    send_json([
        "status" => "error",
        "message" => "User not found."
    ]);
}

$userId = (int)$user['id'];
$action = $_POST['action'] ?? '';

if ($action === 'remove') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId <= 0) {
        send_json([
            "status" => "error",
            "message" => "Invalid product."
        ]);
    }

    $delete = $conn->query("DELETE FROM wishlist WHERE user_id=$userId AND product_id=$productId");

    if (!$delete) {
        send_json([
            "status" => "error",
            "message" => "Could not remove item from wishlist."
        ]);
    }

    $countRes = $conn->query("SELECT COUNT(*) AS total FROM wishlist WHERE user_id=$userId");
    $countRow = $countRes ? $countRes->fetch_assoc() : ['total' => 0];

    send_json([
        "status" => "success",
        "message" => "Item removed from wishlist.",
        "wishlist_count" => (int)$countRow['total']
    ]);
}

if ($action === 'clear') {
    $delete = $conn->query("DELETE FROM wishlist WHERE user_id=$userId");

    if (!$delete) {
        send_json([
            "status" => "error",
            "message" => "Could not clear wishlist."
        ]);
    }

    send_json([
        "status" => "success",
        "message" => "Wishlist cleared.",
        "wishlist_count" => 0
    ]);
}

send_json([
    "status" => "error",
    "message" => "Invalid action."
]);
