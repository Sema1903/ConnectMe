<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Завершаем стрим
$stmt = $db->prepare("
    UPDATE live_streams 
    SET is_live = 0, ended_at = datetime('now')
    WHERE user_id = ? AND is_live = 1
");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

echo json_encode(['success' => (bool)$result]);