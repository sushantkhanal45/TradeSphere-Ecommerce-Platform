<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$invalidFields = [];

$old = [
    "name" => "",
    "category_id" => "",
    "price" => "",
    "description" => "",
    "product_condition" => "",
    "city" => "",
    "contact_number" => ""
];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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

/* Add product only if seller is approved */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {

    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? "");
    }

    $name = $old['name'];
    $categoryId = (int)$old['category_id'];
    $priceRaw = $old['price'];
    $description = $old['description'];
    $condition = $old['product_condition'];
    $city = $old['city'];
    $contactNumber = $old['contact_number'];

    if ($user['seller_status'] !== "approved") {
        $error = "You must be verified as a seller before listing products.";
    } else {
        if ($name === "") {
            $invalidFields[] = "name";
        }

        if ($categoryId <= 0) {
            $invalidFields[] = "category_id";
        }

        if ($priceRaw === "" || !is_numeric($priceRaw) || (float)$priceRaw <= 0) {
            $invalidFields[] = "price";
        }

        if ($condition === "") {
            $invalidFields[] = "product_condition";
        }

        if ($city === "") {
            $invalidFields[] = "city";
        }

        if ($contactNumber === "" || !preg_match('/^[0-9]{10}$/', $contactNumber)) {
            $invalidFields[] = "contact_number";
        }

        if ($description === "") {
            $invalidFields[] = "description";
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
            $invalidFields[] = "image";
        }

        if ($name !== "" && strlen($name) < 3) {
            $invalidFields[] = "name";
            $error = "Product name must be at least 3 characters.";
        }

        if ($name !== "" && !preg_match('/[a-zA-Z]/', $name)) {
            $invalidFields[] = "name";
            $error = "Product name must contain meaningful letters.";
        }

        if ($city !== "" && !preg_match('/^[a-zA-Z\s]+$/', $city)) {
            $invalidFields[] = "city";
            $error = "City name should contain letters only.";
        }

        if (!empty($invalidFields) && $error === "") {
            $error = "Please fill all required fields correctly.";
        }

        if ($error === "") {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            $fileType = mime_content_type($_FILES['image']['tmp_name']);
            $originalName = basename($_FILES['image']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($fileType, $allowedTypes, true) || !in_array($extension, $allowedExtensions, true)) {
                $error = "Only image files are allowed. Please upload JPG, PNG, WEBP, or GIF.";
                $invalidFields[] = "image";
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = "Image size must be less than 5MB.";
                $invalidFields[] = "image";
            } else {
                $uploadDir = "uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newImageName = time() . "_" . uniqid() . "." . $extension;
                $targetPath = $uploadDir . $newImageName;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $error = "Image upload failed. Please try again.";
                    $invalidFields[] = "image";
                } else {
                    $safeName = $conn->real_escape_string($name);
                    $safeDescription = $conn->real_escape_string($description);
                    $safeCondition = $conn->real_escape_string($condition);
                    $safeCity = $conn->real_escape_string($city);
                    $safeContact = $conn->real_escape_string($contactNumber);
                    $safeEmail = $conn->real_escape_string($user['email']);
                    $safeImage = $conn->real_escape_string($newImageName);
                    $priceValue = (float)$priceRaw;

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
                        foreach ($old as $key => $value) {
                            $old[$key] = "";
                        }
                    } else {
                        $error = "Could not list product. " . $conn->error;
                    }
                }
            }
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

function fieldClass($name, $invalidFields) {
    return in_array($name, $invalidFields, true) ? "form-group field-error" : "form-group";
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

        .sell-form-card{
            max-width: 820px;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.10);
            padding: 30px;
            margin: 0 auto 30px;
        }

        .required-star{
            color: #dc2626;
            font-weight: 800;
            display: none;
            margin-left: 4px;
        }

        .submitted .field-error .required-star{
            display: inline;
        }

        .field-error input,
        .field-error select,
        .field-error textarea,
        .field-error .upload-box{
            border-color: #dc2626 !important;
            background: #fff7f7;
        }

        .field-error .field-help{
            color: #dc2626;
            display: block;
        }

        .field-help{
            display: block;
            font-size: 13px;
            color: #64748b;
            margin-top: 6px;
        }

        .form-grid-2{
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .upload-box{
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 22px;
            text-align: center;
            cursor: pointer;
            background: #f8fafc;
            transition: 0.2s ease;
        }

        .upload-box:hover{
            border-color: #2563eb;
            background: #eff6ff;
        }

        .upload-icon{
            font-size: 34px;
            display: block;
            margin-bottom: 8px;
        }

        .upload-title{
            font-weight: 700;
            color: #111827;
        }

        .upload-subtitle{
            color: #64748b;
            font-size: 13px;
            margin-top: 5px;
        }

        .upload-box input{
            display: none;
        }

        .file-name-preview{
            margin-top: 10px;
            font-weight: 600;
            color: #2563eb;
            font-size: 14px;
        }

        .toast{
            position: fixed;
            top: 24px;
            right: 24px;
            min-width: 280px;
            max-width: 420px;
            padding: 14px 18px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .toast.show{
            opacity: 1;
            transform: translateY(0);
        }

        .toast.success{
            background: #16a34a;
        }

        .toast.error{
            background: #dc2626;
        }

        .toast.warning{
            background: #f59e0b;
        }

        @media (max-width: 768px){
            .form-grid-2{
                grid-template-columns: 1fr;
            }

            .sell-form-card{
                padding: 22px;
            }
        }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
    <div class="container">
        <h1 class="section-title">Sell a Product</h1>
        <p class="section-subtitle">List your product on TradeSphere after seller verification.</p>

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

            <div class="sell-form-card">
                <span class="status-pill status-approved">Verified Seller</span>
                <h2>Add Product Listing</h2>
                <p class="helper">Upload product details and image to list your item.</p>

                <form method="POST" enctype="multipart/form-data" id="sellForm" class="<?php echo !empty($invalidFields) ? 'submitted' : ''; ?>" novalidate>

                    <div class="<?php echo fieldClass('name', $invalidFields); ?>">
                        <label>Product Name <span class="required-star">*</span></label>
                        <input type="text" name="name" placeholder="Enter product name" value="<?php echo h($old['name']); ?>" data-required="1">
                        <small class="field-help">Product name must be meaningful and at least 3 characters.</small>
                    </div>

                    <div class="form-grid-2">
                        <div class="<?php echo fieldClass('category_id', $invalidFields); ?>">
                            <label>Category <span class="required-star">*</span></label>
                            <select name="category_id" data-required="1">
                                <option value="">Select Category</option>
                                <?php if ($categories && $categories->num_rows > 0): ?>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$old['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo h($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <small class="field-help">Please select a category.</small>
                        </div>

                        <div class="<?php echo fieldClass('price', $invalidFields); ?>">
                            <label>Price <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="1" name="price" placeholder="Enter price" value="<?php echo h($old['price']); ?>" data-required="1">
                            <small class="field-help">Price must be greater than 0.</small>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="<?php echo fieldClass('product_condition', $invalidFields); ?>">
                            <label>Condition <span class="required-star">*</span></label>
                            <select name="product_condition" data-required="1">
                                <option value="">Select Condition</option>
                                <option value="New" <?php echo $old['product_condition'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $old['product_condition'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Used" <?php echo $old['product_condition'] === 'Used' ? 'selected' : ''; ?>>Used</option>
                                <option value="Old" <?php echo $old['product_condition'] === 'Old' ? 'selected' : ''; ?>>Old</option>
                            </select>
                            <small class="field-help">Please select product condition.</small>
                        </div>

                        <div class="<?php echo fieldClass('city', $invalidFields); ?>">
                            <label>City <span class="required-star">*</span></label>
                            <input type="text" name="city" placeholder="Enter city/location" value="<?php echo h($old['city']); ?>" data-required="1">
                            <small class="field-help">City should contain letters only.</small>
                        </div>
                    </div>

                    <div class="<?php echo fieldClass('contact_number', $invalidFields); ?>">
                        <label>Contact Number <span class="required-star">*</span></label>
                        <input type="text" name="contact_number" maxlength="10" placeholder="10 digit contact number" value="<?php echo h($old['contact_number']); ?>" data-required="1">
                        <small class="field-help">Contact number must be exactly 10 digits.</small>
                    </div>

                    <div class="<?php echo fieldClass('description', $invalidFields); ?>">
                        <label>Description <span class="required-star">*</span></label>
                        <textarea name="description" rows="5" placeholder="Describe your product" data-required="1"><?php echo h($old['description']); ?></textarea>
                        <small class="field-help">Please describe your product.</small>
                    </div>

                    <div class="<?php echo fieldClass('image', $invalidFields); ?>">
                        <label>Product Image <span class="required-star">*</span></label>

                        <label class="upload-box" for="productImage">
                            <span class="upload-icon">📷</span>
                            <div class="upload-title">Click to upload product image</div>
                            <div class="upload-subtitle">Only JPG, PNG, WEBP, or GIF. Max size 5MB.</div>
                            <input type="file" id="productImage" name="image" accept="image/*" data-required="1">
                            <div id="fileNamePreview" class="file-name-preview"></div>
                        </label>

                        <small class="field-help">Please upload a valid image file.</small>
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

<div id="toast" class="toast"></div>

<script src="js/script.js"></script>

<script>
function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    if (!toast || !message) return;

    toast.innerText = message;
    toast.className = "toast show " + type;

    setTimeout(() => {
        toast.classList.remove("show");
    }, 3500);
}

const sellForm = document.getElementById("sellForm");

if (sellForm) {
    sellForm.addEventListener("submit", function(e) {
        sellForm.classList.add("submitted");

        let valid = true;

        sellForm.querySelectorAll(".form-group").forEach(group => {
            group.classList.remove("field-error");
        });

        const name = sellForm.querySelector("[name='name']");
        const category = sellForm.querySelector("[name='category_id']");
        const price = sellForm.querySelector("[name='price']");
        const condition = sellForm.querySelector("[name='product_condition']");
        const city = sellForm.querySelector("[name='city']");
        const contact = sellForm.querySelector("[name='contact_number']");
        const description = sellForm.querySelector("[name='description']");
        const image = sellForm.querySelector("[name='image']");

        function markInvalid(input) {
            valid = false;
            const group = input.closest(".form-group");
            if (group) group.classList.add("field-error");
        }

        if (!name.value.trim() || name.value.trim().length < 3 || !/[a-zA-Z]/.test(name.value)) {
            markInvalid(name);
        }

        if (!category.value) {
            markInvalid(category);
        }

        if (!price.value || parseFloat(price.value) <= 0) {
            markInvalid(price);
        }

        if (!condition.value) {
            markInvalid(condition);
        }

        if (!city.value.trim() || !/^[a-zA-Z\s]+$/.test(city.value.trim())) {
            markInvalid(city);
        }

        if (!/^[0-9]{10}$/.test(contact.value.trim())) {
            markInvalid(contact);
        }

        if (!description.value.trim()) {
            markInvalid(description);
        }

        if (!image.files || image.files.length === 0) {
            markInvalid(image);
        } else {
            const allowed = ["image/jpeg", "image/png", "image/webp", "image/gif"];
            if (!allowed.includes(image.files[0].type) || image.files[0].size > 5 * 1024 * 1024) {
                markInvalid(image);
            }
        }

        if (!valid) {
            e.preventDefault();
            showToast("Please fill all required fields correctly.", "error");
        }
    });
}

const productImage = document.getElementById("productImage");
const fileNamePreview = document.getElementById("fileNamePreview");

if (productImage && fileNamePreview) {
    productImage.addEventListener("change", function() {
        if (this.files && this.files[0]) {
            fileNamePreview.textContent = "Selected: " + this.files[0].name;
        } else {
            fileNamePreview.textContent = "";
        }
    });
}

<?php if ($success): ?>
showToast("<?php echo addslashes($success); ?>", "success");
<?php endif; ?>

<?php if ($error): ?>
showToast("<?php echo addslashes($error); ?>", "error");
<?php endif; ?>
</script>

</body>
</html>