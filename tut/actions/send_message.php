<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$receiver_id = $data['receiver_id'] ?? null;
$message = $data['message'] ?? null;
$sender_id = $_SESSION['user_id'];

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Не указан получатель или сообщение']);
    exit;
}

// Проверяем, являются ли пользователи друзьями
$stmt = $db->prepare("
    SELECT COUNT(*) as count FROM friends 
    WHERE ((user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)) AND status = 1
");
$stmt->bindValue(1, $sender_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $receiver_id, SQLITE3_INTEGER);
$stmt->bindValue(4, $sender_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Вы можете отправлять сообщения только друзьям']);
    exit;
}

// Отправляем сообщение
$stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
$stmt->bindValue(1, $sender_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $message, SQLITE3_TEXT);
$result = $stmt->execute();

echo json_encode(['success' => (bool)$result]);