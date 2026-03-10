<?php
session_start();

if(!isset($_SESSION['admin'])){
header("Location:admin_login.php");
}

?>

<h1>Admin Panel</h1>

<p>Manage Users</p>

<p>Manage Products</p>