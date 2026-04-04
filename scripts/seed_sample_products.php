<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    die("Login required");
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id, email FROM users WHERE email='$userEmail' LIMIT 1");

if (!$userRes || $userRes->num_rows === 0) {
    die("User not found.");
}

$user = $userRes->fetch_assoc();
$userId = (int)$user['id'];
$sellerEmail = $user['email'];

$categories = [
    "Electronics",
    "Books",
    "Clothing",
    "Furniture",
    "Sports",
    "Accessories",
    "Shoes",
    "Stationery"
];

foreach ($categories as $cat) {
    $safeCat = $conn->real_escape_string($cat);
    $conn->query("INSERT IGNORE INTO categories (name) VALUES ('$safeCat')");
}

$catMap = [];
$res = $conn->query("SELECT id, name FROM categories");
while ($row = $res->fetch_assoc()) {
    $catMap[$row['name']] = $row['id'];
}

$productPool = [
    "Electronics" => [
        "iPhone 11", "Samsung Galaxy S20", "Dell Inspiron Laptop", "HP Pavilion Laptop",
        "Wireless Mouse", "Bluetooth Speaker", "Noise Cancelling Headphones", "Power Bank",
        "Smart Watch", "USB Keyboard", "Tablet", "Phone Charger"
    ],
    "Books" => [
        "Organic Chemistry Book", "Biology Textbook", "Physics Guide", "Math Practice Book",
        "English Novel", "History Notes Book", "Exam Preparation Book", "Programming Basics Book",
        "Lab Manual", "Dictionary", "Research Methods Book", "Public Speaking Book"
    ],
    "Clothing" => [
        "Blue T-Shirt", "Black Hoodie", "Denim Jacket", "White Shirt",
        "Casual Pants", "Slim Fit Jeans", "Winter Sweater", "Track Pants",
        "College Jacket", "Sports T-Shirt", "Cotton Kurta", "Printed Shirt"
    ],
    "Furniture" => [
        "Study Table", "Office Chair", "Wooden Shelf", "Bedside Table",
        "Drawer Cabinet", "Reading Desk", "Plastic Chair", "Bookshelf",
        "Single Bed Frame", "Laptop Table", "Mirror Stand", "Storage Rack"
    ],
    "Sports" => [
        "Football", "Cricket Bat", "Badminton Racket", "Basketball",
        "Yoga Mat", "Skipping Rope", "Tennis Ball Set", "Gym Gloves",
        "Volleyball", "Dumbbell Pair", "Shin Guard", "Sports Bottle"
    ],
    "Accessories" => [
        "Backpack", "Laptop Bag", "Wallet", "Sunglasses",
        "Wrist Watch", "Cap", "Belt", "Travel Mug",
        "Phone Stand", "Crossbody Bag", "Jewelry Box", "Scarf"
    ],
    "Shoes" => [
        "Running Shoes", "Canvas Shoes", "Formal Shoes", "White Sneakers",
        "Sports Shoes", "Slip-On Shoes", "Hiking Shoes", "Sandals",
        "College Shoes", "Training Shoes", "Casual Sneakers", "Flat Shoes"
    ],
    "Stationery" => [
        "Notebook Set", "Pen Pack", "Drawing Book", "Scientific Calculator",
        "Geometry Box", "Sticky Notes Pack", "Desk Organizer", "Marker Set",
        "Clipboard", "File Folder Set", "Journal Book", "Whiteboard Kit"
    ]
];

$descriptions = [
    "Well maintained and in good condition.",
    "Used carefully and still works perfectly.",
    "Good quality product with normal signs of use.",
    "Selling because no longer needed.",
    "Affordable and useful for students.",
    "In nice condition and ready to use.",
    "A practical item for everyday use.",
    "Neatly kept and available at a good price."
];

$conditions = ["New", "Like New", "Good", "Used"];
$cities = ["Kathmandu", "Bhaktapur", "Lalitpur", "Pokhara", "Butwal"];

/* make sure this exact file exists in uploads/ */
$image = "1773748043_044A2959.JPG";

$inserted = 0;

foreach ($categories as $categoryName) {
    if (!isset($catMap[$categoryName])) {
        continue;
    }

    $categoryId = (int)$catMap[$categoryName];
    $items = $productPool[$categoryName];

    foreach ($items as $itemName) {
        if ($inserted >= 96) {
            break 2;
        }

        $name = $conn->real_escape_string($itemName . " " . rand(1, 999));
        $price = rand(100, 2000);   // low price range
        $city = $conn->real_escape_string($cities[array_rand($cities)]);
        $description = $conn->real_escape_string($descriptions[array_rand($descriptions)]);
        $condition = $conn->real_escape_string($conditions[array_rand($conditions)]);
        $safeImage = $conn->real_escape_string($image);
        $safeSellerEmail = $conn->real_escape_string($sellerEmail);

        $conn->query("
            INSERT INTO products
            (user_id, name, category_id, price, city, seller_email, image, description, product_condition, status, created_at)
            VALUES
            ($userId, '$name', $categoryId, $price, '$city', '$safeSellerEmail', '$safeImage', '$description', '$condition', 'available', NOW())
        ");

        $inserted++;
    }
}

echo $inserted . " products inserted successfully!";
?>