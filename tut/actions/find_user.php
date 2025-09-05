<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Проверяем наличие username
    if (empty($_GET['username'])) {
        throw new Exception('Не указано имя пользователя');
    }

    $username = trim($_GET['username']);

    // Проверяем длину username
    if (strlen($username) < 3 || strlen($username) > 20) {
        throw new Exception('Имя пользователя должно быть от 3 до 20 символов');
    }

    // Проверяем допустимые символы
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception('Имя пользователя может содержать только буквы, цифры и подчеркивание');
    }

    // Ищем пользователя в базе
    $stmt = $db->prepare("SELECT id, username FROM users WHERE username = ? LIMIT 1");
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user) {
        throw new Exception('Пользователь @' . htmlspecialchars($username) . ' не найден');
    }

    // Возвращаем успешный результат
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username']
        ]
    ]);

} catch (Exception $e) {
    // Возвращаем ошибку
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}