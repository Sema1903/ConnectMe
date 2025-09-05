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
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.8rem;
}

.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.photo-item {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    aspect-ratio: 1;
    background: var(--tg-card-bg);
    border: 1px solid var(--tg-border);
}

.photo-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.photo-item a {
    display: block;
    width: 100%;
    height: 100%;
    text-decoration: none;
}

.photo-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.photo-item:hover .photo-image {
    transform: scale(1.05);
}

.photo-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.photo-item:hover .photo-info {
    opacity: 1;
    transform: translateY(0);
}

.photo-author-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.photo-author-name {
    color: white;
    font-size: 0.95rem;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

/* Пагинация в стиле Telegram */
.pagination {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 10px;
    padding: 20px 0;
    margin-bottom: 100px;
}

.pagination-link {
    padding: 12px 24px;
    background: var(--tg-primary);
    color: white;
    border-radius: 12px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.pagination-link:hover {
    background: #0066a4;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
}

/* Анимация загрузки фотографий */
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

.photo-item {
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.photo-item:nth-child(1) { animation-delay: 0.05s; }
.photo-item:nth-child(2) { animation-delay: 0.1s; }
.photo-item:nth-child(3) { animation-delay: 0.15s; }
.photo-item:nth-child(4) { animation-delay: 0.2s; }
.photo-item:nth-child(5) { animation-delay: 0.25s; }
.photo-item:nth-child(6) { animation-delay: 0.3s; }
.photo-item:nth-child(7) { animation-delay: 0.35s; }
.photo-item:nth-child(8) { animation-delay: 0.4s; }
.photo-item:nth-child(9) { animation-delay: 0.45s; }
.photo-item:nth-child(10) { animation-delay: 0.5s; }

/* Состояние загрузки */
.photos-grid.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 14px;
    }
}

@media (max-width: 768px) {
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
    }
    
    .photos-page {
        padding: 0 10px;
    }
    
    .photos-page h1 {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .photo-info {
        padding: 12px;
        opacity: 1;
        transform: translateY(0);
    }
    
    .photo-author-avatar {
        width: 28px;
        height: 28px;
    }
    
    .photo-author-name {
        font-size: 0.85rem;
    }
    
    .pagination {
        flex-direction: column;
        align-items: center;
        gap: 12px;
    }
    
    .pagination-link {
        padding: 10px 20px;
        width: 200px;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .photos-grid {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }
    
    .photo-item {
        border-radius: 12px;
    }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
    .photo-item {
        border-color: var(--tg-border);
    }
    
    .photo-image {
        filter: brightness(0.95);
    }
    
    .photo-item:hover .photo-image {
        filter: brightness(1);
    }
}

/* Эффект при клике */
.photo-item:active {
    transform: scale(0.98);
}

/* Сообщение если нет фотографий */
.photos-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--tg-text-secondary);
}

.photos-empty i {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.photos-empty h3 {
    font-size: 1.2rem;
    margin-bottom: 10px;
    color: var(--tg-text-primary);
}
</style>
<style>
/* Базовые переменные для темной темы */
:root {
  --tg-bg-dark: #0f0f0f;
  --tg-secondary-dark: #1a1a1a;
  --tg-card-bg-dark: #1e1e1e;
  --tg-border-dark: #2d2d2d;
  --tg-text-primary-dark: #e3e3e3;
  --tg-text-secondary-dark: #a0a0a0;
  --tg-primary-dark: #2ea6ff;
  --tg-hover-dark: #2a2a2a;
}

/* Применение темной темы */
@media (prefers-color-scheme: dark) {
  .photos-page {
    background-color: var(--tg-bg-dark);
    color: var(--tg-text-primary-dark);
  }

  .photos-page h1 {
    color: var(--tg-text-primary-dark);
  }

  .photo-item {
    background: var(--tg-card-bg-dark);
    border: 1px solid var(--tg-border-dark);
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
  }

  .photo-item:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    background: var(--tg-hover-dark);
  }

  .pagination-link {
    background: var(--tg-primary-dark);
  }

  .pagination-link:hover {
    background: #008be6;
    box-shadow: 0 4px 12px rgba(46, 166, 255, 0.25);
  }

  /* Улучшенная анимация для темной темы */
  .photo-image {
    filter: brightness(0.9);
    transition: filter 0.3s ease, transform 0.3s ease;
  }

  .photo-item:hover .photo-image {
    filter: brightness(1.05);
  }

  /* Оптимизация для мобильных в темной теме */
  @media (max-width: 768px) {
    .photo-info {
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
    }
  }
}

/* Улучшения для обоих режимов */
.photos-page {
  transition: background-color 0.3s ease, color 0.3s ease;
}

.photo-item {
  transition: all 0.3s ease, background-color 0.3s ease;
}
</style>

<script>
// Добавляем плавную загрузку изображений
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.photo-image');
    
    images.forEach(img => {
        // Показываем placeholder пока изображение загружается
        img.style.opacity = '0';
        img.addEventListener('load', function() {
            this.style.opacity = '1';
            this.style.transition = 'opacity 0.3s ease';
        });
        
        // Обработка ошибок загрузки
        img.addEventListener('error', function() {
            this.style.opacity = '1';
            this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjJmMmYyIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIGltYWdlPC90ZXh0Pjwvc3ZnPg==';
        });
    });
});

// Бесконечная прокрутка (опционально)
let isLoading = false;
window.addEventListener('scroll', function() {
    if (isLoading) return;
    
    const scrollHeight = document.documentElement.scrollHeight;
    const scrollTop = document.documentElement.scrollTop;
    const clientHeight = document.documentElement.clientHeight;
    
    if (scrollTop + clientHeight >= scrollHeight - 100) {
        const nextPage = <?= $page + 1 ?>;
        const totalPages = Math.ceil(<?= $totalPhotos ?> / <?= $limit ?>);
        
        if (nextPage <= totalPages) {
            isLoading = true;
            // Здесь можно добавить загрузку следующей страницы через AJAX
        }
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
<?php require_once 'includes/footer.php'; ?>