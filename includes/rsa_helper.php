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

    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));

    if (!$privateKey) {
        return false;
    }

    $signature = "";
    $signed = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

    openssl_free_key($privateKey);

    return $signed ? base64_encode($signature) : false;
}

function verifySignature($data, $signatureBase64) {
    $publicKeyPath = getPublicKeyPath();

    if (!file_exists($publicKeyPath) || empty($signatureBase64)) {
        return false;
    }

    $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));

    if (!$publicKey) {
        return false;
    }

    $signature = base64_decode($signatureBase64, true);

    if ($signature === false) {
        return false;
    }

    $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);

    openssl_free_key($publicKey);

    return $result === 1;
}

function rsaEncryptData($plainData) {
    $publicKeyPath = getPublicKeyPath();

    if (!file_exists($publicKeyPath)) {
        return false;
    }

    $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));

    if (!$publicKey) {
        return false;
    }

    $encrypted = "";
    $ok = openssl_public_encrypt(
        $plainData,
        $encrypted,
        $publicKey,
        OPENSSL_PKCS1_OAEP_PADDING
    );

    openssl_free_key($publicKey);

    return $ok ? base64_encode($encrypted) : false;
}

function rsaDecryptData($encryptedBase64) {
    $privateKeyPath = getPrivateKeyPath();

    if (!file_exists($privateKeyPath) || empty($encryptedBase64)) {
        return false;
    }

    $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));

    if (!$privateKey) {
        return false;
    }

    $encrypted = base64_decode($encryptedBase64, true);

    if ($encrypted === false) {
        return false;
    }

    $decrypted = "";
    $ok = openssl_private_decrypt(
        $encrypted,
        $decrypted,
        $privateKey,
        OPENSSL_PKCS1_OAEP_PADDING
    );

    openssl_free_key($privateKey);

    return $ok ? $decrypted : false;
}

function hybridEncryptMessage($message) {
    $aesKey = random_bytes(32);
    $iv = random_bytes(12);
    $tag = "";

    $cipherText = openssl_encrypt(
        $message,
        "aes-256-gcm",
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($cipherText === false) {
        return false;
    }

    $encryptedKey = rsaEncryptData($aesKey);

    if (!$encryptedKey) {
        return false;
    }

    return [
        "encrypted_message" => base64_encode($cipherText),
        "encrypted_key" => $encryptedKey,
        "encryption_iv" => base64_encode($iv),
        "encryption_tag" => base64_encode($tag),
        "encryption_method" => "AES-256-GCM + RSA-OAEP"
    ];
}

function hybridDecryptMessage($encryptedMessage, $encryptedKey, $ivBase64, $tagBase64) {
    $aesKey = rsaDecryptData($encryptedKey);

    if ($aesKey === false) {
        return false;
    }

    $cipherText = base64_decode($encryptedMessage, true);
    $iv = base64_decode($ivBase64, true);
    $tag = base64_decode($tagBase64, true);

    if ($cipherText === false || $iv === false || $tag === false) {
        return false;
    }

    return openssl_decrypt(
        $cipherText,
        "aes-256-gcm",
        $aesKey,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}

function decryptChatMessageRow($row) {
    if (
        !empty($row['encrypted_message']) &&
        !empty($row['encrypted_key']) &&
        !empty($row['encryption_iv']) &&
        !empty($row['encryption_tag'])
    ) {
        $decrypted = hybridDecryptMessage(
            $row['encrypted_message'],
            $row['encrypted_key'],
            $row['encryption_iv'],
            $row['encryption_tag']
        );

        return $decrypted !== false ? $decrypted : "[Unable to decrypt message]";
    }

    return $row['message_text'];
}

function storeSignatureRecord($conn, $userId, $actionType, $relatedId, $signedData, $signature) {
    if (empty($signature)) {
        return false;
    }

    $safeAction = $conn->real_escape_string($actionType);
    $safeData = $conn->real_escape_string($signedData);
    $safeSignature = $conn->real_escape_string($signature);

    $userIdValue = is_null($userId) ? "NULL" : (int)$userId;
    $relatedIdValue = is_null($relatedId) ? "NULL" : (int)$relatedId;

    return $conn->query("
        INSERT INTO signatures (user_id, action_type, related_id, signed_data, signature)
        VALUES ($userIdValue, '$safeAction', $relatedIdValue, '$safeData', '$safeSignature')
    ");
}

function insertEncryptedChatMessage($conn, $roomId, $senderId, $receiverId, $message, $messageType, $signature = null, $signedData = null) {
    $encrypted = hybridEncryptMessage($message);

    if (!$encrypted) {
        return false;
    }

    $roomId = (int)$roomId;
    $senderId = (int)$senderId;
    $receiverId = (int)$receiverId;

    $safeType = $conn->real_escape_string($messageType);
    $safePlaceholder = $conn->real_escape_string("[Encrypted message]");

    $safeEncryptedMessage = $conn->real_escape_string($encrypted['encrypted_message']);
    $safeEncryptedKey = $conn->real_escape_string($encrypted['encrypted_key']);
    $safeIv = $conn->real_escape_string($encrypted['encryption_iv']);
    $safeTag = $conn->real_escape_string($encrypted['encryption_tag']);
    $safeMethod = $conn->real_escape_string($encrypted['encryption_method']);

    $signatureSql = $signature ? "'" . $conn->real_escape_string($signature) . "'" : "NULL";
    $signedDataSql = $signedData ? "'" . $conn->real_escape_string($signedData) . "'" : "NULL";

    return $conn->query("
        INSERT INTO chat_messages
        (
            room_id,
            sender_id,
            receiver_id,
            message_text,
            message_type,
            signature,
            encrypted_message,
            encrypted_key,
            encryption_iv,
            encryption_tag,
            encryption_method,
            signed_data
        )
        VALUES
        (
            $roomId,
            $senderId,
            $receiverId,
            '$safePlaceholder',
            '$safeType',
            $signatureSql,
            '$safeEncryptedMessage',
            '$safeEncryptedKey',
            '$safeIv',
            '$safeTag',
            '$safeMethod',
            $signedDataSql
        )
    ");
}
?>