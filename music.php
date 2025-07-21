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
.music-page {
    max-width: 800px;
    margin: 20px auto;
    padding: 0 15px;
}

.music-page h1 {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.upload-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.upload-container {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.upload-button {
    padding: 10px 15px;
    background: #f0f2f5;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-grow: 1;
    transition: background 0.2s;
}

.upload-button:hover {
    background: #e4e6e9;
}

.submit-button {
    padding: 10px 20px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.submit-button:hover {
    background: #357ae8;
}

.upload-status {
    font-size: 0.9rem;
    color: #666;
}

.music-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.track {
    background: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.track-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 12px;
}

.track-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.track-details {
    flex: 1;
}

.track-title {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
}

.track-author {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.audio-player {
    width: 100%;
    margin-top: 10px;
}

.track-stats {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    font-size: 0.8rem;
    color: #888;
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

@media (max-width: 600px) {
    .upload-container {
        flex-direction: column;
    }
    
    .track-info {
        flex-direction: column;
        align-items: flex-start;
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

<?php require_once 'includes/footer.php'; ?>