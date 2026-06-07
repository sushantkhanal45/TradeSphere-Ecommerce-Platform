<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

$transactionUuid = $_SESSION['checkout_transaction_uuid'] ?? '';
$orderIds = $_SESSION['checkout_order_ids'] ?? [];
$userEmail = $_SESSION['user'] ?? '';
$reason = "Payment failed or was cancelled.";

$userId = null;

if ($userEmail !== '') {
    $safeEmail = $conn->real_escape_string($userEmail);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$safeEmail' LIMIT 1");
    $user = $userRes ? $userRes->fetch_assoc() : null;

    if ($user) {
        $userId = (int)$user['id'];
    }
}

if (!empty($orderIds)) {
    foreach ($orderIds as $orderId) {
        $orderId = (int)$orderId;

        $orderRes = $conn->query("
            SELECT *
            FROM orders
            WHERE id = $orderId
            LIMIT 1
        ");

        $order = $orderRes ? $orderRes->fetch_assoc() : null;

        if (!$order) {
            continue;
        }

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
                raw_response
            )
            VALUES
            (
                $orderId,
                '" . $conn->real_escape_string($order['transaction_uuid']) . "',
                'failed',
                '" . $conn->real_escape_string($reason) . "'
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

        $existingSig = $conn->query("
            SELECT id
            FROM signatures
            WHERE action_type='payment_failure'
            AND related_id=$orderId
            LIMIT 1
        ");

        if (!$existingSig || $existingSig->num_rows === 0) {
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

<div class="page-wrap">
    <div class="container">
        <div class="form-card" style="max-width:720px;">
            <h2>Payment Failed</h2>
            <p class="helper">Your eSewa payment was not completed.</p>

            <div class="error-msg">
                Payment failed or was cancelled. Your order has not been confirmed.
            </div>

            <?php if ($transactionUuid !== ''): ?>
                <p><strong>Transaction UUID:</strong> <?php echo htmlspecialchars($transactionUuid); ?></p>
            <?php endif; ?>

            <div class="form-actions" style="justify-content:center; margin-top:20px;">
                <a href="cart.php" class="btn btn-primary">Back to Cart</a>
                <a href="products.php" class="btn btn-dark">Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>