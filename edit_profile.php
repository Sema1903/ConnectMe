<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
if (!$user) {
    header('Location: /login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Валидация данных
    if (empty($full_name) || empty($email)) {
        $error = 'Имя и email обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email';
    } else {
        // Обработка аватарки
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            //$upload_dir = realpath(__DIR__ . '/../assets/images/avatars/') . '/';
            $upload_dir = realpath('assets/images/avatars/') . '/';
            // Создаем папку, если её нет
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['avatar']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file_ext, $allowed_ext)) {
                $error = 'Допустимы только изображения JPG, PNG или GIF';
            } elseif ($_FILES['avatar']['size'] > $max_size) {
                $error = 'Максимальный размер файла - 2MB';
            } else {
                // Генерируем уникальное имя файла
                $file_name = 'avatar_' . $user['id'] . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    // Удаляем старую аватарку (если она не дефолтная)
                    if ($user['avatar'] && $user['avatar'] != 'unknown.png' && file_exists($upload_dir . $user['avatar'])) {
                        unlink($upload_dir . $user['avatar']);
                    }
                    $avatar = $file_name;
                } else {
                    $error = 'Ошибка при сохранении изображения';
                }
            }
        }
        
        // Обновляем профиль, если нет ошибок
        if (empty($error)) {
            $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, email = ?, avatar = ? WHERE id = ?");
            $stmt->bindValue(1, $full_name, SQLITE3_TEXT);
            $stmt->bindValue(2, $bio, SQLITE3_TEXT);
            $stmt->bindValue(3, $email, SQLITE3_TEXT);
            $stmt->bindValue(4, $avatar, SQLITE3_TEXT);
            $stmt->bindValue(5, $user['id'], SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $success = 'Профиль успешно обновлен';
                // Обновляем данные в сессии
                $_SESSION['user_avatar'] = $avatar;
                $user = getCurrentUser($db); // Обновляем данные пользователя
            } else {
                $error = 'Ошибка при обновлении профиля';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<main class="edit-profile-container">
    <div class="profile-edit-card">
        <h1>Редактирование профиля</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="avatar-upload">
                <div class="avatar-preview-container">
                    <img src="/assets/images/avatars/<?= !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'unknown.png' ?>" 
                         alt="Ваш аватар" 
                         class="avatar-preview"
                         id="avatarPreview">
                    <label for="avatarInput" class="avatar-edit-btn">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" class="avatar-input">
                </div>
            </div>
            
            <div class="form-group">
                <label for="fullName">Полное имя</label>
                <input type="text" id="fullName" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="bio">О себе</label>
                <textarea id="bio" name="bio"><?= htmlspecialchars($user['bio']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <button type="submit" class="save-btn">
                <i class="fas fa-save"></i> Сохранить изменения
            </button>
        </form>
    </div>
</main>

<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('avatarPreview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>