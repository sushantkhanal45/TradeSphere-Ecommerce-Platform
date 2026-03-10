<?php
session_start();

if(!isset($_SESSION['user'])){
header("Location: login.php");
}

?>

<h2>User Dashboard</h2>

<a href="add_product.php">Add Product</a>

<a href="cart.php">Cart</a>

<a href="logout.php">Logout</a>