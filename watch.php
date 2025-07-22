<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$stream_id = $_GET['id'] ?? 0;
$stmt = $db->prepare("SELECT * FROM live_streams WHERE id = ? AND is_live = 1");
$stmt->bindValue(1, $stream_id, SQLITE3_INTEGER);
$stream = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$stream) {
    header('Location: live.php');
    exit;
}

$streamer = getUserById($db, $stream['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($stream['title']) ?></title>
    <style>
        .watch-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            background: #000;
        }
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .streamer-info {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        .streamer-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="watch-container">
        <div class="stream-viewer">
            <h1><?= htmlspecialchars($stream['title']) ?></h1>
            <div class="video-container">
                <video id="streamPlayer" controls autoplay></video>
            </div>
            <div class="streamer-info">
                <img src="assets/avatars/<?= $streamer['avatar'] ?>" alt="<?= htmlspecialchars($streamer['username']) ?>">
                <h2><?= htmlspecialchars($streamer['username']) ?></h2>
            </div>
            <p><?= htmlspecialchars($stream['description']) ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script>
        // Инициализация плеера для зрителя
        function initPlayer() {
            const video = document.getElementById('streamPlayer');
            
            // В реальной системе здесь должен быть URL вашего HLS потока
            // Например: `http://your-server.com/live/${streamKey}.m3u8`
            const streamUrl = `<?= $stream['stream_key'] ?>.m3u8`;
            
            if (Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(streamUrl);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, () => video.play());
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = streamUrl;
                video.addEventListener('loadedmetadata', () => video.play());
            }
        }
        
        initPlayer();
    </script>
</body>
</html>