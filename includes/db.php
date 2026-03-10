<?php

$host = "localhost";
$user = "root";
$password = "";
$db = "tradesphere";

$conn = mysqli_connect($host,$user,$password,$db);

if(!$conn){
    die("Database connection failed");
}

?>