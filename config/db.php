<?php
$conn = new mysqli("localhost", "root", "", "TradeSphere");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>