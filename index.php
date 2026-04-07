<?php
session_start();
include "config/db.php";

$userId = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $safeEmail = $conn->real_escape_string($userEmail);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$safeEmail' LIMIT 1");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
    }
}

$recent = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 6
");

$categoryQuery = $conn->query("SELECT * FROM categories ORDER BY name ASC");
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

<?php include "includes/navbar.php"; ?>

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
        <p class="section-subtitle">Explore product categories to quickly discover items that match your interests.</p>

        <div class="category-chip-row">
            <a href="products.php" class="category-chip">All</a>

            <?php if ($categoryQuery && $categoryQuery->num_rows > 0): ?>
                <?php while ($cat = $categoryQuery->fetch_assoc()): ?>
                    <a href="products.php?category_id=<?php echo (int)$cat['id']; ?>" class="category-chip">
                        <?php echo htmlspecialchars($cat['name']); ?>
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
        <p class="section-subtitle">These are the latest products added to the TradeSphere marketplace.</p>

        <?php if ($recent && $recent->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $recent->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                            <?php if ($row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                            <p class="price">Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                            <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>

                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary">
                                    View Details
                                </a>

                                <?php if ($row['status'] !== 'sold'): ?>
                                    <button
                                        type="button"
                                        class="small-btn dark add-to-cart-btn"
                                        data-id="<?php echo (int)$row['id']; ?>"
                                    >
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.65; cursor:not-allowed;">
                                        Sold
                                    </button>
                                <?php endif; ?>
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
                <p>The platform uses a clean and responsive layout so users can navigate the system more easily.</p>
            </div>

            <div class="feature-card">
                <h3>Marketplace Workflow</h3>
                <p>Users can discover products from the home page, browse all listings, and sell their own items after login.</p>
            </div>

            <div class="feature-card">
                <h3>Final Year Project Goal</h3>
                <p>This project demonstrates a full-stack marketplace system with intelligent recommendation and secure design concepts.</p>
            </div>
        </div>
    </div>
</section>

<section class="home-block alt" id="contact">
    <div class="container">
        <h2 class="section-title">Contact</h2>
        <p class="section-subtitle">Project profile and contact details for presentation and portfolio purposes.</p>

        <div class="profile-card">
            <div class="avatar">👤</div>
            <h3>Sushant Khanal</h3>
            <p class="role">Software Developer</p>
            <p>+977-1111111111</p>
        </div>
    </div>
</section>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".add-to-cart-btn");

    buttons.forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();

            const productId = this.getAttribute("data-id");
            const clickedButton = this;

            clickedButton.disabled = true;

            fetch("ajax_add_to_cart.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "product_id=" + encodeURIComponent(productId)
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                clickedButton.disabled = false;

                if (data.status === "success") {
                    const cart = document.getElementById("floatingCart");
                    const toast = document.getElementById("cartAddedToast");
                    const badge = document.querySelector(".cart-count-badge");

                    if (cart) {
                        cart.classList.add("cart-active");
                        cart.classList.add("cart-bounce");

                        setTimeout(function () {
                            cart.classList.remove("cart-bounce");
                        }, 800);
                    }

                    if (toast) {
                        toast.textContent = data.message || "Added to cart";
                        toast.classList.add("show");

                        setTimeout(function () {
                            toast.classList.remove("show");
                        }, 1800);
                    }

                    if (typeof data.cart_count !== "undefined") {
                        if (badge) {
                            badge.textContent = data.cart_count;
                        } else if (cart) {
                            const newBadge = document.createElement("span");
                            newBadge.className = "cart-count-badge";
                            newBadge.textContent = data.cart_count;
                            cart.appendChild(newBadge);
                        }
                    }
                } else {
                    alert(data.message || "Could not add to cart.");
                }
            })
            .catch(function () {
                clickedButton.disabled = false;
                alert("Something went wrong while adding to cart.");
            });
        });
    });
});
</script>

</body>
</html>