<?php

if (!function_exists('ts_clean_text')) {
    function ts_clean_text($text) {
        $text = strtolower((string)$text);
        $text = preg_replace('/[^a-z0-9\s]+/i', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}

if (!function_exists('ts_tokenize')) {
    function ts_tokenize($text) {
        $text = ts_clean_text($text);

        if ($text === '') {
            return [];
        }

        $stopWords = [
            'the', 'and', 'for', 'with', 'this', 'that', 'from', 'have', 'has', 'had',
            'are', 'was', 'were', 'you', 'your', 'our', 'their', 'its', 'in', 'on',
            'at', 'to', 'of', 'a', 'an', 'is', 'it', 'be', 'as', 'by', 'or', 'if',
            'but', 'not', 'new', 'old', 'very', 'good', 'nice', 'used'
        ];

        $parts = explode(' ', $text);
        $tokens = [];

        foreach ($parts as $word) {
            $word = trim($word);

            if ($word === '' || strlen($word) < 2) {
                continue;
            }

            if (in_array($word, $stopWords, true)) {
                continue;
            }

            $tokens[] = $word;
        }

        return $tokens;
    }
}

if (!function_exists('ts_build_product_text')) {
    function ts_build_product_text($product) {
        return implode(' ', [
            $product['name'] ?? '',
            $product['category_name'] ?? '',
            $product['description'] ?? '',
            $product['product_condition'] ?? '',
            $product['city'] ?? ''
        ]);
    }
}

if (!function_exists('ts_build_term_frequency')) {
    function ts_build_term_frequency($tokens) {
        $freq = [];

        foreach ($tokens as $token) {
            if (!isset($freq[$token])) {
                $freq[$token] = 0;
            }

            $freq[$token]++;
        }

        return $freq;
    }
}

if (!function_exists('ts_build_document_frequency')) {
    function ts_build_document_frequency($documents) {
        $docFreq = [];

        foreach ($documents as $docText) {
            $tokens = array_unique(ts_tokenize($docText));

            foreach ($tokens as $token) {
                if (!isset($docFreq[$token])) {
                    $docFreq[$token] = 0;
                }

                $docFreq[$token]++;
            }
        }

        return $docFreq;
    }
}

if (!function_exists('ts_build_tfidf_vector')) {
    function ts_build_tfidf_vector($text, $documentFrequency, $totalDocuments) {
        $tokens = ts_tokenize($text);

        if (empty($tokens)) {
            return [];
        }

        $termFreq = ts_build_term_frequency($tokens);
        $vector = [];

        foreach ($termFreq as $term => $tf) {
            $df = $documentFrequency[$term] ?? 0;
            $idf = log(($totalDocuments + 1) / ($df + 1)) + 1;
            $vector[$term] = $tf * $idf;
        }

        return $vector;
    }
}

if (!function_exists('ts_cosine_similarity_vectors')) {
    function ts_cosine_similarity_vectors($vecA, $vecB) {
        if (empty($vecA) || empty($vecB)) {
            return 0;
        }

        $allTerms = array_unique(array_merge(array_keys($vecA), array_keys($vecB)));

        $dotProduct = 0;
        $magA = 0;
        $magB = 0;

        foreach ($allTerms as $term) {
            $a = $vecA[$term] ?? 0;
            $b = $vecB[$term] ?? 0;

            $dotProduct += ($a * $b);
            $magA += ($a * $a);
            $magB += ($b * $b);
        }

        if ($magA <= 0 || $magB <= 0) {
            return 0;
        }

        return $dotProduct / (sqrt($magA) * sqrt($magB));
    }
}

if (!function_exists('ts_fetch_all_available_products')) {
    function ts_fetch_all_available_products($conn, $excludeProductIds = []) {
        $excludeSql = '';

        if (!empty($excludeProductIds)) {
            $safeIds = array_map('intval', $excludeProductIds);
            $excludeSql = " AND p.id NOT IN (" . implode(',', $safeIds) . ")";
        }

        $sql = "
            SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status != 'sold'
            $excludeSql
            ORDER BY p.id DESC
        ";

        $result = $conn->query($sql);
        $products = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
        }

        return $products;
    }
}

if (!function_exists('ts_get_recommendation_reason')) {
    function ts_get_recommendation_reason($baseProduct, $candidateProduct, $context = 'similar', $preferredCategoryScores = []) {
        $baseCategory = (int)($baseProduct['category_id'] ?? 0);
        $candidateCategory = (int)($candidateProduct['category_id'] ?? 0);

        $baseCondition = strtolower(trim((string)($baseProduct['product_condition'] ?? '')));
        $candidateCondition = strtolower(trim((string)($candidateProduct['product_condition'] ?? '')));

        $baseCity = strtolower(trim((string)($baseProduct['city'] ?? '')));
        $candidateCity = strtolower(trim((string)($candidateProduct['city'] ?? '')));

        if ($context === 'home' && $candidateCategory > 0 && isset($preferredCategoryScores[$candidateCategory])) {
            return "Because you viewed this category recently";
        }

        if ($baseCategory > 0 && $baseCategory === $candidateCategory && $baseCondition !== '' && $baseCondition === $candidateCondition) {
            return "Similar category and condition";
        }

        if ($baseCategory > 0 && $baseCategory === $candidateCategory && $baseCity !== '' && $baseCity === $candidateCity) {
            return "Similar category in your preferred location";
        }

        if ($baseCategory > 0 && $baseCategory === $candidateCategory) {
            return "Similar category and description";
        }

        if ($baseCondition !== '' && $baseCondition === $candidateCondition) {
            return "Matches your recent browsing";
        }

        if ($context === 'home') {
            return "Based on your cart and order activity";
        }

        return "Similar to this product";
    }
}

if (!function_exists('ts_similarity_score')) {
    function ts_similarity_score($baseProduct, $candidateProduct, $documentFrequency = [], $totalDocuments = 1) {
        $score = 0;

        $baseCategory = (int)($baseProduct['category_id'] ?? 0);
        $candCategory = (int)($candidateProduct['category_id'] ?? 0);

        $baseCondition = strtolower(trim((string)($baseProduct['product_condition'] ?? '')));
        $candCondition = strtolower(trim((string)($candidateProduct['product_condition'] ?? '')));

        $baseCity = strtolower(trim((string)($baseProduct['city'] ?? '')));
        $candCity = strtolower(trim((string)($candidateProduct['city'] ?? '')));

        if ($baseCategory > 0 && $baseCategory === $candCategory) {
            $score += 5;
        }

        if ($baseCondition !== '' && $baseCondition === $candCondition) {
            $score += 2;
        }

        if ($baseCity !== '' && $baseCity === $candCity) {
            $score += 1;
        }

        $baseText = ts_build_product_text($baseProduct);
        $candidateText = ts_build_product_text($candidateProduct);

        $baseVector = ts_build_tfidf_vector($baseText, $documentFrequency, $totalDocuments);
        $candidateVector = ts_build_tfidf_vector($candidateText, $documentFrequency, $totalDocuments);

        $cosine = ts_cosine_similarity_vectors($baseVector, $candidateVector);

        $score += ($cosine * 12);

        return $score;
    }
}

if (!function_exists('getSimilarProducts')) {
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
        $allProducts = ts_fetch_all_available_products($conn, [$productId]);

        $documents = [ts_build_product_text($targetProduct)];
        foreach ($allProducts as $candidate) {
            $documents[] = ts_build_product_text($candidate);
        }

        $documentFrequency = ts_build_document_frequency($documents);
        $totalDocuments = count($documents);

        $scored = [];

        foreach ($allProducts as $candidate) {
            $score = ts_similarity_score($targetProduct, $candidate, $documentFrequency, $totalDocuments);

            if ($score > 0) {
                $candidate['recommendation_score'] = $score;
                $candidate['recommendation_reason'] = ts_get_recommendation_reason($targetProduct, $candidate, 'similar');
                $scored[] = $candidate;
            }
        }

        usort($scored, function ($a, $b) {
            if ($a['recommendation_score'] == $b['recommendation_score']) {
                return (int)$b['id'] <=> (int)$a['id'];
            }

            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });

        return array_slice($scored, 0, $limit);
    }
}

if (!function_exists('getUserRecommendedProducts')) {
    function getUserRecommendedProducts($conn, $userId, $limit = 6) {
        $userId = (int)$userId;

        if ($userId <= 0) {
            $products = array_slice(ts_fetch_all_available_products($conn), 0, $limit);

            foreach ($products as &$product) {
                $product['recommendation_reason'] = "Popular available product";
            }

            return $products;
        }

        $seedProductIds = [];
        $seenProductIds = [];
        $preferredCategoryScores = [];

        $orderRes = $conn->query("
            SELECT product_id
            FROM orders
            WHERE user_id = $userId
            ORDER BY id DESC
            LIMIT 5
        ");

        if ($orderRes) {
            while ($row = $orderRes->fetch_assoc()) {
                $pid = (int)$row['product_id'];

                if ($pid > 0) {
                    $seedProductIds[] = $pid;
                    $seenProductIds[] = $pid;
                }
            }
        }

        $cartRes = $conn->query("
            SELECT product_id
            FROM cart
            WHERE user_id = $userId
            ORDER BY id DESC
            LIMIT 5
        ");

        if ($cartRes) {
            while ($row = $cartRes->fetch_assoc()) {
                $pid = (int)$row['product_id'];

                if ($pid > 0) {
                    $seedProductIds[] = $pid;
                    $seenProductIds[] = $pid;
                }
            }
        }

        $viewRes = $conn->query("
            SELECT product_id, category_id
            FROM product_views
            WHERE user_id = $userId
            ORDER BY viewed_at DESC
            LIMIT 10
        ");

        if ($viewRes) {
            $rank = 0;

            while ($row = $viewRes->fetch_assoc()) {
                $pid = (int)$row['product_id'];
                $cid = (int)$row['category_id'];

                if ($pid > 0) {
                    $seedProductIds[] = $pid;
                    $seenProductIds[] = $pid;
                }

                if ($cid > 0) {
                    $weight = max(1, 5 - $rank);

                    if (!isset($preferredCategoryScores[$cid])) {
                        $preferredCategoryScores[$cid] = 0;
                    }

                    $preferredCategoryScores[$cid] += $weight;
                }

                $rank++;
            }
        }

        $seedProductIds = array_values(array_unique($seedProductIds));
        $seenProductIds = array_values(array_unique($seenProductIds));

        if (empty($seedProductIds) && empty($preferredCategoryScores)) {
            $products = array_slice(ts_fetch_all_available_products($conn), 0, $limit);

            foreach ($products as &$product) {
                $product['recommendation_reason'] = "Recently available product";
            }

            return $products;
        }

        $seedProducts = [];

        foreach ($seedProductIds as $seedId) {
            $seedRes = $conn->query("
                SELECT p.*, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = " . (int)$seedId . "
                LIMIT 1
            ");

            if ($seedRes && $seedRes->num_rows > 0) {
                $seedProducts[] = $seedRes->fetch_assoc();
            }
        }

        $candidates = ts_fetch_all_available_products($conn, $seenProductIds);

        $documents = [];
        foreach ($seedProducts as $seedProduct) {
            $documents[] = ts_build_product_text($seedProduct);
        }
        foreach ($candidates as $candidate) {
            $documents[] = ts_build_product_text($candidate);
        }

        $documentFrequency = ts_build_document_frequency($documents);
        $totalDocuments = max(1, count($documents));

        $scoredMap = [];

        foreach ($candidates as $candidate) {
            $totalScore = 0;
            $candidateCategoryId = (int)($candidate['category_id'] ?? 0);
            $bestSeedProduct = null;
            $bestSeedScore = -1;

            foreach ($seedProducts as $seedProduct) {
                $seedScore = ts_similarity_score($seedProduct, $candidate, $documentFrequency, $totalDocuments);
                $totalScore += $seedScore;

                if ($seedScore > $bestSeedScore) {
                    $bestSeedScore = $seedScore;
                    $bestSeedProduct = $seedProduct;
                }
            }

            if ($candidateCategoryId > 0 && isset($preferredCategoryScores[$candidateCategoryId])) {
                $totalScore += $preferredCategoryScores[$candidateCategoryId];
            }

            if ($totalScore > 0) {
                $candidate['recommendation_score'] = $totalScore;
                $candidate['recommendation_reason'] = ts_get_recommendation_reason(
                    $bestSeedProduct ?? [],
                    $candidate,
                    'home',
                    $preferredCategoryScores
                );
                $scoredMap[$candidate['id']] = $candidate;
            }
        }

        $scored = array_values($scoredMap);

        usort($scored, function ($a, $b) {
            if ($a['recommendation_score'] == $b['recommendation_score']) {
                return (int)$b['id'] <=> (int)$a['id'];
            }

            return $b['recommendation_score'] <=> $a['recommendation_score'];
        });

        if (empty($scored)) {
            $products = array_slice(ts_fetch_all_available_products($conn, $seenProductIds), 0, $limit);

            foreach ($products as &$product) {
                $product['recommendation_reason'] = "Recently available product";
            }

            return $products;
        }

        return array_slice($scored, 0, $limit);
    }
}
?>