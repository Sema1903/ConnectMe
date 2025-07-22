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
                        <textarea name="content" placeholder="Напишите сообщение для группы..." rows="3"></textarea>
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
                        <img src="/assets/images/empty-group.svg" alt="Нет публикаций">
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
</script>

<?php require_once 'includes/footer.php'; ?>