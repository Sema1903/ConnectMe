<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'ConnectMe' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
    </style>
</head>
<body>
    <!-- Header/Navbar -->
    <header>
        <div class="navbar">
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="logo">
                <i class="fas fa-users"></i>
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