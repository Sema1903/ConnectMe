<?php  
    // Подключаем файлы только если они существуют
    $authFile = __DIR__ . '/auth.php';
    $functionsFile = __DIR__ . '/functions.php';
    
    if (file_exists($authFile)) {
        require_once $authFile;
    }
    
    if (file_exists($functionsFile)) {
        require_once $functionsFile;
    }
?>
<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= isset($pageTitle) ? $pageTitle : 'ConnectMe' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        :root {
            --tg-primary: #0088cc;
            --tg-secondary: #6bc259;
            --tg-bg: #ffffff;
            --tg-surface: #f8f9fa;
            --tg-text-primary: #000000;
            --tg-text-secondary: #707579;
            --tg-border: #e7e8ec;
            --tg-hover: #f5f5f5;
            --tg-accent: #e3f2fd;
            --tg-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
            --tg-radius: 8px;
            --tg-backdrop: rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--tg-bg);
            color: var(--tg-text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Header Styles */
        .navbar {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: var(--tg-bg);
            border-bottom: 1px solid var(--tg-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-right: auto;
        }

        .logo img {
            width: 32px;
            height: 32px;
            border-radius: 8px;
        }

        .logo a {
            font-size: 18px;
            font-weight: 700;
            color: var(--tg-primary);
            text-decoration: none;
        }

        /* Search */
        .search-container {
            flex: 1;
            max-width: 400px;
            margin: 0 20px;
        }

        .search-input {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input i {
            position: absolute;
            left: 16px;
            color: var(--tg-text-secondary);
            z-index: 1;
        }

        .search-input input {
            width: 100%;
            padding: 10px 16px 10px 44px;
            border: none;
            border-radius: 20px;
            background: var(--tg-surface);
            font-size: 15px;
            transition: all 0.2s ease;
        }

        .search-input input:focus {
            outline: none;
            background: var(--tg-bg);
            box-shadow: 0 0 0 2px var(--tg-primary);
        }

        /* Navigation */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: var(--tg-radius);
            color: var(--tg-text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--tg-hover);
            color: var(--tg-primary);
        }

        .nav-links i {
            font-size: 18px;
        }

        .nav-links span {
            font-size: 14px;
            font-weight: 500;
            margin-left: 6px;
        }

        /* Badges */
        .badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #ff4444;
            color: white;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            padding: 0 4px;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--tg-text-primary);
            cursor: pointer;
            padding: 8px;
            border-radius: var(--tg-radius);
            transition: background 0.2s ease;
            margin-right: 12px;
        }

        .mobile-menu-btn:hover {
            background: var(--tg-hover);
        }

        /* Sidebar Menu */
        .sidebar-menu {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100vh;
            background: var(--tg-bg);
            border-right: 1px solid var(--tg-border);
            z-index: 2000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .sidebar-menu.active {
            transform: translateX(300px);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        }

        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--tg-backdrop);
            z-index: 1999;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .sidebar-backdrop.active {
            display: block;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--tg-border);
            background: var(--tg-bg);
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--tg-primary);
        }

        .sidebar-user-info {
            flex: 1;
        }

        .sidebar-user-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--tg-text-primary);
            margin-bottom: 2px;
        }

        .sidebar-user-status {
            font-size: 14px;
            color: var(--tg-text-secondary);
        }

        .sidebar-items {
            flex: 1;
            padding: 10px 0;
            overflow-y: auto;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--tg-text-primary);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-item:hover,
        .sidebar-item.active {
            background: var(--tg-hover);
            border-left-color: var(--tg-primary);
        }

        .sidebar-item i {
            width: 24px;
            margin-right: 16px;
            font-size: 18px;
            color: var(--tg-text-secondary);
            text-align: center;
        }

        .sidebar-item span {
            flex: 1;
            font-size: 15px;
            font-weight: 500;
        }

        .sidebar-badge {
            background: var(--tg-primary);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--tg-border);
            background: var(--tg-bg);
        }

        /* Mobile Bottom Navigation */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--tg-bg);
            border-top: 1px solid var(--tg-border);
            z-index: 1000;
            padding-bottom: env(safe-area-inset-bottom);
        }

        .mobile-nav-items {
            display: flex;
            justify-content: space-around;
            padding: 8px 0;
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--tg-text-secondary);
            padding: 8px 12px;
            border-radius: var(--tg-radius);
            transition: all 0.2s ease;
            position: relative;
        }

        .mobile-nav-item.active {
            color: var(--tg-primary);
        }

        .mobile-nav-item:hover {
            background: var(--tg-hover);
        }

        .mobile-nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .mobile-nav-text {
            font-size: 11px;
            font-weight: 500;
        }

        .mobile-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links span {
                display: none;
            }
            
            .nav-links a {
                padding: 10px;
            }
            
            .search-container {
                margin: 0 12px;
                max-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 12px;
            }
            
            .search-container {
                display: none;
            }
            
            .nav-links {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            /* Изменено: скрываем название только на мобильных */
            .logo a {
                display: none;
            }
            
            .mobile-bottom-nav {
                display: block;
            }
            
            .sidebar-menu {
                width: 85%;
                left: -85%;
            }
            
            .sidebar-menu.active {
                transform: translateX(85%);
            }
            .sidebar-user{
                margin-left: 50px;
            }
        }

        @media (max-width: 480px) {
            .sidebar-menu {
                width: 90%;
                left: -90%;
            }
            
            .sidebar-menu.active {
                transform: translateX(90%);
            }
            
            .mobile-nav-text {
                font-size: 10px;
            }
            
            .mobile-nav-item {
                padding: 6px 8px;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .sidebar-item {
            animation: slideIn 0.3s ease forwards;
            opacity: 0;
        }

        .sidebar-item:nth-child(1) { animation-delay: 0.05s; }
        .sidebar-item:nth-child(2) { animation-delay: 0.1s; }
        .sidebar-item:nth-child(3) { animation-delay: 0.15s; }
        .sidebar-item:nth-child(4) { animation-delay: 0.2s; }
        .sidebar-item:nth-child(5) { animation-delay: 0.25s; }
        .sidebar-item:nth-child(6) { animation-delay: 0.3s; }
        .sidebar-item:nth-child(7) { animation-delay: 0.35s; }
        .sidebar-item:nth-child(8) { animation-delay: 0.4s; }
        .sidebar-item:nth-child(9) { animation-delay: 0.45s; }
        .sidebar-item:nth-child(10) { animation-delay: 0.5s; }
        .sidebar-item:nth-child(11) { animation-delay: 0.55s; }
    </style>
</head>
<body>
    <?php 
    // Подключаем файлы только если они существуют

    
    // Инициализируем переменные
    $user = null;
    $unreadMessagesCount = 0;
    $unreadNotificationsCount = 0;
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    // Проверяем авторизацию только если функция существует
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $user = function_exists('getCurrentUser') ? getCurrentUser($db) : null;
        
        if ($user) {
            // Получаем количество непрочитанных сообщений
            if (isset($db)) {
                $unreadMessagesCount = $db->querySingle("
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE receiver_id = {$user['id']} AND is_read = 0
                ");
                
                $unreadNotificationsCount = $db->querySingle("
                    SELECT COUNT(*) 
                    FROM notifications 
                    WHERE user_id = {$user['id']} AND is_read = 0
                ");
            }
            
            $_SESSION['unread_messages'] = $unreadMessagesCount;
            $_SESSION['unread_notifications'] = $unreadNotificationsCount;
        }
    }
    ?>
    
    <!-- Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    
    <!-- Sidebar Menu -->
    <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-header">
            <?php if ($user): ?>
            <div class="sidebar-user">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="/assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile" class="sidebar-user-avatar">
                <?php else: ?>
                    <div class="sidebar-user-avatar" style="background: #ccc; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user" style="font-size: 24px; color: white;"></i>
                    </div>
                <?php endif; ?>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div class="sidebar-user-status">Online</div>
                </div>
            </div>
            <?php else: ?>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar" style="background: #ccc; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="font-size: 24px; color: white;"></i>
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">Гость</div>
                    <div class="sidebar-user-status">Не авторизован</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-items">
            <a href="/" class="sidebar-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Главная</span>
            </a>
            
            <?php if ($user): ?>
                <a href="/profile.php?id=<?= $user['id'] ?>" class="sidebar-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user"></i>
                    <span>Мой профиль</span>
                </a>
                
                <a href="/friends.php" class="sidebar-item <?= $currentPage === 'friends.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-friends"></i>
                    <span>Друзья</span>
                </a>
                
                <a href="/messages.php" class="sidebar-item <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i>
                    <span>Сообщения</span>
                    <?php if ($unreadMessagesCount > 0): ?>
                        <span class="sidebar-badge"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="/groups.php" class="sidebar-item <?= $currentPage === 'groups.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Группы</span>
                </a>
                
                <a href="/notifications.php" class="sidebar-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span>Уведомления</span>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="sidebar-badge"><?= $unreadNotificationsCount ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="/photos.php" class="sidebar-item <?= $currentPage === 'photos.php' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i>
                    <span>Фотографии</span>
                </a>
                
                <a href="/music.php" class="sidebar-item <?= $currentPage === 'music.php' ? 'active' : '' ?>">
                    <i class="fas fa-music"></i>
                    <span>Музыка</span>
                </a>
                
                <a href="/games.php" class="sidebar-item <?= $currentPage === 'games.php' ? 'active' : '' ?>">
                    <i class="fas fa-gamepad"></i>
                    <span>Миниприложения</span>
                </a>
                

            <?php else: ?>
                <a href="/login.php" class="sidebar-item <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Войти</span>
                </a>
                
                <a href="/register.php" class="sidebar-item <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i>
                    <span>Регистрация</span>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <?php if ($user): ?>
            <a href="/logout.php" class="sidebar-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выйти</span>
            </a>
            <?php else: ?>
            <a href="/login.php" class="sidebar-item">
                <i class="fas fa-sign-in-alt"></i>
                <span>Войти</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="navbar">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo">
                <img src='icon-192x192.png' alt="ConnectMe">
                <a class='name' href="/">ConnectMe</a>
            </div>
            
            <div class="search-container">
                <form action="/search.php" method="get">
                    <div class="search-input">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="Поиск..." autocomplete="off">
                    </div>
                </form>
            </div>
            
            <ul class="nav-links">
                <li>
                    <a href="/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                        <i class="fas fa-home"></i>
                        <span>Главная</span>
                    </a>
                </li>
                
                <?php if ($user): ?>
                <li>
                    <a href="/games.php" class=" <?= $currentPage === 'games.php' ? 'active' : '' ?>">
                        <i class="fas fa-gamepad"></i>
                        <span>Миниприложения</span>
                    </a>
                </li>
                
                <li>
                    <a href="/messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">
                        <i class="fas fa-comments"></i>
                        <?php if ($unreadMessagesCount > 0): ?>
                            <span class="badge"><?= $unreadMessagesCount ?></span>
                        <?php endif; ?>
                        <span>Сообщения</span>
                    </a>
                </li>
                
                <li>
                    <a href="/notifications.php" class="<?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadNotificationsCount > 0): ?>
                            <span class="badge"><?= $unreadNotificationsCount ?></span>
                        <?php endif; ?>
                        <span>Уведомления</span>
                    </a>
                </li>
                
                <li>
                    <a href="/music.php" class="<?= $currentPage === 'music.php' ? 'active' : '' ?>">
                        <i class="fas fa-music"></i>
                        <span>Музыка</span>
                    </a>
                </li>
                
                <li>
                    <a href="/groups.php" class="<?= $currentPage === 'groups.php' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        <span>Группы</span>
                    </a>
                </li>
                <li>
                    <a href="/profile.php?id=<?= $user['id'] ?>" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                        <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden;">
                            <?php if (!empty($user['avatar'])): ?>
                                <img src="/assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile" width="32" height="32" style="object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle" style="font-size: 32px; color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <span>Профиль</span>
                    </a>
                </li>
                
                <?php else: ?>
                <li>
                    <a href="/login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Войти</span>
                    </a>
                </li>
                
                <li>
                    <a href="/register.php" class="<?= $currentPage === 'register.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Регистрация</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($user): ?>
            <a href="/logout.php" class="sidebar-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выйти</span>
            </a>
            <?php endif; ?>
            </ul>
        </div>
    </header>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav">
        <div class="mobile-nav-items">
            <a href="/" class="mobile-nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home mobile-nav-icon"></i>
                <!--<span class="mobile-nav-text">Главная</span>-->
            </a>
            
            <?php if ($user): ?>
            <a href="/friends.php" class="mobile-nav-item <?= $currentPage === 'friends.php' ? 'active' : '' ?>">
                <i class="fas fa-user-friends mobile-nav-icon"></i>
                <!--<span class="mobile-nav-text">Друзья</span>-->
            </a>
            
            <a href="/messages.php" class="mobile-nav-item <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
                <i class="fas fa-comments mobile-nav-icon"></i>
                <?php if ($unreadMessagesCount > 0): ?>
                    <span class="mobile-badge"><?= $unreadMessagesCount ?></span>
                <?php endif; ?>
                <!--<span class="mobile-nav-text">Сообщения</span>-->
            </a>
            
            <a href="/notifications.php" class="mobile-nav-item <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                <i class="fas fa-bell mobile-nav-icon"></i>
                <?php if ($unreadNotificationsCount > 0): ?>
                    <span class="mobile-badge"><?= $unreadNotificationsCount ?></span>
                <?php endif; ?>
                <!--<span class="mobile-nav-text">Уведомления</span>-->
            </a>
            
            <a href="/music.php" class="mobile-nav-item <?= $currentPage === 'music.php' ? 'active' : '' ?>">
                <i class="fas fa-music mobile-nav-icon"></i>
                <!--<span class="mobile-nav-text">Музыка</span>-->
            </a>
            
            <a href="/profile.php?id=<?= $user['id'] ?>" class="mobile-nav-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <div style="width: 24px; height: 24px; border-radius: 50%; overflow: hidden;">
                    <?php if (!empty($user['avatar'])): ?>
                        <img src="/assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" alt="Profile" width="24" height="24" style="object-fit: cover;">
                    <?php else: ?>
                        <i class="fas fa-user-circle mobile-nav-icon"></i>
                    <?php endif; ?>
                </div>
                <!--<span class="mobile-nav-text">Профиль</span>-->
            </a>
            
            <?php else: ?>
            <a href="/login.php" class="mobile-nav-item <?= $currentPage === 'login.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-in-alt mobile-nav-icon"></i>
                <span class="mobile-nav-text">Войти</span>
            </a>
            
            <a href="/register.php" class="mobile-nav-item <?= $currentPage === 'register.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus mobile-nav-icon"></i>
                <span class="mobile-nav-text">Регистрация</span>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuBtn = document.getElementById('mobileMenuBtn');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        
        function toggleSidebar() {
            sidebarMenu.classList.toggle('active');
            sidebarBackdrop.classList.toggle('active');
            document.body.style.overflow = sidebarMenu.classList.contains('active') ? 'hidden' : '';
        }
        
        if (menuBtn) {
            menuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }
        
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function() {
                toggleSidebar();
            });
        }
        
        const sidebarLinks = sidebarMenu.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                toggleSidebar();
            });
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.classList.contains('active')) {
                toggleSidebar();
            }
        });
        
        let startX = 0;
        sidebarMenu.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        sidebarMenu.addEventListener('touchmove', function(e) {
            const currentX = e.touches[0].clientX;
            if (currentX < startX - 50) {
                toggleSidebar();
            }
        });
    });
    </script>
    










    <style>
/* Убираем фокусировку на мобильных устройствах */
@media (max-width: 768px) {
    input:focus,
    textarea:focus,
    select:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: inherit !important;
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }
    
    /* Убираем подсветку при касании */
    input,
    textarea,
    select,
    button,
    a {
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
    }
    
    /* Предотвращаем увеличение на iOS */
    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="search"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
        transform: translateZ(0);
    }
    
    /* Отключаем действие масштабирования при фокусе */
    input:focus,
    textarea:focus {
        transform: scale(1) !important;
    }
}

/* Дополнительные стили для улучшения UX */
@media (max-width: 768px) {
    /* Плавное появление клавиатуры */
    input,
    textarea {
        transition: all 0.3s ease !important;
    }
    
    /* Убираем стандартное поведение iOS */
    * {
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Для WebKit браузеров */
    input:focus,
    textarea:focus {
        -webkit-user-modify: read-write-plaintext-only;
    }
}
</style>

<script>
// Скрипт для предотвращения фокусировки и масштабирования
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем мобильное устройство
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Добавляем класс для мобильных устройств
        document.documentElement.classList.add('is-mobile');
        
        // Отключаем масштабирование при фокусе
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(element => {
            // Убираем outline при фокусе
            element.addEventListener('focus', function(e) {
                this.style.outline = 'none';
                this.style.boxShadow = 'none';
                this.style.webkitAppearance = 'none';
            });
            
            // Предотвращаем изменение масштаба
            element.addEventListener('touchstart', function(e) {
                // Сохраняем текущий масштаб
                const viewport = document.querySelector('meta[name="viewport"]');
                if (viewport) {
                    /*viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');*/
                }
            });
            
            // Восстанавливаем после потери фокуса
            element.addEventListener('blur', function(e) {
                setTimeout(() => {
                    const viewport = document.querySelector('meta[name="viewport"]');
                    if (viewport) {
                        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0');
                    }
                }, 300);
            });
        });
        
        // Дополнительная защита от zoom
        document.addEventListener('touchstart', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                document.documentElement.style.zoom = "reset";
            }
        });
        
        // Убираем выделение текста при касании
        document.addEventListener('touchstart', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                //e.preventDefault();
                setTimeout(() => {
                    e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
                }, 0);
            }
        }, { passive: false });
    }
});

// Альтернативный подход - отключение масштабирования полностью
function disableZoom() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
}

// Включаем при загрузке для мобильных
if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    disableZoom();
    
    // Переотключаем при изменении ориентации
    window.addEventListener('orientationchange', disableZoom);
    window.addEventListener('resize', disableZoom);
}

// Простой способ - просто убираем outline
document.addEventListener('focusin', function(e) {
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        if (e.target.matches('input, textarea, select')) {
            /*e.target.style.outline = 'none';
            e.target.style.boxShadow = 'none';*/
        }
    }
});

// Убираем стандартное поведение браузера
/*document.addEventListener('touchstart', function(e) {
    if (e.target.matches('input, textarea, select')) {
        e.target.style.webkitUserSelect = 'none';
        e.target.style.userSelect = 'none';
    }
}, { passive: true });*/

// Восстанавливаем selection после фокуса
document.addEventListener('focus', function(e) {
    if (e.target.matches('input, textarea') && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        setTimeout(() => {
            e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
        }, 10);
    }
}, true);
</script>

<style>
/* Дополнительные гарантированные стили */
.is-mobile input:focus,
.is-mobile textarea:focus,
.is-mobile select:focus {
    outline: none !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
    border-color: inherit !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
}

/* Убираем подсветку для всех интерактивных элементов */
.is-mobile *:focus {
    outline: none !important;
}

/* Предотвращаем изменение масштаба */
.is-mobile {
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
}

/* Для iOS Safari */
@supports (-webkit-touch-callout: none) {
    .is-mobile input,
    .is-mobile textarea {
        font-size: 16px !important;
    }
}

/* Убираем стандартные стили форм в iOS */
.is-mobile input[type="text"],
.is-mobile input[type="password"],
.is-mobile input[type="email"],
.is-mobile input[type="search"],
.is-mobile textarea {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border-radius: 0;
}
</style>





<style>
    .name{
       color: #0589c6 !important;
    }
    .active{
        background: #f5f5f5 !important;
        color: #0589c6 !important;
    }
    a:hover{
        background: #f5f5f5 !important;
        color: #0589c6 !important;
    }
    @media (prefers-color-scheme: dark) {
        .active{
            background: #2a2a2a !important;
        }
        a:hover{
            background: #2a2a2a !important;
        }
        header{
            background: #1a1a1a !important;
        }
        body{
            background: #1a1a1a !important;
        }
    }
    @media (max-width: 768px) {
    .navbar {
        padding-top: max(12px, env(safe-area-inset-top)) !important;
        padding-left: max(12px, env(safe-area-inset-left)) !important;
        padding-right: max(12px, env(safe-area-inset-right)) !important;
    }
    
    body {
        padding-top: env(safe-area-inset-top) !important;
    }
    
    /* Дополнительно: убедитесь, что шапка прижата к верху */
    header {
        position: relative;
    }
    
    .navbar {
        position: sticky;
        top: 0;
    }
}
@media (prefers-color-scheme: dark) {
    .sidebar-menu{
        background: #1a1a1a !important;
    }
}
@media (prefers-color-scheme: light){
    .sidebar-menu{
        background: #ffffff !important;
    }
    .sidebar-user-name{
        color: #000000 !important;
    }
}
@media (max-width: 768px) {
    .sidebar-menu{
        padding-top: 100px !important;
    }
    .navbar {
        padding-top: max(16px, env(safe-area-inset-top)) !important;
        padding-bottom: 16px !important;
        padding-left: max(16px, env(safe-area-inset-left)) !important;
        padding-right: max(16px, env(safe-area-inset-right)) !important;
        min-height: 60px !important;
        height: auto !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
    }
    
    body {
        padding-top: calc(60px + env(safe-area-inset-top)) !important;
    }
    
    /* Увеличиваем логотип */
    .logo img {
        width: 40px !important;
        height: 40px !important;
    }
    
    /* Увеличиваем иконку меню */
    .mobile-menu-btn {
        font-size: 24px !important;
        padding: 10px !important;
    }
    
    /* Увеличиваем высоту навигации */
    .mobile-bottom-nav {
        height: 60px !important;
    }
    
    .mobile-nav-icon {
        font-size: 22px !important;
    }
}

/* Дополнительно: делаем шапку более высокой на всех устройствах */
.navbar {
    min-height: 56px;
    height: auto;
    align-items: center;
}

.logo img {
    width: 36px;
    height: 36px;
}

.mobile-menu-btn {
    font-size: 22px;
}
@media (max-width: 768px) {
    input:focus,
    textarea:focus,
    select:focus {
        outline: none !important;
        box-shadow: none !important;
        -webkit-tap-highlight-color: transparent !important;
    }
    
    input,
    textarea,
    select,
    button,
    a {
        -webkit-tap-highlight-color: transparent !important;
    }
    
    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="search"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
    }
}






</style>