<?php
session_start();
include "../config/db.php";
include "admin_layout.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$error = "";
$success = "";
$editCategory = null;

if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');

    if ($name === "") {
        $error = "Category name is required.";
    } elseif (strlen($name) < 2) {
        $error = "Category name must be at least 2 characters.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $check = $conn->query("SELECT id FROM categories WHERE name='$safeName' LIMIT 1");

        if ($check && $check->num_rows > 0) {
            $error = "This category already exists.";
        } else {
            if ($conn->query("INSERT INTO categories (name) VALUES ('$safeName')")) {
                $success = "Category added successfully.";
            } else {
                $error = "Could not add category.";
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editRes = $conn->query("SELECT * FROM categories WHERE id=$editId LIMIT 1");
    $editCategory = $editRes ? $editRes->fetch_assoc() : null;
}

if (isset($_POST['update_category'])) {
    $categoryId = (int)$_POST['category_id'];
    $name = trim($_POST['category_name'] ?? '');

    if ($categoryId <= 0 || $name === "") {
        $error = "Invalid category update.";
    } else {
        $safeName = $conn->real_escape_string($name);
        $check = $conn->query("
            SELECT id FROM categories
            WHERE name='$safeName' AND id != $categoryId
            LIMIT 1
        ");

        if ($check && $check->num_rows > 0) {
            $error = "Another category with this name already exists.";
        } else {
            if ($conn->query("UPDATE categories SET name='$safeName' WHERE id=$categoryId")) {
                header("Location: manage_categories.php?success=updated");
                exit();
            } else {
                $error = "Could not update category.";
            }
        }
    }
}

if (isset($_POST['delete_category'])) {
    $categoryId = (int)$_POST['category_id'];

    $productCheck = $conn->query("SELECT id FROM products WHERE category_id=$categoryId LIMIT 1");

    if ($productCheck && $productCheck->num_rows > 0) {
        $error = "Cannot delete this category because products exist under it.";
    } else {
        if ($conn->query("DELETE FROM categories WHERE id=$categoryId")) {
            $success = "Category deleted successfully.";
        } else {
            $error = "Could not delete category.";
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $success = "Category updated successfully.";
}

$categories = $conn->query("
    SELECT c.id, c.name, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");

adminHeader("Manage Categories");
?>

<?php if ($error): ?>
    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:360px 1fr;gap:22px;align-items:start;">
    <div class="admin-card">
        <?php if ($editCategory): ?>
            <h2>Edit Category</h2>

            <form method="POST">
                <input type="hidden" name="category_id" value="<?php echo (int)$editCategory['id']; ?>">

                <div class="form-row">
                    <label>Category Name</label>
                    <input type="text" name="category_name" value="<?php echo htmlspecialchars($editCategory['name']); ?>" required>
                </div>

                <div class="action-row">
                    <button type="submit" name="update_category" class="mini-btn btn-blue">Update</button>
                    <a href="manage_categories.php" class="mini-btn btn-dark">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <h2>Add Category</h2>

            <form method="POST">
                <div class="form-row">
                    <label>Category Name</label>
                    <input type="text" name="category_name" placeholder="Enter category name" required>
                </div>

                <button type="submit" name="add_category" class="mini-btn btn-blue">Add Category</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <h2>Category List</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Products</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><?php echo (int)$cat['product_count']; ?></td>
                                <td>
                                    <div class="action-row">
                                        <a href="manage_categories.php?edit=<?php echo (int)$cat['id']; ?>" class="mini-btn btn-blue">Edit</a>

                                        <?php if ((int)$cat['product_count'] > 0): ?>
                                            <button type="button" class="mini-btn btn-gray" disabled>In Use</button>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Delete this category?');" style="margin:0;">
                                                <input type="hidden" name="category_id" value="<?php echo (int)$cat['id']; ?>">
                                                <button type="submit" name="delete_category" class="mini-btn btn-red">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No categories found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php adminFooter($success, $error); ?>