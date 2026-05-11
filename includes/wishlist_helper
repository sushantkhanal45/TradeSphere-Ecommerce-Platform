<?php

function getWishlistProductIds($conn, $userId) {
    $ids = [];

    $userId = (int)$userId;
    if ($userId <= 0) {
        return $ids;
    }

    $res = $conn->query("
        SELECT product_id
        FROM wishlist
        WHERE user_id = $userId
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['product_id'];
        }
    }

    return $ids;
}

function isProductWishlisted($wishlistIds, $productId) {
    return in_array((int)$productId, $wishlistIds, true);
}

function getWishlistCount($conn, $userId) {
    $userId = (int)$userId;
    if ($userId <= 0) {
        return 0;
    }

    $res = $conn->query("
        SELECT COUNT(*) AS total
        FROM wishlist
        WHERE user_id = $userId
    ");

    if ($res) {
        $row = $res->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    return 0;
}