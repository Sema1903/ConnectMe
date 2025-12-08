<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/encryption.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$friend_id = (int)$_GET['friend_id'];

// Получаем сообщения
$stmt = $db->prepare("
    SELECT m.*, u.full_name, u.avatar 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(4, $_SESSION['user_id'], SQLITE3_INTEGER);

$result = $stmt->execute();
$messages = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    // Если есть зашифрованное сообщение, пытаемся расшифровать
    if (!empty($row['encrypted_content'])) {
        $secretKey = md5($_SESSION['user_id'] . $friend_id . 'secret_salt');
        $decrypted = EndToEndEncryption::simpleDecrypt($row['encrypted_content'], $secretKey);
        
        if ($decrypted !== false) {
            $row['content'] = $decrypted;
        } else {
            $row['content'] = '🔒 Не удалось расшифровать сообщение';
        }
    }
    $messages[] = $row;
}

echo json_encode($messages);