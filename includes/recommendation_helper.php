<?php

function cleanWords($text) {
    $text = strtolower((string)$text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text);

    $stopWords = [
        "the", "and", "for", "with", "this", "that", "from", "you", "your",
        "new", "old", "good", "very", "best", "item", "product", "used",
        "have", "has", "was", "were", "are", "is", "in", "on", "at", "to",
        "of", "a", "an", "by", "or", "as", "it", "its", "be", "can"
    ];

    $finalWords = [];

    foreach ($words as $word) {
        $word = trim($word);

        if (strlen($word) >= 3 && !in_array($word, $stopWords)) {
            $finalWords[] = $word;
        }
    }

    return $finalWords;
}

function buildProductDocument($product) {
    return trim(
        ($product['name'] ?? '') . " " .
        ($product['description'] ?? '') . " " .
        ($product['category_name'] ?? '')
    );
}

function termFrequency($words) {
    $tf = [];
    $totalWords = count($words);

    if ($totalWords === 0) {
        return $tf;
    }

    foreach ($words as $word) {
        if (!isset($tf[$word])) {
            $tf[$word] = 0;
        }

        $tf[$word]++;
    }

    foreach ($tf as $term => $count) {
        $tf[$term] = $count / $totalWords;
    }

    return $tf;
}

function inverseDocumentFrequency($documents) {
    $idf = [];
    $totalDocuments = count($documents);

    if ($totalDocuments === 0) {
        return $idf;
    }

    foreach ($documents as $words) {
        $uniqueWords = array_unique($words);

        foreach ($uniqueWords as $word) {
            if (!isset($idf[$word])) {
                $idf[$word] = 0;
            }

            $idf[$word]++;
        }
    }

    foreach ($idf as $term => $documentCount) {
        $idf[$term] = log(($totalDocuments + 1) / ($documentCount + 1)) + 1;
    }

    return $idf;
}

function buildTfidfVector($words, $idf) {
    $tf = termFrequency($words);
    $vector = [];

    foreach ($tf as $term => $value) {
        $vector[$term] = $value * ($idf[$term] ?? 0);
    }

    return $vector;
}

function cosineSimilarity($vectorA, $vectorB) {
    $dotProduct = 0;
    $normA = 0;
    $normB = 0;

    $terms = array_unique(array_merge(array_keys($vectorA), array_keys($vectorB)));

    foreach ($terms as $term) {
        $a = $vectorA[$term] ?? 0;
        $b = $vectorB[$term] ?? 0;

        $dotProduct += $a * $b;
        $normA += $a * $a;
        $normB += $b * $b;
    }

    if ($normA == 0 || $normB == 0) {
        return 0;
    }

    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

function tfidfCosineSimilarityScore($baseProduct, $candidateProduct) {
    $baseWords = cleanWords(buildProductDocument($baseProduct));
    $candidateWords = cleanWords(buildProductDocument($candidateProduct));

    if (empty($baseWords) || empty($candidateWords)) {
        return 0;
    }

    $documents = [$baseWords, $candidateWords];
    $idf = inverseDocumentFrequency($documents);

    $baseVector = buildTfidfVector($baseWords, $idf);
    $candidateVector = buildTfidfVector($candidateWords, $idf);

    return round(cosineSimilarity($baseVector, $candidateVector) * 100, 4);
}

function calculateRecommendationScore($baseProduct, $candidateProduct) {
    return tfidfCosineSimilarityScore($baseProduct, $candidateProduct);
}

function getRecommendationReason() {
    return "Recommended using TF-IDF and cosine similarity";
}

function getUserRecommendedProducts($conn, $userId, $limit = 6) {
    $userId = (int)$userId;
    $seedProducts = [];
    $seenProductIds = [];

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

    $recommended = [];

    if (empty($seedProducts)) {
        $seedRes = $conn->query("
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status != 'sold'
            ORDER BY p.id DESC
            LIMIT 5
        ");

        if ($seedRes) {
            while ($row = $seedRes->fetch_assoc()) {
                $seedProducts[] = $row;
            }
        }
    }

    foreach ($candidates as $candidate) {
        $bestScore = 0;

        foreach ($seedProducts as $baseProduct) {
            if ((int)$baseProduct['id'] === (int)$candidate['id']) {
                continue;
            }

            $score = calculateRecommendationScore($baseProduct, $candidate);

            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }

        if ($bestScore > 0) {
            $candidate['recommendation_score'] = $bestScore;
            $candidate['recommendation_reason'] = getRecommendationReason();
            $recommended[] = $candidate;
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

    $similar = [];

    foreach ($candidates as $candidate) {
        $score = calculateRecommendationScore($targetProduct, $candidate);

        if ($score > 0) {
            $candidate['recommendation_score'] = $score;
            $candidate['recommendation_reason'] = getRecommendationReason();
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