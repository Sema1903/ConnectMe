<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$post_id = $_GET['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан пост']);
    exit;
}

$comments = getComments($db, $post_id);

// Форматируем даты для отображения
foreach ($comments as &$comment) {
    $comment['created_at'] = time_elapsed_string($comment['created_at']);
}

echo json_encode([
    'success' => true,
    'comments' => $comments
]);