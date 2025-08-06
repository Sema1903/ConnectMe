<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$post_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Не указан пост']);
    exit;
}

$result = likePost($db, $user_id, $post_id);

// Получаем обновленное количество лайков и информацию о пользователе
$stmt = $db->prepare("
    SELECT COUNT(*) as likes_count,
           EXISTS(SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?) as is_liked
    FROM likes 
    WHERE post_id = ?
");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $post_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'action' => $result['action'],
    'likes_count' => $row['likes_count'],
    'is_liked' => (bool)$row['is_liked']
]);
?>