<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$gameType = $data['game_id'] ?? '';
$score = $data['score'] ?? 0;

if (empty($gameType)) {
    echo json_encode(['success' => false, 'message' => 'Не указан тип игры']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO leaderboard (user_id, game_type, score) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $userId, SQLITE3_INTEGER);
    $stmt->bindValue(2, $gameType, SQLITE3_TEXT);
    $stmt->bindValue(3, $score, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения результата']);
}
?>