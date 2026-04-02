<?php
session_start();
include "config/db.php";

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$success = "";
$error = "";
$triggerCartAnimation = false;
$userId = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $safeEmail = $conn->real_escape_string($userEmail);
    $userRes = $conn->query("SELECT id FROM users WHERE email='$safeEmail' LIMIT 1");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
    }
}

/* Direct add to cart from product card */
if (isset($_POST['add_to_cart_card'])) {
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    $productId = (int)$_POST['product_id'];
    $productCheck = $conn->query("SELECT * FROM products WHERE id=$productId LIMIT 1");
    $productData = $productCheck ? $productCheck->fetch_assoc() : null;

    if (!$productData) {
        $error = "Product not found.";
    } elseif ($productData['status'] === 'sold') {
        $error = "This item has already been marked as sold.";
    } elseif ($userId <= 0) {
        $error = "User not found.";
    } else {
        $existingCheck = $conn->query("SELECT * FROM cart WHERE user_id=$userId AND product_id=$productId LIMIT 1");

        if ($existingCheck && $existingCheck->num_rows > 0) {
            $existing = $existingCheck->fetch_assoc();
            $newQty = (int)$existing['quantity'] + 1;
            $cartId = (int)$existing['id'];
            $conn->query("UPDATE cart SET quantity=$newQty WHERE id=$cartId");
        } else {
            $conn->query("INSERT INTO cart (user_id, product_id, quantity) VALUES ($userId, $productId, 1)");
        }

        $success = "Product added to cart successfully.";
        $triggerCartAnimation = true;
    }
}

$categoryQuery = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";

if ($search !== "") {
    $safeSearch = $conn->real_escape_string($search);
    $sql .= " AND (
        p.name LIKE '%$safeSearch%' OR
        p.city LIKE '%$safeSearch%' OR
        c.name LIKE '%$safeSearch%' OR
        p.seller_email LIKE '%$safeSearch%' OR
        p.description LIKE '%$safeSearch%' OR
        p.product_condition LIKE '%$safeSearch%'
    )";
}

if ($categoryId > 0) {
    $sql .= " AND p.category_id = $categoryId";
}

$sql .= " ORDER BY p.id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Explore Products</h1>
        <p class="section-subtitle">
            Search products by name, city, category, seller, description, or condition.
        </p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="search-filter-box">
            <form method="GET" action="products.php" class="search-form">
                <div class="search-group">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search products, city, seller, or category..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="search-group">
                    <select name="category_id">
                        <option value="">All Categories</option>
                        <?php if ($categoryQuery && $categoryQuery->num_rows > 0): ?>
                            <?php while ($cat = $categoryQuery->fetch_assoc()): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
                <a href="products.php" class="btn btn-secondary reset-btn">Reset</a>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">
                            <?php if ($row['status'] === 'sold'): ?>
                                <div class="sold-badge">SOLD</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                            <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                            <p class="meta"><strong>Condition:</strong> <?php echo htmlspecialchars($row['product_condition']); ?></p>
                            <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                            <p class="meta"><strong>Seller:</strong> <?php echo htmlspecialchars($row['seller_email']); ?></p>

                            <?php
                            $desc = $row['description'];
                            if (strlen($desc) > 70) {
                                $desc = substr($desc, 0, 70) . "...";
                            }
                            ?>
                            <p class="meta"><strong>Description:</strong> <?php echo htmlspecialchars($desc); ?></p>

                            <div class="product-actions" style="display:flex; gap:10px; flex-wrap:wrap;">
                                <a href="product_details.php?id=<?php echo $row['id']; ?>" class="small-btn primary">View Details</a>

                                <?php if ($row['status'] !== 'sold'): ?>
                                   <button class="small-btn dark add-to-cart-btn" data-id="<?php echo $row['id']; ?>">
    Add to Cart
</button>
                                <?php else: ?>
                                    <button type="button" class="small-btn dark" disabled style="opacity:0.65; cursor:not-allowed;">Sold</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-state">No matching products found.</p>
        <?php endif; ?>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>

<?php if ($triggerCartAnimation): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const cart = document.getElementById("floatingCart");
    const toast = document.getElementById("cartAddedToast");

    if (cart) {
        cart.classList.add("cart-bounce");
        setTimeout(() => {
            cart.classList.remove("cart-bounce");
        }, 800);
    }

    if (toast) {
        toast.classList.add("show");
        setTimeout(() => {
            toast.classList.remove("show");
        }, 1800);
    }
});
</script>
<?php endif; ?>

</body>
</html>