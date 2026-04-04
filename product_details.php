<?php
session_start();
include "config/db.php";

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = (int) $_GET['id'];

$productQuery = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $id
");

$product = $productQuery ? $productQuery->fetch_assoc() : null;

if (!$product) {
    header("Location: products.php");
    exit();
}

$success = "";
$error = "";
$showGoToCart = false;
$userId = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
    }
}

if (isset($_GET['cart_added']) && $_GET['cart_added'] === '1') {
    $success = "Product added to cart successfully.";
    $showGoToCart = true;
}

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    if ($product['status'] === 'sold') {
        $error = "This item has already been marked as sold.";
    } else {
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

        if ($quantity < 1) {
            $quantity = 1;
        }

        if ($userId > 0) {
            $productId = (int)$product['id'];

            $check = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND product_id=$productId");
            if ($check && $check->num_rows > 0) {
                $existing = $check->fetch_assoc();
                $newQty = (int)$existing['quantity'] + $quantity;
                $cartId = (int)$existing['id'];
                $conn->query("UPDATE cart SET quantity=$newQty WHERE id=$cartId");
            } else {
                $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, $quantity)");
            }

            header("Location: product_details.php?id=" . $productId . "&cart_added=1");
            exit();
        } else {
            $error = "User not found.";
        }
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
        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="detail-card">
            <div class="detail-image">
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product">
            </div>

            <div class="detail-content">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="detail-price">Rs <?php echo htmlspecialchars($product['price']); ?></div>

                <div class="detail-info">
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                    <p><strong>Condition:</strong> <?php echo htmlspecialchars($product['product_condition']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($product['city']); ?></p>
                    <p><strong>Seller Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
<p><strong>Contact Number:</strong> <?php echo htmlspecialchars($product['contact_number'] ?? 'Not provided'); ?></p>                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($product['status'])); ?></p>
                </div>

                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($product['status'] !== 'sold'): ?>
                        <form method="POST">
                            <div class="form-group" style="max-width: 140px; margin-bottom: 16px;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" min="1" value="1" required>
                            </div>

                            <div class="detail-action-row">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>

                                <?php if ($showGoToCart): ?>
                                    <a href="cart.php" class="btn btn-dark">Go to Cart</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if ($showGoToCart): ?>
                            <div class="cart-hint-box">
                                <strong>Here is your cart.</strong>
                                Your selected item has been added. You can continue browsing or go straight to your cart now.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="error-msg">This item has already been marked as sold.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn btn-dark">Login to Buy</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>