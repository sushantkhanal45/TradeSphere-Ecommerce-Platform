<?php

function cleanWords($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = explode(" ", $text);

    $stopWords = [
        "the", "and", "for", "with", "this", "that", "from", "you", "your",
        "new", "old", "good", "very", "best", "item", "product", "used"
    ];

    $finalWords = [];

    foreach ($words as $word) {
        $word = trim($word);

        if (strlen($word) >= 3 && !in_array($word, $stopWords)) {
            $finalWords[] = $word;
        }
    }

    return array_unique($finalWords);
}

function keywordSimilarityScore($baseText, $candidateText) {
    $baseWords = cleanWords($baseText);
    $candidateWords = cleanWords($candidateText);

    if (empty($baseWords) || empty($candidateWords)) {
        return 0;
    }

    $matchCount = 0;

    foreach ($baseWords as $word) {
        if (in_array($word, $candidateWords)) {
            $matchCount++;
        }
    }

    return $matchCount * 10;
}

function getPopularityData($conn) {
    $wishlistCounts = [];
    $orderCounts = [];

    $wishlistRes = $conn->query("
        SELECT product_id, COUNT(*) AS total
        FROM wishlist
        GROUP BY product_id
    ");

    if ($wishlistRes) {
        while ($row = $wishlistRes->fetch_assoc()) {
            $wishlistCounts[(int)$row['product_id']] = (int)$row['total'];
        }
    }

    $orderRes = $conn->query("
        SELECT product_id, COUNT(*) AS total
        FROM orders
        WHERE payment_status = 'paid'
        GROUP BY product_id
    ");

    if ($orderRes) {
        while ($row = $orderRes->fetch_assoc()) {
            $orderCounts[(int)$row['product_id']] = (int)$row['total'];
        }
    }

    return [
        "wishlist" => $wishlistCounts,
        "orders" => $orderCounts
    ];
}

function calculateRecommendationScore($baseProduct, $candidateProduct, $popularityData) {
    $score = 0;

    $baseText = ($baseProduct['name'] ?? '') . " " . ($baseProduct['description'] ?? '');
    $candidateText = ($candidateProduct['name'] ?? '') . " " . ($candidateProduct['description'] ?? '');

    // 1. Keyword similarity
    $score += keywordSimilarityScore($baseText, $candidateText);

    // 2. Same category
    if (
        isset($baseProduct['category_id'], $candidateProduct['category_id']) &&
        (int)$baseProduct['category_id'] === (int)$candidateProduct['category_id']
    ) {
        $score += 35;
    }

    // 3. Same condition
    if (
        !empty($baseProduct['product_condition']) &&
        !empty($candidateProduct['product_condition']) &&
        strtolower($baseProduct['product_condition']) === strtolower($candidateProduct['product_condition'])
    ) {
        $score += 10;
    }

    // 4. Same city
    if (
        !empty($baseProduct['city']) &&
        !empty($candidateProduct['city']) &&
        strtolower($baseProduct['city']) === strtolower($candidateProduct['city'])
    ) {
        $score += 8;
    }

    // 5. Rating boost
    $averageRating = isset($candidateProduct['average_rating']) ? (float)$candidateProduct['average_rating'] : 0;
    $ratingCount = isset($candidateProduct['rating_count']) ? (int)$candidateProduct['rating_count'] : 0;

    if ($ratingCount > 0) {
        $score += ($averageRating * 6);
        $score += min($ratingCount, 20);
    }

    // 6. Wishlist popularity
    $candidateId = (int)$candidateProduct['id'];

    if (isset($popularityData['wishlist'][$candidateId])) {
        $score += min($popularityData['wishlist'][$candidateId], 10) * 2;
    }

    // 7. Purchase popularity
    if (isset($popularityData['orders'][$candidateId])) {
        $score += min($popularityData['orders'][$candidateId], 10) * 3;
    }

    return $score;
}

function getRecommendationReason($baseProduct, $candidateProduct, $popularityData) {
    $baseText = ($baseProduct['name'] ?? '') . " " . ($baseProduct['description'] ?? '');
    $candidateText = ($candidateProduct['name'] ?? '') . " " . ($candidateProduct['description'] ?? '');

    if (keywordSimilarityScore($baseText, $candidateText) >= 10) {
        return "Similar to products you viewed";
    }

    if (
        isset($baseProduct['category_id'], $candidateProduct['category_id']) &&
        (int)$baseProduct['category_id'] === (int)$candidateProduct['category_id']
    ) {
        return "Matches your preferred category";
    }

    if ((int)($candidateProduct['rating_count'] ?? 0) > 0) {
        return "Recommended because of good ratings";
    }

    $candidateId = (int)$candidateProduct['id'];

    if (isset($popularityData['wishlist'][$candidateId]) && $popularityData['wishlist'][$candidateId] > 0) {
        return "Popular in wishlists";
    }

    if (isset($popularityData['orders'][$candidateId]) && $popularityData['orders'][$candidateId] > 0) {
        return "Popular among buyers";
    }

    return "Recommended for you";
}

function getUserRecommendedProducts($conn, $userId, $limit = 6) {
    $userId = (int)$userId;
    $seedProducts = [];
    $seenProductIds = [];

    // Viewed products
    if ($userId > 0) {
        $viewRes = $conn->query("
            SELECT p.*, c.name AS category_name
            FROM product_views pv
            INNER JOIN products p ON pv.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE pv.user_id = $userId
            ORDER BY pv.viewed_at DESC
            LIMIT 8
        ");

        if ($viewRes) {
            while ($row = $viewRes->fetch_assoc()) {
                $seedProducts[] = $row;
                $seenProductIds[] = (int)$row['id'];
            }
        }

        // Cart products
        $cartRes = $conn->query("
            SELECT p.*, c.name AS category_name
            FROM cart ca
            INNER JOIN products p ON ca.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE ca.user_id = $userId
            ORDER BY ca.id DESC
            LIMIT 5
        ");

        if ($cartRes) {
            while ($row = $cartRes->fetch_assoc()) {
                $seedProducts[] = $row;
                $seenProductIds[] = (int)$row['id'];
            }
        }

        // Ordered products
        $orderRes = $conn->query("
            SELECT p.*, c.name AS category_name
            FROM orders o
            INNER JOIN products p ON o.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.user_id = $userId
            ORDER BY o.created_at DESC
            LIMIT 5
        ");

        if ($orderRes) {
            while ($row = $orderRes->fetch_assoc()) {
                $seedProducts[] = $row;
                $seenProductIds[] = (int)$row['id'];
            }
        }

        // Wishlist products
        $wishRes = $conn->query("
            SELECT p.*, c.name AS category_name
            FROM wishlist w
            INNER JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = $userId
            ORDER BY w.created_at DESC
            LIMIT 5
        ");

        if ($wishRes) {
            while ($row = $wishRes->fetch_assoc()) {
                $seedProducts[] = $row;
                $seenProductIds[] = (int)$row['id'];
            }
        }
    }

    $excludeSql = "";

    if (!empty($seenProductIds)) {
        $safeIds = array_map('intval', array_unique($seenProductIds));
        $excludeSql = " AND p.id NOT IN (" . implode(",", $safeIds) . ")";
    }

    if ($userId > 0) {
        $excludeSql .= " AND p.user_id != $userId";
    }

    $candidateRes = $conn->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status != 'sold'
        $excludeSql
        ORDER BY p.id DESC
    ");

    $candidates = [];

    if ($candidateRes) {
        while ($row = $candidateRes->fetch_assoc()) {
            $candidates[] = $row;
        }
    }

    $popularityData = getPopularityData($conn);
    $recommended = [];

    // If user has no history, recommend by rating/popularity
    if (empty($seedProducts)) {
        foreach ($candidates as $candidate) {
            $candidateId = (int)$candidate['id'];

            $score = 0;

            $avgRating = isset($candidate['average_rating']) ? (float)$candidate['average_rating'] : 0;
            $ratingCount = isset($candidate['rating_count']) ? (int)$candidate['rating_count'] : 0;

            if ($ratingCount > 0) {
                $score += ($avgRating * 8);
                $score += min($ratingCount, 20);
            }

            if (isset($popularityData['wishlist'][$candidateId])) {
                $score += min($popularityData['wishlist'][$candidateId], 10) * 2;
            }

            if (isset($popularityData['orders'][$candidateId])) {
                $score += min($popularityData['orders'][$candidateId], 10) * 3;
            }

            $score += 1;

            $candidate['recommendation_score'] = $score;
            $candidate['recommendation_reason'] = "Popular and highly rated product";

            $recommended[] = $candidate;
        }
    } else {
        foreach ($candidates as $candidate) {
            $bestScore = 0;
            $bestBaseProduct = null;

            foreach ($seedProducts as $baseProduct) {
                $score = calculateRecommendationScore($baseProduct, $candidate, $popularityData);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestBaseProduct = $baseProduct;
                }
            }

            if ($bestScore > 0) {
                $candidate['recommendation_score'] = $bestScore;
                $candidate['recommendation_reason'] = getRecommendationReason(
                    $bestBaseProduct,
                    $candidate,
                    $popularityData
                );

                $recommended[] = $candidate;
            }
        }
    }

    usort($recommended, function ($a, $b) {
        if ($a['recommendation_score'] == $b['recommendation_score']) {
            return (int)$b['id'] <=> (int)$a['id'];
        }

        return $b['recommendation_score'] <=> $a['recommendation_score'];
    });

    return array_slice($recommended, 0, $limit);
}

function getSimilarProducts($conn, $productId, $limit = 4) {
    $productId = (int)$productId;

    $targetRes = $conn->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = $productId
        LIMIT 1
    ");

    if (!$targetRes || $targetRes->num_rows === 0) {
        return [];
    }

    $targetProduct = $targetRes->fetch_assoc();

    $candidateRes = $conn->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status != 'sold'
        AND p.id != $productId
        AND p.user_id != " . (int)$targetProduct['user_id'] . "
        ORDER BY p.id DESC
    ");

    $candidates = [];

    if ($candidateRes) {
        while ($row = $candidateRes->fetch_assoc()) {
            $candidates[] = $row;
        }
    }

    $popularityData = getPopularityData($conn);
    $similar = [];

    foreach ($candidates as $candidate) {
        $score = calculateRecommendationScore($targetProduct, $candidate, $popularityData);

        if ($score > 0) {
            $candidate['recommendation_score'] = $score;
            $candidate['recommendation_reason'] = getRecommendationReason($targetProduct, $candidate, $popularityData);
            $similar[] = $candidate;
        }
    }

    usort($similar, function ($a, $b) {
        if ($a['recommendation_score'] == $b['recommendation_score']) {
            return (int)$b['id'] <=> (int)$a['id'];
        }

        return $b['recommendation_score'] <=> $a['recommendation_score'];
    });

    return array_slice($similar, 0, $limit);
}
?>