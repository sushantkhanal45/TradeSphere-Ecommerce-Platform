<?php
session_start();
include "../config/db.php";
include "../includes/rsa_helper.php";

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

$signatures = $conn->query("
    SELECT s.*, u.name AS user_name, u.email AS user_email
    FROM signatures s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSA Signatures - TradeSphere Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{
            margin:0;
            background:#f8fafc;
            font-family:"Segoe UI", Arial, sans-serif;
        }

        .admin-layout{display:flex; min-height:100vh;}

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

        .admin-card{
            background:white;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:24px;
            overflow-x:auto;
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

        .signature-box{
            max-width:260px;
            word-break:break-all;
            font-size:12px;
            color:#374151;
            line-height:1.5;
        }

        .json-box{
            max-width:320px;
            white-space:pre-wrap;
            word-break:break-word;
            font-size:12px;
            color:#374151;
            line-height:1.5;
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
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="signatures.php" class="active">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>RSA Signed Actions</h1>
            <p>Review digitally signed marketplace actions and verify their integrity.</p>
        </div>

        <div class="admin-card">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action Type</th>
                    <th>Related ID</th>
                    <th>Signed Data</th>
                    <th>Signature Status</th>
                    <th>Created At</th>
                </tr>

                <?php if ($signatures && $signatures->num_rows > 0): ?>
                    <?php while ($row = $signatures->fetch_assoc()): ?>
                        <?php
                            $isValid = verifySignature($row['signed_data'], $row['signature']);
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td>
                                <?php if (!empty($row['user_name'])): ?>
                                    <strong><?php echo htmlspecialchars($row['user_name']); ?></strong><br>
                                    <span style="color:#6b7280;"><?php echo htmlspecialchars($row['user_email']); ?></span>
                                <?php else: ?>
                                    <span class="admin-badge badge-gray">Unknown User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-badge badge-blue">
                                    <?php echo htmlspecialchars($row['action_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['related_id']); ?></td>
                            <td>
                                <div class="json-box"><?php echo htmlspecialchars($row['signed_data']); ?></div>
                            </td>
                            <td>
                                <?php if ($isValid): ?>
                                    <span class="admin-badge badge-green">Valid Signature</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-red">Invalid Signature</span>
                                <?php endif; ?>
                                <div class="signature-box" style="margin-top:8px;">
                                    <?php echo htmlspecialchars(substr($row['signature'], 0, 80)); ?>...
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No signature records found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

</body>
</html>