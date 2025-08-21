<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="white.png">
    <title><?= isset($pageTitle) ? $pageTitle : 'ConnectMe' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Для Android/Windows -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#000000">
    <!-- Для iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">  


    
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <style>
        /* Фикс для масштабирования на iOS */
        input, textarea, select {
            font-size: 16px !important;
        }
        
        /* Для Safari */
        @supports (-webkit-touch-callout: none) {
            input, textarea, select {
                font-size: 16px !important;
            }
        }
        
        /* Фикс для Android */
        @media screen and (-webkit-min-device-pixel-ratio:0) {
            input, textarea, select {
                font-size: 16px !important;
            }
        }
    </style>
    
    <style>
    body {
  margin: 0;
  padding: env(safe-area-inset-top) env(safe-area-inset-right) 
           env(safe-area-inset-bottom) env(safe-area-inset-left);
  min-height: 100vh;       /* Вместо height */
  overflow-y: auto;        /* Разрешаем вертикальный скролл */
  -webkit-overflow-scrolling: touch;  /* Плавный скролл для iOS */
}
/* Мобильное меню */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--gray-color);
    cursor: pointer;
    padding: 10px;
}

@media (max-width: 768px) {
    .mobile-menu-btn {
        display: block;
        order: 1;
    }
    
    .logo {
        order: 2;
        flex: 1;
        text-align: center;
    }
    
    .nav-links {
        display: none;
        position: absolute;
        top: 60px;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        z-index: 100;
    }
    
    .nav-links.active {
        display: flex;
    }
    
    .nav-links li {
        margin: 0;
        width: 100%;
    }
    
    .nav-links a {
        padding: 12px 20px;
        border-radius: 0;
        justify-content: flex-start;
    }
    
    .nav-links i {
        margin-right: 10px;
    }
    
    .nav-links span {
        display: inline;
    }
}
.badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    line-height: 1;
}

.nav-links li {
    position: relative;
}

.nav-links li a {
    position: relative;
    display: flex;
    align-items: center;
}
.badge2 {
    position: absolute;
    top: 45px;
    right: 165px;
    background-color: #e74c3c;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
    line-height: 1;
}
@media (max-width: 768px) {
    .badge{
        top: 0px;
        right: 140px;
    }
}
</style>
</head>
<body>
    <?php require_once __DIR__ . '/auth.php';
    require_once 'includes/functions.php';

    $user = getCurrentUser($db);
    
    // Получаем количество непрочитанных сообщений
if (isLoggedIn()) {
    $unreadMessagesCount = $db->querySingle("
        SELECT COUNT(*) 
        FROM messages 
        WHERE receiver_id = {$user['id']} AND is_read = 0
    ");
    $_SESSION['unread_messages'] = $unreadMessagesCount;
}
    
    
    ?>
    <!-- Header/Navbar -->
    <header>
        <div class="navbar">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
                <?php if (isLoggedIn() && $_SESSION['unread_messages'] > 0): ?>
                    <span class="badge2"><?= $_SESSION['unread_messages'] ?></span>
                <?php endif; ?>
            </button>
            
            <div class="logo">
                <!--<i class="fas fa-users"></i> -->
                <i><img src = 'icon-192x192.png' width = '50px'></i>
                <a href="/">ConnectMe</a>
            </div>

            
            <div class="search-container">
                <form action="/search.php" method="get">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="Поиск..." autocomplete="off">
                    </div>
                </form>
            </div>
            
            <ul class="nav-links" id="navLinks">
                <li>
                    <a href="/">
                        <i class="fas fa-home"></i>
                        <span>Главная</span>
                    </a>
                </li>
                <li>
                    <a href="/friends.php">
                        <i class="fas fa-user-friends"></i>
                        <span>Друзья</span>
                    </a>
                </li>
                <li>
                    <a href="/groups.php">
                        <i class="fas fa-users"></i>
                        <span>Группы</span>
                    </a>
                </li>
                <li>
                    <a href="/messages.php">
                        <i class="fas fa-comments"></i>
                        <?php if (isLoggedIn() && $_SESSION['unread_messages'] > 0): ?>
                            <span class="badge"><?= $_SESSION['unread_messages'] ?></span>
                        <?php endif; ?>
                        <span>Сообщения</span>
                    </a>
                </li>
                <li>
                    <a href="/music.php">
                        <i class="fas fa-music"></i>
                        <span>Музыка</span>
                    </a>
                </li>
                <li>
                    <a href="/notifications.php">
                        <i class="fas fa-bell"></i>
                        <span>Уведомления</span>
                    </a>
                </li>
                <li>
                    <a href="/games.php">
                        <i class="fas fa-server"></i>
                        <span>Миниприложения</span>
                    </a>
                </li>
                <li>
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser($db); ?>
                        <a href="/profile.php?id=<?= $user['id'] ?>">
                            <div class="user-profile">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="/assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                            </div>
                            <span>Профиль</span>
                        </a>
                    <?php else: ?>
                        <a href="/login.php">
                            <div class="user-profile">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <span>Войти</span>
                        </a>
                    <?php endif; ?>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="mobile-logout">
                    <a href="/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Выйти</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    // Открытие/закрытие меню при клике на гамбургер
    menuBtn.addEventListener('click', function(e) {
        e.stopPropagation(); // Предотвращаем всплытие, чтобы не сработал закрывающий клик
        navLinks.classList.toggle('active');
    });
    
    // Закрытие меню при клике вне его
    document.addEventListener('click', function(e) {
        if (!navLinks.contains(e.target)) {
            navLinks.classList.remove('active');
        }
    });
    
    // Закрытие меню при клике на ссылку внутри меню
    navLinks.addEventListener('click', function(e) {
        if (e.target.tagName === 'A') {
            navLinks.classList.remove('active');
        }
    });
});
</script>