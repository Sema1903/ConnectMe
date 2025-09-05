<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
if (!$user) {
    header('Location: login.php');
    exit;
}

// Помечаем уведомления как прочитанные
$db->exec("UPDATE notifications SET is_read = 1 WHERE user_id = {$user['id']} AND is_read = 0");

// Получаем уведомления
$notifications = $db->query("
    SELECT 
        n.*,
        u.full_name as from_user_name,
        u.avatar as from_user_avatar,
        p.id as post_id,
        p.content as post_content
    FROM notifications n
    JOIN users u ON n.from_user_id = u.id
    LEFT JOIN posts p ON n.post_id = p.id
    WHERE n.user_id = {$user['id']}
    ORDER BY n.created_at DESC
");

// Получаем запросы в друзья
$friend_requests = $db->query("
    SELECT f.id as request_id, u.* 
    FROM friends f
    JOIN users u ON f.user1_id = u.id
    WHERE f.user2_id = {$user['id']} AND f.status = 0
");

require_once 'includes/header.php';
?>

<style>
:root {
    --primary-color: #1877f2;
    --secondary-color: #f0f2f5;
    --text-color: #050505;
    --text-secondary: #65676b;
    --card-bg: #ffffff;
    --unread-bg: #f0f8ff;
}

.notifications-container {
    display: flex;
    max-width: 1200px;
    margin: 20px auto;
    gap: 20px;
    padding: 0 15px;
}

.notifications-main {
    flex: 1;
    background: var(--card-bg);
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 20px;
}

.notifications-header {
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
    margin-bottom: 20px;
}

.notifications-header h1 {
    margin: 0;
    font-size: 24px;
    color: var(--text-color);
    display: flex;
    align-items: center;
    gap: 10px;
}

.notifications-tabs {
    display: flex;
    margin-top: 15px;
    border-bottom: 1px solid #eee;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    color: var(--text-secondary);
    position: relative;
    transition: all 0.2s;
}

.tab-btn.active {
    color: var(--primary-color);
    font-weight: 600;
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.notification-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: background 0.2s;
    position: relative;
}

.notification-item.unread {
    background: var(--unread-bg);
}

.notification-item:hover {
    background: #f5f5f5;
}

.notification-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.notification-author {
    font-weight: 600;
    color: var(--text-color);
}

.notification-time {
    font-size: 13px;
    color: var(--text-secondary);
}

.notification-text {
    color: var(--text-color);
    line-height: 1.5;
}

.mentioned-post {
    margin-top: 10px;
    padding: 12px;
    background: var(--secondary-color);
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

.post-content {
    margin: 5px 0;
    color: var(--text-color);
    font-size: 14px;
    line-height: 1.4;
}

.view-post-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--primary-color);
    text-decoration: none;
    font-size: 14px;
    margin-top: 8px;
}

.view-post-btn:hover {
    text-decoration: underline;
}

.request-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.btn-accept, .btn-decline {
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-accept {
    background: var(--primary-color);
    color: white;
}

.btn-accept:hover {
    background: #166fe5;
}

.btn-decline {
    background: #e4e6eb;
    color: var(--text-color);
}

.btn-decline:hover {
    background: #d8dadf;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 50px;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state p {
    margin: 5px 0;
    font-size: 16px;
}

.unread-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 10px;
    height: 10px;
    background-color: var(--primary-color);
    border-radius: 50%;
}

@media (max-width: 768px) {
    .notifications-container {
        flex-direction: column;
    }
    
    .notification-item {
        padding: 12px;
    }
}
</style>

<div class="notifications-container">
    <div class="notifications-main">
        <div class="notifications-header">
            <h1><i class="fas fa-bell"></i> Уведомления</h1>
            <div class="notifications-tabs">
                <button class="tab-btn active" data-tab="all">Все</button>
                <button class="tab-btn" data-tab="requests">Запросы</button>
            </div>
        </div>

        <!-- Все уведомления -->
        <div class="tab-content active" id="all-tab">
            <?php if ($notifications->fetchArray()): ?>
                <?php $notifications->reset(); ?>
                <div class="notifications-list">
                    <?php while ($notif = $notifications->fetchArray()): ?>
                        <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                            <img src="/assets/images/avatars/<?= htmlspecialchars($notif['from_user_avatar'] ?? 'default.png') ?>" 
                                 alt="<?= htmlspecialchars($notif['from_user_name']) ?>" 
                                 class="notification-avatar">
                            
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-author"><?= htmlspecialchars($notif['from_user_name']) ?></span>
                                    <span class="notification-time"><?= time_elapsed_string($notif['created_at']) ?></span>
                                </div>
                                
                                <div class="notification-text">
                                    <?php switch($notif['type']):
                                        case 'friend_request': ?>
                                            Отправил вам запрос в друзья
                                            <?php break; ?>
                                        <?php case 'currency_received':?>
                                            Перевел(a) вам СС
                                            <?php break; ?>
                                        <?php case 'friend_request_accepted': ?>
                                            Принял ваш запрос в друзья
                                            <?php break; ?>
                                        <?php case 'challenge':?>
                                            Бросил Вам вызов
                                            <?php break;?>
                                        <?php case 'mention': ?>
                                            Упоминает вас в посте:
                                            <?php if (!empty($notif['post_content'])): ?>
                                                <div class="mentioned-post">
                                                    <p class="post-content"><?= htmlspecialchars($notif['post_content']) ?></p>
                                                    <a href="/post.php?id=<?= $notif['post_id'] ?>" class="view-post-btn">
                                                        Посмотреть пост <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php break; ?>
                                        <?php default: ?>
                                            Новое уведомление
                                    <?php endswitch; ?>
                                </div>
                            </div>
                            
                            <?php if (!$notif['is_read']): ?>
                                <div class="unread-badge"></div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>У вас пока нет уведомлений</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Запросы в друзья -->
        <div class="tab-content" id="requests-tab">
            <?php if ($friend_requests->fetchArray()): ?>
                <?php $friend_requests->reset(); ?>
                <div class="requests-list">
                    <?php while ($request = $friend_requests->fetchArray()): ?>
                        <div class="notification-item">
                            <img src="/assets/images/avatars/<?= htmlspecialchars($request['avatar'] ?? 'default.png') ?>" 
                                 alt="<?= htmlspecialchars($request['full_name']) ?>" 
                                 class="notification-avatar">
                            
                            <div class="notification-content">
                                <div class="notification-header">
                                    <span class="notification-author"><?= htmlspecialchars($request['full_name']) ?></span>
                                    <span class="notification-time">Хочет добавить вас в друзья</span>
                                </div>
                                
                                <div class="request-actions">
                                    <button class="btn-accept" onclick="acceptFriendRequest(<?= $request['id'] ?>, this)">
                                        <i class="fas fa-check"></i> Принять
                                    </button>
                                    <button class="btn-decline" onclick="declineFriendRequest(<?= $request['id'] ?>, this)">
                                        <i class="fas fa-times"></i> Отклонить
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Нет новых запросов в друзья</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Переключение между вкладками
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab + '-tab').classList.add('active');
    });
});

// Функции для обработки запросов в друзья
async function acceptFriendRequest(userId, button) {
    try {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const response = await fetch('/actions/accept_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ friend_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.closest('.notification-item').style.opacity = '0.5';
            setTimeout(() => {
                button.closest('.notification-item').remove();
                showToast('Запрос принят', 'success');
            }, 300);
        } else {
            throw new Error(data.error || 'Ошибка при принятии запроса');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check"></i> Принять';
        showToast(error.message, 'error');
    }
}

async function declineFriendRequest(userId, button) {
    if (!confirm('Вы уверены, что хотите отклонить запрос в друзья?')) return;
    
    try {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const response = await fetch('/actions/decline_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ friend_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            button.closest('.notification-item').style.opacity = '0.5';
            setTimeout(() => {
                button.closest('.notification-item').remove();
                showToast('Запрос отклонён', 'success');
            }, 300);
        } else {
            throw new Error(data.error || 'Ошибка при отклонении запроса');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-times"></i> Отклонить';
        showToast(error.message, 'error');
    }
}

// Вспомогательная функция для показа уведомлений
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }, 100);
}
</script>

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
    --tg-unread: #f0f8ff;
    --tg-radius: 12px;
    --tg-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.notifications-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 16px;
}

.notifications-main {
    background: var(--tg-bg);
    border-radius: var(--tg-radius);
    box-shadow: var(--tg-shadow);
    overflow: hidden;
}

.notifications-header {
    padding: 24px;
    background: var(--tg-bg);
    border-bottom: 1px solid var(--tg-border);
}

.notifications-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
    color: var(--tg-text-primary);
    display: flex;
    align-items: center;
    gap: 12px;
}

.notifications-tabs {
    display: flex;
    margin-top: 20px;
    background: var(--tg-surface);
    border-radius: var(--tg-radius);
    padding: 4px;
}

.tab-btn {
    flex: 1;
    padding: 12px 16px;
    background: transparent;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 500;
    color: var(--tg-text-secondary);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-btn.active {
    background: var(--tg-bg);
    color: var(--tg-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tab-content {
    display: none;
    padding: 0;
}

.tab-content.active {
    display: block;
}

.notifications-list, .requests-list {
    padding: 0;
}

.notification-item {
    display: flex;
    gap: 16px;
    padding: 20px;
    border-bottom: 1px solid var(--tg-border);
    transition: all 0.2s ease;
    position: relative;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.unread {
    background: var(--tg-unread);
}

.notification-item:hover {
    background: var(--tg-hover);
}

.notification-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--tg-border);
    background: var(--tg-surface);
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.notification-author {
    font-weight: 600;
    color: var(--tg-text-primary);
    font-size: 16px;
}

.notification-time {
    font-size: 13px;
    color: var(--tg-text-secondary);
}

.notification-text {
    color: var(--tg-text-primary);
    line-height: 1.5;
    font-size: 15px;
    margin-bottom: 8px;
}

.mentioned-post {
    margin-top: 12px;
    padding: 16px;
    background: var(--tg-surface);
    border-radius: var(--tg-radius);
    border-left: 4px solid var(--tg-primary);
}

.post-content {
    margin: 0;
    color: var(--tg-text-primary);
    font-size: 14px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.view-post-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--tg-primary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-top: 10px;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.view-post-btn:hover {
    background: var(--tg-accent);
    text-decoration: none;
}

.request-actions {
    display: flex;
    gap: 12px;
    margin-top: 12px;
}

.btn-accept, .btn-decline {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-accept {
    background: var(--tg-secondary);
    color: white;
}

.btn-accept:hover {
    background: #5aaf4a;
    transform: translateY(-1px);
}

.btn-decline {
    background: var(--tg-surface);
    color: var(--tg-text-secondary);
}

.btn-decline:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
}

.unread-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 12px;
    height: 12px;
    background-color: var(--tg-primary);
    border-radius: 50%;
    box-shadow: 0 0 0 2px var(--tg-bg);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--tg-text-secondary);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    color: var(--tg-border);
    opacity: 0.7;
}

.empty-state p {
    margin: 8px 0;
    font-size: 16px;
    color: var(--tg-text-secondary);
}

.empty-state h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    color: var(--tg-text-primary);
    font-weight: 600;
}

/* Анимации */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-item {
    animation: slideIn 0.3s ease forwards;
    opacity: 0;
}

.notification-item:nth-child(1) { animation-delay: 0.05s; }
.notification-item:nth-child(2) { animation-delay: 0.1s; }
.notification-item:nth-child(3) { animation-delay: 0.15s; }
.notification-item:nth-child(4) { animation-delay: 0.2s; }
.notification-item:nth-child(5) { animation-delay: 0.25s; }

/* Toast уведомления */
.toast-notification {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 16px 24px;
    border-radius: var(--tg-radius);
    color: white;
    font-weight: 500;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: 12px;
}

.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-notification.success {
    background: rgba(76, 175, 80, 0.95);
    border-left: 4px solid #4CAF50;
}

.toast-notification.error {
    background: rgba(244, 67, 54, 0.95);
    border-left: 4px solid #F44336;
}

/* Адаптивность */
@media (max-width: 768px) {
    .notifications-container {
        padding: 0 12px;
        margin: 16px auto;
    }
    
    .notifications-header {
        padding: 20px;
    }
    
    .notifications-header h1 {
        font-size: 20px;
        justify-content: center;
    }
    
    .notification-item {
        padding: 16px;
        gap: 12px;
    }
    
    .notification-avatar {
        width: 48px;
        height: 48px;
    }
    
    .request-actions {
        flex-direction: column;
        gap: 8px;
    }
    
    .btn-accept, .btn-decline {
        width: 100%;
        justify-content: center;
    }
    
    .notifications-tabs {
        flex-direction: column;
        gap: 4px;
    }
    
    .tab-btn {
        padding: 14px;
    }
}

@media (max-width: 480px) {
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .mentioned-post {
        padding: 12px;
    }
    
    .empty-state {
        padding: 40px 16px;
    }
    
    .empty-state i {
        font-size: 48px;
    }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
    :root {
        --tg-bg: #1a1a1a;
        --tg-surface: #2a2a2a;
        --tg-text-primary: #ffffff;
        --tg-text-secondary: #a8a8a8;
        --tg-border: #3a3a3a;
        --tg-hover: #2a2a2a;
        --tg-unread: rgba(0, 136, 204, 0.15);
        --tg-accent: rgba(0, 136, 204, 0.2);
    }
    
    .notification-item.unread {
        background: var(--tg-unread);
    }
    
    .mentioned-post {
        background: var(--tg-surface);
        border-left-color: var(--tg-primary);
    }
}

/* Эффекты при наведении */
.notification-item {
    cursor: pointer;
}

.notification-item:active {
    transform: scale(0.98);
}

/* Иконки в табах */
.tab-btn i {
    font-size: 18px;
}

/* Счетчики в табах */
.tab-badge {
    background: var(--tg-primary);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}
</style>

<script>
// Добавляем плавную анимацию для переключения табов
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Анимация переключения
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.style.transform = 'scale(0.98)';
        });
        
        document.querySelectorAll('.tab-content').forEach(c => {
            c.classList.remove('active');
            c.style.opacity = '0';
        });
        
        btn.classList.add('active');
        btn.style.transform = 'scale(1)';
        
        const targetTab = document.getElementById(btn.dataset.tab + '-tab');
        targetTab.classList.add('active');
        setTimeout(() => {
            targetTab.style.opacity = '1';
        }, 50);
    });
});

// Добавляем анимацию загрузки для кнопок
// Функции для обработки запросов в друзья
async function acceptFriendRequest(userId, button) {
    try {
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        const response = await fetch('/actions/accept_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ friend_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Плавное скрытие элемента
            const item = button.closest('.notification-item');
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                item.remove();
                showToast('Запрос принят', 'success');
                
                // Обновляем счетчик запросов если есть
                updateRequestsCounter();
            }, 300);
        } else {
            throw new Error(data.error || 'Ошибка при принятии запроса');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        button.innerHTML = originalHtml;
        button.disabled = false;
        showToast(error.message, 'error');
    }
}

async function declineFriendRequest(userId, button) {
    if (!confirm('Вы уверены, что хотите отклонить запрос в друзья?')) return;
    
    try {
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        const response = await fetch('/actions/decline_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ friend_id: userId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Плавное скрытие элемента
            const item = button.closest('.notification-item');
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(100%)';
            
            setTimeout(() => {
                item.remove();
                showToast('Запрос отклонён', 'success');
                
                // Обновляем счетчик запросов если есть
                updateRequestsCounter();
            }, 300);
        } else {
            throw new Error(data.error || 'Ошибка при отклонении запроса');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        button.innerHTML = originalHtml;
        button.disabled = false;
        showToast(error.message, 'error');
    }
}

// Функция для обновления счетчика запросов
function updateRequestsCounter() {
    const requests = document.querySelectorAll('#requests-tab .notification-item');
    const tabBtn = document.querySelector('.tab-btn[data-tab="requests"]');
    const badge = tabBtn.querySelector('.tab-badge');
    
    if (requests.length === 0) {
        // Если запросов нет, показываем empty state
        const emptyState = document.querySelector('#requests-tab .empty-state');
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        
        // Убираем badge
        if (badge) {
            badge.remove();
        }
    } else {
        // Обновляем badge
        if (badge) {
            badge.textContent = requests.length;
        } else {
            const newBadge = document.createElement('span');
            newBadge.className = 'tab-badge';
            newBadge.textContent = requests.length;
            tabBtn.appendChild(newBadge);
        }
    }
}

// Показываем уведомления
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }, 100);
}

// Добавляем обработчики для кнопок после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем счетчики запросов
    updateRequestsCounter();
    
    // Добавляем badge на кнопку запросов
    const requests = document.querySelectorAll('#requests-tab .notification-item');
    const tabBtn = document.querySelector('.tab-btn[data-tab="requests"]');
    
    if (requests.length > 0 && tabBtn && !tabBtn.querySelector('.tab-badge')) {
        const badge = document.createElement('span');
        badge.className = 'tab-badge';
        badge.textContent = requests.length;
        tabBtn.appendChild(badge);
    }
});

// Плавная прокрутка к уведомлениям
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach((item, index) => {
        item.style.animationDelay = `${index * 0.05}s`;
    });
});
</script>
<style>
/* Исправление текста в боковом меню для темной темы */
@media (prefers-color-scheme: dark) {
    .sidebar-item {
        color: #ffffff !important;
    }
    
    .sidebar-item:hover,
    .sidebar-item.active {
        color: var(--tg-primary) !important;
    }
    
    .sidebar-item i {
        color: #a8a8a8 !important;
    }
    
    .sidebar-item:hover i,
    .sidebar-item.active i {
        color: var(--tg-primary) !important;
    }
    
    .sidebar-item span {
        color: inherit !important;
    }
    
    /* Улучшение контрастности */
    .sidebar-header {
        background: var(--tg-card-bg) !important;
        border-bottom: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-items {
        background: var(--tg-bg);
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
        border-left-color: var(--tg-primary) !important;
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
}

[data-theme="dark"] .sidebar-item:hover,
[data-theme="dark"] .sidebar-item.active,
.dark-mode .sidebar-item:hover,
.dark-mode .sidebar-item.active,
body.dark .sidebar-item:hover,
body.dark .sidebar-item.active {
    color: var(--tg-primary) !important;
}

/* Улучшение для мобильной версии */
@media (max-width: 768px) {
    body{
        margin-bottom: 100px !important;
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
    background: var(--tg-bg) !important;
}

.sidebar-item {
    background: transparent !important;
}

.sidebar-item:hover {
    background: var(--tg-hover) !important;
}

.sidebar-item.active {
    background: var(--tg-accent) !important;
}
</style>

<script>
// Дополнительный скрипт для гарантии видимости текста
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем темную тему и принудительно применяем стили
    const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (isDarkMode) {
        // Добавляем класс для темной темы
        document.body.classList.add('dark-mode');
        
        // Принудительно обновляем стили sidebar
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            item.style.color = '#ffffff';
            item.style.fontWeight = '500';
        });
        
        const sidebarActiveItems = document.querySelectorAll('.sidebar-item.active');
        sidebarActiveItems.forEach(item => {
            item.style.color = 'var(--tg-primary)';
        });
    }
    
    // Слушаем изменения темы
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>