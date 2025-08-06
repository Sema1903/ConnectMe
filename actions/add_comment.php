<?php
session_start();
require_once '../includes/config.php'; // Должен содержать путь к БД
header('Content-Type: application/json');



// Получаем RAW данные
$input = json_decode(file_get_contents('php://input'), true);

// Валидация данных
if (!$input || !isset($input['post_id']) || !isset($input['comment'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$postId = (int)$input['post_id'];
$comment = trim($input['comment']);
$userId = $_SESSION['user_id'] ?? null;

// Проверка авторизации
if (!$userId) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

// Проверка пустого комментария
if (empty($comment)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Comment cannot be empty']));
}

try {
    // Открываем соединение с SQLite
    $db = new SQLite3(DB_PATH); // DB_PATH должен быть определен в config.php
    
    // Вставляем комментарий
    $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (:post_id, :user_id, :content, datetime('now'))");
    $stmt->bindValue(':post_id', $postId, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $comment, SQLITE3_TEXT);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to insert comment");
    }
    
    // Получаем количество комментариев
    $countResult = $db->querySingle("SELECT COUNT(*) FROM comments WHERE post_id = $postId");
    
    echo json_encode([
        'success' => true,
        'comments_count' => $countResult
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->close();
    }
}