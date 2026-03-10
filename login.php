<?php
session_start();
include "includes/db.php";

if(isset($_POST['login'])){

$email=$_POST['email'];
$password=$_POST['password'];

$query="SELECT * FROM users WHERE email='$email'";
$result=mysqli_query($conn,$query);

$user=mysqli_fetch_assoc($result);

if(password_verify($password,$user['password'])){

$_SESSION['user']=$user;

header("Location: dashboard.php");

}else{

echo "Invalid login";

}

}
?>

<form method="POST">

<input type="email" name="email" placeholder="Email">

<input type="password" name="password" placeholder="Password">

<button name="login">Login</button>

</form>