<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Устанавливаем заголовок для JSON-ответа
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

// Получаем RAW данные POST-запроса
$input = json_decode(file_get_contents('php://input'), true);

// Проверяем, что данные получены
if ($input === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный формат данных']);
    exit;
}

$poll_id = $input['poll_id'] ?? null;
$option_ids = $input['option_ids'] ?? [];
$user_id = $_SESSION['user_id'];

// Проверяем обязательные параметры
if (!$poll_id || empty($option_ids)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Неверные параметры запроса',
        'received_data' => $input // Для отладки - что пришло на сервер
    ]);
    exit;
}

// Проверяем, существует ли опрос
$stmt = $db->prepare("SELECT * FROM polls WHERE id = ?");
$stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
$poll = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$poll) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Опрос не найден']);
    exit;
}

// Проверяем, не завершился ли опрос
if ($poll['ends_at'] && strtotime($poll['ends_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Опрос уже завершен']);
    exit;
}

// Проверяем, что варианты принадлежат этому опросу
$valid_options = [];
$stmt = $db->prepare("SELECT id FROM poll_options WHERE poll_id = ?");
$stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $valid_options[] = $row['id'];
}

// Проверяем, что все переданные option_ids есть в valid_options
foreach ($option_ids as $option_id) {
    if (!in_array($option_id, $valid_options)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Неверный вариант ответа',
            'invalid_option_id' => $option_id,
            'valid_options' => $valid_options
        ]);
        exit;
    }
}

// Голосуем
if (voteInPoll($db, $poll_id, $option_ids, $user_id)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка при сохранении голоса']);
}
?>