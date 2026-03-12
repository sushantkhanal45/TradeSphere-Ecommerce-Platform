<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['user'];
$userRes = $conn->query("SELECT id, name, email FROM users WHERE email='$userEmail'");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$success = "";
$error = "";

/* Add product */
if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $seller_email = $user['email'];

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    if ($name === "" || $category === "" || $price === "" || $city === "" || $description === "" || $image === "") {
        $error = "Please fill in all fields.";
    } else {
        $target = "uploads/" . basename($image);

        if (move_uploaded_file($tmp, $target)) {
            $stmt = "
                INSERT INTO products (user_id, name, category, price, city, seller_email, image, description, status)
                VALUES ('$userId', '$name', '$category', '$price', '$city', '$seller_email', '$image', '$description', 'available')
            ";

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

/* Toggle status */
if (isset($_POST['toggle_status'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId AND user_id=$userId");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $newStatus = ($product['status'] === 'sold') ? 'available' : 'sold';
        $conn->query("UPDATE products SET status='$newStatus' WHERE id=$productId AND user_id=$userId");
        header("Location: sell.php");
        exit();
    }
}

$myProducts = $conn->query("SELECT * FROM products WHERE user_id=$userId ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Product - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <a href="index.php">TradeSphere</a>
        </div>

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
<?php
$cartCount = 0;

if (isset($_SESSION['user'])) {
    $userEmail = $_SESSION['user'];
    $userRes = $conn->query("SELECT id FROM users WHERE email='$userEmail'");
    $userRow = $userRes ? $userRes->fetch_assoc() : null;

    if ($userRow) {
        $userId = (int)$userRow['id'];
        $cartCountRes = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id=$userId");
        $cartCountRow = $cartCountRes ? $cartCountRes->fetch_assoc() : null;
        $cartCount = ($cartCountRow && $cartCountRow['total_items']) ? (int)$cartCountRow['total_items'] : 0;
    }
}
?>

<?php if (isset($_SESSION['user'])): ?>
    <a href="cart.php" class="floating-cart <?php echo ($cartCount > 0) ? 'cart-active' : ''; ?>" title="View Cart">
        🛒
        <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?php echo $cartCount; ?></span>
        <?php endif; ?>
    </a>
<?php endif; ?>

<a href="cart.php" class="floating-cart" title="View Cart">🛒</a>

<div class="form-page">
    <div class="container">
        <div class="form-card">
            <h2>Sell Your Product</h2>
            <p class="helper">
                Add your product to the TradeSphere marketplace. Product status will be available by default and can be updated later by you.
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
                    <label>Category</label>
                    <input type="text" name="category" placeholder="e.g. Electronics, Books, Fashion" required>
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
                    <label>Description</label>
                    <textarea name="description" placeholder="Write product details..." required></textarea>
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

        <div class="seller-listings-section">
            <h2 class="section-title">My Listings</h2>
            <p class="section-subtitle">
                Manage your products here. You can edit details and change the status of your own listings.
            </p>

            <?php if ($myProducts && $myProducts->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $myProducts->fetch_assoc()): ?>
                        <div class="product-card seller-card">
                            <div class="product-image-wrap">
                                <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Product Image">
                                <?php if ($row['status'] === 'sold'): ?>
                                    <div class="sold-badge">SOLD</div>
                                <?php endif; ?>

                                <div class="card-menu">
                                    <button type="button" class="card-menu-btn" onclick="toggleMenu(<?php echo $row['id']; ?>)">⋮</button>

                                    <div class="card-menu-dropdown" id="menu-<?php echo $row['id']; ?>">
                                        <a href="edit_product.php?id=<?php echo $row['id']; ?>">Edit Product</a>

                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="toggle_status" class="menu-action-btn">
                                                <?php echo ($row['status'] === 'sold') ? 'Mark as Available' : 'Mark as Sold'; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="product-body">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                                <p class="price">Rs <?php echo htmlspecialchars($row['price']); ?></p>
                                <p class="meta"><strong>Category:</strong> <?php echo htmlspecialchars($row['category']); ?></p>
                                <p class="meta"><strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?></p>
                                <p class="meta"><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">You have not listed any products yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    © 2026 TradeSphere. All rights reserved.
</footer>

<script src="js/script.js"></script>
<script>
function toggleMenu(id) {
    const menu = document.getElementById("menu-" + id);
    const allMenus = document.querySelectorAll(".card-menu-dropdown");

    allMenus.forEach(item => {
        if (item !== menu) {
            item.style.display = "none";
        }
    });

    if (menu.style.display === "block") {
        menu.style.display = "none";
    } else {
        menu.style.display = "block";
    }
}

window.addEventListener("click", function(e) {
    if (!e.target.matches(".card-menu-btn")) {
        document.querySelectorAll(".card-menu-dropdown").forEach(menu => {
            menu.style.display = "none";
        });
    }
});
</script>
</body>
</html>