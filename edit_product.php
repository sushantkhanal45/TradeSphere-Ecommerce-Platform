<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id, email 
    FROM users 
    WHERE email='$userEmail' 
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

if (!isset($_GET['id'])) {
    header("Location: profile.php#listings");
    exit();
}

$productId = (int)$_GET['id'];

$productRes = $conn->query("
    SELECT * 
    FROM products 
    WHERE id=$productId 
    AND user_id=$userId
    LIMIT 1
");

$product = $productRes ? $productRes->fetch_assoc() : null;

if (!$product) {
    die("Product not found or access denied.");
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$success = "";
$error = "";

if (isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? "");
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $productCondition = trim($_POST['product_condition'] ?? "");
    $price = trim($_POST['price'] ?? "");
    $city = trim($_POST['city'] ?? "");
    $description = trim($_POST['description'] ?? "");
    $status = trim($_POST['status'] ?? "");

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
    } elseif (!is_numeric($price) || (float)$price <= 0) {
        $error = "Price must be greater than 0.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $city)) {
        $error = "Please enter a valid city name.";
    } elseif (!in_array($status, ['available', 'sold'], true)) {
        $error = "Invalid product status.";
    } else {
        $oldProduct = $product;

        $safeName = $conn->real_escape_string($name);
        $safePrice = $conn->real_escape_string($price);
        $safeCity = $conn->real_escape_string($city);
        $safeDescription = $conn->real_escape_string($description);
        $safeStatus = $conn->real_escape_string($status);
        $safeCondition = $conn->real_escape_string($productCondition);

        $imageName = $product['image'];

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            $originalName = basename($_FILES['image']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($fileType, $allowedTypes, true) || !in_array($extension, $allowedExtensions, true)) {
                $error = "Only JPG, PNG, WEBP, or GIF images are allowed.";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = "Image size must be less than 5MB.";
            } else {
                $newImage = time() . "_" . uniqid() . "." . $extension;
                $tmp = $_FILES['image']['tmp_name'];
                $target = "uploads/" . $newImage;

                if (move_uploaded_file($tmp, $target)) {
                    $imageName = $newImage;
                } else {
                    $error = "Image upload failed.";
                }
            }
        }

        if ($error === "") {
            $safeImage = $conn->real_escape_string($imageName);

            $wasRejected = (($oldProduct['ai_status'] ?? '') === 'rejected');

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
            ";

            if ($wasRejected) {
                $update .= ",
                    ai_status='manual_review',
                    ai_reason='Product resubmitted by seller after correction.'
                ";
            }

            $update .= "
                WHERE id=$productId 
                AND user_id=$userId
            ";

            if ($conn->query($update)) {
                $actionData = json_encode([
                    "action" => $wasRejected ? "product_resubmitted" : "product_updated",
                    "user_id" => $userId,
                    "product_id" => $productId,
                    "old_product" => [
                        "name" => $oldProduct['name'],
                        "category_id" => $oldProduct['category_id'],
                        "price" => $oldProduct['price'],
                        "city" => $oldProduct['city'],
                        "description" => $oldProduct['description'],
                        "product_condition" => $oldProduct['product_condition'],
                        "status" => $oldProduct['status'],
                        "image" => $oldProduct['image'],
                        "ai_status" => $oldProduct['ai_status'] ?? '',
                        "ai_reason" => $oldProduct['ai_reason'] ?? ''
                    ],
                    "new_product" => [
                        "name" => $name,
                        "category_id" => $categoryId,
                        "price" => $price,
                        "city" => $city,
                        "description" => $description,
                        "product_condition" => $productCondition,
                        "status" => $status,
                        "image" => $imageName,
                        "ai_status" => $wasRejected ? "manual_review" : ($oldProduct['ai_status'] ?? ''),
                        "ai_reason" => $wasRejected ? "Product resubmitted by seller after correction." : ($oldProduct['ai_reason'] ?? '')
                    ],
                    "timestamp" => date("Y-m-d H:i:s")
                ]);

                $signature = signData($actionData);

                if ($signature) {
                    storeSignatureRecord(
                        $conn,
                        $userId,
                        $wasRejected ? "product_resubmitted" : "product_updated",
                        $productId,
                        $actionData,
                        $signature
                    );
                }

                if ($wasRejected) {
                    $success = "Product updated and resubmitted for admin verification.";
                } else {
                    $success = "Product updated successfully.";
                }

                $productRes = $conn->query("
                    SELECT * 
                    FROM products 
                    WHERE id=$productId 
                    AND user_id=$userId
                    LIMIT 1
                ");

                $product = $productRes ? $productRes->fetch_assoc() : null;
            } else {
                $error = "Could not update product.";
            }
        }
    }
}

$backLink = "profile.php#listings";

if (($product['ai_status'] ?? '') === 'manual_review') {
    $backLink = "profile.php#pending-verification";
} elseif (($product['ai_status'] ?? '') === 'rejected') {
    $backLink = "profile.php#rejected-products";
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

<?php include "includes/navbar.php"; ?>

<div class="form-page">
    <div class="form-card">
        <h2>
            <?php echo (($product['ai_status'] ?? '') === 'rejected') ? 'Edit & Resubmit Product' : 'Edit Product'; ?>
        </h2>

        <p class="helper">
            <?php if (($product['ai_status'] ?? '') === 'rejected'): ?>
                Update your rejected product details and resubmit it for admin verification.
            <?php else: ?>
                Update your product details and change its availability status when needed.
            <?php endif; ?>
        </p>

        <?php if (!empty($product['ai_status'])): ?>
            <div class="info-msg" style="margin-bottom:15px;">
                <strong>Verification Status:</strong>
                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $product['ai_status']))); ?>

                <?php if (!empty($product['ai_reason'])): ?>
                    <br>
                    <strong>Reason:</strong>
                    <?php echo htmlspecialchars($product['ai_reason']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
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
                    <option value="Old" <?php echo ($product['product_condition'] === 'Old') ? 'selected' : ''; ?>>Old</option>
                    <option value="Refurbished" <?php echo ($product['product_condition'] === 'Refurbished') ? 'selected' : ''; ?>>Refurbished</option>
                </select>
            </div>

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" min="1" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
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
                <img 
                    src="uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                    alt="Current Product Image" 
                    style="width: 140px; border-radius: 12px; margin-top: 8px;"
                >
            </div>

            <div class="form-group">
                <label>Change Image (Optional)</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" name="update_product" class="btn btn-primary">
                    <?php echo (($product['ai_status'] ?? '') === 'rejected') ? 'Update & Resubmit' : 'Update Product'; ?>
                </button>

                <a href="<?php echo htmlspecialchars($backLink); ?>" class="btn btn-dark">
                    Back
                </a>
            </div>
        </form>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>

<?php if ($success): ?>
<script>
if (typeof showToast === "function") {
    showToast("<?php echo addslashes($success); ?>", "success");
}
</script>
<?php endif; ?>

</body>
</html>