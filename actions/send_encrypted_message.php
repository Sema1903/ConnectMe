<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/encryption.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$receiver_id = (int)$input['receiver_id'];
$message = $input['encrypted_data']['message'];

// Получаем ключи пользователей
$stmt = $db->prepare("SELECT * FROM user_encryption_keys WHERE user_id IN (?, ?)");
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$keys = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $keys[$row['user_id']] = $row;
}

// Если есть ключи, используем E2EE
if (!empty($keys[$_SESSION['user_id']]) && !empty($keys[$receiver_id])) {
    // В реальном приложении шифрование должно происходить на клиенте
    // Здесь для примера используем простое шифрование
    $secretKey = md5($_SESSION['user_id'] . $receiver_id . 'secret_salt');
    $encrypted = EndToEndEncryption::simpleEncrypt($message, $secretKey);
    
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, encrypted_content) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, ''); // Очищаем plain text
    $stmt->bindValue(4, $encrypted, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    // Fallback: сохраняем без шифрования (для совместимости)
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $message, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}