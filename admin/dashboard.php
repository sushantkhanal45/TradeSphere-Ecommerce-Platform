<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$adminEmail = $conn->real_escape_string($_SESSION['admin']);
$adminCheck = $conn->query("SELECT * FROM users WHERE email='$adminEmail' AND role='admin' LIMIT 1");
$adminUser = $adminCheck ? $adminCheck->fetch_assoc() : null;

if (!$adminUser) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$totalUsersRes = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='user'");
$totalUsers = $totalUsersRes ? (int)$totalUsersRes->fetch_assoc()['total'] : 0;

$totalProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $totalProductsRes ? (int)$totalProductsRes->fetch_assoc()['total'] : 0;

$availableProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status='available'");
$availableProducts = $availableProductsRes ? (int)$availableProductsRes->fetch_assoc()['total'] : 0;

$soldProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status='sold'");
$soldProducts = $soldProductsRes ? (int)$soldProductsRes->fetch_assoc()['total'] : 0;

$totalOrdersRes = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = $totalOrdersRes ? (int)$totalOrdersRes->fetch_assoc()['total'] : 0;

$paidOrdersRes = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE payment_status='paid'");
$paidOrders = $paidOrdersRes ? (int)$paidOrdersRes->fetch_assoc()['total'] : 0;

$deliveredOrdersRes = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE seller_delivery_status='delivered'");
$deliveredOrders = $deliveredOrdersRes ? (int)$deliveredOrdersRes->fetch_assoc()['total'] : 0;

$buyerConfirmedRes = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE buyer_received=1");
$buyerConfirmed = $buyerConfirmedRes ? (int)$buyerConfirmedRes->fetch_assoc()['total'] : 0;

$recentOrders = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT 8
");

$recentUsers = $conn->query("
    SELECT id, name, email, role
    FROM users
    ORDER BY id DESC
    LIMIT 5
");

$recentProducts = $conn->query("
    SELECT id, name, price, status, image
    FROM products
    ORDER BY id DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TradeSphere</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{
            margin:0;
            background:#f8fafc;
            font-family:"Segoe UI", Arial, sans-serif;
        }

        .admin-layout{
            display:flex;
            min-height:100vh;
        }

        .admin-sidebar{
            width:260px;
            background:#0f172a;
            color:white;
            padding:28px 20px;
            position:fixed;
            top:0;
            left:0;
            height:100vh;
        }

        .admin-brand{
            font-size:28px;
            font-weight:700;
            margin-bottom:28px;
        }

        .admin-brand a{
            color:white;
            text-decoration:none;
        }

        .admin-user-box{
            background:rgba(255,255,255,0.08);
            border-radius:16px;
            padding:16px;
            margin-bottom:28px;
        }

        .admin-user-box h3{
            margin:0 0 6px 0;
            font-size:17px;
        }

        .admin-user-box p{
            margin:0;
            font-size:13px;
            color:#cbd5e1;
            word-break:break-word;
        }

        .admin-menu{
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .admin-menu a{
            color:white;
            text-decoration:none;
            padding:12px 14px;
            border-radius:12px;
            font-size:15px;
        }

        .admin-menu a:hover,
        .admin-menu a.active{
            background:#38bdf8;
            color:#062033;
            font-weight:600;
        }

        .admin-main{
            margin-left:260px;
            width:calc(100% - 260px);
            padding:34px;
        }

        .admin-header{
            margin-bottom:24px;
        }

        .admin-header h1{
            margin:0 0 8px 0;
            font-size:34px;
        }

        .admin-header p{
            margin:0;
            color:#6b7280;
        }

        .stats-grid{
            display:grid;
            grid-template-columns:repeat(4, minmax(0, 1fr));
            gap:18px;
            margin-bottom:26px;
        }

        .stat-card{
            background:white;
            border-radius:20px;
            padding:22px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        }

        .stat-card h3{
            margin:0 0 10px 0;
            font-size:15px;
            color:#6b7280;
            font-weight:600;
        }

        .stat-card p{
            margin:0;
            font-size:32px;
            font-weight:700;
            color:#111827;
        }

        .panel-grid{
            display:grid;
            grid-template-columns:2fr 1fr;
            gap:20px;
        }

        .panel-card{
            background:white;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:22px;
        }

        .panel-card h2{
            margin:0 0 18px 0;
            font-size:22px;
        }

        .admin-table{
            width:100%;
            border-collapse:collapse;
        }

        .admin-table th,
        .admin-table td{
            text-align:left;
            padding:12px 10px;
            border-bottom:1px solid #e5e7eb;
            font-size:14px;
            vertical-align:top;
        }

        .admin-table th{
            background:#f8fafc;
        }

        .mini-list{
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .mini-item{
            padding:14px;
            border:1px solid #e5e7eb;
            border-radius:14px;
            background:#fafafa;
        }

        .mini-item strong{
            display:block;
            margin-bottom:6px;
        }

        .badge{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:700;
        }

        .badge-green{background:#dcfce7;color:#166534;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-yellow{background:#fef3c7;color:#92400e;}
        .badge-gray{background:#e5e7eb;color:#374151;}

        .quick-links{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
            margin-top:16px;
        }

        .quick-links a{
            text-decoration:none;
            background:#2563eb;
            color:white;
            padding:10px 14px;
            border-radius:12px;
            font-weight:600;
        }

        .quick-links a:hover{
            background:#1d4ed8;
        }

        @media (max-width: 1100px){
            .stats-grid{
                grid-template-columns:repeat(2, minmax(0,1fr));
            }

            .panel-grid{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 900px){
            .admin-sidebar{
                position:relative;
                width:100%;
                height:auto;
            }

            .admin-main{
                margin-left:0;
                width:100%;
                padding:20px;
            }

            .admin-layout{
                flex-direction:column;
            }
        }

        @media (max-width: 640px){
            .stats-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand"><a href="dashboard.php">TradeSphere</a></div>

        <div class="admin-user-box">
            <h3><?php echo htmlspecialchars($adminUser['name']); ?></h3>
            <p><?php echo htmlspecialchars($adminUser['email']); ?></p>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="manage_orders.php">Manage Orders</a>
            <a href="signature.php">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Overview of users, products, and order activity.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Products</h3>
                <p><?php echo $totalProducts; ?></p>
            </div>

            <div class="stat-card">
                <h3>Available Products</h3>
                <p><?php echo $availableProducts; ?></p>
            </div>

            <div class="stat-card">
                <h3>Sold Products</h3>
                <p><?php echo $soldProducts; ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?php echo $totalOrders; ?></p>
            </div>

            <div class="stat-card">
                <h3>Paid Orders</h3>
                <p><?php echo $paidOrders; ?></p>
            </div>

            <div class="stat-card">
                <h3>Delivered Orders</h3>
                <p><?php echo $deliveredOrders; ?></p>
            </div>

            <div class="stat-card">
                <h3>Buyer Confirmed</h3>
                <p><?php echo $buyerConfirmed; ?></p>
            </div>
        </div>

        <div class="panel-grid">
            <div class="panel-card">
                <h2>Recent Orders</h2>

                <table class="admin-table">
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                        <th>Confirmed</th>
                    </tr>

                    <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                        <?php while ($row = $recentOrders->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['product_name'] ?? 'Deleted Product'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['buyer_name']); ?><br>
                                    <small><?php echo htmlspecialchars($row['buyer_email']); ?></small>
                                </td>
                                <td>Rs <?php echo number_format((float)$row['amount'], 2); ?></td>
                                <td>
                                    <?php if ($row['payment_status'] === 'paid'): ?>
                                        <span class="badge badge-green">Paid</span>
                                    <?php elseif ($row['payment_status'] === 'pending'): ?>
                                        <span class="badge badge-yellow">Pending</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray"><?php echo htmlspecialchars(ucfirst($row['payment_status'])); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-blue">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['seller_delivery_status']))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)$row['buyer_received'] === 1): ?>
                                        <span class="badge badge-green">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                </table>

                <div class="quick-links">
                    <a href="manage_orders.php">Open Manage Orders</a>
                    <a href="manage_products.php">Open Manage Products</a>
                    <a href="manage_users.php">Open Manage Users</a>
                </div>
            </div>

            <div class="panel-card">
                <h2>Recent Users</h2>

                <div class="mini-list">
                    <?php if ($recentUsers && $recentUsers->num_rows > 0): ?>
                        <?php while ($u = $recentUsers->fetch_assoc()): ?>
                            <div class="mini-item">
                                <strong><?php echo htmlspecialchars($u['name']); ?></strong>
                                <div><?php echo htmlspecialchars($u['email']); ?></div>
                                <small><?php echo htmlspecialchars(ucfirst($u['role'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No users found.</p>
                    <?php endif; ?>
                </div>

                <h2 style="margin-top:24px;">Recent Products</h2>

                <div class="mini-list">
                    <?php if ($recentProducts && $recentProducts->num_rows > 0): ?>
                        <?php while ($p = $recentProducts->fetch_assoc()): ?>
                            <div class="mini-item">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <div>Rs <?php echo number_format((float)$p['price'], 2); ?></div>
                                <small>Status: <?php echo htmlspecialchars(ucfirst($p['status'])); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No products found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>