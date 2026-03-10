<?php
session_start();
include "config/db.php";

$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo"><a href="index.php">TradeSphere</a></div>
        <div class="menu-toggle" id="menuToggle">☰</div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="index.php#about">About</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#contact">Contact</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="sell.php">Sell</a>
                <a href="cart.php">Cart</a>
                <a href="logout.php" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-btn">Create Account</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">All Products</h1>
        <p class="section-subtitle">
            This page contains all posted items in the marketplace. Users can view the listings freely,
            while logged-in users can continue toward buying and selling actions.
        </p>

        <div class="products-grid">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product">
                            <?php if (isset($row['status']) && $row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                            <p class="meta">City: <?php echo htmlspecialchars($row['city']); ?></p>
                            <?php if (!empty($row['seller_email'])): ?>
                                <p class="meta">Seller: <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <?php endif; ?>

                            <div class="product-actions">
                                <a class="small-btn primary" href="product_details.php?id=<?php echo $row['id']; ?>">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-state">No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
</body>
</html>