<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id, name, email FROM users WHERE email='" . $conn->real_escape_string($userEmail) . "' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    die("Invalid order ID.");
}

$orderRes = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image,
        p.seller_email,
        p.contact_number,
        p.city,
        p.product_condition
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.id = $orderId
    AND o.user_id = $userId
    LIMIT 1
");

$order = $orderRes ? $orderRes->fetch_assoc() : null;

if (!$order) {
    die("Bill not found.");
}

$receiptNumber = "TS-" . strtoupper(substr(md5($order['transaction_uuid']), 0, 10));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Bill - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .bill-wrap{
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 30px;
        }

        .bill-head{
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .bill-head h2{
            margin-bottom: 8px;
        }

        .bill-meta p{
            margin: 6px 0;
            color: #4b5563;
        }

        .bill-product{
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 22px;
            margin-top: 20px;
            align-items: start;
        }

        .bill-product img{
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 16px;
            background: #e5e7eb;
        }

        .bill-table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .bill-table th,
        .bill-table td{
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .bill-table th{
            background: #f8fafc;
        }

        .bill-total{
            margin-top: 20px;
            text-align: right;
        }

        .bill-note{
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            line-height: 1.6;
        }

        .bill-actions{
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        @media print{
            .navbar,
            footer,
            .bill-actions{
                display: none !important;
            }

            body{
                background: white;
            }

            .bill-wrap{
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                border-radius: 0;
                padding: 0;
            }
        }

        @media (max-width: 768px){
            .bill-product{
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <div class="bill-wrap">
            <div class="bill-head">
                <div>
                    <h2>TradeSphere Digital Bill</h2>
                    <div class="bill-meta">
                        <p><strong>Receipt No:</strong> <?php echo htmlspecialchars($receiptNumber); ?></p>
                        <p><strong>Order ID:</strong> <?php echo (int)$order['id']; ?></p>
                        <p><strong>Transaction UUID:</strong> <?php echo htmlspecialchars($order['transaction_uuid']); ?></p>
                        <p><strong>eSewa Ref ID:</strong> <?php echo htmlspecialchars($order['esewa_ref_id'] ?? 'Not available'); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                    </div>
                </div>

                <div>
                    <h2>Buyer Details</h2>
                    <div class="bill-meta">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['buyer_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['buyer_phone']); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="bill-product">
                <img src="uploads/<?php echo htmlspecialchars($order['product_image']); ?>" alt="Product">
                <div>
                    <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                    <p class="meta">City: <?php echo htmlspecialchars($order['city']); ?></p>
                    <p class="meta">Condition: <?php echo htmlspecialchars($order['product_condition']); ?></p>
                    <p class="meta">Seller Email: <?php echo htmlspecialchars($order['seller_email']); ?></p>
                    <p class="meta">Seller Contact: <?php echo htmlspecialchars($order['contact_number'] ?? 'Not provided'); ?></p>
                </div>
            </div>

            <table class="bill-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Qty</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo (int)$order['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td>Rs <?php echo number_format((float)$order['amount'] / max((int)$order['quantity'], 1), 2); ?></td>
                        <td><?php echo (int)$order['quantity']; ?></td>
                        <td>Rs <?php echo number_format((float)$order['amount'], 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="bill-total">
                <h3>Total Amount: Rs <?php echo number_format((float)$order['amount'], 2); ?></h3>
            </div>

            <?php if (!empty($order['buyer_message'])): ?>
                <div class="bill-note">
                    <strong>Buyer Message:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['buyer_message'])); ?>
                </div>
            <?php endif; ?>

            <div class="bill-note">
                <strong>Important:</strong> This bill confirms payment and order recording only. The product remains active until the seller or admin manually marks it sold.
            </div>

            <div class="bill-actions">
                <button onclick="window.print()" class="btn btn-primary">Download / Print PDF</button>
                <a href="profile.php#purchases" class="btn btn-dark">Back to My Purchases</a>
                <a href="products.php" class="btn btn-dark">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>
</body>
</html>