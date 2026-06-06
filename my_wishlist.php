<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT * FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$myWishlist = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM wishlist w
    INNER JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = $userId
    ORDER BY w.created_at DESC
");

$wishlistCountRes = $conn->query("SELECT COUNT(*) AS total FROM wishlist WHERE user_id=$userId");
$wishlistCount = $wishlistCountRes ? (int)$wishlistCountRes->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wishlist - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-row{margin-bottom:22px;}
        .wishlist-heading-row{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;}
        .clear-wishlist-btn{border:none;padding:10px 16px;border-radius:10px;background:#dc2626;color:white;font-size:14px;font-weight:700;cursor:pointer;}
        .clear-wishlist-btn:hover{background:#b91c1c;}
        .wishlist-remove-btn{border:none;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;background:#fee2e2;color:#b91c1c;}
        .wishlist-remove-btn:hover{background:#fecaca;}
        @media(max-width:600px){.wishlist-heading-row{align-items:flex-start;flex-direction:column;}}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
<div class="container">

    <div class="back-row">
        <a href="profile.php#wishlist" class="small-btn dark">← Back to Profile</a>
    </div>

    <div class="profile-section-card" id="wishlist">
        <div class="wishlist-heading-row">
            <h2 class="section-title" style="text-align:left;margin-bottom:0;">All Wishlist Items</h2>

            <?php if ($wishlistCount > 0): ?>
                <button type="button" id="clearWishlistBtn" class="clear-wishlist-btn">Clear All</button>
            <?php endif; ?>
        </div>

        <?php if ($myWishlist && $myWishlist->num_rows > 0): ?>
            <div class="products-grid" id="wishlistGrid">
                <?php while ($row = $myWishlist->fetch_assoc()): ?>
                    <div class="product-card wishlist-card" id="wishlist-card-<?php echo (int)$row['id']; ?>">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Wishlist Product">

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

                            <div class="product-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary">View Details</a>
                                <button type="button" class="wishlist-remove-btn" onclick="removeProfileWishlistItem(event, <?php echo (int)$row['id']; ?>)">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="inline-empty" id="wishlistEmptyMessage">No items in your wishlist yet.</p>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<div id="wishlistToast" class="cart-added-toast">Wishlist updated</div>

<script src="js/script.js"></script>
<script>
function showWishlistToast(message) {
    const toast = document.getElementById("wishlistToast");
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 1800);
}

function updateNavbarWishlistCount(count) {
    const candidates = [
        document.getElementById("wishlistCount"),
        document.querySelector(".wishlist-count-badge"),
        document.querySelector(".wishlist-badge")
    ];

    candidates.forEach(function(el) {
        if (el) {
            el.textContent = count;
            el.style.display = count <= 0 ? "none" : "inline-flex";
        }
    });
}

function showWishlistEmptyState() {
    const section = document.getElementById("wishlist");
    const grid = document.getElementById("wishlistGrid");
    const clearBtn = document.getElementById("clearWishlistBtn");

    if (grid) grid.remove();
    if (clearBtn) clearBtn.remove();

    if (section && !document.getElementById("wishlistEmptyMessage")) {
        const empty = document.createElement("p");
        empty.className = "inline-empty";
        empty.id = "wishlistEmptyMessage";
        empty.textContent = "No items in your wishlist yet.";
        section.appendChild(empty);
    }
}

function removeProfileWishlistItem(event, productId) {
    event.preventDefault();
    event.stopPropagation();

    const formData = new URLSearchParams();
    formData.append("action", "remove");
    formData.append("product_id", productId);

    fetch("ajax_profile_wishlist.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            const card = document.getElementById("wishlist-card-" + productId);
            if (card) card.remove();

            updateNavbarWishlistCount(data.wishlist_count || 0);

            if ((data.wishlist_count || 0) <= 0) {
                showWishlistEmptyState();
            }

            showWishlistToast(data.message || "Item removed from wishlist.");
        } else {
            showWishlistToast(data.message || "Could not remove item from wishlist.");
        }
    })
    .catch(() => showWishlistToast("Network error. Could not update wishlist."));
}

document.addEventListener("DOMContentLoaded", function () {
    const clearBtn = document.getElementById("clearWishlistBtn");

    if (clearBtn) {
        clearBtn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (!confirm("Are you sure you want to clear all wishlist items?")) return;

            const formData = new URLSearchParams();
            formData.append("action", "clear");

            fetch("ajax_profile_wishlist.php", {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    updateNavbarWishlistCount(0);
                    showWishlistEmptyState();
                    showWishlistToast(data.message || "Wishlist cleared.");
                } else {
                    showWishlistToast(data.message || "Could not clear wishlist.");
                }
            })
            .catch(() => showWishlistToast("Network error. Could not clear wishlist."));
        });
    }
});
</script>

</body>
</html>