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
            $upload_dir = __DIR__ . '/../htdocs/assets/images/groups/';
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
    .groups-list-container{
        margin-bottom: 100px;
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
  --tg-dark-overlay: rgba(0, 0, 0, 0.7);
}

/* Применение темной темы */
@media (prefers-color-scheme: dark) {
  .groups-page {
    background-color: var(--tg-dark-bg);
    color: var(--tg-dark-text);
  }

  .groups-header {
    border-bottom-color: var(--tg-dark-border);
  }

  .groups-title {
    color: var(--tg-dark-text);
  }

  .create-group-btn {
    background: var(--tg-dark-primary);
    box-shadow: 0 4px 12px rgba(46, 166, 255, 0.25);
  }

  .create-group-btn:hover {
    background: #008be6;
    box-shadow: 0 6px 16px rgba(46, 166, 255, 0.35);
  }

  .create-group-container {
    background: var(--tg-dark-card);
    border: 1px solid var(--tg-dark-border);
  }

  .create-group-header {
    background: var(--tg-dark-primary);
    border-bottom: 1px solid var(--tg-dark-border);
  }

  .form-group label {
    color: var(--tg-dark-text);
  }

  .form-group input,
  .form-group textarea {
    background: var(--tg-dark-input);
    border-color: var(--tg-dark-border);
    color: var(--tg-dark-text);
  }

  .form-group input:focus,
  .form-group textarea:focus {
    border-color: var(--tg-dark-primary);
    box-shadow: 0 0 0 3px rgba(46, 166, 255, 0.2);
  }

  .cancel-btn {
    background: var(--tg-dark-input);
    color: var(--tg-dark-text);
  }

  .cancel-btn:hover {
    background: var(--tg-dark-hover);
  }

  .group-card {
    background: var(--tg-dark-card);
    border: 1px solid var(--tg-dark-border);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  }

  .group-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
  }

  .group-name {
    color: var(--tg-dark-text);
  }

  .group-description {
    color: var(--tg-dark-text-secondary);
  }

  .group-meta {
    color: var(--tg-dark-text-secondary);
  }

  .join-btn {
    background: rgba(46, 166, 255, 0.15);
    color: var(--tg-dark-primary);
  }

  .join-btn:hover {
    background: rgba(46, 166, 255, 0.25);
  }

  .creator-badge {
    background: rgba(245, 166, 35, 0.15);
    color: #f5a623;
  }

  .empty-state {
    background: var(--tg-dark-card);
    border: 1px solid var(--tg-dark-border);
  }

  .empty-state h3 {
    color: var(--tg-dark-text);
  }

  .empty-state p {
    color: var(--tg-dark-text-secondary);
  }

  .empty-state-icon {
    background: rgba(46, 166, 255, 0.15);
    color: var(--tg-dark-primary);
  }

  .avatar-preview {
    border-color: var(--tg-dark-border);
  }
}

/* Общие улучшения стиля Telegram */
.groups-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

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
  font-weight: 600;
}

.groups-title i {
  color: var(--tg-dark-primary, #2ea6ff);
}

.create-group-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--tg-dark-primary, #2ea6ff);
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 14px;
}

.create-group-btn:hover {
  transform: translateY(-2px);
}

.create-group-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--tg-dark-overlay, rgba(0, 0, 0, 0.7));
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(10px);
  animation: fadeIn 0.3s ease;
}

.create-group-container {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 450px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.create-group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  background: var(--tg-dark-primary, #2ea6ff);
  color: white;
}

.create-group-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
  opacity: 0.8;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  opacity: 1;
}

.create-group-form {
  padding: 24px;
}

.avatar-upload {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 16px;
  position: relative;
  overflow: hidden;
  border: 2px solid #e2e8f0;
}

.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.edit-avatar-btn {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: var(--tg-dark-primary, #2ea6ff);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
}

.edit-avatar-btn:hover {
  transform: scale(1.1);
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--tg-dark-text, #2d3748);
  font-size: 14px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 15px;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.submit-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: var(--tg-dark-primary, #2ea6ff);
  color: white;
  border: none;
  padding: 14px;
  border-radius: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.submit-btn:hover {
  transform: translateY(-1px);
}

.cancel-btn {
  background: #f1f5f9;
  color: #64748b;
  border: none;
  padding: 14px 20px;
  border-radius: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 14px;
}

.cancel-btn:hover {
  background: #e2e8f0;
}

.groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.group-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  height: 100%;
  border: 1px solid transparent;
}

.group-card:hover {
  transform: translateY(-4px);
}

.group-cover {
  height: 120px;
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
  padding: 20px;
  flex: 1;
}

.group-name {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  line-height: 1.3;
}

.group-description {
  margin: 0 0 16px 0;
  color: #64748b;
  font-size: 14px;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.group-meta {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #64748b;
  margin-top: auto;
}

.group-meta span {
  display: flex;
  align-items: center;
  gap: 6px;
}

.group-meta i {
  font-size: 12px;
  opacity: 0.7;
}

.group-actions {
  padding: 0 20px 20px;
  margin-top: auto;
}

.join-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #f0f9ff;
  color: var(--tg-dark-primary, #2ea6ff);
  border: none;
  padding: 10px;
  border-radius: 10px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 13px;
}

.join-btn:hover {
  transform: translateY(-1px);
}

.badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 500;
  text-align: center;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  margin: 40px 0;
}

.empty-state-icon {
  width: 80px;
  height: 80px;
  background: #f0f9ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: var(--tg-dark-primary, #2ea6ff);
  font-size: 28px;
}

.empty-state h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
  font-weight: 600;
}

.empty-state p {
  margin: 0 0 24px 0;
  color: #64748b;
  font-size: 15px;
}

/* Анимации */
@keyframes cardEntrance {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
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
    gap: 16px;
  }
  
  .groups-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .create-group-container {
    max-width: 90%;
    margin: 20px;
  }
  
  .group-cover {
    height: 100px;
  }
  
  .group-info {
    padding: 16px;
  }
  
  .group-actions {
    padding: 0 16px 16px;
  }
}

@media (max-width: 480px) {
  .groups-page {
    padding: 16px;
  }
  
  .groups-title {
    font-size: 24px;
  }
  
  .create-group-btn {
    padding: 10px 16px;
    font-size: 13px;
  }
  
  .form-actions {
    flex-direction: column;
  }
}
</style>

<script>
// Улучшенный скрипт с анимациями
document.addEventListener('DOMContentLoaded', function() {
  // Показать/скрыть форму создания группы
  const showFormButtons = [
    document.getElementById('showCreateForm'),
    document.getElementById('showCreateFormAlt')
  ].filter(Boolean);
  
  const hideFormButtons = [
    document.getElementById('hideCreateForm'),
    document.getElementById('hideCreateFormAlt')
  ].filter(Boolean);
  
  const form = document.getElementById('createGroupForm');
  
  showFormButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      form.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    });
  });
  
  hideFormButtons.forEach(btn => {
    btn.addEventListener('click', function() {
      form.style.animation = 'fadeOut 0.3s ease forwards';
      setTimeout(() => {
        form.style.display = 'none';
        form.style.animation = '';
        document.body.style.overflow = '';
      }, 300);
    });
  });
  
  // Предпросмотр аватарки
  const avatarUpload = document.getElementById('avatarUpload');
  if (avatarUpload) {
    avatarUpload.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
          const img = document.querySelector('#avatarPreview img');
          img.src = event.target.result;
          img.style.transition = 'opacity 0.3s ease';
          img.style.opacity = '0';
          setTimeout(() => img.style.opacity = '1', 50);
        };
        reader.readAsDataURL(file);
      }
    });
  }
  
  // Обработка кнопки "Присоединиться" с анимацией
  document.querySelectorAll('.join-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const groupId = this.getAttribute('data-group-id');
      const originalText = this.innerHTML;
      
      // Анимация загрузки
      this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Загрузка...</span>';
      this.style.opacity = '0.7';
      this.disabled = true;
      
      setTimeout(() => {
        joinGroup(groupId, this, originalText);
      }, 800);
    });
  });
  
  function joinGroup(groupId, button, originalText) {
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
        button.innerHTML = '<i class="fas fa-check"></i><span>Присоединились!</span>';
        button.style.background = 'rgba(52, 199, 89, 0.15)';
        button.style.color = '#34c759';
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        button.innerHTML = originalText;
        button.style.opacity = '1';
        button.disabled = false;
        alert('Ошибка: ' + (data.message || 'Не удалось присоединиться'));
      }
    })
    .catch(error => {
      button.innerHTML = originalText;
      button.style.opacity = '1';
      button.disabled = false;
      alert('Ошибка сети');
    });
  }
});
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
    --tg-radius: 16px;
    --tg-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    --tg-card-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.games-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 16px;
}

.games-container h1 {
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.8rem;
}

.game-section {
    margin-bottom: 32px;
}

.game-section h2 {
    margin-bottom: 20px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.3rem;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--tg-border);
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.game-card {
    background: var(--tg-bg);
    border-radius: var(--tg-radius);
    padding: 24px;
    transition: all 0.3s ease;
    border: 1px solid var(--tg-border);
    box-shadow: var(--tg-shadow);
    position: relative;
    overflow: hidden;
}

.game-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--tg-card-shadow);
    border-color: var(--tg-primary);
}

.game-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--tg-primary);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.game-card:hover::before {
    opacity: 1;
}

.game-card-blue { border-left: 4px solid #3498db; }
.game-card-green { border-left: 4px solid #2ecc71; }
.game-card-red { border-left: 4px solid #e74c3c; }
.game-card-purple { border-left: 4px solid #9b59b6; }

.game-icon {
    font-size: 2.5rem;
    margin-bottom: 16px;
    color: var(--tg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--tg-accent);
}

.game-card-blue .game-icon { color: #3498db; background: rgba(52, 152, 219, 0.1); }
.game-card-green .game-icon { color: #2ecc71; background: rgba(46, 204, 113, 0.1); }
.game-card-red .game-icon { color: #e74c3c; background: rgba(231, 76, 60, 0.1); }
.game-card-purple .game-icon { color: #9b59b6; background: rgba(155, 89, 182, 0.1); }

.game-info h3 {
    margin: 0 0 12px 0;
    color: var(--tg-text-primary);
    font-size: 1.2rem;
    font-weight: 600;
}

.game-info p {
    color: var(--tg-text-secondary);
    margin-bottom: 20px;
    font-size: 0.95rem;
    line-height: 1.5;
}

.btn-play {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--tg-primary);
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-play:hover {
    background: #0066a4;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
}

/* Модальное окно в стиле Telegram */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.game-modal-content {
    background-color: var(--tg-bg);
    margin: 5% auto;
    padding: 0;
    border: none;
    width: 90%;
    max-width: 800px;
    border-radius: var(--tg-radius);
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.close-modal {
    position: absolute;
    right: 16px;
    top: 16px;
    width: 32px;
    height: 32px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--tg-text-secondary);
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
    transition: all 0.2s ease;
    border: none;
}

.close-modal:hover {
    background: rgba(0, 0, 0, 0.2);
    color: var(--tg-text-primary);
}

.game-frame-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    height: 0;
    overflow: hidden;
    background: #000;
}

#gameFrame {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
    background: #000;
}

/* Заголовок модального окна */
.modal-header {
    padding: 20px;
    background: var(--tg-surface);
    border-bottom: 1px solid var(--tg-border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-header h3 {
    margin: 0;
    color: var(--tg-text-primary);
    font-weight: 600;
}

/* Анимации */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.game-card {
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.game-card:nth-child(1) { animation-delay: 0.05s; }
.game-card:nth-child(2) { animation-delay: 0.1s; }
.game-card:nth-child(3) { animation-delay: 0.15s; }
.game-card:nth-child(4) { animation-delay: 0.2s; }
.game-card:nth-child(5) { animation-delay: 0.25s; }
.game-card:nth-child(6) { animation-delay: 0.3s; }

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal.show .game-modal-content {
    animation: modalSlideIn 0.3s ease;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .games-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
}

@media (max-width: 768px) {
    .games-container {
        padding: 0 12px;
    }
    
    .games-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 100px;
    }
    
    .game-card {
        padding: 20px;
    }
    
    .game-modal-content {
        width: 95%;
        margin: 10% auto;
        border-radius: 12px;
    }
    
    .games-container h1 {
        font-size: 1.5rem;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .game-icon {
        font-size: 2rem;
        width: 50px;
        height: 50px;
    }
    
    .btn-play {
        width: 100%;
        justify-content: center;
    }
    
    .modal-header {
        padding: 16px;
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
        --tg-accent: rgba(0, 136, 204, 0.2);
    }
    
    .game-card:hover {
        border-color: var(--tg-primary);
    }
}

/* Эффекты при наведении */
.game-card {
    cursor: pointer;
}

.game-card:active {
    transform: scale(0.98);
}

/* Состояние загрузки */
.game-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.game-card.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid var(--tg-border);
    border-top: 2px solid var(--tg-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Категории приложений */
.app-categories {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.category-btn {
    padding: 8px 16px;
    background: var(--tg-surface);
    border: 1px solid var(--tg-border);
    border-radius: 20px;
    color: var(--tg-text-secondary);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.category-btn.active,
.category-btn:hover {
    background: var(--tg-primary);
    color: white;
    border-color: var(--tg-primary);
}
</style>

<script>
// Открытие игры в модальном окне
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gameModal');
    const gameFrame = document.getElementById('gameFrame');
    const closeModal = document.querySelector('.close-modal');
    
    // Обработчики для кнопок "Играть"
    document.querySelectorAll('.btn-play').forEach(btn => {
        btn.addEventListener('click', function(e) {

            window.location.href = gameUrl;
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        });
    });
    
    // Закрытие модального окна
    closeModal.addEventListener('click', function() {
        closeGameModal();
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeGameModal();
        }
    });
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeGameModal();
        }
    });
    
    function closeGameModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            gameFrame.src = '';
            document.body.style.overflow = 'auto';
        }, 300);
    }
    
    // Плавная загрузка карточек
    const gameCards = document.querySelectorAll('.game-card');
    gameCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });
});
</script>

<script>
// Открытие игры в модальном окне
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gameModal');
    const gameFrame = document.getElementById('gameFrame');
    const closeModal = document.querySelector('.close-modal');
    
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
        gameFrame.src = '';
        document.body.style.overflow = 'auto';
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            gameFrame.src = '';
            document.body.style.overflow = 'auto';
        }
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