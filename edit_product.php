<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id, email FROM users WHERE email='$userEmail'");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];
$cartCount = 0;

$cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
$cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
$cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;

if (!isset($_GET['id'])) {
    header("Location: sell.php");
    exit();
}

$productId = (int)$_GET['id'];
$productRes = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    die("Product not found or access denied.");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$success = "";
$error = "";

if (isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $categoryId = (int)$_POST['category_id'];
    $productCondition = trim($_POST['product_condition']);
    $price = trim($_POST['price']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);

    if (
        $name === "" ||
        $categoryId <= 0 ||
        $productCondition === "" ||
        $price === "" ||
        $city === "" ||
        $description === "" ||
        $status === ""
    ) {
        $error = "Please fill in all fields.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safePrice = $conn->real_escape_string($price);
        $safeCity = $conn->real_escape_string($city);
        $safeDescription = $conn->real_escape_string($description);
        $safeStatus = $conn->real_escape_string($status);
        $safeCondition = $conn->real_escape_string($productCondition);

        $imageName = $product['image'];

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $newImage = time() . "_" . basename($_FILES['image']['name']);
            $tmp = $_FILES['image']['tmp_name'];
            $target = "uploads/" . $newImage;

            if (move_uploaded_file($tmp, $target)) {
                $imageName = $newImage;
            }
        }

        $safeImage = $conn->real_escape_string($imageName);

        $update = "
            UPDATE products
            SET name='$safeName',
                category_id='$categoryId',
                price='$safePrice',
                city='$safeCity',
                description='$safeDescription',
                product_condition='$safeCondition',
                status='$safeStatus',
                image='$safeImage'
            WHERE id=$productId AND user_id=$userId
        ";

        if ($conn->query($update)) {
            $success = "Product updated successfully.";
            $productRes = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
            $product = $productRes ? $productRes->fetch_assoc() : null;
        } else {
            $error = "Could not update product.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo"><a href="index.php">TradeSphere</a></div>
        <div class="menu-toggle" id="menuToggle">☰</div>
        <div class="nav-links" id="navLinks">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="index.php#categories">Categories</a>
            <a href="sell.php">Sell</a>
            <a href="index.php#about">About</a>
            <a href="index.php#contact">Contact</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        </div>
    </div>
</nav>

<a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
    🛒
    <?php if ($cartCount > 0): ?>
        <span class="cart-count-badge"><?php echo $cartCount; ?></span>
    <?php endif; ?>
</a>

<div class="form-page">
    <div class="form-card">
        <h2>Edit Product</h2>
        <p class="helper">Update your product details and change its availability status when needed.</p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Condition</label>
                <select name="product_condition" required>
                    <option value="New" <?php echo ($product['product_condition'] === 'New') ? 'selected' : ''; ?>>New</option>
                    <option value="Like New" <?php echo ($product['product_condition'] === 'Like New') ? 'selected' : ''; ?>>Like New</option>
                    <option value="Used" <?php echo ($product['product_condition'] === 'Used') ? 'selected' : ''; ?>>Used</option>
                    <option value="Refurbished" <?php echo ($product['product_condition'] === 'Refurbished') ? 'selected' : ''; ?>>Refurbished</option>
                </select>
            </div>

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Price</label>
                <input type="number" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>

            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" value="<?php echo htmlspecialchars($product['city']); ?>" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="available" <?php echo ($product['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                    <option value="sold" <?php echo ($product['status'] === 'sold') ? 'selected' : ''; ?>>Sold</option>
                </select>
            </div>

            <div class="form-group">
                <label>Current Image</label><br>
                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Product Image" style="width: 140px; border-radius: 12px; margin-top: 8px;">
            </div>

            <div class="form-group">
                <label>Change Image (Optional)</label>
                <input type="file" name="image">
            </div>

            <div class="form-actions">
                <button type="submit" name="update_product" class="btn btn-primary">Update Product</button>
                <a href="sell.php" class="btn btn-dark">Back</a>
            </div>
        </form>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>