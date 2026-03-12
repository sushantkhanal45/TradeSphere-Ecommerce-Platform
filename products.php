<?php
session_start();
include "config/db.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

$cartCount = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
        $cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
        $cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
        $cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;
    }
}

$categoryQuery = $conn->query("
    SELECT DISTINCT category 
    FROM products 
    WHERE category IS NOT NULL AND category != '' 
    ORDER BY category ASC
");

$sql = "SELECT * FROM products WHERE 1=1";

if ($search !== "") {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (
        name LIKE '%$safeSearch%' OR
        city LIKE '%$safeSearch%' OR
        category LIKE '%$safeSearch%' OR
        seller_email LIKE '%$safeSearch%' OR
        description LIKE '%$safeSearch%'
    )";
}

if ($category !== "") {
    $safeCategory = $conn->real_escape_string($category);
    $sql .= " AND category = '$safeCategory'";
}

$sql .= " ORDER BY id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <a href="index.php">TradeSphere</a>
        </div>

        <div class="menu-toggle" id="menuToggle">☰</div>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="index.php#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="logout.php" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-btn">Create Account</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php
$cartCount = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
        $cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
        $cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
        $cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;
    }
}
?>

<?php if (isset($_SESSION['user'])): ?>
    <a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
        🛒
        <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>

<?php if (isset($_SESSION['user'])): ?>
    <a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
        🛒
        <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Explore Products</h1>
        <p class="section-subtitle">
            Search products by name, city, category, seller, or description and browse the complete TradeSphere marketplace.
        </p>

        <div class="search-filter-box">
            <form method="GET" action="products.php" class="search-form">
                <div class="search-group">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search products, city, seller, or category..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="search-group">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php if ($categoryQuery && $categoryQuery->num_rows > 0): ?>
                            <?php while ($cat = $categoryQuery->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo ($category === $cat['category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="products.php" class="btn btn-secondary reset-btn">Reset</a>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                            <?php if (isset($row['status']) && $row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>

                            <?php if (!empty($row['category'])): ?>
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?></p>
                            <?php endif; ?>

                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>

                            <?php if (!empty($row['seller_email'])): ?>
                                <p class="meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($row['description'])): ?>
                                <p class="meta"><strong>Description:</strong> <?php echo htmlspecialchars(mb_strimwidth($row['description'], 0, 70, "...")); ?></p>
                            <?php endif; ?>

                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="small-btn primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No matching products found.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
</body>
</html>