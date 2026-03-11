<?php
session_start();
include "config/db.php";

$recent = $conn->query("SELECT * FROM products ORDER BY created_at DESC, id DESC LIMIT 6");
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
 </head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
     <div class="logo">
    <a href="index.php">
        <img src="./images/logo.png" class="site-logo" alt="TradeSphere Logo">
        <!-- <span class="logo-text">TradeSphere</span> -->
    </a>
</div>
        <div class="menu-toggle" id="menuToggle">☰</div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#contact">Contact</a>

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

<section class="hero" id="home">
    <div class="hero-content">
        <h1>Modern Digital Marketplace for Buying and Selling</h1>
        <p>
            TradeSphere is a smart marketplace where users can discover products,
            list their own items, and explore a cleaner buying experience with a
            modern interface and intelligent recommendation-ready design.
        </p>
        <div class="hero-actions">
            <a href="products.php" class="btn btn-primary">Buy Items</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="sell.php" class="btn btn-secondary">Sell Items</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-secondary">Start Selling</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="home-block alt">
    <div class="container">
        <h2 class="section-title">Recently Listed Items</h2>
        <p class="section-subtitle">
            Browse the latest items added to TradeSphere. The home page highlights recent listings,
            while the full Products page lets users explore the complete marketplace.
        </p>

        <?php if ($recent && $recent->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $recent->fetch_assoc()): ?>
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
                                <a class="small-btn outline" href="products.php">More Items</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No items have been listed yet.</p>
        <?php endif; ?>
    </div>
</section>

<section class="home-block" id="services">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <p class="section-subtitle">
            TradeSphere supports a user-centered buying and selling workflow through
            product browsing, listing management, cart flow, and recommendation-ready structure.
        </p>

        <div class="feature-grid">
            <div class="feature-card">
                <h3>Buy Products</h3>
                <p>Users can browse listed items, open product details, and move toward cart and checkout with a clean and clear interface.</p>
            </div>
            <div class="feature-card">
                <h3>Sell Products</h3>
                <p>Registered users can post their own items with images, price, city, and seller details directly from the platform.</p>
            </div>
            <div class="feature-card">
                <h3>Smart Discovery</h3>
                <p>The system is designed for content-based recommendation, helping users discover more relevant products over time.</p>
            </div>
        </div>
    </div>
</section>

<section class="home-block dark" id="about">
    <div class="container">
        <h2 class="section-title">About TradeSphere</h2>
        <p class="section-subtitle">
            TradeSphere is an intelligent digital marketplace project designed to combine
            usability, product discovery, and secure web-based operations in a modern full-stack system.
        </p>

        <div class="feature-grid">
            <div class="feature-card">
                <h3>Modern UI</h3>
                <p>A cleaner visual design improves navigation, readability, and user confidence while interacting with the marketplace.</p>
            </div>
            <div class="feature-card">
                <h3>Structured Workflow</h3>
                <p>The platform separates home discovery, product browsing, selling, cart flow, and admin-oriented management into clear pages.</p>
            </div>
            <div class="feature-card">
                <h3>Project Goal</h3>
                <p>The system demonstrates a final-year level implementation of marketplace logic, recommendation concepts, and secure system thinking.</p>
            </div>
        </div>
    </div>
</section>

<section class="home-block alt" id="contact">
    <div class="container">
        <h2 class="section-title">Contact</h2>
        <p class="section-subtitle">
            Project profile and contact section for presentation and portfolio-style landing page layout.
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