<?php

function normalizeSearchText($text) {
    $text = strtolower(trim($text ?? ''));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function searchKeywords($text) {
    $text = normalizeSearchText($text);
    if ($text === '') return [];
    return array_values(array_filter(explode(' ', $text)));
}

function calculateSearchScore($searchTerm, $product) {
    $score = 0;

    $term = normalizeSearchText($searchTerm);

    $name = normalizeSearchText($product['name'] ?? '');
    $desc = normalizeSearchText($product['description'] ?? '');
    $category = normalizeSearchText($product['category_name'] ?? '');
    $city = normalizeSearchText($product['city'] ?? '');
    $condition = normalizeSearchText($product['product_condition'] ?? '');
    $seller = normalizeSearchText($product['seller_email'] ?? '');

    $fullText = trim("$name $desc $category $city $condition $seller");

    if ($term === '') return 0;

    // Strong exact / prefix matches
    if ($name === $term) $score += 100;
    if (strpos($name, $term) === 0) $score += 60;
    if (strpos($category, $term) === 0) $score += 40;
    if (strpos($city, $term) === 0) $score += 30;

    // Containment matches
    if (strpos($name, $term) !== false) $score += 40;
    if (strpos($category, $term) !== false) $score += 25;
    if (strpos($city, $term) !== false) $score += 20;
    if (strpos($desc, $term) !== false) $score += 15;
    if (strpos($condition, $term) !== false) $score += 10;
    if (strpos($seller, $term) !== false) $score += 5;

    // Keyword matches
    $termWords = searchKeywords($term);
    foreach ($termWords as $word) {
        if (strpos($name, $word) !== false) $score += 12;
        if (strpos($category, $word) !== false) $score += 8;
        if (strpos($city, $word) !== false) $score += 6;
        if (strpos($desc, $word) !== false) $score += 4;
    }

    // Fuzzy similarity on main fields
    similar_text($term, $name, $namePercent);
    similar_text($term, $category, $categoryPercent);
    similar_text($term, $city, $cityPercent);

    $score += (int)($namePercent * 0.35);
    $score += (int)($categoryPercent * 0.20);
    $score += (int)($cityPercent * 0.15);

    // Levenshtein bonus for typo tolerance
    $levName = levenshtein($term, $name ?: $term);
    $levCategory = levenshtein($term, $category ?: $term);
    $levCity = levenshtein($term, $city ?: $term);

    if ($levName <= 2) $score += 20;
    elseif ($levName <= 4) $score += 10;

    if ($levCategory <= 2) $score += 12;
    elseif ($levCategory <= 4) $score += 6;

    if ($levCity <= 2) $score += 10;
    elseif ($levCity <= 4) $score += 5;

    // Small general full-text similarity
    similar_text($term, $fullText, $fullPercent);
    $score += (int)($fullPercent * 0.10);

    return $score;
}

function sortProductsBySearchScore($searchTerm, $products) {
    foreach ($products as &$product) {
        $product['search_score'] = calculateSearchScore($searchTerm, $product);
    }
    unset($product);

    usort($products, function ($a, $b) {
        return $b['search_score'] <=> $a['search_score'];
    });

    return $products;
}