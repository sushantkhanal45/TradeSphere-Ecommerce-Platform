<?php
session_start();
include "config/db.php";

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
$success = "";
$error = "";

if (isset($_POST['update_quantity'])) {
    $cartId = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity < 1) {
        $quantity = 1;
    }

    $checkCartItem = $conn->query("
        SELECT p.status
        FROM cart
        INNER JOIN products p ON cart.product_id = p.id
        WHERE cart.id = $cartId AND cart.user_id = $userId
        LIMIT 1
    ");

    $cartItem = $checkCartItem ? $checkCartItem->fetch_assoc() : null;

    if (!$cartItem) {
        $error = "Cart item not found.";
    } elseif ($cartItem['status'] === 'sold') {
        $error = "This item is already sold and quantity cannot be updated.";
    } else {
        if ($conn->query("UPDATE cart SET quantity=$quantity WHERE id=$cartId AND user_id=$userId")) {
            $success = "Cart quantity updated successfully.";
        } else {
            $error = "Could not update quantity.";
        }
    }
}

if (isset($_POST['remove_item'])) {
    $cartId = (int)$_POST['cart_id'];

    if ($conn->query("DELETE FROM cart WHERE id=$cartId AND user_id=$userId")) {
        $success = "Item removed from cart.";
    } else {
        $error = "Could not remove item.";
    }
}

if (isset($_POST['clear_cart'])) {
    if ($conn->query("DELETE FROM cart WHERE user_id=$userId")) {
        $success = "Cart cleared successfully.";
    } else {
        $error = "Could not clear cart.";
    }
}

$items = $conn->query("
    SELECT 
        p.*, 
        c.name AS category_name, 
        cart.quantity, 
        cart.id AS cart_id
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$total = 0;
$hasSoldItems = false;

if ($items) {
    while ($row = $items->fetch_assoc()) {
        if ($row['status'] !== 'sold') {
            $total += ((float)$row['price'] * (int)$row['quantity']);
        } else {
            $hasSoldItems = true;
        }
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

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Your Cart</h1>
        <p class="section-subtitle">
            Review your selected products, update quantity, remove items, or clear your full cart.
        </p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($hasSoldItems): ?>
            <div class="cart-warning-box">
                Some items in your cart are already sold and are no longer available for purchase.
            </div>
        <?php endif; ?>

        <?php if ($items && $items->num_rows > 0): ?>
            <div style="max-width:920px; margin:0 auto 18px; display:flex; justify-content:flex-end;">
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear the entire cart?');">
                    <button type="submit" name="clear_cart" class="btn btn-dark">Clear Cart</button>
                </form>
            </div>

            <div class="cart-list">
                <?php while ($row = $items->fetch_assoc()): ?>
                    <?php $isSold = ($row['status'] === 'sold'); ?>

                    <div class="cart-item <?php echo $isSold ? 'cart-item-sold' : ''; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Cart Product">

                        <div>
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="meta">Category: <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta">Condition: <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta">City: <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta">Seller: <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <p class="meta">Unit Price: Rs <?php echo htmlspecialchars($row['price']); ?></p>

                            <?php if ($isSold): ?>
                                <p class="meta">Status: <strong style="color:#b91c1c;">Sold</strong></p>
                                <p class="meta">Subtotal: Not available</p>
                                <div class="sold-cart-note">
                                    This item has already been sold and is no longer available.
                                </div>
                            <?php else: ?>
                                <p class="meta">Status: <strong style="color:#047857;">Available</strong></p>
                                <p class="meta">Subtotal: Rs <?php echo ((float)$row['price'] * (int)$row['quantity']); ?></p>
                            <?php endif; ?>

                            <form method="POST" style="margin-top: 12px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                                <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">

                                <input
                                    type="number"
                                    name="quantity"
                                    min="1"
                                    value="<?php echo (int)$row['quantity']; ?>"
                                    style="width: 90px; padding: 10px; border: 1px solid #d1d5db; border-radius: 10px;"
                                    <?php echo $isSold ? 'disabled' : ''; ?>
                                    required
                                >

                                <?php if ($isSold): ?>
                                    <button type="button" class="btn disabled-btn">Unavailable</button>
                                <?php else: ?>
                                    <button type="submit" name="update_quantity" class="btn btn-primary">Update</button>
                                <?php endif; ?>

                                <button type="submit" name="remove_item" class="btn btn-dark">Remove</button>
                            </form>
                        </div>

                        <div class="price">
                            <?php if ($isSold): ?>
                                Sold
                            <?php else: ?>
                                Qty: <?php echo (int)$row['quantity']; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="cart-total">
                <h3>Total: Rs <?php echo $total; ?></h3>
                <span class="meta">Only available items are included in total.</span>
            </div>
            <div class="form-actions" style="margin-top: 15px;">
    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
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