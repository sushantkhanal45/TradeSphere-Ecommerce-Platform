<?php
session_start();
include "includes/db.php";

if(isset($_POST['add'])){

$name=$_POST['name'];
$price=$_POST['price'];
$city=$_POST['city'];
$status=$_POST['status'];

$user_id=$_SESSION['user']['id'];

$query="INSERT INTO products(user_id,name,price,city,status)
VALUES('$user_id','$name','$price','$city','$status')";

mysqli_query($conn,$query);

header("Location:index.php");

}

?>

<form method="POST">

<input type="text" name="name" placeholder="Product name">

<input type="number" name="price" placeholder="Price">

<input type="text" name="city" placeholder="City">

<select name="status">

<option value="available">Available</option>

<option value="sold">Sold</option>

</select>

<button name="add">Add Product</button>

</form>