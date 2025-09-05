<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$user = getCurrentUser($db);
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['item_id'] ?? null;

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

// Проверяем, что предмет принадлежит пользователю и активен
$userItem = $db->querySingle("SELECT * FROM user_items WHERE id = $itemId AND user_id = {$user['id']} AND is_active = 1", true);

if (!$userItem) {
    echo json_encode(['success' => false, 'message' => 'Предмет не найден или уже деактивирован']);
    exit;
}

// Деактивируем предмет
$db->exec("UPDATE user_items SET is_active = 0 WHERE id = $itemId");

// Если это оформление профиля, сбрасываем обложку на стандартную
$itemInfo = $db->querySingle("SELECT type FROM game_items WHERE id = (SELECT item_id FROM user_items WHERE id = $itemId)", true);

if ($itemInfo && in_array($itemInfo['type'], ['3rd lavel', '2nd lavel', '1st lavel', 'premium'])) {
    $db->exec("UPDATE users SET cover = 'default.jpg' WHERE id = {$user['id']}");
}

echo json_encode(['success' => true, 'message' => 'Предмет успешно снят']);