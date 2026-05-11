<?php
session_start();
include "config/db.php";

$encodedData = $_GET['data'] ?? '';

if ($encodedData === '') {
    die("Invalid payment response.");
}

$decodedJson = base64_decode($encodedData, true);
if ($decodedJson === false) {
    die("Could not decode payment response.");
}

$response = json_decode($decodedJson, true);
if (!$response) {
    die("Invalid payment response format.");
}

$transactionUuid = $response['transaction_uuid'] ?? '';
$status = $response['status'] ?? '';
$refId = $response['transaction_code'] ?? '';

if ($transactionUuid === '') {
    die("Transaction ID missing.");
}

if ($status !== 'COMPLETE') {
    die("Payment not completed.");
}

$orderIds = $_SESSION['checkout_order_ids'] ?? [];
$userEmail = $_SESSION['user'] ?? '';

if ($userEmail === '') {
    die("User session missing.");
}

$userRes = $conn->query("SELECT id FROM users WHERE email='" . $conn->real_escape_string($userEmail) . "' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

/* If page is refreshed and session order IDs are missing, recover orders from payment_logs */
if (empty($orderIds)) {
    $logRes = $conn->query("
        SELECT order_id
        FROM payment_logs
        WHERE transaction_uuid = '" . $conn->real_escape_string($transactionUuid) . "'
    ");

    if ($logRes) {
        while ($logRow = $logRes->fetch_assoc()) {
            $orderIds[] = (int)$logRow['order_id'];
        }
    }
}

$receiptItems = [];
$grandTotal = 0;
$buyerName = '';
$buyerEmail = '';
$buyerPhone = '';
$receiptDate = date("Y-m-d H:i:s");

if (!empty($orderIds)) {
    foreach ($orderIds as $orderId) {
        $orderId = (int)$orderId;

        $orderRes = $conn->query("
            SELECT 
                o.*,
                p.name AS product_name,
                p.price AS product_price,
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

        $orderRow = $orderRes ? $orderRes->fetch_assoc() : null;

        if (!$orderRow) {
            continue;
        }

        $productId = (int)$orderRow['product_id'];

        $conn->query("
            UPDATE orders
            SET payment_status='paid',
                order_status='confirmed',
                esewa_ref_id='" . $conn->real_escape_string($refId) . "'
            WHERE id=$orderId AND user_id=$userId
        ");

        $existingLog = $conn->query("
            SELECT id
            FROM payment_logs
            WHERE order_id = $orderId
            AND transaction_uuid = '" . $conn->real_escape_string($transactionUuid) . "'
            LIMIT 1
        ");

        if (!$existingLog || $existingLog->num_rows === 0) {
            $conn->query("
                INSERT INTO payment_logs (order_id, transaction_uuid, status, raw_response)
                VALUES (
                    $orderId,
                    '" . $conn->real_escape_string($transactionUuid) . "',
                    'paid',
                    '" . $conn->real_escape_string($decodedJson) . "'
                )
            ");
        }

        $conn->query("
            DELETE FROM cart
            WHERE user_id=$userId AND product_id=$productId
        ");

        $updatedRes = $conn->query("
            SELECT 
                o.*,
                p.name AS product_name,
                p.price AS product_price,
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

        $updatedRow = $updatedRes ? $updatedRes->fetch_assoc() : null;

        if ($updatedRow) {
            $receiptItems[] = $updatedRow;
            $grandTotal += (float)$updatedRow['amount'];

            if ($buyerName === '') {
                $buyerName = $updatedRow['buyer_name'];
                $buyerEmail = $updatedRow['buyer_email'];
                $buyerPhone = $updatedRow['buyer_phone'];
                $receiptDate = $updatedRow['created_at'];
            }
        }
    }
}

$receiptNumber = "TS-" . strtoupper(substr(md5($transactionUuid), 0, 10));

/*
Do not unset immediately, because refreshing payment_success.php can make buyer details disappear.
You can clear this later if needed.
*/
// unset($_SESSION['checkout_order_ids'], $_SESSION['checkout_transaction_uuid'], $_SESSION['checkout_total_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .receipt-card{
            max-width: 900px;
            margin: 24px auto 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 28px;
        }

        .receipt-head{
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 18px;
        }

        .receipt-head h3{
            margin-bottom: 8px;
        }

        .receipt-meta p{
            margin: 6px 0;
            color: #4b5563;
        }

        .receipt-table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .receipt-table th,
        .receipt-table td{
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        .receipt-table th{
            background: #f8fafc;
        }

        .receipt-total{
            margin-top: 20px;
            text-align: right;
        }

        .receipt-total h3{
            margin-bottom: 8px;
        }

        .receipt-note{
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            line-height: 1.6;
        }

        @media (max-width: 768px){
            .receipt-table{
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <div class="form-card" style="max-width: 900px;">
            <h2>Payment Successful</h2>
            <p class="helper">Your payment has been completed and your digital bill is ready below.</p>

            <div class="success-msg">
                Payment completed successfully. Purchased item(s) have been removed from your cart.
            </div>

            <div class="receipt-card">
                <div class="receipt-head">
                    <div>
                        <h3>TradeSphere Digital Bill</h3>
                        <div class="receipt-meta">
                            <p><strong>Receipt No:</strong> <?php echo htmlspecialchars($receiptNumber); ?></p>
                            <p><strong>Transaction UUID:</strong> <?php echo htmlspecialchars($transactionUuid); ?></p>
                            <p><strong>eSewa Ref ID:</strong> <?php echo htmlspecialchars($refId); ?></p>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($receiptDate); ?></p>
                        </div>
                    </div>

                    <div>
                        <h3>Buyer Details</h3>
                        <div class="receipt-meta">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($buyerName); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($buyerEmail); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($buyerPhone); ?></p>
                            <p><strong>Payment Method:</strong> eSewa</p>
                            <p><strong>Payment Status:</strong> Paid</p>
                        </div>
                    </div>
                </div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>UID</th>
                            <th>Product</th>
                            <th>Seller Email</th>
                            <th>Seller Contact</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receiptItems)): ?>
                            <?php foreach ($receiptItems as $item): ?>
                                <tr>
                                    <td><?php echo (int)$item['product_id']; ?></td>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['seller_email']); ?></td>
                                    <td><?php echo htmlspecialchars($item['contact_number'] ?? 'Not provided'); ?></td>
                                    <td>Rs <?php echo number_format((float)$item['product_price'], 2); ?></td>
                                    <td><?php echo (int)$item['quantity']; ?></td>
                                    <td>Rs <?php echo number_format((float)$item['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No receipt items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="receipt-total">
                    <h3>Total Amount: Rs <?php echo number_format($grandTotal, 2); ?></h3>
                    <p class="meta">This bill confirms payment and order recording only.</p>
                </div>

                <div class="receipt-note">
                    <strong>Important:</strong> The product remains active in the marketplace until the seller or admin manually marks it sold. Seller contact information is shown above to help direct communication.
                </div>
            </div>

            <div class="form-actions" style="justify-content:center; margin-top:20px;">
                <?php if (!empty($receiptItems)): ?>
                    <a href="generate_bill.php?order_id=<?php echo (int)$receiptItems[0]['id']; ?>" class="btn btn-primary">View Digital Bill</a>
                <?php endif; ?>
                <a href="profile.php#purchases" class="btn btn-dark">View My Purchases</a>
                <a href="products.php" class="btn btn-dark">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>