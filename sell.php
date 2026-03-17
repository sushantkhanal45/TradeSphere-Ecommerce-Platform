<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

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

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $categoryId = (int)$_POST['category_id'];
    $productCondition = trim($_POST['product_condition']);
    $price = trim($_POST['price']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    $seller_email = $user['email'];

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    if (
        $name === "" ||
        $categoryId <= 0 ||
        $productCondition === "" ||
        $price === "" ||
        $city === "" ||
        $description === "" ||
        $image === ""
    ) {
        $error = "Please fill in all fields.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $safePrice = $conn->real_escape_string($price);
        $safeCity = $conn->real_escape_string($city);
        $safeDescription = $conn->real_escape_string($description);
        $safeSeller = $conn->real_escape_string($seller_email);
        $safeCondition = $conn->real_escape_string($productCondition);

        $imageName = time() . "_" . basename($image);
        $target = "uploads/" . $imageName;

        if (move_uploaded_file($tmp, $target)) {
            $stmt = "
                INSERT INTO products (
                    user_id,
                    name,
                    category_id,
                    price,
                    city,
                    seller_email,
                    image,
                    description,
                    product_condition,
                    status
                )
                VALUES (
                    '$userId',
                    '$safeName',
                    '$categoryId',
                    '$safePrice',
                    '$safeCity',
                    '$safeSeller',
                    '$imageName',
                    '$safeDescription',
                    '$safeCondition',
                    'available'
                )
            ";

            if ($conn->query($stmt)) {
                $productId = $conn->insert_id;

                $actionData = json_encode([
                    "user_id" => $userId,
                    "product_id" => $productId,
                    "action" => "product_created",
                    "product_name" => $name,
                    "condition" => $productCondition,
                    "timestamp" => date("Y-m-d H:i:s")
                ]);

                $signature = signData($actionData);
                if ($signature) {
                    storeSignatureRecord($conn, $userId, "product_created", $productId, $actionData, $signature);
                }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Product - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="form-page">
    <div class="container">
        <div class="form-card">
            <h2>Sell Your Product</h2>
            <p class="helper">Choose a category and condition, then enter the exact product name and details.</p>

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
                                <option value="<?php echo (int)$cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Condition</label>
                    <select name="product_condition" required>
                        <option value="">Select Condition</option>
                        <option value="New">New</option>
                        <option value="Like New">Like New</option>
                        <option value="Used">Used</option>
                        <option value="Refurbished">Refurbished</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" placeholder="e.g. Samsung Refrigerator, Dell Inspiron Laptop" required>
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
                    <a href="profile.php#listings" class="btn btn-dark">Go to My Listings</a>
                </div>
            </form>
        </div>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>