<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$user = getCurrentUser($db);
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$posts = getPosts($db, $limit, $offset);

foreach ($posts as $post): ?>
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
            <?php if ($post['feeling']): ?>
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
                                                <!-- Показываем результаты -->
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                                    <span><?= htmlspecialchars($option['option_text']) ?></span>
                                                    <span><?= $option['votes'] ?> (<?= round($option['votes'] / max(1, $post['poll']['total_votes']) * 100) ?>%)</span>
                                                </div>
                                                <div style="height: 10px; background: #f0f0f0; border-radius: 5px;">
                                                    <div style="height: 100%; width: <?= round($option['votes'] / max(1, $post['poll']['total_votes']) * 100) ?>%; 
                                                        background: var(--primary-color); border-radius: 5px;"></div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Показываем варианты для голосования -->
                                                <label style="display: flex; align-items: center;">
                                                    <input type="<?= $post['poll']['is_multiple'] ? 'checkbox' : 'radio' ?>" 
                                                        name="poll_option_<?= $post['poll']['id'] ?>" 
                                                        value="<?= $option['id'] ?>" 
                                                        style="margin-right: 10px;">
                                                    <?= htmlspecialchars($option['option_text']) ?>
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
                <i class="<?= $post['is_liked'] ? 'fas' : 'far' ?> fa-thumbs-up"></i>
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
        
       <!-- Секция комментариев (изначально скрыта) -->
       <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display: none;">
                <?php if ($user): ?>
                <div class="add-comment">
                    <img src="assets/images/avatars/<?= $user['avatar'] ?>" class="comment-avatar">
                    <form class="comment-form" data-post-id="<?= $post['id'] ?>">
                        <input type="text" name="comment" placeholder="Написать комментарий..." required>
                        <button type="submit" style="display:none"></button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div class="comments-list" id="comments-list-<?= $post['id'] ?>"></div>
            </div>
        </div>
    </div>
<?php endforeach;?>
<script>
    // Инициализируем обработчики для только что загруженных постов
    if (typeof initPostHandlers === 'function') {
        const newPosts = document.querySelectorAll('.post-card');
        initPostHandlers(newPosts);
    }
</script>
<script src = '../assets/js/main.js'></script>