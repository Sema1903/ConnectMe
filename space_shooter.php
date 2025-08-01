<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Космический стрелок | ConnectMe";
include 'includes/header.php';
?>

<div class="space-game-container">
    <h1><i class="fas fa-rocket"></i> Космический стрелок</h1>
    
    <div class="game-info">
        <div class="stats">
            <div class="stat"><i class="fas fa-star"></i> <span id="score">0</span></div>
            <div class="stat"><i class="fas fa-heart"></i> <span id="lives">3</span></div>
            <div class="stat"><i class="fas fa-clock"></i> <span id="time">0</span>s</div>
        </div>
        <div class="controls">
            <button id="pause-btn" class="btn-control"><i class="fas fa-pause"></i></button>
            <button id="restart-btn" class="btn-control"><i class="fas fa-redo"></i></button>
        </div>
    </div>
    
    <div class="game-wrapper">
        <canvas id="game-canvas" width="800" height="500"></canvas>
        <div class="game-overlay" id="game-overlay">
            <div class="overlay-content">
                <h2 id="result-title">Game Over</h2>
                <p id="result-text"></p>
                <button id="play-again-btn" class="btn-play-again"><i class="fas fa-play"></i> Играть снова</button>
            </div>
        </div>
    </div>
    
    <!-- Мобильное управление -->
    <div class="mobile-controls">
        <div class="control-row">
            <button class="control-btn left" id="mobile-left"><i class="fas fa-arrow-left"></i></button>
            <button class="control-btn up" id="mobile-up"><i class="fas fa-arrow-up"></i></button>
            <button class="control-btn right" id="mobile-right"><i class="fas fa-arrow-right"></i></button>
        </div>
        <div class="control-row">
            <button class="control-btn shoot" id="mobile-shoot"><i class="fas fa-bullseye"></i> Стрелять</button>
        </div>
    </div>
</div>

<script>
// Конфигурация игры
const config = {
    playerSpeed: 5,
    bulletSpeed: 7,
    enemySpeed: 3,
    enemySpawnRate: 2000,
    playerSize: 50,
    bulletSize: 5,
    enemySize: 40
};

// Состояние игры
let gameState = {
    player: { x: 0, y: 0 },
    bullets: [],
    enemies: [],
    lastEnemySpawn: 0,
    score: 0,
    lives: 3,
    gameTime: 0,
    isRunning: false,
    isPaused: false,
    keys: {
        ArrowUp: false,
        ArrowDown: false,
        ArrowLeft: false,
        ArrowRight: false,
        ' ': false
    }
};

// Элементы DOM
const canvas = document.getElementById('game-canvas');
const ctx = canvas.getContext('2d');
const scoreElement = document.getElementById('score');
const livesElement = document.getElementById('lives');
const timeElement = document.getElementById('time');
const pauseBtn = document.getElementById('pause-btn');
const restartBtn = document.getElementById('restart-btn');
const gameOverlay = document.getElementById('game-overlay');
const resultTitle = document.getElementById('result-title');
const resultText = document.getElementById('result-text');
const playAgainBtn = document.getElementById('play-again-btn');

// Кнопки мобильного управления
const mobileUp = document.getElementById('mobile-up');
const mobileDown = document.getElementById('mobile-down');
const mobileLeft = document.getElementById('mobile-left');
const mobileRight = document.getElementById('mobile-right');
const mobileShoot = document.getElementById('mobile-shoot');

// Инициализация игры
function initGame() {
    // Сброс состояния
    gameState = {
        player: { 
            x: canvas.width / 2 - config.playerSize / 2, 
            y: canvas.height - config.playerSize - 20 
        },
        bullets: [],
        enemies: [],
        lastEnemySpawn: 0,
        score: 0,
        lives: 3,
        gameTime: 0,
        isRunning: true,
        isPaused: false,
        keys: {
            ArrowUp: false,
            ArrowDown: false,
            ArrowLeft: false,
            ArrowRight: false,
            ' ': false
        }
    };
    
    // Обновление UI
    updateUI();
    gameOverlay.style.display = 'none';
    pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
    
    // Запуск игрового цикла
    requestAnimationFrame(gameLoop);
}

// Игровой цикл
function gameLoop(timestamp) {
    if (!gameState.isRunning || gameState.isPaused) return;
    
    // Очистка холста
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Обновление состояния
    update(timestamp);
    
    // Отрисовка
    draw();
    
    // Продолжение цикла
    requestAnimationFrame(gameLoop);
}

// Обновление состояния игры
function update(timestamp) {
    // Обновление времени
    gameState.gameTime = Math.floor(timestamp / 1000);
    timeElement.textContent = gameState.gameTime;
    
    // Движение игрока
    if ((gameState.keys.ArrowLeft || gameState.keys.mobileLeft) && gameState.player.x > 0) {
        gameState.player.x -= config.playerSpeed;
    }
    if ((gameState.keys.ArrowRight || gameState.keys.mobileRight) && gameState.player.x < canvas.width - config.playerSize) {
        gameState.player.x += config.playerSpeed;
    }
    if ((gameState.keys.ArrowUp || gameState.keys.mobileUp) && gameState.player.y > 0) {
        gameState.player.y -= config.playerSpeed;
    }
    if ((gameState.keys.ArrowDown || gameState.keys.mobileDown) && gameState.player.y < canvas.height - config.playerSize) {
        gameState.player.y += config.playerSpeed;
    }
    
    // Стрельба
    if ((gameState.keys[' '] || gameState.keys.mobileShoot) && timestamp - (gameState.lastShot || 0) > 300) {
        shoot();
        gameState.lastShot = timestamp;
    }
    
    // Спавн врагов
    if (timestamp - gameState.lastEnemySpawn > config.enemySpawnRate) {
        spawnEnemy();
        gameState.lastEnemySpawn = timestamp;
    }
    
    // Движение пуль
    gameState.bullets.forEach(bullet => {
        bullet.y -= config.bulletSpeed;
    });
    gameState.bullets = gameState.bullets.filter(b => b.y > 0);
    
    // Движение врагов
    gameState.enemies.forEach(enemy => {
        enemy.y += config.enemySpeed;
    });
    gameState.enemies = gameState.enemies.filter(e => e.y < canvas.height);
    
    // Проверка столкновений
    checkCollisions();
}

// Отрисовка игры
function draw() {
    // Фон
    ctx.fillStyle = '#000033';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    // Игрок (синий треугольник)
    ctx.fillStyle = '#3498db';
    ctx.beginPath();
    ctx.moveTo(gameState.player.x + config.playerSize / 2, gameState.player.y);
    ctx.lineTo(gameState.player.x, gameState.player.y + config.playerSize);
    ctx.lineTo(gameState.player.x + config.playerSize, gameState.player.y + config.playerSize);
    ctx.closePath();
    ctx.fill();
    
    // Пули (желтые прямоугольники)
    ctx.fillStyle = '#f1c40f';
    gameState.bullets.forEach(bullet => {
        ctx.fillRect(bullet.x, bullet.y, config.bulletSize, config.bulletSize * 3);
    });
    
    // Враги (красные треугольники)
    ctx.fillStyle = '#e74c3c';
    gameState.enemies.forEach(enemy => {
        ctx.beginPath();
        ctx.moveTo(enemy.x + config.enemySize / 2, enemy.y + config.enemySize);
        ctx.lineTo(enemy.x, enemy.y);
        ctx.lineTo(enemy.x + config.enemySize, enemy.y);
        ctx.closePath();
        ctx.fill();
    });
}

// Выстрел
function shoot() {
    gameState.bullets.push({
        x: gameState.player.x + config.playerSize / 2 - config.bulletSize / 2,
        y: gameState.player.y
    });
}

// Спавн врага
function spawnEnemy() {
    gameState.enemies.push({
        x: Math.random() * (canvas.width - config.enemySize),
        y: -config.enemySize,
        width: config.enemySize,
        height: config.enemySize
    });
}

// Проверка столкновений
function checkCollisions() {
    // Пули с врагами
    gameState.bullets.forEach((bullet, bIndex) => {
        gameState.enemies.forEach((enemy, eIndex) => {
            if (
                bullet.x < enemy.x + config.enemySize &&
                bullet.x + config.bulletSize > enemy.x &&
                bullet.y < enemy.y + config.enemySize &&
                bullet.y + config.bulletSize * 3 > enemy.y
            ) {
                // Удаляем пулю и врага
                gameState.bullets.splice(bIndex, 1);
                gameState.enemies.splice(eIndex, 1);
                
                // Увеличиваем счет
                gameState.score += 10;
                updateUI();
            }
        });
    });
    
    // Игрок с врагами
    gameState.enemies.forEach(enemy => {
        if (
            gameState.player.x < enemy.x + config.enemySize &&
            gameState.player.x + config.playerSize > enemy.x &&
            gameState.player.y < enemy.y + config.enemySize &&
            gameState.player.y + config.playerSize > enemy.y
        ) {
            // Уменьшаем жизни
            gameState.lives--;
            updateUI();
            
            // Удаляем врага
            gameState.enemies.splice(gameState.enemies.indexOf(enemy), 1);
            
            // Проверка окончания игры
            if (gameState.lives <= 0) {
                endGame();
            }
        }
    });
}

// Обновление UI
function updateUI() {
    scoreElement.textContent = gameState.score;
    livesElement.textContent = gameState.lives;
}

// Окончание игры
function endGame() {
    gameState.isRunning = false;
    
    resultTitle.textContent = 'Игра окончена';
    resultText.textContent = `Счет: ${gameState.score} | Время: ${gameState.gameTime} сек`;
    gameOverlay.style.display = 'flex';
    
    <?php if (isLoggedIn()): ?>
        fetch('api/save_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                game_id: 'space_shooter',
                score: gameState.score
            })
        });
    <?php endif; ?>
}

// Обработчики событий клавиатуры
document.addEventListener('keydown', (e) => {
    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' '].includes(e.key)) {
        gameState.keys[e.key] = true;
        e.preventDefault();
        
        // Подсветка кнопок
        if (e.key === 'ArrowLeft') document.getElementById('key-left').classList.add('active');
        if (e.key === 'ArrowRight') document.getElementById('key-right').classList.add('active');
        if (e.key === 'ArrowUp') document.getElementById('key-up').classList.add('active');
        if (e.key === ' ') document.getElementById('key-space').classList.add('active');
    }
});

document.addEventListener('keyup', (e) => {
    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', ' '].includes(e.key)) {
        gameState.keys[e.key] = false;
        
        // Снятие подсветки
        if (e.key === 'ArrowLeft') document.getElementById('key-left').classList.remove('active');
        if (e.key === 'ArrowRight') document.getElementById('key-right').classList.remove('active');
        if (e.key === 'ArrowUp') document.getElementById('key-up').classList.remove('active');
        if (e.key === ' ') document.getElementById('key-space').classList.remove('active');
    }
});

// Обработчики мобильного управления
mobileLeft.addEventListener('touchstart', (e) => {
    e.preventDefault();
    gameState.keys.mobileLeft = true;
    mobileLeft.classList.add('active');
});

mobileLeft.addEventListener('touchend', (e) => {
    e.preventDefault();
    gameState.keys.mobileLeft = false;
    mobileLeft.classList.remove('active');
});

mobileRight.addEventListener('touchstart', (e) => {
    e.preventDefault();
    gameState.keys.mobileRight = true;
    mobileRight.classList.add('active');
});

mobileRight.addEventListener('touchend', (e) => {
    e.preventDefault();
    gameState.keys.mobileRight = false;
    mobileRight.classList.remove('active');
});

mobileUp.addEventListener('touchstart', (e) => {
    e.preventDefault();
    gameState.keys.mobileUp = true;
    mobileUp.classList.add('active');
});

mobileUp.addEventListener('touchend', (e) => {
    e.preventDefault();
    gameState.keys.mobileUp = false;
    mobileUp.classList.remove('active');
});

mobileShoot.addEventListener('touchstart', (e) => {
    e.preventDefault();
    gameState.keys.mobileShoot = true;
    mobileShoot.classList.add('active');
});

mobileShoot.addEventListener('touchend', (e) => {
    e.preventDefault();
    gameState.keys.mobileShoot = false;
    mobileShoot.classList.remove('active');
});

// Обработчики для мыши (на случай, если пользователь на ПК захочет использовать кнопки)
mobileLeft.addEventListener('mousedown', (e) => {
    e.preventDefault();
    gameState.keys.mobileLeft = true;
    mobileLeft.classList.add('active');
});

mobileLeft.addEventListener('mouseup', (e) => {
    e.preventDefault();
    gameState.keys.mobileLeft = false;
    mobileLeft.classList.remove('active');
});

mobileRight.addEventListener('mousedown', (e) => {
    e.preventDefault();
    gameState.keys.mobileRight = true;
    mobileRight.classList.add('active');
});

mobileRight.addEventListener('mouseup', (e) => {
    e.preventDefault();
    gameState.keys.mobileRight = false;
    mobileRight.classList.remove('active');
});

mobileUp.addEventListener('mousedown', (e) => {
    e.preventDefault();
    gameState.keys.mobileUp = true;
    mobileUp.classList.add('active');
});

mobileUp.addEventListener('mouseup', (e) => {
    e.preventDefault();
    gameState.keys.mobileUp = false;
    mobileUp.classList.remove('active');
});

mobileShoot.addEventListener('mousedown', (e) => {
    e.preventDefault();
    gameState.keys.mobileShoot = true;
    mobileShoot.classList.add('active');
});

mobileShoot.addEventListener('mouseup', (e) => {
    e.preventDefault();
    gameState.keys.mobileShoot = false;
    mobileShoot.classList.remove('active');
});

pauseBtn.addEventListener('click', () => {
    if (!gameState.isRunning) return;
    
    gameState.isPaused = !gameState.isPaused;
    pauseBtn.innerHTML = gameState.isPaused ? '<i class="fas fa-play"></i>' : '<i class="fas fa-pause"></i>';
    
    if (!gameState.isPaused) {
        requestAnimationFrame(gameLoop);
    }
});

restartBtn.addEventListener('click', initGame);
playAgainBtn.addEventListener('click', initGame);

// Начало игры
initGame();
</script>

<style>
.space-game-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #0a0a1a;
    border-radius: 10px;
    box-shadow: 0 0 30px rgba(0, 150, 255, 0.3);
    color: #fff;
    font-family: 'Arial', sans-serif;
}

.game-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(0, 20, 40, 0.7);
    border-radius: 8px;
    border: 1px solid #1a3a5a;
}

.stats {
    display: flex;
    gap: 25px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    color: #4fc3f7;
}

.controls {
    display: flex;
    gap: 12px;
}

.btn-control {
    background: linear-gradient(145deg, #1a3a5a, #0d2b45);
    color: #4fc3f7;
    border: none;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    transition: all 0.2s;
}

.btn-control:hover {
    background: linear-gradient(145deg, #2a4a6a, #1d3b55);
    transform: scale(1.05);
}

.game-wrapper {
    position: relative;
    margin-bottom: 20px;
}

#game-canvas {
    background: #000;
    border-radius: 8px;
    display: block;
    margin: 0 auto;
    border: 2px solid #1a3a5a;
    box-shadow: 0 0 20px rgba(0, 100, 255, 0.2);
}

.game-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 10, 30, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100;
    border-radius: 6px;
}

.overlay-content {
    background: linear-gradient(145deg, #0d2b45, #1a3a5a);
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
    border: 1px solid #2a4a6a;
}

.overlay-content h2 {
    color: #4fc3f7;
    margin-top: 0;
    font-size: 32px;
    text-shadow: 0 0 10px rgba(79, 195, 247, 0.5);
}

.btn-play-again {
    background: linear-gradient(145deg, #00c853, #00e676);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 30px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 10px rgba(0, 200, 83, 0.3);
    transition: all 0.2s;
}

.btn-play-again:hover {
    background: linear-gradient(145deg, #00e676, #00c853);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 200, 83, 0.4);
}

/* Мобильное управление */
.mobile-controls {
    display: none; /* По умолчанию скрыто для десктопов */
    margin-top: 20px;
    padding: 15px;
    background: rgba(0, 20, 40, 0.7);
    border-radius: 8px;
    border: 1px solid #1a3a5a;
}

.control-row {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 15px;
}

.control-btn {
    width: 70px;
    height: 70px;
    background: linear-gradient(145deg, #1a3a5a, #0d2b45);
    border: none;
    border-radius: 50%;
    color: #4fc3f7;
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    transition: all 0.1s;
    user-select: none;
}

.control-btn.shoot {
    width: 180px;
    height: 60px;
    border-radius: 30px;
    font-size: 18px;
    gap: 8px;
}

.control-btn.active {
    background: linear-gradient(145deg, #2a4a6a, #1d3b55);
    transform: scale(0.95);
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    color: #80d8ff;
}

@media (max-width: 768px) {
    .game-info {
        flex-direction: column;
        gap: 15px;
    }
    
    .stats {
        width: 100%;
        justify-content: space-between;
    }
    
    #game-canvas {
        width: 100%;
        height: auto;
    }
    
    .mobile-controls {
        display: flex;
        flex-direction: column;
    }
    
    .control-btn {
        width: 60px;
        height: 60px;
        font-size: 20px;
    }
    
    .control-btn.shoot {
        width: 160px;
        height: 50px;
        font-size: 16px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>