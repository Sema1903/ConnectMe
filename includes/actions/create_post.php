<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$has_poll = isset($_POST['has_poll']) && $_POST['has_poll'] == '1';
$image = null;

// Обработка загрузки изображения
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../assets/images/posts/';
    $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image = $file_name;
    }
}

// Валидация
if (empty($content)) {
    $_SESSION['error'] = 'Текст поста не может быть пустым';
    header('Location: /');
    exit;
}

// Проверка опроса
if ($has_poll) {
    $poll_question = trim($_POST['poll_question'] ?? '');
    $poll_options = array_filter(array_map('trim', $_POST['poll_options'] ?? []));
    
    if (empty($poll_question) || count($poll_options) < 2) {
        $_SESSION['error'] = 'Опрос должен содержать вопрос и минимум 2 варианта ответа';
        header('Location: /');
        exit;
    }
}

// Создаем пост
$post_id = addPost($db, $user_id, $content, $image, $_POST['feeling'] ?? null);

if ($post_id) {
    // Создаем опрос, если нужно
    if ($has_poll) {
        $is_multiple = isset($_POST['poll_multiple']) && $_POST['poll_multiple'] == 'on';
        $ends_at = isset($_POST['poll_has_deadline']) && !empty($_POST['poll_deadline']) 
            ? $_POST['poll_deadline'] 
            : null;
        
        createPoll(
            $db,
            $post_id,
            $poll_question,
            $poll_options,
            $is_multiple,
            $ends_at
        );
    }
    
    // Обработка упоминаний
    preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
    $mentioned_usernames = array_unique($matches[1]);
    
    foreach ($mentioned_usernames as $username) {
        $mentioned_user = getUserByUsername($db, $username);
        if ($mentioned_user && $mentioned_user['id'] != $user_id) {
            addNotification($db, $mentioned_user['id'], 'mention', $user_id, $post_id);
        }
    }
    
    $_SESSION['success'] = 'Пост успешно опубликован' . ($has_poll ? ' с опросом' : '');
} else {
    $_SESSION['error'] = 'Ошибка при создании поста';
}

header('Location: /');
exit;
?>