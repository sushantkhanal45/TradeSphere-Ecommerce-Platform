<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

$userEmail = $_SESSION['user'] ?? '';
$userId = null;

if ($userEmail !== '') {
    $safeEmail = $conn->real_escape_string($userEmail);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$safeEmail' LIMIT 1");
    $user = $userRes ? $userRes->fetch_assoc() : null;

    if ($user) {
        $userId = (int)$user['id'];
    }
}

$transactionUuid = $_GET['transaction_uuid'] ?? ($_SESSION['checkout_transaction_uuid'] ?? '');
$reason = $_GET['reason'] ?? 'User cancelled or payment failed';

if ($transactionUuid !== '') {
    $safeTransactionUuid = $conn->real_escape_string($transactionUuid);
    $safeReason = $conn->real_escape_string($reason);

    $orderRes = $conn->query("
        SELECT *
        FROM orders
        WHERE transaction_uuid LIKE '$safeTransactionUuid%'
    ");

    if ($orderRes && $orderRes->num_rows > 0) {
        while ($order = $orderRes->fetch_assoc()) {
            $orderId = (int)$order['id'];

            $conn->query("
                UPDATE orders
                SET payment_status = 'failed',
                    order_status = 'failed'
                WHERE id = $orderId
            ");

            $conn->query("
                INSERT INTO payment_logs
                (
                    order_id,
                    transaction_uuid,
                    status,
                    message
                )
                VALUES
                (
                    $orderId,
                    '" . $conn->real_escape_string($order['transaction_uuid']) . "',
                    'failed',
                    '$safeReason'
                )
            ");

            $actionData = json_encode([
                "action" => "payment_failure",
                "user_id" => $userId,
                "order_id" => $orderId,
                "product_id" => (int)$order['product_id'],
                "seller_user_id" => (int)$order['seller_user_id'],
                "transaction_uuid" => $order['transaction_uuid'],
                "payment_status" => "failed",
                "order_status" => "failed",
                "reason" => $reason,
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);

            if ($signature) {
                storeSignatureRecord(
                    $conn,
                    $userId,
                    "payment_failure",
                    $orderId,
                    $actionData,
                    $signature
                );
            }
        }
    }
}

unset($_SESSION['checkout_order_ids']);
unset($_SESSION['checkout_transaction_uuid']);
unset($_SESSION['checkout_total_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="form-page">
    <div class="form-card">
        <h2>Payment Failed</h2>
        <p class="helper">Your payment could not be completed.</p>

        <div class="error-msg">
            Payment failed or was cancelled.
        </div>

        <?php if ($transactionUuid !== ''): ?>
            <p><strong>Transaction UUID:</strong> <?php echo htmlspecialchars($transactionUuid); ?></p>
        <?php endif; ?>

        <p><strong>Reason:</strong> <?php echo htmlspecialchars($reason); ?></p>

        <div class="form-actions">
            <a href="cart.php" class="btn btn-primary">Back to Cart</a>
            <a href="products.php" class="btn btn-dark">Continue Shopping</a>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>