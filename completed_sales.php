<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userEmail = $conn->real_escape_string($_SESSION['user']);
$userRes = $conn->query("SELECT * FROM users WHERE email='$userEmail' LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

if (!$user) {
    die("User not found.");
}

$userId = (int)$user['id'];

$mySales = $conn->query("
    SELECT 
        o.*,
        p.name AS product_name,
        p.image AS product_image
    FROM orders o
    INNER JOIN products p ON o.product_id = p.id
    WHERE o.seller_user_id = $userId
    AND o.buyer_received = 1
    ORDER BY o.buyer_received_at DESC, o.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Completed Sales - TradeSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-row{margin-bottom:22px;}
        .purchase-badge{display:inline-block;margin-top:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#dcfce7;color:#166534;}
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="page-wrap">
<div class="container">

    <div class="back-row">
        <a href="profile.php#sales" class="small-btn dark">← Back to Profile</a>
    </div>

    <div class="profile-section-card">
        <h2 class="section-title" style="text-align:left;margin-bottom:20px;">All Completed Sales</h2>

        <?php if ($mySales && $mySales->num_rows > 0): ?>
            <div style="margin-bottom:20px;">
    <input 
        type="text" 
        id="profileSearchInput" 
        placeholder="Search records..." 
        style="width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:12px;font-size:15px;"
    >
</div>
            <div class="products-grid">
                <?php while ($row = $mySales->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image-wrap">
                            <img src="uploads/<?php echo htmlspecialchars($row['product_image']); ?>" alt="Sold Product">
                        </div>

                        <div class="product-body">
                            <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                            <p class="price">Rs <?php echo number_format((float)$row['amount'], 2); ?></p>
                            <p class="meta"><strong>Buyer:</strong> <?php echo htmlspecialchars($row['buyer_name']); ?></p>
                            <p class="meta"><strong>Email:</strong> <?php echo htmlspecialchars($row['buyer_email']); ?></p>
                            <span class="purchase-badge">SALE COMPLETED</span>

                            <div class="product-actions" style="margin-top:12px;">
                                <a href="product_details.php?id=<?php echo (int)$row['product_id']; ?>" class="small-btn primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="inline-empty">No completed sales yet.</p>
        <?php endif; ?>
    </div>

</div>
</div>

<footer>© 2026 TradeSphere. All rights reserved.</footer>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("profileSearchInput");
    const cards = document.querySelectorAll(".product-card");

    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        const keyword = this.value.toLowerCase().trim();

        cards.forEach(function (card) {
            const text = card.textContent.toLowerCase();

            if (text.includes(keyword)) {
                card.style.display = "";
            } else {
                card.style.display = "none";
            }
        });
    });
});
</script>

<script src="js/script.js"></script>

</body>
</html>