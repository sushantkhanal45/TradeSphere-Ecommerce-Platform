<?php
session_start();
include "config/db.php";

$recent = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT 6");
$categoryQuery = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeSphere</title>
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
            <a href="#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>

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
    <a href="cart.php" class="floating-cart" title="View Cart">🛒</a>
<?php endif; ?>

<section class="hero" id="home">
    <div class="hero-content">
        <h1>Buy, Sell, and Discover Smarter with TradeSphere</h1>
        <p>
            A modern digital marketplace where users can explore products, list their own items,
            and enjoy a cleaner and more intelligent buying and selling experience.
        </p>

        <div class="hero-actions">
            <a href="products.php" class="btn btn-primary">Browse Products</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="sell.php" class="btn btn-secondary">Start Selling</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-secondary">Join TradeSphere</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-block alt" id="categories">
    <div class="container">
        <h2 class="section-title">Browse by Category</h2>
        <p class="section-subtitle">
            Explore product categories to quickly discover items that match your interests.
        </p>

        <div class="category-chip-row">
            <a href="products.php" class="category-chip">All</a>

            <?php if ($categoryQuery && $categoryQuery->num_rows > 0): ?>
                <?php while ($cat = $categoryQuery->fetch_assoc()): ?>
                    <a href="products.php?category=<?php echo urlencode($cat['category']); ?>" class="category-chip">
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <span class="category-chip">No Categories Yet</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-block alt">
    <div class="container">
        <h2 class="section-title">Recently Listed Items</h2>
        <p class="section-subtitle">
            These are the latest products added to the TradeSphere marketplace.
            Visit the Products page to explore all available listings.
        </p>

        <?php if ($recent && $recent->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $recent->fetch_assoc()): ?>
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

                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="small-btn primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No products have been listed yet.</p>
        <?php endif; ?>
    </div>
</section>

<section class="home-block dark" id="about">
    <div class="container">
        <h2 class="section-title">About TradeSphere</h2>
        <p class="section-subtitle">
            TradeSphere is an intelligent digital marketplace project developed to combine modern UI design,
            structured marketplace features, and future-ready recommendation functionality.
        </p>

        <div class="feature-grid">
            <div class="feature-card">
                <h3>Modern Interface</h3>
                <p>
                    The platform uses a clean and responsive layout so users can navigate the system more easily.
                </p>
            </div>

            <div class="feature-card">
                <h3>Marketplace Workflow</h3>
                <p>
                    Users can discover products from the home page, browse all listings, and sell their own items after login.
                </p>
            </div>

            <div class="feature-card">
                <h3>Final Year Project Goal</h3>
                <p>
                    This project demonstrates a full-stack marketplace system with intelligent recommendation and secure design concepts.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="home-block alt" id="contact">
    <div class="container">
        <h2 class="section-title">Contact</h2>
        <p class="section-subtitle">
            Project profile and contact details for presentation and portfolio purposes.
        </p>

        <div class="profile-card">
            <div class="avatar">👤</div>
            <h3>Sushant Khanal</h3>
            <p class="role">Software Developer</p>
            <p>+977-1111111111</p>
        </div>
    </div>
</section>

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
</body>
</html>