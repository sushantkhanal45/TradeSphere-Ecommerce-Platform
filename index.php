<?php
session_start();
include "config/db.php";
include "includes/recommendation_helper.php";

$userId = 0;
$wishlistIds = [];

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $safeEmail = $conn->real_escape_string($userEmail);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$safeEmail' LIMIT 1");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];

        $wishlistRes = $conn->query("
            SELECT product_id
            FROM wishlist
            WHERE user_id = $userId
        ");

        if ($wishlistRes) {
            while ($wish = $wishlistRes->fetch_assoc()) {
                $wishlistIds[] = (int)$wish['product_id'];
            }
        }
    }
}


$cartQuantities = [];

if ($userId > 0) {
    $cartQtyRes = $conn->query("
        SELECT product_id, quantity
        FROM cart
        WHERE user_id = $userId
    ");

    if ($cartQtyRes) {
        while ($cartQty = $cartQtyRes->fetch_assoc()) {
            $cartQuantities[(int)$cartQty['product_id']] = (int)$cartQty['quantity'];
        }
    }
}

function isWishlistedIndex($productId, $wishlistIds) {
    return in_array((int)$productId, $wishlistIds, true);
}

function renderCardStarsIndex($avgRating) {
    $rounded = (int) round((float)$avgRating);
    return str_repeat("★", $rounded) . str_repeat("☆", 5 - $rounded);
}

$recommendedProducts = getUserRecommendedProducts($conn, $userId, 6);

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
    <style>
        .wishlist-icon-btn{
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 12px;
            background: #fff1f2;
            color: #e11d48;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
        }

        .wishlist-icon-btn:hover{
            background: #ffe4e6;
            transform: translateY(-1px);
        }

        .wishlist-icon-btn.active{
            background: #ef4444;
            color: white;
        }

        .wishlist-toast{
            position: fixed;
            top: 178px;
            right: 24px;
            background: #111827;
            color: white;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.18);
            z-index: 1201;
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .wishlist-toast.show{
            opacity: 1;
            transform: translateY(0);
        }

        .rating-line{
            color: #f59e0b;
            font-weight: 700;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .rating-line.empty{
            color: #9ca3af;
            font-weight: 600;
        }

        @media (max-width: 768px){
            .wishlist-toast{
                top: 170px;
                right: 14px;
            }
        }


        .product-card-qty-control{
            display:inline-flex;
            align-items:center;
            gap:8px;
            background:#f8fafc;
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:5px;
            onclick:none;
        }

        .product-card-qty-btn{
            width:32px;
            height:32px;
            border:none;
            border-radius:9px;
            background:#111827;
            color:white;
            font-size:18px;
            font-weight:800;
            cursor:pointer;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            line-height:1;
        }

        .product-card-qty-btn:hover{
            background:#2563eb;
        }

        .product-card-qty-btn:disabled{
            background:#d1d5db;
            color:#6b7280;
            cursor:not-allowed;
        }

        .product-card-qty-value{
            min-width:32px;
            text-align:center;
            font-weight:800;
            color:#111827;
            font-size:14px;
        }

        .clickable-card{
            cursor:pointer;
            transition:0.25s ease;
        }

        .clickable-card:hover{
            transform:translateY(-3px);
        }
    </style>
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

<section class="home-block alt">
    <div class="container">
        <h2 class="section-title">Recommended for You</h2>
        <p class="section-subtitle">
            Personalized suggestions based on your browsing, cart activity, past orders, and better-rated listings.
        </p>

        <?php if (!empty($recommendedProducts)): ?>
            <div class="products-grid">
                <?php foreach ($recommendedProducts as $row): ?>
                    <div class="product-card clickable-card" onclick="window.location.href='product_details.php?id=<?php echo (int)$row['id']; ?>'">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                            <?php if ($row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                            <p class="price">Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                            <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                <p class="rating-line">
                                    <?php echo renderCardStarsIndex($row['average_rating'] ?? 0); ?>
                                    <?php echo number_format((float)($row['average_rating'] ?? 0), 1); ?>
                                    (<?php echo (int)$row['rating_count']; ?>)
                                </p>
                            <?php else: ?>
                                <p class="rating-line empty">No ratings yet</p>
                            <?php endif; ?>

                            <?php if (!empty($row['recommendation_reason'])): ?>
                                <p class="meta" style="color:#2563eb; font-weight:600;">
                                    <?php echo htmlspecialchars($row['recommendation_reason']); ?>
                                </p>
                            <?php endif; ?>

                            <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>

                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary" onclick="event.stopPropagation();">
                                    View Details
                                </a>

                                <?php if ($userId > 0 && (int)$row['user_id'] === $userId): ?>
                                    <button type="button" class="small-btn dark disabled-btn" disabled title="This is your own listing">
                                        Your Listing
                                    </button>
                                <?php elseif ($row['status'] !== 'sold'): ?>
                                    <?php $cardCartQty = $cartQuantities[(int)$row['id']] ?? 0; ?>

                                    <button
                                        type="button"
                                        class="small-btn dark"
                                        data-product-add-button="<?php echo (int)$row['id']; ?>"
                                        style="<?php echo $cardCartQty > 0 ? 'display:none;' : ''; ?>"
                                        onclick="event.stopPropagation(); addToCartFromHome(<?php echo (int)$row['id']; ?>)"
                                    >
                                        Add to Cart
                                    </button>

                                    <div
                                        class="product-card-qty-control"
                                        data-product-qty-control="<?php echo (int)$row['id']; ?>"
                                        style="<?php echo $cardCartQty > 0 ? '' : 'display:none;'; ?>"
                                        onclick="event.stopPropagation();"
                                    >
                                        <button
                                            type="button"
                                            class="product-card-qty-btn"
                                            onclick="event.stopPropagation(); updateProductCardQty(<?php echo (int)$row['id']; ?>, 'decrease', this)"
                                        >−</button>

                                        <span
                                            class="product-card-qty-value"
                                            data-product-qty-value="<?php echo (int)$row['id']; ?>"
                                        ><?php echo max(1, (int)$cardCartQty); ?></span>

                                        <button
                                            type="button"
                                            class="product-card-qty-btn"
                                            onclick="event.stopPropagation(); updateProductCardQty(<?php echo (int)$row['id']; ?>, 'increase', this)"
                                        >+</button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.65; cursor:not-allowed;">
                                        Sold
                                    </button>
                                <?php endif; ?>

                                <?php if (!($userId > 0 && (int)$row['user_id'] === $userId)): ?>
                                    <button
                                        type="button"
                                        class="wishlist-icon-btn <?php echo isWishlistedIndex($row['id'], $wishlistIds) ? 'active' : ''; ?>"
                                        onclick="event.stopPropagation(); toggleWishlist(<?php echo (int)$row['id']; ?>, this)"
                                        title="<?php echo isWishlistedIndex($row['id'], $wishlistIds) ? 'Remove from wishlist' : 'Add to wishlist'; ?>"
                                    >
                                        <?php echo isWishlistedIndex($row['id'], $wishlistIds) ? '♥' : '♡'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No recommendations available yet.</p>
        <?php endif; ?>
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
                    <div class="product-card clickable-card" onclick="window.location.href='product_details.php?id=<?php echo (int)$row['id']; ?>'">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                            <?php if ($row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                            <p class="price">Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                            <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                <p class="rating-line">
                                    <?php echo renderCardStarsIndex($row['average_rating'] ?? 0); ?>
                                    <?php echo number_format((float)($row['average_rating'] ?? 0), 1); ?>
                                    (<?php echo (int)$row['rating_count']; ?>)
                                </p>
                            <?php else: ?>
                                <p class="rating-line empty">No ratings yet</p>
                            <?php endif; ?>

                            <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>

                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary" onclick="event.stopPropagation();">
                                    View Details
                                </a>

                                <?php if ($userId > 0 && (int)$row['user_id'] === $userId): ?>
                                    <button type="button" class="small-btn dark disabled-btn" disabled title="This is your own listing">
                                        Your Listing
                                    </button>
                                <?php elseif ($row['status'] !== 'sold'): ?>
                                    <?php $cardCartQty = $cartQuantities[(int)$row['id']] ?? 0; ?>

                                    <button
                                        type="button"
                                        class="small-btn dark"
                                        data-product-add-button="<?php echo (int)$row['id']; ?>"
                                        style="<?php echo $cardCartQty > 0 ? 'display:none;' : ''; ?>"
                                        onclick="event.stopPropagation(); addToCartFromHome(<?php echo (int)$row['id']; ?>)"
                                    >
                                        Add to Cart
                                    </button>

                                    <div
                                        class="product-card-qty-control"
                                        data-product-qty-control="<?php echo (int)$row['id']; ?>"
                                        style="<?php echo $cardCartQty > 0 ? '' : 'display:none;'; ?>"
                                        onclick="event.stopPropagation();"
                                    >
                                        <button
                                            type="button"
                                            class="product-card-qty-btn"
                                            onclick="event.stopPropagation(); updateProductCardQty(<?php echo (int)$row['id']; ?>, 'decrease', this)"
                                        >−</button>

                                        <span
                                            class="product-card-qty-value"
                                            data-product-qty-value="<?php echo (int)$row['id']; ?>"
                                        ><?php echo max(1, (int)$cardCartQty); ?></span>

                                        <button
                                            type="button"
                                            class="product-card-qty-btn"
                                            onclick="event.stopPropagation(); updateProductCardQty(<?php echo (int)$row['id']; ?>, 'increase', this)"
                                        >+</button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.65; cursor:not-allowed;">
                                        Sold
                                    </button>
                                <?php endif; ?>

                                <?php if (!($userId > 0 && (int)$row['user_id'] === $userId)): ?>
                                    <button
                                        type="button"
                                        class="wishlist-icon-btn <?php echo isWishlistedIndex($row['id'], $wishlistIds) ? 'active' : ''; ?>"
                                        onclick="event.stopPropagation(); toggleWishlist(<?php echo (int)$row['id']; ?>, this)"
                                        title="<?php echo isWishlistedIndex($row['id'], $wishlistIds) ? 'Remove from wishlist' : 'Add to wishlist'; ?>"
                                    >
                                        <?php echo isWishlistedIndex($row['id'], $wishlistIds) ? '♥' : '♡'; ?>
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
            structured marketplace features, and recommendation functionality.
        </p>

        <div class="feature-grid">
            <div class="feature-card">
                <h3>Modern Interface</h3>
                <p>The platform uses a clean and responsive layout so users can navigate the system more easily.</p>
            </div>

            <div class="feature-card">
                <h3>Marketplace Workflow</h3>
                <p>Users can discover products, browse listings, receive recommendations, and sell their own items after login.</p>
            </div>

            <div class="feature-card">
                <h3>Smart Recommendations</h3>
                <p>The system suggests products using category, description, condition, browsing activity, cart history, past orders, and ratings.</p>
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

<div id="wishlistToast" class="wishlist-toast">Added to wishlist</div>

<script src="js/script.js"></script>

<script>
function updateWishlistButton(buttonEl, active) {
    if (!buttonEl) return;

    if (active) {
        buttonEl.classList.add("active");
        buttonEl.textContent = "♥";
        buttonEl.setAttribute("title", "Remove from wishlist");
    } else {
        buttonEl.classList.remove("active");
        buttonEl.textContent = "♡";
        buttonEl.setAttribute("title", "Add to wishlist");
    }
}

function showWishlistToast(message) {
    const toast = document.getElementById("wishlistToast");
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 1800);
}

function toggleWishlist(productId, buttonEl) {
    fetch("ajax_toggle_wishlist.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "product_id=" + encodeURIComponent(productId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "login_required") {
            window.location.href = data.redirect || "login.php";
            return;
        }

        if (data.status === "added") {
            updateWishlistButton(buttonEl, true);
            showWishlistToast(data.message || "Added to wishlist");
        } else if (data.status === "removed") {
            updateWishlistButton(buttonEl, false);
            showWishlistToast(data.message || "Removed from wishlist");
        } else {
            showWishlistToast(data.message || "Could not update wishlist");
        }
    })
    .catch(() => {
        showWishlistToast("Something went wrong while updating wishlist.");
    });
}


function updateProductCardCartUI(productId, quantity, cartCount) {
    const addButtons = document.querySelectorAll(`[data-product-add-button="${productId}"]`);
    const qtyControls = document.querySelectorAll(`[data-product-qty-control="${productId}"]`);
    const qtyValues = document.querySelectorAll(`[data-product-qty-value="${productId}"]`);

    addButtons.forEach(btn => {
        btn.style.display = "none";
    });

    qtyControls.forEach(control => {
        control.style.display = "inline-flex";
    });

    qtyValues.forEach(valueEl => {
        valueEl.textContent = Math.max(1, parseInt(quantity || 1, 10));
    });

    if (typeof updateCartBadge === "function" && typeof cartCount !== "undefined") {
        updateCartBadge(parseInt(cartCount || 0, 10));
    }
}

function updateProductCardQty(productId, action, buttonEl) {
    const qtyValues = document.querySelectorAll(`[data-product-qty-value="${productId}"]`);

    if (action === "decrease") {
        const currentQty = qtyValues.length ? parseInt(qtyValues[0].textContent.trim(), 10) : 1;

        if (currentQty <= 1) {
            const toast = document.getElementById("cartAddedToast");
            if (toast) {
                toast.textContent = "Quantity cannot be less than 1.";
                toast.classList.add("show");
                setTimeout(() => toast.classList.remove("show"), 1800);
            }
            return;
        }
    }

    const relatedButtons = document.querySelectorAll(`[data-product-qty-control="${productId}"] .product-card-qty-btn`);
    relatedButtons.forEach(btn => btn.disabled = true);

    const formData = new URLSearchParams();
    formData.append("product_id", productId);
    formData.append("action", action);

    fetch("ajax_update_product_qty.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        relatedButtons.forEach(btn => btn.disabled = false);

        if (data.status === "login_required") {
            window.location.href = data.redirect || "login.php";
            return;
        }

        const toast = document.getElementById("cartAddedToast");

        if (data.status !== "success") {
            if (toast) {
                toast.textContent = data.message || "Could not update quantity.";
                toast.classList.add("show");
                setTimeout(() => toast.classList.remove("show"), 1800);
            }
            return;
        }

        updateProductCardCartUI(productId, data.quantity, data.cart_count);

        if (toast) {
            toast.textContent = data.message || "Cart updated.";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 1800);
        }
    })
    .catch(() => {
        relatedButtons.forEach(btn => btn.disabled = false);

        const toast = document.getElementById("cartAddedToast");
        if (toast) {
            toast.textContent = "Network error while updating cart.";
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 1800);
        }
    });
}

function addToCartFromHome(productId) {
    fetch("ajax_add_to_cart.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "product_id=" + encodeURIComponent(productId)
    })
    .then(response => response.json())
    .then(data => {
        const toast = document.getElementById("cartAddedToast");
        const cart = document.getElementById("floatingCart");

        if (data.status === "login_required") {
            window.location.href = data.redirect || "login.php";
            return;
        }

        if (data.status === "success") {
            if (cart) {
                let badge = cart.querySelector(".cart-count-badge");

                cart.classList.add("cart-active");
                cart.classList.add("cart-bounce");

                setTimeout(() => {
                    cart.classList.remove("cart-bounce");
                }, 800);

                if (typeof data.cart_count !== "undefined") {
                    if (badge) {
                        badge.textContent = data.cart_count;
                    } else {
                        badge = document.createElement("span");
                        badge.className = "cart-count-badge";
                        badge.textContent = data.cart_count;
                        cart.appendChild(badge);
                    }
                }
            }

            updateProductCardCartUI(productId, data.quantity || 1, data.cart_count);

            if (toast) {
                toast.textContent = data.message || "Added to cart";
                toast.classList.add("show");

                setTimeout(() => {
                    toast.classList.remove("show");
                }, 1800);
            }
        } else {
            if (toast) {
                toast.textContent = data.message || "Could not add to cart";
                toast.classList.add("show");

                setTimeout(() => {
                    toast.classList.remove("show");
                }, 1800);
            } else {
                alert(data.message || "Could not add to cart.");
            }
        }
    })
    .catch(() => {
        const toast = document.getElementById("cartAddedToast");
        if (toast) {
            toast.textContent = "Something went wrong while adding to cart.";
            toast.classList.add("show");

            setTimeout(() => {
                toast.classList.remove("show");
            }, 1800);
        } else {
            alert("Something went wrong while adding to cart.");
        }
    });
}
</script>

</body>
</html>