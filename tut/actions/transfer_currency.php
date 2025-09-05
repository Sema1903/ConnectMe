<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Очищаем буфер на всякий случай
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

// Проверяем, есть ли активная транзакция
if ($db->lastErrorCode() === 5) { // SQLITE_BUSY
    $db->exec('ROLLBACK'); // Откатываем любую активную транзакцию
}

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Необходима авторизация', 401);
    }

    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception('Неверные данные запроса', 400);
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Неверный формат данных', 400);
    }

    // Валидация данных
    $to_user_id = filter_var($data['to_user_id'] ?? null, FILTER_VALIDATE_INT);
    $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_FLOAT);
    $message = trim($data['message'] ?? '');

    if (!$to_user_id || $to_user_id <= 0) {
        throw new Exception('Неверный получатель', 400);
    }

    if (!$amount || $amount <= 0) {
        throw new Exception('Неверная сумма', 400);
    }

    $from_user_id = (int)$_SESSION['user_id'];

    if ($from_user_id === $to_user_id) {
        throw new Exception('Нельзя отправить средства самому себе', 400);
    }

    // Проверяем существование получателя (без транзакции)
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bindValue(1, $to_user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();

    if (!$result->fetchArray()) {
        throw new Exception('Получатель не найден', 404);
    }

    // Проверяем баланс отправителя (без транзакции)
    $balance = getUserBalance($db, $from_user_id);
    if ($balance < $amount) {
        throw new Exception('Недостаточно средств. Ваш баланс: ' . $balance . ' CC', 400);
    }

    // Начинаем транзакцию только когда все проверки пройдены
    $db->exec('BEGIN TRANSACTION');
    
    // Выполняем перевод
    if (transferCurrency($db, $from_user_id, $to_user_id, $amount)) {
        // Добавляем уведомление получателю
        $notificationMessage = 'Вам перевели ' . number_format($amount, 2) . ' CC';
        if (!empty($message)) {
            $notificationMessage .= ' с сообщением: ' . $message;
        }
        
        addNotification($db, $to_user_id, 'currency_received', $from_user_id, null, $notificationMessage);
        
        $db->exec('COMMIT');
        
        $response = [
            'success' => true,
            'message' => 'Перевод выполнен успешно',
            'new_balance' => getUserBalance($db, $from_user_id)
        ];
    } else {
        $db->exec('ROLLBACK');
        throw new Exception('Ошибка при переводе', 500);
    }

} catch (Exception $e) {
    // Убедимся, что транзакция откатывается при ошибке
    if ($db->lastErrorCode() === 5) { // SQLITE_BUSY
        $db->exec('ROLLBACK');
    }
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ];
}

// Убедимся, что ничего лишнего не выводится
die(json_encode($response));