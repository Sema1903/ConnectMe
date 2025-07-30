<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$posts = getPosts($db, $limit, $offset);

foreach ($posts as $post): ?>
    <div class="post-card" id="post-<?= $post['id'] ?>">
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
            <p class="post-text"><?= $post['content'] ?></p>
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
                <div class="add-comment" style="display: flex; margin-bottom: 15px;">
                    <img src="assets/images/avatars/<?= $user['avatar'] ?>" alt="User" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                    <form class="comment-form" data-post-id="<?= $post['id'] ?>" style="flex-grow: 1;">
                        <input type="text" name="comment" placeholder="Написать комментарий..." style="width: 100%; padding: 8px 12px; border-radius: 20px; border: 1px solid #ddd; outline: none;">
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="comments-list" id="comments-list-<?= $post['id'] ?>">
                <!-- Комментарии будут загружены по запросу -->
            </div>
        </div>
    </div>
<?php endforeach;