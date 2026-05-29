<?php
session_start();
include "../config/db.php";
include "admin_layout.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$adminEmail = $conn->real_escape_string($_SESSION['admin']);
$adminCheck = $conn->query("
    SELECT * 
    FROM users 
    WHERE email='$adminEmail' 
    AND role='admin' 
    LIMIT 1
");
$adminUser = $adminCheck ? $adminCheck->fetch_assoc() : null;

if (!$adminUser) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$_SESSION['admin_name'] = $adminUser['name'] ?? 'Admin';

$success = "";
$error = "";

/* Admin updates full order control */
if (isset($_POST['update_order_admin'])) {
    $orderId = (int)$_POST['order_id'];
    $paymentStatus = trim($_POST['payment_status']);
    $orderStatus = trim($_POST['order_status']);
    $sellerDeliveryStatus = trim($_POST['seller_delivery_status']);
    $buyerReceived = isset($_POST['buyer_received']) ? (int)$_POST['buyer_received'] : 0;

    $allowedPayment = ['pending', 'paid', 'failed'];
    $allowedOrder = ['pending', 'processing', 'completed', 'cancelled'];
    $allowedDelivery = ['pending', 'processing', 'out_for_delivery', 'delivered'];

    if (
        !in_array($paymentStatus, $allowedPayment, true) ||
        !in_array($orderStatus, $allowedOrder, true) ||
        !in_array($sellerDeliveryStatus, $allowedDelivery, true) ||
        !in_array($buyerReceived, [0, 1], true)
    ) {
        $error = "Invalid order update values.";
    } else {
        $paymentStatusEsc = $conn->real_escape_string($paymentStatus);
        $orderStatusEsc = $conn->real_escape_string($orderStatus);
        $sellerDeliveryStatusEsc = $conn->real_escape_string($sellerDeliveryStatus);

        $deliveredAtSql = "";
        $buyerReceivedAtSql = "";

        if ($sellerDeliveryStatusEsc === 'delivered') {
            $deliveredAtSql = ", delivered_at = IF(delivered_at IS NULL, NOW(), delivered_at)";
        } else {
            $deliveredAtSql = ", delivered_at = NULL, buyer_received = 0, buyer_received_at = NULL";
        }

        if ($buyerReceived === 1) {
            $buyerReceivedAtSql = ", buyer_received_at = IF(buyer_received_at IS NULL, NOW(), buyer_received_at)";
            $orderStatusEsc = 'completed';
        } else {
            $buyerReceivedAtSql = ", buyer_received_at = NULL";

            if ($sellerDeliveryStatusEsc === 'pending') {
                $orderStatusEsc = 'pending';
            } elseif ($orderStatusEsc !== 'cancelled') {
                $orderStatusEsc = 'processing';
            }
        }

        $updateSql = "
            UPDATE orders
            SET payment_status = '$paymentStatusEsc',
                order_status = '$orderStatusEsc',
                seller_delivery_status = '$sellerDeliveryStatusEsc',
                buyer_received = $buyerReceived
                $deliveredAtSql
                $buyerReceivedAtSql
            WHERE id = $orderId
            LIMIT 1
        ";

        if ($conn->query($updateSql)) {
            $success = "Order updated successfully.";
        } else {
            $error = "Could not update order.";
        }
    }
}

$orders = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image,
        p.city,
        p.product_condition,
        seller.name AS seller_name,
        seller.email AS seller_email
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    LEFT JOIN users seller ON p.user_id = seller.id
    ORDER BY o.created_at DESC, o.id DESC
");

function paymentBadgeClass($status) {
    if ($status === 'paid') return 'badge-green';
    if ($status === 'failed') return 'badge-red';
    return 'badge-yellow';
}

function orderBadgeClass($status) {
    if ($status === 'completed') return 'badge-green';
    if ($status === 'cancelled') return 'badge-red';
    if ($status === 'processing') return 'badge-blue';
    return 'badge-yellow';
}

function deliveryBadgeClass($status) {
    if ($status === 'delivered') return 'badge-green';
    if ($status === 'out_for_delivery') return 'badge-blue';
    if ($status === 'processing') return 'badge-yellow';
    return 'badge-gray';
}

adminHeader("Manage Orders");
?>

<?php if ($success): ?>
    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin-top:0;">Order Management</h2>
    <p class="muted" style="margin-bottom:18px;">
        Monitor order payments, delivery progress, and buyer confirmation status.
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Qty</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Order</th>
                    <th>Delivery</th>
                    <th>Buyer Received</th>
                    <th>Date</th>
                    <th>Admin Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while ($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo (int)$row['id']; ?></strong>
                            </td>

                            <td>
                                <div style="display:flex;align-items:center;gap:12px;min-width:210px;">
                                    <?php if (!empty($row['product_image'])): ?>
                                        <img 
                                            src="../uploads/<?php echo htmlspecialchars($row['product_image']); ?>" 
                                            alt="Product"
                                            style="width:58px;height:58px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;"
                                        >
                                    <?php else: ?>
                                        <span class="badge badge-gray">No Image</span>
                                    <?php endif; ?>

                                    <div>
                                        <strong><?php echo htmlspecialchars($row['product_name'] ?? 'Deleted Product'); ?></strong><br>
                                        <span class="muted">
                                            <?php echo htmlspecialchars($row['city'] ?? ''); ?>
                                            <?php if (!empty($row['product_condition'])): ?>
                                                • <?php echo htmlspecialchars($row['product_condition']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['buyer_name'] ?? 'N/A'); ?></strong><br>
                                <span class="muted"><?php echo htmlspecialchars($row['buyer_email'] ?? 'N/A'); ?></span><br>
                                <span class="muted"><?php echo htmlspecialchars($row['buyer_phone'] ?? 'N/A'); ?></span>
                            </td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['seller_name'] ?? 'Unknown Seller'); ?></strong><br>
                                <span class="muted"><?php echo htmlspecialchars($row['seller_email'] ?? 'N/A'); ?></span>
                            </td>

                            <td>
                                <?php echo (int)($row['quantity'] ?? 0); ?>
                            </td>

                            <td>
                                <strong>Rs <?php echo number_format((float)($row['amount'] ?? 0), 2); ?></strong>
                            </td>

                            <td>
                                <span class="badge <?php echo paymentBadgeClass($row['payment_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['payment_status'] ?? 'pending')); ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?php echo orderBadgeClass($row['order_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($row['order_status'] ?? 'pending')); ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge <?php echo deliveryBadgeClass($row['seller_delivery_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['seller_delivery_status'] ?? 'pending'))); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ((int)($row['buyer_received'] ?? 0) === 1): ?>
                                    <span class="badge badge-green">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-red">No</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="muted"><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></span><br>

                                <?php if (!empty($row['delivered_at'])): ?>
                                    <span class="muted">
                                        Delivered: <?php echo htmlspecialchars($row['delivered_at']); ?>
                                    </span><br>
                                <?php endif; ?>

                                <?php if (!empty($row['buyer_received_at'])): ?>
                                    <span class="muted">
                                        Confirmed: <?php echo htmlspecialchars($row['buyer_received_at']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="POST" style="display:grid;gap:8px;min-width:190px;">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">

                                    <select name="payment_status">
                                        <option value="pending" <?php echo (($row['payment_status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Payment Pending</option>
                                        <option value="paid" <?php echo (($row['payment_status'] ?? '') === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo (($row['payment_status'] ?? '') === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                    </select>

                                    <select name="order_status">
                                        <option value="pending" <?php echo (($row['order_status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Order Pending</option>
                                        <option value="processing" <?php echo (($row['order_status'] ?? '') === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo (($row['order_status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo (($row['order_status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>

                                    <select name="seller_delivery_status">
                                        <option value="pending" <?php echo (($row['seller_delivery_status'] ?? '') === 'pending') ? 'selected' : ''; ?>>Delivery Pending</option>
                                        <option value="processing" <?php echo (($row['seller_delivery_status'] ?? '') === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="out_for_delivery" <?php echo (($row['seller_delivery_status'] ?? '') === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo (($row['seller_delivery_status'] ?? '') === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    </select>

                                    <select name="buyer_received">
                                        <option value="0" <?php echo ((int)($row['buyer_received'] ?? 0) === 0) ? 'selected' : ''; ?>>Buyer Not Confirmed</option>
                                        <option value="1" <?php echo ((int)($row['buyer_received'] ?? 0) === 1) ? 'selected' : ''; ?>>Buyer Confirmed</option>
                                    </select>

                                    <button type="submit" name="update_order_admin" class="mini-btn btn-blue">
                                        Update Order
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter($success, $error); ?>