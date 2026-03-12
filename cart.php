<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int) $user['id'];

if (isset($_POST['update_quantity'])) {
    $cartId = (int) $_POST['cart_id'];
    $quantity = (int) $_POST['quantity'];

    if ($quantity < 1) {
        $quantity = 1;
    }

    $conn->query("UPDATE cart SET quantity=$quantity WHERE id=$cartId AND user_id=$userId");
    header("Location: cart.php");
    exit();
}

if (isset($_POST['remove_item'])) {
    $cartId = (int) $_POST['cart_id'];
    $conn->query("DELETE FROM cart WHERE id=$cartId AND user_id=$userId");
    header("Location: cart.php");
    exit();
}

$items = $conn->query("
    SELECT products.*, cart.quantity, cart.id AS cart_id
    FROM cart
    INNER JOIN products ON cart.product_id = products.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$total = 0;
$cartCount = 0;

if ($items) {
    while ($row = $items->fetch_assoc()) {
        $total += ((float) $row['price'] * (int) $row['quantity']);
        $cartCount += (int) $row['quantity'];
    }
    $items->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo"><a href="index.php">TradeSphere</a></div>
        <div class="menu-toggle" id="menuToggle">☰</div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="index.php#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        </div>
    </div>
</nav>

<a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
    🛒
    <?php if ($cartCount > 0): ?>
        <span class="cart-count-badge"><?php echo $cartCount; ?></span>
    <?php endif; ?>
</a>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Your Cart</h1>
        <p class="section-subtitle">Review your selected products, update quantity, remove items, and continue toward checkout.</p>

        <?php if ($items && $items->num_rows > 0): ?>
            <div class="cart-list">
                <?php while ($row = $items->fetch_assoc()): ?>
                    <div class="cart-item">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Cart Product">

                        <div>
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="meta">Category: <?php echo htmlspecialchars($row['category']); ?></p>
                            <p class="meta">City: <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta">Seller: <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <p class="meta">Unit Price: Rs <?php echo htmlspecialchars($row['price']); ?></p>
                            <p class="meta">Subtotal: Rs <?php echo ((float)$row['price'] * (int)$row['quantity']); ?></p>

                            <form method="POST" style="margin-top: 12px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">
                                <input type="number" name="quantity" min="1" value="<?php echo (int)$row['quantity']; ?>" style="width: 90px; padding: 10px; border: 1px solid #d1d5db; border-radius: 10px;" required>
                                <button type="submit" name="update_quantity" class="btn btn-primary">Update</button>
                                <button type="submit" name="remove_item" class="btn btn-dark">Remove</button>
                            </form>
                        </div>

                        <div class="price">Qty: <?php echo (int)$row['quantity']; ?></div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="cart-total">
                <h3>Total: Rs <?php echo $total; ?></h3>
                <a href="#" class="btn btn-primary">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p class="empty-state">Your cart is currently empty.</p>
        <?php endif; ?>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>