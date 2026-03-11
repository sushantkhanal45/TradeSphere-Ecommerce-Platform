<?php
session_start();
include "config/db.php";

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = (int) $_GET['id'];
$productQuery = $conn->query("SELECT * FROM products WHERE id = $id");
$product = $productQuery ? $productQuery->fetch_assoc() : null;

if (!$product) {
    header("Location: products.php");
    exit();
}

$success = "";
$error = "";
$showGoToCart = false;
$cartCount = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int) $userRow['id'];

        $cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
        $cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
        $cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;
    }
}

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    if (isset($product['status']) && $product['status'] === 'sold') {
        $error = "This item has already been marked as sold.";
    } else {
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

        if ($quantity < 1) {
            $quantity = 1;
        }

        $userEmail = $_SESSION['user'];
        $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
        $userRow = $userRes ? $userRes->fetch_assoc() : null;

        if ($userRow) {
            $userId = (int) $userRow['id'];
            $productId = (int) $product['id'];

            $check = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND product_id=$productId");

            if ($check && $check->num_rows > 0) {
                $existing = $check->fetch_assoc();
                $newQty = (int) $existing['quantity'] + $quantity;
                $cartId = (int) $existing['id'];

                $conn->query("UPDATE cart SET quantity=$newQty WHERE id=$cartId");
                $success = "Product quantity updated in cart.";
            } else {
                $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, $quantity)");
                $success = "Product added to cart successfully.";
            }

            $showGoToCart = true;

            $cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
            $cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
            $cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;
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

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <a href="index.php">TradeSphere</a>
        </div>

        <div class="menu-toggle" id="menuToggle">☰</div>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="index.php#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>

            <?php if (isset($_SESSION['user'])): ?>
                <a href="logout.php" class="nav-btn">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-btn">Create Account</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if (isset($_SESSION['user'])): ?>
    <a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
        🛒
        <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>

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
                    <?php if (!empty($product['category'])): ?>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                    <?php endif; ?>

                    <p><strong>City:</strong> <?php echo htmlspecialchars($product['city']); ?></p>

                    <?php if (!empty($product['seller_email'])): ?>
                        <p><strong>Seller Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($product['description'])): ?>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <?php endif; ?>

                    <?php if (isset($product['status'])): ?>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($product['status'])); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user'])): ?>
                    <?php if (!isset($product['status']) || $product['status'] !== 'sold'): ?>
                        <form method="POST">
                            <div class="form-group" style="max-width: 140px; margin-bottom: 16px;">
                                <label>Quantity</label>
                                <input type="number" name="quantity" min="1" value="1" required>
                            </div>

                            <div class="detail-action-row">
                                <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>

                                <?php if ($showGoToCart || $cartCount > 0): ?>
                                    <a href="cart.php" class="btn btn-dark">Go to Cart</a>
                                <?php endif; ?>
                            </div>
                        </form>
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

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
</body>
</html>