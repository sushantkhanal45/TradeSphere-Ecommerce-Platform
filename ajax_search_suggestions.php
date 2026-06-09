<?php
session_start();
include "config/db.php";

header("Content-Type: application/json");

function tsSearchNormalize($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function tsSearchTokens($text) {
    $text = tsSearchNormalize($text);

    if ($text === '') {
        return [];
    }

    $stopWords = [
        'the','and','for','with','this','that','from','your','you','are','is',
        'was','were','have','has','had','product','item','good','best','new',
        'old','very','nice','hello','hi','hey'
    ];

    $words = explode(' ', $text);
    $tokens = [];

    foreach ($words as $word) {
        $word = trim($word);

        if (strlen($word) >= 2 && !in_array($word, $stopWords, true)) {
            $tokens[] = $word;
        }
    }

    return $tokens;
}

function tsExpandSearchTokens($tokens) {
    $synonyms = [
        'phone' => ['mobile', 'smartphone', 'iphone', 'android'],
        'mobile' => ['phone', 'smartphone', 'iphone', 'android'],
        'laptop' => ['notebook', 'computer', 'pc'],
        'computer' => ['laptop', 'pc', 'desktop'],
        'football' => ['soccer', 'ball', 'sports'],
        'soccer' => ['football', 'ball', 'sports'],
        'shoe' => ['shoes', 'sneaker', 'sneakers'],
        'shoes' => ['shoe', 'sneaker', 'sneakers'],
        'bike' => ['bicycle', 'cycle'],
        'bicycle' => ['bike', 'cycle'],
        'table' => ['desk', 'study'],
        'desk' => ['table', 'study'],
        'chair' => ['seat'],
        'tv' => ['television'],
        'television' => ['tv'],
        'camera' => ['dslr', 'photo'],
        'book' => ['textbook', 'study'],
        'mouse' => ['wireless', 'computer'],
        'keyboard' => ['computer', 'typing']
    ];

    $expanded = $tokens;

    foreach ($tokens as $token) {
        if (isset($synonyms[$token])) {
            $expanded = array_merge($expanded, $synonyms[$token]);
        }
    }

    return array_values(array_unique($expanded));
}

function tsBuildProductDocument($product) {
    return trim(
        ($product['name'] ?? '') . ' ' .
        ($product['description'] ?? '') . ' ' .
        ($product['category_name'] ?? '') . ' ' .
        ($product['city'] ?? '') . ' ' .
        ($product['product_condition'] ?? '')
    );
}

function tsDirectSearchRank($query, $product) {
    $query = tsSearchNormalize($query);

    if ($query === '') {
        return 0;
    }

    $name = tsSearchNormalize($product['name'] ?? '');
    $category = tsSearchNormalize($product['category_name'] ?? '');
    $description = tsSearchNormalize($product['description'] ?? '');
    $city = tsSearchNormalize($product['city'] ?? '');
    $condition = tsSearchNormalize($product['product_condition'] ?? '');

    $rank = 0;

    if ($name === $query) {
        $rank += 1000;
    }

    if (strpos($name, $query) === 0) {
        $rank += 800;
    }

    if (strpos($name, $query) !== false) {
        $rank += 650;
    }

    if (strpos($category, $query) !== false) {
        $rank += 400;
    }

    if (strpos($description, $query) !== false) {
        $rank += 250;
    }

    if (strpos($city, $query) !== false) {
        $rank += 120;
    }

    if (strpos($condition, $query) !== false) {
        $rank += 80;
    }

    $queryTokens = tsSearchTokens($query);
    $queryTokens = tsExpandSearchTokens($queryTokens);

    $productTokens = tsSearchTokens($name . ' ' . $category . ' ' . $description . ' ' . $city . ' ' . $condition);

    foreach ($queryTokens as $qToken) {
        foreach ($productTokens as $pToken) {
            if ($qToken === $pToken) {
                $rank += 120;
            } elseif (strlen($qToken) >= 4 && levenshtein($qToken, $pToken) <= 2) {
                $rank += 70;
            }
        }
    }

    return $rank;
}

function tsTermFrequency($tokens) {
    $tf = [];
    $total = count($tokens);

    if ($total === 0) {
        return $tf;
    }

    foreach ($tokens as $token) {
        if (!isset($tf[$token])) {
            $tf[$token] = 0;
        }

        $tf[$token]++;
    }

    foreach ($tf as $term => $count) {
        $tf[$term] = $count / $total;
    }

    return $tf;
}

function tsInverseDocumentFrequency($documents) {
    $idf = [];
    $totalDocs = count($documents);

    foreach ($documents as $tokens) {
        foreach (array_unique($tokens) as $token) {
            if (!isset($idf[$token])) {
                $idf[$token] = 0;
            }

            $idf[$token]++;
        }
    }

    foreach ($idf as $term => $docCount) {
        $idf[$term] = log(($totalDocs + 1) / ($docCount + 1)) + 1;
    }

    return $idf;
}

function tsBuildTfidfVector($tokens, $idf) {
    $tf = tsTermFrequency($tokens);
    $vector = [];

    foreach ($tf as $term => $value) {
        $vector[$term] = $value * ($idf[$term] ?? 0);
    }

    return $vector;
}

function tsCosineSimilarity($a, $b) {
    $dot = 0;
    $normA = 0;
    $normB = 0;

    $terms = array_unique(array_merge(array_keys($a), array_keys($b)));

    foreach ($terms as $term) {
        $x = $a[$term] ?? 0;
        $y = $b[$term] ?? 0;

        $dot += $x * $y;
        $normA += $x * $x;
        $normB += $y * $y;
    }

    if ($normA == 0 || $normB == 0) {
        return 0;
    }

    return $dot / (sqrt($normA) * sqrt($normB));
}

function tsRankProductsForSearch($query, $products) {
    $queryTokens = tsExpandSearchTokens(tsSearchTokens($query));

    if (empty($queryTokens)) {
        return [];
    }

    $documents = [$queryTokens];

    foreach ($products as $product) {
        $documents[] = tsSearchTokens(tsBuildProductDocument($product));
    }

    $idf = tsInverseDocumentFrequency($documents);
    $queryVector = tsBuildTfidfVector($queryTokens, $idf);

    foreach ($products as &$product) {
        $productTokens = tsSearchTokens(tsBuildProductDocument($product));
        $productVector = tsBuildTfidfVector($productTokens, $idf);

        $tfidfScore = tsCosineSimilarity($queryVector, $productVector) * 100;
        $directRank = tsDirectSearchRank($query, $product);

        $product['direct_rank'] = $directRank;
        $product['search_score'] = round($tfidfScore, 4);
    }

    unset($product);

    $products = array_filter($products, function ($product) {
        return ($product['direct_rank'] ?? 0) > 0 || ($product['search_score'] ?? 0) >= 12;
    });

    usort($products, function ($a, $b) {
        if (($a['direct_rank'] ?? 0) !== ($b['direct_rank'] ?? 0)) {
            return ($b['direct_rank'] ?? 0) <=> ($a['direct_rank'] ?? 0);
        }

        return ($b['search_score'] ?? 0) <=> ($a['search_score'] ?? 0);
    });

    return array_values($products);
}

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if ($term === '' || strlen($term) < 2) {
    echo json_encode([]);
    exit();
}

$result = $conn->query("
    SELECT 
        p.id,
        p.name,
        p.description,
        p.city,
        p.product_condition,
        p.seller_email,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'available'
    AND p.ai_status = 'approved'
    ORDER BY p.id DESC
    LIMIT 150
");

$items = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$items = tsRankProductsForSearch($term, $items);
$items = array_slice($items, 0, 8);

$suggestions = [];

foreach ($items as $row) {
    $suggestions[] = [
        "id" => (int)$row["id"],
        "name" => $row["name"],
        "category" => $row["category_name"],
        "score" => $row["search_score"] ?? 0
    ];
}

echo json_encode($suggestions);
exit();
?>