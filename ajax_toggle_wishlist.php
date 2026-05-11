<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Please login first."
    ]);
    exit();
}

if (!isset($_POST['product_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Product ID missing."
    ]);
    exit();
}

$productId = (int)$_POST['product_id'];
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

$productRes = $conn->query("SELECT id FROM products WHERE id=$productId LIMIT 1");
if (!$productRes || $productRes->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found."
    ]);
    exit();
}

$check = $conn->query("
    SELECT id
    FROM wishlist
    WHERE user_id = $userId
    AND product_id = $productId
    LIMIT 1
");

if ($check && $check->num_rows > 0) {
    $conn->query("
        DELETE FROM wishlist
        WHERE user_id = $userId
        AND product_id = $productId
    ");

    $countRes = $conn->query("
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = $userId
    ");
    $countRow = $countRes ? $countRes->fetch_assoc() : ['total' => 0];

    echo json_encode([
        "status" => "removed",
        "message" => "Removed from wishlist.",
        "wishlist_count" => (int)$countRow['total']
    ]);
    exit();
}

$conn->query("
    INSERT INTO wishlist (user_id, product_id)
    VALUES ($userId, $productId)
");

$countRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM wishlist
    WHERE user_id = $userId
");
$countRow = $countRes ? $countRes->fetch_assoc() : ['total' => 0];

echo json_encode([
    "status" => "added",
    "message" => "Added to wishlist.",
    "wishlist_count" => (int)$countRow['total']
]);
exit();