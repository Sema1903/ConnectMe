<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Необходимо авторизоваться']));
}

$data = json_decode(file_get_contents('php://input'), true);
$friend_id = $data['friend_id'] ?? 0;

// Принимаем запрос
$stmt = $db->prepare("UPDATE friends SET status = 1 
                     WHERE user1_id = ? AND user2_id = ? AND status = 0");
$stmt->bindValue(1, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $_SESSION['user_id'], SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных']);
}
?>