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

// Запрос для получения групп пользователя
$query = "
    SELECT 
        g.id, 
        g.name, 
        g.description, 
        g.avatar, 
        g.posts_count,
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count,
        EXISTS(
            SELECT 1 FROM group_members 
            WHERE group_id = g.id AND user_id = :user_id
        ) as is_member
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = :user_id
    ORDER BY g.name
";

$stmt = $db->prepare($query);
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$groups = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $groups[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $groups
]);

$db->close();
?>