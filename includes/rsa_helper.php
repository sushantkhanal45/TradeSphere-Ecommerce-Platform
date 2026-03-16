<?php

function getPrivateKeyPath() {
    return __DIR__ . '/../keys/private.pem';
}

function getPublicKeyPath() {
    return __DIR__ . '/../keys/public.pem';
}

function signData($data) {
    $privateKeyPath = getPrivateKeyPath();

    if (!file_exists($privateKeyPath)) {
        return false;
    }

    $privateKeyContent = file_get_contents($privateKeyPath);
    $privateKey = openssl_pkey_get_private($privateKeyContent);

    if (!$privateKey) {
        return false;
    }

    $signature = '';
    $signed = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    openssl_free_key($privateKey);

    if (!$signed) {
        return false;
    }

    return base64_encode($signature);
}

function verifySignature($data, $signatureBase64) {
    $publicKeyPath = getPublicKeyPath();

    if (!file_exists($publicKeyPath)) {
        return false;
    }

    $publicKeyContent = file_get_contents($publicKeyPath);
    $publicKey = openssl_pkey_get_public($publicKeyContent);

    if (!$publicKey) {
        return false;
    }

    $signature = base64_decode($signatureBase64);
    $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

    openssl_free_key($publicKey);

    return $result === 1;
}

function storeSignatureRecord($conn, $userId, $actionType, $relatedId, $signedData, $signature) {
    $safeAction = $conn->real_escape_string($actionType);
    $safeData = $conn->real_escape_string($signedData);
    $safeSignature = $conn->real_escape_string($signature);

    $userIdValue = is_null($userId) ? "NULL" : (int)$userId;
    $relatedIdValue = is_null($relatedId) ? "NULL" : (int)$relatedId;

    $sql = "
        INSERT INTO signatures (user_id, action_type, related_id, signed_data, signature)
        VALUES ($userIdValue, '$safeAction', $relatedIdValue, '$safeData', '$safeSignature')
    ";

    return $conn->query($sql);
}
?>