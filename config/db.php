<!-- This is only used for azure host  -->
<?php
$host = getenv("DB_HOST");
$username = getenv("DB_USER");
$password = getenv("DB_PASSWORD");
$database = getenv("DB_NAME");
$port = getenv("DB_PORT") ?: 3306;

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database,
    (int)$port
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>