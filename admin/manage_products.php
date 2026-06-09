<?php
session_start();
include "../config/db.php";
include "admin_layout.php";
include "../includes/mail_helper.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$adminEmail = $conn->real_escape_string($_SESSION['admin']);

$adminCheck = $conn->query("
    SELECT *
    FROM users
    WHERE email='$adminEmail'
    AND role='admin'
    LIMIT 1
");

$adminUser = $adminCheck ? $adminCheck->fetch_assoc() : null;

if (!$adminUser) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$_SESSION['admin_name'] = $adminUser['name'] ?? 'Admin';

$success = "";
$error = "";

function notifySeller($conn, $userId, $message) {
    $userId = (int)$userId;
    $safeMessage = $conn->real_escape_string($message);

    $conn->query("
        INSERT INTO notifications (user_id, order_id, message)
        VALUES ($userId, NULL, '$safeMessage')
    ");
}

function sendProductVerificationEmailIfAvailable($product, $subject, $reason, $statusLabel) {
    if (empty($product['user_email'])) {
        return false;
    }

    $sellerName = $product['user_name'] ?? 'Seller';
    $productName = $product['name'] ?? 'Product';
    $description = $product['description'] ?? '';
    $city = $product['city'] ?? '';
    $condition = $product['product_condition'] ?? '';
    $price = $product['price'] ?? '';

    if (function_exists('sendProductRejectedEmail') && strtolower($statusLabel) === 'rejected') {
        return @sendProductRejectedEmail(
            $product['user_email'],
            $sellerName,
            $productName,
            $reason
        );
    }

    if (function_exists('sendMailMessage')) {
        $body = "
            <h2>TradeSphere Product Verification Update</h2>
            <p>Hello " . htmlspecialchars($sellerName) . ",</p>
            <p>Your product listing has been marked as <strong>" . htmlspecialchars($statusLabel) . "</strong>.</p>

            <h3>Product Details</h3>
            <p><strong>Product Name:</strong> " . htmlspecialchars($productName) . "</p>
            <p><strong>Description:</strong> " . nl2br(htmlspecialchars($description)) . "</p>
            <p><strong>City:</strong> " . htmlspecialchars($city) . "</p>
            <p><strong>Condition:</strong> " . htmlspecialchars($condition) . "</p>
            <p><strong>Price:</strong> Rs " . htmlspecialchars((string)$price) . "</p>

            <h3>Reason / Message</h3>
            <p>" . nl2br(htmlspecialchars($reason)) . "</p>

            <p>If your product was not approved, please submit again with a clearer product name, detailed description, valid city, proper image, and accurate product information.</p>
            <p>Regards,<br>TradeSphere Team</p>
        ";

        return @sendMailMessage($product['user_email'], $sellerName, $subject, $body);
    }

    return false;
}

/* DELETE PRODUCT */
if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("
        SELECT p.*, u.email AS user_email, u.name AS user_name
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id=$productId
        LIMIT 1
    ");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $imageName = $product['image'] ?? '';

        if ($conn->query("DELETE FROM products WHERE id=$productId")) {
            if (!empty($imageName) && file_exists("../uploads/" . $imageName)) {
                @unlink("../uploads/" . $imageName);
            }

            notifySeller(
                $conn,
                (int)$product['user_id'],
                "Your product '" . $product['name'] . "' was removed by admin. Please check your email for full details."
            );

            sendProductVerificationEmailIfAvailable(
                $product,
                "TradeSphere Product Removed by Admin",
                "Your product was removed by the administrator during product verification or marketplace moderation.",
                "Removed"
            );

            $success = "Product deleted successfully.";
        } else {
            $error = "Could not delete product.";
        }
    } else {
        $error = "Product not found.";
    }
}

/* TOGGLE STATUS */
if (isset($_POST['toggle_status'])) {
    $productId = (int)$_POST['product_id'];
    $currentStatus = $_POST['current_status'] ?? 'available';

    $newStatus = ($currentStatus === 'sold') ? 'available' : 'sold';
    $safeStatus = $conn->real_escape_string($newStatus);

    if ($conn->query("UPDATE products SET status='$safeStatus' WHERE id=$productId")) {
        $success = "Product status updated successfully.";
    } else {
        $error = "Could not update product status.";
    }
}

/* APPROVE PRODUCT */
if (isset($_POST['approve_product'])) {
    $productId = (int)$_POST['product_id'];

    $productRes = $conn->query("
        SELECT p.*, u.email AS user_email, u.name AS user_name
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = $productId
        LIMIT 1
    ");

    $product = $productRes ? $productRes->fetch_assoc() : null;

    if (!$product) {
        $error = "Product not found.";
    } else {
        if ($conn->query("
            UPDATE products
            SET ai_status='approved',
                ai_reason='Approved by administrator'
            WHERE id=$productId
        ")) {
            notifySeller(
                $conn,
                (int)$product['user_id'],
                "Your product '" . $product['name'] . "' has been approved by admin and is now visible in the marketplace."
            );

            if (function_exists('sendProductApprovedEmail')) {
                @sendProductApprovedEmail(
                    $product['user_email'],
                    $product['user_name'] ?? 'Seller',
                    $product['name']
                );
            } else {
                sendProductVerificationEmailIfAvailable(
                    $product,
                    "TradeSphere Product Approved",
                    "Your product has been approved and is now visible in the marketplace.",
                    "Approved"
                );
            }

            $success = "Product approved successfully.";
        } else {
            $error = "Could not approve product.";
        }
    }
}

/* REJECT PRODUCT */
if (isset($_POST['reject_product'])) {
    $productId = (int)$_POST['product_id'];
    $reason = trim($_POST['reject_reason'] ?? '');

    if ($reason === '') {
        $error = "Please provide a rejection reason.";
    } else {
        $safeReason = $conn->real_escape_string($reason);

        $productRes = $conn->query("
            SELECT p.*, u.email AS user_email, u.name AS user_name
            FROM products p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id=$productId
            LIMIT 1
        ");

        $product = $productRes ? $productRes->fetch_assoc() : null;

        if (!$product) {
            $error = "Product not found.";
        } else {
            if ($conn->query("
                UPDATE products
                SET ai_status='rejected',
                    ai_reason='$safeReason'
                WHERE id=$productId
            ")) {
                notifySeller(
                    $conn,
                    (int)$product['user_id'],
                    "Your product '" . $product['name'] . "' was rejected by admin. Please check your email for full details and submit again with better product information."
                );

                sendProductVerificationEmailIfAvailable(
                    $product,
                    "TradeSphere Product Listing Rejected",
                    $reason,
                    "Rejected"
                );

                $success = "Product rejected successfully.";
            } else {
                $error = "Could not reject product.";
            }
        }
    }
}

$products = $conn->query("
    SELECT
        p.*,
        c.name AS category_name,
        u.name AS seller_name,
        u.email AS user_email,
        u.name AS user_name,
        u.seller_status,
        COALESCE(p.average_rating, 0) AS average_rating,
        COALESCE(p.rating_count, 0) AS rating_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY
        CASE
            WHEN p.ai_status = 'manual_review' THEN 1
            WHEN p.ai_status = 'rejected' THEN 2
            WHEN p.ai_status = 'approved' THEN 3
            ELSE 4
        END,
        p.id DESC
");

adminHeader("Manage Products");
?>

<?php if ($success): ?>
    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h2 style="margin-top:0;">Product Listings</h2>
    <p class="muted" style="margin-bottom:18px;">
        Review marketplace products, seller information, ratings, product status, and rule-based product verification results.
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Price</th>
                    <th>City</th>
                    <th>Seller</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th>AI Review</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while ($row = $products->fetch_assoc()): ?>
                        <?php
                            $image = $row['image'] ?? '';
                            $status = $row['status'] ?? 'available';
                            $sellerStatus = $row['seller_status'] ?? 'none';
                            $aiStatus = $row['ai_status'] ?? 'skipped';
                        ?>

                        <tr>
                            <td><strong>#<?php echo (int)$row['id']; ?></strong></td>

                            <td>
                                <div style="display:flex;align-items:center;gap:12px;min-width:230px;">
                                    <?php if (!empty($image)): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($image); ?>" alt="Product"
                                             style="width:64px;height:64px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;">
                                    <?php else: ?>
                                        <span class="badge badge-gray">No Image</span>
                                    <?php endif; ?>

                                    <div>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <span class="muted">
                                            <?php echo htmlspecialchars(substr((string)$row['description'], 0, 55)); ?>
                                            <?php echo strlen((string)$row['description']) > 55 ? '...' : ''; ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <?php if (!empty($row['category_name'])): ?>
                                    <span class="badge badge-blue"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-gray">No Category</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo htmlspecialchars($row['product_condition']); ?></td>
                            <td><strong>Rs <?php echo number_format((float)$row['price'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['city']); ?></td>

                            <td>
                                <strong><?php echo htmlspecialchars($row['seller_name'] ?? 'Unknown Seller'); ?></strong><br>
                                <span class="muted"><?php echo htmlspecialchars($row['seller_email']); ?></span><br>

                                <?php if ($sellerStatus === 'approved'): ?>
                                    <span class="badge badge-green">Verified Seller</span>
                                <?php elseif ($sellerStatus === 'pending'): ?>
                                    <span class="badge badge-yellow">Pending Seller</span>
                                <?php elseif ($sellerStatus === 'rejected'): ?>
                                    <span class="badge badge-red">Rejected Seller</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Normal User</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int)$row['rating_count'] > 0): ?>
                                    <span class="badge badge-yellow">⭐ <?php echo number_format((float)$row['average_rating'], 1); ?></span><br>
                                    <span class="muted"><?php echo (int)$row['rating_count']; ?> reviews</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">No Rating</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($status === 'sold'): ?>
                                    <span class="badge badge-red">Sold</span>
                                <?php else: ?>
                                    <span class="badge badge-green">Available</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($aiStatus === 'approved'): ?>
                                    <span class="badge badge-green">Approved</span>
                                <?php elseif ($aiStatus === 'manual_review'): ?>
                                    <span class="badge badge-yellow">Manual Review</span>
                                <?php elseif ($aiStatus === 'rejected'): ?>
                                    <span class="badge badge-red">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-gray">Skipped</span>
                                <?php endif; ?>

                                <?php if (!empty($row['ai_reason'])): ?>
                                    <br>
                                    <span class="muted" style="display:inline-block;max-width:220px;margin-top:6px;">
                                        <?php echo htmlspecialchars($row['ai_reason']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td><span class="muted"><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></span></td>

                            <td>
                                <div class="action-row">
                                    <a href="../product_details.php?id=<?php echo (int)$row['id']; ?>" class="mini-btn btn-blue" target="_blank">View</a>

                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($status); ?>">
                                        <button type="submit" name="toggle_status" class="mini-btn btn-dark">
                                            <?php echo ($status === 'sold') ? 'Mark Available' : 'Mark Sold'; ?>
                                        </button>
                                    </form>

                                    <?php if ($aiStatus === 'manual_review'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="approve_product" class="mini-btn btn-green">Approve</button>
                                        </form>

                                        <form method="POST" style="margin:0;display:flex;gap:6px;align-items:center;" onsubmit="return confirm('Reject this product?');">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="text" name="reject_reason" placeholder="Reason" required
                                                   style="width:135px;padding:7px 9px;border:1px solid #d1d5db;border-radius:8px;">
                                            <button type="submit" name="reject_product" class="mini-btn btn-red">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($aiStatus === 'rejected'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" name="approve_product" class="mini-btn btn-green">Approve Again</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="delete_product" class="mini-btn btn-red">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="12">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter($success, $error); ?>
