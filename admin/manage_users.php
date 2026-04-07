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

$success = "";
$error = "";

if (isset($_POST['make_admin'])) {
    $userId = (int)$_POST['user_id'];
    if ($conn->query("UPDATE users SET role='admin' WHERE id=$userId")) {
        $success = "User promoted to admin successfully.";
    } else {
        $error = "Could not promote user.";
    }
}

if (isset($_POST['make_user'])) {
    $userId = (int)$_POST['user_id'];
    if ($userId === (int)$adminUser['id']) {
        $error = "You cannot remove your own admin role.";
    } elseif ($conn->query("UPDATE users SET role='user' WHERE id=$userId")) {
        $success = "Admin changed back to user successfully.";
    } else {
        $error = "Could not update user role.";
    }
}

$users = $conn->query("SELECT id, name, email, role, is_verified FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - TradeSphere Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body{margin:0;background:#f8fafc;font-family:"Segoe UI", Arial, sans-serif;}
        .admin-layout{display:flex; min-height:100vh;}
        .admin-sidebar{
            width:260px;background:#0f172a;color:white;padding:28px 20px;
            position:fixed;top:0;left:0;height:100vh;
        }
        .admin-brand{font-size:28px;font-weight:700;margin-bottom:28px;}
        .admin-brand a{color:white;text-decoration:none;}
        .admin-user-box{
            background:rgba(255,255,255,0.08);border-radius:16px;padding:16px;margin-bottom:28px;
        }
        .admin-user-box h3{margin:0 0 6px 0;font-size:17px;}
        .admin-user-box p{margin:0;font-size:13px;color:#cbd5e1;word-break:break-word;}
        .admin-menu{display:flex;flex-direction:column;gap:10px;}
        .admin-menu a{
            color:white;text-decoration:none;padding:12px 14px;border-radius:12px;font-size:15px;
        }
        .admin-menu a:hover,.admin-menu a.active{
            background:#38bdf8;color:#062033;font-weight:600;
        }
        .admin-main{
            margin-left:260px;width:calc(100% - 260px);padding:34px;
        }
        .admin-header{margin-bottom:24px;}
        .admin-header h1{margin:0 0 8px 0;font-size:34px;}
        .admin-header p{margin:0;color:#6b7280;}
        .admin-card{
            background:white;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.08);
            padding:24px;overflow-x:auto;
        }
        .admin-table{width:100%;border-collapse:collapse;}
        .admin-table th,.admin-table td{
            padding:14px 12px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px;vertical-align:top;
        }
        .admin-table th{background:#f8fafc;}
        .admin-badge{
            padding:6px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-block;
        }
        .badge-green{background:#dcfce7;color:#166534;}
        .badge-red{background:#fee2e2;color:#991b1b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-gray{background:#e5e7eb;color:#374151;}
        .small-action{
            border:none;padding:8px 12px;border-radius:8px;cursor:pointer;font-size:13px;
        }
        .make-admin{background:#2563eb;color:white;}
        .make-user{background:#6b7280;color:white;}
        @media (max-width: 900px){
            .admin-sidebar{position:relative;width:100%;height:auto;}
            .admin-main{margin-left:0;width:100%;padding:20px;}
            .admin-layout{flex-direction:column;}
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
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php" class="active">Manage Users</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="manage_orders.php">Manage Orders</a>
            <a href="signature.php">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Users</h1>
            <p>Promote or demote users and review account status.</p>
        </div>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Action</th>
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
                            <td>
                                <?php if ($row['role'] === 'admin'): ?>
                                    <?php if ((int)$row['id'] !== (int)$adminUser['id']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="make_user" class="small-action make-user">Make User</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="admin-badge badge-gray">Current Admin</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="make_admin" class="small-action make-admin">Make Admin</button>
                                    </form>
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