<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
$page = $_GET['page'] ?? 1;
$limit = 20; // Количество фото на страницу
$offset = ($page - 1) * $limit;

// Получаем все посты с изображениями
$photos = $db->query("
    SELECT p.id as post_id, p.image, p.created_at, u.id as user_id, u.full_name, u.avatar 
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.image IS NOT NULL
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
");

$totalPhotos = $db->querySingle("
    SELECT COUNT(*) FROM posts WHERE image IS NOT NULL
");

require_once 'includes/header.php';
?>

<div class="photos-page">
    <h1><i class="fas fa-images"></i> Фотографии</h1>
    
    <div class="photos-grid">
        <?php while ($photo = $photos->fetchArray()): ?>
            <div class="photo-item">
                <a href="/post.php?id=<?= $photo['post_id'] ?>">
                    <img src="/assets/images/posts/<?= htmlspecialchars($photo['image']) ?>" 
                         alt="Фото пользователя <?= htmlspecialchars($photo['full_name']) ?>"
                         class="photo-image">
                    <div class="photo-info">
                        <img src="/assets/images/avatars/<?= htmlspecialchars($photo['avatar']) ?>" 
                             alt="<?= htmlspecialchars($photo['full_name']) ?>"
                             class="photo-author-avatar">
                        <span class="photo-author-name"><?= htmlspecialchars($photo['full_name']) ?></span>
                    </div>
                </a>
            </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Пагинация -->
    <?php if ($totalPhotos > $limit): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="pagination-link"><i class="fas fa-chevron-left"></i> Назад</a>
            <?php endif; ?>
            
            <?php if ($page * $limit < $totalPhotos): ?>
                <a href="?page=<?= $page + 1 ?>" class="pagination-link">Вперед <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.photos-page {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 15px;
}

.photos-page h1 {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.photo-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    aspect-ratio: 1;
}

.photo-item:hover {
    transform: scale(1.02);
}

.photo-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    padding: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.photo-author-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

.photo-author-name {
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
}

.pagination-link {
    padding: 8px 16px;
    background: var(--primary-color);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-link:hover {
    background: #357ae8;
}

@media (max-width: 768px) {
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>