<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
$names = ['Дефолтное', 'Зима близко', 'Hello World.', 'Гламурно на стиле', 'Устал', 'АУЕ', 'Marvel', 'Киберпанк', 'Спорт это жизнь', 'Спокойствие'];
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
    $cover = trim($_POST['cover'] ?? $user['cover'] ?? '1.jpg'); // По умолчанию 1.jpg
    
    // Валидация данных
    if (empty($full_name) || empty($email)) {
        $error = 'Имя и email обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Укажите корректный email';
    } else {
        // Обработка аватарки
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $upload_dir = 'assets/images/avatars/' . '/';
            
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
            $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ?, email = ?, avatar = ?, cover = ? WHERE id = ?");
            $stmt->bindValue(1, $full_name, SQLITE3_TEXT);
            $stmt->bindValue(2, $bio, SQLITE3_TEXT);
            $stmt->bindValue(3, $email, SQLITE3_TEXT);
            $stmt->bindValue(4, $avatar, SQLITE3_TEXT);
            $stmt->bindValue(5, $cover, SQLITE3_TEXT);
            $stmt->bindValue(6, $user['id'], SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $success = 'Профиль успешно обновлен';
                // Обновляем данные в сессии
                $_SESSION['user_avatar'] = $avatar;
                $_SESSION['user_cover'] = $cover;
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
                <label>Оформление профиля</label>
                <div class="cover-selector">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <label class="cover-option">
                            <input type="radio" name="cover" value="<?= $i ?>.jpg" <?= ($user['cover'] ?? '1.jpg') == "$i.jpg" ? 'checked' : '' ?>>
                            <img src="/assets/images/covers/<?= $i ?>.jpg" alt="Cover <?= $i ?>">
                            <p><?= $names[$i - 1]?></p>
                        </label>
                    <?php endfor; ?>
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

<style>
/* Стили для выбора обложки */
main{
    height: 1500px;
}
.cover-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.cover-option {
    position: relative;
    width: calc(20% - 10px);
    cursor: pointer;
    border-radius: 4px;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.cover-option:hover {
    border-color: var(--primary-color);
}

.cover-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.cover-option input[type="radio"]:checked + img {
    border: 2px solid var(--primary-color);
}

.cover-option img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid transparent;
}

.cover-option input[type="radio"]:checked + img {
    border-color: var(--primary-color);
}
</style>

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
<style>
/* Темная тема в стиле Telegram для страницы редактирования профиля */
@media (prefers-color-scheme: dark) {
    .edit-profile-container {
        background-color: #0f0f0f;
        min-height: calc(100vh - 150px);
        padding: 20px;
    }
    
    .profile-edit-card {
        background-color: #1e1e1e;
        border: 1px solid #2d2d2d;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        max-width: 800px;
        margin: 0 auto;
    }
    
    .profile-edit-card h1 {
        color: #ffffff;
        margin-bottom: 25px;
        text-align: center;
        font-size: 1.8rem;
    }
    
    /* Стили для уведомлений */
    .alert.error {
        background-color: #422 !important;
        color: #ff6b6b !important;
        border: 1px solid #ff4757;
    }
    
    .alert.success {
        background-color: #242 !important;
        color: #51cf66 !important;
        border: 1px solid #2ed573;
    }
    
    /* Стили для формы */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        color: #e1e1e1 !important;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group textarea {
        background-color: #2d2d2d !important;
        border: 1px solid #3d3d3d !important;
        color: #e1e1e1 !important;
        padding: 12px 15px;
        border-radius: 8px;
        width: 100%;
        box-sizing: border-box;
        font-size: 1rem;
    }
    
    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group textarea:focus {
        border-color: #5D93B5 !important;
        background-color: #3d3d3d !important;
        outline: none;
    }
    
    .form-group input[type="text"]::placeholder,
    .form-group input[type="email"]::placeholder,
    .form-group textarea::placeholder {
        color: #a0a0a0 !important;
    }
    
    /* Стили для загрузки аватарки */
    .avatar-upload {
        display: flex;
        justify-content: center;
        margin-bottom: 25px;
    }
    
    .avatar-preview-container {
        position: relative;
        width: 120px;
        height: 120px;
    }
    
    .avatar-preview {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #3d3d3d;
    }
    
    .avatar-edit-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background-color: #5D93B5;
        color: white;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: 2px solid #1e1e1e;
        transition: all 0.3s ease;
    }
    
    .avatar-edit-btn:hover {
        background-color: #4A7A99;
        transform: scale(1.1);
    }
    
    .avatar-input {
        display: none;
    }
    
    /* Стили для выбора обложки */
    .cover-selector {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 10px;
    }
    
    .cover-option {
        position: relative;
        width: calc(25% - 12px);
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .cover-option:hover {
        border-color: #5D93B5;
        transform: translateY(-2px);
    }
    
    .cover-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .cover-option img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
    }
    
    .cover-option p {
        margin: 5px 0 0 0;
        font-size: 0.8rem;
        color: #a0a0a0;
        text-align: center;
    }
    
    .cover-option input[type="radio"]:checked + img {
        border-color: #5D93B5;
        box-shadow: 0 0 0 2px #5D93B5;
    }
    
    /* Стили для кнопки сохранения */
    .save-btn {
        background-color: #5D93B5;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 20px auto 0;
        transition: all 0.3s ease;
    }
    
    .save-btn:hover {
        background-color: #4A7A99;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
}

/* Принудительное применение темной темы */
.dark-theme .edit-profile-container {
    background-color: #0f0f0f;
}

.dark-theme .profile-edit-card {
    background-color: #1e1e1e;
    border: 1px solid #2d2d2d;
}

.dark-theme .profile-edit-card h1 {
    color: #ffffff;
}

.dark-theme .alert.error {
    background-color: #422 !important;
    color: #ff6b6b !important;
    border: 1px solid #ff4757;
}

.dark-theme .alert.success {
    background-color: #242 !important;
    color: #51cf66 !important;
    border: 1px solid #2ed573;
}

.dark-theme .form-group label {
    color: #e1e1e1 !important;
}

.dark-theme .form-group input[type="text"],
.dark-theme .form-group input[type="email"],
.dark-theme .form-group textarea {
    background-color: #2d2d2d !important;
    border: 1px solid #3d3d3d !important;
    color: #e1e1e1 !important;
}

.dark-theme .form-group input[type="text"]:focus,
.dark-theme .form-group input[type="email"]:focus,
.dark-theme .form-group textarea:focus {
    border-color: #5D93B5 !important;
    background-color: #3d3d3d !important;
}

.dark-theme .avatar-preview {
    border: 3px solid #3d3d3d;
}

.dark-theme .avatar-edit-btn {
    background-color: #5D93B5;
}

.dark-theme .avatar-edit-btn:hover {
    background-color: #4A7A99;
}

.dark-theme .cover-option:hover {
    border-color: #5D93B5;
}

.dark-theme .cover-option p {
    color: #a0a0a0;
}

.dark-theme .save-btn {
    background-color: #5D93B5;
}

.dark-theme .save-btn:hover {
    background-color: #4A7A99;
}

/* Плавные переходы для темной темы */
.profile-edit-card,
.form-group input,
.form-group textarea,
.avatar-edit-btn,
.cover-option,
.save-btn {
    transition: all 0.3s ease;
}

/* Улучшенные стили для мобильных устройств в темной теме */
@media (max-width: 768px) {
    @media (prefers-color-scheme: dark),
    .dark-theme {
        .edit-profile-container {
            padding: 10px;
        }
        
        .profile-edit-card {
            padding: 20px;
            margin: 10px;
        }
        
        .cover-option {
            width: calc(33.333% - 12px);
        }
        
        .cover-option img {
            height: 80px;
        }
        
        .avatar-preview-container {
            width: 100px;
            height: 100px;
        }
    }
}

/* Дополнительные улучшения для формы редактирования */
@media (prefers-color-scheme: dark),
.dark-theme {
    .profile-edit-card {
        backdrop-filter: blur(10px);
    }
    
    /* Эффект при наведении на поля ввода */
    .form-group input:hover,
    .form-group textarea:hover {
        border-color: #5D93B5 !important;
    }
    
    /* Анимация для кнопки сохранения */
    .save-btn {
        position: relative;
        overflow: hidden;
    }
    
    .save-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .save-btn:hover::before {
        left: 100%;
    }
    
    /* Стили для выбранной обложки */
    .cover-option input[type="radio"]:checked ~ img {
        border: 3px solid #5D93B5 !important;
        box-shadow: 0 0 15px rgba(93, 147, 181, 0.4);
    }
    
    .cover-option input[type="radio"]:checked ~ p {
        color: #5D93B5 !important;
        font-weight: 600;
    }
}

/* Анимация появления формы */
@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.profile-edit-card {
    animation: fadeInScale 0.6s ease-out;
}

/* Валидация полей ввода */
@media (prefers-color-scheme: dark),
.dark-theme {
    input:invalid,
    textarea:invalid {
        border-color: #ff4757 !important;
    }
    
    input:valid,
    textarea:valid {
        border-color: #2ed573 !important;
    }
    
    input:focus:invalid,
    textarea:focus:invalid {
        box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.2) !important;
    }
    
    input:focus:valid,
    textarea:focus:valid {
        box-shadow: 0 0 0 3px rgba(46, 213, 115, 0.2) !important;
    }
}

/* Эффект при наведении на карточку */
@media (prefers-color-scheme: dark),
.dark-theme {
    .profile-edit-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        transform: translateY(-5px);
    }
}
</style>








<style>
:root {
    --primary-color: #1877f2;
    --secondary-color: #f0f2f5;
    --text-color: #050505;
    --text-secondary: #65676b;
    --card-bg: #ffffff;
    --border-color: #ddd;
}

.messages-container {
    display: flex;
    height: calc(100vh - 80px);
    max-width: 1200px;
    margin: 0 auto;
    background: var(--card-bg);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

/* Контакты */
.contacts-sidebar {
    width: 350px;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.contacts-header {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.contacts-search {
    position: relative;
    margin-top: 10px;
}

.contacts-search input {
    width: 100%;
    padding: 10px 15px 10px 35px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    outline: none;
    background: var(--secondary-color);
}

.contacts-search i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
}

.contact-item {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-color);
}

.contact-item:hover, .contact-item.active {
    background: var(--secondary-color);
}

.contact-avatar {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.contact-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.online-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #31a24c;
    border-radius: 50%;
    border: 2px solid var(--card-bg);
}

.contact-info {
    flex: 1;
    margin-left: 12px;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-preview {
    font-size: 0.9rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-time {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-left: 10px;
}

/* Чат */
.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chat-header {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: var(--secondary-color);
    background-image: url('assets/images/chat-bg-pattern.png');
    background-repeat: repeat;
    background-blend-mode: overlay;
}

.message {
    margin-bottom: 15px;
    display: flex;
}

.message-outgoing {
    justify-content: flex-end;
}

.message-incoming {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message-outgoing .message-bubble {
    background: var(--primary-color);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-incoming .message-bubble {
    background: var(--card-bg);
    color: var(--text-color);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.message-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 5px;
    text-align: right;
}

.message-incoming .message-time {
    color: var(--text-secondary);
    text-align: left;
}

.chat-input {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    background: var(--card-bg);
}

.chat-form {
    display: flex;
    align-items: center;
}

.chat-form input {
    flex: 1;
    padding: 12px 15px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    outline: none;
    background: var(--secondary-color);
}

.chat-form button {
    background: none;
    border: none;
    margin-left: 10px;
    cursor: pointer;
}

.send-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
}

/* Пустой чат */
.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    text-align: center;
    padding: 20px;
}

.empty-chat i {
    font-size: 5rem;
    margin-bottom: 20px;
    color: var(--secondary-color);
}

.empty-chat h2 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--text-color);
}

/* Адаптивность */
@media (max-width: 992px) {
    .contacts-sidebar {
        width: 300px;
    }
}

@media (max-width: 768px) {
    .messages-container {
        height: calc(100vh - 60px);
    }
    
    .contacts-sidebar {
        width: 100%;
        display: <?= isset($_GET['user_id']) ? 'none' : 'flex' ?>;
    }
    
    .chat-container {
        display: <?= isset($_GET['user_id']) ? 'flex' : 'none' ?>;
    }
    
    .mobile-back-btn {
        display: block;
        margin-right: 15px;
        font-size: 1.2rem;
    }
}

@media (min-width: 769px) {
    .mobile-back-btn {
        display: none !important;
    }
}
/* Чат */
.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative; /* Добавляем для позиционирования */
    margin-right: -160px;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: var(--secondary-color);
    background-image: url('assets/images/chat-bg-pattern.png');
    background-repeat: repeat;
    background-blend-mode: overlay;
    padding-bottom: 80px; /* Добавляем отступ снизу для input */
}

.message {
    margin-bottom: 15px;
    display: flex;
    width: 100%; /* Занимаем всю ширину */
}

.message-outgoing {
    justify-content: flex-end;
    padding-left: 15%; /* Уменьшаем отступ справа */
}

.message-incoming {
    justify-content: flex-start;
    padding-right: 15%; /* Уменьшаем отступ слева */
}

.message-bubble {
    max-width: 85%; /* Увеличиваем максимальную ширину */
    min-width: 30%; /* Добавляем минимальную ширину */
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

/* Фиксированное поле ввода */
.chat-input {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    background: var(--card-bg);
    position: fixed; /* Фиксируем внизу */
    bottom: 0;
    left: 0;
    right: 0;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

/* Адаптация для мобильных */
@media (max-width: 768px) {
    .messages-container {
        height: calc(100vh - 60px);
    }
    
    .contacts-sidebar {
        width: 100%;
        display: <?= isset($_GET['user_id']) ? 'none' : 'flex' ?>;
    }
    
    .chat-container {
        display: <?= isset($_GET['user_id']) ? 'flex' : 'none' ?>;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        height: 100vh;
        margin-top: 70px;
    }
    .chat-input {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        top: 600px;
        max-width: 100%;
    }
    
    .message-outgoing {
        padding-left: 10%;
    }
    
    .message-incoming {
        padding-right: 10%;
    }
    
    .message-bubble {
        max-width: 90%;
    }
    .chat-messages{
        margin-bottom: 200px;
    }
}

/* Для iPhone с "челкой" */
@supports(padding-bottom: env(safe-area-inset-bottom)) {
    .chat-input {
        padding-bottom: calc(15px + env(safe-area-inset-bottom));
    }
    
    .chat-messages {
        padding-bottom: calc(80px + env(safe-area-inset-bottom));
    }
}
:root {
    --tg-primary: #182533;
    --tg-secondary: #17212b;
    --tg-accent: #2b5278;
    --tg-message-out: #2b5278;
    --tg-message-in: #182533;
    --tg-text-primary: #ffffff;
    --tg-text-secondary: #8696a8;
    --tg-border: #1e2c3a;
    --tg-hover: #1e2c3a;
    --tg-active: #2b5278;
}

/* Применяем темную тему */
@media (prefers-color-scheme: dark) {
    .messages-container {
        background: var(--tg-secondary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .navbar{
        background: black;
    }
    /* Контакты */
    .contacts-sidebar {
        background: var(--tg-primary);
        border-right-color: var(--tg-border);
    }
    .mobile-nav-item.active{
        color: '#0088cc';
    }
    .contacts-header {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .contacts-search input {
        background: var(--tg-secondary);
        border-color: var(--tg-border);
        color: var(--tg-text-primary);
    }

    .contacts-search input::placeholder {
        color: var(--tg-text-secondary);
    }

    .contact-item {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .contact-item:hover {
        background: var(--tg-hover);
    }

    .contact-item.active {
        background: var(--tg-active);
    }

    .contact-name {
        color: var(--tg-text-primary);
    }

    .contact-preview {
        color: var(--tg-text-secondary);
    }

    .contact-time {
        color: var(--tg-text-secondary);
    }

    /* Чат */
    .chat-container {
        background: var(--tg-secondary);
    }

    .chat-header {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .chat-messages {
        background: var(--tg-secondary);
        background-image: none;
    }

    .message-outgoing .message-bubble {
        background: var(--tg-message-out);
        color: var(--tg-text-primary);
        border-bottom-right-radius: 4px;
    }

    .message-incoming .message-bubble {
        background: var(--tg-message-in);
        color: var(--tg-text-primary);
        border: 1px solid var(--tg-border);
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .message-time {
        color: var(--tg-text-secondary);
    }

    .chat-input {
        background: var(--tg-primary);
        border-top-color: var(--tg-border);
    }

    .chat-form input {
        background: var(--tg-secondary);
        border-color: var(--tg-border);
        color: var(--tg-text-primary);
    }

    .chat-form input::placeholder {
        color: var(--tg-text-secondary);
    }

    .send-icon {
        color: var(--tg-accent);
    }

    /* Пустой чат */
    .empty-chat {
        color: var(--tg-text-secondary);
        background: var(--tg-secondary);
    }

    .empty-chat h2 {
        color: var(--tg-text-primary);
    }

    .empty-chat i {
        color: var(--tg-text-secondary);
    }

    /* Скроллбар */
    .contacts-list::-webkit-scrollbar,
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .contacts-list::-webkit-scrollbar-track,
    .chat-messages::-webkit-scrollbar-track {
        background: var(--tg-primary);
    }

    .contacts-list::-webkit-scrollbar-thumb,
    .chat-messages::-webkit-scrollbar-thumb {
        background: var(--tg-border);
        border-radius: 3px;
    }

    .contacts-list::-webkit-scrollbar-thumb:hover,
    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: var(--tg-text-secondary);
    }

    /* Мобильная версия */
    @media (max-width: 768px) {
        .messages-container {
            background: var(--tg-secondary);
        }
        
        .contacts-sidebar {
            background: var(--tg-primary);
        }
        
        .chat-container {
            background: var(--tg-secondary);
        }
        
        .chat-input {
            background: var(--tg-primary);
        }
    }
}

/* Дополнительные улучшения для Telegram-like стиля */
.message-bubble {
    max-width: 65%;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.95rem;
    line-height: 1.4;
}

.message-outgoing .message-bubble {
    border-bottom-right-radius: 0;
}

.message-incoming .message-bubble {
    border-bottom-left-radius: 0;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 3px;
}

.contact-avatar {
    width: 40px;
    height: 40px;
}

.online-badge {
    background: #00c853;
    width: 10px;
    height: 10px;
    border: 2px solid var(--tg-primary);
}

.contacts-search i {
    color: var(--tg-text-secondary);
}

.mobile-back-btn {
    color: var(--tg-text-primary);
}
</style>
<style>
/* Исправление текста в боковом меню для темной темы */
@media (prefers-color-scheme: dark) {
    .sidebar-item {
        color: #ffffff !important;
    }
    .mobile-bottom-nav{
        background: #1a1a1a;
    }
    .sidebar-item:hover,
    .sidebar-item.active {
        color: var(--tg-primary) !important;
    }
    .sidebar-menu{
        background: black;
    }
    .sidebar-item i {
        color: #a8a8a8 !important;
    }
    .sidebar-item:hover i,
    .sidebar-item.active i {
        color: #0088cc !important;
    }
    
    .sidebar-item span {
        color: inherit !important;
    }
    .mobile-nav-item.active{
        color: #0088cc !important;
    }
    /* Улучшение контрастности */
    .sidebar-header {
        background: #1a1a1a !important;
        border-bottom: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-items {
        background: #1a1a1a !important;
        color: #1a1a1a1a !important;
    }
    
    .sidebar-footer {
        background: var(--tg-card-bg) !important;
        border-top: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-user-name {
        color: #ffffff !important;
    }
    
    .sidebar-user-status {
        color: #a8a8a8 !important;
    }
    
    /* Дополнительное улучшение видимости */
    .sidebar-item {
        border-left: 3px solid transparent;
    }
    
    .sidebar-item:hover,
    .sidebar-item.active {
        background: rgba(0, 136, 204, 0.1) !important;
        border-left-color: #0088cc !important;
        color: #0088cc !important;
    }
    
    /* Улучшение иконок */
    .sidebar-item i {
        filter: brightness(1.2);
    }
}

/* Дополнительные гарантии видимости текста */
.sidebar-item {
    font-weight: 500 !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.sidebar-item span {
    font-weight: 500 !important;
}

/* Принудительное применение стилей если автоматическая тема не сработала */
.sidebar-menu {
    color-scheme: light dark;
}

/* Резервный вариант для темной темы */
[data-theme="dark"] .sidebar-item,
.dark-mode .sidebar-item,
body.dark .sidebar-item {
    color: #ffffff !important;
    background-color: #1a1a1a !important;
}

[data-theme="dark"] .sidebar-item:hover,
[data-theme="dark"] .sidebar-item.active,
.dark-mode .sidebar-item:hover,

body.dark .sidebar-item:hover,
body.dark .sidebar-item.active {
    color: #0088cc !important;
}

/* Улучшение для мобильной версии */
@media (max-width: 768px) {
    .sidebar-item {
        font-size: 16px !important;
        padding: 16px 20px !important;
        margin-left: 50px;
    }
    
    .sidebar-item i {
        font-size: 20px !important;
        width: 28px !important;
    }
    
    .sidebar-item span {
        font-size: 16px !important;
        font-weight: 500 !important;
    }
}

/* Повышение контрастности для accessibility */
.sidebar-item {
    contrast: 4.5 !important;
}

/* Гарантия что текст всегда будет виден */
.sidebar-items {
        background: #1a1a1a !important;
        color: #1a1a1a1a !important;
    }

.sidebar-item {
    background: transparent !important;
}

.sidebar-item:hover {
    background: #2a2a2a !important;
}

.sidebar-item.active {
    background: #16303d !important;
    color: #0088cc !important;
}
</style>
<style>
    @media (prefers-color-scheme: light) {
        .mobile-menu-btn{
            color: black;
        }
        .mobile-menu-btn:hover{
            background: #f5f5f5;
        }
        .sidebar-items{
            background: #ffffff !important;
        }
        .sidebar-item{
            background: #ffffff !important;
        }
        .sidebar-item.active{
            background: #e3f2fc !important;
            border-left-color: #0589c6 !important;
            color: #000000 !important;
        }
        .sidebar-item:hover{
            background: #f5f5f5 !important;
            border-left-color: #0589c6 !important;
        }
        .mobile-nav-item.active{
            color: #0589c6;
        }
        .mobile-nav-item:hover{
            background: #f5f5f5;
        }
        .sidebar-badge{
            background: #0589c6;
        }
    }
</style>
<?php require_once 'includes/footer.php'; ?>