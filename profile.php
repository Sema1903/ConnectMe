<head>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&display=swap" rel="stylesheet">
</head>
<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Получаем параметр - может быть ID (число) или username (строка)
$profile_identifier = $_GET['id'] ?? $_GET['username'] ?? null;
$user = getCurrentUser($db);

if (!$profile_identifier) {
    if ($user) {
        header("Location: /profile.php?username=" . $user['username']);
        exit;
    } else {
        header('Location: /login.php');
        exit;
    }
}

// Определяем, что нам передали - ID или username
if (is_numeric($profile_identifier)) {
    $profile_user = getUserById($db, $profile_identifier);
} else {
    $profile_user = getUserByUsername($db, $profile_identifier);
}

if (!$profile_user) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Редирект с ID на username для SEO
if (isset($_GET['id']) && !is_numeric($profile_identifier)) {
    header("Location: /profile.php?username=" . $profile_user['username']);
    exit;
}

$is_own_profile = ($user && $user['id'] == $profile_user['id']);
$friends = getFriends($db, $profile_user['id']);
$posts = getPostsByUser($db, $profile_user['id']);
$groups = getGroups($db, $profile_user['id']);
require_once('includes/header.php');
?>
<?php
// Определяем стиль профиля на основе обложки
/*$cover_file = $profile_user['cover'] ?? 'default.jpg';
$profile_styles = [
    '1.jpg' => 'default',
    '2.jpg' => 'got',
    '3.jpg' => 'khakas',
    '4.jpg' => 'modern',
    '5.jpg' => 'cute',
    '6.jpg' => 'street',
    '7.jpg' => 'marvel',
    '8.jpg' => 'cyber',
    '9.jpg' => 'sport',
    '10.jpg' => 'nature'
];

$profile_style = $profile_styles[$cover_file] ?? 'default';*/




$active_cover = $profile_user['cover'] ?? '1.jpg';
$active_styles = [
    'bronze.jpg' => 'bronze',
    'silver.jpg' => 'silver',
    'gold.jpg' => 'gold',
    'vip.jpg' => 'vip',
    // остальные стандартные обложки
    '1.jpg' => 'default',
    '2.jpg' => 'got',
    '3.jpg' => 'khakas',
    '4.jpg' => 'modern',
    '5.jpg' => 'cute',
    '6.jpg' => 'street',
    '7.jpg' => 'marvel',
    '8.jpg' => 'cyber',
    '9.jpg' => 'sport',
    '10.jpg' => 'nature'
];

$profile_style = $active_styles[$active_cover] ?? 'default';

if ($profile_user) {
    $active_item = $db->querySingle("
        SELECT gi.type 
        FROM user_items ui
        JOIN game_items gi ON ui.item_id = gi.id
        WHERE ui.user_id = {$profile_user['id']} AND ui.is_active = 1 
        AND gi.type IN ('3rd lavel', '2nd lavel', '1st lavel', 'premium')
    ", true);
    if ($active_item) {
        switch ($active_item['type']) {
            case '3rd lavel': 
                $active_style = 'bronze';
                $active_cover = 'bronze.jpg';
                break;
            case '2nd lavel': 
                $active_style = 'silver';
                $active_cover = 'silver.jpg';
                break;
            case '1st lavel': 
                $active_style = 'gold';
                $active_cover = 'gold.jpg';
                break;
            case 'premium': 
                $active_style = 'vip';
                $active_cover = 'vip.jpg';
                break;
        }
        $profile_style = $active_style;
        $profile_user['cover'] = $active_cover;
        // Если у пользователя нет обложки или она не соответствует стилю
        /*if (!$profile_user['cover'] || !in_array($profile_user['cover'], ['bronze.jpg', 'silver.jpg', 'gold.jpg', 'vip.jpg'])) {
            $profile_user['cover'] = $active_cover;
        }*/
    }
}


?>

<style>
/* Общие стили */
.profile-container {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    gap: 20px;
}

.profile-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.profile-content {
    flex: 1;
    min-width: 0;
}

/* Шапка профиля */
.profile-header {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 20px;
    height: 400px;
}

.cover-container {
    position: relative;
    height: 400px;
    overflow: hidden;
}

.profile-cover {
    width: 100%;
    height: 55%;
    object-fit: cover;
}

.profile-info {
    display: flex;
    padding: 20px;
    position: relative;
    height: 0px;
}

.avatar-container {
    position: relative;
    margin-top: -75px;
    margin-right: 20px;
    z-index: 2;
}

.profile-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    border: 5px solid white;
    object-fit: cover;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-text {
    flex: 1;
}
/* В секции стилей */
.profile-style-got {
    font-family: 'Times New Roman', serif;
}

.profile-style-marvel {
    font-family: 'Bangers', cursive;
}

.profile-style-cyber {
    font-family: 'Courier New', monospace;
}
.profile-text h1 {
    margin: 0;
    font-size: 28px;
    color: #333;
}

.profile-bio {
    margin: 5px 0;
    color: #666;
}

.profile-stats {
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
    font-size: 14px;
}

.profile-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Вкладки */
.profile-tabs {
    display: flex;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    overflow: hidden;
}

.profile-tab {
    padding: 15px 20px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
}

.profile-tab.active {
    color: #1877f2;
    border-bottom-color: #1877f2;
}

.profile-tab:hover {
    background: #f5f5f5;
}

/* Посты */
.post {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 15px;
    margin-bottom: 20px;
}

.post-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.post-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.post-user img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.user-details .name {
    font-weight: 600;
    color: #333;
    text-decoration: none;
}

.user-details .time {
    font-size: 12px;
    color: #999;
}

.post-content .post-text {
    margin-bottom: 15px;
    line-height: 1.5;
}

.post-image {
    width: 100%;
    border-radius: 8px;
    margin-top: 10px;
    cursor: pointer;
    max-height: 500px;
    object-fit: contain;
}

.post-stats {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    margin: 15px 0;
    color: #666;
    font-size: 14px;
}

.post-actions {
    display: flex;
    justify-content: space-around;
}

.post-action-btn {
    background: none;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.post-action-btn:hover {
    background: #f5f5f5;
}

/* Сайдбар */
.sidebar-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    padding: 15px;
    margin-bottom: 20px;
}

.sidebar-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

.friends-grid, .groups-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.friend-item, .group-item {
    text-align: center;
}

.friend-item img, .group-item img {
    width: 100%;
    border-radius: 8px;
    aspect-ratio: 1;
    object-fit: cover;
    margin-bottom: 5px;
}

.friend-item span, .group-item span {
    display: block;
    font-size: 12px;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.see-all {
    display: block;
    text-align: center;
    color: #1877f2;
    text-decoration: none;
    font-size: 14px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .profile-header{
        height: 550px;
    }
    .cover-container {
        height: 300px;
    }
    .profile-container {
        flex-direction: column;
    }
    
    .profile-sidebar {
        width: 100%;
    }
    
    .profile-info {
        flex-direction: column;
        text-align: center;
        margin-top: -150px;
    }
    
    .avatar-container {
        margin: -75px auto 15px;
    }
    
    .profile-actions {
        justify-content: center;
    }
    
    .friends-grid, .groups-list {
        grid-template-columns: repeat(2, 1fr);
    }
    /* Добавьте в CSS профиля */
    .profile-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .profile-actions .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }

    .profile-actions .btn-secondary {
        background: #e4e6eb;
        color: #050505;
    }

    .profile-actions .btn-secondary:hover {
        background: #d8dadf;
    }

    .profile-actions .btn i {
        font-size: 0.9em;
    }
}
/* Добавьте в секцию стилей */
/* Стандартный стиль (1.jpg) */
.profile-style-default {
    --primary-color: #1877f2;
    --text-color: #333;
    --bg-color: #f0f2f5;
    --card-bg: white;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.3));
}

/* Игра Престолов (2.jpg) */
.profile-style-got {
    --primary-color: #8b0000;
    --text-color: #f8f8f8;
    --bg-color: #1a1a1a;
    --card-bg: #2d2d2d;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(70,0,0,0.7));
    font-family: 'Times New Roman', serif;
}

/* Хакасский стиль (3.jpg) */
.profile-style-khakas {
    --primary-color: #4a6b22;
    --text-color: #2c2c2c;
    --bg-color: #f5f0e6;
    --card-bg: #fff9e6;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(94,70,44,0.4));
    font-family: 'Arial', sans-serif;
}

/* Модный и спокойный (4.jpg) */
.profile-style-modern {
    --primary-color: #6a5acd;
    --text-color: #333;
    --bg-color: #f8f9fa;
    --card-bg: white;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(106,90,205,0.3));
    font-family: 'Helvetica Neue', sans-serif;
}

/* Милый стиль (5.jpg) */
.profile-style-cute {
    --primary-color: #ff6b88;
    --text-color: #555;
    --bg-color: #fff0f5;
    --card-bg: #fff9fb;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(255,182,193,0.4));
    font-family: 'Comic Sans MS', cursive;
}

/* Пацанский стиль (6.jpg) */
.profile-style-street {
    --primary-color: #ff4500;
    --text-color: #333;
    --bg-color: #f0f0f0;
    --card-bg: white;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.6));
    font-family: 'Impact', sans-serif;
}

/* Марвеловский стиль (7.jpg) */
.profile-style-marvel {
    --primary-color: #ed1d24;
    --text-color: #f8f8f8;
    --bg-color: #1a1a1a;
    --card-bg: #2d2d2d;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(237,29,36,0.5));
    font-family: 'Bangers', cursive;
}

/* Неоновый киберстиль (8.jpg) */
.profile-style-cyber {
    --primary-color: #0ff;
    --text-color: #fff;
    --bg-color: #0a0a20;
    --card-bg: #1a1a3a;
    --cover-overlay: linear-gradient(to bottom, rgba(0,255,255,0.1), rgba(0,255,255,0.3));
    font-family: 'Courier New', monospace;
}

/* Спортивный стиль (9.jpg) */
.profile-style-sport {
    --primary-color: #ff6600;
    --text-color: #333;
    --bg-color: #f5f5f5;
    --card-bg: white;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(255,102,0,0.4));
    font-family: 'Arial Black', sans-serif;
}

/* Природный голубой (10.jpg) */
.profile-style-nature {
    --primary-color: #4682b4;
    --text-color: #333;
    --bg-color: #f0f8ff;
    --card-bg: white;
    --cover-overlay: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(70,130,180,0.3));
    font-family: 'Georgia', serif;
}

/* Общие стили, которые будут использовать CSS-переменные */
.profile-container {
    background-color: var(--bg-color);
    color: var(--text-color);
}

.sidebar-card, .profile-header, .profile-tabs, .post {
    background-color: var(--card-bg);
    color: var(--text-color);
}

.profile-tab.active, .btn-primary {
    background-color: var(--primary-color);
    color: white;
}

.profile-cover {
    position: relative;
}

.profile-cover::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--cover-overlay);
}
<style>
    /* Базовые стили */
    :root {
        --primary-color: #1877f2;
        --text-color: #333;
        --bg-color: #f0f2f5;
        --card-bg: white;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        color: var(--text-color);
    }
    
    .profile-container {
        display: flex;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        gap: 20px;
        min-height: 100vh;
    }
    
    /* Стили для разных обложек */
    /* 1. Стандартный стиль */
    .profile-style-default {
        --primary-color: #1877f2;
        --text-color: #333;
        --bg-color: #f0f2f5;
        --card-bg: white;
        background: var(--bg-color);
    }
    
    /* 2. Игра Престолов */
    .profile-style-got {
        --primary-color: #8b0000;
        --text-color: #f8f8f8;
        --card-bg: rgba(45, 45, 45, 0.8);
        background: url('/assets/images/backgrounds/got-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Times New Roman', serif;
    }
    
    .profile-style-got .profile-header {
        border: 2px solid #8b0000;
        box-shadow: 0 0 15px rgba(139, 0, 0, 0.5);
    }
    
    .profile-style-got .profile-tab.active {
        background: #8b0000;
        color: gold;
        font-weight: bold;
    }
    
    .profile-style-got .btn-primary {
        background: linear-gradient(to right, #8b0000, #5a0000);
        border: 1px solid gold;
        font-family: 'Russo One', sans-serif;
    }
    
    /* 3. Хакасский стиль */
    .profile-style-khakas {
        --primary-color: #4a6b22;
        --text-color: #2c2c2c;
        --card-bg: rgba(255, 249, 230, 0.9);
        background: url('/assets/images/backgrounds/khakas-bg.jpg') no-repeat center center fixed;
        background-size: cover;
    }
    
    .profile-style-khakas .profile-header {
        border-radius: 0;
        border: 3px dashed #4a6b22;
    }
    
    .profile-style-khakas .profile-avatar {
        border: 3px solid #8a6d3b;
    }
    
    /* 4. Модный и спокойный */
    .profile-style-modern {
        --primary-color: #6a5acd;
        --text-color: #333;
        --card-bg: rgba(255, 255, 255, 0.95);
        background: url('/assets/images/backgrounds/modern-bg.jpg') no-repeat center center fixed;
        background-size: cover;
    }
    
    .profile-style-modern .profile-header {
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(106, 90, 205, 0.2);
    }
    
    .profile-style-modern .btn {
        border-radius: 50px;
    }
    
    /* 5. Милый стиль */
    .profile-style-cute {
        --primary-color: #ff6b88;
        --text-color: #555;
        --card-bg: rgba(255, 249, 251, 0.95);
        background: url('/assets/images/backgrounds/cute-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Comic Sans MS', cursive;
    }
    
    .profile-style-cute .profile-header {
        border-radius: 30px 0 30px 0;
        box-shadow: 0 5px 15px rgba(255, 107, 136, 0.3);
    }
    
    .profile-style-cute .btn {
        border-radius: 15px;
    }
    
    /* 6. Пацанский стиль */
    .profile-style-street {
        --primary-color: #ff4500;
        --text-color: #333;
        --card-bg: rgba(255, 255, 255, 0.9);
        background: url('/assets/images/backgrounds/street-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Impact', sans-serif;
    }
    
    .profile-style-street .profile-header {
        border: 3px solid #ff4500;
        box-shadow: 0 0 20px rgba(255, 69, 0, 0.4);
    }
    
    .profile-style-street .btn {
        transform: skew(-15deg);
        border: 2px solid black;
    }
    
    /* 7. Марвеловский стиль */
    .profile-style-marvel {
        --primary-color: #ed1d24;
        --text-color: #f8f8f8;
        --card-bg: rgba(45, 45, 45, 0.9);
        background: url('/assets/images/backgrounds/marvel-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Bangers', cursive;
    }
    
    .profile-style-marvel .profile-header {
        border: 3px solid #ed1d24;
        box-shadow: 0 0 30px rgba(237, 29, 36, 0.6);
    }
    
    .profile-style-marvel .btn {
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    
    /* 8. Неоновый киберстиль */
    .profile-style-cyber {
        --primary-color: #0ff;
        --text-color: #fff;
        --card-bg: rgba(26, 26, 58, 0.9);
        background: url('/assets/images/backgrounds/cyber-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Courier New', monospace;
    }
    
    .profile-style-cyber .profile-header {
        border: 1px solid #0ff;
        box-shadow: 0 0 20px rgba(0, 255, 255, 0.7);
    }
    
    .profile-style-cyber .btn {
        background: black;
        border: 1px solid #0ff;
        color: #0ff;
    }
    
    /* 9. Спортивный стиль */
    .profile-style-sport {
        --primary-color: #ff6600;
        --text-color: #333;
        --card-bg: rgba(255, 255, 255, 0.9);
        background: url('/assets/images/backgrounds/sport-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Arial Black', sans-serif;
    }
    
    .profile-style-sport .profile-header {
        border-radius: 0;
        border-top: 5px solid #ff6600;
    }
    
    .profile-style-sport .btn {
        background: linear-gradient(to right, #ff6600, #ff8c00);
        color: white;
        font-weight: bold;
    }
    
    /* 10. Природный голубой */
    .profile-style-nature {
        --primary-color: #4682b4;
        --text-color: #333;
        --card-bg: rgba(255, 255, 255, 0.9);
        background: url('/assets/images/backgrounds/nature-bg.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Georgia', serif;
    }
    
    .profile-style-nature .profile-header {
        border-radius: 0 0 20px 20px;
        border-top: 5px solid #4682b4;
    }
    
    .profile-style-nature .btn {
        background: linear-gradient(to right, #4682b4, #5f9ea0);
        color: white;
    }
    
    /* Общие компоненты */
    .profile-sidebar {
        width: 300px;
        flex-shrink: 0;
    }
    
    .profile-content {
        flex: 1;
        min-width: 0;
    }
    
    .profile-header {
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 20px;
        height: 400px;
        position: relative;
    }
    
    .cover-container {
        position: relative;
        height: 400px;
        overflow: hidden;
    }
    
    .profile-cover {
        width: 100%;
        height: 55%;
        object-fit: cover;
    }
    
    .profile-info {
        display: flex;
        padding: 20px;
        position: relative;
        height: 0px;
    }
    
    .avatar-container {
        position: relative;
        margin-top: -75px;
        margin-right: 20px;
        z-index: 2;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        object-fit: cover;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .profile-text {
        flex: 1;
    }
    
    .profile-text h1 {
        margin: 0;
        font-size: 28px;
        color: var(--text-color);
    }
    
    .profile-bio {
        margin: 5px 0;
        color: var(--text-color);
    }
    
    .profile-stats {
        display: flex;
        gap: 15px;
        margin-top: 10px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: var(--text-color);
        font-size: 14px;
    }
    
    .profile-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .btn-secondary {
        background: #e4e6eb;
        color: #050505;
    }
    
    .btn-secondary:hover {
        background: #d8dadf;
    }
    
    .btn i {
        font-size: 0.9em;
    }
        /* Базовый сброс стилей */
        * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    /* 1. Стандартный стиль - Техно-футуризм */
    .profile-style-default {
        --primary: #00f0ff;
        --secondary: #ff00aa;
        --bg: #0a0a12;
        --card: rgba(20, 20, 40, 0.9);
        --text: #e0e0ff;
        background: 
            radial-gradient(circle at 20% 30%, var(--primary), transparent 50%),
            radial-gradient(circle at 80% 70%, var(--secondary), transparent 50%),
            var(--bg);
        font-family: 'Iceberg', cursive;
    }
    
    .profile-style-default .profile-header {
        background: var(--card);
        border: 1px solid var(--primary);
        box-shadow: 0 0 30px var(--primary);
        clip-path: polygon(0 0, 100% 0, 100% 90%, 80% 100%, 0 100%);
    }
    
    .profile-style-default .profile-avatar {
        border: 3px solid var(--primary);
        box-shadow: 0 0 20px var(--primary);
    }
    
    .profile-style-default .btn-primary {
        background: linear-gradient(45deg, var(--primary), var(--secondary));
        color: black;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: none;
    }
    
    /* 2. Игра Престолов - Средневековый готический */
    .profile-style-got {
        --primary: #8b0000;
        --secondary: #d4af37;
        --bg: #121212;
        --card: rgba(30, 30, 30, 0.9);
        --text: #f0f0f0;
        background: 
            url('/assets/images/backgrounds/got-bg.jpg') center/cover no-repeat,
            linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
        font-family: 'Russo One', sans-serif;
    }
    
    .profile-style-got .profile-header {
        background: var(--card);
        border-left: 10px solid var(--primary);
        border-right: 10px solid var(--primary);
        position: relative;
        overflow: hidden;
    }
    
    .profile-style-got .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary), var(--secondary), var(--primary));
    }
    
    .profile-style-got .profile-avatar {
        border: 3px double var(--secondary);
        filter: sepia(0.3);
    }
    
    /* 3. Хакасский стиль - Этнический шаманизм */
    .profile-style-khakas {
        --primary: #4a6b22;
        --secondary: #8a6d3b;
        --bg: #f5f0e6;
        --card: rgba(255, 252, 240, 0.95);
        --text: #3a3a3a;
        background: 
            url('/assets/images/backgrounds/khakas-pattern.png'),
            var(--bg);
        font-family: 'Marck Script', cursive;
    }
    
    .profile-style-khakas .profile-header {
        background: var(--card);
        border: 3px dashed var(--primary);
        border-radius: 0 0 30px 30px;
        background-image: url('/assets/images/backgrounds/khakas-border.png');
        background-repeat: repeat-x;
        background-position: bottom;
    }
    
    .profile-style-khakas .profile-avatar {
        border: 3px solid var(--secondary);
        box-shadow: 5px 5px 0 var(--primary);
    }
    
    /* 4. Модный и спокойный - Неоморфизм */
    .profile-style-modern {
        --primary: #6a5acd;
        --secondary: #9370db;
        --bg: #f0f0f5;
        --card: rgba(255, 255, 255, 0.7);
        --text: #333;
        background: var(--bg);
        font-family: 'Segoe UI', sans-serif;
    }
    
    .profile-style-modern .profile-header {
        background: var(--card);
        border-radius: 25px;
        box-shadow: 
            8px 8px 15px rgba(0, 0, 0, 0.1),
            -8px -8px 15px rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
    }
    
    .profile-style-modern .profile-avatar {
        box-shadow: 
            5px 5px 10px rgba(0, 0, 0, 0.1),
            -5px -5px 10px rgba(255, 255, 255, 0.8);
    }
    
    /* 5. Милый стиль - Каваий */
    .profile-style-cute {
        --primary: #ff6b88;
        --secondary: #ffb6c1;
        --bg: #fff0f5;
        --card: rgba(255, 255, 255, 0.9);
        --text: #555;
        background: 
            url('/assets/images/backgrounds/cute-pattern.png'),
            var(--bg);
        font-family: 'Pacifico', cursive;
    }
    
    .profile-style-cute .profile-header {
        background: var(--card);
        border-radius: 50px 0 50px 0;
        border: 3px dotted var(--primary);
        box-shadow: 0 10px 20px rgba(255, 107, 136, 0.2);
    }
    
    .profile-style-cute .profile-avatar {
        border: 3px solid var(--secondary);
        shape-outside: circle(50%);
    }
    
    /* 6. Пацанский стиль - Уличный граффити */
    .profile-style-street {
        --primary: #ff4500;
        --secondary: #000;
        --bg: #e0e0e0;
        --card: rgba(255, 255, 255, 0.9);
        --text: #333;
        background: 
            url('/assets/images/backgrounds/street-graffiti.png'),
            var(--bg);
        font-family: 'Rubik Mono One', sans-serif;
    }
    
    .profile-style-street .profile-header {
        background: var(--card);
        border: 5px solid var(--secondary);
        transform: rotate(-1deg);
        box-shadow: 10px 10px 0 var(--primary);
    }
    
    .profile-style-street .profile-avatar {
        border: 3px solid var(--primary);
        filter: contrast(1.2);
    }
    
    /* 7. Марвеловский стиль - Комиксы */
    .profile-style-marvel {
        --primary: #ed1d24;
        --secondary: #f78f1e;
        --bg: #121212;
        --card: rgba(30, 30, 30, 0.9);
        --text: #fff;
        background: 
            url('/assets/images/backgrounds/marvel-comic.png'),
            var(--bg);
        font-family: 'Bangers', cursive;
    }
    .profile-style-marvel .profile-header {
        background: var(--card);
        border: 5px solid var(--primary);
        box-shadow: 0 0 0 5px var(--secondary);
        clip-path: polygon(0 0, 100% 0, 100% 80%, 90% 100%, 0 100%);
    }
    
    .profile-style-marvel .profile-avatar {
        border: 3px solid var(--secondary);
        box-shadow: 5px 5px 0 var(--primary);
    }
    
    /* 8. Неоновый киберстиль - Киберпанк */
    .profile-style-cyber {
        --primary: #0ff;
        --secondary: #f0f;
        --bg: #0a0a20;
        --card: rgba(20, 20, 50, 0.9);
        --text: #fff;
        background: 
            linear-gradient(45deg, 
                rgba(0, 255, 255, 0.1) 0%, 
                rgba(255, 0, 255, 0.1) 100%),
            url('/assets/images/backgrounds/cyber-grid.png'),
            var(--bg);
        font-family: 'Press Start 2P', cursive;
    }
    .profile-style-cyber .comment{
        background: var(--card);
        border: 1px solid var(--primary);
    }
    .profile-style-cyber .profile-header {
        background: var(--card);
        border: 1px solid var(--primary);
        box-shadow: 
            0 0 10px var(--primary),
            0 0 20px var(--secondary);
        position: relative;
        overflow: hidden;
    }
    
    .profile-style-cyber .profile-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            linear-gradient(45deg, 
                transparent 48%, 
                var(--primary) 49%, 
                var(--secondary) 51%, 
                transparent 52%);
        background-size: 10px 10px;
        opacity: 0.1;
        pointer-events: none;
    }
    
    /* 9. Спортивный стиль - Экстрим */
    .profile-style-sport {
        --primary: #ff6600;
        --secondary: #0066ff;
        --bg: #f5f5f5;
        --card: rgba(255, 255, 255, 0.9);
        --text: #333;
        background: 
            url('/assets/images/backgrounds/sport-lines.png'),
            var(--bg);
        font-family: 'Arial Black', sans-serif;
    }
    
    .profile-style-sport .profile-header {
        background: var(--card);
        border-top: 10px solid var(--primary);
        border-bottom: 10px solid var(--secondary);
        position: relative;
    }
    
    .profile-style-sport .profile-header::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        transform: translateY(-50%);
    }
    
    /* 10. Природный голубой - Акварель */
    .profile-style-nature {
        --primary: #4682b4;
        --secondary: #5f9ea0;
        --bg: #f0f8ff;
        --card: rgba(255, 255, 255, 0.95);
        --text: #333;
        background: 
            url('/assets/images/backgrounds/nature-watercolor.jpg') center/cover no-repeat,
            var(--bg);
        font-family: 'Georgia', serif;
    }
    
    .profile-style-nature .profile-header {
        background: var(--card);
        border-radius: 0 0 30px 30px;
        box-shadow: 0 10px 20px rgba(70, 130, 180, 0.2);
        background-image: url('/assets/images/backgrounds/nature-border.png');
        background-repeat: repeat-x;
        background-position: bottom;
    }
    
    .profile-style-nature .profile-avatar {
        border: 3px solid var(--primary);
        filter: drop-shadow(5px 5px 5px rgba(70, 130, 180, 0.3));
    }
    
    /* Общие компоненты (адаптированные под все стили) */
    .profile-container {
        display: flex;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        gap: 20px;
        min-height: 100vh;
        color: var(--text);
    }
    
    .profile-sidebar {
        width: 300px;
        flex-shrink: 0;
    }
    
    .profile-content {
        flex: 1;
        min-width: 0;
    }
    
    .profile-header {
        height: 400px;
        margin-bottom: 20px;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .cover-container {
        position: relative;
        height: 400px;
        overflow: hidden;
    }
    
    .profile-cover {
        width: 100%;
        height: 55%;
        object-fit: cover;
    }
    
    .profile-info {
        display: flex;
        padding: 20px;
        position: relative;
        height: 0px;
    }
    
    .avatar-container {
        position: relative;
        margin-top: -75px;
        margin-right: 20px;
        z-index: 2;
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        transition: all 0.3s ease;
    }
    
    .profile-text h1 {
        margin: 0;
        font-size: 28px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        color: black;
    }
    
    .profile-bio {
        margin: 5px 0;
        color: gray;
    }
    
    .profile-stats {
        display: flex;
        gap: 15px;
        margin-top: 10px;
        color: grey;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
    }
    
    .profile-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
        color: black;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 10px 20px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s ease;
        border: none;
        color: black;
        cursor: pointer;
    }
    
    .btn-primary {
        background: var(--primary);
        color: black;
    }
    
    .btn-secondary {
        background: rgba(0, 0, 0, 0.2);
        color: black;
        backdrop-filter: blur(5px);
    }
    
    .btn i {
        font-size: 0.9em;
    }
    
    /* Анимации и эффекты */
    .profile-avatar:hover {
        transform: scale(1.05);
        filter: brightness(1.1);
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        color: black;
    }
    
    /* Адаптивность */
    @media (max-width: 768px) {
        .profile-container {
            flex-direction: column;
        }
        
        .profile-sidebar {
            width: 100%;
        }
        
        .profile-header {
            height: auto;
        }
        
        .profile-info {
            flex-direction: column;
            text-align: center;
        }
        
        .avatar-container {
            margin: -75px auto 15px;
        }
    }    /* Базовые стили и сброс */
    :root {
        --mobile-breakpoint: 768px;
        --small-mobile-breakpoint: 480px;
    }
    
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    
    html {
        scroll-behavior: smooth;
    }
    
    body {
        font-family: 'Montserrat', sans-serif;
        line-height: 1.6;
        overflow-x: hidden;
    }
    
    /* Общие адаптивные компоненты */
    .profile-container {
        display: flex;
        flex-direction: column;
        max-width: 100%;
        min-height: 100vh;
        padding: 0;
        position: relative;
    }
    
    .profile-sidebar, .profile-content {
        width: 100%;
        padding: 15px;
    }
    
    .profile-header {
        height: auto;
        min-height: 300px;
        margin-bottom: 0;
        border-radius: 0 !important;
    }
    
    .cover-container {
        height: 400px;
    }
    
    .profile-info {
        flex-direction: column;
        text-align: center;
        padding: 15px;
        height: 200px;
        margin-top: 0px;
    }
    
    .avatar-container {
        margin: -60px auto 15px;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        margin-bottom: 70px;
    }
    
    .profile-text h1 {
        font-size: 24px;
    }
    
    .profile-actions {
        justify-content: center;
        margin-bottom: 70px;
    }
    .profile-stats{
        margin-bottom: 70px;
    }
    
    .btn {
        padding: 8px 15px;
        font-size: 14px;
    }
    .profile-cover{
        height: 400px;
    }
    /* Медиа-запросы для планшетов */
    @media (min-width: 768px) {
        .profile-container {
            flex-direction: row;
            padding: 20px;
        }
        
        .profile-sidebar {
            width: 300px;
            padding-right: 0;
        }
        
        .profile-content {
            flex: 1;
            padding-left: 20px;
        }
        
        .profile-header {
            border-radius: 10px !important;
            margin-bottom: 20px;
            height: 350px;
        }
        
        .cover-container {
            height: 200px;
        }
        
        .profile-info {
            flex-direction: row;
            text-align: left;
            padding: 20px;
        }
        
        .avatar-container {
            margin: -75px 20px 0 0;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
        }
    }
    
    /* Медиа-запросы для десктопов */
    @media (min-width: 1024px) {
        .cover-container {
            height: 250px;
        }
        
        .profile-header {
            height: 400px;
        }
    }
    
    /* Стили для очень маленьких экранов */
    @media (max-width: 480px) {
        .profile-text h1 {
            font-size: 20px;
        }
        
        .profile-actions {
            flex-direction: column;
            align-items: center;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
        .profile-cover{
            margin-bottom: 100px;
        }
        .cover-container {
            height: 200px;
            margin-bottom: 100px;
        }
        .profile-info{
            margin-bottom: 300px;
            margin-top: -100px;
        }
    }
    
    /* 1. Техно-футуризм - адаптивные модификации */
    .profile-style-default {
        --primary: #00f0ff;
        --secondary: #ff00aa;
        --bg: #0a0a12;
        --card: rgba(20, 20, 40, 0.95);
        --text: #e0e0ff;
    }
    
    @media (max-width: 768px) {
        .profile-style-default .profile-header {
            clip-path: none;
            border: none;
            box-shadow: 0 0 15px var(--primary);
        }
    }
    
    /* 2. Игра Престолов - адаптивные модификации */

    
    /* 3. Хакасский стиль - адаптивные модификации */
    /* Хакерский стиль (3.jpg) */
.profile-style-khakas{
    --primary-color: #00FF00; /* Классический зеленый хакерский */
    --secondary-color: #00AA00;
    --text-color: #00FF00;
    --bg-color: #000000;
    --card-bg: rgba(0, 20, 0, 0.9);
    background: 
        url('/assets/images/backgrounds/matrix-code.jpg') no-repeat center center fixed,
        linear-gradient(rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.9));
    background-size: cover;
    font-family: 'Courier New', monospace;
}
.profile-style-khakas .comment{
    border: 1px solid var(--primary-color);
    box-shadow: 0 0 10px var(--primary-color);
    background: var(--card-bg);
}
.profile-style-khakas .profile-header {
    border: 1px solid var(--primary-color);
    box-shadow: 0 0 10px var(--primary-color);
    background: var(--card-bg);
}

.profile-style-khakas .profile-avatar {
    border: 2px solid var(--primary-color);
    box-shadow: 0 0 10px var(--primary-color);
}

.profile-style-khakas .btn-primary {
    background: black;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    text-shadow: 0 0 5px var(--primary-color);
}





    
    /* 4. Неоморфизм - адаптивные модификации */
    .profile-style-modern {
        --primary: #6a5acd;
        --secondary: #9370db;
    }
    
    @media (max-width: 768px) {
        .profile-style-modern .profile-header {
            box-shadow: none;
            backdrop-filter: none;
        }
    }
    
    /* 5. Каваий стиль - адаптивные модификации */
    .profile-style-cute {
        --primary: #ff6b88;
        --secondary: #ffb6c1;
    }
    
    @media (max-width: 768px) {
        .profile-style-cute .profile-header {
            border-radius: 0 !important;
            border: none;
            border-bottom: 3px dotted var(--primary);
        }
    }
    
    /* 6. Граффити стиль - адаптивные модификации */
    .profile-style-street {
        --primary: #ff4500;
        --secondary: #000;
    }
    
    @media (max-width: 768px) {
        .profile-style-street .profile-header {
            transform: none;
            box-shadow: none;
            border: none;
            border-bottom: 5px solid var(--secondary);
        }
        
        .profile-style-street .btn {
            transform: none;
        }
    }
    
    /* 7. Комиксы - адаптивные модификации */
    .profile-style-marvel {
        --primary: #ed1d24;
        --secondary: #f78f1e;
    }
    
    @media (max-width: 768px) {
        .profile-style-marvel .profile-header {
            clip-path: none;
            border: none;
            border-bottom: 5px solid var(--primary);
        }
    }
    .profile-style-marvel .comment{
        background: var(--card);
        border: 5px solid var(--primary);
        box-shadow: 0 0 0 5px var(--secondary);
    }
    /* 8. Киберпанк - адаптивные модификации */
    .profile-style-cyber {
        --primary: #0ff;
        --secondary: #f0f;
    }
    
    @media (max-width: 768px) {
        .profile-style-cyber .profile-header::after {
            background-size: 5px 5px;
        }
    }
    /* 9. Спорт - адаптивные модификации */
    .profile-style-sport {
        --primary: #ff6600;
        --secondary: #0066ff;
    }
    
    @media (max-width: 768px) {
        .profile-style-sport .profile-header {
            border-top: 5px solid var(--primary);
            border-bottom: 5px solid var(--secondary);
        }
    }
    
    /* 10. Акварель - адаптивные модификации */
    .profile-style-nature {
        --primary: #4682b4;
        --secondary: #5f9ea0;
    }
    
    @media (max-width: 768px) {
        .profile-style-nature .profile-header {
            background-image: none;
        }
    }
    
    /* Адаптивные вкладки */
    .profile-tabs {
        display: flex;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .profile-tabs::-webkit-scrollbar {
        display: none;
    }
    
    .profile-tab {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    
    /* Адаптивные карточки друзей и групп */
    .friends-grid, .groups-list {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }
    
    @media (min-width: 480px) {
        .friends-grid, .groups-list {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
    }
    
    /* Оптимизация фонов для мобильных устройств */
    @media (max-width: 768px) {
        .profile-style-got,
        .profile-style-marvel,
        .profile-style-cyber {
            background-attachment: scroll;
        }
    }
    
    /* Оптимизация анимаций для устройств с prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }






    /* Добавить в CSS профиля */
.crypto-card {
    text-align: center;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border: 1px solid #e0e0e0;
}

.crypto-balance {
    font-size: 28px;
    font-weight: bold;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.crypto-balance .amount {
    color: #f7931a; /* Оранжевый как у биткоина */
}

.crypto-balance .currency {
    font-size: 16px;
    color: #666;
}

.crypto-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    position: relative;
}

.close {
    position: absolute;
    right: 15px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
/* Добавить в CSS профиля */
.crypto-card {
    text-align: center;
    background: #1a1a2e;
    border: 1px solid #2d2d4d;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    background: linear-gradient(145deg, #16213e 0%, #1a1a2e 100%);
    color: #e6e6e6;
    position: relative;
    overflow: hidden;
}

.crypto-card::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(247, 147, 26, 0.1) 0%, transparent 70%);
    animation: rotate 15s linear infinite;
    z-index: 0;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.crypto-card h3 {
    color: #f7931a;
    font-size: 1.2rem;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}

.crypto-balance {
    font-size: 2rem;
    font-weight: bold;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    position: relative;
    z-index: 1;
}

.crypto-balance .amount {
    color: #ffffff;
    text-shadow: 0 0 10px rgba(247, 147, 26, 0.5);
}

.crypto-balance .currency {
    font-size: 1rem;
    color: #f7931a;
    background: rgba(247, 147, 26, 0.1);
    padding: 3px 8px;
    border-radius: 4px;
}

.crypto-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    position: relative;
    z-index: 1;
}

.crypto-actions .btn {
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.crypto-actions .btn-primary {
    background: linear-gradient(90deg, #f7931a 0%, #f9b54a 100%);
    color: #1a1a2e;
}

.crypto-actions .btn-primary:hover {
    background: linear-gradient(90deg, #f9b54a 0%, #f7931a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(247, 147, 26, 0.3);
}

.crypto-actions .btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.crypto-actions .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* Модальное окно в крипто-стиле */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: #1a1a2e;
    margin: 10% auto;
    padding: 25px;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    position: relative;
    border: 1px solid #2d2d4d;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    color: #e6e6e6;
}

.modal-content h3 {
    color: #f7931a;
    margin-bottom: 20px;
    text-align: center;
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #aaa;
    transition: color 0.3s;
}

.close:hover {
    color: #f7931a;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #aaa;
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid #2d2d4d;
    border-radius: 8px;
    color: #ffffff;
    font-size: 16px;
    transition: all 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #f7931a;
    box-shadow: 0 0 0 2px rgba(247, 147, 26, 0.2);
}

#sendCurrencyForm button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(90deg, #f7931a 0%, #f9b54a 100%);
    color: #1a1a2e;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

#sendCurrencyForm button:hover {
    background: linear-gradient(90deg, #f9b54a 0%, #f7931a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(247, 147, 26, 0.3);
}
/* Crypto Popup Styles */
.crypto-popup {
    position: fixed;
    bottom: -100%;
    right: 20px;
    width: 320px;
    background: #1E1E1E;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    transition: bottom 0.3s ease;
    border: 1px solid #2D2D2D;
    overflow: hidden;
    color: #FFFFFF;
}

.crypto-popup.active {
    bottom: 20px;
}

.crypto-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #121212;
    border-bottom: 1px solid #2D2D2D;
}

.crypto-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
}

.crypto-logo i {
    color: #F7931A;
    font-size: 20px;
}

.crypto-close {
    background: none;
    border: none;
    color: #AAAAAA;
    cursor: pointer;
    font-size: 16px;
}

.crypto-balance-card {
    padding: 24px 16px;
    text-align: center;
    position: relative;
}

.network-badge {
    position: absolute;
    top: 12px;
    right: 16px;
    background: rgba(247, 147, 26, 0.1);
    color: #F7931A;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.balance-amount {
    font-size: 32px;
    font-weight: 700;
    margin: 8px 0;
}

.balance-currency {
    color: #AAAAAA;
    font-size: 16px;
}

.crypto-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 0 16px 16px;
}

.crypto-action-btn {
    background: #2D2D2D;
    border: none;
    border-radius: 12px;
    padding: 12px;
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: background 0.2s;
}

.crypto-action-btn:hover {
    background: #3D3D3D;
}

.crypto-action-btn i {
    font-size: 20px;
}

.crypto-account {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #121212;
    border-top: 1px solid #2D2D2D;
}

.account-address {
    font-family: 'Courier New', monospace;
    font-size: 14px;
}

.copy-btn {
    background: none;
    border: none;
    color: #AAAAAA;
    cursor: pointer;
}

/* Send Popup Styles */
.crypto-send-popup {
    position: fixed;
    bottom: -100%;
    right: 20px;
    width: 320px;
    background: #1E1E1E;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1001;
    transition: bottom 0.3s ease;
    border: 1px solid #2D2D2D;
    overflow: hidden;
    color: #FFFFFF;
}

.crypto-send-popup.active {
    bottom: 20px;
}

.send-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: #121212;
    border-bottom: 1px solid #2D2D2D;
    position: relative;
}

.send-header h3 {
    margin: 0;
    font-size: 18px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
}

.back-btn, .close-btn {
    background: none;
    border: none;
    color: #AAAAAA;
    cursor: pointer;
    font-size: 16px;
    z-index: 1;
}

#sendCryptoForm {
    padding: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #AAAAAA;
}

.form-group input {
    width: 100%;
    padding: 12px;
    background: #2D2D2D;
    border: 1px solid #3D3D3D;
    border-radius: 8px;
    color: #FFFFFF;
    font-size: 16px;
}

.amount-input {
    position: relative;
}

.amount-input .currency {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #AAAAAA;
}

.balance-hint {
    font-size: 12px;
    color: #AAAAAA;
    margin-top: 4px;
    text-align: right;
}

.submit-btn {
    width: 100%;
    padding: 12px;
    background: #F7931A;
    border: none;
    border-radius: 8px;
    color: #121212;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
}

.submit-btn:hover {
    background: #FFAA33;
}
/* Игра Престолов (2.jpg) */
.profile-style-got {
    --primary-color: #00308F; /* Темно-синий вместо красного */
    --secondary-color: #72A0C1; /* Светло-голубой */
    --text-color: #f8f8f8;
    --bg-color: #0a0a1a; /* Темно-синий фон */
    --card-bg: rgba(10, 20, 45, 0.9); /* Синий оттенок */
    background: 
        url('/assets/images/backgrounds/got-bg-blue.jpg') no-repeat center center fixed,
        linear-gradient(rgba(0, 0, 20, 0.8), rgba(0, 0, 20, 0.8));
    background-size: cover;
    font-family: 'Times New Roman', serif;
}

.profile-style-got .profile-header {
    border: 2px solid var(--primary-color);
    box-shadow: 0 0 15px rgba(0, 48, 143, 0.5);
    background: var(--card-bg);
}

.profile-style-got .profile-tab.active {
    background: var(--primary-color);
    color: silver; /* Серебро вместо золота */
    font-weight: bold;
    border-bottom-color: var(--secondary-color);
}

.profile-style-got .btn-primary {
    background: linear-gradient(to right, var(--primary-color), #0047AB);
    border: 1px solid var(--secondary-color);
    font-family: 'Russo One', sans-serif;
}
.profile-style-got .comment{
    box-shadow: 0 0 15px rgba(0, 48, 143, 0.5);
    background: var(--card-bg);
}
.crypto-wallet-btn {
    position: fixed;
    bottom: 80px;
    right: 20px;
    background: linear-gradient(145deg, #00308F, #0047AB);
    color: white;
    border: none;
    border-radius: 30px;
    padding: 12px 20px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0, 48, 143, 0.4);
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 999;
    transition: all 0.3s ease;
}

.crypto-wallet-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 48, 143, 0.6);
}

.crypto-wallet-btn i {
    font-size: 20px;
}









/* Бронзовое оформление (3rd level) */
.profile-style-bronze {
    --primary-color: #cd7f32; /* бронзовый */
    --secondary-color: #8b4513; /* коричневый */
    --text-color: #333;
    --bg-color: #f5f5dc; /* бежевый */
    --card-bg: rgba(245, 245, 220, 0.9);
    background: #f5f5dc;
    font-family: 'Arial', sans-serif;
    background: 
            url('/assets/images/backgrounds/bronze.jpg') center/cover no-repeat,
            linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
}

.profile-style-bronze .profile-header {
    border: 3px solid var(--primary-color);
    background: var(--card-bg);
}

.profile-style-bronze .profile-tab.active {
    background: var(--primary-color);
    color: white;
}

.profile-style-bronze .btn-primary {
    background: var(--primary-color);
    border: 1px solid var(--secondary-color);
    color: white;
}

/* Серебряное оформление (2nd level) */
.profile-style-silver {
    --primary-color: #c0c0c0; /* серебряный */
    --secondary-color: #a9a9a9; /* темно-серый */
    --text-color: #333;
    --bg-color: #f8f8f8;
    --card-bg: rgba(248, 248, 248, 0.95);
    background: #f8f8f8;
    font-family: 'Segoe UI', sans-serif;
    background: 
            url('/assets/images/backgrounds/silver.jpg') center/cover no-repeat,
            linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
}

.profile-style-silver .profile-header {
    border: 3px solid var(--primary-color);
    background: var(--card-bg);
    box-shadow: 0 0 15px rgba(192, 192, 192, 0.5);
}

.profile-style-silver .profile-tab.active {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.profile-style-silver .btn-primary {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: white;
}

/* Золотое оформление (1st level) */
.profile-style-gold {
    --primary-color: #ffd700; /* золотой */
    --secondary-color: #daa520; /* золотистый */
    --text-color: #333;
    --bg-color: #fffacd; /* лимонный */
    --card-bg: rgba(255, 250, 205, 0.9);
    background: #fffacd;
    font-family: 'Georgia', serif;
    background: 
            url('/assets/images/backgrounds/gold.jpg') center/cover no-repeat,
            linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7));
}

.profile-style-gold .profile-header {
    border: 3px solid var(--primary-color);
    background: var(--card-bg);
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
}

.profile-style-gold .profile-tab.active {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: #333;
}

.profile-style-gold .btn-primary {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: #333;
    font-weight: bold;
}

/* VIP оформление (premium) */
.profile-style-vip {
    --primary-color: #9400d3; /* фиолетовый */
    --secondary-color: #ff00ff; /* пурпурный */
    --text-color: #fff;
    --bg-color: #000;
    --card-bg: rgba(20, 0, 30, 0.9);
    background: 
        radial-gradient(circle at 20% 30%, var(--primary-color), transparent 50%),
        radial-gradient(circle at 80% 70%, var(--secondary-color), transparent 50%),
        var(--bg-color);
    font-family: 'Montserrat', sans-serif;
}
.profile-style-vip .comment{
    border: 2px solid var(--primary-color);
    background: var(--card-bg);
    box-shadow: 0 0 30px var(--primary-color);
}
.profile-style-vip .profile-header {
    border: 2px solid var(--primary-color);
    background: var(--card-bg);
    box-shadow: 0 0 30px var(--primary-color);
}

.profile-style-vip .profile-tab.active {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    color: white;
    text-shadow: 0 0 5px white;
}

.profile-style-vip .btn-primary {
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border: none;
    color: white;
    font-weight: bold;
    text-transform: uppercase;
}
.profile-actions{
    height: 90px;
    width: 170px;
}
.post-card {
    /* Существующие стили */
    border: 2px solid transparent; /* Добавляем прозрачную границу */
    transition: border-color 0.3s ease;
}

.post-card.happy {
    border-color: #FFD700; /* Желтый для счастья */
}

.post-card.sad {
    border-color: #87CEEB; /* Голубой для грусти */
}

.post-card.angry {
    border-color: #FF4500; /* Оранжево-красный для злости */
}

.post-card.loved {
    border-color: #FF69B4; /* Розовый для влюбленности */
}

.post-card.tired {
    border-color: #A9A9A9; /* Серый для усталости */
}

.post-card.blessed {
    border-color: #9370DB; /* Фиолетовый для благословения */
}

</style>

<div class="profile-container profile-style-<?= $profile_style ?>">
    <!-- Левая колонка (информация) -->
    <div class="profile-sidebar">
        <div class="sidebar-card">
            <h3><i class="fas fa-info-circle"></i> Информация</h3>
            <?php if (!empty($profile_user['bio'])): ?>
                <div class="info-item">
                    <i class="fas fa-quote-left"></i>
                    <span><?= htmlspecialchars($profile_user['bio']) ?></span>
                </div>
            <?php endif; ?>
            
            <div class="info-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Зарегистрирован: <?= date('d.m.Y', strtotime($profile_user['created_at'])) ?></span>
            </div>
        </div>
        
        <div class="sidebar-card">
            <h3><i class="fas fa-users"></i> Друзья <span><?= count($friends) ?></span></h3>
            <div class="friends-grid">
                <?php foreach (array_slice($friends, 0, 6) as $friend): ?>
                <div class="friend-item">
                    <a href="/profile.php?id=<?= $friend['id'] ?>">
                        <img src="/assets/images/avatars/<?= $friend['avatar'] ?>" 
                             alt="<?= htmlspecialchars($friend['full_name']) ?>"
                             onerror="this.src='/assets/images/avatars/default.png'">
                        <span><?= htmlspecialchars(explode(' ', $friend['full_name'])[0]) ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($friends) > 6): ?>
            <a href="/friends.php?id=<?= $profile_user['id'] ?>" class="see-all">Показать всех</a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($groups)): ?>
        <div class="sidebar-card">
            <h3><i class="fas fa-users"></i> Группы <span><?= count($groups) ?></span></h3>
            <div class="groups-list">
                <?php foreach (array_slice($groups, 0, 3) as $group): ?>
                <div class="group-item">
                    <a href="/group.php?id=<?= $group['id'] ?>">
                        <img src="/assets/images/groups/<?= $group['avatar'] ?>" 
                             alt="<?= htmlspecialchars($group['name']) ?>"
                             onerror="this.src='/assets/images/groups/default.png'">
                        <span><?= htmlspecialchars($group['name']) ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($groups) > 3): ?>
            <a href="/groups.php?id=<?= $profile_user['id'] ?>" class="see-all">Все группы</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Правая колонка (контент) -->
    <div class="profile-content">
        <!-- Шапка профиля -->
        <div class="profile-header">
            <div class="cover-container">
                <img src="/assets/images/covers/<?= $profile_user['cover'] ?? 'default.jpg' ?>" 
                     class="profile-cover" 
                     alt="Обложка профиля"
                     onerror="this.src='/assets/images/covers/default.jpg'">
                <?php if ($is_own_profile): ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="avatar-container">
                    <img src="/assets/images/avatars/<?= $profile_user['avatar'] ?>" 
                         class="profile-avatar" 
                         alt="Аватар"
                         onerror="this.src='/assets/images/avatars/default.png'">
                    <?php if ($is_own_profile): ?>

                    <?php endif; ?>
                </div>
                <div class="profile-text">
                    <h1><?= htmlspecialchars($profile_user['full_name']) ?> / <a href = '#'>@<?= htmlspecialchars($profile_user['username']) ?></a></h1>
                    <?php
                    function truncateText($text, $length = 80) {
                        if (empty($text)) return '';
                        if (mb_strlen($text, 'UTF-8') > $length) {
                            return htmlspecialchars(mb_substr($text, 0, $length, 'UTF-8')) . '...';
                        }
                        return htmlspecialchars($text);
                    }
                    ?>

                    <p class="profile-bio"><?= truncateText($profile_user['bio'] ?? '') ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span color="grey"><?= count($friends) ?> друзей</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-newspaper"></i>
                            <span><?= count($posts) ?> публикаций</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span><?= count($groups) ?> групп</span>
                        </div>
                    </div>
                </div>
                
                <?php if (!$is_own_profile && $user): ?>
                <div class="profile-actions">
                    <?php $friendship_status = getFriendshipStatus($db, $user['id'], $profile_user['id']); ?>
                    
                    <?php if ($friendship_status === 'friends'): ?>
                        <button class="btn btn-secondary" onclick="removeFriend(<?= $profile_user['id'] ?>)">
                            <i class="fas fa-user-minus"></i> Удалить из друзей
                        </button>
                    <?php elseif ($friendship_status === 'request_sent'): ?>
                        <button class="btn btn-disabled" disabled>
                            <i class="fas fa-clock"></i> Запрос отправлен
                        </button>
                    <?php elseif ($friendship_status === 'request_received'): ?>
                        <button class="btn btn-primary" onclick="acceptFriendRequest(<?= $profile_user['id'] ?>)">
                            <i class="fas fa-check"></i> Принять заявку
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary" onclick="sendFriendRequest(<?= $profile_user['id'] ?>)">
                            <i class="fas fa-user-plus"></i> Добавить в друзья
                        </button>
                    <?php endif; ?>
                    
                    <a href="/messages.php?user_id=<?= $profile_user['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-envelope"></i> Написать сообщение
                    </a>
                </div>
                <?php elseif ($is_own_profile): ?>
                <div class="profile-actions">
                    <a href="/edit_profile.php" class="btn btn-secondary">
                        <i class="fas fa-edit"></i> Редактировать профиль
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_own_profile): ?>
            <button onclick="toggleCryptoPopup()" class="crypto-wallet-btn">
                <i class="fab fa-ethereum"></i> ConnectCoin
            </button>
        <?php endif; ?>


        <!-- Вкладки -->
        <div class="profile-tabs">
            <a href="#" class="profile-tab active" data-tab="posts">
                <i class="fas fa-newspaper"></i> Публикации
            </a>
            <a href="#" class="profile-tab" data-tab="photos">
                <i class="fas fa-images"></i> Фотографии
            </a>
            <a href="#" class="profile-tab" data-tab="friends">
                <i class="fas fa-users"></i> Друзья
            </a>
            <a href="#" class="profile-tab" data-tab="groups">
                <i class="fas fa-users"></i> Группы
            </a>
        </div>
        
        <!-- Контент вкладок -->
        <div id="posts-tab" class="tab-content active">
            <?php if ($is_own_profile): ?>
            <!-- Форма создания поста -->
            <div class="create-post">
                <form id="create-post-form" method="POST" action="/actions/create_post.php" enctype="multipart/form-data">
                    <div class="post-input">
                        <img src="/assets/images/avatars/<?= $user['avatar'] ?>" 
                             alt="Ваш аватар"
                             onerror="this.src='/assets/images/avatars/default.png'">
                        <input type="text" name="content" placeholder="Что у вас нового, <?= explode(' ', $user['full_name'])[0] ?>?" required>
                    </div>
                    <div class="post-actions">
                        <label class="post-action">
                            <i class="fas fa-images"></i> Фото/Видео
                            <input type="file" name="image" accept="image/*" style="display: none;">
                        </label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Опубликовать
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Список постов -->
            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                <div class="post" id="post-<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="post-user">
                            <img src="/assets/images/avatars/<?= $post['avatar'] ?>" 
                                 alt="<?= htmlspecialchars($post['full_name']) ?>"
                                 onerror="this.src='/assets/images/avatars/default.png'">
                            <div class="user-details">
                                <a href="/profile.php?id=<?= $post['user_id'] ?>" class="name">
                                    <?= htmlspecialchars($post['full_name']) ?>
                                </a>
                                <div class="time">
                                    <?= time_elapsed_string($post['created_at']) ?> · 
                                    <i class="fas fa-globe-americas"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        <?php if (!empty($post['image'])): ?>
                        <img src="/assets/images/posts/<?= $post['image'] ?>" 
                             alt="Изображение поста" 
                             class="post-image"
                             onclick="openImageModal('/assets/images/posts/<?= $post['image'] ?>')">
                        <?php endif; ?>






                        <?php if ($post['feeling']):?>
                            <div class="post-feeling">
                                <?php 
                                    $feeling_icons = [
                                        'happy' => 'fa-smile-beam',
                                        'sad' => 'fa-sad-tear',
                                        'angry' => 'fa-angry',
                                        'loved' => 'fa-heart',
                                        'tired' => 'fa-tired',
                                        'blessed' => 'fa-pray'
                                    ];
                                    $feeling_texts = [
                                        'happy' => 'чувствует себя счастливым',
                                        'sad' => 'чувствует себя грустным',
                                        'angry' => 'чувствует себя злым',
                                        'loved' => 'чувствует себя влюблённым',
                                        'tired' => 'чувствует себя уставшим',
                                        'blessed' => 'чувствует себя благословлённым'
                                    ];
                                ?>
                                <i class="fas <?= $feeling_icons[$post['feeling']] ?>"></i>
                                <span><?= $feeling_texts[$post['feeling']] ?></span>
                            </div>
                        <?php endif; ?>



                        <?php if (isset($post['poll'])): ?>
                            <div class="poll-container" style="margin-top: 15px; border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                                <h4 style="margin-top: 0; margin-bottom: 15px;"><?= htmlspecialchars($post['poll']['question']) ?></h4>
                                
                                <?php if ($post['poll']['ends_at'] && strtotime($post['poll']['ends_at']) > time()): ?>
                                    <div class="poll-deadline" style="font-size: 0.8em; color: #666; margin-bottom: 10px;">
                                        Опрос активен до: <?= date('d.m.Y H:i', strtotime($post['poll']['ends_at'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="poll-options">
                                    <?php foreach ($post['poll']['options'] as $option): ?>
                                        <div class="poll-option" style="margin-bottom: 10px;">
                                            <?php if (hasUserVoted($db, $post['poll']['id'], $user['id']) || 
                                                    ($post['poll']['ends_at'] && strtotime($post['poll']['ends_at']) < time())): ?>
                                                <!-- Показываем результаты -->
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span><?= htmlspecialchars($option['option_text']) ?></span>
                                                    <span><?= $option['votes'] ?> (<?= round($option['votes'] / max(1, $post['poll']['total_votes']) * 100) ?>%)</span>
                                                </div>
                                                <div style="height: 10px; background: #f0f0f0; border-radius: 5px;">
                                                    <div style="height: 100%; width: <?= round($option['votes'] / max(1, $post['poll']['total_votes']) * 100) ?>%; 
                                                        background: var(--primary-color); border-radius: 5px;"></div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Показываем варианты для голосования -->
                                                <label style="display: flex; align-items: center;">
                                                    <input type="<?= $post['poll']['is_multiple'] ? 'checkbox' : 'radio' ?>" 
                                                        name="poll_option_<?= $post['poll']['id'] ?>" 
                                                        value="<?= $option['id'] ?>" 
                                                        style="margin-right: 10px;">
                                                    <?= htmlspecialchars($option['option_text']) ?>
                                                </label>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="poll-total" style="font-size: 0.8em; color: #666; margin-top: 10px;">
                                    Всего голосов: <?= $post['poll']['total_votes'] ?>
                                </div>
                                
                                <?php if (!hasUserVoted($db, $post['poll']['id'], $user['id']) && 
                                        (!$post['poll']['ends_at'] || strtotime($post['poll']['ends_at']) > time())): ?>
                                    <button class="vote-btn" data-poll-id="<?= $post['poll']['id'] ?>" 
                                            style="margin-top: 10px; padding: 5px 15px; background: var(--primary-color); 
                                                color: white; border: none; border-radius: 4px; cursor: pointer;">
                                        Голосовать
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>


                       

                    </div>
                    
                    <div class="post-stats">
                        <div class="likes">
                            <i class="fas fa-thumbs-up"></i> <?= $post['likes_count'] ?>
                        </div>
                        <div class="comments">
                            <?= $post['comments_count'] ?> комментариев
                        </div>
                    </div>
                    
                    <div class="post-actions">
                        <button class="post-action-btn like-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-thumbs-up"></i> Нравится
                        </button>
                        <button class="post-action-btn comment-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-comment"></i> Комментировать
                        </button>
                    </div>
                    <!-- Комментарии -->
                    <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <?php if ($user): ?>
                            <div class="add-comment" style="display: flex; margin-bottom: 15px;">
                                <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                                <form class="comment-form" data-post-id="<?= $post['id'] ?>" style="flex-grow: 1;">
                                    <input type="text" name="comment" placeholder="Написать комментарий..." style="width: 100%; padding: 8px 12px; border-radius: 20px; border: 1px solid #ddd; outline: none;">
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="comments-list" id="comments-list-<?= $post['id'] ?>">
                            <!-- Комментарии будут загружены по запросу -->
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-content">
                    <i class="fas fa-newspaper"></i>
                    <p>Здесь пока нет публикаций</p>
                    <?php if ($is_own_profile): ?>
                    <p>Напишите что-нибудь, чтобы ваши друзья увидели это!</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Вкладка фотографий -->
        <div id="photos-tab" class="tab-content">
            <div class="photos-container">
                <?php
                $photos = $db->query("
                    SELECT id, image FROM posts 
                    WHERE user_id = {$profile_user['id']} 
                    AND image IS NOT NULL
                    ORDER BY created_at DESC
                ");
                
                if ($photos->fetchArray()): 
                    $photos->reset();
                ?>
                    <div class="photos-grid">
                        <?php while ($photo = $photos->fetchArray()): ?>
                        <div class="photo-item">
                            <img src="/assets/images/posts/<?= $photo['image'] ?>" 
                                 alt="Фото пользователя"
                                 loading="lazy"
                                 onclick="openImageModal('/assets/images/posts/<?= $photo['image'] ?>')"
                                 onerror="this.src='/assets/images/default-post.jpg'">
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-content">
                        <i class="fas fa-images"></i>
                        <p>Нет фотографий для отображения</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Вкладка друзей -->
        <div id="friends-tab" class="tab-content">
            <div class="friends-container">
                <?php if (!empty($friends)): ?>
                    <div class="friends-grid">
                        <?php foreach ($friends as $friend): ?>
                        <div class="friend-card">
                            <a href="/profile.php?id=<?= $friend['id'] ?>" class="friend-link">
                                <div class="friend-avatar">
                                    <img src="/assets/images/avatars/<?= $friend['avatar'] ?>" 
                                         alt="<?= htmlspecialchars($friend['full_name']) ?>"
                                         loading="lazy"
                                         onerror="this.src='/assets/images/avatars/default.png'">
                                </div>
                                <div class="friend-info">
                                    <div class="friend-name"><?= htmlspecialchars($friend['full_name']) ?></div>
                                    <div class="friend-username">@<?= htmlspecialchars($friend['username']) ?></div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-content">
                        <i class="fas fa-user-friends"></i>
                        <h3>Нет друзей</h3>
                        <p>Здесь будут отображаться ваши друзья</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Вкладка групп -->
        <div id="groups-tab" class="tab-content">
            <div class="groups-container">
                <?php if (!empty($groups)): ?>
                    <div class="groups-grid">
                        <?php foreach ($groups as $group): ?>
                        <div class="group-card">
                            <div class="group-header">
                                <a href="/group.php?id=<?= $group['id'] ?>" class="group-avatar">
                                    <img src="/assets/images/groups/<?= $group['avatar'] ?>" 
                                         alt="<?= htmlspecialchars($group['name']) ?>"
                                         loading="lazy"
                                         onerror="this.src='/assets/images/groups/group-default.jpg'">
                                </a>
                                <div class="group-info">
                                    <a href="/group.php?id=<?= $group['id'] ?>" class="group-name"><?= htmlspecialchars($group['name']) ?></a>
                                    <div class="group-stats">
                                        <span><i class="fas fa-newspaper"></i> <?= $group['posts_count'] ?></span>
                                        <span><i class="fas fa-users"></i> <?= $group['members_count'] ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="group-description"><?= htmlspecialchars($group['description'] ?? 'Нет описания') ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-content">
                        <i class="fas fa-users"></i>
                        <p>Пользователь не состоит ни в одной группе</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Добавить в конец body -->
<div id="cryptoPopup" class="crypto-popup">
    <div class="crypto-header">
        <div class="crypto-logo">
            <i class="fab fa-ethereum"></i>
            <span>ConnectCoin</span>
        </div>
        <button class="crypto-close" onclick="closeCryptoPopup()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="crypto-balance-card">
        <div class="network-badge">Mainnet</div>
        <div class="balance-amount" id="cryptoBalance">0.00</div>
        <div class="balance-currency">CC</div>
    </div>
    
    <div class="crypto-actions">
        <button class="crypto-action-btn" onclick="showSendPopup()">
            <i class="fas fa-paper-plane"></i>
            <span>Отправить</span>
        </button>
        <a href="/shop.php" class="crypto-action-btn">
            <i class="fas fa-shopping-bag"></i>
            <span>Магазин</span>
        </a>
    </div>
    
    <div class="crypto-account">
        <div class="account-address" id="walletAddress">
            0x7f...3a4b
        </div>
        <button class="copy-btn" onclick="copyWalletAddress()">
            <i class="far fa-copy"></i>
        </button>
    </div>
</div>

<!-- Отдельное модальное окно для отправки -->
<div id="sendCryptoPopup" class="crypto-send-popup">
    <div class="send-header">
        <button class="back-btn" onclick="backToWallet()">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h3>Отправить ConnectCoin</h3>
        <button class="close-btn" onclick="closeSendPopup()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form id="sendCryptoForm" onsubmit="sendCurrency(event)">
        <input type="hidden" name="to_user_id" id="sendToUserId">
        
        <div class="form-group">
            <label for="recipient">Получатель</label>
            <input type="text" id="recipient" name="recipient" 
                   placeholder="Имя пользователя или адрес" required>
        </div>
        
        <div class="form-group">
            <label for="amount">Сумма</label>
            <div class="amount-input">
                <input type="number" id="amount" name="amount" 
                       step="0.01" min="0.01" placeholder="0.00" required>
                <span class="currency">CC</span>
            </div>
            <div class="balance-hint">
                Доступно: <span id="availableBalance">0.00</span> CC
            </div>
        </div>
        
        <div class="form-group">
            <label for="message">Сообщение (необязательно)</label>
            <input type="text" id="message" name="message" 
                   placeholder="Назначение платежа">
        </div>
        
        <button type="submit" class="submit-btn">
            Подтвердить перевод
        </button>
    </form>
</div>

<script>
// Переключение вкладок
document.querySelectorAll('.profile-tab').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Удаляем активный класс у всех вкладок
        document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
        // Добавляем активный класс текущей вкладке
        this.classList.add('active');
        
        // Скрываем все содержимое вкладок
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Показываем содержимое текущей вкладки
        const tabId = this.getAttribute('data-tab');
        document.getElementById(`${tabId}-tab`).classList.add('active');
    });
});

// Функция для открытия изображения в модальном окне
function openImageModal(src) {
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
    modal.style.display = 'flex';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.zIndex = '1000';
    modal.style.cursor = 'pointer';
    
    const img = document.createElement('img');
    img.src = src;
    img.style.maxWidth = '90%';
    img.style.maxHeight = '90%';
    img.style.objectFit = 'contain';
    
    modal.appendChild(img);
    document.body.appendChild(modal);
    
    modal.addEventListener('click', () => {
        document.body.removeChild(modal);
    });
}
function sendFriendRequest(id){
    fetch('/actions/send_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ friend_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
function acceptFriendRequest(id){
    fetch('/actions/accept_friend_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ friend_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
function removeFriend(id){
    fetch('/actions/remove_friend.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ friend_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}






// Добавить в script-секцию профиля
function showSendModal(userId = null, username = null) {
    const modal = document.getElementById('sendModal');
    const form = document.getElementById('sendCurrencyForm');
    
    if (userId) {
        document.getElementById('sendToUserId').value = userId;
        form.querySelector('h3').textContent = `Отправить ConnectCoin пользователю @${username}`;
    } else {
        // Если отправляем с собственного профиля, нужно будет выбрать получателя
        // Здесь можно добавить поле для ввода username получателя
        document.getElementById('sendToUserId').value = '';
        form.querySelector('h3').textContent = 'Отправить ConnectCoin';
    }
    
    modal.style.display = 'block';
}

function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
}

function sendCurrency(event) {
    event.preventDefault();
    
    const form = event.target;
    const toUserId = document.getElementById('sendToUserId').value;
    const amount = parseInt(document.getElementById('sendAmount').value);
    const message = document.getElementById('sendMessage').value;
    
    if (!toUserId || !amount || amount <= 0) {
        alert('Пожалуйста, укажите получателя и сумму');
        return;
    }
    
    fetch('/actions/transfer_currency.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            to_user_id: toUserId,
            amount: amount,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Перевод выполнен успешно!');
            closeSendModal();
            location.reload();
        } else {
            alert(data.message || 'Ошибка при переводе');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при отправке');
    });
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('sendModal');
    if (event.target == modal) {
        closeSendModal();
    }
}
// Добавить в script-секцию профиля
function copyWalletAddress(btn) {
    const address = btn.closest('.crypto-wallet-address').querySelector('.address').textContent;
    navigator.clipboard.writeText(address).then(() => {
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.innerHTML = originalIcon;
            btn.classList.remove('copied');
        }, 2000);
    });
}

let currentRecipientId = null;
let currentRecipientUsername = null;

function showSendModal(userId = null, username = null) {
    const modal = document.getElementById('sendModal');
    const modalTitle = document.getElementById('modalTitle');
    const recipientGroup = document.getElementById('recipientGroup');
    
    currentRecipientId = userId;
    currentRecipientUsername = username;
    
    if (userId) {
        // Если отправляем конкретному пользователю (из его профиля)
        modalTitle.textContent = `Отправить CC пользователю @${username}`;
        document.getElementById('sendToUserId').value = userId;
        recipientGroup.style.display = 'none';
    } else {
        // Если открываем форму из своего профиля
        modalTitle.textContent = 'Отправить ConnectCoin';
        document.getElementById('sendToUserId').value = '';
        recipientGroup.style.display = 'block';
    }
    
    // Сброс формы
    document.getElementById('sendAmount').value = '';
    document.getElementById('sendMessage').value = '';
    document.getElementById('recipientUsername').value = '';
    
    modal.style.display = 'block';
    document.getElementById('sendAmount').focus();
}

function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
    document.getElementById('sendCurrencyForm').reset();
}

async function sendCurrency(event) {
    event.preventDefault();
    
    try {
        // Получаем данные формы
        const form = event.target;
        const formData = new FormData(form);
        const amount = parseFloat(formData.get('amount'));
        const message = formData.get('message') || '';
        const recipientId = formData.get('to_user_id');
        const recipientUsername = formData.get('recipient')?.trim();

        // Валидация
        if (isNaN(amount)) throw new Error("Введите корректную сумму");
        if (amount <= 0) throw new Error("Сумма должна быть больше 0");

        let finalRecipientId = recipientId;

        // Если нет ID, но есть username
        if (!finalRecipientId && recipientUsername) {
            const cleanUsername = recipientUsername.replace(/^@/, '');
            if (!cleanUsername) throw new Error("Введите имя пользователя");

            // Ищем пользователя
            const response = await fetch(`/actions/find_user.php?username=${encodeURIComponent(cleanUsername)}`);
            const text = await response.text();
            
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                throw new Error("Ошибка обработки ответа сервера");
            }
            
            if (!response.ok || !data?.success) {
                throw new Error(data?.message || "Пользователь не найден");
            }
            
            finalRecipientId = data.user.id;
        }

        if (!finalRecipientId) throw new Error("Не указан получатель");

        // Показываем лоадер
        form.querySelector('button[type="submit"]').disabled = true;
        
        // Отправка запроса
        const response = await fetch('/actions/transfer_currency.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                to_user_id: finalRecipientId, 
                amount: amount.toFixed(2),
                message: message 
            })
        });

        const text = await response.text();
        let result;
        try {
            result = text ? JSON.parse(text) : {};
        } catch (e) {
            throw new Error("Ошибка обработки ответа сервера");
        }

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Ошибка перевода");
        }
        // Успех
        showCryptoAlert('success', `Успешно отправлено ${amount.toFixed(2)} CC`);
        closeSendPopup();
        updateBalance();
        
    } catch (error) {
        console.error("Transfer error:", error);
        showCryptoAlert('error', error.message || "Ошибка перевода");
    } finally {
        // Восстанавливаем кнопку
        if (event.target && event.target.querySelector('button[type="submit"]')) {
            event.target.querySelector('button[type="submit"]').disabled = false;
        }
    }
    location.reload(true);
}

function updateBalance() {
    fetch('actions/get_balance.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сети');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error('Ошибка при получении баланса');
            }
            //<div class="balance-amount" id="cryptoBalance">0.00</div>
            const balanceElement = document.querySelector('.balance-amount');
            if (balanceElement) {
                balanceElement.textContent = parseFloat(data.balance).toLocaleString('ru-RU', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        })
        .catch(error => {
            console.error('Balance update error:', error);
        });
}

function showCryptoAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `crypto-alert crypto-alert-${type}`;
    alert.innerHTML = `
        <div class="crypto-alert-icon">
            ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>'}
        </div>
        <div class="crypto-alert-message">${message}</div>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 3000);
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('sendModal');
    if (event.target == modal) {
        closeSendModal();
    }
}
// Управление крипто-попапом
function toggleCryptoPopup() {
    const popup = document.getElementById('cryptoPopup');
    popup.classList.toggle('active');
    
    if (popup.classList.contains('active')) {
        updateBalance();
    }
}

function closeCryptoPopup() {
    document.getElementById('cryptoPopup').classList.remove('active');
}

// Управление попапом отправки
function showSendPopup(userId = null, username = null) {
    const sendPopup = document.getElementById('sendCryptoPopup');
    const cryptoPopup = document.getElementById('cryptoPopup');
    
    if (userId) {
        document.getElementById('sendToUserId').value = userId;
        document.getElementById('recipient').value = username || '';
        document.getElementById('recipient').readOnly = true;
    } else {
        document.getElementById('sendToUserId').value = '';
        document.getElementById('recipient').value = '';
        document.getElementById('recipient').readOnly = false;
    }
    
    cryptoPopup.classList.remove('active');
    sendPopup.classList.add('active');
    
    // Обновляем доступный баланс
    fetch('/actions/get_balance.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('availableBalance').textContent = 
                    parseFloat(data.balance).toFixed(2);
            }
        });
}

function closeSendPopup() {
    document.getElementById('sendCryptoPopup').classList.remove('active');
    document.getElementById('sendCryptoForm').reset();
}

function backToWallet() {
    closeSendPopup();
    toggleCryptoPopup();
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    // Генерируем "адрес кошелька"
    const userId = <?= $user['id'] ?? 0 ?>;
    const username = '<?= $user['username'] ?? '' ?>';
    if (userId) {
        const hash = sha256(userId + username).substring(0, 24);
        const walletAddress = '0x' + hash.substring(0, 4) + '...' + hash.substring(20);
        document.getElementById('walletAddress').textContent = walletAddress;
    }
});

// Простая функция хеширования для демонстрации
function sha256(str) {
    // В реальном приложении используйте window.crypto.subtle.digest
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = (hash << 5) - hash + char;
        hash |= 0; // Convert to 32bit integer
    }
    return hash.toString(16);
}






// Обработчик голосования
document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('vote-btn')) {
        const pollId = e.target.getAttribute('data-poll-id');
        const selectedOptions = document.querySelectorAll(
            `input[name="poll_option_${pollId}"]:checked`
        );
        
        if (selectedOptions.length === 0) {
            alert('Пожалуйста, выберите вариант ответа');
            return;
        }
        
        const optionIds = Array.from(selectedOptions).map(opt => parseInt(opt.value));
        
        try {
            const response = await fetch('/actions/vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    poll_id: parseInt(pollId),
                    option_ids: optionIds
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Ошибка сервера');
            }
            
            if (data.success) {
                // Перезагружаем страницу для обновления результатов
                location.reload();
            } else {
                alert(data.message || 'Ошибка при голосовании');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка: ' + error.message);
        }
    }
});
document.getElementById('poll-has-deadline').addEventListener('change', function() {
    const deadlineInput = document.querySelector('input[name="poll_deadline"]');
    deadlineInput.style.display = this.checked ? 'inline-block' : 'none';
    
    if (this.checked) {
        const now = new Date();
        now.setHours(now.getHours() + 1);
        deadlineInput.min = now.toISOString().slice(0, 16);
        deadlineInput.value = now.toISOString().slice(0, 16);
    }
});
</script>












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
    body{
        margin-bottom: 50px !important;
    }
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