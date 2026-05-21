<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($productId > 0) {
        $_SESSION['pending_wishlist_product'] = $productId;
    }

    echo json_encode([
        "status" => "login_required",
        "message" => "Please login first.",
        "redirect" => "login.php"
    ]);
    exit();
}

if (!isset($_POST['product_id']) || (int)$_POST['product_id'] <= 0) {
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
        "status" => "login_required",
        "message" => "Please login again.",
        "redirect" => "login.php"
    ]);
    exit();
}

$userId = (int)$user['id'];

$productRes = $conn->query("SELECT id, user_id FROM products WHERE id=$productId LIMIT 1");
$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    echo json_encode([
        "status" => "error",
        "message" => "Product not found."
    ]);
    exit();
}

if ((int)$product['user_id'] === $userId) {
    echo json_encode([
        "status" => "error",
        "message" => "You cannot add your own listing to wishlist."
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

$insert = $conn->query("
    INSERT INTO wishlist (user_id, product_id)
    VALUES ($userId, $productId)
");

if (!$insert) {
    echo json_encode([
        "status" => "error",
        "message" => "Could not add to wishlist."
    ]);
    exit();
}

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
?>