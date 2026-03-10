<?php
session_start();
include "../includes/db.php";

if(isset($_POST['login'])){

$email=$_POST['email'];
$password=$_POST['password'];

$query="SELECT * FROM users WHERE email='$email' AND role='admin'";
$result=mysqli_query($conn,$query);

$admin=mysqli_fetch_assoc($result);

if(password_verify($password,$admin['password'])){

$_SESSION['admin']=$admin;

header("Location:dashboard.php");

}

}

?>

<form method="POST">

<input name="email">

<input name="password" type="password">

<button name="login">Admin Login</button>

</form>