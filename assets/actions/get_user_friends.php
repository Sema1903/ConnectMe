<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$db = new SQLite3(DB_PATH);

if (!$db) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка подключения к SQLite'
    ]);
    exit;
}

$user_id = (int) ($_GET['user_id'] ?? 0);
$offset = (int) ($_GET['offset'] ?? 0);
$limit = (int) ($_GET['limit'] ?? 9);

// Запрос для получения друзей пользователя
$query = "
    SELECT u.id, u.username, u.full_name, u.avatar 
    FROM friends f
    JOIN users u ON (f.user1_id = u.id OR f.user2_id = u.id) AND u.id != :user_id
    WHERE (f.user1_id = :user_id OR f.user2_id = :user_id) AND f.status = 1
    ORDER BY u.full_name
    LIMIT :limit OFFSET :offset
";

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
$stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
$result = $stmt->execute();

$friends = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $friends[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $friends
]);

$db->close();
?>