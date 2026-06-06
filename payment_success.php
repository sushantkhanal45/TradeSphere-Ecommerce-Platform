<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT * FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$transactionUuid = $_GET['transaction_uuid'] ?? ($_SESSION['checkout_transaction_uuid'] ?? '');
$status = $_GET['status'] ?? 'success';
$refId = $_GET['refId'] ?? ("LOCAL-" . time());

if ($transactionUuid === '') {
    die("Invalid transaction.");
}

$safeTransactionUuid = $conn->real_escape_string($transactionUuid);
$safeRefId = $conn->real_escape_string($refId);

$orderRes = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.user_id AS product_owner_id
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.user_id = $userId
    AND o.transaction_uuid LIKE '$safeTransactionUuid%'
    ORDER BY o.id ASC
");

if (!$orderRes || $orderRes->num_rows === 0) {
    die("No orders found for this transaction.");
}

$paidOrders = [];

while ($order = $orderRes->fetch_assoc()) {
    $orderId = (int)$order['id'];
    $productId = (int)$order['product_id'];
    $sellerId = (int)$order['seller_user_id'];

    $conn->query("
        UPDATE orders
        SET payment_status = 'paid',
            order_status = 'confirmed',
            esewa_ref_id = '$safeRefId'
        WHERE id = $orderId
        AND user_id = $userId
    ");

    $conn->query("
        UPDATE products
        SET status = 'sold'
        WHERE id = $productId
    ");

    $actionData = json_encode([
        "action" => "payment_success",
        "user_id" => $userId,
        "order_id" => $orderId,
        "product_id" => $productId,
        "seller_user_id" => $sellerId,
        "transaction_uuid" => $order['transaction_uuid'],
        "esewa_ref_id" => $refId,
        "payment_status" => "paid",
        "order_status" => "confirmed",
        "amount" => $order['amount'],
        "timestamp" => date("Y-m-d H:i:s")
    ]);

    $signature = signData($actionData);

    if ($signature) {
        storeSignatureRecord(
            $conn,
            $userId,
            "payment_success",
            $orderId,
            $actionData,
            $signature
        );
    }

    $notificationMessage = "Payment received for " . $order['product_name'];
    $safeNotification = $conn->real_escape_string($notificationMessage);

    $conn->query("
        INSERT INTO notifications (user_id, order_id, message)
        VALUES ($sellerId, $orderId, '$safeNotification')
    ");

    $paidOrders[] = $orderId;
}

unset($_SESSION['checkout_order_ids']);
unset($_SESSION['checkout_transaction_uuid']);
unset($_SESSION['checkout_total_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="form-page">
    <div class="form-card">
        <h2>Payment Successful</h2>
        <p class="helper">Your order has been placed and payment has been recorded successfully.</p>

        <div class="success-msg">
            Payment completed successfully.
        </div>

        <p><strong>Transaction UUID:</strong> <?php echo htmlspecialchars($transactionUuid); ?></p>
        <p><strong>Reference ID:</strong> <?php echo htmlspecialchars($refId); ?></p>

        <div class="form-actions">
            <a href="profile.php#purchases" class="btn btn-primary">View My Purchases</a>
            <a href="products.php" class="btn btn-dark">Continue Shopping</a>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>