<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$postId = $_GET['id'] ?? 0;
$post = $db->querySingle("
    SELECT p.*, u.full_name, u.avatar 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.id = $postId
", true);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    die("Пост не найден");
}

require_once 'includes/header.php';
?>

<div class="single-post-page">
    <div class="post-card">
        <!-- Ваш код для отображения поста (аналогично index.php) -->
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
                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        <?php if ($post['image']): ?>
                            <img src="/assets/images/posts/<?= $post['image'] ?>" alt="Post Image" class="post-image">
                        <?php endif; ?>
                    </div>
    </div>
</div>
<style>
/* Основные стили */
.page-container {
    display: flex;
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
    gap: 20px;
}

/* Левая колонка */
.left-sidebar {
    width: 25%;
    min-width: 250px;
}

.sidebar-card {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    margin-bottom: 5px;
}

.user-bio {
    font-size: 0.9rem;
    color: #65676b;
}

.sidebar-title {
    font-size: 1.1rem;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu li {
    margin-bottom: 5px;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    border-radius: 8px;
    color: #050505;
    text-decoration: none;
    transition: background 0.2s;
}

.sidebar-menu a:hover {
    background: #f0f2f5;
}

.sidebar-menu i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.shortcut-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.shortcut-item {
    display: flex;
    align-items: center;
    padding: 8px;
    border-radius: 8px;
    text-decoration: none;
    color: #050505;
    transition: background 0.2s;
}

.shortcut-item:hover {
    background: #f0f2f5;
}

.group-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    margin-right: 10px;
}

.group-name {
    font-size: 0.95rem;
}

/* Основная лента */
.main-content {
    flex: 1;
    min-width: 0;
}

.create-post {
    background: white;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.post-input {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.post-author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
}

.post-input-field {
    flex: 1;
    padding: 10px 15px;
    border: none;
    border-radius: 20px;
    background: #f0f2f5;
    font-size: 0.95rem;
}

.post-input-field:focus {
    outline: none;
    background: #e4e6e9;
}

.post-actions {
    display: flex;
    justify-content: space-between;
    padding-top: 8px;
    border-top: 1px solid #eee;
}

.post-action {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #65676b;
    transition: all 0.2s;
}

.post-action:hover {
    background: #f0f2f5;
}

.post-action i {
    margin-right: 8px;
}

.post-submit {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: background 0.2s;
}

.post-submit:hover {
    background: #357ae8;
}

.file-input {
    display: none;
}

/* Посты */
.posts-feed {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post-card {
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.post-author {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
}

.author-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 12px;
}

.author-details {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    margin-bottom: 2px;
}

.post-time {
    font-size: 0.8rem;
    color: #65676b;
}

.post-options {
    background: none;
    border: none;
    color: #65676b;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
}

.post-options:hover {
    background: #f0f2f5;
}

.post-content {
    margin-bottom: 12px;
}

.post-text {
    margin-bottom: 10px;
    line-height: 1.4;
}

.post-image {
    width: 100%;
    border-radius: 8px;
    margin-top: 10px;
    max-height: 500px;
    object-fit: contain;
}

.post-stats {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    color: #65676b;
    font-size: 0.9rem;
}

.post-actions {
    display: flex;
    justify-content: space-around;
    padding: 8px 0;
}

.post-action-btn {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    background: none;
    border: none;
    cursor: pointer;
    color: #65676b;
    transition: all 0.2s;
}

.post-action-btn:hover {
    background: #f0f2f5;
}

.post-action-btn i {
    margin-right: 8px;
}

/* Комментарии */
.comments-section {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #eee;
}

.add-comment {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.comment-author-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
}

.comment-form {
    flex: 1;
}

.comment-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 20px;
    border: none;
    background: #f0f2f5;
}

.comment-input:focus {
    outline: none;
    background: #e4e6e9;
}

/* Правая колонка */
.right-sidebar {
    width: 25%;
    min-width: 250px;
}

.friends-header, .groups-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.friend-actions a, .group-actions a {
    color: #65676b;
    padding: 6px;
    border-radius: 50%;
    transition: all 0.2s;
}

.friend-actions a:hover, .group-actions a:hover {
    background: #f0f2f5;
}

.friends-list, .groups-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.friend-item, .group-item {
    padding: 8px 0;
}

.friend-link, .group-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: inherit;
    padding: 4px;
    border-radius: 6px;
    transition: background 0.2s;
}

.friend-link:hover, .group-link:hover {
    background: #f0f2f5;
}

.friend-avatar-container {
    position: relative;
    width: 36px;
    height: 36px;
    margin-right: 12px;
}

.friend-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.online-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #31a24c;
    border: 2px solid white;
}

.friend-info {
    flex: 1;
}

.friend-name {
    font-weight: 500;
    font-size: 0.95rem;
}

.group-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: cover;
    margin-right: 12px;
}

.group-name {
    font-size: 0.95rem;
}

.view-all-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 4px;
    margin-top: 8px;
    color: var(--primary-color);
    text-decoration: none;
    border-top: 1px solid #eee;
    transition: color 0.2s;
}

.view-all-link:hover {
    color: #357ae8;
}

/* Адаптивность */
@media (max-width: 992px) {
    .page-container {
        flex-direction: column;
    }
    
    .left-sidebar, .right-sidebar {
        width: 100%;
        order: 3;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .sidebar-card {
        flex: 1;
        min-width: 250px;
    }
    
    .main-content {
        order: 1;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .post-action span, .post-action-btn span, .post-submit span {
        display: none;
    }
    
    .post-action i, .post-action-btn i {
        margin-right: 0;
    }
    
    .sidebar-menu a {
        padding: 8px;
    }
    
    .sidebar-menu span {
        display: none;
    }
    
    .sidebar-menu i {
        margin-right: 0;
        font-size: 1.1rem;
    }
}

@media (max-width: 576px) {
    .create-post {
        padding: 12px;
    }
    
    .post-input-field {
        padding: 8px 12px;
    }
    
    .post-action, .post-action-btn {
        padding: 8px;
    }
}
</style>
<?php require_once 'includes/footer.php'; ?>