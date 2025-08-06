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
.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
    z-index: 1000;
}

.toast-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.toast-notification.success {
    background-color: #4CAF50;
}

.toast-notification.error {
    background-color: #F44336;
}




/* Добавить в CSS */
.crypto-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 1001;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    max-width: 300px;
}

.crypto-alert.show {
    opacity: 1;
    transform: translateX(0);
}

.crypto-alert-success {
    background: #2e7d32;
    color: white;
    border-left: 4px solid #4caf50;
}

.crypto-alert-error {
    background: #c62828;
    color: white;
    border-left: 4px solid #f44336;
}

.crypto-alert-icon {
    font-size: 20px;
}

.crypto-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.crypto-network {
    font-size: 0.7rem;
    background: rgba(247, 147, 26, 0.2);
    color: #f7931a;
    padding: 3px 8px;
    border-radius: 4px;
}

.crypto-wallet-address {
    background: rgba(255, 255, 255, 0.05);
    padding: 8px 12px;
    border-radius: 6px;
    margin: 15px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    justify-content: center;
}

.copy-btn {
    background: transparent;
    border: none;
    color: #aaa;
    cursor: pointer;
    transition: all 0.3s;
    padding: 5px;
    border-radius: 4px;
}

.copy-btn:hover {
    color: #f7931a;
    background: rgba(247, 147, 26, 0.1);
}

.copy-btn.copied {
    color: #4caf50;
}
</style>

<?php require_once 'includes/footer.php'; ?>