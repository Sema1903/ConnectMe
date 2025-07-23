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
</style>

<div class="profile-container">
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
                    <button class="edit-avatar-btn">
                        <i class="fas fa-camera"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="profile-text">
                    <h1><?= htmlspecialchars($profile_user['full_name']) ?> / <a href = '#'>@<?= htmlspecialchars($profile_user['username']) ?></a></h1>
                    <p class="profile-bio"><?= htmlspecialchars($profile_user['bio'] ?? '') ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span><?= count($friends) ?> друзей</span>
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
</script>

<?php require_once 'includes/footer.php'; ?>