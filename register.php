<?php
include "includes/db.php";

if(isset($_POST['register'])){

$name = $_POST['name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'],PASSWORD_DEFAULT);

$query = "INSERT INTO users(name,email,password)
VALUES('$name','$email','$password')";

mysqli_query($conn,$query);

header("Location: login.php");

}
?>

<form method="POST">

<input type="text" name="name" placeholder="Name" required>

<input type="email" name="email" placeholder="Email" required>

<input type="password" name="password" placeholder="Password" required>

<button name="register">Register</button>

</form>