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
$totalCategories = 0;

$res = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($res) $totalUsers = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($res) $totalProducts = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($res) $totalOrders = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE seller_status='pending'");
if ($res) $totalPendingSellers = (int)$res->fetch_assoc()['total'];

$res = $conn->query("SELECT COUNT(*) AS total FROM categories");
if ($res) $totalCategories = (int)$res->fetch_assoc()['total'];

$pendingSellers = $conn->query("
    SELECT id, name, email, seller_requested_at
    FROM users
    WHERE seller_status='pending'
    ORDER BY seller_requested_at DESC
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
    <div class="stat-card">
        <h3>Total Users</h3>
        <div class="number"><?php echo $totalUsers; ?></div>
    </div>

    <div class="stat-card">
        <h3>Total Products</h3>
        <div class="number"><?php echo $totalProducts; ?></div>
    </div>

    <div class="stat-card">
        <h3>Total Orders</h3>
        <div class="number"><?php echo $totalOrders; ?></div>
    </div>

    <div class="stat-card">
        <h3>Categories</h3>
        <div class="number"><?php echo $totalCategories; ?></div>
    </div>

    <div class="stat-card">
        <h3>Pending Seller Requests</h3>
        <div class="number"><?php echo $totalPendingSellers; ?></div>
    </div>
</div>

<div class="admin-card">
    <br><h2>Seller Verification Notifications</h2>

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