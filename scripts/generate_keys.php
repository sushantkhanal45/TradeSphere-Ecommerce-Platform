
// Development utility script for TradeSphere
// Used for testing/setup only
// Do not run on production server



<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$keysDir = __DIR__ . '/keys';

if (!is_dir($keysDir)) {
    if (!mkdir($keysDir, 0777, true)) {
        die("Could not create keys directory.");
    }
}

if (!extension_loaded('openssl')) {
    die("OpenSSL extension is not enabled.");
}

/* IMPORTANT: set your OpenSSL config file path here */
$opensslConfigPath = "C:\\xampp\\php\\extras\\ssl\\openssl.cnf";

if (!file_exists($opensslConfigPath)) {
    die("OpenSSL config file not found at: " . $opensslConfigPath);
}

$config = [
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    "config" => $opensslConfigPath
];

$res = openssl_pkey_new($config);

if (!$res) {
    echo "<h3>Could not generate RSA keys.</h3>";
    while ($msg = openssl_error_string()) {
        echo $msg . "<br>";
    }
    exit();
}

$privateKeyPem = '';
if (!openssl_pkey_export($res, $privateKeyPem, null, $config)) {
    echo "<h3>Could not export private key.</h3>";
    while ($msg = openssl_error_string()) {
        echo $msg . "<br>";
    }
    exit();
}

$publicKeyDetails = openssl_pkey_get_details($res);

if (!$publicKeyDetails || !isset($publicKeyDetails['key'])) {
    die("Could not get public key details.");
}

$publicKeyPem = $publicKeyDetails['key'];

if (file_put_contents($keysDir . '/private.pem', $privateKeyPem) === false) {
    die("Could not write private.pem");
}

if (file_put_contents($keysDir . '/public.pem', $publicKeyPem) === false) {
    die("Could not write public.pem");
}

echo "RSA keys generated successfully in /keys folder.";
?>