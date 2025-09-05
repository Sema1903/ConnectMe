<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
$group_id = $_GET['id'] ?? 0;

// Получаем информацию о группе
$stmt = $db->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$group = $result->fetchArray(SQLITE3_ASSOC);
$posts = getGroupPosts($db, $group['id']);

if (!$group) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

// Проверяем, является ли пользователь участником группы
$is_member = false;
if ($current_user) {
    $stmt = $db->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
    $is_member = (bool)$stmt->execute()->fetchArray();
}

// Получаем участников группы
$members = getGroupMembers($db, $group_id);

require_once 'includes/header.php';
?>



<main class="group-page">
    <!-- Шапка группы -->
    <div class="group-header">
        <div class="group-cover" style="background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);"></div>
        
        <div class="group-info-container">
            <div class="group-info">
                <div class="group-avatar-container">
                    <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                         alt="<?= htmlspecialchars($group['name']) ?>" 
                         class="group-avatar"
                         onerror="this.src='/assets/images/groups/default.jpg'">
                    <div class="group-verified">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                
                <div class="group-details">
                    <h1 class="group-title"><?= htmlspecialchars($group['name']) ?></h1>
                    <p class="group-description"><?= htmlspecialchars($group['description']) ?></p>
                    
                    <div class="group-stats">
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span><?= count($members) ?> участников</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>С <?= date('d.m.Y', strtotime($group['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="group-actions">
                <?php if ($current_user): ?>
                    <?php if ($is_member): ?>
                        <button class="action-btn leave-btn" onclick="leaveGroup(<?= $group['id'] ?>)">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Покинуть группу</span>
                        </button>
                    <?php else: ?>
                        <button class="action-btn join-btn" onclick="joinGroup(<?= $group['id'] ?>)">
                            <i class="fas fa-plus"></i>
                            <span>Присоединиться</span>
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($group['creator_id'] == $current_user['id']): ?>
                        <a href="/edit_group.php?id=<?= $group['id'] ?>" class="action-btn manage-btn">
                            <i class="fas fa-cog"></i>
                            <span>Управление</span>
                        </a>
                    <?php else: ?>
                        <!-- Добавляем кнопку отправки средств -->
                        <button class="action-btn send-money-btn" onclick="showSendModal(<?= $group['creator_id'] ?>, '<?= htmlspecialchars(getUserNameById($db, $group['creator_id'])) ?>')">
                            <i class="fas fa-coins"></i>
                            <span>Отправить средства</span>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Основное содержимое -->
    <div class="group-content">
        <!-- Левая колонка -->
        <aside class="group-sidebar">
            <div class="sidebar-card about-card">
                <h3><i class="fas fa-info-circle"></i> О группе</h3>
                <div class="about-item">
                    <i class="fas fa-user"></i>
                    <div>
                        <span class="about-label">Создатель</span>
                        <a href="/profile.php?id=<?= $group['creator_id'] ?>" class="about-value">
                            <?= htmlspecialchars(getUserNameById($db, $group['creator_id'])) ?>
                        </a>
                    </div>
                </div>
                <div class="about-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span class="about-label">Дата создания</span>
                        <span class="about-value"><?= date('d.m.Y', strtotime($group['created_at'])) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-card members-card">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Участники</h3>
                    <span class="count-badge"><?= count($members) ?></span>
                </div>
                
                <div class="members-grid">
                    <?php foreach (array_slice($members, 0, 9) as $member): ?>
                        <a href="/profile.php?id=<?= $member['id'] ?>" class="member-item" title="<?= htmlspecialchars($member['full_name']) ?>">
                            <img src="/assets/images/avatars/<?= htmlspecialchars($member['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($member['full_name']) ?>"
                                 onerror="this.src='/assets/images/avatars/default.jpg'">
                            <?php if (isUserOnline($member['id'])): ?>
                                <div class="online-dot"></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($members) > 9): ?>
                    <a href="/group_members.php?id=<?= $group['id'] ?>" class="view-all-btn">
                        Показать всех участников
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Центральная колонка -->
        <div class="group-main">
            <?php if ($current_user && $is_member): ?>
                <div class="create-post-card">
                    <div class="post-author">
                        <img src="/assets/images/avatars/<?= htmlspecialchars($current_user['avatar']) ?>" 
                             alt="Ваш аватар"
                             onerror="this.src='/assets/images/avatars/default.jpg'">
                    </div>
                    <form class="post-form" method="POST" action="/actions/create_group_post.php">
                        <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                        <textarea name="content" placeholder="Напишите сообщение для группы..." rows="3" class="textarea"></textarea>
                        <div class="post-actions">
                            <input type = 'file' name = 'image'>
                            <button type="submit" class="post-submit-btn">Опубликовать</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <div class="posts-feed">
                <?php if (!empty($posts)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card">
                        <div class="feed" id="post-<?= $post['id'] ?>">
                            <div class="post">
                            <div class="post-header">
                                <a href="/profile.php?id=<?= $post['user_id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                                    <div class="post-user">
                                        <img src="assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" alt="User">
                                        <div class="user-details">
                                            <div class="name"><?= htmlspecialchars($post['full_name']) ?></div>
                                            <div class="time"><?= time_elapsed_string($post['created_at']) ?></div>
                                        </div>
                                    </div>
                                </a>
                                <div class="post-options">
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                            </div>
                                
                            <div class="post-content">
                                <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                <?php if ($post['image']): ?>
                                    <img src="/assets/images/posts/<?= $post['image'] ?>" alt="Post Image" class="post-image">
                                <?php endif; ?>
                            </div>
                            </div>
                        </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-feed">
                        <i class="fas fa-newspaper"></i>
                        <h3>В группе пока нет публикаций</h3>
                        <?php if ($current_user && $is_member): ?>
                            <p>Будьте первым, кто поделится чем-то в этой группе!</p>
                        <?php else: ?>
                            <p>Присоединитесь к группе, чтобы видеть публикации</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>






    <!-- Модальное окно для отправки средств -->
<div id="sendModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeSendModal()">&times;</span>
        <h3 id="modalTitle">Отправить ConnectCoin</h3>
        <form id="sendCurrencyForm" onsubmit="sendCurrency(event)">
            <input type="hidden" name="to_user_id" id="sendToUserId">
            
            <div class="form-group">
                <label for="sendAmount">Сумма</label>
                <input type="number" id="sendAmount" name="amount" step="0.01" min="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="sendMessage">Сообщение (необязательно)</label>
                <input type="text" id="sendMessage" name="message" placeholder="Назначение платежа">
            </div>
            
            <button type="submit" class="btn btn-primary">Отправить</button>
        </form>
    </div>
</div>
</main>

<style>
/* Основные стили */
.group-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
    color: #333;
}

/* Шапка группы */
.group-header {
    position: relative;
    margin-bottom: 30px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    margin-top: 30px;
    padding-top: 30px;
}

.group-cover {
    height: 300px;
    background-size: cover;
    background-position: center;
}

.group-info-container {
    position: relative;
    padding: 0 30px 30px;
}

.group-info {
    display: flex;
    align-items: flex-end;
    margin-top: -60px;
    position: relative;
    z-index: 2;
}

.group-avatar-container {
    position: relative;
    width: 150px;
    height: 150px;
    border-radius: 12px;
    border: 4px solid white;
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.group-avatar {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
}

.group-verified {
    position: absolute;
    bottom: -8px;
    right: -8px;
    background: #1877f2;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: 3px solid white;
}

.group-details {
    margin-left: 30px;
    flex: 1;
}

.group-title {
    font-size: 2.2rem;
    margin: 0 0 10px 0;
    color: #333;
}

.group-description {
    font-size: 1rem;
    color: #666;
    margin: 0 0 20px 0;
    max-width: 600px;
}

.group-stats {
    display: flex;
    gap: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #65676b;
    font-size: 0.95rem;
}

.stat-item i {
    color: var(--primary-color);
}

.group-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 0.95rem;
}

.join-btn {
    background: var(--primary-color);
    color: white;
    width: 160px;
    height: 50px;
}

.join-btn:hover {
    background: #166fe5;
}

.leave-btn {
    background: #f0f2f5;
    color: #333;
    width: 140px;
    height: 50px;
}

.leave-btn:hover {
    background: #e4e6eb;
}

.manage-btn {
    background: #e7f3ff;
    color: var(--primary-color);
    text-decoration: none;
    margin-left: 50px;
    width: 140px;
    height: 50px;
}

.manage-btn:hover {
    background: #dbe7f2;
}

/* Основное содержимое */
.group-content {
    display: flex;
    gap: 24px;
    margin-top: 20px;
}

.group-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.group-main {
    flex: 1;
    min-width: 0;
}

/* Боковая панель */
.sidebar-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.sidebar-card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1rem;
    margin: 0 0 20px 0;
    color: #333;
}

.about-item {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    align-items: center;
}

.about-item i {
    color: var(--primary-color);
    font-size: 1.1rem;
    width: 24px;
    text-align: center;
}

.about-label {
    display: block;
    font-size: 0.8rem;
    color: #65676b;
    margin-bottom: 2px;
}

.about-value {
    font-weight: 500;
    color: #333;
    text-decoration: none;
}

.about-value:hover {
    text-decoration: underline;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.count-badge {
    background: #f0f2f5;
    color: #65676b;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.members-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.member-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}

.member-item img {
    width: 65px;
    height: 65px;
    object-fit: cover;
    transition: transform 0.2s;
}

.member-item:hover img {
    transform: scale(1.05);
}

.online-dot {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #31a24c;
    border: 2px solid white;
}

.view-all-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.view-all-btn:hover {
    color: #166fe5;
}

.view-all-btn i {
    font-size: 0.8rem;
}

/* Создание поста */
.create-post-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 12px;
}

.post-author {
    width: 40px;
    height: 40px;
}

.post-author img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.post-form {
    flex: 1;
}

.post-form textarea {
    width: 100%;
    border: none;
    resize: none;
    font-family: inherit;
    font-size: 1rem;
    padding: 10px 0;
    min-height: 60px;
    outline: none;
}

.post-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #eee;
    padding-top: 12px;
}

.action-icon {
    background: none;
    border: none;
    color: #65676b;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.2s;
}

.action-icon:hover {
    background: #f0f2f5;
    color: var(--primary-color);
}

.post-submit-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.post-submit-btn:hover {
    background: #166fe5;
}
/* Лента постов */
.empty-feed {
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.empty-feed img {
    max-width: 200px;
    opacity: 0.7;
    margin-bottom: 20px;
}

.empty-feed h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-feed p {
    color: #65676b;
    margin: 0;
}

/* Адаптивность */
@media (max-width: 992px) {
    .group-content {
        flex-direction: column;
    }
    .group-sidebar {
        width: 100%;
    }
    .group-header{
        height: 350px;
        padding-top: 150px;
    }
    .group-info {
        flex-direction: column;
        align-items: flex-start;
        margin-top: -120px;
    }
    .manage-btn{
        margin-left: 10px;
    }
    
    .group-details {
        margin-left: 0;
        margin-top: 20px;
    }
    .group-title {
        font-size: 1.8rem;
    }
    .member-item img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        transition: transform 0.2s;
    }
    .post-form {
        width: 100%;
        overflow: hidden;
    }
    
    .post-form textarea {
        width: calc(100% - 20px); /* учитываем padding */
        max-width: 100%;
        box-sizing: border-box;
    }
    
    .post-actions {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .post-actions input[type="file"] {
        width: 100%;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .post-submit-btn {
        width: 100%;
        padding: 10px;
    }
    
    .create-post-card {
        padding: 12px;
        flex-direction: column;
    }
    
    .post-author {
        margin-bottom: 10px;
    }
}

@media (max-width: 576px) {
    .group-avatar-container {
        width: 100px;
        height: 100px;
    }
    .group-actions {
        flex-wrap: wrap;
    }
    
    .members-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
.send-money-btn {
    background: linear-gradient(90deg, #f7931a 0%, #f9b54a 100%);
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 140px;
    height: 50px;
}

.send-money-btn:hover {
    background: linear-gradient(90deg, #f9b54a 0%, #f7931a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(247, 147, 26, 0.3);
}
.group-header{
    height: 450px;
}
</style>
<style>
.post-text {
    white-space: pre-wrap; /* Сохраняет пробелы и переносы */
    word-wrap: break-word; /* Переносит длинные слова */
    line-height: 1.4;
}
</style>


<?php require_once 'includes/footer.php'; ?>

<script>
function joinGroup(groupId) {
    fetch('/actions/join_group.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ group_id: groupId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function leaveGroup(groupId) {
    if (confirm('Вы уверены, что хотите покинуть группу?')) {
        fetch('/actions/leave_group.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ group_id: groupId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}
document.querySelector('.post-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/actions/create_group_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем ленту или показываем сообщение
            location.reload(); // или динамически добавляем пост
        } else {
            alert(data.error || 'Ошибка при создании поста');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка');
    });
});








// Показываем модальное окно для отправки средств
function showSendModal(userId, username) {
    const modal = document.getElementById('sendModal');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.textContent = `Отправить ConnectCoin владельцу группы @${username}`;
    document.getElementById('sendToUserId').value = userId;
    
    modal.style.display = 'block';
}

// Закрываем модальное окно
function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
    document.getElementById('sendCurrencyForm').reset();
}

// Отправка средств
function sendCurrency(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const toUserId = formData.get('to_user_id');
    const amount = parseFloat(formData.get('amount'));
    const message = formData.get('message') || '';
    
    if (!toUserId || isNaN(amount) || amount <= 0) {
        alert('Пожалуйста, укажите корректную сумму');
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
            alert('Средства успешно отправлены!');
            closeSendModal();
        } else {
            alert(data.message || 'Ошибка при отправке средств');
        }
        location.reload(true);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при отправке');
        location.reload(true);
    });
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('sendModal');
    if (event.target == modal) {
        closeSendModal();
    }
}
</script>
<style>
/* Базовые переменные для темной темы Telegram */
:root {
  --tg-dark-bg: #0f0f0f;
  --tg-dark-secondary: #1a1a1a;
  --tg-dark-card: #1e1e1e;
  --tg-dark-border: #2d2d2d;
  --tg-dark-text: #e3e3e3;
  --tg-dark-text-secondary: #a0a0a0;
  --tg-dark-primary: #2ea6ff;
  --tg-dark-hover: #2a2a2a;
  --tg-dark-input: #2a2a2a;
}

/* Применение темной темы */
@media (prefers-color-scheme: dark) {
  .group-page {
    background-color: var(--tg-dark-bg);
    color: var(--tg-dark-text);
  }

  .group-header {
    background: linear-gradient(135deg, #2c3e50 0%, #1a1a2e 100%) !important;
  }

  .group-title {
    color: var(--tg-dark-text) !important;
  }

  .group-description {
    color: var(--tg-dark-text-secondary) !important;
  }

  .stat-item {
    color: var(--tg-dark-text-secondary) !important;
  }

  .stat-item i {
    color: var(--tg-dark-text-secondary) !important;
  }

  .sidebar-card {
    background: var(--tg-dark-card) !important;
    border: 1px solid var(--tg-dark-border) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
  }

  .sidebar-card h3 {
    color: var(--tg-dark-text) !important;
  }

  .about-label {
    color: var(--tg-dark-text-secondary) !important;
  }

  .about-value {
    color: var(--tg-dark-text) !important;
  }

  .count-badge {
    background: var(--tg-dark-input) !important;
    color: var(--tg-dark-text-secondary) !important;
  }

  .create-post-card {
    background: var(--tg-dark-card) !important;
    border: 1px solid var(--tg-dark-border) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
  }

  .post-form textarea {
    background: var(--tg-dark-card) !important;
    color: var(--tg-dark-text) !important;
    border: none !important;
  }

  .post-form textarea::placeholder {
    color: var(--tg-dark-text-secondary) !important;
  }

  .post-actions {
    border-top-color: var(--tg-dark-border) !important;
  }

  .post-actions input[type="file"] {
    background: var(--tg-dark-input) !important;
    color: var(--tg-dark-text) !important;
    border: 1px solid var(--tg-dark-border) !important;
  }

  .empty-feed {
    background: var(--tg-dark-card) !important;
    border: 1px solid var(--tg-dark-border) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
  }

  .empty-feed h3 {
    color: var(--tg-dark-text) !important;
  }

  .empty-feed p {
    color: var(--tg-dark-text-secondary) !important;
  }

  .view-all-btn {
    border-top-color: var(--tg-dark-border) !important;
    color: var(--tg-dark-primary) !important;
  }

  .join-btn {
    background: var(--tg-dark-primary) !important;
    color: white !important;
  }

  .join-btn:hover {
    background: #008be6 !important;
  }

  .leave-btn {
    background: var(--tg-dark-input) !important;
    color: var(--tg-dark-text) !important;
    border: 1px solid var(--tg-dark-border) !important;
  }

  .leave-btn:hover {
    background: var(--tg-dark-hover) !important;
  }

  .manage-btn {
    background: rgba(46, 166, 255, 0.15) !important;
    color: var(--tg-dark-primary) !important;
    border: 1px solid rgba(46, 166, 255, 0.3) !important;
  }

  .manage-btn:hover {
    background: rgba(46, 166, 255, 0.25) !important;
  }

  .send-money-btn {
    background: linear-gradient(90deg, #f7931a 0%, #f9b54a 100%) !important;
    color: white !important;
  }

  .send-money-btn:hover {
    background: linear-gradient(90deg, #f9b54a 0%, #f7931a 100%) !important;
  }

  /* Стили для постов в ленте */
  .post-card {
    background: var(--tg-dark-card) !important;
    border: 1px solid var(--tg-dark-border) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
  }

  .post-user .name {
    color: black;
  }

  .post-user .time {
    color: var(--tg-dark-text-secondary) !important;
  }

  .post-text {
    color: black;
  }

  .post-options {
    color: var(--tg-dark-text-secondary) !important;
  }

  .post-options:hover {
    background: var(--tg-dark-hover) !important;
  }

  /* Модальное окно */
  .modal-content {
    background: var(--tg-dark-card) !important;
    border: 1px solid var(--tg-dark-border) !important;
    color: var(--tg-dark-text) !important;
  }

  .modal-content h3 {
    color: var(--tg-dark-text) !important;
  }

  .modal-content .form-group label {
    color: var(--tg-dark-text) !important;
  }

  .modal-content input {
    background: var(--tg-dark-input) !important;
    color: var(--tg-dark-text) !important;
    border: 1px solid var(--tg-dark-border) !important;
  }

  .modal-content input::placeholder {
    color: var(--tg-dark-text-secondary) !important;
  }

  .btn-primary {
    background: var(--tg-dark-primary) !important;
    color: white !important;
  }

  .close {
    color: var(--tg-dark-text) !important;
  }
}

/* Улучшения для темной темы */
@media (prefers-color-scheme: dark) {
  .group-avatar-container {
    border-color: var(--tg-dark-border) !important;
    background: var(--tg-dark-card) !important;
  }

  .group-verified {
    background: var(--tg-dark-primary) !important;
    border-color: var(--tg-dark-card) !important;
  }

  .member-item {
    border: 1px solid var(--tg-dark-border) !important;
  }

  .online-dot {
    border-color: var(--tg-dark-card) !important;
  }

  .post-author img {
    border: 2px solid var(--tg-dark-border) !important;
  }

  .post-image {
    border: 1px solid var(--tg-dark-border) !important;
  }
}

/* Плавные переходы */
.group-page,
.group-header,
.sidebar-card,
.create-post-card,
.post-card,
.action-btn {
  transition: all 0.3s ease;
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