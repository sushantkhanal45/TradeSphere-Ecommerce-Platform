<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

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
$success = "";
$error = "";

/* Toggle product status */
if (isset($_POST['toggle_status'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $oldStatus = $product['status'];
        $newStatus = ($oldStatus === 'sold') ? 'available' : 'sold';

        if ($conn->query("UPDATE products SET status='$newStatus' WHERE id=$productId AND user_id=$userId")) {
            $actionData = json_encode([
                "user_id" => $userId,
                "product_id" => $productId,
                "old_status" => $oldStatus,
                "new_status" => $newStatus,
                "action" => "product_status_update",
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);
            if ($signature) {
                storeSignatureRecord($conn, $userId, "product_status_update", $productId, $actionData, $signature);
            }

            $success = "Product status updated successfully.";
        } else {
            $error = "Could not update product status.";
        }
    } else {
        $error = "Product not found or access denied.";
    }
}

/* Delete product */
if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $productName = $product['name'];
        $productImage = $product['image'];

        if ($conn->query("DELETE FROM products WHERE id=$productId AND user_id=$userId")) {
            $actionData = json_encode([
                "user_id" => $userId,
                "product_id" => $productId,
                "product_name" => $productName,
                "action" => "product_deleted",
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);
            if ($signature) {
                storeSignatureRecord($conn, $userId, "product_deleted", $productId, $actionData, $signature);
            }

            if (!empty($productImage) && file_exists("uploads/" . $productImage)) {
                @unlink("uploads/" . $productImage);
            }

            $success = "Product deleted successfully.";
        } else {
            $error = "Could not delete product.";
        }
    } else {
        $error = "Product not found or access denied.";
    }
}

$myListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Listings - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .seller-card{position:relative;}
        .seller-card .product-image-wrap{position:relative;overflow:visible;}
        .card-menu{position:absolute;top:12px;right:12px;z-index:999;}
        .card-menu-btn{width:40px;height:40px;border-radius:50%;border:none;background:rgba(15,23,42,.88);color:white;font-size:20px;cursor:pointer;box-shadow:0 8px 20px rgba(0,0,0,.18);}
        .card-menu-btn:hover{background:#38bdf8;color:#062033;}
        .card-menu-dropdown{display:none;position:absolute;top:46px;right:0;min-width:190px;background:white;border-radius:14px;box-shadow:0 16px 35px rgba(0,0,0,.18);overflow:hidden;z-index:1000;}
        .card-menu-dropdown a,.menu-action-btn{display:block;width:100%;padding:13px 14px;text-align:left;background:white;border:none;text-decoration:none;color:#111827;font-size:14px;cursor:pointer;}
        .card-menu-dropdown a:hover,.menu-action-btn:hover{background:#f3f4f6;}
        .back-row{margin-bottom:22px;}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
<div class="container">

    <div class="back-row">
        <a href="profile.php#listings" class="small-btn dark">← Back to Profile</a>
    </div>

    <?php if ($success): ?>
        <div class="success-msg"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-section-card">
        <h2 class="section-title" style="text-align:left;margin-bottom:20px;">All My Listings</h2>

        <?php if ($myListings && $myListings->num_rows > 0): ?>
            <div style="margin-bottom:20px;">
    <input 
        type="text" 
        id="profileSearchInput" 
        placeholder="Search records..." 
        style="width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:12px;font-size:15px;"
    >
</div>
            <div class="products-grid">
                <?php while ($row = $myListings->fetch_assoc()): ?>
                    <div class="product-card seller-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                            <?php if ($row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>

                            <div class="card-menu">
                                <button type="button" class="card-menu-btn" onclick="toggleListingMenu(<?php echo (int)$row['id']; ?>)">⋮</button>

                                <div class="card-menu-dropdown" id="listing-menu-<?php echo (int)$row['id']; ?>">
                                    <a href="edit_product.php?id=<?php echo (int)$row['id']; ?>">Edit Product</a>

                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="toggle_status" class="menu-action-btn">
                                            <?php echo ($row['status'] === 'sold') ? 'Mark as Available' : 'Mark as Sold'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="delete_product" class="menu-action-btn">Delete Product</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                            <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="inline-empty">You have not listed any products yet.</p>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
<script>
function toggleListingMenu(id) {
    const menu = document.getElementById("listing-menu-" + id);
    const allMenus = document.querySelectorAll(".card-menu-dropdown");

    allMenus.forEach(function(item) {
        if (item !== menu) item.style.display = "none";
    });

    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

window.addEventListener("click", function(e) {
    if (!e.target.closest(".card-menu")) {
        document.querySelectorAll(".card-menu-dropdown").forEach(function(menu) {
            menu.style.display = "none";
        });
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("profileSearchInput");
    const cards = document.querySelectorAll(".product-card");

    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();

        cards.forEach(function (card) {
            const text = card.textContent.toLowerCase();

            if (text.includes(keyword)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
});
</script>

</body>
</html>