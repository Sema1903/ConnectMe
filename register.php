<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';



// В процессе регистрации добавить




if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';
$showTutorialQuestion = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $result = registerUser($db, $username, $password, $full_name, $email);
    
    if ($result['success']) {
        $success = $result['message'];
        $showTutorialQuestion = true;
        

    } else {
        $error = $result['message'];
    }
}

// Обработка ответа на вопрос об обучалке
if (isset($_POST['tutorial_response'])) {
    $response = $_POST['tutorial_response'];
    $userId = $_SESSION['new_user_id'] ?? null;
    
    if ($userId) {
        // Сохраняем предпочтение пользователя в БД
        $tutorialPreference = $response === 'yes' ? 1 : 0;
        $stmt = $db->prepare("UPDATE users SET wants_tutorial = ? WHERE id = ?");
        $stmt->execute([$tutorialPreference, $userId]);
        
        if ($response === 'yes') {
            // Перенаправляем на обучалку
            echo '<script>window.location.href="tutorial.php"</script>';
        } else {
            // Перенаправляем на главную
            header('Location: /');
            exit;
        }
    }
    
    // Если что-то пошло не так, просто перенаправляем на главную
    header('Location: /');
    exit;
}

require_once 'includes/header.php';
?>

<main class="main-content" style="width: 100%; display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 150px);">
    <div class="feed" style="max-width: 500px; width: 100%;">
        <h1 style="font-size: 1.8rem; margin-bottom: 20px; text-align: center;">Регистрация в ConnectMe</h1>
        
        <?php if ($error): ?>
            <div style="background-color: #ffebee; color: var(--accent-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success && !$showTutorialQuestion): ?>
            <div style="background-color: #e8f5e9; color: var(--secondary-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 10px;">
                    <a href="/login.php" style="color: var(--primary-color); text-decoration: none;">Войти в аккаунт</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($showTutorialQuestion): ?>
            <div style="background-color: #e8f5e9; color: var(--secondary-color); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <h3 style="margin-bottom: 15px;">Регистрация завершена успешно!</h3>
                <p style="margin-bottom: 20px;">Хотите пройти краткое обучение по использованию платформы?</p>
                       
                        <button id='tut' type="submit" class="post-action-btn" style="background-color: var(--primary-color); color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
                            <i class="fas fa-graduation-cap"></i> Да, пройти обучение
                        </button>
                
                <form method="POST" style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                    <input type="hidden" name="tutorial_response" value="no">
                  
                        <button type="submit" class="post-action-btn" style="background-color: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer;">
                            <i class="fas fa-times"></i> Нет, спасибо
                        </button>
                
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST" style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Имя пользователя</label>
                    <input type="text" name="username" placeholder="Введите имя пользователя" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none;" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Пароль</label>
                    <input type="password" name="password" placeholder="Введите пароль" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none;" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Полное имя</label>
                    <input type="text" name="full_name" placeholder="Введите ваше имя" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none;" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Email</label>
                    <input type="email" name="email" placeholder="Введите ваш email" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none;" required>
                </div>
                
                <button type="submit" class="post-action-btn" style="width: 100%; background-color: var(--primary-color); color: white; justify-content: center;">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; color: var(--gray-color);">
                Уже есть аккаунт? <a href="/login.php" style="color: var(--primary-color); text-decoration: none;">Войдите</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
h1{
    color: #000000;
}
/* Стили для кнопок выбора обучалки */
@media (prefers-color-scheme: dark) {
    .post-action-btn[style*="background-color: #6c757d"] {
        background-color: #5a6268 !important;
    }
    
    .post-action-btn[style*="background-color: #6c757d"]:hover {
        background-color: #4e555b !important;
    }
}

.dark-theme .post-action-btn[style*="background-color: #6c757d"] {
    background-color: #5a6268 !important;
}

.dark-theme .post-action-btn[style*="background-color: #6c757d"]:hover {
    background-color: #4e555b !important;
}
</style>
<style>
/* Темная тема в стиле Telegram для страницы регистрации */
@media (prefers-color-scheme: dark) {
    .main-content {
        background-color: #0f0f0f;
    }
    
    .feed {
        background-color: #1e1e1e;
        border: 1px solid #2d2d2d;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    h1 {
        color: #ffffff;
    }
    
    /* Стили для сообщений об ошибках и успехе */
    div[style*="background-color: #ffebee"] {
        background-color: #422 !important;
        color: #ff6b6b !important;
        border: 1px solid #ff4757;
    }
    
    div[style*="background-color: #e8f5e9"] {
        background-color: #242 !important;
        color: #51cf66 !important;
        border: 1px solid #2ed573;
    }
    
    div[style*="background-color: #e8f5e9"] a[href="/login.php"] {
        color: #5D93B5 !important;
        text-decoration: underline !important;
    }
    
    div[style*="background-color: #e8f5e9"] a[href="/login.php"]:hover {
        color: #7ab0d3 !important;
    }
    
    /* Стили для формы */
    label {
        color: #e1e1e1 !important;
    }
    
    input[type="text"],
    input[type="password"],
    input[type="email"] {
        background-color: #2d2d2d !important;
        border: 1px solid #3d3d3d !important;
        color: #e1e1e1 !important;
    }
    
    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="email"]:focus {
        border-color: #5D93B5 !important;
        background-color: #3d3d3d !important;
    }
    
    input[type="text"]::placeholder,
    input[type="password"]::placeholder,
    input[type="email"]::placeholder {
        color: #a0a0a0 !important;
    }
    
    /* Стили для кнопки */
    .post-action-btn {
        background-color: #5D93B5 !important;
        border: none !important;
        transition: all 0.3s ease !important;
    }
    
    .post-action-btn:hover {
        background-color: #4A7A99 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
    
    /* Стили для ссылок */
    div[style*="color: var(--gray-color)"] {
        color: #a0a0a0 !important;
    }
    
    a[href="/login.php"] {
        color: #5D93B5 !important;
        transition: color 0.3s ease;
    }
    
    a[href="/login.php"]:hover {
        color: #7ab0d3 !important;
        text-decoration: underline !important;
    }
}

/* Принудительное применение темной темы */
.dark-theme .main-content {
    background-color: #0f0f0f;
}

.dark-theme .feed {
    background-color: #1e1e1e;
    border: 1px solid #2d2d2d;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dark-theme h1 {
    color: #ffffff;
}

.dark-theme div[style*="background-color: #ffebee"] {
    background-color: #422 !important;
    color: #ff6b6b !important;
    border: 1px solid #ff4757;
}

.dark-theme div[style*="background-color: #e8f5e9"] {
    background-color: #242 !important;
    color: #51cf66 !important;
    border: 1px solid #2ed573;
}

.dark-theme div[style*="background-color: #e8f5e9"] a[href="/login.php"] {
    color: #5D93B5 !important;
    text-decoration: underline !important;
}

.dark-theme div[style*="background-color: #e8f5e9"] a[href="/login.php"]:hover {
    color: #7ab0d3 !important;
}

.dark-theme label {
    color: #e1e1e1 !important;
}

.dark-theme input[type="text"],
.dark-theme input[type="password"],
.dark-theme input[type="email"] {
    background-color: #2d2d2d !important;
    border: 1px solid #3d3d3d !important;
    color: #e1e1e1 !important;
}

.dark-theme input[type="text"]:focus,
.dark-theme input[type="password"]:focus,
.dark-theme input[type="email"]:focus {
    border-color: #5D93B5 !important;
    background-color: #3d3d3d !important;
}

.dark-theme input[type="text"]::placeholder,
.dark-theme input[type="password"]::placeholder,
.dark-theme input[type="email"]::placeholder {
    color: #a0a0a0 !important;
}

.dark-theme .post-action-btn {
    background-color: #5D93B5 !important;
    border: none !important;
    transition: all 0.3s ease !important;
}

.dark-theme .post-action-btn:hover {
    background-color: #4A7A99 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
}

.dark-theme div[style*="color: var(--gray-color)"] {
    color: #a0a0a0 !important;
}

.dark-theme a[href="/login.php"] {
    color: #5D93B5 !important;
    transition: color 0.3s ease;
}

.dark-theme a[href="/login.php"]:hover {
    color: #7ab0d3 !important;
    text-decoration: underline !important;
}

/* Плавные переходы для темной темы */
.feed,
input[type="text"],
input[type="password"],
input[type="email"],
.post-action-btn,
a {
    transition: all 0.3s ease;
}

/* Улучшенные стили для мобильных устройств в темной теме */
@media (max-width: 768px) {
    @media (prefers-color-scheme: dark),
    .dark-theme {
        .feed {
            margin: 20px;
            padding: 20px;
            border: none;
            box-shadow: none;
        }
        
        .main-content {
            align-items: flex-start !important;
            padding-top: 50px;
        }
        
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            font-size: 16px !important; /* Предотвращает масштабирование на iOS */
        }
    }
}

/* Дополнительные улучшения для формы регистрации */
@media (prefers-color-scheme: dark),
.dark-theme {
    .feed {
        backdrop-filter: blur(10px);
    }
    
    input[type="text"],
    input[type="password"],
    input[type="email"] {
        transition: all 0.3s ease;
    }
    
    input[type="text"]:hover,
    input[type="password"]:hover,
    input[type="email"]:hover {
        border-color: #5D93B5 !important;
    }
    
    .post-action-btn {
        position: relative;
        overflow: hidden;
    }
    
    .post-action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .post-action-btn:hover::before {
        left: 100%;
    }
}

/* Анимация появления формы */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.feed {
    animation: slideInUp 0.7s ease-out;
}

/* Специальные стили для иконок в темной теме */
@media (prefers-color-scheme: dark),
.dark-theme {
    .post-action-btn i {
        color: white !important;
    }
}

/* Валидация полей ввода */
@media (prefers-color-scheme: dark),
.dark-theme {
    input:invalid {
        border-color: #ff4757 !important;
    }
    
    input:valid {
        border-color: #2ed573 !important;
    }
    
    input:focus:invalid {
        box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.2) !important;
    }
    
    input:focus:valid {
        box-shadow: 0 0 0 3px rgba(46, 213, 115, 0.2) !important;
    }
}

/* Эффект при наведении на карточку */
@media (prefers-color-scheme: dark),
.dark-theme {
    .feed:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        transform: translateY(-5px);
        transition: all 0.3s ease;
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
<script>
    let a = document.getElementById('tut');
    a.addEventListener('click', ()=>{
        window.location.href = 'tutorial.php';
    });
</script>
<?php require_once 'includes/footer.php'; ?>