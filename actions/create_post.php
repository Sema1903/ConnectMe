<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$content = $_POST['content'] ?? '';
$image = null;
$feeling = $_POST['feeling'] ?? null;

// Обработка загрузки изображения
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../assets/images/posts/';
    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image = $file_name;
    }
}

if (empty($content)) {
    $_SESSION['error'] = 'Поле содержимого не может быть пустым';
    header('Location: /');
    exit;
}

// Создаем пост с учетом чувства
$post_id = addPost($db, $user_id, $content, $image, $feeling);

if ($post_id) {
    // Ищем упоминания (@username) в тексте
    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
    $mentioned_usernames = array_unique($matches[1]);
    
    // Создаем уведомления для упомянутых пользователей
    foreach ($mentioned_usernames as $username) {
        $mentioned_user = getUserByUsername($db, $username);
        if ($mentioned_user && $mentioned_user['id'] != $user_id) {
            addNotification($db, $mentioned_user['id'], 'mention', $user_id, $post_id);
        }
    }
    
    $_SESSION['success'] = 'Пост успешно опубликован';
} else {
    $_SESSION['error'] = 'Ошибка при публикации поста';
}

header('Location: /');
exit;
?>