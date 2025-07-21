<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
if (!$user) {
    header('Location: /login.php');
    exit;
}

$streams = getLiveStreams($db);

// Проверяем, есть ли у пользователя активный стрим
$user_stream = null;
$stmt = $db->prepare("SELECT * FROM live_streams WHERE user_id = ? AND is_live = 1");
$stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$user_stream = $result->fetchArray(SQLITE3_ASSOC);

require_once 'includes/header.php';
?>

<main class="main-content" style="width: 100%;">
    <?php if ($user_stream): ?>
        <!-- Пользователь ведет стрим -->
        <div class="feed">
            <h1 style="font-size: 1.5rem; margin-bottom: 20px;">
                Ваш прямой эфир
            </h1>
            
            <div style="background-color: #000; border-radius: 10px; position: relative; padding-top: 56.25%;">
                <video id="live-stream" controls style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px;"></video>
            </div>
            
            <div style="margin-top: 20px;">
                <h2 style="font-size: 1.2rem; margin-bottom: 10px;"><?= htmlspecialchars($user_stream['title']) ?></h2>
                <p><?= htmlspecialchars($user_stream['description']) ?></p>
                
                <div style="margin-top: 20px;">
                    <button id="stop-stream-btn" class="post-action-btn" style="background-color: var(--accent-color); color: white;">
                        <i class="fas fa-stop"></i> Завершить трансляцию
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Инициализация трансляции (упрощенная версия)
        document.addEventListener('DOMContentLoaded', function() {
            const videoElement = document.getElementById('live-stream');
            
            // В реальном приложении здесь было бы подключение к серверу трансляции
            // Например, через WebRTC или HLS
            
            // Для демонстрации просто показываем сообщение
            videoElement.innerHTML = `
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; background-color: rgba(0,0,0,0.7);">
                    <i class="fas fa-broadcast-tower" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <div style="font-size: 1.5rem;">Идет трансляция</div>
                </div>
            `;
            
            // Обработка завершения трансляции
            document.getElementById('stop-stream-btn').addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите завершить трансляцию?')) {
                    fetch('/actions/stop_stream.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Error:', error));
                }
            });
        });
        </script>
    <?php else: ?>
        <!-- Пользователь может начать стрим -->
        <div class="feed">
            <h1 style="font-size: 1.5rem; margin-bottom: 20px;">
                Начать прямой эфир
            </h1>
            
            <form id="start-stream-form" style="margin-top: 20px;">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Название трансляции</label>
                    <input type="text" name="title" placeholder="Введите название" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none;" required>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Описание</label>
                    <textarea name="description" placeholder="Введите описание" style="width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none; min-height: 100px;"></textarea>
                </div>
                
                <button type="submit" class="post-action-btn" style="background-color: var(--accent-color); color: white;">
                    <i class="fas fa-broadcast-tower"></i> Начать трансляцию
                </button>
            </form>
        </div>
        
        <script>
        document.getElementById('start-stream-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                title: formData.get('title'),
                description: formData.get('description')
            };
            
            fetch('/actions/start_stream.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при запуске трансляции');
                }
            })
            .catch(error => console.error('Error:', error));
        });
        </script>
    <?php endif; ?>
    
    <!-- Активные трансляции -->
    <div class="feed" style="margin-top: 30px;">
        <h1 style="font-size: 1.5rem; margin-bottom: 20px;">
            Активные трансляции
        </h1>
        
        <?php if (!empty($streams)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($streams as $stream): 
                    $streamer = getUserById($db, $stream['user_id']);
                ?>
                    <a href="/watch_stream.php?id=<?= $stream['id'] ?>" style="text-decoration: none; color: inherit;">
                        <div style="background-color: #000; border-radius: 10px; position: relative; padding-top: 56.25%;">
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; background-color: rgba(0,0,0,0.7);">
                                <i class="fas fa-broadcast-tower" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div style="font-size: 1.2rem;">Идет трансляция</div>
                            </div>
                            <div style="position: absolute; top: 10px; left: 10px; background-color: var(--accent-color); color: white; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">
                                LIVE
                            </div>
                        </div>
                        <div style="margin-top: 10px; display: flex;">
                            <img src="assets/images/avatars/<?= $streamer['avatar'] ?>" alt="Streamer" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                            <div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($stream['title']) ?></div>
                                <div style="font-size: 0.9rem; color: var(--gray-color);"><?= htmlspecialchars($streamer['full_name']) ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; color: var(--gray-color);">
                <i class="fas fa-broadcast-tower" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <div style="font-size: 1.2rem;">Сейчас нет активных трансляций</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>