<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Заменяем основную логику на эту:
$current_user = getCurrentUser($db);
if (!$current_user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? 0;
$emoji = $data['emoji'] ?? '';

// Проверяем количество реакций пользователя на этот пост
$stmt = $db->prepare("SELECT COUNT(*) as count FROM reactions WHERE post_id = ? AND user_id = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$count_data = $result->fetchArray(SQLITE3_ASSOC);
$reaction_count = $count_data['count'] ?? 0;

// Проверяем лимит
if ($reaction_count >= 3) {
    echo json_encode(['success' => false, 'message' => 'Лимит реакций исчерпан (максимум 3)']);
    exit;
}

// Проверяем, есть ли уже такая реакция у пользователя
$stmt = $db->prepare("SELECT * FROM reactions WHERE post_id = ? AND user_id = ? AND emoji = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
$stmt->bindValue(3, $emoji, SQLITE3_TEXT);
$existing_reaction = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($existing_reaction) {
    // Удаляем существующую реакцию (toggle)
    $stmt = $db->prepare("DELETE FROM reactions WHERE id = ?");
    $stmt->bindValue(1, $existing_reaction['id'], SQLITE3_INTEGER);
    $stmt->execute();
} else {
    // Добавляем новую реакцию
    $stmt = $db->prepare("INSERT INTO reactions (post_id, user_id, emoji, created_at) VALUES (?, ?, ?, datetime('now'))");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(3, $emoji, SQLITE3_TEXT);
    $stmt->execute();
}

echo json_encode(['success' => true]);