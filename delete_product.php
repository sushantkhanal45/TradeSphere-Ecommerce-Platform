<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit();
}

$productId = (int)($_POST['product_id'] ?? 0);
$returnTo = $_POST['return_to'] ?? "profile.php#listings";

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id
    FROM users
    WHERE email='$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user || $productId <= 0) {
    header("Location: profile.php?delete_error=1#listings");
    exit();
}

$userId = (int)$user['id'];

$productRes = $conn->query("
    SELECT *
    FROM products
    WHERE id=$productId
    AND user_id=$userId
    LIMIT 1
");

$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    header("Location: profile.php?delete_error=1#listings");
    exit();
}

$imageName = $product['image'] ?? "";

if ($conn->query("DELETE FROM products WHERE id=$productId AND user_id=$userId")) {
    if (!empty($imageName) && file_exists("uploads/" . $imageName)) {
        @unlink("uploads/" . $imageName);
    }

    if (strpos($returnTo, "?") !== false) {
        header("Location: " . $returnTo . "&deleted=1");
    } else {
        if (strpos($returnTo, "#") !== false) {
            $parts = explode("#", $returnTo, 2);
            header("Location: " . $parts[0] . "?deleted=1#" . $parts[1]);
        } else {
            header("Location: " . $returnTo . "?deleted=1");
        }
    }

    exit();
}

if (strpos($returnTo, "#") !== false) {
    $parts = explode("#", $returnTo, 2);
    header("Location: " . $parts[0] . "?delete_error=1#" . $parts[1]);
} else {
    header("Location: " . $returnTo . "?delete_error=1");
}

exit();
?>