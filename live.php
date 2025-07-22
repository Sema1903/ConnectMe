<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
if (!$user) {
    header('Location: login.php');
    exit;
}

// Получаем активные трансляции
$streams = $db->query("SELECT * FROM live_streams WHERE is_live = 1");

// Проверяем активный стрим пользователя
$stmt = $db->prepare("SELECT * FROM live_streams WHERE user_id = ? AND is_live = 1");
$stmt->bindValue(1, $user['id'], SQLITE3_INTEGER);
$user_stream = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прямые трансляции</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .stream-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 */
            background: #000;
            margin-bottom: 15px;
            border-radius: 4px;
            overflow: hidden;
        }
        video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background: #4285f4;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .stream-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stream-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stream-thumbnail {
            position: relative;
            padding-bottom: 56.25%;
            background: #222;
        }
        .live-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: red;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input, textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($user_stream): ?>
            <div class="stream-section">
                <h1>Ваш прямой эфир</h1>
                <div class="video-container">
                    <video id="streamPreview" autoplay muted playsinline></video>
                </div>
                <div>
                    <h2><?= htmlspecialchars($user_stream['title']) ?></h2>
                    <p><?= htmlspecialchars($user_stream['description']) ?></p>
                    <button id="stopStream" class="btn btn-danger">Завершить трансляцию</button>
                </div>
            </div>
        <?php else: ?>
            <div class="stream-section">
                <h1>Начать трансляцию</h1>
                <form id="startStreamForm">
                    <div>
                        <label>Название трансляции</label>
                        <input type="text" name="title" required>
                    </div>
                    <div>
                        <label>Описание</label>
                        <textarea name="description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Начать трансляцию</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="stream-section">
            <h1>Активные трансляции</h1>
            <?php if ($streams->fetchArray()): ?>
                <div class="stream-grid">
                    <?php 
                    $streams->reset(); // Сброс указателя для повторного чтения
                    while ($stream = $streams->fetchArray(SQLITE3_ASSOC)): 
                        $streamer = getUserById($db, $stream['user_id']);
                    ?>
                        <a href="watch.php?id=<?= $stream['id'] ?>" class="stream-card">
                            <div class="stream-thumbnail">
                                <div class="live-badge">LIVE</div>
                            </div>
                            <div style="padding: 15px;">
                                <h3><?= htmlspecialchars($stream['title']) ?></h3>
                                <p><?= htmlspecialchars($streamer['username'] ?? 'Unknown') ?></p>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>Сейчас нет активных трансляций</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Обработка формы начала трансляции
        document.getElementById('startStreamForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                title: this.title.value,
                description: this.description.value
            };
            
            try {
                const response = await fetch('actions/start_stream.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Ошибка при запуске трансляции');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения');
            }
        });

        // Обработка кнопки завершения трансляции
        document.getElementById('stopStream')?.addEventListener('click', async function() {
            if (confirm('Вы уверены, что хотите завершить трансляцию?')) {
                try {
                    const response = await fetch('actions/stop_stream.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Ошибка при завершении трансляции');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Ошибка соединения');
                }
            }
        });

        // Инициализация трансляции (для стримера)
        <?php if ($user_stream): ?>
        async function initStream() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                const videoElement = document.getElementById('streamPreview');
                videoElement.srcObject = stream;
                
                console.log('Трансляция начата с ключом:', '<?= $user_stream['stream_key'] ?>');
                
                // В реальном приложении здесь будет отправка на RTMP сервер
                // Например: new RTMPPublisher(stream, 'rtmp://yourserver/live/<?= $user_stream['stream_key'] ?>');
                
            } catch (error) {
                console.error('Ошибка доступа к медиаустройствам:', error);
                alert('Не удалось получить доступ к камере/микрофону');
            }
        }
        
        // Проверяем поддержку getUserMedia
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            initStream();
        } else {
            alert('Ваш браузер не поддерживает доступ к камере');
        }
        <?php endif; ?>
    </script>
</body>
</html>