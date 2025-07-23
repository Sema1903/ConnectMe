<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$current_password || !$new_password || !$confirm_password) {
    $_SESSION['error'] = 'Все поля обязательны для заполнения';
    header('Location: /edit_profile.php');
    exit;
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'Новые пароли не совпадают';
    header('Location: /edit_profile.php');
    exit;
}

// Получаем текущий пароль пользователя
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if (!password_verify($current_password, $user['password'])) {
    $_SESSION['error'] = 'Текущий пароль неверен';
    header('Location: /edit_profile.php');
    exit;
}

// Обновляем пароль
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->bindValue(1, $hashed_password, SQLITE3_TEXT);
$stmt->bindValue(2, $user_id, SQLITE3_INTEGER);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Пароль успешно изменен';
} else {
    $_SESSION['error'] = 'Ошибка при изменении пароля';
}

header('Location: /edit_profile.php');
exit;