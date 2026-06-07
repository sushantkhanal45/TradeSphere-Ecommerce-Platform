<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
$safeUserEmail = $conn->real_escape_string($userEmail);

$userRes = $conn->query("SELECT id, name, email FROM users WHERE email='$safeUserEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

function getFinalCheckoutPrice($conn, $productId, $buyerId, $sellerUserId, $originalPrice) {
    $productId = (int)$productId;
    $buyerId = (int)$buyerId;
    $sellerUserId = (int)$sellerUserId;

    $offerRes = $conn->query("
        SELECT offer_amount
        FROM product_offers
        WHERE product_id = $productId
        AND buyer_id = $buyerId
        AND seller_id = $sellerUserId
        AND status = 'accepted'
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($offerRes && $offerRes->num_rows > 0) {
        $offer = $offerRes->fetch_assoc();
        return (float)$offer['offer_amount'];
    }

    return (float)$originalPrice;
}

$cartQuery = $conn->query("
    SELECT 
        cart.id AS cart_id,
        cart.product_id,
        cart.quantity,
        p.user_id AS seller_user_id,
        p.name,
        p.price,
        p.city,
        p.image,
        p.status,
        p.contact_number,
        p.seller_email
    FROM cart
    INNER JOIN products p ON cart.product_id = p.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$cartItems = [];
$totalAmount = 0;
$hasAvailableItems = false;

if ($cartQuery) {
    while ($row = $cartQuery->fetch_assoc()) {
        $finalPrice = getFinalCheckoutPrice(
            $conn,
            $row['product_id'],
            $userId,
            $row['seller_user_id'],
            $row['price']
        );

        $row['final_price'] = $finalPrice;
        $row['has_accepted_offer'] = ((float)$finalPrice !== (float)$row['price']);

        $cartItems[] = $row;

        if ($row['status'] !== 'sold') {
            $totalAmount += ($finalPrice * (int)$row['quantity']);
            $hasAvailableItems = true;
        }
    }
}

if (!$cartItems || count($cartItems) === 0) {
    die("Your cart is empty.");
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $buyerName = trim($_POST['buyer_name'] ?? '');
    $buyerEmail = trim($_POST['buyer_email'] ?? '');
    $buyerPhone = trim($_POST['buyer_phone'] ?? '');
    $buyerMessage = trim($_POST['buyer_message'] ?? '');

    if ($buyerName === '' || $buyerEmail === '' || $buyerPhone === '') {
        $error = "Please fill in buyer name, email, and phone number.";
    } elseif (!$hasAvailableItems) {
        $error = "There are no available items in your cart for checkout.";
    } else {
        $transactionUuid = uniqid("ts_");
        $productCode = "EPAYTEST";
        $secretKey = "8gBm/:&EnhH.1/q";

        $safeBuyerName = $conn->real_escape_string($buyerName);
        $safeBuyerEmail = $conn->real_escape_string($buyerEmail);
        $safeBuyerPhone = $conn->real_escape_string($buyerPhone);
        $safeBuyerMessage = $conn->real_escape_string($buyerMessage);

        $insertedOrderIds = [];

        foreach ($cartItems as $item) {
            if ($item['status'] === 'sold') {
                continue;
            }

            $productId = (int)$item['product_id'];
            $sellerUserId = (int)$item['seller_user_id'];
            $qty = (int)$item['quantity'];
            $finalPrice = getFinalCheckoutPrice($conn, $productId, $userId, $sellerUserId, $item['price']);
            $amount = $finalPrice * $qty;

            $itemTransactionUuid = $transactionUuid . "_p" . $productId;

            $insertOrder = "
                INSERT INTO orders (
                    user_id,
                    product_id,
                    seller_user_id,
                    buyer_name,
                    buyer_email,
                    buyer_phone,
                    buyer_message,
                    amount,
                    quantity,
                    transaction_uuid,
                    payment_method,
                    payment_status,
                    order_status
                ) VALUES (
                    $userId,
                    $productId,
                    $sellerUserId,
                    '$safeBuyerName',
                    '$safeBuyerEmail',
                    '$safeBuyerPhone',
                    '$safeBuyerMessage',
                    '$amount',
                    '$qty',
                    '$itemTransactionUuid',
                    'eSewa',
                    'pending',
                    'placed'
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
                    "buyer_name" => $buyerName,
                    "buyer_email" => $buyerEmail,
                    "buyer_phone" => $buyerPhone,
                    "amount" => $amount,
                    "quantity" => $qty,
                    "transaction_uuid" => $itemTransactionUuid,
                    "payment_method" => "eSewa",
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

        if (count($insertedOrderIds) === 0) {
            $error = "No valid available products found for checkout.";
        } else {
            $_SESSION['checkout_order_ids'] = $insertedOrderIds;
            $_SESSION['checkout_transaction_uuid'] = $transactionUuid;
            $_SESSION['checkout_total_amount'] = $totalAmount;

            $message = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
            $hash = hash_hmac('sha256', $message, $secretKey, true);
            $signature = base64_encode($hash);
            $signedFieldNames = "total_amount,transaction_uuid,product_code";

            $successUrl = "http://localhost/TradeSphere/payment_success.php";
            $failureUrl = "http://localhost/TradeSphere/payment_failure.php";
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Redirecting to eSewa</title>
            </head>
            <body>
                <p style="text-align:center; margin-top:40px;">Redirecting to eSewa...</p>

                <form id="esewaForm" action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">
                    <input type="hidden" name="amount" value="<?php echo $totalAmount; ?>">
                    <input type="hidden" name="tax_amount" value="0">
                    <input type="hidden" name="total_amount" value="<?php echo $totalAmount; ?>">
                    <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transactionUuid); ?>">
                    <input type="hidden" name="product_code" value="<?php echo $productCode; ?>">
                    <input type="hidden" name="product_service_charge" value="0">
                    <input type="hidden" name="product_delivery_charge" value="0">
                    <input type="hidden" name="success_url" value="<?php echo $successUrl; ?>">
                    <input type="hidden" name="failure_url" value="<?php echo $failureUrl; ?>">
                    <input type="hidden" name="signed_field_names" value="<?php echo $signedFieldNames; ?>">
                    <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                </form>

                <script>
                    document.getElementById("esewaForm").submit();
                </script>
            </body>
            </html>
            <?php
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Checkout</h1>
        <p class="section-subtitle">Confirm your details and proceed to eSewa. Accepted offers are applied automatically.</p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="detail-card" style="grid-template-columns: 1fr; max-width: 980px; margin: 0 auto;">
            <div class="detail-content">
                <h2 style="margin-bottom: 18px;">Cart Summary</h2>

                <?php foreach ($cartItems as $item): ?>
                    <div style="padding:14px 0; border-bottom:1px solid #e5e7eb;">
                        <p><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>

                        <?php if (!empty($item['has_accepted_offer'])): ?>
                            <p class="meta">
                                Original Price:
                                <span style="text-decoration:line-through;">
                                    Rs <?php echo number_format((float)$item['price'], 2); ?>
                                </span>
                            </p>
                            <p class="meta" style="color:#16a34a; font-weight:700;">
                                Accepted Offer Price: Rs <?php echo number_format((float)$item['final_price'], 2); ?>
                            </p>
                        <?php else: ?>
                            <p class="meta">Price: Rs <?php echo number_format((float)$item['price'], 2); ?></p>
                        <?php endif; ?>

                        <p class="meta">Quantity: <?php echo (int)$item['quantity']; ?></p>
                        <p class="meta">
                            Line Total:
                            Rs <?php echo number_format((float)$item['final_price'] * (int)$item['quantity'], 2); ?>
                        </p>
                        <p class="meta">Seller Email: <?php echo htmlspecialchars($item['seller_email']); ?></p>
                        <p class="meta">Contact Number: <?php echo htmlspecialchars($item['contact_number'] ?? 'Not provided'); ?></p>
                        <p class="meta">Status: <?php echo htmlspecialchars(ucfirst($item['status'])); ?></p>

                        <?php if ($item['status'] === 'sold'): ?>
                            <p style="color:#b91c1c; font-weight:600;">This item is sold and will not be included in payment.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <h3 style="margin-top:20px;">Total Payable: Rs <?php echo number_format($totalAmount, 2); ?></h3>

                <form method="POST" style="margin-top: 24px;">
                    <div class="form-group">
                        <label>Buyer Name</label>
                        <input type="text" name="buyer_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Buyer Email</label>
                        <input type="email" name="buyer_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Buyer Phone</label>
                        <input type="text" name="buyer_phone" placeholder="Enter your contact number" required>
                    </div>

                    <div class="form-group">
                        <label>Message to Seller (optional)</label>
                        <textarea name="buyer_message" placeholder="Any note for the seller..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="place_order" class="btn btn-primary">Pay with eSewa</button>
                        <a href="cart.php" class="btn btn-dark">Back to Cart</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

</body>
</html>