<?php
session_start();
include "../config/db.php";
include "admin_layout.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$totalUsers = 0;
$totalProducts = 0;
$totalOrders = 0;
$totalPendingSellers = 0;
$totalPendingProducts = 0;
$totalPendingUsers = 0;
$totalCategories = 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($res) $totalUsers = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($res) $totalProducts = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($res) $totalOrders = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE seller_status='pending'");
if ($res) $totalPendingSellers = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM products WHERE ai_status='manual_review'");
if ($res) $totalPendingProducts = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE account_status='pending_admin'");
if ($res) $totalPendingUsers = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM categories");
if ($res) $totalCategories = (int)$res->fetch_assoc()['total'];

$pendingSellers = $conn->query("
    SELECT id, name, email, seller_requested_at
    FROM users
    WHERE seller_status='pending'
    ORDER BY seller_requested_at DESC
    LIMIT 5
");

$pendingProducts = $conn->query("
    SELECT p.id, p.name, p.ai_reason, p.created_at, u.name AS seller_name, u.email AS seller_email
    FROM products p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.ai_status='manual_review'
    ORDER BY p.created_at DESC
    LIMIT 5
");

$pendingUsers = $conn->query("
    SELECT id, name, email, reapply_requested_at, removal_reason
    FROM users
    WHERE account_status='pending_admin'
    ORDER BY reapply_requested_at DESC
    LIMIT 5
");

$recentOrders = $conn->query("
    SELECT o.id, o.buyer_name, o.amount, o.payment_status, o.order_status, o.created_at, p.name AS product_name
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.id DESC
    LIMIT 5
");

adminHeader("Dashboard");
?>

<div class="admin-grid">
    <a href="manage_users.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Total Users</h3>
        <div class="number"><?php echo $totalUsers; ?></div>
    </a>

    <a href="manage_products.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Total Products</h3>
        <div class="number"><?php echo $totalProducts; ?></div>
    </a>

    <a href="manage_orders.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Total Orders</h3>
        <div class="number"><?php echo $totalOrders; ?></div>
    </a>

    <a href="manage_categories.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Categories</h3>
        <div class="number"><?php echo $totalCategories; ?></div>
    </a>

    <a href="manage_users.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Pending Seller Requests</h3>
        <div class="number"><?php echo $totalPendingSellers; ?></div>
    </a>

    <a href="manage_products.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Pending Product Reviews</h3>
        <div class="number"><?php echo $totalPendingProducts; ?></div>
    </a>

    <a href="manage_users.php" class="stat-card" style="text-decoration:none;color:inherit;">
        <h3>Pending User Verification</h3>
        <div class="number"><?php echo $totalPendingUsers; ?></div>
    </a>
</div>

<br>

<div class="admin-card">
    <h2>Seller Verification Notifications</h2>

    <?php if ($pendingSellers && $pendingSellers->num_rows > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Requested At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($seller = $pendingSellers->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($seller['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($seller['email']); ?></td>
                            <td><?php echo htmlspecialchars($seller['seller_requested_at']); ?></td>
                            <td>
                                <a href="manage_users.php" class="mini-btn btn-blue">Review</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">No new seller verification requests.</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>Product Verification Notifications</h2>

    <?php if ($pendingProducts && $pendingProducts->num_rows > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Reason</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $pendingProducts->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($product['seller_name'] ?? 'Unknown'); ?><br>
                                <span class="muted"><?php echo htmlspecialchars($product['seller_email'] ?? ''); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($product['ai_reason'] ?? 'Needs admin review'); ?></td>
                            <td><?php echo htmlspecialchars($product['created_at']); ?></td>
                            <td>
                                <a href="manage_products.php" class="mini-btn btn-blue">Review</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">No products pending verification.</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>User Verification Notifications</h2>

    <?php if ($pendingUsers && $pendingUsers->num_rows > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Previous Removal Reason</th>
                        <th>Requested At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $pendingUsers->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['removal_reason'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['reapply_requested_at'] ?? ''); ?></td>
                            <td>
                                <a href="manage_users.php" class="mini-btn btn-blue">Review</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">No user re-verification requests.</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <h2>Recent Orders</h2>

    <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recentOrders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo (int)$order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['product_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                            <td>Rs <?php echo number_format((float)$order['amount'], 2); ?></td>
                            <td>
                                <span class="badge badge-blue">
                                    <?php echo htmlspecialchars($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-yellow">
                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="muted">No recent orders found.</p>
    <?php endif; ?>
</div>

<?php adminFooter(); ?>