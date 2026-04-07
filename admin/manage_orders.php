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
    } else {
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
        p.product_condition
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    ORDER BY o.created_at DESC, o.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - TradeSphere Admin</title>
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
            white-space:nowrap;
        }

        .badge-green{background:#dcfce7;color:#166534;}
        .badge-red{background:#fee2e2;color:#991b1b;}
        .badge-blue{background:#dbeafe;color:#1d4ed8;}
        .badge-gray{background:#e5e7eb;color:#374151;}
        .badge-yellow{background:#fef3c7;color:#92400e;}

        .admin-form-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:8px;
            min-width:180px;
        }

        .admin-form-grid select{
            padding:8px 10px;
            border:1px solid #d1d5db;
            border-radius:10px;
            background:white;
        }

        .update-btn{
            border:none;
            background:#2563eb;
            color:white;
            padding:9px 12px;
            border-radius:10px;
            cursor:pointer;
            font-weight:600;
        }

        .update-btn:hover{
            background:#1d4ed8;
        }

        .thumb{
            width:56px;
            height:56px;
            object-fit:cover;
            border-radius:12px;
            border:1px solid #e5e7eb;
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
        <div class="admin-brand"><a href="dashboard.php">TradeSphere</a></div>

        <div class="admin-user-box">
            <h3><?php echo htmlspecialchars($adminUser['name']); ?></h3>
            <p><?php echo htmlspecialchars($adminUser['email']); ?></p>
        </div>

        <nav class="admin-menu">
            <a href="dashboard.php">Dashboard</a>
            <a href="manage_users.php">Manage Users</a>
            <a href="manage_products.php">Manage Products</a>
            <a href="manage_orders.php" class="active">Manage Orders</a>
            <a href="signature.php">RSA Signatures</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Orders</h1>
            <p>Monitor and control all buyer-seller order activity.</p>
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
                    <th>Image</th>
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

                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while ($row = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>

                            <td>
                                <?php if (!empty($row['product_image'])): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($row['product_image']); ?>" class="thumb" alt="Product">
                                <?php else: ?>
                                    <span class="admin-badge badge-gray">No Image</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['product_name'] ?? 'Deleted Product'); ?></strong><br>
                                <small><?php echo htmlspecialchars($row['city'] ?? ''); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['buyer_name']); ?><br>
                                <small><?php echo htmlspecialchars($row['buyer_email']); ?></small><br>
                                <small><?php echo htmlspecialchars($row['buyer_phone']); ?></small>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['seller_email']); ?>
                            </td>

                            <td><?php echo (int)$row['quantity']; ?></td>
                            <td>Rs <?php echo number_format((float)$row['amount'], 2); ?></td>

                            <td>
                                <?php if ($row['payment_status'] === 'paid'): ?>
                                    <span class="admin-badge badge-green">Paid</span>
                                <?php elseif ($row['payment_status'] === 'failed'): ?>
                                    <span class="admin-badge badge-red">Failed</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-yellow">Pending</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="admin-badge badge-blue">
                                    <?php echo htmlspecialchars(ucfirst($row['order_status'])); ?>
                                </span>
                            </td>

                            <td>
                                <span class="admin-badge badge-gray">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['seller_delivery_status']))); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ((int)$row['buyer_received'] === 1): ?>
                                    <span class="admin-badge badge-green">Yes</span>
                                <?php else: ?>
                                    <span class="admin-badge badge-red">No</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['created_at']); ?><br>

                                <?php if (!empty($row['delivered_at'])): ?>
                                    <small><strong>Delivered:</strong> <?php echo htmlspecialchars($row['delivered_at']); ?></small><br>
                                <?php endif; ?>

                                <?php if (!empty($row['buyer_received_at'])): ?>
                                    <small><strong>Confirmed:</strong> <?php echo htmlspecialchars($row['buyer_received_at']); ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="POST" class="admin-form-grid">
                                    <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">

                                    <select name="payment_status">
                                        <option value="pending" <?php echo ($row['payment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="paid" <?php echo ($row['payment_status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="failed" <?php echo ($row['payment_status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                    </select>

                                    <select name="order_status">
                                        <option value="pending" <?php echo ($row['order_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo ($row['order_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="completed" <?php echo ($row['order_status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo ($row['order_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>

                                    <select name="seller_delivery_status">
                                        <option value="pending" <?php echo ($row['seller_delivery_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo ($row['seller_delivery_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="out_for_delivery" <?php echo ($row['seller_delivery_status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                        <option value="delivered" <?php echo ($row['seller_delivery_status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                    </select>

                                    <select name="buyer_received">
                                        <option value="0" <?php echo ((int)$row['buyer_received'] === 0) ? 'selected' : ''; ?>>Buyer Not Confirmed</option>
                                        <option value="1" <?php echo ((int)$row['buyer_received'] === 1) ? 'selected' : ''; ?>>Buyer Confirmed</option>
                                    </select>

                                    <button type="submit" name="update_order_admin" class="update-btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

</body>
</html>