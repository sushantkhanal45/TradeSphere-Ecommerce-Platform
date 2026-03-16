<?php
include "config/db.php";
include "includes/rsa_helper.php";

$result = $conn->query("SELECT * FROM signatures ORDER BY id DESC LIMIT 1");
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    die("No signature records found.");
}

$data = $row['signed_data'];
$signature = $row['signature'];

if (verifySignature($data, $signature)) {
    echo "Signature is valid.";
} else {
    echo "Signature is NOT valid.";
}
?>