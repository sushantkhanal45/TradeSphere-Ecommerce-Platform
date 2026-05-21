
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

<!-- encrypted form -->
<?php
$host = getenv("DB_HOST");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
);

mysqli_real_connect(
    $conn,
    $host,
    $username,
    $password,
    $database,
    (int)$port,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>


   <!-- localhost db connection -->
<!-- // // $conn = new mysqli(
//     // "localhost",
//     "root",
//     "",
//     "TradeSphere"
// );

// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// } -->

