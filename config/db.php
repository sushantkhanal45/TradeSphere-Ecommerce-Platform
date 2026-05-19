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
$conn = new mysqli(
    "tradesphere-db.mysql.database.azure.com",
    "adminuser",
    "YOUR_AZURE_MYSQL_PASSWORD",
    "tradesphere",
    3306
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>