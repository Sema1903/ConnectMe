<?php
session_start();

// Уничтожаем все данные сессии
$_SESSION = array();

// Если нужно уничтожить куки
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header("Location: /login.php");
exit;
?>