<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$stream_id = $data['stream_id'] ?? null;
$message = $data['message'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$stream_id || !$message) {
    echo json_encode(['success' => false, 'message' => 'Не указан стрим или сообщение']);
    exit;
}

// Проверяем, существует ли стрим и активен ли он
$stmt = $db->prepare("SELECT COUNT(*) as count FROM live_streams WHERE id = ? AND is_live = 1");
$stmt->bindValue(1, $stream_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Трансляция не найдена или завершена']);
    exit;
}

// В реальном приложении здесь было бы сохранение сообщения в базу данных чата
// Для демонстрации просто возвращаем успех

echo json_encode(['success' => true]);