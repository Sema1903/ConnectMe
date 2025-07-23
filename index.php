<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
$page = $_GET['page'] ?? 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$posts = getPosts($db, $limit, $offset);
$friends = $user ? getFriends($db, $user['id']) : [];
$groups = $user ? getGroups($db, $user['id']) : getGroups($db);
$online_users = getOnlineUsers($db);

require_once 'includes/header.php';
?>

<div class="page-container">
    <!-- Left Sidebar -->
    <aside class="left-sidebar">
        <?php if ($user): ?>
            <div class="sidebar-card">
                <div class="user-info">
                    <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" class="user-avatar">
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-bio"><?= htmlspecialchars($user['bio']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-bars"></i> Меню</h3>
                <ul class="sidebar-menu">
                    <li>
                        <a href="/profile.php?id=<?= $user['id'] ?>">
                            <i class="fas fa-user"></i>
                            <span>Моя страница</span>
                        </a>
                    </li>
                    <li>
                        <a href="/friends.php">
                            <i class="fas fa-users"></i>
                            <span>Друзья</span>
                        </a>
                    </li>
                    <li>
                        <a href="/messages.php">
                            <i class="fas fa-comments"></i>
                            <span>Сообщения</span>
                        </a>
                    </li>
                    <li>
                    <a href="/notifications.php" class="notification-link">
                        <i class="fas fa-bell"></i>
                        <span>Уведомления</span>
                        <?php if (hasUnreadNotifications($db, $user['id'])): ?>
                            <span class="notification-badge">!</span>
                        <?php endif; ?>
                    </a>
                    </li>
                    <li>
                        <a href="/photos.php">
                            <i class="fas fa-images"></i>
                            <span>Фотографии</span>
                        </a>
                    </li>
                    <li>
                        <a href="/music.php">
                            <i class="fas fa-music"></i>
                            <span>Музыка</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="sidebar-card">
            <h3 class="sidebar-title"><i class="fas fa-users"></i> Группы</h3>
            <div class="shortcut-list">
                <?php foreach ($groups as $group): ?>
                    <a href="/group.php?id=<?= $group['id'] ?>" class="shortcut-item">
                        <img src="assets/images/groups/<?= $group['avatar'] ?>" alt="<?= htmlspecialchars($group['name']) ?>" class="group-avatar">
                        <span class="group-name"><?= htmlspecialchars($group['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Main Feed -->
    <main class="main-content">
        <?php if ($user): ?>
            <!-- Create Post -->
            <div class="create-post">
                <form id="create-post-form" method="POST" action="/actions/create_post.php" enctype="multipart/form-data">
                    <div class="post-input">
                        <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" class="post-author-avatar">
                        <input type="text" name="content" placeholder="Что у вас нового, <?= explode(' ', $user['full_name'])[0] ?>?" class="post-input-field" required>
                    </div>
                    <!-- В блоке create-post замените кнопку feeling-action на это: -->
                    <div class="post-actions">
                        <label class="post-action photo-action">
                            <i class="fas fa-images"></i>
                            <span class="action-text">Фото/Видео</span>
                            <input type="file" name="image" accept="image/*" class="file-input">
                        </label>
                        <div class="feeling-container">
                            <button type="button" class="post-action feeling-action" id="feeling-btn">
                                <i class="fas fa-smile"></i>
                                <span class="action-text">Чувства</span>
                            </button>
                            <div class="feeling-dropdown" id="feeling-dropdown">
                                <div class="feeling-options">
                                    <button type="button" class="feeling-option" data-feeling="happy">
                                        <i class="fas fa-smile-beam"></i> Счастлив
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="sad">
                                        <i class="fas fa-sad-tear"></i> Грустный
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="angry">
                                        <i class="fas fa-angry"></i> Злой
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="loved">
                                        <i class="fas fa-heart"></i> Влюблён
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="tired">
                                        <i class="fas fa-tired"></i> Уставший
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="blessed">
                                        <i class="fas fa-pray"></i> Благословлён
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="feeling" id="feeling-input">
                        </div>
                        <button type="submit" class="post-submit">
                            <i class="fas fa-paper-plane"></i>
                            <span class="submit-text">Опубликовать</span>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Post Feed -->
        <div class="posts-feed">
            <?php foreach ($posts as $post): ?>
                <div class="post-card" id="post-<?= $post['id'] ?>">
                    <div class="post-header">
                        <a href="/profile.php?id=<?= $post['user_id'] ?>" class="post-author">
                            <img src="assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" alt="User" class="author-avatar">
                            <div class="author-details">
                                <span class="author-name"><?= htmlspecialchars($post['full_name']) ?></span>
                                <span class="post-time"><?= time_elapsed_string($post['created_at']) ?></span>
                            </div>
                        </a>
                        <button class="post-options"><i class="fas fa-ellipsis-h"></i></button>
                    </div>
                    
                    <div class="post-content">
                        <p class="post-text"><?= processMentions(nl2br(htmlspecialchars($post['content'])), $db) ?></p>
                        <?php if ($post['feeling']): ?>
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
                        <?php if ($post['image']): ?>
                            <img src="/assets/images/posts/<?= $post['image'] ?>" alt="Post Image" class="post-image">
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
                        <div class="post-action-btn like-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-thumbs-up"></i>
                            <span>Нравится</span>
                        </div>
                        <div class="post-action-btn comment-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-comment"></i>
                            <span>Комментировать</span>
                        </div>
                        <div class="post-action-btn">
                            <i class="fas fa-share"></i>
                            <span>Поделиться</span>
                        </div>
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
        </div>
    </main>

    <!-- Right Sidebar -->
    <aside class="right-sidebar">
        <div class="sidebar-card friends-card">
            <div class="friends-header">
                <h3 class="sidebar-title"><i class="fas fa-user-friends"></i> Друзья онлайн</h3>
                <div class="friend-actions">
                    <a href="/friends.php" title="Все друзья"><i class="fas fa-ellipsis-h"></i></a>
                </div>
            </div>
            
            <ul class="friends-list">
                <?php foreach (array_slice($friends, 0, 8) as $friend): ?>
                    <li class="friend-item">
                        <a href="/profile.php?id=<?= $friend['id'] ?>" class="friend-link">
                            <div class="friend-avatar-container">
                                <img src="/assets/images/avatars/<?= htmlspecialchars($friend['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($friend['full_name']) ?>"
                                     class="friend-avatar">
                                <?php if (isUserOnline($friend['id'])): ?>
                                    <div class="online-badge"></div>
                                <?php endif; ?>
                            </div>
                            <div class="friend-info">
                                <span class="friend-name"><?= htmlspecialchars(explode(' ', $friend['full_name'])[0]) ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (count($friends) > 8): ?>
                <a href="/friends.php" class="view-all-link">
                    Показать всех друзей (<?= count($friends) ?>)
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Группы -->
        <div class="sidebar-card groups-card">
            <h3 class="sidebar-title"><i class="fas fa-users"></i> Ваши группы</h3>
            <ul class="groups-list">
                <?php foreach (array_slice($groups, 0, 3) as $group): ?>
                    <li class="group-item">
                        <a href="/group.php?id=<?= $group['id'] ?>" class="group-link">
                            <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($group['name']) ?>"
                                 class="group-avatar">
                            <span class="group-name"><?= htmlspecialchars($group['name']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="/groups.php" class="view-all-link">
                Все группы
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </aside>
</div>

<style>
/* Основные стили */
.page-container {
    display: flex;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
    gap: 20px;
}

/* Левая колонка */
.left-sidebar {
    width: 25%;
    min-width: 250px;
}

.sidebar-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.user-bio {
    font-size: 0.9rem;
    color: #65676b;
}

.sidebar-title {
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    margin-bottom: 5px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 8px;
    color: #050505;
    text-decoration: none;
    transition: background 0.2s;
}

.sidebar-menu a:hover {
    background: #f0f2f5;
}

.sidebar-menu i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.shortcut-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 8px;
    text-decoration: none;
    color: #050505;
    transition: background 0.2s;
}

.shortcut-item:hover {
    background: #f0f2f5;
}

.group-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    margin-right: 10px;
}

.group-name {
    font-size: 0.95rem;
}

/* Основная лента */
.main-content {
    flex: 1;
    min-width: 0;
}

.create-post {
    background: white;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.post-input {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.post-author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
}

.post-input-field {
    flex: 1;
    padding: 10px 15px;
    border: none;
    border-radius: 20px;
    background: #f0f2f5;
    font-size: 0.95rem;
}

.post-input-field:focus {
    outline: none;
    background: #e4e6e9;
}

.post-actions {
    display: flex;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px solid #eee;
}

.post-action {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #65676b;
    transition: all 0.2s;
}

.post-action:hover {
    background: #f0f2f5;
}

.post-action i {
    margin-right: 8px;
}

.post-submit {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background 0.2s;
}

.post-submit:hover {
    background: #357ae8;
}

.file-input {
    display: none;
}

/* Посты */
.posts-feed {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post-card {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.post-author {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    margin-bottom: 2px;
}

.post-time {
    font-size: 0.8rem;
    color: #65676b;
}

.post-options {
    background: none;
    border: none;
    color: #65676b;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
}

.post-options:hover {
    background: #f0f2f5;
}

.post-content {
    margin-bottom: 12px;
}

.post-text {
    margin-bottom: 10px;
    line-height: 1.4;
}

.post-image {
    width: 100%;
    border-radius: 8px;
    margin-top: 10px;
    max-height: 500px;
    object-fit: contain;
}

.post-stats {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    color: #65676b;
    font-size: 0.9rem;
}

.post-actions {
    display: flex;
    justify-content: space-around;
    padding: 8px 0;
}

.post-action-btn {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #65676b;
    transition: all 0.2s;
}

.post-action-btn:hover {
    background: #f0f2f5;
}

.post-action-btn i {
    margin-right: 8px;
}

/* Комментарии */
.comments-section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.add-comment {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.comment-author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
}

.comment-form {
    flex: 1;
}

.comment-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 20px;
    border: none;
    background: #f0f2f5;
}

.comment-input:focus {
    outline: none;
    background: #e4e6e9;
}

/* Правая колонка */
.right-sidebar {
    width: 25%;
    min-width: 250px;
}

.friends-header, .groups-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.friend-actions a, .group-actions a {
    color: #65676b;
    padding: 6px;
    border-radius: 50%;
    transition: all 0.2s;
}

.friend-actions a:hover, .group-actions a:hover {
    background: #f0f2f5;
}

.friends-list, .groups-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.friend-item, .group-item {
    padding: 8px 0;
}

.friend-link, .group-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding: 4px;
    padding-right: 65px;
    border-radius: 6px;
    transition: background 0.2s;
}

.friend-link:hover, .group-link:hover {
    background: #f0f2f5;
}

.friend-avatar-container {
    position: relative;
    width: 36px;
    height: 36px;
    margin-right: 12px;
}

.friend-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.online-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #31a24c;
    border: 2px solid white;
}

.friend-info {
    flex: 1;
}

.friend-name {
    font-weight: 500;
    font-size: 0.95rem;
}

.group-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    margin-right: 12px;
}

.group-name {
    font-size: 0.95rem;
}

.view-all-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 4px;
    margin-top: 8px;
    color: var(--primary-color);
    text-decoration: none;
    border-top: 1px solid #eee;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: #357ae8;
}

/* Адаптивность */
@media (max-width: 992px) {
    .page-container {
        flex-direction: column;
    }
    
    .left-sidebar, .right-sidebar {
        width: 100%;
        order: 3;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .sidebar-card {
        flex: 1;
        min-width: 250px;
    }
    
    .main-content {
        order: 1;
        width: 100%;
    }
}
.feeling-container {
    position: relative;
}

.feeling-dropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 10px;
    z-index: 100;
    display: none;
    width: 200px;
}

.feeling-dropdown.show {
    display: block;
}

.feeling-options {
    display: flex;
    flex-direction: column;
}

.feeling-option {
    padding: 8px 12px;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: 4px;
}

.feeling-option:hover {
    background: #f0f2f5;
}

.feeling-option i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
}

.selected-feeling {
    display: inline-flex;
    align-items: center;
    background: #f0f2f5;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
    font-size: 0.9rem;
}

.selected-feeling i {
    margin-right: 4px;
}
.post-feeling {
    display: inline-flex;
    align-items: center;
    background: #f0f2f5;
    padding: 4px 12px;
    border-radius: 20px;
    margin-top: 8px;
    font-size: 0.9rem;
    color: #65676b;
}

.post-feeling i {
    margin-right: 6px;
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .post-action span, .post-action-btn span, .post-submit span {
        display: none;
    }
    
    .post-action i, .post-action-btn i {
        margin-right: 0;
    }
    
    .sidebar-menu a {
        padding: 8px;
    }
    
    .sidebar-menu span {
        display: none;
    }
    
    .sidebar-menu i {
        margin-right: 0;
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .create-post {
        padding: 12px;
    }
    
    .post-input-field {
        padding: 8px 12px;
    }
    
    .post-action, .post-action-btn {
        padding: 8px;
    }
}
</style>
<!-- Вставьте этот код перед закрывающим тегом </body> или перед подключением footer.php -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Обрабатываем все блоки с постами
    document.querySelectorAll('.post-text').forEach(function(postText) {
        // Заменяем @username на кликабельные ссылки (только для отображения)
        postText.innerHTML = postText.innerHTML.replace(
            /@([a-zA-Z0-9_]+)/g, 
            '<a href="/profile.php?username=$1" class="mention">@$1</a>'
        );
    });
});
// Обработка выбора чувств
const feelingBtn = document.getElementById('feeling-btn');
const feelingDropdown = document.getElementById('feeling-dropdown');
const feelingInput = document.getElementById('feeling-input');

feelingBtn.addEventListener('click', function() {
    feelingDropdown.classList.toggle('show');
});

// Закрываем dropdown при клике вне его
document.addEventListener('click', function(e) {
    if (!feelingBtn.contains(e.target) && !feelingDropdown.contains(e.target)) {
        feelingDropdown.classList.remove('show');
    }
});

// Обработка выбора чувства
document.querySelectorAll('.feeling-option').forEach(option => {
    option.addEventListener('click', function() {
        const feeling = this.getAttribute('data-feeling');
        const iconClass = this.querySelector('i').className;
        const text = this.textContent.trim();
        
        feelingInput.value = feeling;
        
        // Обновляем кнопку
        feelingBtn.innerHTML = `
            <i class="${iconClass}"></i>
            <span class="action-text">${text}</span>
        `;
        
        feelingDropdown.classList.remove('show');
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>