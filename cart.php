<?php
session_start();
include "includes/db.php";

$user_id=$_SESSION['user']['id'];

$query="SELECT * FROM cart
JOIN products ON cart.product_id=products.id
WHERE cart.user_id='$user_id'";

$result=mysqli_query($conn,$query);

?>

<h2>Your Cart</h2>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<p>

<?php echo $row['name']; ?>

$<?php echo $row['price']; ?>

</p>

<?php } ?>

<a href="checkout.php">Checkout</a>