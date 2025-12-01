<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

$user = getCurrentUser($db);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'ID поста не указан']);
    exit;
}

// Проверяем, принадлежит ли пост пользователю
$stmt = $db->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$post = $result->fetchArray(SQLITE3_ASSOC);

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Пост не найден']);
    exit;
}

if ($post['user_id'] != $user['id']) {
    echo json_encode(['success' => false, 'message' => 'Вы можете удалять только свои посты']);
    exit;
}

// Удаляем пост
$stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Пост удален']);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при удалении поста']);
}
?>