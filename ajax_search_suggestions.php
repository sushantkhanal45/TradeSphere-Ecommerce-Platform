<?php
session_start();
include "config/db.php";
include "includes/search_helper.php";

header("Content-Type: application/json");

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if ($term === '') {
    echo json_encode([]);
    exit();
}

$safeTerm = $conn->real_escape_string($term);

$sql = "
    SELECT p.id, p.name, p.description, p.city, p.product_condition, p.seller_email,
           c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE (
        p.name LIKE '%$safeTerm%' OR
        p.description LIKE '%$safeTerm%' OR
        c.name LIKE '%$safeTerm%' OR
        p.city LIKE '%$safeTerm%' OR
        p.product_condition LIKE '%$safeTerm%' OR
        p.seller_email LIKE '%$safeTerm%'
    )
    OR p.status = 'available'
    LIMIT 50
";

$result = $conn->query($sql);

$items = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

$items = sortProductsBySearchScore($term, $items);
$items = array_filter($items, function ($item) {
    return ($item['search_score'] ?? 0) >= 15;
});
$items = array_slice(array_values($items), 0, 8);

$suggestions = [];

foreach ($items as $row) {
    $suggestions[] = [
        "id" => (int)$row["id"],
        "name" => $row["name"],
        "category" => $row["category_name"],
        "score" => $row["search_score"]
    ];
}

echo json_encode($suggestions);
exit();