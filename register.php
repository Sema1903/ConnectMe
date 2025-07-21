<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    $result = registerUser($db, $username, $password, $full_name, $email);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
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
        
        <?php if ($success): ?>
            <div style="background-color: #e8f5e9; color: var(--secondary-color); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
                <div style="margin-top: 10px;">
                    <a href="/login.php" style="color: var(--primary-color); text-decoration: none;">Войти в аккаунт</a>
                </div>
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

<?php require_once 'includes/footer.php'; ?>