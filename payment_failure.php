<?php
session_start();
include "config/db.php";

$orderIds = $_SESSION['checkout_order_ids'] ?? [];
$transactionUuid = $_SESSION['checkout_transaction_uuid'] ?? '';

if (!empty($orderIds)) {
    foreach ($orderIds as $orderId) {
        $orderId = (int)$orderId;

        $conn->query("
            UPDATE orders 
            SET payment_status='failed', order_status='failed'
            WHERE id=$orderId
        ");

        $conn->query("
            INSERT INTO payment_logs (order_id, transaction_uuid, status, raw_response)
            VALUES (
                $orderId,
                '" . $conn->real_escape_string($transactionUuid) . "',
                'failed',
                'User cancelled or payment failed'
            )
        ");
    }
}

unset($_SESSION['checkout_order_ids'], $_SESSION['checkout_transaction_uuid'], $_SESSION['checkout_total_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <div class="form-card" style="max-width: 760px;">
            <h2>Payment Failed</h2>
            <div class="error-msg">
                Your payment was not completed. No product was marked sold or removed.
            </div>

            <div class="form-actions" style="justify-content:center; margin-top:20px;">
                <a href="checkout.php" class="btn btn-primary">Try Again</a>
                <a href="cart.php" class="btn btn-dark">Back to Cart</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>
</body>
</html>