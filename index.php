<?php
session_start();
include "config/db.php";

$categoryQuery = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$recent = $conn->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TradeSphere</title>
<link rel="stylesheet" href="css/style.css">
</head>

<body>

<?php include "includes/navbar.php"; ?>

<section class="hero">
    <h1>Welcome to TradeSphere</h1>
    <p>Buy and sell anything easily.</p>
</section>

<section class="categories">
    <h2>Categories</h2>
    <div class="category-chip-row">
        <a href="products.php">All</a>

        <?php while($cat = $categoryQuery->fetch_assoc()): ?>
            <a href="products.php?category_id=<?php echo $cat['id']; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<section class="products">
    <h2>Recent Products</h2>

    <div class="products-grid">
        <?php while($row = $recent->fetch_assoc()): ?>
            <div class="product-card">

                <div class="product-image-wrap">
                    <img src="uploads/<?php echo $row['image']; ?>">
                    <?php if($row['status'] === 'sold'): ?>
                        <div class="sold-badge">SOLD</div>
                    <?php endif; ?>
                </div>

                <div class="product-body">
                    <h3><?php echo $row['name']; ?></h3>
                    <p>Rs <?php echo $row['price']; ?></p>

                    <div class="product-actions">
                        <a href="product_details.php?id=<?php echo $row['id']; ?>" class="small-btn primary">View</a>

                        <?php if($row['status'] !== 'sold'): ?>
                            <button class="small-btn dark add-to-cart-btn"
                                data-id="<?php echo $row['id']; ?>">
                                Add to Cart
                            </button>
                        <?php else: ?>
                            <button disabled class="small-btn">Sold</button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endwhile; ?>
    </div>
</section>

<script src="js/script.js"></script>

</body>
</html>