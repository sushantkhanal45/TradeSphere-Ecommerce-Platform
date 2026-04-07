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

            $success = "Order marked as received successfully.";
            $highlightOrderId = $orderId;
            $highlightMessage = "Buyer confirmed received";
        } else {
            $error = "Could not confirm receipt for this order.";
        }
    }
}

$totalListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId");
$totalListings = $totalListingsRes ? (int)$totalListingsRes->fetch_assoc()['total'] : 0;

$soldListingsRes = $conn->query("SELECT COUNT(*) AS total FROM products WHERE user_id=$userId AND status='sold'");
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

$myListings = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = $userId
    ORDER BY p.id DESC
");

$myPurchases = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image,
        p.seller_email,
        p.contact_number
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
        .seller-card{
            position: relative;
        }

        .seller-card .product-image-wrap{
            position: relative;
            overflow: visible;
        }

        .card-menu{
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 999;
        }

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

        .card-menu-btn:hover{
            background: #38bdf8;
            color: #062033;
        }

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
        .menu-action-btn:hover{
            background: #f3f4f6;
        }

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

        @keyframes fadePop {
            0% {
                opacity: 0;
                transform: translateY(-8px) scale(0.96);
            }
            10% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            80% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(-8px) scale(0.98);
            }
        }

        @media (max-width: 992px){
            .profile-grid{
                grid-template-columns: repeat(2, minmax(0,1fr)) !important;
            }
        }

        @media (max-width: 768px){
            .profile-grid{
                grid-template-columns: 1fr !important;
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

            <div class="profile-grid" style="grid-template-columns: repeat(6, minmax(0,1fr));">
                <div class="profile-stat">
                    <h3>Total Listings</h3>
                    <p><?php echo $totalListings; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Marked Sold</h3>
                    <p><?php echo $soldListings; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Received Orders</h3>
                    <p><?php echo $receivedOrdersCount; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Completed Sales</h3>
                    <p><?php echo $completedSales; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Cart Items</h3>
                    <p><?php echo $cartItems; ?></p>
                </div>

                <div class="profile-stat">
                    <h3>Paid Purchases</h3>
                    <p><?php echo $totalPurchases; ?></p>
                </div>
            </div>
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
                                <p class="meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>
                                <p class="meta"><strong>Phone:</strong> <?php echo htmlspecialchars($row['contact_number'] ?? 'Not provided'); ?></p>

                                <?php if ((int)$row['buyer_received'] === 1): ?>
                                    <span class="purchase-badge">RECEIVED CONFIRMED</span>
                                <?php elseif ($row['seller_delivery_status'] === 'delivered'): ?>
                                    <span class="status-note">Seller marked this as delivered</span>
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
                                    <span class="purchase-badge">BUYER CONFIRMED</span>
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
                                    <button type="button" class="card-menu-btn" onclick="toggleListingMenu(<?php echo $row['id']; ?>)">⋮</button>
                                    <div class="card-menu-dropdown" id="listing-menu-<?php echo $row['id']; ?>">
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit Product</a>

                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_status" class="menu-action-btn">
                                                <?php echo ($row['status'] === 'sold') ? 'Mark as Available' : 'Mark as Sold'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
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
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                                <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="inline-empty">You have not listed any products yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
<script>
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
</script>

<?php if ($highlightOrderId > 0): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const card = document.getElementById("order-card-<?php echo (int)$highlightOrderId; ?>");
    if (card) {
        card.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }
});
</script>
<?php endif; ?>

</body>
</html>