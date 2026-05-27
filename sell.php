<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

$userEmail = $conn->real_escape_string($_SESSION['user']);

$userRes = $conn->query("
    SELECT id, name, email, seller_status
    FROM users
    WHERE email='$userEmail'
    LIMIT 1
");

$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    header("Location: logout.php");
    exit();
}

$userId = (int)$user['id'];

if (!isset($user['seller_status']) || $user['seller_status'] === "") {
    $user['seller_status'] = "none";
}

/* Request seller verification */
if (isset($_POST['request_seller'])) {
    $conn->query("
        UPDATE users
        SET seller_status='pending',
            seller_requested_at=NOW()
        WHERE id=$userId
    ");

    $user['seller_status'] = "pending";
    $success = "Your seller verification request has been sent to admin.";
}

/* Load categories */
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

/* Add product only if seller is approved */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {

    if ($user['seller_status'] !== "approved") {
        $error = "You must be verified as a seller before listing products.";
    } else {
        $name = trim($_POST['name']);
        $categoryId = (int)$_POST['category_id'];
        $price = trim($_POST['price']);
        $description = trim($_POST['description']);
        $condition = trim($_POST['product_condition']);
        $city = trim($_POST['city']);
        $contactNumber = trim($_POST['contact_number']);

        if (
            $name === "" ||
            $categoryId <= 0 ||
            $price === "" ||
            $description === "" ||
            $condition === "" ||
            $city === "" ||
            $contactNumber === ""
        ) {
            $error = "Please fill in all required fields.";
        } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
            $error = "Please upload a product image.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);

            if (!in_array($fileType, $allowedTypes, true)) {
                $error = "Only image files are allowed. Please upload JPG, PNG, WEBP, or GIF.";
            } else {
                $uploadDir = "uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $originalName = basename($_FILES['image']['name']);
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($extension, $allowedExtensions, true)) {
                    $error = "Invalid image extension. Only JPG, PNG, WEBP, and GIF are allowed.";
                } else {
                    $newImageName = time() . "_" . uniqid() . "." . $extension;
                    $targetPath = $uploadDir . $newImageName;

                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $error = "Image upload failed. Please try again.";
                    } else {
                        $safeName = $conn->real_escape_string($name);
                        $safeDescription = $conn->real_escape_string($description);
                        $safeCondition = $conn->real_escape_string($condition);
                        $safeCity = $conn->real_escape_string($city);
                        $safeContact = $conn->real_escape_string($contactNumber);
                        $safeEmail = $conn->real_escape_string($user['email']);
                        $safeImage = $conn->real_escape_string($newImageName);
                        $priceValue = (float)$price;

                        $insert = $conn->query("
                            INSERT INTO products
                            (
                                user_id,
                                name,
                                category_id,
                                price,
                                description,
                                product_condition,
                                city,
                                contact_number,
                                seller_email,
                                image,
                                status
                            )
                            VALUES
                            (
                                $userId,
                                '$safeName',
                                $categoryId,
                                $priceValue,
                                '$safeDescription',
                                '$safeCondition',
                                '$safeCity',
                                '$safeContact',
                                '$safeEmail',
                                '$safeImage',
                                'available'
                            )
                        ");

                        if ($insert) {
                            $success = "Product listed successfully.";
                        } else {
                            $error = "Could not list product. " . $conn->error;
                        }
                    }
                }
            }
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
    <style>
        .seller-verification-box{
            max-width: 720px;
            margin: 30px auto;
            background: #fff;
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
        }

        .seller-verification-box h3{
            margin-bottom: 12px;
        }

        .seller-verification-box p{
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 18px;
        }

        .status-pill{
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .status-none{
            background: #fef3c7;
            color: #92400e;
        }

        .status-pending{
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-rejected{
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-approved{
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Sell a Product</h1>
        <p class="section-subtitle">List your product on TradeSphere after seller verification.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($user['seller_status'] !== "approved"): ?>

            <div class="seller-verification-box">

                <?php if ($user['seller_status'] === "none"): ?>
                    <span class="status-pill status-none">Not Verified as Seller</span>
                    <h3>Seller Verification Required</h3>
                    <p>
                        To start selling on TradeSphere, you must request seller verification.
                        After submitting the request, admin will review and approve your seller access.
                    </p>

                    <form method="POST">
                        <button type="submit" name="request_seller" class="btn btn-primary">
                            Request Seller Verification
                        </button>
                    </form>

                <?php elseif ($user['seller_status'] === "pending"): ?>
                    <span class="status-pill status-pending">Pending Approval</span>
                    <h3>Your Request is Under Review</h3>
                    <p>
                        Your seller verification request has been sent to admin.
                        You can start listing products after admin approval.
                    </p>

                <?php elseif ($user['seller_status'] === "rejected"): ?>
                    <span class="status-pill status-rejected">Request Rejected</span>
                    <h3>Seller Verification Rejected</h3>
                    <p>
                        Your seller verification request was rejected by admin.
                        Please contact the administrator for more information.
                    </p>

                    <form method="POST">
                        <button type="submit" name="request_seller" class="btn btn-primary">
                            Request Again
                        </button>
                    </form>
                <?php endif; ?>

            </div>

        <?php else: ?>

            <div class="form-card" style="max-width: 760px;">
                <span class="status-pill status-approved">Verified Seller</span>
                <h2>Add Product Listing</h2>
                <p class="helper">Upload product details and image to list your item.</p>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" placeholder="Enter product name" required>
                    </div>

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
                        <label>Price</label>
                        <input type="number" step="0.01" name="price" placeholder="Enter price" required>
                    </div>

                    <div class="form-group">
                        <label>Condition</label>
                        <select name="product_condition" required>
                            <option value="">Select Condition</option>
                            <option value="New">New</option>
                            <option value="Like New">Like New</option>
                            <option value="Used">Used</option>
                            <option value="Old">Old</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" placeholder="Enter city/location" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" placeholder="Enter contact number" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="5" placeholder="Describe your product" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" name="image" accept="image/*" required>
                        <small class="helper">Only image files are allowed: JPG, PNG, WEBP, GIF.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_product" class="btn btn-primary">
                            List Product
                        </button>
                    </div>

                </form>
            </div>

        <?php endif; ?>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>
</body>
</html>