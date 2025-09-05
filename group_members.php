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
  .group-members-page {
    background-color: var(--tg-dark-bg);
    color: var(--tg-dark-text);
  }

  .container {
    background: var(--tg-dark-card);
    border: 1px solid var(--tg-dark-border);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  }

  .breadcrumbs a {
    color: var(--tg-dark-primary);
  }

  .breadcrumbs a:hover {
    color: #008be6;
  }

  .breadcrumbs span {
    color: var(--tg-dark-text-secondary);
  }

  .page-header h1 {
    color: var(--tg-dark-text);
  }

  .members-count {
    background: var(--tg-dark-input);
    color: var(--tg-dark-text-secondary);
  }

  .search-box input {
    background: var(--tg-dark-input);
    color: var(--tg-dark-text);
    border-color: var(--tg-dark-border);
  }

  .search-box input:focus {
    border-color: var(--tg-dark-primary);
    box-shadow: 0 0 0 2px rgba(46, 166, 255, 0.2);
  }

  .search-box i {
    color: var(--tg-dark-text-secondary);
  }

  .sort-options select {
    background: var(--tg-dark-input);
    color: var(--tg-dark-text);
    border-color: var(--tg-dark-border);
  }

  .sort-options select:focus {
    border-color: var(--tg-dark-primary);
  }

  .member-card {
    background: var(--tg-dark-card);
    border: 1px solid var(--tg-dark-border);
  }

  .member-card:hover {
    background: var(--tg-dark-hover);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
    border-color: var(--tg-dark-border);
  }

  .member-avatar img {
    border: 2px solid var(--tg-dark-card);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
  }

  .member-name {
    color: var(--tg-dark-text);
  }

  .member-name:hover {
    color: var(--tg-dark-primary);
  }

  .member-meta {
    color: var(--tg-dark-text-secondary);
  }

  .member-role {
    background: var(--tg-dark-input);
    color: var(--tg-dark-text-secondary);
  }

  .btn-message {
    background: rgba(46, 166, 255, 0.15);
    color: var(--tg-dark-primary);
  }

  .btn-message:hover {
    background: rgba(46, 166, 255, 0.25);
  }

  .btn-remove {
    background: rgba(240, 40, 73, 0.15);
    color: #f02849;
  }

  .btn-remove:hover {
    background: rgba(240, 40, 73, 0.25);
  }

  .empty-members {
    background: var(--tg-dark-card);
  }

  .empty-members h3 {
    color: var(--tg-dark-text);
  }

  .empty-members p {
    color: var(--tg-dark-text-secondary);
  }

  .btn-invite {
    background: var(--tg-dark-primary);
    color: white;
  }

  .btn-invite:hover {
    background: #008be6;
  }

  /* Улучшения для темной темы */
  .member-avatar img {
    filter: brightness(0.9);
    transition: filter 0.3s ease;
  }

  .member-card:hover .member-avatar img {
    filter: brightness(1.05);
  }

  .online-badge {
    border-color: var(--tg-dark-card);
  }

  .creator-badge {
    color: #f7b500;
  }
}

/* Плавные переходы для темной темы */
.container,
.member-card,
.search-box input,
.sort-options select,
.btn {
  transition: all 0.3s ease;
}

/* Улучшенные стили для обоих тем */
.members-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
  margin-top: 24px;
}

.member-card {
  padding: 20px;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.member-avatar {
  width: 64px;
  height: 64px;
}

.member-avatar img {
  transition: transform 0.3s ease;
}

.member-card:hover .member-avatar img {
  transform: scale(1.05);
}

.member-info {
  flex: 1;
  min-width: 0;
}

.member-name {
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.creator-badge {
  font-size: 0.9rem;
}

.member-actions {
  display: flex;
  gap: 8px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}

.btn:hover {
  transform: translateY(-1px);
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 280px;
  max-width: 400px;
}

.search-box input {
  width: 100%;
  padding: 12px 16px 12px 44px;
  border-radius: 12px;
  font-size: 0.95rem;
  outline: none;
  transition: all 0.3s ease;
}

.search-box i {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1;
}

/* Анимации */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.member-card {
  animation: fadeIn 0.4s ease forwards;
  opacity: 0;
}

.member-card:nth-child(1) { animation-delay: 0.1s; }
.member-card:nth-child(2) { animation-delay: 0.2s; }
.member-card:nth-child(3) { animation-delay: 0.3s; }
.member-card:nth-child(4) { animation-delay: 0.4s; }
.member-card:nth-child(5) { animation-delay: 0.5s; }

/* Адаптивность для темной темы */
@media (max-width: 768px) {
  .group-members-page {
    padding: 16px;
  }
  
  .container {
    padding: 20px;
    border-radius: 16px;
  }
  
  .members-list {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .member-card {
    padding: 16px;
  }
  
  .member-avatar {
    width: 56px;
    height: 56px;
  }
  
  .member-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  .search-box {
    min-width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
  
  .page-header h1 {
    font-size: 1.5rem;
  }
  
  .breadcrumbs {
    font-size: 0.8rem;
  }
  
  .member-card {
    flex-direction: column;
    text-align: center;
  }
  
  .member-info {
    text-align: center;
  }
  
  .member-name {
    justify-content: center;
  }
}
</style>

<script>
// Улучшенный скрипт с анимациями для темной темы
document.addEventListener('DOMContentLoaded', function() {
  // Поиск участников с debounce
  let searchTimeout;
  const searchInput = document.getElementById('members-search');
  
  if (searchInput) {
    searchInput.addEventListener('input', function(e) {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        const searchTerm = e.target.value.toLowerCase().trim();
        const members = document.querySelectorAll('.member-card');
        let visibleCount = 0;
        
        members.forEach(member => {
          const name = member.querySelector('.member-name').textContent.toLowerCase();
          const role = member.querySelector('.member-role').textContent.toLowerCase();
          
          if (name.includes(searchTerm) || role.includes(searchTerm)) {
            member.style.display = 'flex';
            member.style.animation = 'fadeIn 0.3s ease forwards';
            visibleCount++;
          } else {
            member.style.display = 'none';
          }
        });
        
        // Показываем сообщение, если ничего не найдено
        const emptyState = document.querySelector('.empty-members');
        if (emptyState) {
          if (visibleCount === 0 && searchTerm) {
            emptyState.style.display = 'block';
            emptyState.innerHTML = `
              <i class="fas fa-search" style="font-size: 3rem; color: var(--tg-dark-text-secondary, #65676b); margin-bottom: 16px;"></i>
              <h3>Участники не найдены</h3>
              <p>Попробуйте изменить поисковый запрос</p>
            `;
          } else {
            emptyState.style.display = 'none';
          }
        }
      }, 300);
    });
  }
  
  // Улучшенная функция удаления участника
  window.removeMember = function(groupId, userId) {
    if (confirm('Вы уверены, что хотите удалить этого участника из группы?')) {
      const button = event.target.closest('.btn-remove');
      const originalText = button.innerHTML;
      
      // Анимация загрузки
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Удаление...';
      button.disabled = true;
      
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
          // Анимация успеха
          button.innerHTML = '<i class="fas fa-check"></i> Удалено!';
          button.style.background = 'rgba(16, 185, 129, 0.15)';
          button.style.color = '#10b981';
          
          // Удаляем карточку с анимацией
          const card = button.closest('.member-card');
          card.style.animation = 'fadeOut 0.3s ease forwards';
          
          setTimeout(() => {
            card.remove();
            
            // Обновляем счетчик участников
            const countElement = document.querySelector('.members-count');
            if (countElement) {
              const currentCount = parseInt(countElement.textContent);
              countElement.textContent = `${currentCount - 1} участников`;
            }
          }, 300);
        } else {
          button.innerHTML = originalText;
          button.disabled = false;
          alert(data.error || 'Ошибка при удалении участника');
        }
      })
      .catch(error => {
        button.innerHTML = originalText;
        button.disabled = false;
        alert('Произошла ошибка сети');
      });
    }
  };
  
  // Анимация для fadeOut
  const style = document.createElement('style');
  style.textContent = `
    @keyframes fadeOut {
      from {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
      to {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
      }
    }
  `;
  document.head.appendChild(style);
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
    body{
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