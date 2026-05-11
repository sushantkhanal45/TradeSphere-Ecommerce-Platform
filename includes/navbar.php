<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";

$navUser = null;
$navCartCount = 0;
$sellerOrderCount = 0;
$sellerNewOrderCount = 0;

if (isset($_SESSION['user'])) {
    $navEmail = $conn->real_escape_string($_SESSION['user']);
    $navRes = $conn->query("SELECT id, name, email FROM users WHERE email='$navEmail' LIMIT 1");
    $navUser = $navRes ? $navRes->fetch_assoc() : null;

    if ($navUser) {
        $navUserId = (int)$navUser['id'];

        $navCartRes = $conn->query("
            SELECT SUM(quantity) AS total_items
            FROM cart
            WHERE user_id = $navUserId
        ");
        $navCartRow = $navCartRes ? $navCartRes->fetch_assoc() : null;
        $navCartCount = ($navCartRow && $navCartRow['total_items']) ? (int)$navCartRow['total_items'] : 0;

        $sellerOrderCountRes = $conn->query("
            SELECT COUNT(*) AS total
            FROM orders
            WHERE seller_user_id = $navUserId
            AND payment_status = 'paid'
            AND buyer_received = 0
        ");
        $sellerOrderCount = $sellerOrderCountRes ? (int)$sellerOrderCountRes->fetch_assoc()['total'] : 0;

        $sellerNewOrderCountRes = $conn->query("
            SELECT COUNT(*) AS total
            FROM orders
            WHERE seller_user_id = $navUserId
            AND payment_status = 'paid'
            AND buyer_received = 0
            AND seller_cleared = 0
        ");
        $sellerNewOrderCount = $sellerNewOrderCountRes ? (int)$sellerNewOrderCountRes->fetch_assoc()['total'] : 0;
    }
}

$firstLetter = $navUser ? strtoupper(substr($navUser['name'], 0, 1)) : "U";
?>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo"><a href="index.php">TradeSphere</a></div>

        <div class="menu-toggle" id="menuToggle">☰</div>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="index.php#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>

            <?php if ($navUser): ?>
                <div class="profile-menu">
                    <button type="button" class="profile-toggle" id="profileToggle">
                        <span class="profile-avatar"><?php echo htmlspecialchars($firstLetter); ?></span>
                        <span class="profile-name"><?php echo htmlspecialchars($navUser['name']); ?></span>
                        <span class="profile-caret">▾</span>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="profile.php">My Profile</a>
                        <a href="profile.php#wishlist">My Wishlist</a>
                        <a href="profile.php#purchases">My Purchases</a>

                        <a href="profile.php#orders_received" style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <span>Received Orders</span>

                            <span style="display:flex; align-items:center; gap:6px;">
                                <?php if ($sellerNewOrderCount > 0): ?>
                                    <span style="background:#ef4444;color:#fff;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;">
                                        New <?php echo $sellerNewOrderCount; ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($sellerOrderCount > 0): ?>
                                    <span style="background:#2563eb;color:#fff;padding:2px 7px;border-radius:999px;font-size:11px;font-weight:700;">
                                        <?php echo $sellerOrderCount; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </a>

                        <a href="profile.php#sales">Completed Sales</a>
                        <a href="profile.php#listings">My Listings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-btn">Create Account</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php if ($navUser): ?>
    <a
        href="cart.php"
        id="floatingCart"
        class="floating-cart <?php echo ($navCartCount > 0) ? 'cart-active' : ''; ?>"
        title="View Cart"
    >
        🛒
        <?php if ($navCartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $navCartCount; ?></span>
        <?php endif; ?>
    </a>

    <div id="cartAddedToast" class="cart-added-toast">Added to cart</div>
<?php endif; ?>