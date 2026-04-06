<?php
session_start();
include "config/db.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die("Invalid product.");
}

/* Fetch product */
$res = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $productId
    LIMIT 1
");

$product = $res ? $res->fetch_assoc() : null;

if (!$product) {
    die("Product not found.");
}

$showGoToCart = false;

/* Add to cart */
if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
    $user = $userRes ? $userRes->fetch_assoc() : null;

    if (!$user) {
        die("User not found.");
    }

    $userId = (int)$user['id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if ($product['status'] === 'sold') {
        $error = "This product is already sold.";
    } else {
        $check = $conn->query("
            SELECT id, quantity 
            FROM cart 
            WHERE user_id=$userId AND product_id=$productId
        ");

        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $newQty = $row['quantity'] + $qty;

            $conn->query("
                UPDATE cart 
                SET quantity=$newQty 
                WHERE id=" . (int)$row['id']
            );
        } else {
            $conn->query("
                INSERT INTO cart (user_id, product_id, quantity)
                VALUES ($userId, $productId, $qty)
            ");
        }

        $showGoToCart = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">

        <!-- BACK BUTTON -->
        <div style="margin-bottom: 20px;">
            <button onclick="window.history.back()" class="btn btn-dark">← Back</button>
        </div>

        <div class="detail-card">
            <div class="detail-image-wrap">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">

                <?php if ($product['status'] === 'sold'): ?>
                    <div class="sold-badge">SOLD</div>
                <?php endif; ?>
            </div>

            <div class="detail-content">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>

                <p class="price">Rs <?php echo number_format((float)$product['price'], 2); ?></p>

                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($product['product_condition']); ?></p>
                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($product['city']); ?></p>
                <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($product['status'])); ?></p>

                <hr style="margin: 16px 0;">

                <!-- SELLER INFO (ONLY HERE) -->
                <h4>Seller Contact</h4>
                <p class="meta"><strong>Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
                <p class="meta"><strong>Phone:</strong> <?php echo htmlspecialchars($product['contact_number'] ?? 'Not provided'); ?></p>

                <hr style="margin: 16px 0;">

                <!-- ACTIONS -->
                <?php if (isset($_SESSION['user'])): ?>

                    <?php if ($product['status'] !== 'sold'): ?>

                        <form method="POST">
                            <div class="form-group" style="max-width: 140px;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" min="1" value="1" required>
                            </div>

                            <div class="detail-action-row" style="margin-top: 14px;">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>

                                <?php if ($showGoToCart): ?>
                                    <a href="cart.php" class="btn btn-dark">Go to Cart</a>
                                <?php endif; ?>

                                <button type="button" onclick="window.history.back()" class="btn btn-secondary">Back</button>
                            </div>
                        </form>

                        <?php if ($showGoToCart): ?>
                            <div class="cart-hint-box">
                                <strong>Item added to cart.</strong><br>
                                You can go to your cart or continue browsing.
                            </div>
                        <?php endif; ?>

                    <?php else: ?>

                        <div class="error-msg">This item has already been marked as sold.</div>

                        <div class="detail-action-row" style="margin-top: 14px;">
                            <button onclick="window.history.back()" class="btn btn-secondary">Back</button>
                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <div class="detail-action-row">
                        <a href="login.php" class="btn btn-dark">Login to Buy</a>
                        <button onclick="window.history.back()" class="btn btn-secondary">Back</button>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

</body>
</html>