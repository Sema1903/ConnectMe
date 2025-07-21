<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;
$comment = $data['comment'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$post_id || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Не указан пост или комментарий']);
    exit;
}

$result = addComment($db, $post_id, $user_id, $comment);

// Получаем обновленное количество комментариев
$stmt = $db->prepare("SELECT COUNT(*) as comments_count FROM comments WHERE post_id = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

echo json_encode([
    'success' => $result,
    'comments_count' => $row['comments_count']
]);
