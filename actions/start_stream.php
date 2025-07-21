<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? null;
$description = $data['description'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$title) {
    echo json_encode(['success' => false, 'message' => 'Не указано название трансляции']);
    exit;
}

// Проверяем, нет ли у пользователя уже активного стрима
$stmt = $db->prepare("SELECT COUNT(*) as count FROM live_streams WHERE user_id = ? AND is_live = 1");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'У вас уже есть активная трансляция']);
    exit;
}

// Создаем стрим
$stream_key = bin2hex(random_bytes(16));
$stmt = $db->prepare("
    INSERT INTO live_streams (user_id, title, description, stream_key, is_live, started_at)
    VALUES (?, ?, ?, ?, 1, datetime('now'))
");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $title, SQLITE3_TEXT);
$stmt->bindValue(3, $description, SQLITE3_TEXT);
$stmt->bindValue(4, $stream_key, SQLITE3_TEXT);
$result = $stmt->execute();

echo json_encode(['success' => (bool)$result]);