<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$posts = getPosts($db, $limit, $offset);
$friends = $user ? getFriends($db, $user['id']) : [];
$groups = $user ? getGroups($db, $user['id']) : getGroups($db);
$online_users = getOnlineUsers($db);

require_once 'includes/header.php'
?>
<div class="tg-app">
<div class="page-container">
    <!-- Left Sidebar -->
    <aside class="left-sidebar">
        <?php if ($user): ?>
            <div class="sidebar-card">
                <div class="user-info">
                    <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" class="user-avatar">
                    <div class="user-details">
                        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-bio"><?= htmlspecialchars($user['bio']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-bars"></i> Меню</h3>
                <ul class="sidebar-menu">
                    <li>
                        <a href="/profile.php?id=<?= $user['id'] ?>">
                            <i class="fas fa-user"></i>
                            <span>Моя страница</span>
                        </a>
                    </li>
                    <li>
                        <a href="/friends.php">
                            <i class="fas fa-users"></i>
                            <span>Друзья</span>
                        </a>
                    </li>
                    <li>
                        <a href="/messages.php">
                            <i class="fas fa-comments"></i>
                            <span>Сообщения</span>
                        </a>
                    </li>
                    <li>
                    <a href="/notifications.php" class="notification-link">
                        <i class="fas fa-bell"></i>
                        <span>Уведомления</span>
                        <?php if (hasUnreadNotifications($db, $user['id'])): ?>
                            <span class="notification-badge">!</span>
                        <?php endif; ?>
                    </a>
                    </li>
                    <li>
                        <a href="/photos.php">
                            <i class="fas fa-images"></i>
                            <span>Фотографии</span>
                        </a>
                    </li>
                    <li>
                        <a href="/music.php">
                            <i class="fas fa-music"></i>
                            <span>Музыка</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="sidebar-card">
            <h3 class="sidebar-title"><i class="fas fa-users"></i> Группы</h3>
            <div class="shortcut-list">
                <?php foreach ($groups as $group): ?>
                    <a href="/group.php?id=<?= $group['id'] ?>" class="shortcut-item">
                        <img src="assets/images/groups/<?= $group['avatar'] ?>" alt="<?= htmlspecialchars($group['name']) ?>" class="group-avatar">
                        <span class="group-name"><?= htmlspecialchars($group['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <!-- Main Feed -->
    <main class="main-content">
        <?php if ($user): ?>
            <!-- Create Post -->
            <div class="create-post">
                <form id="create-post-form" method="POST" action="/actions/create_post.php" enctype="multipart/form-data">
                    <div class="post-input">
                        <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" class="post-author-avatar">
                        <input type="text" name="content" placeholder="Что у вас нового, <?= explode(' ', $user['full_name'])[0] ?>?" class="post-input-field" required>
                    </div>




                    <div class="poll-creation" id="poll-creation" style="display: none;">
                        <input type="hidden" name="has_poll" value="0">
                        <div class="form-group">
                            <input type="text" name="poll_question" class="form-control" placeholder="Вопрос опроса">
                        </div>
                        
                        <div id="poll-options-container">
                            <div class="poll-option">
                                <input type="text" name="poll_options[]" class="form-control" placeholder="Вариант ответа 1">
                            </div>
                            <div class="poll-option">
                                <input type="text" name="poll_options[]" class="form-control" placeholder="Вариант ответа 2">
                            </div>
                        </div>
                        
                        <button type="button" id="add-poll-option" class="btn">+ Добавить вариант</button>
                        
                        <div class="poll-settings" style='display:none;'>
                            <label>
                                <input type="checkbox" name="poll_multiple"> Разрешить несколько вариантов
                            </label>
                            <label>
                                <input type="checkbox" id="poll-has-deadline" name="poll_has_deadline">
                                <span>Установить срок завершения</span>
                                <input type="datetime-local" name="poll_deadline" style="display: none;">
                            </label>
                        </div>
                    </div>




                    <!-- В блоке create-post замените кнопку feeling-action на это: -->
                    <div class="post-actions">
                    <button type="submit" class="post-submit">
                        <i class="fas fa-paper-plane"></i>
                        <span class="submit-text">Опубликовать</span>
                    </button>

                        <label class="post-action photo-action">
                            <i class="fas fa-images"></i>
                            <span class="action-text">Фото/Видео</span>
                            <input type="file" name="image" accept="image/*" class="file-input">
                        </label>
                        <div class="feeling-container">
                            <button type="button" class="post-action feeling-action" id="feeling-btn">
                                <i class="fas fa-smile"></i>
                                <span class="action-text">Чувства</span>
                            </button>
                            <div class="feeling-dropdown" id="feeling-dropdown">
                                <div class="feeling-options">
                                    <button type="button" class="feeling-option" data-feeling="happy">
                                        <i class="fas fa-smile-beam"></i> Счастлив
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="sad">
                                        <i class="fas fa-sad-tear"></i> Грустный
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="angry">
                                        <i class="fas fa-angry"></i> Злой
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="loved">
                                        <i class="fas fa-heart"></i> Влюблён
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="tired">
                                        <i class="fas fa-tired"></i> Уставший
                                    </button>
                                    <button type="button" class="feeling-option" data-feeling="blessed">
                                        <i class="fas fa-pray"></i> Благословлён
                                    </button>
                                </div>
                            </div>
                            <input type="hidden" name="feeling" id="feeling-input">
                        </div>
                        <button type="button" id="toggle-poll" class="post-action">
                            <i class="fas fa-poll"></i> Опрос
                        </button>

                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- Post Feed -->
        <div class="posts-feed">
            <?php foreach ($posts as $post): ?>
                <div class="post-card <?= $post['feeling'] ? ' ' . htmlspecialchars($post['feeling']) : '' ?>" id="post-<?= $post['id'] ?>">
                    <div class="post-header">
                        <a href="/profile.php?id=<?= $post['user_id'] ?>" class="post-author">
                            <img src="assets/images/avatars/<?= htmlspecialchars($post['avatar']) ?>" alt="User" class="author-avatar">
                            <div class="author-details">
                                <span class="author-name"><?= htmlspecialchars($post['full_name']) ?></span>
                                <span class="post-time"><?= time_elapsed_string($post['created_at']) ?></span>
                            </div>
                        </a>
                        <button class="post-options"><i class="fas fa-ellipsis-h"></i></button>
                    </div>
                    
                    <div class="post-content">
                        <p class="post-text"><?= processMentions(nl2br(htmlspecialchars($post['content'])), $db) ?></p>
                        <?php if ($post['feeling']):?>
                            <div class="post-feeling">
                                <?php 
                                    $feeling_icons = [
                                        'happy' => 'fa-smile-beam',
                                        'sad' => 'fa-sad-tear',
                                        'angry' => 'fa-angry',
                                        'loved' => 'fa-heart',
                                        'tired' => 'fa-tired',
                                        'blessed' => 'fa-pray'
                                    ];
                                    $feeling_texts = [
                                        'happy' => 'чувствует себя счастливым',
                                        'sad' => 'чувствует себя грустным',
                                        'angry' => 'чувствует себя злым',
                                        'loved' => 'чувствует себя влюблённым',
                                        'tired' => 'чувствует себя уставшим',
                                        'blessed' => 'чувствует себя благословлённым'
                                    ];
                                ?>
                                <i class="fas <?= $feeling_icons[$post['feeling']] ?>"></i>
                                <span><?= $feeling_texts[$post['feeling']] ?></span>
                            </div>
                        <?php endif; ?>



                        <?php if (isset($post['poll'])): ?>
                            
                            <div class="poll-container" style="margin-top: 15px; border: 1px solid #ddd; border-radius: 8px; padding: 15px;">
                                <h4 style="margin-top: 0; margin-bottom: 15px;"><?= htmlspecialchars($post['poll']['question']) ?></h4>
                                
                                <?php if ($post['poll']['ends_at'] && strtotime($post['poll']['ends_at']) > time()): ?>
                                    <div class="poll-deadline" style="font-size: 0.8em; color: #666; margin-bottom: 10px;">
                                        Опрос активен до: <?= date('d.m.Y H:i', strtotime($post['poll']['ends_at'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="poll-options">
                                    <?php foreach ($post['poll']['options'] as $option): ?>
                                        <div class="poll-option" style="margin-bottom: 10px;">
                                        <?php if (isset($user) && (hasUserVoted($db, $post['poll']['id'], $user['id']) || 
                                                ($post['poll']['ends_at'] && strtotime($post['poll']['ends_at']) < time()))): ?>
                                            <?php 
                                                $percentage = round($option['votes'] / max(1, $post['poll']['total_votes']) * 100);
                                            ?>
                                            <div class="poll-option-result" style="--progress-width: <?= $percentage ?>%;">
                                                <div class="poll-option-text"><?= htmlspecialchars($option['option_text']) ?></div>
                                                <div class="poll-option-votes"><?= $option['votes'] ?> (<?= $percentage ?>%)</div>
                                            </div>

                                        <?php else: ?>
                                            <label class="poll-option-vote">
                                                <input type="<?= $post['poll']['is_multiple'] ? 'checkbox' : 'radio' ?>" 
                                                    name="poll_option_<?= $post['poll']['id'] ?>" 
                                                    value="<?= $option['id'] ?>">
                                                <span><?= htmlspecialchars($option['option_text']) ?></span>
                                            </label>
                                        <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="poll-total" style="font-size: 0.8em; color: #666; margin-top: 10px;">
                                    Всего голосов: <?= $post['poll']['total_votes'] ?>
                                </div>
                                
                                <?php if (isset($user) && (!hasUserVoted($db, $post['poll']['id'], $user['id']) && 
                                        (!$post['poll']['ends_at'] || strtotime($post['poll']['ends_at']) > time()))): ?>
                                    <button class="vote-btn" data-poll-id="<?= $post['poll']['id'] ?>" 
                                            style="margin-top: 10px; padding: 5px 15px; background: var(--primary-color); 
                                                color: white; border: none; border-radius: 4px; cursor: pointer;">
                                        Голосовать
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>




                        <?php if ($post['image']): ?>
                            <img src="/assets/images/posts/<?= $post['image'] ?>" alt="Post Image" class="post-image">
                        <?php endif; ?>
                    </div>
                    
                    <div class="post-stats">
                        <div class="likes">
                            <i class="fas fa-thumbs-up"></i> <?= $post['likes_count'] ?>
                        </div>
                        <div class="comments">
                            <?= $post['comments_count'] ?> комментариев
                        </div>
                    </div>
                    
                    <div class="post-actions">
                        <div class="post-action-btn like-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-thumbs-up"></i>
                            <span>Нравится</span>
                        </div>
                        <div class="post-action-btn comment-btn" data-post-id="<?= $post['id'] ?>">
                            <i class="far fa-comment"></i>
                            <span>Комментировать</span>
                        </div>
                        <div class="post-action-btn">
                            <i class="fas fa-share"></i>
                            <span>Поделиться</span>
                        </div>
                    </div>
                    
                    <!-- Комментарии -->
                    <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <?php if ($user): ?>
                            <div class="add-comment">
                                <img src="assets/images/avatars/<?= $user['avatar'] ?>" class="comment-author-avatar">
                                <form class="comment-form" data-post-id="<?= $post['id'] ?>">
                                    <input type="text" name="comment" placeholder="Написать комментарий..." required>
                                    <button type="submit" style="display:none"></button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="comments-list" id="comments-list-<?= $post['id'] ?>">
                            <!-- Комментарии будут загружены по запросу -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Right Sidebar -->
    <aside class="right-sidebar">
        <div class="sidebar-card friends-card">
            <div class="friends-header">
                <h3 class="sidebar-title"><i class="fas fa-user-friends"></i> Друзья онлайн</h3>
                <div class="friend-actions">
                    <a href="/friends.php" title="Все друзья"><i class="fas fa-ellipsis-h"></i></a>
                </div>
            </div>
            
            <ul class="friends-list">
                <?php foreach (array_slice($friends, 0, 8) as $friend): ?>
                    <li class="friend-item">
                        <a href="/profile.php?id=<?= $friend['id'] ?>" class="friend-link">
                            <div class="friend-avatar-container">
                                <img src="/assets/images/avatars/<?= htmlspecialchars($friend['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($friend['full_name']) ?>"
                                     class="friend-avatar">
                                <?php if (isUserOnline($friend['id'])): ?>
                                    <div class="online-badge"></div>
                                <?php endif; ?>
                            </div>
                            <div class="friend-info">
                                <span class="friend-name"><?= htmlspecialchars(explode(' ', $friend['full_name'])[0]) ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (count($friends) > 8): ?>
                <a href="/friends.php" class="view-all-link">
                    Показать всех друзей (<?= count($friends) ?>)
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Группы -->
        <div class="sidebar-card groups-card">
            <h3 class="sidebar-title"><i class="fas fa-users"></i> Ваши группы</h3>
            <ul class="groups-list">
                <?php foreach (array_slice($groups, 0, 3) as $group): ?>
                    <li class="group-item">
                        <a href="/group.php?id=<?= $group['id'] ?>" class="group-link">
                            <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($group['name']) ?>"
                                 class="group-avatar">
                            <span class="group-name"><?= htmlspecialchars($group['name']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="/groups.php" class="view-all-link">
                Все группы
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </aside>
</div>
</div>




<script>
// Simple JavaScript for feeling dropdown
document.addEventListener('DOMContentLoaded', function() {
    const feelingBtn = document.getElementById('feeling-btn');
    const feelingDropdown = document.getElementById('feeling-dropdown');
    
    if (feelingBtn && feelingDropdown) {
        feelingBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            feelingDropdown.classList.toggle('show');
            
            // Mobile detection
            if (window.innerWidth <= 768) {
                feelingDropdown.classList.add('mobile-bottom');
            } else {
                feelingDropdown.classList.remove('mobile-bottom');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!feelingBtn.contains(e.target) && !feelingDropdown.contains(e.target)) {
                feelingDropdown.classList.remove('show');
            }
        });
        
        // Feeling option selection
        document.querySelectorAll('.feeling-option').forEach(option => {
            option.addEventListener('click', function() {
                const feeling = this.getAttribute('data-feeling');
                document.getElementById('feeling-input').value = feeling;
                feelingDropdown.classList.remove('show');
            });
        });
    }
});
</script>
<!-- Вставьте этот код перед закрывающим тегом </body> или перед подключением footer.php -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Обрабатываем все блоки с постами
    document.querySelectorAll('.post-text').forEach(function(postText) {
        // Заменяем @username на кликабельные ссылки (только для отображения)
        postText.innerHTML = postText.innerHTML.replace(
            /@([a-zA-Z0-9_]+)/g, 
            '<a href="/profile.php?username=$1" class="mention">@$1</a>'
        );
    });
});
// Обработка выбора чувств
const feelingBtn = document.getElementById('feeling-btn');
const feelingDropdown = document.getElementById('feeling-dropdown');
const feelingInput = document.getElementById('feeling-input');

feelingBtn.addEventListener('click', function() {
    feelingDropdown.classList.toggle('show');
});

// Закрываем dropdown при клике вне его
document.addEventListener('click', function(e) {
    if (!feelingBtn.contains(e.target) && !feelingDropdown.contains(e.target)) {
        feelingDropdown.classList.remove('show');
    }
});

// Обработка выбора чувства
document.querySelectorAll('.feeling-option').forEach(option => {
    option.addEventListener('click', function() {
        const feeling = this.getAttribute('data-feeling');
        const iconClass = this.querySelector('i').className;
        const text = this.textContent.trim();
        
        feelingInput.value = feeling;
        
        // Обновляем кнопку
        feelingBtn.innerHTML = `
            <i class="${iconClass}"></i>
            <span class="action-text">${text}</span>
        `;
        
        feelingDropdown.classList.remove('show');
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let isLoading = false;
    let currentPage = 1;
    const postsFeed = document.querySelector('.posts-feed');
    
    window.addEventListener('scroll', function() {
        // Проверяем, достигли ли мы нижней части страницы
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500 && !isLoading) {
            loadMorePosts();
        }
    });
    
    function loadMorePosts() {
        isLoading = true;
        currentPage++;
        
        // Показываем индикатор загрузки
        const loader = document.createElement('div');
        loader.className = 'loader';
        loader.innerHTML = '<div class="spinner"></div>';
        postsFeed.appendChild(loader);
        
        // Загружаем новые посты через AJAX
        fetch(`/actions/load_posts.php?page=${currentPage}`)
            .then(response => response.text())
            .then(html => {
                loader.remove();
                
                if (html.trim() !== '') {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    // Добавляем новые посты в ленту
                    const newPosts = tempDiv.querySelectorAll('.post-card');
                    newPosts.forEach(post => {
                        postsFeed.appendChild(post);
                    });
                    
                    // Инициализируем обработчики для новых постов
                    initPostHandlers();
                }
                
                isLoading = false;
            })
            .catch(error => {
                console.error('Error loading more posts:', error);
                loader.remove();
                isLoading = false;
            });
    }
    
    function initPostHandlers() {
        // Инициализация обработчиков событий для новых постов
        // (например, для кнопок лайков, комментариев и т.д.)
    }
    
    // Инициализация обработчиков при первой загрузке
    initPostHandlers();
});







document.getElementById('toggle-poll').addEventListener('click', function() {
    const pollCreation = document.getElementById('poll-creation');
    const hasPollInput = pollCreation.querySelector('input[name="has_poll"]');
    
    if (pollCreation.style.display === 'none') {
        pollCreation.style.display = 'block';
        hasPollInput.value = '1';
        this.classList.add('active');
    } else {
        pollCreation.style.display = 'none';
        hasPollInput.value = '0';
        this.classList.remove('active');
    }
});

document.getElementById('add-poll-option').addEventListener('click', function() {
    const optionsContainer = document.getElementById('poll-options-container');
    const optionCount = optionsContainer.querySelectorAll('.poll-option').length + 1;
    
    const optionDiv = document.createElement('div');
    optionDiv.className = 'poll-option';
    optionDiv.innerHTML = `
        <input type="text" name="poll_options[]" class="form-control" placeholder="Вариант ответа ${optionCount}">
        <button type="button" class="remove-option-btn"><i class="fas fa-times"></i></button>
    `;
    
    optionsContainer.appendChild(optionDiv);
    
    // Обработчик для кнопки удаления
    optionDiv.querySelector('.remove-option-btn').addEventListener('click', function() {
        if (optionsContainer.querySelectorAll('.poll-option').length > 2) {
            optionsContainer.removeChild(optionDiv);
        } else {
            alert('Опрос должен содержать минимум 2 варианта');
        }
    });
});
document.getElementById('poll-has-deadline').addEventListener('change', function() {
    const deadlineInput = document.querySelector('input[name="poll_deadline"]');
    deadlineInput.style.display = this.checked ? 'inline-block' : 'none';
    
    if (this.checked) {
        const now = new Date();
        now.setHours(now.getHours() + 1);
        deadlineInput.min = now.toISOString().slice(0, 16);
        deadlineInput.value = now.toISOString().slice(0, 16);
    }
});

// Модифицируем обработчик формы
document.getElementById('create-post-form').addEventListener('submit', function(e) {
    const pollQuestion = document.querySelector('input[name="poll_question"]').value;
    const pollOptions = Array.from(document.querySelectorAll('input[name="poll_options[]"]'))
        .map(input => input.value.trim())
        .filter(text => text !== '');
    
    const hasPoll = document.querySelector('input[name="has_poll"]').value === '1';
    
    if (hasPoll && (!pollQuestion || pollOptions.length < 2)) {
        e.preventDefault();
        alert('Для создания опроса укажите вопрос и минимум 2 варианта ответа');
        return;
    }
});
// Обработчик голосования
document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('vote-btn')) {
        const pollId = e.target.getAttribute('data-poll-id');
        const selectedOptions = document.querySelectorAll(
            `input[name="poll_option_${pollId}"]:checked`
        );
        
        if (selectedOptions.length === 0) {
            alert('Пожалуйста, выберите вариант ответа');
            return;
        }
        
        const optionIds = Array.from(selectedOptions).map(opt => parseInt(opt.value));
        
        try {
            const response = await fetch('/actions/vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    poll_id: parseInt(pollId),
                    option_ids: optionIds
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Ошибка сервера');
            }
            
            if (data.success) {
                // Перезагружаем страницу для обновления результатов
                location.reload();
            } else {
                alert(data.message || 'Ошибка при голосовании');
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка: ' + error.message);
        }
    }
});
</script>
<script>
    // JS для работы с выпадающим меню "Чувство"
    document.addEventListener('DOMContentLoaded', () => {
        const feelingBtn = document.getElementById('feeling-btn');
        const feelingDropdown = document.querySelector('.feeling-dropdown');
        const feelingOptions = document.querySelectorAll('.feeling-option');

        // Переключаем видимость выпадающего меню при нажатии на кнопку
        feelingBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Предотвращаем закрытие при нажатии
            feelingDropdown.classList.toggle('show');
        });

        // Обрабатываем выбор опции из меню
        feelingOptions.forEach(option => {
            option.addEventListener('click', () => {
                const selectedText = option.textContent.trim();
                const selectedIcon = option.querySelector('i').className;
                
                // Обновляем текст и иконку на кнопке
                feelingBtn.querySelector('i').className = selectedIcon;
                feelingBtn.querySelector('.action-text').textContent = selectedText;

                // Скрываем выпадающее меню
                feelingDropdown.classList.remove('show');
            });
        });

        // Скрываем меню, если пользователь нажимает в любом другом месте страницы
        document.addEventListener('click', (e) => {
            if (!feelingBtn.contains(e.target) && !feelingDropdown.contains(e.target)) {
                feelingDropdown.classList.remove('show');
            }
        });
    });
</script>
<style>
    /* Основные стили и переменные */
    :root {
        --primary-color: #5D93B5;
        --accent-color: #31A24C;
        --background-color: #F0F2F5;
        --card-background: #ffffff;
        --text-color: #1C1E21;
        --text-secondary: #65676B;
        --border-color: #E4E6EB;
        --link-color: #5D93B5;
        --hover-background: #E9ECEF;
        --online-color: #31A24C;
        --shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        --border-radius: 12px;
    }

    /* Общие стили */
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background-color: var(--background-color);
        margin: 0;
        padding: 0;
        color: var(--text-color);
    }

    .tg-app {
        display: flex;
        justify-content: center;
        width: 100%;
        min-height: 100vh;
    }

    .page-container {
        display: flex;
        gap: 20px;
        width: 100%;
        max-width: 1200px;
        padding: 20px;
    }

    .left-sidebar, .right-sidebar {
        flex-shrink: 0;
        width: 280px;
    }

    .main-content {
        flex-grow: 1;
        width: 100%;
    }
    
    .sidebar-card {
        background: var(--card-background);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 20px;
        padding: 20px;
        box-sizing: border-box;
    }

    .sidebar-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--text-color);
    }

    /* Стили для аватара и имени пользователя */
    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-size: 1.2rem;
        font-weight: bold;
    }

    .user-bio {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    /* Меню в боковой панели */
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        margin-bottom: 8px;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px;
        color: var(--text-color);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: background-color 0.2s;
    }

    .sidebar-menu a:hover {
        background-color: var(--hover-background);
    }

    .sidebar-menu .fas {
        width: 20px;
        text-align: center;
        color: var(--text-secondary);
    }
    
    .notification-badge {
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 0.7em;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-left: auto;
    }

    /* Список групп и друзей */
    .shortcut-list, .friends-list, .groups-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .shortcut-item, .friend-link, .group-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        color: var(--text-color);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: background-color 0.2s;
        box-sizing: border-box;
    }

    .shortcut-item:hover, .friend-link:hover, .group-link:hover {
        background-color: var(--hover-background);
    }

    .group-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
    }

    .friend-avatar-container {
        position: relative;
    }

    .friend-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .online-badge {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        background-color: var(--online-color);
        border: 2px solid var(--online-border);
        border-radius: 50%;
    }

    .view-all-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-align: center;
        font-size: 0.9em;
        color: var(--link-color);
        text-decoration: none;
        margin-top: 15px;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .view-all-link:hover {
        color: #4A7A99;
    }

    /* Секция создания поста */
    .create-post {
        background: var(--card-background);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 20px;
        margin-bottom: 20px;
        position: relative;
    }

    .post-input {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .post-author-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
    }

    .post-input-field {
        flex-grow: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 25px;
        background-color: var(--hover-background);
        font-size: 1rem;
        color: var(--text-color);
        transition: background-color 0.2s;
    }

    .post-input-field:focus {
        outline: none;
        background-color: #E4E6EB;
    }

    .post-actions {
        display: flex;
        align-items: center;
        border-top: 1px solid var(--border-color);
        padding-top: 15px;
        margin-top: 15px;
        gap: 10px;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
    }

    /* Скрываем текст "Выбрать файл..." */
    .file-input {
        display: none;
    }

    .post-action {
        background: none;
        border: none;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 20px;
        cursor: pointer;
        color: var(--text-secondary);
        font-weight: 500;
        transition: background-color 0.2s, color 0.2s;
        flex-shrink: 0;
    }

    .post-action:hover {
        background-color: var(--hover-background);
        color: var(--primary-color);
    }
    
    .post-action.active {
        background-color: var(--primary-color);
        color: white;
    }
    
    .post-action.active .fas {
        color: white;
    }

    .post-action .fas {
        color: var(--text-secondary);
    }
    
    .post-submit {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.2s;
        margin-left: auto;
        flex-shrink: 0;
    }

    .post-submit:hover {
        background-color: #4A7A99;
    }

    /* Выпадающее меню "Чувства" */
    .feeling-container {
        position: relative;
    }
    
    .feeling-dropdown {
    position: fixed; /* 👈 Самое важное изменение */
    top: 0px;
    left: 0px;
    transform: translate(-50%, -50%); /* Центрирует меню по экрану */
    
    background: var(--card-background);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 10px;
    z-index: 1000;
    
    opacity: 0;
    visibility: hidden;
    /* Убираем трансформ, так как он задан выше */
    transition: opacity 0.3s ease-out, visibility 0.3s ease-out;
}

.feeling-dropdown.show {
    opacity: 1;
    visibility: visible;
    /* Конечное положение (возвращается на место) */
    transform: translateY(0);
}
    
    .feeling-option {
        width: 100%;
        background: none;
        border: none;
        text-align: left;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        border-radius: 8px;
        transition: background-color 0.2s;
        color: var(--text-color);
        font-size: 0.95rem;
    }
    
    .feeling-option:hover {
        background-color: var(--hover-background);
    }
    
    .feeling-option .fas {
        color: var(--text-secondary);
    }
    
    .feeling-dropdown.mobile-bottom {
        bottom: auto;
        top: 110%;
    }

    /* Лента постов */
    .post-card {
        background: var(--card-background);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 20px;
        margin-bottom: 20px;
        border: 2px solid transparent;
        transition: border-color 0.3s ease-in-out;
    }
    
    /* Стили для обводки постов в зависимости от настроения */
    .post-card.happy { border-color: #ffc107; }
    .post-card.sad { border-color: #5D93B5; }
    .post-card.angry { border-color: #dc3545; }
    .post-card.loved { border-color: #e83e8c; }
    .post-card.tired { border-color: #6c757d; }
    .post-card.blessed { border-color: #28a745; }

    .post-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
    }

    .post-author {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: var(--text-color);
    }

    .author-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
    }

    .author-details {
        display: flex;
        flex-direction: column;
    }

    .author-name {
        font-weight: bold;
        font-size: 1rem;
    }

    .post-time {
        font-size: 0.8em;
        color: var(--text-secondary);
    }

    .post-options {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.1em;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background-color 0.2s;
    }

    .post-options:hover {
        background-color: var(--hover-background);
    }

    .post-content {
        margin-bottom: 15px;
    }

    .post-text {
        font-size: 1rem;
        line-height: 1.5;
        margin-top: 0;
        margin-bottom: 15px;
        word-wrap: break-word;
    }
    
    .mention {
        color: var(--link-color);
        text-decoration: none;
        font-weight: 500;
    }
    
    .post-feeling {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-weight: 500;
        font-size: 0.9em;
        margin-top: -10px;
        margin-bottom: 10px;
    }
    
    .post-image {
        max-width: 100%;
        border-radius: var(--border-radius);
        margin-top: 15px;
    }

    /* Статистика поста */
    .post-stats {
        display: flex;
        justify-content: space-between;
        font-size: 0.9em;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
        margin-bottom: 10px;
    }
    
    .likes, .comments {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Кнопки действий */
    .post-action-btn {
        flex: 1;
        text-align: center;
        cursor: pointer;
        padding: 8px;
        border-radius: var(--border-radius);
        transition: background-color 0.2s;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 500;
    }

    .post-action-btn:hover {
        background-color: var(--hover-background);
        color: var(--text-color);
    }
    
    .post-action-btn .fas, .post-action-btn .far {
        color: var(--text-secondary);
    }

    /* Секция комментариев */
    .add-comment {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .comment-author-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .comment-form {
        flex-grow: 1;
    }

    .comment-form input {
        width: 100%;
        padding: 8px 12px;
        border: none;
        background-color: var(--hover-background);
        border-radius: 20px;
    }

    .comment-form input:focus {
        outline: none;
        background-color: #E4E6EB;
    }

    /* Опросы */
    .poll-container {
        margin-top: 15px;
    }

    .poll-option {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 8px;
        background-color: var(--hover-background);
        margin-bottom: 8px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.2s ease-in-out;
    }
    
    .poll-option:hover {
        transform: scale(1.01);
    }

    .poll-option-bar {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        background-color: #C0DEEE;
        z-index: 0;
        transition: width 0.5s ease-in-out;
    }

    .poll-option-content {
        position: relative;
        z-index: 1;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .poll-text {
        font-weight: 500;
        color: var(--text-color);
        white-space: normal;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .poll-votes {
        font-size: 0.9em;
        color: var(--text-secondary);
        font-weight: 600;
        flex-shrink: 0;
        margin-left: 10px;
    }
    
    /* Стили для формы создания опроса */
    .poll-creation {
        background-color: #f9f9f9;
        border: 1px solid #e4e6eb;
        border-radius: var(--border-radius);
        padding: 15px;
        margin-top: 15px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        box-sizing: border-box;
    }

    /* Медиа-запросы для мобильных устройств */
    @media (max-width: 992px) {
        .page-container {
            flex-direction: column;
            padding: 10px;
        }

        .left-sidebar, .right-sidebar {
            width: 100%;
        }
        
        /* Скрываем блок "Группы" в левой панели */
        .left-sidebar .sidebar-card:last-child {
            display: none;
        }

        /* Убираем правый сайдбар на мобильных */
        .right-sidebar {
            display: none;
        }

        .main-content {
            padding: 0;
        }

        /* Делаем кнопки действий в одну строку */
        .post-actions {
            flex-direction: row;
            justify-content: space-around;
        }

        .post-action-btn {
            flex-grow: 0;
            width: auto;
        }

        .post-action-btn span {
            display: none;
        }
        
        /* Стили для формы создания поста на мобильных */
        .create-post .post-actions {
            flex-wrap: nowrap;
            overflow-x: auto;
            white-space: nowrap;
            justify-content: flex-start;
        }

        .create-post .post-action, .create-post .post-submit {
            flex-shrink: 0;
        }
        
        .create-post .post-submit {
            margin-left: auto;
        }
        
        .post-input-field {
            font-size: 0.9rem;
        }
        
        /* Выпадающее меню "Чувства" на мобильных устройствах */
/* Выпадающее меню "Чувства" на мобильных устройствах */
/* Выпадающее меню "Чувства" на мобильных устройствах */

    
    .feeling-dropdown.mobile-bottom {
        top: 20px; /* Принудительно устанавливаем сверху */
        bottom: auto !important;
    }
    
    /* Затемнение фона при открытом меню */
    .feeling-dropdown.show::before {
        content: '';
        position: fixed;
        top: 0px;
        left: 0px;
        width: 90vw;
        background: rgba(0, 0, 0, 0.5);
        z-index: -1;
    }

}
/* Добавьте эти стили в ваш основной CSS */

.poll-option-result {
    position: relative; /* Необходимо для позиционирования псевдоэлемента */
    background-color: #f0f2f5; /* Фон для всей полосы */
    border-radius: 8px;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    overflow: hidden; /* Скрываем то, что выходит за пределы скругленных углов */
    z-index: 1; /* Чтобы контент был выше псевдоэлемента */
}

/* Псевдоэлемент, который и будет нашей полосой прогресса */
.poll-option-result::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: var(--progress-width); /* Используем значение из HTML */
    background-color: #C0DEEE; /* Цвет заполнения */
    border-radius: 8px;
    z-index: -1; /* Помещаем под текстом */
    transition: width 0.5s ease-in-out; /* Плавная анимация */
}

.poll-option-text {
    font-weight: 500;
    color: var(--text-color);
}

.poll-option-votes {
    font-size: 0.9em;
    font-weight: 600;
    color: var(--text-secondary);
    flex-shrink: 0; /* Чтобы текст не сжимался */
    margin-left: 10px;
}

/* Стили для формы голосования, чтобы они выглядели похоже */
.poll-option-vote {
    display: flex;
    align-items: center;
    background-color: #f0f2f5;
    border-radius: 8px;
    padding: 12px 15px;
    cursor: pointer;
    transition: background-color 0.2s;
}
.poll-option-vote:hover {
    background-color: #e4e6eb;
}
.poll-option-vote input {
    margin-right: 10px;
}
.poll-option-result {
    /* Ваши существующие стили */
    position: relative;
    background-color: #f0f2f5;
    border-radius: 8px;
    padding: 12px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    overflow: hidden;
    z-index: 1;

    /* ДОБАВЬТЕ ЭТО 👇 */
    width: 100%;
    box-sizing: border-box; /* Чтобы padding не ломал ширину в 100% */
}
</style>






<style>
/* Темная тема в стиле Telegram */
@media (prefers-color-scheme: dark) {
    :root {
        --background-color: #0f0f0f;
        --card-background: #1e1e1e;
        --text-color: #e1e1e1;
        --text-secondary: #a0a0a0;
        --border-color: #2d2d2d;
        --hover-background: #2d2d2d;
        --online-color: #48bb78;
    }

    body {
        background-color: var(--background-color);
        color: var(--text-color);
    }

    .sidebar-card {
        background-color: var(--card-background);
        border: 1px solid var(--border-color);
    }

    .post-input-field {
        background-color: #2d2d2d;
        color: var(--text-color);
    }

    .post-input-field:focus {
        background-color: #3d3d3d;
    }

    .post-action {
        color: var(--text-secondary);
    }

    .post-action:hover {
        background-color: var(--hover-background);
        color: var(--text-color);
    }

    .create-post {
        background-color: var(--card-background);
        border: 1px solid var(--border-color);
    }

    .post-card {
        background-color: var(--card-background);
        border: 1px solid var(--border-color);
    }

    .comment-form input {
        background-color: #2d2d2d;
        color: var(--text-color);
    }

    .comment-form input:focus {
        background-color: #3d3d3d;
    }

    .poll-creation {
        background-color: #2d2d2d;
        border-color: var(--border-color);
    }

    .form-control {
        background-color: #3d3d3d;
        border-color: var(--border-color);
        color: var(--text-color);
    }

    .poll-option-vote,
    .poll-option-result {
        background-color: #2d2d2d;
    }

    .poll-option-vote:hover {
        background-color: #3d3d3d;
    }

    .feeling-dropdown {
        background-color: var(--card-background);
        border: 1px solid var(--border-color);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    .feeling-option {
        color: var(--text-color);
    }

    .feeling-option:hover {
        background-color: var(--hover-background);
    }

    /* Специфичные стили для постов с чувствами */
    .post-card.happy { border-color: #d97706; }
    .post-card.sad { border-color: #2563eb; }
    .post-card.angry { border-color: #dc2626; }
    .post-card.loved { border-color: #db2777; }
    .post-card.tired { border-color: #475569; }
    .post-card.blessed { border-color: #059669; }
}

/* Принудительное применение темной темы */
.dark-theme {
    --background-color: #0f0f0f;
    --card-background: #1e1e1e;
    --text-color: #e1e1e1;
    --text-secondary: #a0a0a0;
    --border-color: #2d2d2d;
    --hover-background: #2d2d2d;
    --online-color: #48bb78;
}

.dark-theme body {
    background-color: var(--background-color);
    color: var(--text-color);
}

.dark-theme .sidebar-card {
    background-color: var(--card-background);
    border: 1px solid var(--border-color);
}

.dark-theme .post-input-field {
    background-color: #2d2d2d;
    color: var(--text-color);
}

.dark-theme .post-input-field:focus {
    background-color: #3d3d3d;
}

.dark-theme .post-action {
    color: var(--text-secondary);
}

.dark-theme .post-action:hover {
    background-color: var(--hover-background);
    color: var(--text-color);
}

.dark-theme .create-post {
    background-color: var(--card-background);
    border: 1px solid var(--border-color);
}

.dark-theme .post-card {
    background-color: var(--card-background);
    border: 1px solid var(--border-color);
}

.dark-theme .comment-form input {
    background-color: #2d2d2d;
    color: var(--text-color);
}

.dark-theme .comment-form input:focus {
    background-color: #3d3d3d;
}

.dark-theme .poll-creation {
    background-color: #2d2d2d;
    border-color: var(--border-color);
}

.dark-theme .form-control {
    background-color: #3d3d3d;
    border-color: var(--border-color);
    color: var(--text-color);
}

.dark-theme .poll-option-vote,
.dark-theme .poll-option-result {
    background-color: #2d2d2d;
}

.dark-theme .poll-option-vote:hover {
    background-color: #3d3d3d;
}

.dark-theme .feeling-dropdown {
    background-color: var(--card-background);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dark-theme .feeling-option {
    color: var(--text-color);
}

.dark-theme .feeling-option:hover {
    background-color: var(--hover-background);
}

.dark-theme .post-card.happy { border-color: #d97706; }
.dark-theme .post-card.sad { border-color: #2563eb; }
.dark-theme .post-card.angry { border-color: #dc2626; }
.dark-theme .post-card.loved { border-color: #db2777; }
.dark-theme .post-card.tired { border-color: #475569; }
.dark-theme .post-card.blessed { border-color: #059669; }

/* Плавные переходы для темной темы */
.tg-app,
.sidebar-card,
.post-input-field,
.post-action,
.create-post,
.post-card,
.comment-form input,
.poll-creation,
.form-control,
.poll-option-vote,
.poll-option-result,
.feeling-dropdown {
    transition: background-color 0.3s ease, 
                color 0.3s ease, 
                border-color 0.3s ease,
                box-shadow 0.3s ease;
}

/* Дополнительные улучшения для темной темы */
@media (prefers-color-scheme: dark),
.dark-theme {
    .post-stats {
        border-bottom-color: var(--border-color);
    }
    
    .post-actions {
        border-top-color: var(--border-color);
    }
    
    .comments-section {
        border-top-color: var(--border-color);
    }
    
    .view-all-link {
        color: #5D93B5;
    }
    
    .view-all-link:hover {
        color: #7ab0d3;
    }
    
    .notification-badge {
        background-color: #ef4444;
    }
    
    /* Улучшенные тени для темной темы */
    .sidebar-card,
    .create-post,
    .post-card,
    .feeling-dropdown {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
    }
}

/* Адаптация для мобильных в темной теме */
@media (max-width: 992px) and (prefers-color-scheme: dark),
@media (max-width: 992px) and (.dark-theme) {
    .page-container {
        background-color: var(--background-color);
    }
    
    .feeling-dropdown.show::before {
        background: rgba(0, 0, 0, 0.7);
    }
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
    .user-name{
            color: #ffffff !important;
        }
        h3{
            color: #ffffff !important;
        }
        .group-name{
            color: #ffffff !important;
        }
        .friend-name{
            color: #ffffff !important;
        }
    .poll-container{
        color: #ffffff;
    }
    .poll-option-result{
        background: #6b6b6b;
    }
    .feeling-option{
        color: #ffffff !important;
    }
    .post-author{
        color: #ffffff;
    }
    .post-text{
        color: #ffffff;
    }
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