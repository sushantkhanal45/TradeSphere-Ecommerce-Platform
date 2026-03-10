<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$success = "";
$error = "";

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $city = trim($_POST['city']);
    $seller_email = trim($_POST['seller_email']);
    $status = trim($_POST['status']);

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    if ($name === "" || $price === "" || $city === "" || $seller_email === "" || $image === "") {
        $error = "Please fill in all fields.";
    } else {
        $target = "uploads/" . basename($image);

        if (move_uploaded_file($tmp, $target)) {
            $stmt = "INSERT INTO products (name, price, city, seller_email, image, status)
                     VALUES ('$name', '$price', '$city', '$seller_email', '$image', '$status')";
            if ($conn->query($stmt)) {
                $success = "Your product has been listed successfully.";
            } else {
                $error = "Could not save product.";
            }
        } else {
            $error = "Image upload failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sell Product - TradeSphere</title>
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
            <a href="index.php#about">About</a>
            <a href="index.php#services">Services</a>
            <a href="index.php#contact">Contact</a>
            <a href="sell.php">Sell</a>
            <a href="cart.php">Cart</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        </div>
    </div>
</nav>

<div class="form-page">
    <div class="form-card">
        <h2>Sell Your Product</h2>
        <p class="helper">
            Add your item to the TradeSphere marketplace by filling out the product details below.
        </p>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" placeholder="Enter product name" required>
            </div>

            <div class="form-group">
                <label>Price</label>
                <input type="number" name="price" placeholder="Enter price" required>
            </div>

            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" placeholder="Enter city" required>
            </div>

            <div class="form-group">
                <label>Seller Email</label>
                <input type="email" name="seller_email" value="<?php echo htmlspecialchars($_SESSION['user']); ?>" required>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                </select>
            </div>

            <div class="form-group">
                <label>Product Image</label>
                <input type="file" name="image" required>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit" class="btn btn-primary">Post Product</button>
            </div>
        </form>
    </div>
</div>

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
</body>
</html>