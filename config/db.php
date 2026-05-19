<!-- This code was used for xampp phpMyAdmin connection -->
<!-- <?php
$conn = new mysqli("localhost", "root", "", "TradeSphere");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
 -->

 <!-- This is used for azure db connection -->
<?php
$host = "tradesphere-db.mysql.database.azure.com";
$username = "adminuser@tradesphere-db";
$password = "#hey it's sk_45..";
$database = "tradesphere";
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>