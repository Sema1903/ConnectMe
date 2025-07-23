<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$stream_id = $data['stream_id'] ?? null;
$message = $data['message'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$stream_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Неверные данные']);
    exit;
}

// Сохраняем сообщение в БД
$stmt = $db->prepare("
    INSERT INTO chat_messages (stream_id, user_id, message, created_at)
    VALUES (?, ?, ?, datetime('now'))
");
$stmt->bindValue(1, $stream_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $message, SQLITE3_TEXT);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения']);
}