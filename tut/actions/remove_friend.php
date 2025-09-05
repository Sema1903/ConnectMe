<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Необходимо авторизоваться']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$friend_id = $data['friend_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$friend_id) {
    echo json_encode(['success' => false, 'message' => 'Не указан друг']);
    exit;
}

// Удаляем дружбу в обоих направлениях
$stmt = $db->prepare("DELETE FROM friends WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
$stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(3, $friend_id, SQLITE3_INTEGER);
$stmt->bindValue(4, $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

echo json_encode(['success' => (bool)$result]);