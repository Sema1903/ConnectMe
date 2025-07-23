<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Необходимо авторизоваться']));
}

$data = json_decode(file_get_contents('php://input'), true);
$friend_id = $data['friend_id'] ?? 0;

// Проверяем существование пользователя
$userCheck = $db->prepare("SELECT 1 FROM users WHERE id = ?");
$userCheck->bindValue(1, $friend_id, SQLITE3_INTEGER);
if (!$userCheck->execute()->fetchArray()) {
    die(json_encode(['error' => 'Пользователь не найден']));
}

// Проверяем, не отправили ли уже запрос
$stmt = $db->prepare("SELECT status FROM friends WHERE 
    (user1_id = ? AND user2_id = ?) OR 
    (user1_id = ? AND user2_id = ?)");
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(4, $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();

if ($row = $result->fetchArray()) {
    $status = $row['status'];
    die(json_encode([
        'error' => $status == 1 ? 'Уже друзья' : 'Запрос уже отправлен'
    ]));
}

// Отправляем запрос
$stmt = $db->prepare("INSERT INTO friends (user1_id, user2_id, status, created_at) 
                     VALUES (?, ?, 0, datetime('now'))");
$stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(2, $friend_id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    // Добавляем уведомление (упрощённая версия)
    $notifStmt = $db->prepare("INSERT INTO notifications 
                              (user_id, from_user_id, type, created_at) 
                              VALUES (?, ?, 'friend_request', datetime('now'))");
    $notifStmt->bindValue(1, $friend_id, SQLITE3_INTEGER);
    $notifStmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);
    $notifStmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных']);
}
?>