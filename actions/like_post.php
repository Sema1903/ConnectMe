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
$user_id = $_SESSION['user_id'];

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан пост']);
    exit;
}

$result = likePost($db, $user_id, $post_id);

// Получаем обновленное количество лайков
$stmt = $db->prepare("SELECT COUNT(*) as likes_count FROM likes WHERE post_id = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

echo json_encode([
    'success' => true,
    'action' => $result['action'],
    'likes_count' => $row['likes_count']
]);
