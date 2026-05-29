<?php
session_start();
include "../config/db.php";
include "admin_layout.php";

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

/* DELETE PRODUCT */
if (isset($_POST['delete_product'])) {
    $productId = (int)$_POST['product_id'];

    $check = $conn->query("SELECT * FROM products WHERE id=$productId LIMIT 1");
    $product = $check ? $check->fetch_assoc() : null;

    if ($product) {
        $imageName = $product['image'] ?? '';

        if ($conn->query("DELETE FROM products WHERE id=$productId")) {
            if (!empty($imageName) && file_exists("../uploads/" . $imageName)) {
                @unlink("../uploads/" . $imageName);
            }

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

$products = $conn->query("
    SELECT 
        p.*,
        c.name AS category_name,
        u.name AS seller_name,
        u.seller_status,
        COALESCE(p.average_rating, 0) AS average_rating,
        COALESCE(p.rating_count, 0) AS rating_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.user_id = u.id
    ORDER BY p.id DESC
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
        Review marketplace products, seller information, ratings, and product status.
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
                        ?>

                        <tr>
                            <td>
                                <strong>#<?php echo (int)$row['id']; ?></strong>
                            </td>

                            <td>
                                <div style="display:flex;align-items:center;gap:12px;min-width:230px;">
                                    <?php if (!empty($image)): ?>
                                        <img 
                                            src="../uploads/<?php echo htmlspecialchars($image); ?>" 
                                            alt="Product"
                                            style="width:64px;height:64px;object-fit:cover;border-radius:14px;border:1px solid #e5e7eb;"
                                        >
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
                                    <span class="badge badge-blue">
                                        <?php echo htmlspecialchars($row['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-gray">No Category</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo htmlspecialchars($row['product_condition']); ?></td>

                            <td>
                                <strong>Rs <?php echo number_format((float)$row['price'], 2); ?></strong>
                            </td>

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
                                    <span class="badge badge-yellow">
                                        ⭐ <?php echo number_format((float)$row['average_rating'], 1); ?>
                                    </span><br>
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
                                <span class="muted">
                                    <?php echo htmlspecialchars($row['created_at'] ?? ''); ?>
                                </span>
                            </td>

                            <td>
                                <div class="action-row">
                                    <a 
                                        href="../product_details.php?id=<?php echo (int)$row['id']; ?>" 
                                        class="mini-btn btn-blue"
                                        target="_blank"
                                    >
                                        View
                                    </a>

                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($status); ?>">

                                        <button type="submit" name="toggle_status" class="mini-btn btn-dark">
                                            <?php echo ($status === 'sold') ? 'Mark Available' : 'Mark Sold'; ?>
                                        </button>
                                    </form>

                                    <form 
                                        method="POST" 
                                        style="margin:0;" 
                                        onsubmit="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        <input type="hidden" name="product_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" name="delete_product" class="mini-btn btn-red">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminFooter($success, $error); ?>