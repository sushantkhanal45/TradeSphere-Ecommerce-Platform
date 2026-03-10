<?php
session_start();
include "includes/db.php";

$id = $_GET['id'];

$query = "SELECT * FROM products WHERE id='$id'";
$result = mysqli_query($conn,$query);
$product = mysqli_fetch_assoc($result);

if(isset($_POST['add_cart'])){

if(!isset($_SESSION['user'])){
header("Location: login.php");
exit();
}

$user_id = $_SESSION['user']['id'];

$insert = "INSERT INTO cart(user_id,product_id)
VALUES('$user_id','$id')";

mysqli_query($conn,$insert);

echo "Added to cart";

}

?>

<!DOCTYPE html>
<html>
<head>

<title><?php echo $product['name']; ?></title>
<link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<h2><?php echo $product['name']; ?></h2>

<p>Price: $<?php echo $product['price']; ?></p>

<p>City: <?php echo $product['city']; ?></p>

<p>Status: <?php echo $product['status']; ?></p>

<?php if($product['status']=="available"){ ?>

<form method="POST">
<button name="add_cart">Add to Cart</button>
</form>

<?php } ?>

<a href="index.php">Back</a>

</body>
</html>