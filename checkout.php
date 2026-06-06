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
$error = "";

$cartItems = $conn->query("
    SELECT 
        c.*,
        p.name,
        p.price,
        p.image,
        p.user_id AS seller_user_id,
        p.status
    FROM cart c
    INNER JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $userId
");

if (!$cartItems || $cartItems->num_rows === 0) {
    header("Location: cart.php");
    exit();
}

$totalAmount = 0;
$items = [];

while ($row = $cartItems->fetch_assoc()) {
    if ($row['status'] === 'sold') {
        continue;
    }

    $row['line_total'] = (float)$row['price'] * (int)$row['quantity'];
    $totalAmount += $row['line_total'];
    $items[] = $row;
}

if (empty($items)) {
    die("No available items in cart.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $buyerName = trim($_POST['buyer_name']);
    $buyerEmail = trim($_POST['buyer_email']);
    $buyerPhone = trim($_POST['buyer_phone']);
    $buyerAddress = trim($_POST['buyer_address']);

    if ($buyerName === "" || $buyerEmail === "" || $buyerPhone === "" || $buyerAddress === "") {
        $error = "Please fill in all checkout details.";
    } else {
        $safeBuyerName = $conn->real_escape_string($buyerName);
        $safeBuyerEmail = $conn->real_escape_string($buyerEmail);
        $safeBuyerPhone = $conn->real_escape_string($buyerPhone);
        $safeBuyerAddress = $conn->real_escape_string($buyerAddress);

        $transactionUuid = "TS-" . time() . "-" . $userId;
        $insertedOrderIds = [];

        foreach ($items as $item) {
            $productId = (int)$item['product_id'];
            $sellerUserId = (int)$item['seller_user_id'];
            $qty = (int)$item['quantity'];
            $amount = (float)$item['line_total'];
            $itemTransactionUuid = $transactionUuid . "-" . $productId;

            $insertOrder = "
                INSERT INTO orders
                (
                    user_id,
                    seller_user_id,
                    product_id,
                    quantity,
                    amount,
                    buyer_name,
                    buyer_email,
                    buyer_phone,
                    buyer_address,
                    transaction_uuid,
                    payment_status,
                    order_status,
                    seller_delivery_status,
                    buyer_received
                )
                VALUES
                (
                    $userId,
                    $sellerUserId,
                    $productId,
                    $qty,
                    $amount,
                    '$safeBuyerName',
                    '$safeBuyerEmail',
                    '$safeBuyerPhone',
                    '$safeBuyerAddress',
                    '$itemTransactionUuid',
                    'pending',
                    'placed',
                    'pending',
                    0
                )
            ";

            if ($conn->query($insertOrder)) {
                $newOrderId = (int)$conn->insert_id;
                $insertedOrderIds[] = $newOrderId;

                $actionData = json_encode([
                    "action" => "order_created",
                    "user_id" => $userId,
                    "order_id" => $newOrderId,
                    "product_id" => $productId,
                    "seller_user_id" => $sellerUserId,
                    "quantity" => $qty,
                    "amount" => $amount,
                    "buyer_name" => $buyerName,
                    "buyer_email" => $buyerEmail,
                    "buyer_phone" => $buyerPhone,
                    "transaction_uuid" => $itemTransactionUuid,
                    "payment_status" => "pending",
                    "order_status" => "placed",
                    "timestamp" => date("Y-m-d H:i:s")
                ]);

                $signature = signData($actionData);

                if ($signature) {
                    storeSignatureRecord(
                        $conn,
                        $userId,
                        "order_created",
                        $newOrderId,
                        $actionData,
                        $signature
                    );
                }
            }
        }

        if (!empty($insertedOrderIds)) {
            $conn->query("DELETE FROM cart WHERE user_id=$userId");

            $_SESSION['checkout_order_ids'] = $insertedOrderIds;
            $_SESSION['checkout_transaction_uuid'] = $transactionUuid;
            $_SESSION['checkout_total_amount'] = $totalAmount;

            header("Location: payment_success.php?transaction_uuid=" . urlencode($transactionUuid) . "&status=success");
            exit();
        } else {
            $error = "Could not create order.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="form-page">
    <div class="form-card">
        <h2>Checkout</h2>
        <p class="helper">Review your order and enter buyer details.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div style="margin-bottom:20px;">
            <h3>Order Summary</h3>

            <?php foreach ($items as $item): ?>
                <div style="display:flex;align-items:center;gap:12px;margin:12px 0;padding:12px;border:1px solid #e5e7eb;border-radius:12px;">
                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" style="width:70px;height:70px;object-fit:cover;border-radius:10px;">
                    <div>
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                        <p style="margin:4px 0;">Qty: <?php echo (int)$item['quantity']; ?></p>
                        <p style="margin:4px 0;">Rs <?php echo number_format((float)$item['line_total'], 2); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <h3>Total: Rs <?php echo number_format((float)$totalAmount, 2); ?></h3>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="buyer_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="buyer_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="buyer_phone" required>
            </div>

            <div class="form-group">
                <label>Delivery Address</label>
                <textarea name="buyer_address" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Place Order</button>
            <a href="cart.php" class="btn btn-dark">Back to Cart</a>
        </form>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>
<script src="js/script.js"></script>
</body>
</html>