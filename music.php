<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);

// Обработка загрузки музыки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['music'])) {
    if (!$user) {
        die(json_encode(['error' => 'Необходимо авторизоваться']));
    }

    $allowedTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg'];
    $file = $_FILES['music'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        die(json_encode(['error' => 'Недопустимый формат файла']));
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $extension;
    $uploadPath = 'assets/music/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Сохраняем информацию о треке в БД
        $title = basename($file['name'], '.' . $extension);
        $stmt = $db->prepare("
            INSERT INTO music 
            (user_id, title, file_name, uploaded_at) 
            VALUES (?, ?, ?, datetime('now'))
        ");
        $stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
        $stmt->bindValue(2, $title, SQLITE3_TEXT);
        $stmt->bindValue(3, $fileName, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    die(json_encode(['error' => 'Ошибка загрузки файла']));
}

// Получаем список музыки
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$music = $db->query("
    SELECT m.*, u.full_name, u.avatar 
    FROM music m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.uploaded_at DESC
    LIMIT $limit OFFSET $offset
");

$totalTracks = $db->querySingle("SELECT COUNT(*) FROM music");

require_once 'includes/header.php';
?>

<div class="music-page">
    <h1><i class="fas fa-music"></i> Музыка</h1>
    
    <?php if ($user): ?>
    <div class="upload-section">
        <form id="upload-form" enctype="multipart/form-data">
            <div class="upload-container">
                <label for="music-input" class="upload-button">
                    <i class="fas fa-upload"></i> Загрузить трек
                </label>
                <input type="file" id="music-input" name="music" accept="audio/*" style="display: none;">
                <button type="submit" class="submit-button">Отправить</button>
            </div>
            <div class="upload-status" id="upload-status"></div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="music-list">
        <?php while ($track = $music->fetchArray()): ?>
            <div class="track" data-id="<?= $track['id'] ?>">
                <div class="track-info">
                    <img src="/assets/images/avatars/<?= htmlspecialchars($track['avatar']) ?>" 
                         alt="<?= htmlspecialchars($track['full_name']) ?>" 
                         class="track-avatar">
                    <div class="track-details">
                        <h3 class="track-title"><?= htmlspecialchars($track['title']) ?></h3>
                        <p class="track-author"><?= htmlspecialchars($track['full_name']) ?></p>
                    </div>
                </div>
                <div class="track-controls">
                    <audio controls class="audio-player" loop>
                        <source src="/assets/music/<?= htmlspecialchars($track['file_name']) ?>" type="audio/mpeg">
                        Ваш браузер не поддерживает аудио элемент.
                    </audio>
                    <div class="track-stats">
                        <span class="track-date"><?= date('d.m.Y', strtotime($track['uploaded_at'])) ?></span>
                        <span class="track-plays"><?= $track['plays'] ?? 0 ?> прослушиваний</span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <?php if ($totalTracks > $limit): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="pagination-link"><i class="fas fa-chevron-left"></i> Назад</a>
            <?php endif; ?>
            
            <?php if ($page * $limit < $totalTracks): ?>
                <a href="?page=<?= $page + 1 ?>" class="pagination-link">Вперед <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --tg-primary: #0088cc;
    --tg-bg: #f5f5f5;
    --tg-card-bg: #ffffff;
    --tg-text-primary: #333333;
    --tg-text-secondary: #707579;
    --tg-border: #e0e0e0;
    --tg-hover: #f8f9fa;
}

.music-page {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 15px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.music-page h1 {
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.8rem;
}

.upload-section {
    background: var(--tg-card-bg);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid var(--tg-border);
}

.upload-container {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    align-items: center;
}

.upload-button {
    padding: 12px 18px;
    background: var(--tg-hover);
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-grow: 1;
    transition: all 0.2s ease;
    border: 1px dashed var(--tg-border);
    color: var(--tg-text-secondary);
    font-weight: 500;
}

.upload-button:hover {
    background: #e8f4fd;
    border-color: var(--tg-primary);
    color: var(--tg-primary);
}

.submit-button {
    padding: 12px 24px;
    background: var(--tg-primary);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.submit-button:hover {
    background: #0066a4;
    transform: translateY(-1px);
}

.upload-status {
    font-size: 0.9rem;
    color: var(--tg-text-secondary);
    padding: 8px 0;
}

.music-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.track {
    background: var(--tg-card-bg);
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid var(--tg-border);
    transition: all 0.2s ease;
}

.track:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-1px);
}

.track-info {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.track-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--tg-border);
}

.track-details {
    flex: 1;
}

.track-title {
    margin: 0 0 4px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--tg-text-primary);
}

.track-author {
    margin: 0;
    color: var(--tg-text-secondary);
    font-size: 0.9rem;
}

.audio-player {
    width: 100%;
    height: 40px;
    border-radius: 20px;
    background: var(--tg-hover);
    border: 1px solid var(--tg-border);
    margin-top: 12px;
}

.audio-player::-webkit-media-controls-panel {
    background: var(--tg-hover);
    border-radius: 20px;
}

.audio-player::-webkit-media-controls-play-button {
    background-color: var(--tg-primary);
    border-radius: 50%;
}

.track-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 12px;
    font-size: 0.8rem;
    color: var(--tg-text-secondary);
    padding-top: 8px;
    border-top: 1px solid var(--tg-border);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 30px;
}

.pagination-link {
    padding: 10px 20px;
    background: var(--tg-primary);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.pagination-link:hover {
    background: #0066a4;
    transform: translateY(-1px);
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.track {
    animation: fadeIn 0.3s ease;
}

/* Темная тема (опционально) */
@media (prefers-color-scheme: dark) {
    :root {
        --tg-bg: #0f0f0f;
        --tg-card-bg: #1a1a1a;
        --tg-text-primary: #ffffff;
        --tg-text-secondary: #a8a8a8;
        --tg-border: #2a2a2a;
        --tg-hover: #2a2a2a;
    }
}

@media (max-width: 600px) {
    .upload-container {
        flex-direction: column;
    }
    
    .track-info {
        flex-direction: column;
        align-items: flex-start;
        text-align: center;
    }
    
    .track-avatar {
        align-self: center;
    }
    
    .music-page {
        padding: 0 10px;
    }
    
    .music-page h1 {
        font-size: 1.5rem;
        justify-content: center;
    }
}

/* Progress bar стилизация */
.audio-player::-webkit-media-controls-current-time-display,
.audio-player::-webkit-media-controls-time-remaining-display {
    color: var(--tg-text-secondary);
    font-size: 0.8rem;
}

.audio-player::-webkit-media-controls-timeline {
    background: var(--tg-border);
    border-radius: 2px;
    margin: 0 10px;
}
/* Улучшение контрастности для бокового меню */
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

/* Убедимся, что цвет текста всегда контрастный */
.sidebar-user-name {
    color: var(--tg-text-primary) !important;
}

.sidebar-user-status {
    color: var(--tg-text-secondary) !important;
}

/* Для темной темы улучшим контрастность */
@media (prefers-color-scheme: dark) {
    .sidebar-item {
        color: #ffffff !important;
    }
    
    .sidebar-item:hover,
    .sidebar-item.active {
        color: var(--tg-primary) !important;
    }
    
    .sidebar-item i {
        color: #a8a8a8;
    }
    
    .sidebar-item:hover i,
    .sidebar-item.active i {
        color: var(--tg-primary);
    }
}
</style>

<script>
// Обработка загрузки музыки
document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileInput = document.getElementById('music-input');
    if (!fileInput.files.length) {
        showStatus('Выберите файл для загрузки', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('music', fileInput.files[0]);
    
    const statusElement = document.getElementById('upload-status');
    statusElement.textContent = 'Загрузка...';
    statusElement.style.color = '#666';
    
    fetch('/music.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Трек успешно загружен!', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showStatus(data.error || 'Ошибка загрузки', 'error');
        }
    })
    .catch(() => {
        showStatus('Ошибка соединения', 'error');
    });
});

function showStatus(message, type) {
    const statusElement = document.getElementById('upload-status');
    statusElement.textContent = message;
    statusElement.style.color = type === 'success' ? 'green' : 'red';
}

// Счетчик прослушиваний
document.querySelectorAll('.audio-player').forEach(player => {
    player.addEventListener('play', function() {
        const trackId = this.closest('.track').dataset.id;
        
        // Отправляем запрос на увеличение счетчика
        fetch(`/actions/track_play.php?track_id=${trackId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const playsElement = this.closest('.track').querySelector('.track-plays');
                    playsElement.textContent = `${data.plays} прослушиваний`;
                }
            });
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
        margin-bottom: 100px !important;
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
<?php require_once 'includes/footer.php'; ?>