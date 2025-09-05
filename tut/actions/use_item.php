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
$itemType = $data['item_type'] ?? null;

if (!$itemId || !$itemType) {
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

// Проверяем, что предмет принадлежит пользователю
$userItem = $db->querySingle("SELECT * FROM user_items WHERE id = $itemId AND user_id = {$user['id']}", true);

if (!$userItem) {
    echo json_encode(['success' => false, 'message' => 'Этот предмет вам не принадлежит']);
    exit;
}

// Определяем тип предмета и выполняем соответствующие действия
$coverMap = [
    '3rd lavel' => 'bronze.jpg',
    '2nd lavel' => 'silver.jpg',
    '1st lavel' => 'gold.jpg',
    'premium' => 'vip.jpg',
    'avatar_frame' => null,
    'profile_cover' => null
];

if (array_key_exists($itemType, $coverMap)) {
    // Деактивируем все предметы этого типа
    $db->exec("UPDATE user_items SET is_active = 0 WHERE user_id = {$user['id']} AND item_id IN (SELECT id FROM game_items WHERE type = '$itemType')");
    
    // Активируем текущий предмет
    $db->exec("UPDATE user_items SET is_active = 1 WHERE id = $itemId");
    
    // Если это оформление профиля, обновляем обложку
    if ($coverMap[$itemType]) {
        $db->exec("UPDATE users SET cover = '{$coverMap[$itemType]}' WHERE id = {$user['id']}");
    }
    
    echo json_encode(['success' => true, 'message' => 'Предмет успешно применен']);
} else {
    echo json_encode(['success' => false, 'message' => 'Этот предмет нельзя применить']);
}