<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
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

$totalListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId");
$totalListings = $totalListingsRes ? (int)$totalListingsRes->fetch_assoc()['total'] : 0;

$soldListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND status='sold'");
$soldListings = $soldListingsRes ? (int)$soldListingsRes->fetch_assoc()['total'] : 0;

$cartItemsRes = $conn->query("SELECT SUM(quantity) AS total FROM cart WHERE user_id=$userId");
$cartItems = $cartItemsRes ? (int)($cartItemsRes->fetch_assoc()['total'] ?? 0) : 0;

$myListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    ORDER BY p.id DESC
");

$myPurchases = $conn->query("
    SELECT p.*, c.name AS category_name, cart.quantity, cart.id AS cart_id
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$firstLetter = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .seller-card{
            position: relative;
        }

        .seller-card .product-image-wrap{
            position: relative;
            overflow: visible;
        }

        .card-menu{
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 999;
        }

        .card-menu-btn{
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(15, 23, 42, 0.88);
            color: white;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.18);
        }

        .card-menu-btn:hover{
            background: #38bdf8;
            color: #062033;
        }

        .card-menu-dropdown{
            display: none;
            position: absolute;
            top: 46px;
            right: 0;
            min-width: 190px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 16px 35px rgba(0,0,0,0.18);
            overflow: hidden;
            z-index: 1000;
        }

        .card-menu-dropdown a,
        .menu-action-btn{
            display: block;
            width: 100%;
            padding: 13px 14px;
            text-align: left;
            background: white;
            border: none;
            text-decoration: none;
            color: #111827;
            font-size: 14px;
            cursor: pointer;
        }

        .card-menu-dropdown a:hover,
        .menu-action-btn:hover{
            background: #f3f4f6;
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-section-card">
            <div class="profile-header-box">
                <div class="profile-avatar-lg"><?php echo htmlspecialchars($firstLetter); ?></div>

                <div class="profile-meta">
                    <h2 style="margin:0 0 8px 0;"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    <p><strong>Verified:</strong> <?php echo ((int)$user['is_verified'] === 1) ? "Yes" : "No"; ?></p>
                </div>
            </div>
        </div>

        <div class="profile-section-card">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Account Overview</h2>

            <div class="profile-grid">
                <div class="profile-stat">
                    <h3>Total Listings</h3>
                    <p><?php echo $totalListings; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Sold Listings</h3>
                    <p><?php echo $soldListings; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Cart Items</h3>
                    <p><?php echo $cartItems; ?></p>
                </div>
            </div>
        </div>

        <div class="profile-section-card" id="purchases">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">My Purchases / Cart Items</h2>

            <?php if ($myPurchases && $myPurchases->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $myPurchases->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">
                                <?php if ($row['status'] === 'sold'): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php endif; ?>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>Qty:</strong> <?php echo (int)$row['quantity']; ?></p>
                                <p class="meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>

                                <div class="product-actions">
                                    <a href="product_details.php?id=<?php echo $row['id']; ?>" class="small-btn primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="inline-empty">No purchase/cart history available yet.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="listings">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">My Listings</h2>

            <?php if ($myListings && $myListings->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $myListings->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                                <?php if ($row['status'] === 'sold'): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php endif; ?>

                                <div class="card-menu">
                                    <button type="button" class="card-menu-btn" onclick="toggleListingMenu(<?php echo $row['id']; ?>)">⋮</button>
                                    <div class="card-menu-dropdown" id="listing-menu-<?php echo $row['id']; ?>">
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit Product</a>

                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_status" class="menu-action-btn">
                                                <?php echo ($row['status'] === 'sold') ? 'Mark as Available' : 'Mark as Sold'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="delete_product" class="menu-action-btn">
                                                Delete Product
                                            </button>
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
        if (item !== menu) {
            item.style.display = "none";
        }
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
</body>
</html>