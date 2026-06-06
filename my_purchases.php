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
$success = "";
$error = "";
$openRatingModalOrderId = 0;

/* Buyer confirms received */
if (isset($_POST['confirm_received'])) {
    $orderId = (int)$_POST['order_id'];

    $checkOrder = $conn->query("
        SELECT o.*, p.name AS product_name
        FROM orders o
        INNER JOIN products p ON o.product_id = p.id
        WHERE o.id = $orderId
        AND o.user_id = $userId
        LIMIT 1
    ");

    $orderData = $checkOrder ? $checkOrder->fetch_assoc() : null;

    if (!$orderData) {
        $error = "Order not found or access denied.";
    } elseif ($orderData['seller_delivery_status'] !== 'delivered') {
        $error = "You can confirm received only after seller marks it as delivered.";
    } elseif ((int)$orderData['buyer_received'] === 1) {
        $error = "You have already confirmed this order as received.";
    } else {
        if ($conn->query("
            UPDATE orders
            SET buyer_received = 1,
                buyer_received_at = NOW(),
                order_status = 'completed'
            WHERE id = $orderId
            AND user_id = $userId
        ")) {
            $actionData = json_encode([
                "user_id" => $userId,
                "order_id" => $orderId,
                "product_id" => $orderData['product_id'],
                "product_name" => $orderData['product_name'],
                "action" => "buyer_confirmed_received",
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);
            if ($signature) {
                storeSignatureRecord($conn, $userId, "buyer_confirmed_received", $orderId, $actionData, $signature);
            }

            $sellerId = (int)$orderData['seller_user_id'];
            $notificationMessage = "Buyer confirmed receiving your product: " . $orderData['product_name'];

            $conn->query("
                INSERT INTO notifications (user_id, order_id, message)
                VALUES ($sellerId, $orderId, '" . $conn->real_escape_string($notificationMessage) . "')
            ");

            $success = "Order marked as received successfully.";
            $openRatingModalOrderId = $orderId;
        } else {
            $error = "Could not confirm receipt for this order.";
        }
    }
}

/* Ratings already submitted */
$myRatings = [];
$ratingRes = $conn->query("
    SELECT order_id, rating, review_text
    FROM product_ratings
    WHERE buyer_user_id = $userId
");

if ($ratingRes) {
    while ($r = $ratingRes->fetch_assoc()) {
        $myRatings[(int)$r['order_id']] = $r;
    }
}

$myPurchases = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image,
        p.seller_email,
        p.contact_number,
        p.average_rating,
        p.rating_count
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.user_id = $userId
    AND o.payment_status = 'paid'
    ORDER BY o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Purchases - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-row{margin-bottom:22px;}
        .purchase-badge,.status-note{display:inline-block;margin-top:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;}
        .purchase-badge{background:#dcfce7;color:#166534;}
        .status-note{background:#eff6ff;color:#1d4ed8;}
        .rating-box{margin-top:14px;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fafafa;}
        .rating-stars-line{color:#f59e0b;font-weight:700;margin-bottom:6px;}
        .rating-modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.65);display:none;align-items:center;justify-content:center;z-index:3000;padding:20px;}
        .rating-modal-overlay.show{display:flex;}
        .rating-modal{width:100%;max-width:430px;background:white;border-radius:22px;padding:28px;position:relative;box-shadow:0 25px 60px rgba(0,0,0,.28);}
        .rating-modal h2{margin-bottom:8px;text-align:center;}
        .rating-modal-product{text-align:center;color:#6b7280;margin-bottom:18px;}
        .rating-modal-close{position:absolute;top:12px;right:14px;border:none;background:#f3f4f6;width:34px;height:34px;border-radius:50%;font-size:22px;cursor:pointer;}
        .star-select{display:flex;justify-content:center;gap:8px;margin-bottom:18px;}
        .star-select button{border:none;background:transparent;font-size:36px;color:#d1d5db;cursor:pointer;transition:.2s ease;}
        .star-select button.active,.star-select button:hover{color:#f59e0b;transform:scale(1.08);}
        .rating-modal textarea{width:100%;padding:12px;border-radius:12px;border:1px solid #d1d5db;resize:vertical;}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
<div class="container">

    <div class="back-row">
        <a href="profile.php#purchases" class="small-btn dark">← Back to Profile</a>
    </div>

    <?php if ($success): ?><div class="success-msg"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

    <div class="profile-section-card">
        <h2 class="section-title" style="text-align:left;margin-bottom:20px;">All My Purchases</h2>

        <?php if ($myPurchases && $myPurchases->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $myPurchases->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Purchased Product">
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>
                            <p class="meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>
                            <p class="meta"><strong>Phone:</strong> <?php echo htmlspecialchars($row['contact_number'] ?? 'Not provided'); ?></p>

                            <?php if ((int)$row['buyer_received'] === 1): ?>
                                <span class="purchase-badge">You confirmed this order as received</span>
                            <?php elseif ($row['seller_delivery_status'] === 'delivered'): ?>
                                <span class="status-note">Seller marked this as delivered. Please confirm received.</span>
                            <?php elseif ($row['seller_delivery_status'] === 'out_for_delivery'): ?>
                                <span class="status-note">Your product is out for delivery</span>
                            <?php elseif ($row['seller_delivery_status'] === 'processing'): ?>
                                <span class="status-note">Seller is processing your order</span>
                            <?php else: ?>
                                <span class="status-note">Order is pending seller confirmation</span>
                            <?php endif; ?>

                            <div class="product-actions" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
                                <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>
                                <a href="generate_bill.php?order_id=<?php echo (int)$row['id']; ?>" class="small-btn">View Bill</a>

                                <?php if ($row['seller_delivery_status'] === 'delivered' && (int)$row['buyer_received'] === 0): ?>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="confirm_received" class="small-btn dark">Confirm Received</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <?php if ((int)$row['buyer_received'] === 1): ?>
                                <?php if (!isset($myRatings[(int)$row['id']])): ?>
                                    <button type="button" class="small-btn dark" style="margin-top:12px;" onclick="openRatingModal(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>')">Rate Product</button>
                                <?php else: ?>
                                    <div class="rating-box">
                                        <p style="margin:0 0 8px 0;font-weight:700;">Your Rating</p>
                                        <p class="rating-stars-line">
                                            <?php
                                                $given = (int)$myRatings[(int)$row['id']]['rating'];
                                                echo str_repeat("★", $given) . str_repeat("☆", 5 - $given);
                                            ?>
                                        </p>
                                        <?php if (!empty($myRatings[(int)$row['id']]['review_text'])): ?>
                                            <p style="margin:0;color:#4b5563;"><?php echo htmlspecialchars($myRatings[(int)$row['id']]['review_text']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="inline-empty">No paid purchases available yet.</p>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<div id="ratingModal" class="rating-modal-overlay">
    <div class="rating-modal">
        <button type="button" class="rating-modal-close" onclick="closeRatingModal()">×</button>
        <h2>Rate Product</h2>
        <p id="ratingProductName" class="rating-modal-product">How was your experience?</p>

        <form id="ratingModalForm">
            <input type="hidden" name="order_id" id="ratingOrderId">

            <div class="star-select" id="starSelect">
                <button type="button" data-value="1">★</button>
                <button type="button" data-value="2">★</button>
                <button type="button" data-value="3">★</button>
                <button type="button" data-value="4">★</button>
                <button type="button" data-value="5">★</button>
            </div>

            <input type="hidden" name="rating" id="ratingValue" required>
            <textarea name="review_text" rows="4" placeholder="Write a short review (optional)"></textarea>

            <button type="submit" class="small-btn dark" style="width:100%;margin-top:12px;">Submit Rating</button>
        </form>
    </div>
</div>

<div id="ratingToast" class="cart-added-toast">Rating submitted</div>

<script src="js/script.js"></script>
<script>
function openRatingModal(orderId, productName) {
    const modal = document.getElementById("ratingModal");
    document.getElementById("ratingOrderId").value = orderId;
    document.getElementById("ratingProductName").textContent = productName || "How was your experience?";
    document.getElementById("ratingValue").value = "";

    document.querySelectorAll("#starSelect button").forEach(btn => btn.classList.remove("active"));
    modal.classList.add("show");
}

function closeRatingModal() {
    document.getElementById("ratingModal").classList.remove("show");
}

document.querySelectorAll("#starSelect button").forEach(button => {
    button.addEventListener("click", function () {
        const value = parseInt(this.getAttribute("data-value"));
        document.getElementById("ratingValue").value = value;

        document.querySelectorAll("#starSelect button").forEach(btn => {
            const btnValue = parseInt(btn.getAttribute("data-value"));
            btn.classList.toggle("active", btnValue <= value);
        });
    });
});

document.getElementById("ratingModalForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const toast = document.getElementById("ratingToast");

    if (!formData.get("rating")) {
        toast.textContent = "Please select a rating.";
        toast.classList.add("show");
        setTimeout(() => toast.classList.remove("show"), 1800);
        return;
    }

    fetch("ajax_submit_rating.php", {
        method: "POST",
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        toast.textContent = data.message || "Rating submitted";
        toast.classList.add("show");

        setTimeout(() => toast.classList.remove("show"), 1800);

        if (data.status === "success") {
            closeRatingModal();
            setTimeout(() => window.location.reload(), 700);
        }
    })
    .catch(() => {
        toast.textContent = "Could not submit rating.";
        toast.classList.add("show");
        setTimeout(() => toast.classList.remove("show"), 1800);
    });
});

document.getElementById("ratingModal").addEventListener("click", function (e) {
    if (e.target === this) closeRatingModal();
});

<?php if ($openRatingModalOrderId > 0): ?>
document.addEventListener("DOMContentLoaded", function () {
    openRatingModal(<?php echo (int)$openRatingModalOrderId; ?>, "Your purchased product");
});
<?php endif; ?>
</script>

</body>
</html>