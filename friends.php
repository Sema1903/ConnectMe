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
            <button class="tab-btn" data-tab="online">Онлайн (<?= count($online_friends) ?>)</button>
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

<?php require_once 'includes/footer.php'; ?>