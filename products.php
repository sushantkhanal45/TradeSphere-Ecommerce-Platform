<?php
session_start();
include "config/db.php";
include "includes/search_helper.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$userId = 0;
$wishlistIds = [];

if (isset($_SESSION['user'])) {
    $safeEmail = $conn->real_escape_string($_SESSION['user']);
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

function isWishlistedProducts($productId, $wishlistIds) {
    return in_array((int)$productId, $wishlistIds, true);
}

function renderCardStarsProducts($avgRating) {
    $rounded = (int) round((float)$avgRating);
    return str_repeat("★", $rounded) . str_repeat("☆", 5 - $rounded);
}

$categoryQuery = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

if ($categoryId > 0) {
    $sql .= " AND p.category_id = $categoryId";
}

$sql .= " ORDER BY p.id DESC";

$result = $conn->query($sql);

$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

if ($search !== "") {
    $products = sortProductsBySearchScore($search, $products);

    $products = array_filter($products, function ($product) {
        return ($product['search_score'] ?? 0) >= 15;
    });

    $products = array_values($products);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-box-wrap{
            position: relative;
            width: 100%;
        }

        .search-suggestions{
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.10);
            z-index: 999;
            display: none;
            overflow: hidden;
        }

        .suggestion-item{
            display: block;
            padding: 12px 14px;
            text-decoration: none;
            color: #111827;
            border-bottom: 1px solid #f1f5f9;
        }

        .suggestion-item:last-child{ border-bottom:none; }
        .suggestion-item:hover{ background:#f8fafc; }
        .suggestion-item strong{ display:block; margin-bottom:4px; }
        .suggestion-item small{ color:#64748b; }
        .no-suggestion{ padding:12px 14px; color:#64748b; }

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
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Explore Products</h1>
        <p class="section-subtitle">
            Search by product name, description, category, city, condition, or seller information.
        </p>

        <div class="search-filter-box">
            <form method="GET" action="products.php" class="search-form" id="browseSearchForm">
                <div class="search-group">
                    <div class="search-box-wrap">
                        <input
                            type="text"
                            id="searchInput"
                            name="search"
                            placeholder="Search products..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            autocomplete="off"
                        >
                        <div id="searchSuggestions" class="search-suggestions"></div>
                    </div>
                </div>

                <div class="search-group">
                    <select name="category_id">
                        <option value="">All Categories</option>
                        <?php if ($categoryQuery && $categoryQuery->num_rows > 0): ?>
                            <?php while ($cat = $categoryQuery->fetch_assoc()): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="products.php" class="btn btn-secondary reset-btn">Reset</a>
            </form>
        </div>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $row): ?>
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

                            <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                <p class="rating-line">
                                    <?php echo renderCardStarsProducts($row['average_rating'] ?? 0); ?>
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

                            <div class="product-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary">View Details</a>

                                <?php if ($userId > 0 && (int)$row['user_id'] === $userId): ?>
                                    <button type="button" class="small-btn dark disabled-btn" disabled title="This is your own listing">
                                        Your Listing
                                    </button>
                                <?php elseif ($row['status'] !== 'sold'): ?>
                                    <button
                                        type="button"
                                        class="small-btn dark"
                                        onclick="addToCartFromBrowse(<?php echo (int)$row['id']; ?>)"
                                    >
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.65; cursor:not-allowed;">Sold</button>
                                <?php endif; ?>

                                <?php if (!($userId > 0 && (int)$row['user_id'] === $userId)): ?>
                                    <button
                                        type="button"
                                        class="wishlist-icon-btn <?php echo isWishlistedProducts($row['id'], $wishlistIds) ? 'active' : ''; ?>"
                                        onclick="toggleWishlist(<?php echo (int)$row['id']; ?>, this)"
                                        title="<?php echo isWishlistedProducts($row['id'], $wishlistIds) ? 'Remove from wishlist' : 'Add to wishlist'; ?>"
                                    >
                                        <?php echo isWishlistedProducts($row['id'], $wishlistIds) ? '♥' : '♡'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No matching products found.</p>
        <?php endif; ?>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<div id="wishlistToast" class="wishlist-toast">Added to wishlist</div>

<script src="js/script.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchInput");
    const suggestionsBox = document.getElementById("searchSuggestions");
    const form = document.getElementById("browseSearchForm");

    if (!input || !suggestionsBox) return;

    input.addEventListener("keyup", function () {
        const term = input.value.trim();

        if (term.length === 0) {
            suggestionsBox.innerHTML = "";
            suggestionsBox.style.display = "none";
            return;
        }

        fetch("ajax_search_suggestions.php?term=" + encodeURIComponent(term))
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    suggestionsBox.innerHTML = "<div class='no-suggestion'>No suggestions found</div>";
                    suggestionsBox.style.display = "block";
                    return;
                }

                let html = "";

                data.forEach(item => {
                    html += `
                        <a href="products.php?search=${encodeURIComponent(item.name)}" class="suggestion-item">
                            <strong>${item.name}</strong>
                            <small>${item.category ? item.category : "Suggested result"}</small>
                        </a>
                    `;
                });

                suggestionsBox.innerHTML = html;
                suggestionsBox.style.display = "block";
            })
            .catch(() => {
                suggestionsBox.innerHTML = "";
                suggestionsBox.style.display = "none";
            });
    });

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            suggestionsBox.style.display = "none";
            form.submit();
        }
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".search-box-wrap")) {
            suggestionsBox.style.display = "none";
        }
    });
});

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

function addToCartFromBrowse(productId) {
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