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

// Получаем всех участников группы
$members = getGroupMembers($db, $group_id);

require_once 'includes/header.php';
?>

<main class="group-members-page">
    <div class="container">
        <!-- Хлебные крошки -->
        <div class="breadcrumbs">
            <a href="/">Главная</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/group.php?id=<?= $group['id'] ?>"><?= htmlspecialchars($group['name']) ?></a>
            <i class="fas fa-chevron-right"></i>
            <span>Участники</span>
        </div>

        <!-- Заголовок -->
        <div class="page-header">
            <h1>Участники группы "<?= htmlspecialchars($group['name']) ?>"</h1>
            <div class="members-count"><?= count($members) ?> участников</div>
        </div>

        <!-- Поиск и фильтры -->
        <div class="members-toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Поиск участников..." id="members-search">
            </div>
        </div>

        <!-- Список участников -->
        <div class="members-list">
            <?php if (!empty($members)): ?>
                <?php foreach ($members as $member): ?>
                    <div class="member-card">
                        <a href="/profile.php?id=<?= $member['id'] ?>" class="member-avatar">
                            <img src="/assets/images/avatars/<?= htmlspecialchars($member['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($member['full_name']) ?>"
                                 onerror="this.src='/assets/images/avatars/default.jpg'">
                            <?php if (isUserOnline($member['id'])): ?>
                                <div class="online-badge"></div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="member-info">
                            <a href="/profile.php?id=<?= $member['id'] ?>" class="member-name">
                                <?= htmlspecialchars($member['full_name']) ?>
                                <?php if ($member['id'] == $group['creator_id']): ?>
                                    <span class="creator-badge" title="Создатель группы">
                                        <i class="fas fa-crown"></i>
                                    </span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="member-meta">
                                <span class="member-role">
                                    <?php if ($member['id'] == $group['creator_id']): ?>
                                        Создатель
                                    <?php else: ?>
                                        Участник
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="member-actions">
                                <?php if ($current_user && $current_user['id'] != $member['id']): ?>
                                    <button class="btn btn-message" onclick="window.location.href='/messages.php?user_id=<?= $member['id'] ?>'">
                                        <i class="far fa-comment"></i> Написать
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($current_user && $current_user['id'] == $group['creator_id'] && $member['id'] != $group['creator_id']): ?>
                                    <button class="btn btn-remove" onclick="removeMember(<?= $group['id'] ?>, <?= $member['id'] ?>)">
                                        <i class="fas fa-user-minus"></i> Удалить
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-members">
                    <img src="/assets/images/empty-group.svg" alt="Нет участников">
                    <h3>В группе пока нет участников</h3>
                    <p>Пригласите друзей, чтобы они присоединились к вашей группе</p>
                    <?php if ($is_member): ?>
                        <button class="btn btn-invite" onclick="showInviteDialog()">
                            <i class="fas fa-user-plus"></i> Пригласить друзей
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Основные стили */
.group-members-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 15px;
    color: #333;
}

.container {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

/* Хлебные крошки */
.breadcrumbs {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 0.9rem;
    color: #65676b;
    margin-bottom: 25px;
    height: 0px;
}

.breadcrumbs a {
    color: var(--primary-color);
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumbs a:hover {
    color: #166fe5;
    text-decoration: underline;
}

.breadcrumbs i {
    font-size: 0.7rem;
    opacity: 0.7;
}

.breadcrumbs span {
    color: #65676b;
    font-weight: 500;
}

/* Заголовок страницы */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
    height: 0px;
}

.page-header h1 {
    font-size: 1.8rem;
    margin: 0;
    color: #333;
}

.members-count {
    background: #f0f2f5;
    color: #65676b;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Панель инструментов */
.members-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
    height: 0px;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
    max-width: 400px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #65676b;
}

.search-box input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 1px solid #ddd;
    border-radius: 20px;
    font-size: 0.95rem;
    outline: none;
    transition: all 0.2s;
}

.search-box input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
}

.sort-options select {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    background: white;
    cursor: pointer;
    outline: none;
    transition: all 0.2s;
}

.sort-options select:focus {
    border-color: var(--primary-color);
}

/* Список участников */
.members-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.member-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 10px;
    transition: all 0.2s;
    border: 1px solid #eee;
}

.member-card:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    border-color: #ddd;
}

.member-avatar {
    position: relative;
    width: 70px;
    height: 70px;
    flex-shrink: 0;
}

.member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.online-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #31a24c;
    border: 2px solid white;
}

.member-info {
    flex: 1;
    min-width: 0;
}

.member-name {
    font-weight: 600;
    font-size: 1.05rem;
    color: #333;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 5px;
}

.member-name:hover {
    color: var(--primary-color);
    text-decoration: underline;
}

.creator-badge {
    color: #f7b500;
    font-size: 0.9rem;
    margin-left: 5px;
}

.member-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 0.85rem;
    color: #65676b;
    margin-bottom: 10px;
}

.member-role {
    background: #f0f2f5;
    padding: 3px 8px;
    border-radius: 12px;
}

.member-joined i {
    margin-right: 3px;
}

.member-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-message {
    background: #e7f3ff;
    color: var(--primary-color);
}

.btn-message:hover {
    background: #dbe7f2;
}

.btn-remove {
    background: #ffeeee;
    color: #f02849;
}

.btn-remove:hover {
    background: #f8d7d7;
}

/* Пустой список */
.empty-members {
    text-align: center;
    padding: 40px 20px;
    grid-column: 1 / -1;
}

.empty-members img {
    max-width: 200px;
    opacity: 0.7;
    margin-bottom: 20px;
}

.empty-members h3 {
    color: #333;
    margin-bottom: 10px;
}

.empty-members p {
    color: #65676b;
    margin-bottom: 20px;
}

.btn-invite {
    background: var(--primary-color);
    color: white;
    padding: 10px 20px;
    margin: 0 auto;
}

.btn-invite:hover {
    background: #166fe5;
}
/* Адаптивность */
@media (max-width: 768px) {
    .members-list {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
    
    .member-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .member-info {
        width: 100%;
        text-align: center;
    }
    
    .member-actions {
        justify-content: center;
    }
    .members-toolbar{
        height: auto;
    }
    .page-header{
        height: auto;
    }
    .breadcrums{
        height: auto;
    }
}

@media (max-width: 480px) {
    .members-list {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .search-box {
        min-width: 100%;
    }
}
</style>

<script>
function removeMember(groupId, userId) {
    if (confirm('Вы уверены, что хотите удалить этого участника из группы?')) {
        fetch('/actions/remove_group_member.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                group_id: groupId,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка при удалении участника');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Произошла ошибка');
        });
    }
}

function showInviteDialog() {
    // Здесь можно реализовать модальное окно для приглашения друзей
    alert('Функция приглашения друзей будет реализована в будущем');
}

// Поиск участников
document.getElementById('members-search').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const members = document.querySelectorAll('.member-card');
    
    members.forEach(member => {
        const name = member.querySelector('.member-name').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            member.style.display = 'flex';
        } else {
            member.style.display = 'none';
        }
    });
});


</script>

<?php require_once 'includes/footer.php'; ?>