<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Открываем соединение с SQLite3
$db = new SQLite3(DB_PATH); // DB_PATH должен вести к файлу .sqlite

if (!$db) {
    echo json_encode([
        'success' => false,
        'error' => 'Не удалось подключиться к базе данных'
    ]);
    exit;
}

$user_id = (int) ($_GET['user_id'] ?? 0);

// Запрос для получения фотографий пользователя
$query = "
    SELECT image FROM posts 
    WHERE user_id = :user_id AND image IS NOT NULL 
    ORDER BY created_at DESC
";

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$photos = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $photos[] = $row;
}

echo json_encode([
    'success' => true,
    'photos' => $photos
]);

$db->close();
?>