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

$totalUsersRes = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $totalUsersRes ? $totalUsersRes->fetch_assoc()['total'] : 0;

$totalProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $totalProductsRes ? $totalProductsRes->fetch_assoc()['total'] : 0;

$soldProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status='sold'");
$soldProducts = $soldProductsRes ? $soldProductsRes->fetch_assoc()['total'] : 0;

$availableProductsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE status='available'");
$availableProducts = $availableProductsRes ? $availableProductsRes->fetch_assoc()['total'] : 0;

$totalSignaturesRes = $conn->query("SELECT COUNT(*) AS total FROM signatures");
$totalSignatures = $totalSignaturesRes ? $totalSignaturesRes->fetch_assoc()['total'] : 0;

$users = $conn->query("SELECT id, name, email, role, is_verified FROM users ORDER BY id DESC LIMIT 5");

$products = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
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
            border:1px solid rgba(255,255,255,0.08);
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
            transition:0.2s ease;
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
            margin-bottom:26px;
        }

        .admin-header h1{
            margin:0 0 8px 0;
            font-size:34px;
            color:#111827;
        }

        .admin-header p{
            margin:0;
            color:#6b7280;
        }

        .admin-grid{
            display:grid;
            grid-template-columns:repeat(5, minmax(0,1fr));
            gap:20px;
            margin-bottom:28px;
        }

        .admin-stat{
            background:white;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:24px;
        }

        .admin-stat h3{
            margin:0 0 10px 0;
            color:#6b7280;
            font-size:15px;
            font-weight:600;
        }

        .admin-stat p{
            margin:0;
            font-size:32px;
            font-weight:700;
            color:#111827;
        }

        .admin-card{
            background:white;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:24px;
            margin-bottom:24px;
            overflow-x:auto;
        }

        .admin-card h2{
            margin:0 0 18px 0;
            font-size:22px;
            color:#111827;
        }

        .admin-table{
            width:100%;
            border-collapse:collapse;
        }

        .admin-table th,
        .admin-table td{
            padding:14px 12px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            font-size:14px;
            vertical-align:top;
        }

        .admin-table th{
            background:#f8fafc;
            color:#374151;
        }

        .admin-badge{
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:600;
            display:inline-block;
        }

        .badge-green{background:#dcfce7;color:#166534;}
        .badge-red{background:#fee2e2;color:#991b1b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-gray{background:#e5e7eb;color:#374151;}

        @media (max-width: 1200px){
            .admin-grid{
                grid-template-columns:repeat(3, minmax(0,1fr));
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

        @media (max-width: 700px){
            .admin-grid{
                grid-template-columns:repeat(2, minmax(0,1fr));
            }
        }

        @media (max-width: 500px){
            .admin-grid{
                grid-template-columns:1fr;
            }

            .admin-header h1{
                font-size:28px;
            }
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <a href="dashboard.php">TradeSphere</a>
        </div>

        <div class="admin-user-box">
            <h3><?php echo htmlspecialchars($adminUser['name']); ?></h3>
            <p><?php echo htmlspecialchars($adminUser['email']); ?></p>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="signatures.php">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back. Here is your marketplace overview.</p>
        </div>

        <div class="admin-grid">
            <div class="admin-stat">
                <h3>Total Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>

            <div class="admin-stat">
                <h3>Total Products</h3>
                <p><?php echo $totalProducts; ?></p>
            </div>

            <div class="admin-stat">
                <h3>Sold Products</h3>
                <p><?php echo $soldProducts; ?></p>
            </div>

            <div class="admin-stat">
                <h3>Available Products</h3>
                <p><?php echo $availableProducts; ?></p>
            </div>

            <div class="admin-stat">
                <h3>Signed Actions</h3>
                <p><?php echo $totalSignatures; ?></p>
            </div>
        </div>

        <div class="admin-card">
            <h2>Recent Users</h2>
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verified</th>
                </tr>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="admin-badge badge-blue">Admin</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-gray">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$row['is_verified'] === 1): ?>
                                    <span class="admin-badge badge-green">Verified</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-red">Not Verified</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>

        <div class="admin-card">
            <h2>Recent Products</h2>
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Price</th>
                    <th>Status</th>
                </tr>
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_condition']); ?></td>
                            <td>Rs <?php echo htmlspecialchars($row['price']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'sold'): ?>
                                    <span class="admin-badge badge-red">Sold</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-blue">Available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

</body>
</html>