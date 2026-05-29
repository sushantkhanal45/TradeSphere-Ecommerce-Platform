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
        cart.id AS cart_id,
        (
            SELECT po.offer_amount
            FROM product_offers po
            WHERE po.product_id = p.id
            AND po.buyer_id = $userId
            AND po.seller_id = p.user_id
            AND po.status = 'accepted'
            ORDER BY po.id DESC
            LIMIT 1
        ) AS accepted_offer_amount
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$total = 0;
$hasSoldItems = false;
$itemCount = 0;

if ($items) {
    while ($row = $items->fetch_assoc()) {
        if ($row['status'] !== 'sold') {
            $unitPrice = !empty($row['accepted_offer_amount'])
                ? (float)$row['accepted_offer_amount']
                : (float)$row['price'];

            $total += ($unitPrice * (int)$row['quantity']);
            $itemCount += (int)$row['quantity'];
        } else {
            $hasSoldItems = true;
        }
    }
    $items->data_seek(0);
}

function money($amount) {
    return number_format((float)$amount, 2);
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

        .qty-control{
            display:flex;
            align-items:center;
            gap:10px;
            margin-top:14px;
        }

        .qty-btn{
            width:36px;
            height:36px;
            border:none;
            border-radius:10px;
            background:#111827;
            color:white;
            font-size:20px;
            font-weight:700;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .qty-btn:hover{
            background:#2563eb;
        }

        .qty-btn:disabled{
            background:#d1d5db;
            color:#6b7280;
            cursor:not-allowed;
        }

        .qty-value{
            min-width:42px;
            text-align:center;
            padding:8px 12px;
            border-radius:10px;
            border:1px solid #d1d5db;
            background:#f8fafc;
            font-weight:700;
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

        .sold-badge{
            position:absolute;
            top:12px;
            left:12px;
            background:#dc2626;
            color:white;
            padding:7px 12px;
            border-radius:999px;
            font-size:13px;
            font-weight:800;
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

        .trade-toast{
            position:fixed;
            top:24px;
            right:24px;
            min-width:260px;
            max-width:420px;
            padding:14px 18px;
            border-radius:12px;
            background:#111827;
            color:#fff;
            font-weight:700;
            z-index:99999;
            opacity:0;
            transform:translateY(-16px);
            transition:0.25s ease;
            box-shadow:0 10px 25px rgba(0,0,0,0.18);
        }

        .trade-toast.show{
            opacity:1;
            transform:translateY(0);
        }

        .trade-toast.success{
            background:#16a34a;
        }

        .trade-toast.error{
            background:#dc2626;
        }

        .trade-toast.warning{
            background:#f59e0b;
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
            Review your selected products, change quantity, remove items, or clear your full cart.
        </p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
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
                    <?php
                        $isSold = ($row['status'] === 'sold');

                        $unitPrice = !empty($row['accepted_offer_amount'])
                            ? (float)$row['accepted_offer_amount']
                            : (float)$row['price'];

                        $subtotal = $unitPrice * (int)$row['quantity'];
                    ?>

                    <div 
                        class="cart-product-card <?php echo $isSold ? 'sold-card' : ''; ?>"
                        data-cart-card="<?php echo (int)$row['cart_id']; ?>"
                        data-unit-price="<?php echo $unitPrice; ?>"
                    >
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Cart Product">

                            <?php if ($isSold): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="cart-product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>

                            <p class="cart-price">
                                Rs <?php echo money($unitPrice); ?>
                                <?php if (!empty($row['accepted_offer_amount'])): ?>
                                    <span style="font-size:13px;color:#16a34a;font-weight:700;">(Offer Price)</span>
                                <?php endif; ?>
                            </p>

                            <p class="cart-meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="cart-meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="cart-meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="cart-meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <p class="cart-meta"><strong>Unit Price:</strong> Rs <?php echo money($unitPrice); ?></p>

                            <?php if ($isSold): ?>
                                <p class="cart-meta"><strong>Status:</strong> <span style="color:#b91c1c;font-weight:700;">Sold</span></p>
                                <p class="cart-meta"><strong>Subtotal:</strong> Not available</p>
                                <div class="sold-cart-note">
                                    This item has already been sold and is no longer available.
                                </div>
                            <?php else: ?>
                                <p class="cart-meta"><strong>Status:</strong> <span style="color:#047857;font-weight:700;">Available</span></p>
                                <p class="cart-meta">
                                    <strong>Subtotal:</strong>
                                    Rs <span class="item-subtotal" data-cart-subtotal="<?php echo (int)$row['cart_id']; ?>">
                                        <?php echo money($subtotal); ?>
                                    </span>
                                </p>
                            <?php endif; ?>

                            <div class="qty-control">
                                <button
                                    type="button"
                                    class="qty-btn"
                                    onclick="updateCartQuantity(<?php echo (int)$row['cart_id']; ?>, 'decrease', this)"
                                    <?php echo $isSold ? 'disabled' : ''; ?>
                                >−</button>

                                <span class="qty-value" data-cart-qty="<?php echo (int)$row['cart_id']; ?>">
                                    <?php echo (int)$row['quantity']; ?>
                                </span>

                                <button
                                    type="button"
                                    class="qty-btn"
                                    onclick="updateCartQuantity(<?php echo (int)$row['cart_id']; ?>, 'increase', this)"
                                    <?php echo $isSold ? 'disabled' : ''; ?>
                                >+</button>
                            </div>

                            <div class="cart-action-row">
                                <?php if ($isSold): ?>
                                    <button type="button" class="small-btn disabled-btn">Unavailable</button>
                                <?php endif; ?>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="cart_id" value="<?php echo (int)$row['cart_id']; ?>">
                                    <button type="submit" name="remove_item" class="small-btn dark">Remove</button>
                                </form>

                                <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="cart-summary-box">
                <div>
                    <h3 style="margin:0 0 6px 0;">
                        Total: Rs <span id="cartTotalAmount"><?php echo money($total); ?></span>
                    </h3>
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

<div id="tradeToast" class="trade-toast"></div>

<script src="js/script.js"></script>

<script>
function showTradeToast(message, type = "success") {
    const toast = document.getElementById("tradeToast");
    if (!toast || !message) return;

    toast.textContent = message;
    toast.className = "trade-toast show " + type;

    setTimeout(() => {
        toast.classList.remove("show");
    }, 1800);
}

function formatMoney(value) {
    return parseFloat(value || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateCartQuantity(cartId, action, buttonEl) {
    const qtyEl = document.querySelector(`[data-cart-qty="${cartId}"]`);

    if (!qtyEl) return;

    const currentQty = parseInt(qtyEl.textContent.trim(), 10);

    if (action === "decrease" && currentQty <= 1) {
        showTradeToast("Quantity cannot be less than 1.", "warning");
        return;
    }

    const buttons = document.querySelectorAll(`[onclick*="updateCartQuantity(${cartId}"]`);
    buttons.forEach(btn => btn.disabled = true);

    const formData = new URLSearchParams();
    formData.append("cart_id", cartId);
    formData.append("action", action);

    fetch("ajax_update_cart_quantity.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        buttons.forEach(btn => btn.disabled = false);

        if (data.status !== "success") {
            showTradeToast(data.message || "Could not update cart.", "error");
            return;
        }

        qtyEl.textContent = data.quantity;

        const subtotalEl = document.querySelector(`[data-cart-subtotal="${cartId}"]`);
        if (subtotalEl) {
            subtotalEl.textContent = formatMoney(data.subtotal);
        }

        const totalEl = document.getElementById("cartTotalAmount");
        if (totalEl) {
            totalEl.textContent = formatMoney(data.cart_total);
        }

        if (typeof updateCartBadge === "function") {
            updateCartBadge(parseInt(data.cart_count || 0, 10));
        }

        showTradeToast(data.message || "Cart updated.", "success");
    })
    .catch(() => {
        buttons.forEach(btn => btn.disabled = false);
        showTradeToast("Network error while updating cart.", "error");
    });
}
</script>

<?php if ($success): ?>
<script>
showTradeToast("<?php echo addslashes($success); ?>", "success");
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
showTradeToast("<?php echo addslashes($error); ?>", "error");
</script>
<?php endif; ?>

</body>
</html>