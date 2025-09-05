<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
$user = getCurrentUser($db);
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

echo json_encode([
    'success' => true,
    'balance' => getUserBalance($db, $user['id'])
]);