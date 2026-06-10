<?php
session_start();
include "config/db.php";

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
        p.ai_status,
        p.contact_number,
        p.seller_email
    FROM cart
    INNER JOIN products p 
        ON cart.product_id = p.id
    WHERE cart.user_id = $userId
    ORDER BY cart.id DESC
");

$cartItems = [];
$totalAmount = 0;
$hasAvailableItems = false;
$hasOwnProduct = false;
$hasUnapprovedProduct = false;

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

        if ((int)$row['seller_user_id'] === $userId) {
            $hasOwnProduct = true;
            $row['checkout_blocked_reason'] = "You cannot purchase your own product.";
        } elseif (($row['ai_status'] ?? '') !== 'approved') {
            $hasUnapprovedProduct = true;
            $row['checkout_blocked_reason'] = "This product is not approved for checkout.";
        } elseif ($row['status'] === 'sold') {
            $row['checkout_blocked_reason'] = "This product is already sold.";
        } else {
            $row['checkout_blocked_reason'] = "";
            $totalAmount += ($finalPrice * (int)$row['quantity']);
            $hasAvailableItems = true;
        }

        $cartItems[] = $row;
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
        $error = "Please fill in all required fields.";
    } elseif (strlen($buyerName) < 3) {
        $error = "Buyer name must contain at least 3 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $buyerName)) {
        $error = "Buyer name should contain letters and spaces only.";
    } elseif (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!preg_match('/^[0-9]{10}$/', $buyerPhone)) {
        $error = "Phone number must contain exactly 10 digits.";
    } elseif (strlen($buyerMessage) > 500) {
        $error = "Message cannot exceed 500 characters.";
    } elseif ($hasOwnProduct) {
        $error = "Your cart contains your own product. Please remove it before checkout.";
    } elseif ($hasUnapprovedProduct) {
        $error = "Your cart contains products that are not approved for checkout.";
    } elseif (!$hasAvailableItems || $totalAmount <= 0) {
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
            if (
                $item['status'] === 'sold' ||
                ($item['ai_status'] ?? '') !== 'approved' ||
                (int)$item['seller_user_id'] === $userId
            ) {
                continue;
            }

            $productId = (int)$item['product_id'];
            $sellerUserId = (int)$item['seller_user_id'];
            $qty = max(1, (int)$item['quantity']);
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
                $insertedOrderIds[] = (int)$conn->insert_id;
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
                    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($totalAmount); ?>">
                    <input type="hidden" name="tax_amount" value="0">
                    <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($totalAmount); ?>">
                    <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transactionUuid); ?>">
                    <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($productCode); ?>">
                    <input type="hidden" name="product_service_charge" value="0">
                    <input type="hidden" name="product_delivery_charge" value="0">
                    <input type="hidden" name="success_url" value="<?php echo htmlspecialchars($successUrl); ?>">
                    <input type="hidden" name="failure_url" value="<?php echo htmlspecialchars($failureUrl); ?>">
                    <input type="hidden" name="signed_field_names" value="<?php echo htmlspecialchars($signedFieldNames); ?>">
                    <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>">
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
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($hasOwnProduct): ?>
            <div class="error-msg">Your cart contains your own product. Please remove it before checkout.</div>
        <?php endif; ?>

        <?php if ($hasUnapprovedProduct): ?>
            <div class="error-msg">Your cart contains products that are not approved for checkout.</div>
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

                        <?php if (empty($item['checkout_blocked_reason'])): ?>
                            <p class="meta">
                                Line Total:
                                Rs <?php echo number_format((float)$item['final_price'] * (int)$item['quantity'], 2); ?>
                            </p>
                        <?php endif; ?>

                        <p class="meta">Seller Email: <?php echo htmlspecialchars($item['seller_email']); ?></p>
                        <p class="meta">Contact Number: <?php echo htmlspecialchars($item['contact_number'] ?? 'Not provided'); ?></p>
                        <p class="meta">Status: <?php echo htmlspecialchars(ucfirst($item['status'])); ?></p>

                        <?php if (!empty($item['checkout_blocked_reason'])): ?>
                            <p style="color:#b91c1c; font-weight:600;">
                                <?php echo htmlspecialchars($item['checkout_blocked_reason']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <h3 style="margin-top:20px;">Total Payable: Rs <?php echo number_format($totalAmount, 2); ?></h3>

                <form method="POST" style="margin-top: 24px;" id="checkoutForm" novalidate>
                    <div class="form-group">
                        <label>Buyer Name</label>
                        <input
                            type="text"
                            name="buyer_name"
                            value="<?php echo htmlspecialchars($_POST['buyer_name'] ?? $user['name']); ?>"
                            minlength="3"
                            pattern="[A-Za-z\s]+"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Buyer Email</label>
                        <input
                            type="email"
                            name="buyer_email"
                            value="<?php echo htmlspecialchars($_POST['buyer_email'] ?? $user['email']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Buyer Phone</label>
                        <input
                            type="tel"
                            name="buyer_phone"
                            placeholder="98XXXXXXXX"
                            value="<?php echo htmlspecialchars($_POST['buyer_phone'] ?? ''); ?>"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label>Message to Seller (optional)</label>
                        <textarea
                            name="buyer_message"
                            maxlength="500"
                            placeholder="Any note for the seller..."
                        ><?php echo htmlspecialchars($_POST['buyer_message'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button
                            type="submit"
                            name="place_order"
                            class="btn btn-primary"
                            <?php echo (!$hasAvailableItems || $hasOwnProduct || $hasUnapprovedProduct) ? 'disabled style="opacity:0.6;cursor:not-allowed;"' : ''; ?>
                        >
                            Pay with eSewa
                        </button>
                        <a href="cart.php" class="btn btn-dark">Back to Cart</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
<script>
const checkoutForm = document.getElementById("checkoutForm");

if (checkoutForm) {
    checkoutForm.addEventListener("submit", function (e) {
        const name = checkoutForm.querySelector("[name='buyer_name']").value.trim();
        const email = checkoutForm.querySelector("[name='buyer_email']").value.trim();
        const phone = checkoutForm.querySelector("[name='buyer_phone']").value.trim();
        const message = checkoutForm.querySelector("[name='buyer_message']").value.trim();

        let error = "";

        if (!name || name.length < 3 || !/^[A-Za-z\s]+$/.test(name)) {
            error = "Please enter a valid buyer name.";
        } else if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            error = "Please enter a valid email address.";
        } else if (!/^[0-9]{10}$/.test(phone)) {
            error = "Phone number must contain exactly 10 digits.";
        } else if (message.length > 500) {
            error = "Message cannot exceed 500 characters.";
        }

        if (error) {
            e.preventDefault();

            if (typeof showToast === "function") {
                showToast(error, "error");
            } else {
                alert(error);
            }
        }
    });
}
</script>

</body>
</html>