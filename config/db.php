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


   
<!-- // // $conn = new mysqli(
//     // "localhost",
//     "root",
//     "",
//     "TradeSphere"
// );

// if ($conn->connect_error) {
//     die("Database connection failed: " . $conn->connect_error);
// } -->

