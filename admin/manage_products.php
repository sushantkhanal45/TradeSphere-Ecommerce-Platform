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

if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $imageName = $product['image'];

        if ($conn->query("DELETE FROM products WHERE id=$productId")) {
            if (!empty($imageName) && file_exists("../uploads/" . $imageName)) {
                @unlink("../uploads/" . $imageName);
            }
            $success = "Product deleted successfully.";
        } else {
            $error = "Could not delete product.";
        }
    } else {
        $error = "Product not found.";
    }
}

$products = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - TradeSphere Admin</title>
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
        .badge-red{background:#fee2e2;color:#991b1b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .delete-btn{
            background:#dc2626;color:white;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;
        }
        .delete-btn:hover{background:#b91c1c;}
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
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_products.php" class="active">Manage Products</a>
            <a href="signatures.php">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Products</h1>
            <p>Review marketplace products and remove listings when needed.</p>
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
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Price</th>
                    <th>City</th>
                    <th>Seller</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_condition']); ?></td>
                            <td>Rs <?php echo htmlspecialchars($row['price']); ?></td>
                            <td><?php echo htmlspecialchars($row['city']); ?></td>
                            <td><?php echo htmlspecialchars($row['seller_email']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'sold'): ?>
                                    <span class="admin-badge badge-red">Sold</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-blue">Available</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_product" class="delete-btn">Delete</button>
                                </form>
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