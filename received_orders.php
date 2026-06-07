<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT * FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];
$success = "";
$error = "";
$highlightOrderId = 0;
$highlightMessage = "";

/* Seller delivery status update */
if (isset($_POST['update_delivery_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newDeliveryStatus = trim($_POST['seller_delivery_status']);

    $allowedStatuses = ['pending', 'processing', 'out_for_delivery', 'delivered'];

    if (!in_array($newDeliveryStatus, $allowedStatuses, true)) {
        $error = "Invalid delivery status selected.";
    } else {
        $escapedStatus = $conn->real_escape_string($newDeliveryStatus);

        $checkOrder = $conn->query("
            SELECT o.*, p.name AS product_name
            FROM orders o
            INNER JOIN products p ON o.product_id = p.id
            WHERE o.id = $orderId
            AND o.seller_user_id = $userId
            LIMIT 1
        ");

        $orderData = $checkOrder ? $checkOrder->fetch_assoc() : null;

        if (!$orderData) {
            $error = "Order not found or access denied.";
        } else {
            $deliveredAtSql = ($escapedStatus === 'delivered') ? ", delivered_at = NOW()" : ", delivered_at = NULL";
            $newOrderStatus = ($escapedStatus === 'pending') ? 'pending' : 'processing';
            $buyerReceivedResetSql = ($escapedStatus !== 'delivered') ? ", buyer_received = 0, buyer_received_at = NULL" : "";

            if ($conn->query("
                UPDATE orders
                SET seller_delivery_status = '$escapedStatus',
                    order_status = '$newOrderStatus'
                    $deliveredAtSql
                    $buyerReceivedResetSql
                WHERE id = $orderId
                AND seller_user_id = $userId
            ")) {
                $actionData = json_encode([
                    "user_id" => $userId,
                    "order_id" => $orderId,
                    "product_id" => $orderData['product_id'],
                    "product_name" => $orderData['product_name'],
                    "new_delivery_status" => $escapedStatus,
                    "action" => "seller_delivery_status_update",
                    "timestamp" => date("Y-m-d H:i:s")
                ]);

                $signature = signData($actionData);

                if ($signature) {
                    storeSignatureRecord(
                        $conn,
                        $userId,
                        "seller_delivery_status_update",
                        $orderId,
                        $actionData,
                        $signature
                    );
                }

                $buyerId = (int)$orderData['user_id'];
                $prettyStatus = ucwords(str_replace('_', ' ', $escapedStatus));

                if ($escapedStatus === 'processing') {
                    $notificationMessage = "Seller is processing your order: " . $orderData['product_name'];
                } elseif ($escapedStatus === 'out_for_delivery') {
                    $notificationMessage = "Your product is out for delivery: " . $orderData['product_name'];
                } elseif ($escapedStatus === 'delivered') {
                    $notificationMessage = "Seller marked your order as delivered. Please confirm received: " . $orderData['product_name'];
                } else {
                    $notificationMessage = "Seller updated your order status to $prettyStatus: " . $orderData['product_name'];
                }

                $conn->query("
                    INSERT INTO notifications (user_id, order_id, message)
                    VALUES ($buyerId, $orderId, '" . $conn->real_escape_string($notificationMessage) . "')
                ");

                $success = "Seller delivery status updated successfully.";
                $highlightOrderId = $orderId;
                $highlightMessage = "Delivery status updated";
            } else {
                $error = "Could not update seller delivery status.";
            }
        }
    }
}

$receivedOrders = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.seller_user_id = $userId
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Received Orders - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-row{margin-bottom:22px;}
        .purchase-badge,.status-note{display:inline-block;margin-top:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;}
        .purchase-badge{background:#dcfce7;color:#166534;}
        .status-note{background:#eff6ff;color:#1d4ed8;}
        .tracked-order-card{position:relative;transition:.3s ease;}
        .tracked-order-card.active-track{border:2px solid #2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.12),0 18px 35px rgba(37,99,235,.12);transform:translateY(-2px);}
        .order-action-popup{position:absolute;top:14px;left:14px;z-index:20;background:#2563eb;color:#fff;padding:8px 12px;border-radius:999px;font-size:12px;font-weight:700;box-shadow:0 10px 25px rgba(37,99,235,.25);animation:fadePop 2.4s ease forwards;}
        @keyframes fadePop{0%{opacity:0;transform:translateY(-8px) scale(.96);}10%{opacity:1;transform:translateY(0) scale(1);}80%{opacity:1;}100%{opacity:0;transform:translateY(-8px) scale(.98);}}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
<div class="container">

    <div class="back-row">
        <a href="profile.php#orders_received" class="small-btn dark">← Back to Profile</a>
    </div>

    <?php if ($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

    <div class="profile-section-card">
        <h2 class="section-title" style="text-align:left;margin-bottom:20px;">All Received Orders</h2>

        <?php if ($receivedOrders && $receivedOrders->num_rows > 0): ?>
            <div style="margin-bottom:20px;">
    <input 
        type="text" 
        id="profileSearchInput" 
        placeholder="Search records..." 
        style="width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:12px;font-size:15px;"
    >
</div>
            <div class="products-grid">
                <?php while ($row = $receivedOrders->fetch_assoc()): ?>
                    <div class="product-card tracked-order-card <?php echo ($highlightOrderId === (int)$row['id']) ? 'active-track' : ''; ?>" id="order-card-<?php echo (int)$row['id']; ?>">
                        <?php if ($highlightOrderId === (int)$row['id']): ?>
                            <div class="order-action-popup"><?php echo htmlspecialchars($highlightMessage); ?></div>
                        <?php endif; ?>

                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Ordered Product">
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>
                            <p class="meta"><strong>Buyer:</strong> <?php echo htmlspecialchars($row['buyer_name']); ?></p>
                            <p class="meta"><strong>Email:</strong> <?php echo htmlspecialchars($row['buyer_email']); ?></p>

                            <?php if ((int)$row['buyer_received'] === 1): ?>
                                <span class="purchase-badge">Buyer confirmed receiving this product</span>
                            <?php else: ?>
                                <span class="status-note">Waiting for buyer confirmation</span>
                            <?php endif; ?>

                            <div class="product-actions" style="display:flex;flex-direction:column;gap:10px;align-items:stretch;margin-top:12px;">
                                <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>

                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">

                                    <select name="seller_delivery_status" style="width:100%;padding:10px;border-radius:10px;border:1px solid #d1d5db;margin-bottom:10px;">
                                        <option value="pending" <?php echo ($row['seller_delivery_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo ($row['seller_delivery_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="out_for_delivery" <?php echo ($row['seller_delivery_status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo ($row['seller_delivery_status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    </select>

                                    <button type="submit" name="update_delivery_status" class="small-btn dark" style="width:100%;">
                                        Update Delivery Status
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="inline-empty">No received orders yet.</p>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("profileSearchInput");
    const cards = document.querySelectorAll(".product-card");

    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();

        cards.forEach(function (card) {
            const text = card.textContent.toLowerCase();

            if (text.includes(keyword)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
});
</script>

<script src="js/script.js"></script>

<?php if ($highlightOrderId > 0): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const card = document.getElementById("order-card-<?php echo (int)$highlightOrderId; ?>");

    if (card) {
        card.scrollIntoView({behavior:"smooth", block:"center"});
    }
});
</script>
<?php endif; ?>

</body>
</html>