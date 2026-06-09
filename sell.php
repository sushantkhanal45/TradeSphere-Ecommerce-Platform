<?php
session_start();
include "config/db.php";
include "includes/rsa_helper.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeProductText($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function wordCountClean($text) {
    $text = normalizeProductText($text);
    if ($text === '') return 0;
    return count(array_filter(explode(' ', $text)));
}

function hasRepeatedCharactersOnly($text) {
    $clean = preg_replace('/\s+/', '', strtolower((string)$text));
    return $clean !== '' && preg_match('/^(.)\1+$/', $clean);
}

function looksLikeGibberish($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z\s]/', '', $text);
    $words = array_filter(explode(' ', $text));

    if (empty($words)) return true;

    $badWords = 0;

    foreach ($words as $word) {
        if (strlen($word) >= 8) {
            $vowels = preg_match_all('/[aeiou]/', $word);
            $letters = strlen($word);
            $vowelRatio = $vowels / max(1, $letters);

            if ($vowelRatio < 0.20 || $vowelRatio > 0.75) {
                $badWords++;
            }

            if (preg_match('/[bcdfghjklmnpqrstvwxyz]{5,}/', $word)) {
                $badWords++;
            }
        }
    }

    return $badWords > 0;
}

function runProductRuleVerification($name, $description, $city, $condition) {
    $reasons = [];
    $manualReasons = [];

    $cleanName = normalizeProductText($name);
    $cleanDescription = normalizeProductText($description);
    $cleanCity = normalizeProductText($city);

    $blockedWords = [
        'asdf', 'qwerty', 'lorem ipsum', 'dummy', 'spam',
        'scam', 'fake product', 'illegal', 'test product',
        'hack', 'weapon', 'drugs', 'adult', 'gambling'
    ];

    foreach ($blockedWords as $word) {
        if (strpos($cleanName, $word) !== false || strpos($cleanDescription, $word) !== false) {
            $reasons[] = "Product contains inappropriate, unsafe, or test-like words.";
            break;
        }
    }

    if ($cleanName === '' || strlen($cleanName) < 3) {
        $reasons[] = "Product name is too short or unclear.";
    }

    if (!preg_match('/[a-z]/', $cleanName)) {
        $reasons[] = "Product name must contain meaningful letters.";
    }

    if (hasRepeatedCharactersOnly($name)) {
        $reasons[] = "Product name appears to be repeated random characters.";
    }

    if (looksLikeGibberish($name)) {
        $reasons[] = "Product name appears to be random or meaningless text.";
    }

    if ($cleanDescription === '' || !preg_match('/[a-z]/', $cleanDescription)) {
        $reasons[] = "Product description must be meaningful.";
    }

    if (hasRepeatedCharactersOnly($description)) {
        $reasons[] = "Product description appears to be repeated random characters.";
    }

    if (looksLikeGibberish($description)) {
        $reasons[] = "Product description appears to be random or meaningless text.";
    }

    if ($cleanCity === '' || strlen($cleanCity) < 2 || !preg_match('/^[a-z\s]+$/', $cleanCity)) {
        $reasons[] = "City name is invalid.";
    }

    if (wordCountClean($description) < 4) {
        $manualReasons[] = "Product description is too short and needs admin review.";
    }

    if (strlen($cleanDescription) < 25) {
        $manualReasons[] = "Product description lacks enough detail.";
    }

    if (wordCountClean($name) < 1) {
        $manualReasons[] = "Product name needs more detail.";
    }

    if ($condition === '') {
        $manualReasons[] = "Product condition is missing.";
    }

    if (!empty($reasons)) {
        return [
            "status" => "blocked",
            "reason" => implode(" ", array_unique($reasons))
        ];
    }

    if (!empty($manualReasons)) {
        return [
            "status" => "manual_review",
            "reason" => implode(" ", array_unique($manualReasons))
        ];
    }

    return [
        "status" => "approved",
        "reason" => "Product passed rule-based verification."
    ];
}

function insertSellerNotification($conn, $userId, $message) {
    $userId = (int)$userId;
    $safeMessage = $conn->real_escape_string($message);

    $conn->query("
        INSERT INTO notifications (user_id, order_id, message)
        VALUES ($userId, NULL, '$safeMessage')
    ");
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

/* Add Product */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_product'])) {
    foreach ($old as $key => $value) {
        $old[$key] = trim($_POST[$key] ?? "");
    }

    $name = $old['name'];
    $categoryId = (int)$old['category_id'];
    $price = $old['price'];
    $description = $old['description'];
    $condition = $old['product_condition'];
    $city = $old['city'];
    $contactNumber = $old['contact_number'];

    $errors = [];

    if ($user['seller_status'] !== "approved") {
        $errors[] = "You must be verified as a seller before listing products.";
    }

    if ($name === "") {
        $invalidFields[] = "name";
        $errors[] = "Product name is required.";
    } elseif (!preg_match('/[a-zA-Z]/', $name)) {
        $invalidFields[] = "name";
        $errors[] = "Product name must be meaningful.";
    } elseif (hasRepeatedCharactersOnly($name) || looksLikeGibberish($name)) {
        $invalidFields[] = "name";
        $errors[] = "Product name must be meaningful.";
    }

    if ($categoryId <= 0) {
        $invalidFields[] = "category_id";
        $errors[] = "Please select a category.";
    }

    if ($price === "" || !is_numeric($price) || (float)$price <= 0) {
        $invalidFields[] = "price";
        $errors[] = "Price must be greater than 0.";
    }

    if ($condition === "") {
        $invalidFields[] = "product_condition";
        $errors[] = "Please select product condition.";
    }

    if ($city === "" || strlen($city) < 2 || !preg_match('/^[a-zA-Z\s]+$/', $city)) {
        $invalidFields[] = "city";
        $errors[] = "Please enter a valid city name.";
    }

    if (!preg_match('/^[0-9]{10}$/', $contactNumber)) {
        $invalidFields[] = "contact_number";
        $errors[] = "Contact number must contain exactly 10 digits.";
    }

    if ($description === "") {
        $invalidFields[] = "description";
        $errors[] = "Please describe your product.";
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        $invalidFields[] = "image";
        $errors[] = "Please upload a product image.";
    }

    if (empty($errors)) {
        $verification = runProductRuleVerification($name, $description, $city, $condition);

        if ($verification['status'] === "blocked") {
            $invalidFields[] = "name";
            $invalidFields[] = "description";

            $errors[] = "Product could not be listed. " . $verification['reason'];

            insertSellerNotification(
                $conn,
                $userId,
                "Your product was not listed because it failed automatic verification: " . $verification['reason']
            );
        }
    }

    if (empty($errors)) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        $originalName = basename($_FILES['image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($fileType, $allowedTypes, true) || !in_array($extension, $allowedExtensions, true)) {
            $invalidFields[] = "image";
            $errors[] = "Only image files are allowed. Please upload JPG, PNG, WEBP, or GIF.";
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $invalidFields[] = "image";
            $errors[] = "Image size must be less than 5MB.";
        } else {
            $uploadDir = "uploads/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newImageName = time() . "_" . uniqid() . "." . $extension;
            $targetPath = $uploadDir . $newImageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $invalidFields[] = "image";
                $errors[] = "Image upload failed. Please try again.";
            } else {
                $safeName = $conn->real_escape_string($name);
                $safeDescription = $conn->real_escape_string($description);
                $safeCondition = $conn->real_escape_string($condition);
                $safeCity = $conn->real_escape_string($city);
                $safeContact = $conn->real_escape_string($contactNumber);
                $safeEmail = $conn->real_escape_string($user['email']);
                $safeImage = $conn->real_escape_string($newImageName);
                $priceValue = (float)$price;

                $aiStatus = ($verification['status'] === "manual_review") ? "manual_review" : "approved";
                $aiReason = $verification['reason'];

                $safeAiStatus = $conn->real_escape_string($aiStatus);
                $safeAiReason = $conn->real_escape_string($aiReason);

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
                        status,
                        ai_status,
                        ai_reason
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
                        'available',
                        '$safeAiStatus',
                        '$safeAiReason'
                    )
                ");

                if ($insert) {
                    $productId = (int)$conn->insert_id;

                    $actionData = json_encode([
                        "user_id" => $userId,
                        "product_id" => $productId,
                        "action" => "product_created",
                        "product_name" => $name,
                        "ai_status" => $aiStatus,
                        "ai_reason" => $aiReason,
                        "timestamp" => date("Y-m-d H:i:s")
                    ]);

                    $signature = signData($actionData);
                    if ($signature) {
                        storeSignatureRecord($conn, $userId, "product_created", $productId, $actionData, $signature);
                    }

                    if ($aiStatus === "approved") {
                        $success = "Product listed successfully.";
                    } else {
                        $success = "Product submitted for admin review. It will appear after approval.";

                        insertSellerNotification(
                            $conn,
                            $userId,
                            "Your product '" . $name . "' was sent for admin review. Reason: " . $aiReason
                        );
                    }

                    foreach ($old as $key => $value) {
                        $old[$key] = "";
                    }
                } else {
                    $errors[] = "Could not list product. " . $conn->error;
                }
            }
        }
    }

    if (!empty($errors)) {
        $error = implode("\n", array_unique($errors));
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
    <title>Sell Product - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .seller-verification-box{max-width:720px;margin:30px auto;background:#fff;padding:28px;border-radius:18px;box-shadow:0 10px 30px rgba(0,0,0,0.08);text-align:center;}
        .seller-verification-box h3{margin-bottom:12px;}
        .seller-verification-box p{color:#4b5563;line-height:1.6;margin-bottom:18px;}
        .status-pill{display:inline-block;padding:8px 14px;border-radius:999px;font-weight:700;font-size:14px;margin-bottom:16px;}
        .status-none{background:#fef3c7;color:#92400e;}
        .status-pending{background:#dbeafe;color:#1d4ed8;}
        .status-rejected{background:#fee2e2;color:#b91c1c;}
        .status-approved{background:#dcfce7;color:#166534;}
        .sell-form-card{max-width:820px;background:#ffffff;border-radius:22px;box-shadow:0 14px 35px rgba(15,23,42,0.10);padding:30px;margin:0 auto 30px;}
        .required-star{color:#dc2626;font-weight:800;display:none;margin-left:4px;}
        .submitted .field-error .required-star{display:inline;}
        .field-error input,.field-error select,.field-error textarea,.field-error .upload-box{border-color:#dc2626 !important;background:#fff7f7;}
        .field-help{display:block;font-size:13px;color:#64748b;margin-top:6px;}
        .field-error .field-help{color:#dc2626;}
        .form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
        .upload-box{border:2px dashed #cbd5e1;border-radius:16px;padding:22px;text-align:center;cursor:pointer;background:#f8fafc;transition:0.2s ease;}
        .upload-box:hover{border-color:#2563eb;background:#eff6ff;}
        .upload-icon{font-size:34px;display:block;margin-bottom:8px;}
        .upload-title{font-weight:700;color:#111827;}
        .upload-subtitle{color:#64748b;font-size:13px;margin-top:5px;}
        .upload-box input{display:none;}
        .image-preview{display:none;margin-top:14px;}
        .image-preview img{width:220px;height:150px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;}
        .file-name-preview{margin-top:10px;font-weight:600;color:#2563eb;font-size:14px;}
        @media(max-width:768px){.form-grid-2{grid-template-columns:1fr;}.sell-form-card{padding:22px;}}
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
                    <p>To start selling on TradeSphere, you must request seller verification.</p>

                    <form method="POST">
                        <button type="submit" name="request_seller" class="btn btn-primary">
                            Request Seller Verification
                        </button>
                    </form>

                <?php elseif ($user['seller_status'] === "pending"): ?>
                    <span class="status-pill status-pending">Pending Approval</span>
                    <h3>Your Request is Under Review</h3>
                    <p>Your seller verification request has been sent to admin.</p>

                <?php elseif ($user['seller_status'] === "rejected"): ?>
                    <span class="status-pill status-rejected">Request Rejected</span>
                    <h3>Seller Verification Rejected</h3>
                    <p>Your seller verification request was rejected by admin.</p>

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
                <p class="helper">Upload product details and image to list your item.</p><br>

                <?php if ($success): ?><div class="success-msg"><?php echo h($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="error-msg"><?php echo nl2br(h($error)); ?></div><?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="sellForm" class="<?php echo !empty($invalidFields) ? 'submitted' : ''; ?>" novalidate>

                    <div class="<?php echo fieldClass('name', $invalidFields); ?>">
                        <label>Product Name <span class="required-star">*</span></label>
                        <input type="text" name="name" value="<?php echo h($old['name']); ?>">
                        <small class="field-help">Product name must be meaningful.</small>
                    </div>

                    <div class="form-grid-2">
                        <div class="<?php echo fieldClass('category_id', $invalidFields); ?>">
                            <label>Category <span class="required-star">*</span></label>
                            <select name="category_id">
                                <option value="">Select Category</option>
                                <?php if ($categories && $categories->num_rows > 0): ?>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$old['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo h($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="<?php echo fieldClass('price', $invalidFields); ?>">
                            <label>Price <span class="required-star">*</span></label>
                            <input type="number" step="0.01" min="1" name="price" value="<?php echo h($old['price']); ?>">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="<?php echo fieldClass('product_condition', $invalidFields); ?>">
                            <label>Condition <span class="required-star">*</span></label>
                            <select name="product_condition">
                                <option value="">Select Condition</option>
                                <option value="New" <?php echo $old['product_condition'] === 'New' ? 'selected' : ''; ?>>New</option>
                                <option value="Like New" <?php echo $old['product_condition'] === 'Like New' ? 'selected' : ''; ?>>Like New</option>
                                <option value="Used" <?php echo $old['product_condition'] === 'Used' ? 'selected' : ''; ?>>Used</option>
                                <option value="Old" <?php echo $old['product_condition'] === 'Old' ? 'selected' : ''; ?>>Old</option>
                            </select>
                        </div>

                        <div class="<?php echo fieldClass('city', $invalidFields); ?>">
                            <label>City <span class="required-star">*</span></label>
                            <input type="text" name="city" value="<?php echo h($old['city']); ?>">
                        </div>
                    </div>

                    <div class="<?php echo fieldClass('contact_number', $invalidFields); ?>">
                        <label>Contact Number <span class="required-star">*</span></label>
                        <input type="text" name="contact_number" maxlength="10" placeholder="98XXXXXXXX" value="<?php echo h($old['contact_number']); ?>">
                    </div>

                    <div class="<?php echo fieldClass('description', $invalidFields); ?>">
                        <label>Description <span class="required-star">*</span></label>
                        <textarea name="description" rows="5"><?php echo h($old['description']); ?></textarea>
                        <small class="field-help">Very short descriptions may be sent for admin review. Random text will be blocked.</small>
                    </div>

                    <div class="<?php echo fieldClass('image', $invalidFields); ?>">
                        <label>Product Image <span class="required-star">*</span></label>

                        <label class="upload-box" for="productImage">
                            <span class="upload-icon">📷</span>
                            <div class="upload-title">Click to upload product image</div>
                            <div class="upload-subtitle">Only JPG, PNG, WEBP, or GIF. Max size 5MB.</div>
                            <input type="file" id="productImage" name="image" accept="image/*">

                            <div id="fileNamePreview" class="file-name-preview"></div>

                            <div class="image-preview" id="imagePreview">
                                <img src="" alt="Preview">
                            </div>
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_product" class="btn btn-primary">List Product</button>
                    </div>

                </form>
            </div>

        <?php endif; ?>
    </div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>

<script src="js/script.js"></script>

<script>
const sellForm = document.getElementById("sellForm");

function jsLooksLikeGibberish(text) {
    text = String(text || "").toLowerCase().replace(/[^a-z\s]/g, "");
    const words = text.split(/\s+/).filter(Boolean);

    if (words.length === 0) return true;

    return words.some(word => {
        if (word.length < 8) return false;

        const vowels = (word.match(/[aeiou]/g) || []).length;
        const vowelRatio = vowels / Math.max(1, word.length);

        return vowelRatio < 0.20 || vowelRatio > 0.75 || /[bcdfghjklmnpqrstvwxyz]{5,}/.test(word);
    });
}

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

        const cleanName = name.value.trim().toLowerCase().replace(/\s+/g, "");

        if (!name.value.trim() || !/[a-zA-Z]/.test(name.value) || /^(.)\1+$/.test(cleanName) || jsLooksLikeGibberish(name.value)) {
            markInvalid(name);
        }

        if (!category.value) markInvalid(category);
        if (!price.value || parseFloat(price.value) <= 0) markInvalid(price);
        if (!condition.value) markInvalid(condition);

        if (!city.value.trim() || city.value.trim().length < 2 || !/^[a-zA-Z\s]+$/.test(city.value.trim())) {
            markInvalid(city);
        }

        if (!/^[0-9]{10}$/.test(contact.value.trim())) {
            markInvalid(contact);
        }

        if (!description.value.trim() || jsLooksLikeGibberish(description.value)) {
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

            if (typeof showToast === "function") {
                showToast("Please fill all required fields correctly.", "error");
            }
        }
    });
}

const productImage = document.getElementById("productImage");
const fileNamePreview = document.getElementById("fileNamePreview");
const imagePreview = document.getElementById("imagePreview");

if (productImage && fileNamePreview && imagePreview) {
    const previewImg = imagePreview.querySelector("img");

    productImage.addEventListener("change", function() {
        if (this.files && this.files[0]) {
            fileNamePreview.textContent = "Selected: " + this.files[0].name;
            previewImg.src = URL.createObjectURL(this.files[0]);
            imagePreview.style.display = "block";
        } else {
            fileNamePreview.textContent = "";
            previewImg.src = "";
            imagePreview.style.display = "none";
        }
    });
}

<?php if ($success): ?>
if (typeof showToast === "function") {
    showToast("<?php echo addslashes($success); ?>", "success");
}
<?php endif; ?>

<?php if ($error): ?>
if (typeof showToast === "function") {
    showToast("Please check the form and try again.", "error");
}
<?php endif; ?>
</script>

</body>
</html>