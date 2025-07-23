<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Устанавливаем заголовок для JSON-ответа
header('Content-Type: application/json');

// Функция для отправки ошибок
function sendError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Проверяем авторизацию
if (!isLoggedIn()) {
    sendError('Необходимо авторизоваться', 401);
}

// Получаем текущего пользователя
$current_user = getCurrentUser($db);
if (!$current_user) {
    sendError('Пользователь не найден', 403);
}

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Метод не поддерживается', 405);
}

// Получаем и проверяем данные
$group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$content = trim($_POST['content'] ?? '');

if ($group_id <= 0) {
    sendError('Неверный ID группы', 400);
}

if (empty($content)) {
    sendError('Текст поста не может быть пустым', 400);
}

// Проверяем, существует ли группа
$stmt = $db->prepare("SELECT 1 FROM groups WHERE id = ?");
$stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
$result = $stmt->execute();

if (!$result->fetchArray()) {
    sendError('Группа не найдена', 404);
}

// Проверяем, является ли пользователь участником группы
$stmt = $db->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();

if (!$result->fetchArray()) {
    sendError('Вы не участник этой группы', 403);
}

// Обработка загрузки изображения
$image_path = null;
if (!empty($_FILES['image']['tmp_name'])) {
    try {
        // Проверяем тип файла
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['image']['tmp_name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($mime, $allowed_types)) {
            sendError('Допустимы только JPG, PNG или GIF изображения', 400);
        }
        
        // Проверяем размер файла (макс. 5MB)
        if ($_FILES['image']['size'] > 5242880) {
            sendError('Максимальный размер файла - 5MB', 400);
        }
        
        // Создаем директорию, если её нет
        $upload_dir = __DIR__ . '/../assets/images/posts/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                sendError('Не удалось создать директорию для загрузки', 500);
            }
        }
        
        // Генерируем уникальное имя файла
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $file_name = 'group_post_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $target_path = $upload_dir . $file_name;
        
        // Пытаемся переместить файл
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            sendError('Ошибка при сохранении изображения', 500);
        }
        
        $image_path = $file_name;
    } catch (Exception $e) {
        sendError('Ошибка обработки изображения: ' . $e->getMessage(), 500);
    }
}

// Создаем пост в базе данных
try {
    $db->exec('BEGIN TRANSACTION');
    
    $stmt = $db->prepare("INSERT INTO group_posts 
                         (group_id, user_id, content, image, created_at) 
                         VALUES (:group_id, :user_id, :content, :image, datetime('now'))");
    $stmt->bindValue(':group_id', $group_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $current_user['id'], SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->bindValue(':image', $image_path, SQLITE3_TEXT);
    
    if (!$stmt->execute()) {
        throw new Exception('Ошибка при создании поста: ' . $db->lastErrorMsg());
    }
    
    $post_id = $db->lastInsertRowID();
    
    // Обновляем счетчик постов в группе
    $update_stmt = $db->prepare("UPDATE groups SET posts_count = posts_count + 1 WHERE id = ?");
    $update_stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
    $update_stmt->execute();
    
    $db->exec('COMMIT');
    
    // Возвращаем успешный ответ
    echo json_encode([
        'success' => true,
        'post_id' => $post_id,
        'message' => 'Пост успешно создан',
        'image_path' => $image_path
    ]);
    
} catch (Exception $e) {
    $db->exec('ROLLBACK');
    
    // Удаляем загруженное изображение, если что-то пошло не так
    if ($image_path && file_exists($upload_dir . $image_path)) {
        unlink($upload_dir . $image_path);
    }
    
    error_log("Database error: " . $e->getMessage());
    sendError('Ошибка базы данных: ' . $e->getMessage(), 500);
}
?>