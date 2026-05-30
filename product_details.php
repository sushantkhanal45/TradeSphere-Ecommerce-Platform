<?php
session_start();
include "config/db.php";
include "includes/recommendation_helper.php";

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die("Invalid product.");
}

$res = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = $productId
    LIMIT 1
");

$product = $res ? $res->fetch_assoc() : null;

if (!$product) {
    die("Product not found.");
}

$viewerId = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $conn->real_escape_string($_SESSION['user']);
    $viewerRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
    $viewer = $viewerRes ? $viewerRes->fetch_assoc() : null;

    if ($viewer) {
        $viewerId = (int)$viewer['id'];
        $viewedCategoryId = (int)($product['category_id'] ?? 0);

        $recentViewCheck = $conn->query("
            SELECT id
            FROM product_views
            WHERE user_id = $viewerId
            AND product_id = $productId
            ORDER BY viewed_at DESC
            LIMIT 1
        ");

        if ($recentViewCheck && $recentViewCheck->num_rows > 0) {
            $row = $recentViewCheck->fetch_assoc();
            $viewId = (int)$row['id'];

            $conn->query("
                UPDATE product_views
                SET viewed_at = NOW(), category_id = $viewedCategoryId
                WHERE id = $viewId
            ");
        } else {
            $conn->query("
                INSERT INTO product_views (user_id, product_id, category_id)
                VALUES ($viewerId, $productId, $viewedCategoryId)
            ");
        }
    }
}

$similarProducts = getSimilarProducts($conn, $productId, 4);

$showGoToCart = false;
$success = "";
$error = "";

if (isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    $userEmail = $conn->real_escape_string($_SESSION['user']);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail' LIMIT 1");
    $user = $userRes ? $userRes->fetch_assoc() : null;

    if (!$user) {
        $error = "User not found.";
    } else {
        $userId = (int)$user['id'];
        $qty = max(1, (int)($_POST['quantity'] ?? 1));

        if ($product['status'] === 'sold') {
            $error = "This product is already sold.";
        } elseif ((int)$product['user_id'] === $userId) {
            $error = "You cannot add your own product to cart.";
        } else {
            $check = $conn->query("
                SELECT id, quantity
                FROM cart
                WHERE user_id = $userId AND product_id = $productId
                LIMIT 1
            ");

            if ($check && $check->num_rows > 0) {
                $row = $check->fetch_assoc();
                $newQty = (int)$row['quantity'] + $qty;
                $cartId = (int)$row['id'];

                $conn->query("
                    UPDATE cart
                    SET quantity = $newQty
                    WHERE id = $cartId
                ");
            } else {
                $conn->query("
                    INSERT INTO cart (user_id, product_id, quantity)
                    VALUES ($userId, $productId, $qty)
                ");
            }

            $success = "Product added to cart successfully.";
            $showGoToCart = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .product-detail-wrap{max-width:980px;margin:0 auto;}
        .product-detail-card{background:#fff;border-radius:18px;box-shadow:0 8px 24px rgba(15,23,42,.08);border:1px solid #eef2f7;overflow:hidden;display:grid;grid-template-columns:380px 1fr;}
        .product-detail-left{background:#f8fafc;padding:22px;display:flex;align-items:center;justify-content:center;}
        .product-detail-left img{width:100%;height:320px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;background:#fff;}
        .product-detail-right{padding:24px 26px;}
        .product-detail-title{margin:0 0 10px;font-size:28px;color:#0f172a;}
        .product-detail-price{font-size:30px;font-weight:700;color:#2563eb;margin-bottom:16px;}
        .detail-meta{margin:8px 0;color:#475569;line-height:1.65;font-size:15px;}
        .detail-section-title{margin:18px 0 8px;font-size:16px;font-weight:700;color:#111827;}
        .detail-description{margin-top:12px;padding-top:12px;border-top:1px solid #e5e7eb;}
        .qty-row{margin-top:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;}
        .qty-row label{font-weight:700;color:#111827;}

        .qty-control{display:flex;align-items:center;gap:10px;}
        .qty-btn{
            width:40px;height:40px;border:none;border-radius:10px;
            background:#111827;color:white;font-size:22px;font-weight:800;
            cursor:pointer;display:flex;align-items:center;justify-content:center;
        }
        .qty-btn:hover{background:#2563eb;}
        #quantity{
            width:72px;height:40px;text-align:center;font-weight:800;
            border:1px solid #d1d5db;border-radius:10px;background:#f8fafc;
        }

        .detail-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;}
        .recommend-section{margin-top:28px;}
        .recommend-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px;margin-top:14px;}
        .recommend-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 6px 18px rgba(15,23,42,.06);}
        .recommend-card img{width:100%;height:180px;object-fit:cover;display:block;}
        .recommend-body{padding:14px;}
        .recommend-body h4{margin:0 0 8px;font-size:16px;color:#0f172a;}
        .recommend-meta{font-size:14px;color:#475569;margin:6px 0;}
        .recommend-price{font-size:18px;font-weight:700;color:#2563eb;margin:8px 0 10px;}

        .trade-toast{
            position:fixed;top:24px;right:24px;min-width:280px;max-width:420px;
            padding:14px 18px;border-radius:12px;color:white;font-weight:700;
            z-index:99999;opacity:0;transform:translateY(-16px);
            transition:.25s ease;box-shadow:0 10px 25px rgba(0,0,0,.18);
        }
        .trade-toast.show{opacity:1;transform:translateY(0);}
        .trade-toast.success{background:#16a34a;}
        .trade-toast.error{background:#dc2626;}
        .trade-toast.warning{background:#f59e0b;}

        @media(max-width:900px){
            .product-detail-card{grid-template-columns:1fr;}
            .product-detail-left img{height:260px;}
            .product-detail-title{font-size:24px;}
            .product-detail-price{font-size:26px;}
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container product-detail-wrap">

        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="product-detail-card">
            <div class="product-detail-left">
                <div class="image-box">
                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image">

                    <?php if ($product['status'] === 'sold'): ?>
                        <div class="sold-badge">SOLD</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="product-detail-right">
                <h2 class="product-detail-title"><?php echo htmlspecialchars($product['name']); ?></h2>

                <div class="product-detail-price">
                    Rs <?php echo number_format((float)$product['price'], 2); ?>
                </div>

                <p class="detail-meta"><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                <p class="detail-meta"><strong>Condition:</strong> <?php echo htmlspecialchars($product['product_condition']); ?></p>
                <p class="detail-meta"><strong>City:</strong> <?php echo htmlspecialchars($product['city']); ?></p>
                <p class="detail-meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($product['status'])); ?></p>

                <div class="detail-section-title">Seller Contact</div>
                <p class="detail-meta"><strong>Email:</strong> <?php echo htmlspecialchars($product['seller_email']); ?></p>
                <p class="detail-meta"><strong>Phone:</strong> <?php echo htmlspecialchars($product['contact_number'] ?? 'Not provided'); ?></p>

                <?php if (!empty($product['description'])): ?>
                    <div class="detail-description">
                        <div class="detail-section-title" style="margin-top:0;">Description</div>
                        <p class="detail-meta"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user'])): ?>
                    <?php if ($product['status'] !== 'sold'): ?>

                        <form method="POST">
                            <div class="qty-row">
                                <label>Quantity</label>

                                <div class="qty-control">
                                    <button type="button" class="qty-btn" onclick="decreaseQty()">−</button>

                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        min="1"
                                        value="1"
                                        readonly
                                        required
                                    >

                                    <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                                </div>
                            </div>

                            <div class="detail-actions">
                                <?php if ((int)$product['user_id'] !== $viewerId): ?>
                                    <button type="submit" name="add_to_cart" class="small-btn dark">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.7; cursor:not-allowed;">
                                        Your Product
                                    </button>
                                <?php endif; ?>

                                <?php if ($showGoToCart): ?>
                                    <a href="cart.php" class="small-btn primary">Go to Cart</a>
                                <?php endif; ?>

                                <?php if ($viewerId > 0 && (int)$product['user_id'] !== $viewerId): ?>
                                    <a href="start_chat.php?product_id=<?php echo (int)$product['id']; ?>" class="small-btn primary">
                                        Chat with Seller
                                    </a>
                                <?php endif; ?>

                                <button type="button" onclick="window.history.back()" class="small-btn">
                                    Back
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <div class="detail-actions">
                            <button type="button" class="small-btn dark" disabled style="opacity:0.7; cursor:not-allowed;">Sold</button>

                            <?php if ($viewerId > 0 && (int)$product['user_id'] !== $viewerId): ?>
                                <a href="start_chat.php?product_id=<?php echo (int)$product['id']; ?>" class="small-btn primary">
                                    Chat with Seller
                                </a>
                            <?php endif; ?>

                            <button type="button" onclick="window.history.back()" class="small-btn">Back</button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="detail-actions">
                        <a href="login.php" class="small-btn dark">Login to Buy</a>
                        <button type="button" onclick="window.history.back()" class="small-btn">Back</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($similarProducts)): ?>
            <div class="recommend-section">
                <h3 class="detail-section-title" style="font-size:22px;margin-top:28px;">Similar Products</h3>

                <div class="recommend-grid">
                    <?php foreach ($similarProducts as $item): ?>
                        <div class="recommend-card">
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="Product Image">

                            <div class="recommend-body">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <div class="recommend-price">Rs <?php echo number_format((float)$item['price'], 2); ?></div>

                                <?php if (!empty($item['recommendation_reason'])): ?>
                                    <p class="recommend-meta" style="color:#2563eb;font-weight:600;">
                                        <?php echo htmlspecialchars($item['recommendation_reason']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="recommend-meta"><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                                <p class="recommend-meta"><strong>Condition:</strong> <?php echo htmlspecialchars($item['product_condition']); ?></p>
                                <p class="recommend-meta"><strong>City:</strong> <?php echo htmlspecialchars($item['city']); ?></p>

                                <a href="product_details.php?id=<?php echo (int)$item['id']; ?>" class="small-btn primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<div id="tradeToast" class="trade-toast"></div>

<script src="js/script.js"></script>

<script>
function increaseQty() {
    const qty = document.getElementById("quantity");
    qty.value = parseInt(qty.value || 1) + 1;
}

function decreaseQty() {
    const qty = document.getElementById("quantity");
    let value = parseInt(qty.value || 1);

    if (value > 1) {
        qty.value = value - 1;
    } else {
        showTradeToast("Quantity cannot be less than 1.", "warning");
    }
}

function showTradeToast(message, type = "success") {
    const toast = document.getElementById("tradeToast");
    if (!toast || !message) return;

    toast.textContent = message;
    toast.className = "trade-toast show " + type;

    setTimeout(() => {
        toast.classList.remove("show");
    }, 2200);
}

<?php if ($success): ?>
showTradeToast("<?php echo addslashes($success); ?>", "success");
<?php endif; ?>

<?php if ($error): ?>
showTradeToast("<?php echo addslashes($error); ?>", "error");
<?php endif; ?>
</script>

</body>
</html>