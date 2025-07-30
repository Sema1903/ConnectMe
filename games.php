<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Игровая зона | ConnectMe";
include 'includes/header.php';

$currentUser = getCurrentUser($db);
?>

<div class="games-container">
    <h1><i class="fas fa-gamepad"></i> Игровая зона</h1>

<?php 
$games = [
    [
        'id' => 'space_shooter',
        'title' => 'Космический стрелок',
        'description' => 'Уничтожайте вражеские корабли и собирайте бонусы',
        'icon' => 'fas fa-space-shuttle',
        'color' => 'blue', 
        'href' => 'space_shooter.php'
    ],
    [
        'id' => 'memory_game',
        'title' => 'Игра на память',
        'description' => 'Найдите все пары карточек за минимальное время',
        'icon' => 'fas fa-brain',
        'color' => 'green',
        'href' => 'memory_game.php'
    ],
    [
        'id' => 'snake',
        'title' => 'Змейка',
        'description' => 'Классическая змейка с новыми возможностями',
        'icon' => 'fas fa-snake',
        'color' => 'red',
        'href' => 'snake.php'
    ],
    [
        'id' => 'quiz',
        'title' => 'Викторина',
        'description' => 'Проверьте свои знания в различных темах',
        'icon' => 'fas fa-question-circle',
        'color' => 'purple',
        'href' => 'quiz.php'
    ]
];
?>

    <!-- Доступные игры -->
    <div class="game-section">
        <h2><i class="fas fa-joystick"></i> Выберите игру</h2>
        <div class="games-grid">
            <?php foreach ($games as $game): ?>
                <div class="game-card game-card-<?= $game['color'] ?>">
                    <div class="game-icon">
                        <i class="<?= $game['icon'] ?>"></i>
                    </div>
                    <div class="game-info">
                        <h3><?= $game['title'] ?></h3>
                        <p><?= $game['description'] ?></p>
                        <a href="<?= $game['href'] ?>" class="btn btn-play">
                            Играть <i class="fas fa-play"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<!-- Модальное окно для игры -->
<div id="gameModal" class="modal">
    <div class="modal-content game-modal-content">
        <span class="close-modal">&times;</span>
        <div class="game-frame-container">
            <iframe id="gameFrame" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<style>
/* Геймерский стиль */
.games-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background-color: #1a1a2e;
    color: #fff;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    font-family: 'Arial', sans-serif;
}

.games-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #4e4e6d;
}

.games-header h1 {
    color: #f1c40f;
    font-size: 2.2em;
    margin: 0;
    text-shadow: 0 0 10px rgba(241, 196, 15, 0.5);
}

.game-section {
    margin-bottom: 40px;
    background: rgba(26, 32, 44, 0.7);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #4e4e6d;
}

.game-section h2 {
    color: #f1c40f;
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #4e4e6d;
    font-size: 1.5em;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.game-card {
    background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
    border-radius: 10px;
    padding: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
    overflow: hidden;
    border: 1px solid #4e4e6d;
}

.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.game-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #f1c40f, #e74c3c, #9b59b6, #3498db);
}

.game-card-blue::before { background: #3498db; }
.game-card-green::before { background: #2ecc71; }
.game-card-red::before { background: #e74c3c; }
.game-card-purple::before { background: #9b59b6; }

.game-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
    color: #f1c40f;
}

.game-info h3 {
    margin: 0 0 10px 0;
    color: #fff;
    font-size: 1.3em;
}

.game-info p {
    color: #b8c2cc;
    margin-bottom: 20px;
    font-size: 0.9em;
}

.btn-play {
    display: inline-block;
    background: #f1c40f;
    color: #1a1a2e;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-play:hover {
    background: #f39c12;
    color: #fff;
}

/* Модальное окно для игры */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
}

.game-modal-content {
    background-color: #1a1a2e;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #4e4e6d;
    width: 80%;
    max-width: 800px;
    border-radius: 10px;
    position: relative;
}

.close-modal {
    color: #aaa;
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #fff;
}

.game-frame-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    height: 0;
    overflow: hidden;
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

/* Адаптивность */
@media (max-width: 768px) {
    .games-grid {
        grid-template-columns: 1fr;
    }
    
    .game-modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>

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

<?php include 'includes/footer.php'; ?>