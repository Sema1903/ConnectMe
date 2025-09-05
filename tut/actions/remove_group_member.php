<?php
header('Content-Type: application/json');

// Включение подробного вывода ошибок для отладки (убрать в продакшене)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Подключение зависимостей
    require_once '../includes/config.php';   
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    // Проверка метода запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Метод не поддерживается', 405);
    }

    // Проверка авторизации
    if (!isLoggedIn()) {
        throw new Exception('Необходима авторизация', 401);
    }

    // Получение текущего пользователя
    $current_user = getCurrentUser($db);
    if (!$current_user) {
        throw new Exception('Пользователь не найден', 403);
    }

    // Получение и валидация входных данных
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Неверный формат JSON', 400);
    }

    $group_id = (int)($input['group_id'] ?? 0);
    $user_id = (int)($input['user_id'] ?? 0);

    if ($group_id <= 0 || $user_id <= 0) {
        throw new Exception('Неверные параметры запроса', 400);
    }

    // Проверка прав доступа
    $stmt = $db->prepare("SELECT creator_id FROM groups WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Ошибка подготовки запроса: ' . $db->lastErrorMsg(), 500);
    }
    $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $group = $result->fetchArray(SQLITE3_ASSOC);

    if (!$group) {
        throw new Exception('Группа не найдена', 404);
    }

    if ($group['creator_id'] != $current_user['id']) {
        throw new Exception('Только создатель группы может удалять участников', 403);
    }

    if ($user_id == $current_user['id']) {
        throw new Exception('Нельзя удалить самого себя', 400);
    }

    // Проверка существования участника в группе
    $stmt = $db->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    if (!$result->fetchArray()) {
        throw new Exception('Пользователь не состоит в группе', 400);
    }

    // Начало транзакции
    $db->exec('BEGIN TRANSACTION');

    try {
        // Удаление участника
        $stmt = $db->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            throw new Exception('Ошибка удаления участника');
        }

        // Обновление счетчика участников
        $stmt = $db->prepare("UPDATE groups SET members_count = members_count - 1 WHERE id = ?");
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        if (!$stmt->execute()) {
            throw new Exception('Ошибка обновления счетчика');
        }

        // Логирование действия (если таблица существует)
        $stmt = $db->prepare("INSERT INTO group_logs (group_id, user_id, action_type, action_data) VALUES (?, ?, ?, ?)");
        $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(3, 'member_removed', SQLITE3_TEXT);
        $stmt->bindValue(4, json_encode(['removed_user_id' => $user_id]), SQLITE3_TEXT);
        $stmt->execute();

        $db->exec('COMMIT');

        echo json_encode([
            'success' => true,
            'message' => 'Участник успешно удален'
        ]);

    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        throw new Exception('Ошибка транзакции: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}