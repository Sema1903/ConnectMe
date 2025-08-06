<?php
ini_set('session.gc_maxlifetime', 360000);
session_set_cookie_params(360000);
session_start();
function registerUser($db, $username, $password, $full_name, $email) {
    // Проверяем, существует ли пользователь с таким именем или email
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $stmt->bindValue(2, $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($row['count'] > 0) {
        return ['success' => false, 'message' => 'Пользователь с таким именем или email уже существует'];
    }
    
    // Хешируем пароль
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Создаем пользователя
    $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, avatar) VALUES (?, ?, ?, ?, 'unknown.png')");
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $stmt->bindValue(2, $hashed_password, SQLITE3_TEXT);
    $stmt->bindValue(3, $full_name, SQLITE3_TEXT);
    $stmt->bindValue(4, $email, SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Регистрация прошла успешно'];
    } else {
        return ['success' => false, 'message' => 'Ошибка при регистрации'];
    }
}

function loginUser($db, $username, $password) {
    // Находим пользователя
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bindValue(1, $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Пользователь не найден'];
    }
    
    // Проверяем пароль
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return ['success' => true, 'message' => 'Вход выполнен успешно'];
    } else {
        return ['success' => false, 'message' => 'Неверный пароль'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}