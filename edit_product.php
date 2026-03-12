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

$success = "";
$error = "";

if (isset($_POST['update_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);

    if ($name === "" || $category === "" || $price === "" || $city === "" || $description === "" || $status === "") {
        $error = "Please fill in all fields.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safeCategory = $conn->real_escape_string($category);
        $safePrice = $conn->real_escape_string($price);
        $safeCity = $conn->real_escape_string($city);
        $safeDescription = $conn->real_escape_string($description);
        $safeStatus = $conn->real_escape_string($status);

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
                category='$safeCategory',
                price='$safePrice',
                city='$safeCity',
                description='$safeDescription',
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
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
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