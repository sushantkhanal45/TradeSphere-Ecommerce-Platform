
<!-- Ths is not used for hosting because your personal credentials may be exposed..so setting up environment variable in azure -->

<!-- DEMO ONLY -->

<!-- This code was used for xampp phpMyAdmin connection -->
 <!-- <?php
$conn = new mysqli("localhost", "root", "", "TradeSphere");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?> -->


 <!-- This is used for azure db connection -->
<?php
$host = "tradesphere-db.mysql.database.azure.com";
$username = "adminuser@tradesphere-db";
$password = "#your_azure_password_here";
$database = "tradesphere";
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>