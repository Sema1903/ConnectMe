<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
if (!$current_user) {
    header('Location: /login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Название группы обязательно';
    } else {
        // Обработка аватарки группы
        $avatar = 'unknown.png';
        if (!empty($_FILES['avatar']['name'])) {
            $upload_dir = __DIR__ . '/../assets/images/groups/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['avatar']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $file_name = 'group_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    $avatar = $file_name;
                }
            }
        }
        
        // Создаем группу
        $stmt = $db->prepare("INSERT INTO groups (name, description, avatar, creator_id, created_at) 
                             VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->bindValue(2, $description, SQLITE3_TEXT);
        $stmt->bindValue(3, $avatar, SQLITE3_TEXT);
        $stmt->bindValue(4, $current_user['id'], SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $group_id = $db->lastInsertRowID();
            // Автоматически добавляем создателя в участники
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, joined_at) 
                                 VALUES (?, ?, datetime('now'))");
            $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $success = 'Группа успешно создана!';
            header("Location: /group.php?id=$group_id");
            exit;
        } else {
            $error = 'Ошибка при создании группы';
        }
    }
}

require_once 'includes/header.php';
?>

<main class="create-group-container">
    <div class="create-group-card">
        <h1>Создать новую группу</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="create-group-form">
            <div class="form-group">
                <label for="name">Название группы</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Описание группы</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="avatar">Аватар группы (необязательно)</label>
                <input type="file" id="avatar" name="avatar" accept="image/*">
            </div>
            
            <button type="submit" class="btn btn-primary">Создать группу</button>
        </form>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>