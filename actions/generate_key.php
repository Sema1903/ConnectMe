<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/encryption.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

// Генерируем пару ключей
$keyPair = EndToEndEncryption::generateKeyPair();

// Сохраняем публичный ключ, приватный ключ шифруем мастер-паролем
$masterPassword = 'your_master_password_hash'; // В реальном приложении должен быть у каждого пользователя свой
$encryptedPrivateKey = EndToEndEncryption::simpleEncrypt($keyPair['private_key'], $masterPassword);

$stmt = $db->prepare("
    INSERT OR REPLACE INTO user_encryption_keys (user_id, public_key, private_key_encrypted) 
    VALUES (?, ?, ?)
");
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $keyPair['public_key'], SQLITE3_TEXT);
$stmt->bindValue(3, $encryptedPrivateKey, SQLITE3_TEXT);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'public_key' => $keyPair['public_key']]);
} else {
    echo json_encode(['success' => false]);
}