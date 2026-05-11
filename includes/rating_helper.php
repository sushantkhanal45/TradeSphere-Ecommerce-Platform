<?php

function updateProductRatingSummary($conn, $productId) {
    $productId = (int)$productId;

    $summaryRes = $conn->query("
        SELECT 
            COUNT(*) AS total_ratings,
            AVG(rating) AS avg_rating
        FROM product_ratings
        WHERE product_id = $productId
    ");

    if (!$summaryRes) {
        return false;
    }

    $summary = $summaryRes->fetch_assoc();
    $count = (int)($summary['total_ratings'] ?? 0);
    $avg = ($count > 0) ? number_format((float)$summary['avg_rating'], 2, '.', '') : '0.00';

    return $conn->query("
        UPDATE products
        SET average_rating = $avg,
            rating_count = $count
        WHERE id = $productId
    ");
}

function renderStars($rating) {
    $rating = (float)$rating;
    $full = (int)floor($rating);
    $empty = 5 - $full;

    $stars = str_repeat("★", $full) . str_repeat("☆", $empty);
    return $stars;
}