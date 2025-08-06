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