<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$group_id = $data['group_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$group_id) {
    echo json_encode(['success' => false, 'message' => 'Не указана группа']);
    exit;
}

// Удаляем пользователя из группы
$stmt = $db->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

echo json_encode(['success' => (bool)$result]);