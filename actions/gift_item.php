<?php
// gift_item.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$username = $data['username'] ?? null;

if (!$item_id || !$username) {
    echo json_encode(['success' => false, 'message' => 'Не указаны данные']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверяем, что предмет принадлежит пользователю
$stmt = $db->prepare("
    SELECT 1 FROM user_items
    WHERE id = ? AND user_id = ?
");
$stmt->bindValue(1, $item_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $user_id, SQLITE3_INTEGER);

if (!$stmt->execute()->fetchArray()) {
    echo json_encode(['success' => false, 'message' => 'Предмет не найден']);
    exit;
}

// Получаем ID получателя
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bindValue(1, $username, SQLITE3_TEXT);
$receiver = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$receiver) {
    echo json_encode(['success' => false, 'message' => 'Пользователь не найден']);
    exit;
}

if ($receiver['id'] == $user_id) {
    echo json_encode(['success' => false, 'message' => 'Нельзя подарить предмет самому себе']);
    exit;
}

// Начинаем транзакцию
$db->exec('BEGIN TRANSACTION');

try {
    // Передаём предмет
    $stmt = $db->prepare("
        UPDATE user_items
        SET user_id = ?, is_active = 0
        WHERE id = ?
    ");
    $stmt->bindValue(1, $receiver['id'], SQLITE3_INTEGER);
    $stmt->bindValue(2, $item_id, SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка при передаче предмета');
    }
    
    // Записываем в историю
    $stmt = $db->prepare("
        INSERT INTO gifts_history (sender_id, receiver_id, item_id, gift_date)
        VALUES (?, ?, ?, datetime('now'))
    ");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $receiver['id'], SQLITE3_INTEGER);
    $stmt->bindValue(3, $item_id, SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка при записи истории');
    }
    
    $db->exec('COMMIT');
    echo json_encode(['success' => true, 'message' => 'Предмет успешно подарен']);
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}