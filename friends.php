<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
if (!$current_user) {
    header('Location: /login.php');
    exit;
}

$user_id = $_GET['id'] ?? $current_user['id'];
$is_own_profile = ($user_id == $current_user['id']);

// Получаем друзей пользователя
$friends = getFriends($db, $user_id);
$online_friends = array_filter($friends, function($friend) {
    return isUserOnline($friend['id']);
});

require_once 'includes/header.php';
?>

<main class="friends-page">
    <div class="friends-header">
        <h1>
            <i class="fas fa-user-friends"></i>
            <?= $is_own_profile ? 'Мои друзья' : 'Друзья пользователя' ?>
        </h1>
        
        <div class="friends-tabs">
            <button class="tab-btn active" data-tab="all">Все друзья (<?= count($friends) ?>)</button>
            <button class="tab-btn" data-tab="online" disabled='true'>Онлайн (<?= count($online_friends) ?>)</button>
        </div>
    </div>

    <div class="friends-search">
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Поиск друзей..." id="friendsSearch">
        </div>
    </div>

    <div class="friends-container">
        <!-- Все друзья -->
        <div class="friends-grid active" id="allFriends">
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $friend): ?>
                    <div class="friend-card" data-user-id="<?= $friend['id'] ?>">
                        <a href="/profile.php?id=<?= $friend['id'] ?>" class="friend-link">
                            <div class="friend-avatar-container">
                                <img src="/assets/images/avatars/<?= htmlspecialchars($friend['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($friend['full_name']) ?>"
                                     class="friend-avatar"
                                     onerror="this.src='/assets/images/avatars/default.jpg'">
                                <?php if (isUserOnline($friend['id'])): ?>
                                    <div class="online-badge" title="Онлайн"></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="friend-info">
                                <h3 class="friend-name"><?= htmlspecialchars($friend['full_name']) ?></h3>
                            </div>
                        </a>
                        
                        <div class="friend-actions">
                            <button class="action-btn message-btn" title="Написать сообщение">
                                <i class="fas fa-envelope"></i>
                            </button>
                            
                            <?php if ($is_own_profile): ?>
                                <button class="action-btn remove-btn" title="Удалить из друзей">
                                    <i class="fas fa-user-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3><?= $is_own_profile ? 'У вас пока нет друзей' : 'У пользователя нет друзей' ?></h3>
                    <p><?= $is_own_profile ? 'Найдите друзей и добавьте их в свой список' : '' ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Друзья онлайн -->
        <div class="friends-grid" id="onlineFriends">
            <?php if (!empty($online_friends)): ?>
                <?php foreach ($online_friends as $friend): ?>
                    <div class="friend-card" data-user-id="<?= $friend['id'] ?>">
                        <!-- [Аналогичная карточка друга, как в разделе "Все друзья"] -->
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-wifi"></i>
                    <h3>Нет друзей онлайн</h3>
                    <p>Когда ваши друзья будут в сети, они появятся здесь</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Основные стили */
.friends-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    color: #2d3748;
}

/* Шапка страницы */
.friends-header {
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.friends-header h1 {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    margin: 0 0 20px 0;
    color: #1a202c;
}

.friends-header h1 i {
    color: var(--primary-color);
}

.friends-tabs {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.tab-btn {
    padding: 10px 20px;
    background: #f7fafc;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.3s ease;
}

.tab-btn.active {
    background: var(--primary-color);
    color: white;
}

.tab-btn:hover:not(.active) {
    background: #e2e8f0;
}

/* Поиск */
.friends-search {
    margin-bottom: 25px;
}

.search-container {
    position: relative;
    max-width: 500px;
}

.search-container i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

#friendsSearch {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s;
}

#friendsSearch:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
}

/* Сетка друзей */
.friends-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    display: none;
}

.friends-grid.active {
    display: grid;
}

.friend-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.friend-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.friend-link {
    text-decoration: none;
    color: inherit;
    flex: 1;
    padding: 20px;
    display: block;
}

.friend-avatar-container {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 15px;
}

.friend-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #f7fafc;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
}

.friend-card:hover .friend-avatar {
    transform: scale(1.05);
}

.online-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #48bb78;
    border: 2px solid white;
}

.friend-info {
    text-align: center;
}

.friend-name {
    margin: 0 0 5px 0;
    font-size: 18px;
    color: #2d3748;
    font-weight: 600;
}

.friend-status {
    margin: 0;
    font-size: 14px;
    color: #718096;
}

.friend-actions {
    display: flex;
    border-top: 1px solid #edf2f7;
    padding: 12px;
}

.action-btn {
    flex: 1;
    background: none;
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    color: #718096;
    font-size: 16px;
}

.action-btn:hover {
    background: #f7fafc;
    color: var(--primary-color);
}

.message-btn:hover {
    color: #4299e1;
}

.remove-btn:hover {
    color: #f56565;
}

/* Состояние "нет друзей" */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 60px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #2d3748;
    font-size: 20px;
}

.empty-state p {
    margin: 0;
    color: #718096;
    font-size: 16px;
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.friend-card {
    animation: fadeIn 0.5s ease forwards;
    opacity: 0;
}

.friend-card:nth-child(1) { animation-delay: 0.1s; }
.friend-card:nth-child(2) { animation-delay: 0.2s; }
.friend-card:nth-child(3) { animation-delay: 0.3s; }
.friend-card:nth-child(4) { animation-delay: 0.4s; }
.friend-card:nth-child(5) { animation-delay: 0.5s; }
.friend-card:nth-child(6) { animation-delay: 0.6s; }

/* Адаптивность */
@media (max-width: 768px) {
    .friends-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .friend-avatar-container {
        width: 80px;
        height: 80px;
    }
}

@media (max-width: 576px) {
    .friends-grid {
        grid-template-columns: 1fr;
    }
    
    .friends-header h1 {
        font-size: 24px;
    }
    
    .friends-tabs {
        flex-direction: column;
    }
}
</style>

<script>
// Переключение между вкладками
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Удаляем активный класс у всех кнопок
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        // Добавляем активный класс текущей кнопке
        this.classList.add('active');
        
        // Скрываем все сетки
        document.querySelectorAll('.friends-grid').forEach(grid => {
            grid.classList.remove('active');
        });
        
        // Показываем нужную сетку
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId + 'Friends').classList.add('active');
    });
});

// Поиск друзей
document.getElementById('friendsSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const activeGrid = document.querySelector('.friends-grid.active');
    
    activeGrid.querySelectorAll('.friend-card').forEach(card => {
        const name = card.querySelector('.friend-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
});

// Обработка кнопок действий
document.querySelectorAll('.message-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const userId = this.closest('.friend-card').getAttribute('data-user-id');
        openChat(userId);
    });
});

document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const userId = this.closest('.friend-card').getAttribute('data-user-id');
        if (confirm('Вы уверены, что хотите удалить этого пользователя из друзей?')) {
            removeFriend(userId);
        }
    });
});

function openChat(userId) {
    window.location.href = `/messages.php?user_id=${userId}`;
}

function removeFriend(userId) {
    fetch('/actions/remove_friend.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ friend_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
<style>
/* Темная тема в стиле Telegram */
@media (prefers-color-scheme: dark) {
    .friends-page {
        background-color: #0f0f0f;
        color: #e1e1e1;
    }

    .friends-header {
        border-bottom-color: #2d2d2d;
    }

    .friends-header h1 {
        color: #ffffff;
    }

    .tab-btn {
        background: #1e1e1e;
        color: #a0a0a0;
    }

    .tab-btn.active {
        background: #2b5278;
        color: white;
    }

    .tab-btn:hover:not(.active) {
        background: #2d2d2d;
    }

    #friendsSearch {
        background: #1e1e1e;
        border-color: #2d2d2d;
        color: #e1e1e1;
    }

    #friendsSearch:focus {
        border-color: #2b5278;
        box-shadow: 0 0 0 3px rgba(43, 82, 120, 0.3);
    }

    .search-container i {
        color: #6d6d6d;
    }

    .friend-card {
        background: #1e1e1e;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
    }

    .friend-card:hover {
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.25);
    }

    .friend-avatar {
        border-color: #2d2d2d;
    }

    .friend-name {
        color: #ffffff;
    }

    .friend-actions {
        border-top-color: #2d2d2d;
    }

    .action-btn {
        color: #6d6d6d;
    }

    .action-btn:hover {
        background: #2d2d2d;
    }

    .empty-state {
        background: #1e1e1e;
    }

    .empty-state h3 {
        color: #e1e1e1;
    }

    .empty-state p {
        color: #a0a0a0;
    }

    .empty-state i {
        color: #3d3d3d;
    }
}

/* Принудительное применение темной темы */
.dark-theme .friends-page {
    background-color: #0f0f0f;
    color: #e1e1e1;
}

.dark-theme .friends-header {
    border-bottom-color: #2d2d2d;
}

.dark-theme .friends-header h1 {
    color: #ffffff;
}

.dark-theme .tab-btn {
    background: #1e1e1e;
    color: #a0a0a0;
}

.dark-theme .tab-btn.active {
    background: #2b5278;
    color: white;
}

.dark-theme .tab-btn:hover:not(.active) {
    background: #2d2d2d;
}

.dark-theme #friendsSearch {
    background: #1e1e1e;
    border-color: #2d2d2d;
    color: #e1e1e1;
}

.dark-theme #friendsSearch:focus {
    border-color: #2b5278;
    box-shadow: 0 0 0 3px rgba(43, 82, 120, 0.3);
}

.dark-theme .search-container i {
    color: #6d6d6d;
}

.dark-theme .friend-card {
    background: #1e1e1e;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
}

.dark-theme .friend-card:hover {
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.25);
}

.dark-theme .friend-avatar {
    border-color: #2d2d2d;
}

.dark-theme .friend-name {
    color: #ffffff;
}

.dark-theme .friend-actions {
    border-top-color: #2d2d2d;
}

.dark-theme .action-btn {
    color: #6d6d6d;
}

.dark-theme .action-btn:hover {
    background: #2d2d2d;
}

.dark-theme .empty-state {
    background: #1e1e1e;
}

.dark-theme .empty-state h3 {
    color: #e1e1e1;
}

.dark-theme .empty-state p {
    color: #a0a0a0;
}

.dark-theme .empty-state i {
    color: #3d3d3d;
}

/* Плавные переходы для темной темы */
.friends-page,
.friend-card,
.tab-btn,
#friendsSearch {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
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
    .friends-container{
        background: #0088cc;
    }
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
    main{
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