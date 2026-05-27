<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* Seller approval/rejection */
if (isset($_POST['approve_seller'])) {
    $userId = (int)$_POST['user_id'];

    $conn->query("
        UPDATE users
        SET seller_status='approved',
            seller_verified_at=NOW()
        WHERE id=$userId
    ");

    header("Location: manage_users.php");
    exit();
}

if (isset($_POST['reject_seller'])) {
    $userId = (int)$_POST['user_id'];

    $conn->query("
        UPDATE users
        SET seller_status='rejected'
        WHERE id=$userId
    ");

    header("Location: manage_users.php");
    exit();
}

/* Role management */
if (isset($_POST['make_admin'])) {
    $userId = (int)$_POST['user_id'];

    $conn->query("
        UPDATE users
        SET role='admin'
        WHERE id=$userId
    ");

    header("Location: manage_users.php");
    exit();
}

if (isset($_POST['make_user'])) {
    $userId = (int)$_POST['user_id'];

    $conn->query("
        UPDATE users
        SET role='user'
        WHERE id=$userId
    ");

    header("Location: manage_users.php");
    exit();
}

$users = $conn->query("
    SELECT id, name, email, role, is_verified, seller_status, seller_requested_at, seller_verified_at, created_at
    FROM users
    ORDER BY 
        CASE 
            WHEN seller_status='pending' THEN 0
            ELSE 1
        END,
        id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - TradeSphere Admin</title>
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
            margin-bottom:20px;
        }

        .admin-nav a{
            text-decoration:none;
            padding:10px 14px;
            border-radius:10px;
            background:#111827;
            color:white;
            font-size:14px;
        }

        .table-card{
            background:white;
            border-radius:16px;
            box-shadow:0 10px 28px rgba(0,0,0,0.08);
            padding:20px;
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
            min-width:1050px;
        }

        th, td{
            padding:12px 10px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            vertical-align:middle;
            font-size:14px;
        }

        th{
            background:#f1f5f9;
            font-weight:700;
        }

        .badge{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:700;
        }

        .badge-admin{
            background:#ede9fe;
            color:#6d28d9;
        }

        .badge-user{
            background:#e0f2fe;
            color:#0369a1;
        }

        .badge-verified{
            background:#dcfce7;
            color:#166534;
        }

        .badge-unverified{
            background:#fee2e2;
            color:#991b1b;
        }

        .seller-none{
            background:#f3f4f6;
            color:#374151;
        }

        .seller-pending{
            background:#fef3c7;
            color:#92400e;
        }

        .seller-approved{
            background:#dcfce7;
            color:#166534;
        }

        .seller-rejected{
            background:#fee2e2;
            color:#991b1b;
        }

        .action-row{
            display:flex;
            gap:6px;
            flex-wrap:wrap;
        }

        .mini-btn{
            border:none;
            padding:7px 10px;
            border-radius:8px;
            cursor:pointer;
            font-size:12px;
            font-weight:700;
        }

        .approve-btn{
            background:#16a34a;
            color:white;
        }

        .reject-btn{
            background:#dc2626;
            color:white;
        }

        .role-btn{
            background:#2563eb;
            color:white;
        }

        .muted{
            color:#6b7280;
            font-size:12px;
        }
    </style>
</head>
<body>

<div class="admin-wrap">

    <div class="admin-header">
        <div>
            <h1>Manage Users</h1>
            <p class="muted">View users, manage roles, and verify seller requests.</p>
        </div>

        <a href="admin_logout.php" class="btn btn-dark">Logout</a>
    </div>

    <div class="admin-nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_products.php">Manage Products</a>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="manage_users.php">Manage Users</a>
    </div>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User Details</th>
                    <th>Role</th>
                    <th>Email Verification</th>
                    <th>Seller Status</th>
                    <th>Seller Dates</th>
                    <th>Seller Action</th>
                    <th>Role Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($users && $users->num_rows > 0): ?>
                    <?php while ($row = $users->fetch_assoc()): ?>
                        <?php
                            $sellerStatus = $row['seller_status'] ?? 'none';

                            if ($sellerStatus === '') {
                                $sellerStatus = 'none';
                            }
                        ?>

                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                <span class="muted"><?php echo htmlspecialchars($row['email']); ?></span><br>
                                <span class="muted">Joined: <?php echo htmlspecialchars($row['created_at'] ?? ''); ?></span>
                            </td>

                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int)$row['is_verified'] === 1): ?>
                                    <span class="badge badge-verified">Verified</span>
                                <?php else: ?>
                                    <span class="badge badge-unverified">Not Verified</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge seller-<?php echo htmlspecialchars($sellerStatus); ?>">
                                    <?php echo ucfirst(htmlspecialchars($sellerStatus)); ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($row['seller_requested_at'])): ?>
                                    <span class="muted">Requested: <?php echo htmlspecialchars($row['seller_requested_at']); ?></span><br>
                                <?php endif; ?>

                                <?php if (!empty($row['seller_verified_at'])): ?>
                                    <span class="muted">Verified: <?php echo htmlspecialchars($row['seller_verified_at']); ?></span>
                                <?php endif; ?>

                                <?php if (empty($row['seller_requested_at']) && empty($row['seller_verified_at'])): ?>
                                    <span class="muted">No seller request</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($sellerStatus === 'pending'): ?>
                                    <form method="POST" class="action-row">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">

                                        <button type="submit" name="approve_seller" class="mini-btn approve-btn">
                                            Approve
                                        </button>

                                        <button type="submit" name="reject_seller" class="mini-btn reject-btn">
                                            Reject
                                        </button>
                                    </form>
                                <?php elseif ($sellerStatus === 'approved'): ?>
                                    <span class="muted">Seller approved</span>
                                <?php elseif ($sellerStatus === 'rejected'): ?>
                                    <span class="muted">Request rejected</span>
                                <?php else: ?>
                                    <span class="muted">No action</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="make_user" class="mini-btn role-btn">
                                            Make User
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="make_admin" class="mini-btn role-btn">
                                            Make Admin
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>