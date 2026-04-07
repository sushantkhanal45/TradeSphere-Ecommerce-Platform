<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
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
$itemCount = ($items && $items->num_rows > 0) ? $items->num_rows : 0;

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
    <style>
        .cart-cards-grid{
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 320px));
            gap:20px;
            margin-top:20px;
            justify-content:center;
        }

        .cart-product-card{
            background:#fff;
            border-radius:18px;
            box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);
            border:1px solid #eef2f7;
            overflow:hidden;
            display:flex;
            flex-direction:column;
            width:100%;
        }

        .cart-product-card.sold-card{
            opacity:0.95;
            border:1px solid #fecaca;
            background:#fff7f7;
        }

        .cart-product-card .product-image-wrap{
            position:relative;
        }

        .cart-product-card .product-image-wrap img{
            width:100%;
            height:220px;
            object-fit:cover;
            display:block;
        }

        .cart-product-body{
            padding:18px;
        }

        .cart-product-body h3{
            margin:0 0 10px 0;
            font-size:20px;
            color:#0f172a;
        }

        .cart-price{
            font-size:22px;
            font-weight:700;
            color:#2563eb;
            margin-bottom:10px;
        }

        .cart-meta{
            margin:6px 0;
            color:#475569;
            line-height:1.6;
            font-size:14px;
        }

        .cart-qty-row{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
            margin-top:14px;
        }

        .cart-qty-row input{
            width:90px;
            padding:10px;
            border:1px solid #d1d5db;
            border-radius:10px;
            font-size:14px;
        }

        .cart-action-row{
            display:flex;
            flex-wrap:wrap;
            gap:10px;
            margin-top:14px;
        }

        .sold-cart-note{
            display:inline-block;
            margin-top:10px;
            padding:8px 12px;
            border-radius:10px;
            background:#fee2e2;
            color:#b91c1c;
            font-size:14px;
            font-weight:600;
            line-height:1.5;
        }

        .disabled-btn{
            background:#d1d5db !important;
            color:#6b7280 !important;
            cursor:not-allowed;
            pointer-events:none;
        }

        .cart-warning-box{
            max-width:1200px;
            margin:0 auto 18px;
            padding:12px 14px;
            border-radius:12px;
            background:#fff7ed;
            color:#9a3412;
            border:1px solid #fdba74;
            font-size:14px;
            line-height:1.5;
        }

        .cart-summary-box{
            margin-top:24px;
            background:#fff;
            box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);
            border-radius:18px;
            padding:22px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
        }

        .cart-top-actions{
            display:flex;
            justify-content:flex-end;
            margin:0 0 10px 0;
        }

        @media (max-width: 640px){
            .cart-cards-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
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
            <div class="cart-top-actions">
                <form method="POST" onsubmit="return confirm('Are you sure you want to clear the entire cart?');">
                    <button type="submit" name="clear_cart" class="btn btn-dark">Clear Cart</button>
                </form>
            </div>

            <div class="cart-cards-grid">
                <?php while ($row = $items->fetch_assoc()): ?>
                    <?php $isSold = ($row['status'] === 'sold'); ?>
                    <div class="cart-product-card <?php echo $isSold ? 'sold-card' : ''; ?>">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Cart Product">

                            <?php if ($isSold): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="cart-price">Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                            <p class="cart-meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="cart-meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="cart-meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="cart-meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <p class="cart-meta"><strong>Unit Price:</strong> Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                            <?php if ($isSold): ?>
                                <p class="cart-meta"><strong>Status:</strong> <span style="color:#b91c1c;font-weight:700;">Sold</span></p>
                                <p class="cart-meta"><strong>Subtotal:</strong> Not available</p>
                                <div class="sold-cart-note">
                                    This item has already been sold and is no longer available.
                                </div>
                            <?php else: ?>
                                <p class="cart-meta"><strong>Status:</strong> <span style="color:#047857;font-weight:700;">Available</span></p>
                                <p class="cart-meta"><strong>Subtotal:</strong> Rs <?php echo number_format(((float)$row['price'] * (int)$row['quantity']), 2); ?></p>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">

                                <div class="cart-qty-row">
                                    <label for="qty_<?php echo (int)$row['cart_id']; ?>"><strong>Quantity</strong></label>
                                    <input
                                        type="number"
                                        id="qty_<?php echo (int)$row['cart_id']; ?>"
                                        name="quantity"
                                        min="1"
                                        value="<?php echo (int)$row['quantity']; ?>"
                                        <?php echo $isSold ? 'disabled' : ''; ?>
                                        required
                                    >
                                </div>

                                <div class="cart-action-row">
                                    <?php if ($isSold): ?>
                                        <button type="button" class="small-btn disabled-btn">Unavailable</button>
                                    <?php else: ?>
                                        <button type="submit" name="update_quantity" class="small-btn primary">Update</button>
                                    <?php endif; ?>

                                    <button type="submit" name="remove_item" class="small-btn dark">Remove</button>
                                    <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn">View Details</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="cart-summary-box">
                <div>
                    <h3 style="margin:0 0 6px 0;">Total: Rs <?php echo number_format($total, 2); ?></h3>
                    <span class="meta">Only available items are included in total.</span>
                </div>

                <div class="form-actions" style="margin:0;">
                    <?php if ($itemCount > 0): ?>
                        <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                    <?php endif; ?>
                </div>
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