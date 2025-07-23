<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
$error = '';
$success = '';

// Обработка создания новой группы
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    if (!$current_user) {
        header('Location: /login.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = 'Название группы обязательно';
    } else {
        // Обработка аватарки группы
        $avatar = 'unknown.png';
        if (!empty($_FILES['avatar']['name'])) {
            $upload_dir = __DIR__ . '/../assets/images/groups/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['avatar']['name']);
            $file_ext = strtolower($file_info['extension']);
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $file_name = 'group_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    $avatar = $file_name;
                }
            }
        }
        
        // Создаем группу
        $stmt = $db->prepare("INSERT INTO groups (name, description, avatar, creator_id, created_at) 
                             VALUES (?, ?, ?, ?, datetime('now'))");
        $stmt->bindValue(1, $name, SQLITE3_TEXT);
        $stmt->bindValue(2, $description, SQLITE3_TEXT);
        $stmt->bindValue(3, $avatar, SQLITE3_TEXT);
        $stmt->bindValue(4, $current_user['id'], SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $group_id = $db->lastInsertRowID();
            // Автоматически добавляем создателя в участники
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id, joined_at) 
                                 VALUES (?, ?, datetime('now'))");
            $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $current_user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $success = 'Группа успешно создана!';
            header("Location: /group.php?id=$group_id");
            exit;
        } else {
            $error = 'Ошибка при создании группы';
        }
    }
}

// Получаем список всех групп
$stmt = $db->prepare("SELECT g.*, u.full_name as creator_name, 
                     (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as members_count
                     FROM groups g
                     JOIN users u ON g.creator_id = u.id
                     ORDER BY g.created_at DESC");
$result = $stmt->execute();

$groups = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $groups[] = $row;
}

require_once 'includes/header.php';
?>

<main class="groups-page">
    <div class="groups-header">
        <h1 class="groups-title">
            <i class="fas fa-users"></i>
            <span>Сообщества</span>
        </h1>
        
        <?php if ($current_user): ?>
            <button class="create-group-btn" id="showCreateForm">
                <i class="fas fa-plus-circle"></i>
                <span>Создать группу</span>
            </button>
        <?php endif; ?>
    </div>

    <!-- Форма создания группы (изначально скрыта) -->
    <div class="create-group-wrapper" id="createGroupForm" style="display: none;">
        <div class="create-group-container">
            <div class="create-group-header">
                <h2>Новое сообщество</h2>
                <button class="close-btn" id="hideCreateForm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="create-group-form">
                <div class="form-group avatar-upload">
                    <div class="avatar-preview" id="avatarPreview">
                        <img src="/assets/images/groups/default.jpg" alt="Аватар группы">
                        <label for="avatarUpload" class="edit-avatar-btn">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="avatarUpload" name="avatar" accept="image/*">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="groupName">Название группы*</label>
                    <input type="text" id="groupName" name="name" required 
                           placeholder="Например: Киноманы Москвы">
                </div>
                
                <div class="form-group">
                    <label for="groupDescription">Описание</label>
                    <textarea id="groupDescription" name="description" rows="3"
                              placeholder="Расскажите о тематике группы"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="create_group" class="submit-btn">
                        <i class="fas fa-rocket"></i>
                        <span>Создать сообщество</span>
                    </button>
                    <button type="button" class="cancel-btn" id="hideCreateFormAlt">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Список групп -->
    <div class="groups-list-container">
        <?php if (!empty($groups)): ?>
            <div class="groups-grid">
                <?php foreach ($groups as $group): ?>
                    <a href="/group.php?id=<?= $group['id'] ?>" class="group-card">
                        <div class="group-cover" style="background: linear-gradient(135deg, 
                            <?= sprintf('#%06X', mt_rand(0, 0xFFFFFF)) ?>, 
                            <?= sprintf('#%06X', mt_rand(0, 0xFFFFFF)) ?>)">
                            <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($group['name']) ?>"
                                 onerror="this.src='/assets/images/groups/default.jpg'">
                        </div>
                        
                        <div class="group-info">
                            <h3 class="group-name"><?= htmlspecialchars($group['name']) ?></h3>
                            <p class="group-description"><?= htmlspecialchars($group['description']) ?></p>
                            
                            <div class="group-meta">
                                <span class="members-count">
                                    <i class="fas fa-users"></i>
                                    <?= $group['members_count'] ?>
                                </span>
                                <span class="creator">
                                    <i class="fas fa-crown"></i>
                                    <?= htmlspecialchars($group['creator_name']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="group-actions">
                            <?php if ($current_user && $group['creator_id'] == $current_user['id']): ?>
                                <span class="badge creator-badge">Вы создатель</span>
                            <?php else: ?>
                                <button class="join-btn" data-group-id="<?= $group['id'] ?>">
                                    <i class="fas fa-plus"></i>
                                    <span>Присоединиться</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-users-slash"></i>
                </div>
                <h3>Пока нет групп</h3>
                <p>Создайте первое сообщество и пригласите друзей</p>
                <?php if ($current_user): ?>
                    <button class="create-group-btn" id="showCreateFormAlt">
                        <i class="fas fa-plus-circle"></i>
                        <span>Создать группу</span>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
/* Основные стили */
.groups-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    color: #2d3748;
}

/* Шапка страницы */
.groups-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.groups-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    margin: 0;
    color: #1a202c;
}

.groups-title i {
    color: var(--primary-color);
}

.create-group-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.create-group-btn:hover {
    background: #2c5282;
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
}

.create-group-btn i {
    font-size: 18px;
}

/* Форма создания группы */
.create-group-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.create-group-container {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transform: translateY(0);
    transition: transform 0.3s ease;
}

.create-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--primary-color);
    color: white;
}

.create-group-header h2 {
    margin: 0;
    font-size: 20px;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.create-group-form {
    padding: 20px;
}

.avatar-upload {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    position: relative;
    overflow: hidden;
    border: 3px solid #e2e8f0;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-avatar-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: var(--primary-color);
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.edit-avatar-btn:hover {
    transform: scale(1.1);
}

#avatarUpload {
    display: none;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #4a5568;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 16px;
    transition: border 0.3s;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.submit-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.submit-btn:hover {
    background: #2b6cb0;
}

.cancel-btn {
    background: #e2e8f0;
    color: #4a5568;
    border: none;
    padding: 14px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.cancel-btn:hover {
    background: #cbd5e0;
}

/* Список групп */
.groups-list-container {
    margin-top: 30px;
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
}

.group-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.group-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.group-cover {
    height: 140px;
    position: relative;
    overflow: hidden;
}

.group-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.group-card:hover .group-cover img {
    transform: scale(1.05);
}

.group-info {
    padding: 18px;
    flex: 1;
}

.group-name {
    margin: 0 0 8px 0;
    font-size: 18px;
    color: #2d3748;
    font-weight: 700;
}

.group-description {
    margin: 0 0 15px 0;
    color: #718096;
    font-size: 14px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.group-meta {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #718096;
    margin-top: auto;
}

.group-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.group-meta i {
    font-size: 12px;
}

.group-actions {
    padding: 0 18px 18px;
    margin-top: 10px;
}

.join-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #ebf8ff;
    color: var(--primary-color);
    border: none;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.join-btn:hover {
    background: #bee3f8;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.creator-badge {
    background: #fefcbf;
    color: #744210;
    width: 100%;
    text-align: center;
}

/* Состояние "нет групп" */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: #ebf8ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: var(--primary-color);
    font-size: 32px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #2d3748;
    font-size: 20px;
}

.empty-state p {
    margin: 0 0 20px 0;
    color: #718096;
    font-size: 16px;
}

/* Анимации */
@keyframes cardEntrance {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.groups-grid .group-card {
    animation: cardEntrance 0.5s ease forwards;
    opacity: 0;
}

.groups-grid .group-card:nth-child(1) { animation-delay: 0.1s; }
.groups-grid .group-card:nth-child(2) { animation-delay: 0.2s; }
.groups-grid .group-card:nth-child(3) { animation-delay: 0.3s; }
.groups-grid .group-card:nth-child(4) { animation-delay: 0.4s; }
.groups-grid .group-card:nth-child(5) { animation-delay: 0.5s; }
.groups-grid .group-card:nth-child(6) { animation-delay: 0.6s; }

/* Адаптивность */
@media (max-width: 768px) {
    .groups-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .groups-grid {
        grid-template-columns: 1fr;
    }
    
    .create-group-container {
        max-width: 90%;
    }
}
</style>

<script>
// Показать/скрыть форму создания группы
document.getElementById('showCreateForm')?.addEventListener('click', function() {
    document.getElementById('createGroupForm').style.display = 'flex';
});

document.getElementById('showCreateFormAlt')?.addEventListener('click', function() {
    document.getElementById('createGroupForm').style.display = 'flex';
});

document.getElementById('hideCreateForm')?.addEventListener('click', function() {
    document.getElementById('createGroupForm').style.display = 'none';
});

document.getElementById('hideCreateFormAlt')?.addEventListener('click', function() {
    document.getElementById('createGroupForm').style.display = 'none';
});

// Предпросмотр аватарки
document.getElementById('avatarUpload')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.querySelector('#avatarPreview img').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Обработка кнопки "Присоединиться"
document.querySelectorAll('.join-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const groupId = this.getAttribute('data-group-id');
        joinGroup(groupId);
    });
});

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
</script>

<?php require_once 'includes/footer.php'; ?>