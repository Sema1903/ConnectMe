<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
$stream_id = $_GET['id'] ?? null;

if (!$stream_id) {
    header('Location: /live.php');
    exit;
}

// Получаем информацию о стриме
$stmt = $db->prepare("
    SELECT l.*, u.username, u.full_name, u.avatar
    FROM live_streams l
    JOIN users u ON l.user_id = u.id
    WHERE l.id = ? AND l.is_live = 1
");
$stmt->bindValue(1, $stream_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$stream = $result->fetchArray(SQLITE3_ASSOC);

if (!$stream) {
    header('Location: /live.php');
    exit;
}

require_once 'includes/header.php';
?>

<main class="main-content" style="width: 100%;">
    <div class="feed">
        <h1 style="font-size: 1.5rem; margin-bottom: 20px;">
            Прямой эфир: <?= htmlspecialchars($stream['title']) ?>
        </h1>
        
        <div style="background-color: #000; border-radius: 10px; position: relative; padding-top: 56.25%;">
            <video id="live-stream" controls autoplay style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 10px;"></video>
        </div>
        
        <div style="margin-top: 20px; display: flex;">
            <img src="assets/images/avatars/<?= $stream['avatar'] ?>" alt="Streamer" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 15px;">
            <div>
                <div style="font-weight: 600; font-size: 1.2rem;"><?= htmlspecialchars($stream['full_name']) ?></div>
                <div style="color: var(--gray-color);">В прямом эфире с <?= time_elapsed_string($stream['started_at']) ?></div>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <h2 style="font-size: 1.2rem; margin-bottom: 10px;"><?= htmlspecialchars($stream['title']) ?></h2>
            <p><?= htmlspecialchars($stream['description']) ?></p>
        </div>
    </div>
    
    <!-- Чат стрима -->
    <div class="feed" style="margin-top: 30px;">
        <h2 style="font-size: 1.2rem; margin-bottom: 20px;">Чат трансляции</h2>
        
        <div id="stream-chat" style="height: 400px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-bottom: 15px; background-color: #f8f9fa;">
            <?php if ($user): ?>
                <!-- Сообщения чата будут загружаться здесь -->
            <?php else: ?>
                <div style="text-align: center; padding: 50px 0; color: var(--gray-color);">
                    <i class="fas fa-sign-in-alt" style="font-size: 2rem; margin-bottom: 15px;"></i>
                    <div>Войдите, чтобы участвовать в чате</div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($user): ?>
            <form id="stream-chat-form" style="display: flex;">
                <input type="text" name="message" placeholder="Написать сообщение..." style="flex-grow: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid #ddd; outline: none;" autocomplete="off">
                <button type="submit" style="background: none; border: none; cursor: pointer; margin-left: 10px;">
                    <i class="fas fa-paper-plane" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                </button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
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
    
    <?php if ($user): ?>
    // Обработка чата
    const chatForm = document.getElementById('stream-chat-form');
    const chatMessages = document.getElementById('stream-chat');
    
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const messageInput = this.querySelector('input[name="message"]');
        const message = messageInput.value.trim();
        
        if (message) {
            fetch('/actions/send_stream_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    stream_id: <?= $stream['id'] ?>,
                    message: message 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Добавляем сообщение в чат
                    const messageElement = document.createElement('div');
                    messageElement.style.marginBottom = '15px';
                    messageElement.style.display = 'flex';
                    
                    messageElement.innerHTML = `
                        <img src="https://randomuser.me/api/portraits/<?= $user['avatar'] ?>" alt="User" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                        <div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($user['full_name']) ?></div>
                            <div>${message}</div>
                            <div style="font-size: 0.8rem; color: var(--gray-color);">Только что</div>
                        </div>
                    `;
                    
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    messageInput.value = '';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>