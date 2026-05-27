<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$totalUsers = 0;
$totalProducts = 0;
$totalOrders = 0;
$totalPendingSellers = 0;

$userRes = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($userRes) {
    $totalUsers = (int)$userRes->fetch_assoc()['total'];
}

$productRes = $conn->query("SELECT COUNT(*) AS total FROM products");
if ($productRes) {
    $totalProducts = (int)$productRes->fetch_assoc()['total'];
}

$orderRes = $conn->query("SELECT COUNT(*) AS total FROM orders");
if ($orderRes) {
    $totalOrders = (int)$orderRes->fetch_assoc()['total'];
}

$sellerReqRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE seller_status='pending'");
if ($sellerReqRes) {
    $totalPendingSellers = (int)$sellerReqRes->fetch_assoc()['total'];
}

$pendingSellers = $conn->query("
    SELECT id, name, email, seller_requested_at
    FROM users
    WHERE seller_status='pending'
    ORDER BY seller_requested_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TradeSphere</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{
            background:#f8fafc;
        }

        .admin-wrap{
            max-width:1200px;
            margin:30px auto;
            padding:20px;
        }

        .admin-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
            flex-wrap:wrap;
            margin-bottom:20px;
        }

        .admin-header h1{
            margin:0;
        }

        .admin-nav{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-bottom:24px;
        }

        .admin-nav a{
            text-decoration:none;
            padding:10px 14px;
            border-radius:10px;
            background:#111827;
            color:white;
            font-size:14px;
        }

        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:18px;
            margin-bottom:24px;
        }

        .stat-card{
            background:white;
            padding:22px;
            border-radius:18px;
            box-shadow:0 10px 28px rgba(0,0,0,0.08);
        }

        .stat-card h3{
            margin-bottom:10px;
            color:#374151;
            font-size:16px;
        }

        .stat-card .number{
            font-size:34px;
            font-weight:800;
            color:#111827;
        }

        .notification-card{
            background:white;
            padding:22px;
            border-radius:18px;
            box-shadow:0 10px 28px rgba(0,0,0,0.08);
            margin-top:18px;
        }

        .notification-card h2{
            margin-bottom:14px;
        }

        .seller-alert{
            border:1px solid #facc15;
            background:#fefce8;
            padding:14px;
            border-radius:12px;
            margin-bottom:12px;
            display:flex;
            justify-content:space-between;
            gap:14px;
            align-items:center;
            flex-wrap:wrap;
        }

        .seller-alert strong{
            color:#111827;
        }

        .seller-alert p{
            margin:4px 0 0;
            color:#4b5563;
            font-size:14px;
        }

        .badge-pending{
            display:inline-block;
            background:#fef3c7;
            color:#92400e;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:700;
        }

        .empty-note{
            color:#6b7280;
            margin-top:8px;
        }
    </style>
</head>
<body>

<div class="admin-wrap">

    <div class="admin-header">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="helper">Monitor TradeSphere users, products, orders, and seller verification requests.</p>
        </div>

        <a href="admin_logout.php" class="btn btn-dark">Logout</a>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Manage Products</a>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="manage_users.php">Manage Users</a>
    </div>

    <div class="stats-grid">
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
            <h3>Pending Seller Requests</h3>
            <div class="number"><?php echo $totalPendingSellers; ?></div>
        </div>
    </div>

    <div class="notification-card">
        <h2>Seller Verification Notifications</h2>

        <?php if ($pendingSellers && $pendingSellers->num_rows > 0): ?>
            <?php while ($seller = $pendingSellers->fetch_assoc()): ?>
                <div class="seller-alert">
                    <div>
                        <span class="badge-pending">Pending Verification</span>
                        <p>
                            <strong><?php echo htmlspecialchars($seller['name']); ?></strong>
                            requested seller verification.
                        </p>
                        <p>Email: <?php echo htmlspecialchars($seller['email']); ?></p>
                        <p>Requested At: <?php echo htmlspecialchars($seller['seller_requested_at']); ?></p>
                    </div>

                    <a href="manage_users.php" class="btn btn-primary">
                        Review
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-note">No new seller verification requests.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>