<?php
include "includes/db.php";

$query = "SELECT * FROM products";
$result = mysqli_query($conn,$query);
?>

<!DOCTYPE html>
<html>
<head>

<title>TradeSphere</title>
<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h1>TradeSphere Marketplace</h1>

<a href="login.php">Login</a>
<a href="register.php">Register</a>

<div class="products">

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<div class="card">

<h3><?php echo $row['name']; ?></h3>

<p>Price: $<?php echo $row['price']; ?></p>

<p>City: <?php echo $row['city']; ?></p>

<?php if($row['status']=="sold"){ ?>

<span class="sold">SOLD</span>

<?php } ?>

<a href="product.php?id=<?php echo $row['id']; ?>">View</a>

</div>

<?php } ?>

</div>

</body>
</html>