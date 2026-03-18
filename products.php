<?php
session_start();
include "config/db.php";

$search = $_GET['search'] ?? "";
$categoryId = $_GET['category_id'] ?? "";

$categoryQuery = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$sql = "
SELECT p.*, c.name AS category_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE 1=1
";

if($search != ""){
    $sql .= " AND p.name LIKE '%$search%'";
}

if($categoryId != ""){
    $sql .= " AND p.category_id = $categoryId";
}

$sql .= " ORDER BY p.id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products</title>
<link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "includes/navbar.php"; ?>

<h2>Products</h2>

<form method="GET">
    <input type="text" name="search" placeholder="Search..." value="<?php echo $search; ?>">

    <select name="category_id">
        <option value="">All Categories</option>

        <?php while($cat = $categoryQuery->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>"
                <?php if($categoryId == $cat['id']) echo "selected"; ?>>
                <?php echo $cat['name']; ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Search</button>
</form>

<div class="products-grid">
<?php while($row = $result->fetch_assoc()): ?>
    <div class="product-card">

        <img src="uploads/<?php echo $row['image']; ?>">

        <h3><?php echo $row['name']; ?></h3>
        <p>Rs <?php echo $row['price']; ?></p>

        <div class="product-actions">
            <a href="product_details.php?id=<?php echo $row['id']; ?>">View</a>

            <?php if($row['status'] !== 'sold'): ?>
                <button class="add-to-cart-btn"
                    data-id="<?php echo $row['id']; ?>">
                    Add to Cart
                </button>
            <?php else: ?>
                <button disabled>Sold</button>
            <?php endif; ?>
        </div>

    </div>
<?php endwhile; ?>
</div>

<script src="js/script.js"></script>

</body>
</html>