<?php
// buy_item.php
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

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан предмет']);
    exit;
}

// Получаем информацию о предмете
$stmt = $db->prepare("
    SELECT gi.*, 
           (gi.quantity - IFNULL((
               SELECT COUNT(*) 
               FROM user_items 
               WHERE item_id = gi.id
           ), 0)) as available 
    FROM game_items gi
    WHERE gi.id = ?
");
$stmt->bindValue(1, $item_id, SQLITE3_INTEGER);
$item = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Предмет не найден']);
    exit;
}

if ($item['available'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Этот предмет закончился']);
    exit;
}

$user_id = $_SESSION['user_id'];
$balance = getUserBalance($db, $user_id);

if ($balance < $item['price']) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно средств']);
    exit;
}

// Начинаем транзакцию
$db->exec('BEGIN TRANSACTION');

try {
    // Списание средств
    if (!addCurrency($db, $user_id, -$item['price'], "Покупка предмета: {$item['name']}")) {
        throw new Exception('Ошибка при списании средств');
    }
    
    // Добавляем предмет пользователю
    $stmt = $db->prepare("
        INSERT INTO user_items (user_id, item_id, purchase_date, is_active)
        VALUES (?, ?, datetime('now'), 0)
    ");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $item['id'], SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка при добавлении предмета');
    }
    
    $db->exec('COMMIT');
    echo json_encode(['success' => true, 'message' => 'Покупка успешна']);
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}