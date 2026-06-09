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
$highlightOrderId = 0;
$highlightMessage = "";
$openRatingModalOrderId = 0;

/* Toggle product status */
if (isset($_POST['toggle_status'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $oldStatus = $product['status'];
        $newStatus = ($oldStatus === 'sold') ? 'available' : 'sold';

        if ($conn->query("UPDATE products SET status='$newStatus' WHERE id=$productId AND user_id=$userId")) {
            $actionData = json_encode([
                "user_id" => $userId,
                "product_id" => $productId,
                "old_status" => $oldStatus,
                "new_status" => $newStatus,
                "action" => "product_status_update",
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);
            if ($signature) {
                storeSignatureRecord($conn, $userId, "product_status_update", $productId, $actionData, $signature);
            }

            $success = "Product status updated successfully.";
        } else {
            $error = "Could not update product status.";
        }
    } else {
        $error = "Product not found or access denied.";
    }
}

/* Delete product */
if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $productName = $product['name'];
        $productImage = $product['image'];

        if ($conn->query("DELETE FROM products WHERE id=$productId AND user_id=$userId")) {
            $actionData = json_encode([
                "user_id" => $userId,
                "product_id" => $productId,
                "product_name" => $productName,
                "action" => "product_deleted",
                "timestamp" => date("Y-m-d H:i:s")
            ]);

            $signature = signData($actionData);
            if ($signature) {
                storeSignatureRecord($conn, $userId, "product_deleted", $productId, $actionData, $signature);
            }

            if (!empty($productImage) && file_exists("uploads/" . $productImage)) {
                @unlink("uploads/" . $productImage);
            }

            $success = "Product deleted successfully.";
        } else {
            $error = "Could not delete product.";
        }
    } else {
        $error = "Product not found or access denied.";
    }
}

/* Seller delivery status update */
if (isset($_POST['update_delivery_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newDeliveryStatus = trim($_POST['seller_delivery_status']);

    $allowedStatuses = ['pending', 'processing', 'out_for_delivery', 'delivered'];

    if (!in_array($newDeliveryStatus, $allowedStatuses, true)) {
        $error = "Invalid delivery status selected.";
    } else {
        $escapedStatus = $conn->real_escape_string($newDeliveryStatus);

        $checkOrder = $conn->query("
            SELECT o.*, p.name AS product_name
            FROM orders o
            INNER JOIN products p ON o.product_id = p.id
            WHERE o.id = $orderId
            AND o.seller_user_id = $userId
            LIMIT 1
        ");

        $orderData = $checkOrder ? $checkOrder->fetch_assoc() : null;

        if (!$orderData) {
            $error = "Order not found or access denied.";
        } else {
            $deliveredAtSql = ($escapedStatus === 'delivered')
                ? ", delivered_at = NOW()"
                : ", delivered_at = NULL";

            $newOrderStatus = ($escapedStatus === 'pending') ? 'pending' : 'processing';

            $buyerReceivedResetSql = ($escapedStatus !== 'delivered')
                ? ", buyer_received = 0, buyer_received_at = NULL"
                : "";

            $updateSql = "
                UPDATE orders
                SET seller_delivery_status = '$escapedStatus',
                    order_status = '$newOrderStatus'
                    $deliveredAtSql
                    $buyerReceivedResetSql
                WHERE id = $orderId
                AND seller_user_id = $userId
            ";

            if ($conn->query($updateSql)) {
                $actionData = json_encode([
                    "user_id" => $userId,
                    "order_id" => $orderId,
                    "product_id" => $orderData['product_id'],
                    "product_name" => $orderData['product_name'],
                    "new_delivery_status" => $escapedStatus,
                    "action" => "seller_delivery_status_update",
                    "timestamp" => date("Y-m-d H:i:s")
                ]);

                $signature = signData($actionData);
                if ($signature) {
                    storeSignatureRecord($conn, $userId, "seller_delivery_status_update", $orderId, $actionData, $signature);
                }

                $buyerId = (int)$orderData['user_id'];
                $prettyStatus = ucwords(str_replace('_', ' ', $escapedStatus));

                if ($escapedStatus === 'processing') {
                    $notificationMessage = "Seller is processing your order: " . $orderData['product_name'];
                } elseif ($escapedStatus === 'out_for_delivery') {
                    $notificationMessage = "Your product is out for delivery: " . $orderData['product_name'];
                } elseif ($escapedStatus === 'delivered') {
                    $notificationMessage = "Seller marked your order as delivered. Please confirm received: " . $orderData['product_name'];
                } else {
                    $notificationMessage = "Seller updated your order status to $prettyStatus: " . $orderData['product_name'];
                }

                $conn->query("
                    INSERT INTO notifications (user_id, order_id, message)
                    VALUES (
                        $buyerId,
                        $orderId,
                        '" . $conn->real_escape_string($notificationMessage) . "'
                    )
                ");

                $success = "Seller delivery status updated successfully.";
                $highlightOrderId = $orderId;
                $highlightMessage = "Delivery status updated";
            } else {
                $error = "Could not update seller delivery status.";
            }
        }
    }
}

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
        $updateSql = "
            UPDATE orders
            SET buyer_received = 1,
                buyer_received_at = NOW(),
                order_status = 'completed'
            WHERE id = $orderId
            AND user_id = $userId
        ";

        if ($conn->query($updateSql)) {
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
                VALUES (
                    $sellerId,
                    $orderId,
                    '" . $conn->real_escape_string($notificationMessage) . "'
                )
            ");

            $success = "Order marked as received successfully.";
            $highlightOrderId = $orderId;
            $highlightMessage = "Buyer confirmed received";
            $openRatingModalOrderId = $orderId;
        } else {
            $error = "Could not confirm receipt for this order.";
        }
    }
}

$totalListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND ai_status='approved'");
$totalListings = $totalListingsRes ? (int)$totalListingsRes->fetch_assoc()['total'] : 0;

$soldListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND status='sold' AND ai_status='approved'");
$soldListings = $soldListingsRes ? (int)$soldListingsRes->fetch_assoc()['total'] : 0;

$completedSalesRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE seller_user_id = $userId
    AND buyer_received = 1
");
$completedSales = $completedSalesRes ? (int)$completedSalesRes->fetch_assoc()['total'] : 0;

$receivedOrdersCountRes = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE seller_user_id = $userId
");
$receivedOrdersCount = $receivedOrdersCountRes ? (int)$receivedOrdersCountRes->fetch_assoc()['total'] : 0;

$cartItemsRes = $conn->query("SELECT SUM(quantity) AS total FROM cart WHERE user_id=$userId");
$cartItems = $cartItemsRes ? (int)($cartItemsRes->fetch_assoc()['total'] ?? 0) : 0;

$totalPurchasesRes = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE user_id=$userId AND payment_status='paid'");
$totalPurchases = $totalPurchasesRes ? (int)$totalPurchasesRes->fetch_assoc()['total'] : 0;

$wishlistCountRes = $conn->query("SELECT COUNT(*) AS total FROM wishlist WHERE user_id = $userId");
$wishlistCount = $wishlistCountRes ? (int)$wishlistCountRes->fetch_assoc()['total'] : 0;

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

$myWishlist = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM wishlist w
    INNER JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = $userId
    ORDER BY w.created_at DESC
");

$myListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    AND p.ai_status = 'approved'
    ORDER BY p.id DESC
");

$pendingListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    AND p.ai_status = 'manual_review'
    ORDER BY p.id DESC
");

$rejectedListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    AND p.ai_status = 'rejected'
    ORDER BY p.id DESC
");

$pendingListingsCountRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND ai_status='manual_review'");
$pendingListingsCount = $pendingListingsCountRes ? (int)$pendingListingsCountRes->fetch_assoc()['total'] : 0;

$rejectedListingsCountRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND ai_status='rejected'");
$rejectedListingsCount = $rejectedListingsCountRes ? (int)$rejectedListingsCountRes->fetch_assoc()['total'] : 0;

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

$receivedOrders = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.seller_user_id = $userId
    ORDER BY o.created_at DESC
");

$mySales = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.seller_user_id = $userId
    AND o.buyer_received = 1
    ORDER BY o.buyer_received_at DESC, o.created_at DESC
");

$firstLetter = strtoupper(substr($user['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .seller-card{ position: relative; }
        .seller-card .product-image-wrap{ position: relative; overflow: visible; }
        .card-menu{ position: absolute; top: 12px; right: 12px; z-index: 999; }

        .card-menu-btn{
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(15, 23, 42, 0.88);
            color: white;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.18);
        }

        .card-menu-btn:hover{ background: #38bdf8; color: #062033; }

        .card-menu-dropdown{
            display: none;
            position: absolute;
            top: 46px;
            right: 0;
            min-width: 190px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 16px 35px rgba(0,0,0,0.18);
            overflow: hidden;
            z-index: 1000;
        }

        .card-menu-dropdown a,
        .menu-action-btn{
            display: block;
            width: 100%;
            padding: 13px 14px;
            text-align: left;
            background: white;
            border: none;
            text-decoration: none;
            color: #111827;
            font-size: 14px;
            cursor: pointer;
        }

        .card-menu-dropdown a:hover,
        .menu-action-btn:hover{ background: #f3f4f6; }

        .purchase-badge{
            display: inline-block;
            margin-top: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 12px;
            font-weight: 700;
        }

        .status-note{
            display:inline-block;
            margin-top:8px;
            padding:6px 10px;
            border-radius:999px;
            background:#eff6ff;
            color:#1d4ed8;
            font-size:12px;
            font-weight:700;
        }

        .tracked-order-card{
            position: relative;
            transition: all 0.3s ease;
        }

        .tracked-order-card.active-track{
            border: 2px solid #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12), 0 18px 35px rgba(37, 99, 235, 0.12);
            transform: translateY(-2px);
        }

        .order-action-popup{
            position: absolute;
            top: 14px;
            left: 14px;
            z-index: 20;
            background: #2563eb;
            color: #fff;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.25);
            animation: fadePop 2.4s ease forwards;
        }

        .order-action-popup.success-green{
            background: #059669;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.25);
        }

        .wishlist-heading-row{
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .clear-wishlist-btn{
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            background: #dc2626;
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .clear-wishlist-btn:hover{ background: #b91c1c; }

        .wishlist-remove-btn{
            border: none;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #fee2e2;
            color: #b91c1c;
        }

        .wishlist-remove-btn:hover{ background: #fecaca; }

        .rating-box{
            margin-top: 14px;
            padding: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fafafa;
        }

        .rating-stars-line{
            color:#f59e0b;
            font-weight:700;
            margin-bottom:6px;
        }

        .rating-modal-overlay{
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 20px;
        }

        .rating-modal-overlay.show{ display: flex; }

        .rating-modal{
            width: 100%;
            max-width: 430px;
            background: white;
            border-radius: 22px;
            padding: 28px;
            position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.28);
            animation: modalPop 0.25s ease;
        }

        .rating-modal h2{
            margin-bottom: 8px;
            text-align: center;
        }

        .rating-modal-product{
            text-align: center;
            color: #6b7280;
            margin-bottom: 18px;
        }

        .rating-modal-close{
            position: absolute;
            top: 12px;
            right: 14px;
            border: none;
            background: #f3f4f6;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            font-size: 22px;
            cursor: pointer;
        }

        .star-select{
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 18px;
        }

        .star-select button{
            border: none;
            background: transparent;
            font-size: 36px;
            color: #d1d5db;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .star-select button.active,
        .star-select button:hover{
            color: #f59e0b;
            transform: scale(1.08);
        }

        .rating-modal textarea{
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            resize: vertical;
        }

        @keyframes fadePop {
            0% { opacity: 0; transform: translateY(-8px) scale(0.96); }
            10% { opacity: 1; transform: translateY(0) scale(1); }
            80% { opacity: 1; transform: translateY(0) scale(1); }
            100% { opacity: 0; transform: translateY(-8px) scale(0.98); }
        }

        @keyframes modalPop{
            from{ opacity: 0; transform: translateY(12px) scale(0.96); }
            to{ opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (max-width: 992px){
            .profile-grid{ grid-template-columns: repeat(2, minmax(0,1fr)) !important; }
        }

        @media (max-width: 768px){
            .profile-grid{ grid-template-columns: 1fr !important; }
        }

        @media (max-width: 600px){
            .wishlist-heading-row{
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="profile-section-card">
            <div class="profile-header-box">
                <div class="profile-avatar-lg"><?php echo htmlspecialchars($firstLetter); ?></div>

                <div class="profile-meta">
                    <h2 style="margin:0 0 8px 0;"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                    <p><strong>Verified:</strong> <?php echo ((int)$user['is_verified'] === 1) ? "Yes" : "No"; ?></p>
                </div>
            </div>
        </div>

        <div class="profile-section-card">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Account Overview</h2>

            <div class="profile-grid" style="grid-template-columns: repeat(7, minmax(0,1fr));">
                <div class="profile-stat"><h3>Total Listings</h3><p><?php echo $totalListings; ?></p></div>
                <div class="profile-stat"><h3>Marked Sold</h3><p><?php echo $soldListings; ?></p></div>
                <div class="profile-stat"><h3>Received Orders</h3><p><?php echo $receivedOrdersCount; ?></p></div>
                <div class="profile-stat"><h3>Completed Sales</h3><p><?php echo $completedSales; ?></p></div>
                <div class="profile-stat"><h3>Cart Items</h3><p><?php echo $cartItems; ?></p></div>
                <div class="profile-stat"><h3>Paid Purchases</h3><p><?php echo $totalPurchases; ?></p></div>
                <div class="profile-stat"><h3>Wishlist Items</h3><p id="wishlistStatCount"><?php echo $wishlistCount; ?></p></div>
            </div>
        </div>

        <div class="profile-section-card" id="wishlist">
            <div class="wishlist-heading-row">
                <h2 class="section-title" style="text-align:left; margin-bottom:0;">My Wishlist</h2>

                <?php if ($myWishlist && $myWishlist->num_rows > 0): ?>
                    <button type="button" id="clearWishlistBtn" class="clear-wishlist-btn">
                        Clear All
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($myWishlist && $myWishlist->num_rows > 0): ?>
                <div class="products-grid" id="wishlistGrid">
                    <?php while ($row = $myWishlist->fetch_assoc()): ?>
                        <div class="product-card wishlist-card" id="wishlist-card-<?php echo (int)$row['id']; ?>">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Wishlist Product">

                                <?php if ($row['status'] === 'sold'): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php endif; ?>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo number_format((float)$row['price'], 2); ?></p>

                                <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                    <p class="rating-stars-line">
                                        <?php
                                            $rounded = (int) round((float)$row['average_rating']);
                                            echo str_repeat("★", $rounded) . str_repeat("☆", 5 - $rounded);
                                        ?>
                                        <?php echo number_format((float)$row['average_rating'], 1); ?>
                                        (<?php echo (int)$row['rating_count']; ?>)
                                    </p>
                                <?php else: ?>
                                    <p class="meta">No ratings yet</p>
                                <?php endif; ?>

                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>

                                <div class="product-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                                    <a href="product_details.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary">View Details</a>

                                    <button 
                                        type="button" 
                                        class="wishlist-remove-btn"
                                        onclick="removeProfileWishlistItem(event, <?php echo (int)$row['id']; ?>)"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="inline-empty" id="wishlistEmptyMessage">No items in your wishlist yet.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="purchases">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">My Purchases</h2>

            <?php if ($myPurchases && $myPurchases->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $myPurchases->fetch_assoc()): ?>
                        <div 
                            class="product-card tracked-order-card <?php echo ($highlightOrderId === (int)$row['id']) ? 'active-track' : ''; ?>" 
                            id="order-card-<?php echo (int)$row['id']; ?>"
                        >
                            <?php if ($highlightOrderId === (int)$row['id']): ?>
                                <div class="order-action-popup success-green">
                                    <?php echo htmlspecialchars($highlightMessage); ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Purchased Product">
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>

                                <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                    <p class="rating-stars-line">
                                        <?php
                                            $rounded = (int) round((float)$row['average_rating']);
                                            echo str_repeat("★", $rounded) . str_repeat("☆", 5 - $rounded);
                                        ?>
                                        <?php echo number_format((float)$row['average_rating'], 1); ?>
                                        (<?php echo (int)$row['rating_count']; ?>)
                                    </p>
                                <?php else: ?>
                                    <p class="meta">No ratings yet</p>
                                <?php endif; ?>

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
                                <?php elseif ($row['seller_delivery_status'] === 'pending'): ?>
                                    <span class="status-note">Order is pending seller confirmation</span>
                                <?php else: ?>
                                    <span class="status-note">Waiting for seller update</span>
                                <?php endif; ?>

                                <div class="product-actions" style="display:flex; flex-wrap:wrap; gap:10px;">
                                    <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>
                                    <a href="generate_bill.php?order_id=<?php echo (int)$row['id']; ?>" class="small-btn">View Bill</a>

                                    <?php if ($row['seller_delivery_status'] === 'delivered' && (int)$row['buyer_received'] === 0): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="confirm_received" class="small-btn dark">
                                                Confirm Received
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <?php if ((int)$row['buyer_received'] === 1): ?>
                                    <?php if (!isset($myRatings[(int)$row['id']])): ?>
                                        <button
                                            type="button"
                                            class="small-btn dark"
                                            onclick="openRatingModal(
                                                <?php echo (int)$row['id']; ?>,
                                                '<?php echo htmlspecialchars(addslashes($row['product_name'])); ?>'
                                            )"
                                            style="margin-top:12px;"
                                        >
                                            Rate Product
                                        </button>
                                    <?php else: ?>
                                        <div class="rating-box">
                                            <p style="margin:0 0 8px 0; font-weight:700;">Your Rating</p>
                                            <p class="rating-stars-line">
                                                <?php
                                                    $given = (int)$myRatings[(int)$row['id']]['rating'];
                                                    echo str_repeat("★", $given) . str_repeat("☆", 5 - $given);
                                                ?>
                                            </p>

                                            <?php if (!empty($myRatings[(int)$row['id']]['review_text'])): ?>
                                                <p style="margin:0; color:#4b5563;">
                                                    <?php echo htmlspecialchars($myRatings[(int)$row['id']]['review_text']); ?>
                                                </p>
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

        <div class="profile-section-card" id="orders_received">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Received Orders</h2>

            <?php if ($receivedOrders && $receivedOrders->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $receivedOrders->fetch_assoc()): ?>
                        <div 
                            class="product-card tracked-order-card <?php echo ($highlightOrderId === (int)$row['id']) ? 'active-track' : ''; ?>" 
                            id="order-card-<?php echo (int)$row['id']; ?>"
                        >
                            <?php if ($highlightOrderId === (int)$row['id']): ?>
                                <div class="order-action-popup">
                                    <?php echo htmlspecialchars($highlightMessage); ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Ordered Product">
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>
                                <p class="meta"><strong>Buyer:</strong> <?php echo htmlspecialchars($row['buyer_name']); ?></p>
                                <p class="meta"><strong>Email:</strong> <?php echo htmlspecialchars($row['buyer_email']); ?></p>
                                <p class="meta"><strong>Phone:</strong> <?php echo htmlspecialchars($row['buyer_phone']); ?></p>
                                <p class="meta"><strong>Delivery:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $row['seller_delivery_status']))); ?></p>

                                <?php if ((int)$row['buyer_received'] === 1): ?>
                                    <span class="purchase-badge">Buyer confirmed receiving this product</span>
                                <?php else: ?>
                                    <span class="status-note">Waiting for buyer confirmation</span>
                                <?php endif; ?>

                                <div class="product-actions" style="display:flex; flex-direction:column; gap:10px; align-items:stretch;">
                                    <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>

                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">

                                        <select name="seller_delivery_status" style="width:100%; padding:10px; border-radius:10px; border:1px solid #d1d5db; margin-bottom:10px;">
                                            <option value="pending" <?php echo ($row['seller_delivery_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($row['seller_delivery_status'] === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="out_for_delivery" <?php echo ($row['seller_delivery_status'] === 'out_for_delivery') ? 'selected' : ''; ?>>Out for Delivery</option>
                                            <option value="delivered" <?php echo ($row['seller_delivery_status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        </select>

                                        <button type="submit" name="update_delivery_status" class="small-btn dark" style="width:100%;">
                                            Update Delivery Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="inline-empty">No received orders yet.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="sales">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Completed Sales</h2>

            <?php if ($mySales && $mySales->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $mySales->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Sold Product">
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>
                                <p class="meta"><strong>Buyer:</strong> <?php echo htmlspecialchars($row['buyer_name']); ?></p>
                                <p class="meta"><strong>Email:</strong> <?php echo htmlspecialchars($row['buyer_email']); ?></p>

                                <span class="purchase-badge">SALE COMPLETED</span>

                                <div class="product-actions">
                                    <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="inline-empty">No completed sales yet.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="listings">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">My Listings</h2>

            <?php if ($myListings && $myListings->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $myListings->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">

                                <?php if ($row['status'] === 'sold'): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php endif; ?>

                                <div class="card-menu">
                                    <button type="button" class="card-menu-btn" onclick="toggleListingMenu(<?php echo (int)$row['id']; ?>)">⋮</button>
                                    <div class="card-menu-dropdown" id="listing-menu-<?php echo (int)$row['id']; ?>">
                                        <a href="edit_product.php?id=<?php echo (int)$row['id']; ?>">Edit Product</a>

                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="toggle_status" class="menu-action-btn">
                                                <?php echo ($row['status'] === 'sold') ? 'Mark as Available' : 'Mark as Sold'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="delete_product" class="menu-action-btn">
                                                Delete Product
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>

                                <?php if ((int)($row['rating_count'] ?? 0) > 0): ?>
                                    <p class="rating-stars-line">
                                        <?php
                                            $rounded = (int) round((float)$row['average_rating']);
                                            echo str_repeat("★", $rounded) . str_repeat("☆", 5 - $rounded);
                                        ?>
                                        <?php echo number_format((float)$row['average_rating'], 1); ?>
                                        (<?php echo (int)$row['rating_count']; ?>)
                                    </p>
                                <?php else: ?>
                                    <p class="meta">No ratings yet</p>
                                <?php endif; ?>

                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>
                                <p class="meta"><strong>Verification:</strong> Approved</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($totalListings > 6): ?>
                    <div class="profile-show-all-row">
                        <a href="my_listings.php" class="small-btn dark">Show All Listings</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="inline-empty">You have no approved product listings yet.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="pending-verification">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Pending Verification</h2>

            <?php if ($pendingListings && $pendingListings->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $pendingListings->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Pending Product">
                                <div class="sold-badge" style="background:#f59e0b;">REVIEW</div>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Verification:</strong> Under Admin Review</p>
                                <?php if (!empty($row['ai_reason'])): ?>
                                    <p class="meta"><strong>Reason:</strong> <?php echo htmlspecialchars($row['ai_reason']); ?></p>
                                <?php endif; ?>
                                <p class="meta" style="color:#92400e;font-weight:700;">This product is hidden from buyers until admin approval.</p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($pendingListingsCount > 6): ?>
                    <div class="profile-show-all-row">
                        <a href="my_listings.php?status=manual_review" class="small-btn dark">Show All Pending</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="inline-empty">No products are pending verification.</p>
            <?php endif; ?>
        </div>

        <div class="profile-section-card" id="rejected-products">
            <h2 class="section-title" style="text-align:left; margin-bottom:20px;">Rejected Products</h2>

            <?php if ($rejectedListings && $rejectedListings->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $rejectedListings->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Rejected Product">
                                <div class="sold-badge">REJECTED</div>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Verification:</strong> Rejected</p>

                                <?php if (!empty($row['ai_reason'])): ?>
                                    <p class="meta"><strong>Reason:</strong> <?php echo htmlspecialchars($row['ai_reason']); ?></p>
                                <?php endif; ?>

                                <p class="meta" style="color:#b91c1c;font-weight:700;">
                                    This product is not visible to buyers. Check your email for full details and submit again with better information.
                                </p>

                                <div class="product-actions" style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <a href="edit_product.php?id=<?php echo (int)$row['id']; ?>" class="small-btn primary">Edit & Resubmit</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($rejectedListingsCount > 6): ?>
                    <div class="profile-show-all-row">
                        <a href="my_listings.php?status=rejected" class="small-btn dark">Show All Rejected</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="inline-empty">No rejected products.</p>
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

            <textarea
                name="review_text"
                rows="4"
                placeholder="Write a short review (optional)"
            ></textarea>

            <button type="submit" class="small-btn dark" style="width:100%; margin-top:12px;">
                Submit Rating
            </button>
        </form>
    </div>
</div>

<div id="ratingToast" class="cart-added-toast">Rating submitted</div>
<div id="wishlistToast" class="cart-added-toast">Wishlist updated</div>

<script src="js/script.js"></script>

<script>

function showWishlistToast(message) {
    const toast = document.getElementById("wishlistToast");
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 1800);
}

function updateWishlistStat(count) {
    const stat = document.getElementById("wishlistStatCount");
    if (stat) {
        stat.textContent = count;
    }
}

function updateNavbarWishlistCount(count) {
    const candidates = [
        document.getElementById("wishlistCount"),
        document.querySelector(".wishlist-count-badge"),
        document.querySelector(".wishlist-badge")
    ];

    candidates.forEach(function(el) {
        if (el) {
            el.textContent = count;
            if (count <= 0) {
                el.style.display = "none";
            } else {
                el.style.display = "inline-flex";
            }
        }
    });
}

function showWishlistEmptyState() {
    const section = document.getElementById("wishlist");
    const grid = document.getElementById("wishlistGrid");
    const clearBtn = document.getElementById("clearWishlistBtn");

    if (grid) {
        grid.remove();
    }

    if (clearBtn) {
        clearBtn.remove();
    }

    if (section && !document.getElementById("wishlistEmptyMessage")) {
        const empty = document.createElement("p");
        empty.className = "inline-empty";
        empty.id = "wishlistEmptyMessage";
        empty.textContent = "No items in your wishlist yet.";
        section.appendChild(empty);
    }
}

function removeProfileWishlistItem(event, productId) {
    event.preventDefault();
    event.stopPropagation();

    const formData = new URLSearchParams();
    formData.append("action", "remove");
    formData.append("product_id", productId);

    fetch("ajax_profile_wishlist.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            const card = document.getElementById("wishlist-card-" + productId);
            if (card) {
                card.remove();
            }

            updateWishlistStat(data.wishlist_count || 0);
            updateNavbarWishlistCount(data.wishlist_count || 0);

            if ((data.wishlist_count || 0) <= 0) {
                showWishlistEmptyState();
            }

            showWishlistToast(data.message || "Item removed from wishlist.");
        } else {
            showWishlistToast(data.message || "Could not remove item from wishlist.");
        }
    })
    .catch(() => {
        showWishlistToast("Network error. Could not update wishlist.");
    });
}

document.addEventListener("DOMContentLoaded", function () {
    const clearBtn = document.getElementById("clearWishlistBtn");

    if (clearBtn) {
        clearBtn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (!confirm("Are you sure you want to clear all wishlist items?")) {
                return;
            }

            const formData = new URLSearchParams();
            formData.append("action", "clear");

            fetch("ajax_profile_wishlist.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    updateWishlistStat(0);
                    updateNavbarWishlistCount(0);
                    showWishlistEmptyState();
                    showWishlistToast(data.message || "Wishlist cleared.");
                } else {
                    showWishlistToast(data.message || "Could not clear wishlist.");
                }
            })
            .catch(() => {
                showWishlistToast("Network error. Could not clear wishlist.");
            });
        });
    }
});

function toggleListingMenu(id) {
    const menu = document.getElementById("listing-menu-" + id);
    const allMenus = document.querySelectorAll(".card-menu-dropdown");

    allMenus.forEach(function(item) {
        if (item !== menu) {
            item.style.display = "none";
        }
    });

    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

window.addEventListener("click", function(e) {
    if (!e.target.closest(".card-menu")) {
        document.querySelectorAll(".card-menu-dropdown").forEach(function(menu) {
            menu.style.display = "none";
        });
    }
});

function openRatingModal(orderId, productName) {
    const modal = document.getElementById("ratingModal");
    const orderInput = document.getElementById("ratingOrderId");
    const productText = document.getElementById("ratingProductName");
    const ratingValue = document.getElementById("ratingValue");

    orderInput.value = orderId;
    productText.textContent = productName || "How was your experience?";
    ratingValue.value = "";

    document.querySelectorAll("#starSelect button").forEach(btn => {
        btn.classList.remove("active");
    });

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

        setTimeout(() => {
            toast.classList.remove("show");
        }, 1800);

        if (data.status === "success") {
            closeRatingModal();

            setTimeout(() => {
                window.location.reload();
            }, 700);
        }
    })
    .catch(() => {
        toast.textContent = "Could not submit rating.";
        toast.classList.add("show");

        setTimeout(() => {
            toast.classList.remove("show");
        }, 1800);
    });
});

document.getElementById("ratingModal").addEventListener("click", function (e) {
    if (e.target === this) {
        closeRatingModal();
    }
});

<?php if ($highlightOrderId > 0): ?>
document.addEventListener("DOMContentLoaded", function () {
    const card = document.getElementById("order-card-<?php echo (int)$highlightOrderId; ?>");
    if (card) {
        card.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }
});
<?php endif; ?>

<?php if ($openRatingModalOrderId > 0): ?>
document.addEventListener("DOMContentLoaded", function () {
    openRatingModal(
        <?php echo (int)$openRatingModalOrderId; ?>,
        "Your purchased product"
    );
});
<?php endif; ?>
</script>

</body>
</html>