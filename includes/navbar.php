<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . "/../config/db.php";

$navUser = null;
$navCartCount = 0;
$notificationCount = 0;
$notifications = [];
$unreadMessageCount = 0;

if (isset($_SESSION['user'])) {
    $navEmail = $conn->real_escape_string($_SESSION['user']);

    $navRes = $conn->query("
        SELECT id, name, email
        FROM users
        WHERE email='$navEmail'
        LIMIT 1
    ");

    $navUser = $navRes ? $navRes->fetch_assoc() : null;

    if ($navUser) {
        $navUserId = (int)$navUser['id'];

        $cartRes = $conn->query("
            SELECT SUM(quantity) AS total
            FROM cart
            WHERE user_id=$navUserId
        ");

        if ($cartRes) {
            $cartData = $cartRes->fetch_assoc();
            $navCartCount = (int)($cartData['total'] ?? 0);
        }

        $countRes = $conn->query("
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id=$navUserId
            AND is_read=0
        ");

        if ($countRes) {
            $notificationCount = (int)$countRes->fetch_assoc()['total'];
        }

        $notiRes = $conn->query("
            SELECT *
            FROM notifications
            WHERE user_id=$navUserId
            ORDER BY created_at DESC
            LIMIT 8
        ");

        if ($notiRes) {
            while ($row = $notiRes->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        $msgRes = $conn->query("
            SELECT COUNT(*) AS total
            FROM chat_messages
            WHERE receiver_id=$navUserId
            AND is_read=0
        ");

        if ($msgRes) {
            $unreadMessageCount = (int)$msgRes->fetch_assoc()['total'];
        }
    }
}

$firstLetter = $navUser ? strtoupper(substr($navUser['name'], 0, 1)) : "U";
?>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <a href="index.php">TradeSphere</a>
        </div>

        <div class="menu-toggle" id="menuToggle">☰</div>

        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>

            <?php if ($navUser): ?>
                <div class="profile-menu">
                    <div class="profile-combo">
                        <button type="button" class="profile-toggle" id="profileToggle">
                            <span class="profile-avatar">
                                <?php echo htmlspecialchars($firstLetter); ?>
                            </span>

                            <span class="profile-name">
                                <?php echo htmlspecialchars($navUser['name']); ?>
                            </span>

                            <span class="profile-caret">▾</span>
                        </button>

                        <button
                            type="button"
                            class="notification-btn"
                            onclick="toggleNotifications()"
                            title="Notifications"
                        >
                            🔔

                            <span
                                id="notificationCountBadge"
                                class="notification-count"
                                style="<?php echo ($notificationCount > 0) ? '' : 'display:none;'; ?>"
                            >
                                <?php echo $notificationCount; ?>
                            </span>
                        </button>
                    </div>

                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-head">
                            <strong>Notifications</strong>
                        </div>

                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $n): ?>
                                <?php
                                    $msg = strtolower($n['message']);
                                    $notiLink = "profile.php";

                                    if (
                                        strpos($msg, "message") !== false ||
                                        strpos($msg, "chat") !== false ||
                                        strpos($msg, "offer") !== false
                                    ) {
                                        $notiLink = "messages.php";
                                    }
                                ?>

                                <a
                                    href="<?php echo $notiLink; ?>"
                                    class="notification-item <?php echo ((int)$n['is_read'] === 0) ? 'unread' : ''; ?>"
                                    onclick="markSingleNotificationRead(event, <?php echo (int)$n['id']; ?>, '<?php echo htmlspecialchars($notiLink, ENT_QUOTES); ?>')"
                                >
                                    <div>
                                        <?php echo htmlspecialchars($n['message']); ?>
                                    </div>

                                    <small>
                                        <?php echo htmlspecialchars($n['created_at']); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>

                            <button
                                type="button"
                                class="mark-read-btn"
                                onclick="markNotificationsRead()"
                            >
                                Mark all as read
                            </button>
                        <?php else: ?>
                            <p class="empty-notification">No notifications</p>
                        <?php endif; ?>
                    </div>

                    <div class="profile-dropdown" id="profileDropdown">
                        <a href="profile.php">My Profile</a>
                        <a href="profile.php#wishlist">Wishlist</a>
                        <a href="profile.php#purchases">Purchases</a>
                        <a href="profile.php#orders_received">Received Orders</a>
                        <a href="profile.php#sales">Completed Sales</a>
                        <a href="profile.php#listings">My Listings</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="nav-btn">Register</a>
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
            <span class="cart-count-badge">
                <?php echo $navCartCount; ?>
            </span>
        <?php endif; ?>
    </a>

    <a
        href="messages.php"
        id="floatingMessages"
        class="floating-message-btn"
        title="Messages"
    >
        💬

        <span
            id="messageFloatingCount"
            class="message-floating-count"
            style="<?php echo ($unreadMessageCount > 0) ? '' : 'display:none;'; ?>"
        >
            <?php echo $unreadMessageCount; ?>
        </span>
    </a>

    <div id="cartAddedToast" class="cart-added-toast">Added to cart</div>
<?php endif; ?>