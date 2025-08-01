<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Змейка | ConnectMe";
include 'includes/header.php';
?>

<div class="snake-game-container">
    <h1><i class="fas fa-snake"></i> Змейка</h1>
    
    <div class="game-info">
        <div class="stats">
            <div class="stat"><i class="fas fa-star"></i> <span id="score">0</span></div>
            <div class="stat"><i class="fas fa-tachometer-alt"></i> <span id="speed">1</span>x</div>
            <div class="stat"><i class="fas fa-clock"></i> <span id="time">0</span> сек</div>
        </div>
        <div class="controls">
            <button id="pause-btn" class="btn-control"><i class="fas fa-pause"></i></button>
            <button id="restart-btn" class="btn-control"><i class="fas fa-redo"></i></button>
        </div>
    </div>
    
    <div class="game-wrapper">
        <canvas id="game-canvas" width="600" height="400"></canvas>
        <div class="game-overlay" id="game-overlay">
            <div class="overlay-content">
                <h2 id="result-title">Game Over</h2>
                <p id="result-text"></p>
                <button id="play-again-btn" class="btn-play-again"><i class="fas fa-play"></i> Играть снова</button>
            </div>
        </div>
    </div>
    
    <div class="mobile-controls">
        <div class="control-row">
            <button class="control-btn up" id="mobile-up"><i class="fas fa-arrow-up"></i></button>
        </div>
        <div class="control-row">
            <button class="control-btn left" id="mobile-left"><i class="fas fa-arrow-left"></i></button>
            <button class="control-btn down" id="mobile-down"><i class="fas fa-arrow-down"></i></button>
            <button class="control-btn right" id="mobile-right"><i class="fas fa-arrow-right"></i></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Конфигурация игры
    const config = {
        canvasWidth: 600,
        canvasHeight: 400,
        gridSize: 20,
        initialSpeed: 800,
        minSpeed: 400,
        speedStep: 2,
        speedChange: 0.985
    };
    
    // Элементы DOM
    const canvas = document.getElementById('game-canvas');
    const ctx = canvas.getContext('2d');
    const scoreElement = document.getElementById('score');
    const speedElement = document.getElementById('speed');
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
    
    // Состояние игры
    let snake = [];
    let food = {};
    let direction = 'right';
    let nextDirection = 'right';
    let gameLoopId;
    let score = 0;
    let speed = 1;
    let gameTime = 0;
    let lastMoveTime = 0;
    let currentSpeed = config.initialSpeed;
    let timer;
    let gameActive = false;
    let gamePaused = false;
    let speedLevel = 1;
    
    // Инициализация игры
    function initGame() {
        // Сброс состояния
        clearInterval(timer);
        cancelAnimationFrame(gameLoopId);
        
        snake = [
            {x: 5 * config.gridSize, y: 10 * config.gridSize},
            {x: 4 * config.gridSize, y: 10 * config.gridSize},
            {x: 3 * config.gridSize, y: 10 * config.gridSize}
        ];
        
        direction = 'right';
        nextDirection = 'right';
        score = 0;
        speedLevel = 1;
        gameTime = 0;
        gameActive = true;
        gamePaused = false;
        
        // Создание еды
        createFood();
        
        // Обновление UI
        updateUI();
        gameOverlay.style.display = 'none';
        pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
        
        // Запуск таймера
        timer = setInterval(() => {
            if (!gamePaused && gameActive) {
                gameTime++;
                timeElement.textContent = gameTime;
            }
        }, 1000);
        
        // Запуск игрового цикла
        gameLoopId = requestAnimationFrame(gameLoop);
    }
    
    // Игровой цикл
    function gameLoop(timestamp) {
        if (!gameActive || gamePaused) return;
        
        // Очистка холста
        ctx.clearRect(0, 0, config.canvasWidth, config.canvasHeight);
        
        // Отрисовка сетки
        drawGrid();
        
        // Отрисовка змейки
        drawSnake();
        
        // Отрисовка еды
        drawFood();
        
        // Перемещение змейки
        moveSnake(timestamp);
        
        // Проверка столкновений
        checkCollisions();
        
        // Продолжение цикла
        gameLoopId = requestAnimationFrame(gameLoop);
    }
    
    // Отрисовка сетки
    function drawGrid() {
        ctx.strokeStyle = '#2c3e50';
        ctx.lineWidth = 0.5;
        
        // Вертикальные линии
        for (let x = 0; x <= config.canvasWidth; x += config.gridSize) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, config.canvasHeight);
            ctx.stroke();
        }
        
        // Горизонтальные линии
        for (let y = 0; y <= config.canvasHeight; y += config.gridSize) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(config.canvasWidth, y);
            ctx.stroke();
        }
    }
    
    // Отрисовка змейки
    function drawSnake() {
        // Рисуем тело
        snake.forEach((segment, index) => {
            if (index === 0) {
                // Голова
                ctx.fillStyle = '#2ecc71';
                ctx.strokeStyle = '#27ae60';
            } else {
                // Тело
                ctx.fillStyle = '#3498db';
                ctx.strokeStyle = '#2980b9';
            }
            
            ctx.beginPath();
            ctx.roundRect(
                segment.x, 
                segment.y, 
                config.gridSize, 
                config.gridSize, 
                5
            );
            ctx.fill();
            ctx.stroke();
            
            // Глаза у головы
            if (index === 0) {
                ctx.fillStyle = 'white';
                const eyeSize = config.gridSize / 5;
                
                // Позиция глаз зависит от направления
                let leftEyeX, leftEyeY, rightEyeX, rightEyeY;
                
                switch(direction) {
                    case 'up':
                        leftEyeX = segment.x + config.gridSize * 0.25;
                        leftEyeY = segment.y + config.gridSize * 0.25;
                        rightEyeX = segment.x + config.gridSize * 0.75;
                        rightEyeY = segment.y + config.gridSize * 0.25;
                        break;
                    case 'down':
                        leftEyeX = segment.x + config.gridSize * 0.25;
                        leftEyeY = segment.y + config.gridSize * 0.75;
                        rightEyeX = segment.x + config.gridSize * 0.75;
                        rightEyeY = segment.y + config.gridSize * 0.75;
                        break;
                    case 'left':
                        leftEyeX = segment.x + config.gridSize * 0.25;
                        leftEyeY = segment.y + config.gridSize * 0.25;
                        rightEyeX = segment.x + config.gridSize * 0.25;
                        rightEyeY = segment.y + config.gridSize * 0.75;
                        break;
                    case 'right':
                        leftEyeX = segment.x + config.gridSize * 0.75;
                        leftEyeY = segment.y + config.gridSize * 0.25;
                        rightEyeX = segment.x + config.gridSize * 0.75;
                        rightEyeY = segment.y + config.gridSize * 0.75;
                        break;
                }
                
                ctx.beginPath();
                ctx.arc(leftEyeX, leftEyeY, eyeSize, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.beginPath();
                ctx.arc(rightEyeX, rightEyeY, eyeSize, 0, Math.PI * 2);
                ctx.fill();
            }
        });
    }
    
    // Отрисовка еды
    function drawFood() {
        ctx.fillStyle = '#e74c3c';
        ctx.strokeStyle = '#c0392b';
        
        ctx.beginPath();
        ctx.roundRect(
            food.x, 
            food.y, 
            config.gridSize, 
            config.gridSize, 
            50
        );
        ctx.fill();
        ctx.stroke();
    }
    
    // Создание еды
    function createFood() {
        const maxX = Math.floor(config.canvasWidth / config.gridSize) - 1;
        const maxY = Math.floor(config.canvasHeight / config.gridSize) - 1;
        
        let foodX, foodY;
        let validPosition = false;
        
        while (!validPosition) {
            foodX = Math.floor(Math.random() * maxX) * config.gridSize;
            foodY = Math.floor(Math.random() * maxY) * config.gridSize;
            
            validPosition = true;
            
            // Проверяем, чтобы еда не появилась на змейке
            for (const segment of snake) {
                if (segment.x === foodX && segment.y === foodY) {
                    validPosition = false;
                    break;
                }
            }
        }
        
        food = {x: foodX, y: foodY};
    }
    
    // Перемещение змейки
    function moveSnake(timestamp) {
        if (!gameActive || gamePaused) return;
        
        // Движение только по истечении интервала
        if (timestamp - lastMoveTime < currentSpeed) return;
        lastMoveTime = timestamp;
        
        // Обновляем направление
        direction = nextDirection;
        const head = {x: snake[0].x, y: snake[0].y};
        
        // Перемещаем голову
        switch(direction) {
            case 'up': head.y -= config.gridSize; break;
            case 'down': head.y += config.gridSize; break;
            case 'left': head.x -= config.gridSize; break;
            case 'right': head.x += config.gridSize; break;
        }
        
        snake.unshift(head);
        
        // Проверка съедания еды
        if (head.x === food.x && head.y === food.y) {
            score += 10;
            currentSpeed = Math.max(
                config.minSpeed,
                currentSpeed * config.speedChange - config.speedStep
            );
            speedLevel = (config.initialSpeed - currentSpeed) / 80 + 1;
            createFood();
            updateUI();
        } else {
            snake.pop();
        }
    }
    
    // Проверка столкновений
    function checkCollisions() {
        const head = snake[0];
        
        // Столкновение со стенами
        if (
            head.x < 0 || 
            head.y < 0 || 
            head.x >= config.canvasWidth || 
            head.y >= config.canvasHeight
        ) {
            endGame();
            return;
        }
        
        // Столкновение с собой
        for (let i = 1; i < snake.length; i++) {
            if (head.x === snake[i].x && head.y === snake[i].y) {
                endGame();
                return;
            }
        }
    }
    
    // Конец игры
    function endGame() {
        gameActive = false;
        clearInterval(timer);
        cancelAnimationFrame(gameLoopId);
        
        // Показываем оверлей
        resultTitle.textContent = 'Игра окончена';
        resultText.textContent = `Счет: ${score} | Время: ${gameTime} сек`;
        gameOverlay.style.display = 'flex';
        
        <?php if (isLoggedIn()): ?>
            // Сохраняем результат
            fetch('api/save_score.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    game_id: 'snake',
                    score: score
                })
            });
        <?php endif; ?>
    }
    
    // Обновление UI
    function updateUI() {
        scoreElement.textContent = score;
        speedElement.textContent = speedLevel.toFixed(1);
    }
    
    // Обработка нажатий клавиш
    document.addEventListener('keydown', function(e) {
        if (!gameActive || gamePaused) return;
        
        switch(e.key) {
            case 'ArrowUp':
                if (direction !== 'down') nextDirection = 'up';
                break;
            case 'ArrowDown':
                if (direction !== 'up') nextDirection = 'down';
                break;
            case 'ArrowLeft':
                if (direction !== 'right') nextDirection = 'left';
                break;
            case 'ArrowRight':
                if (direction !== 'left') nextDirection = 'right';
                break;
        }
    });
    
    // Обработка нажатий мобильных кнопок
    mobileUp.addEventListener('click', function() {
        if (!gameActive || gamePaused) return;
        if (direction !== 'down') nextDirection = 'up';
        highlightButton(mobileUp);
    });
    
    mobileDown.addEventListener('click', function() {
        if (!gameActive || gamePaused) return;
        if (direction !== 'up') nextDirection = 'down';
        highlightButton(mobileDown);
    });
    
    mobileLeft.addEventListener('click', function() {
        if (!gameActive || gamePaused) return;
        if (direction !== 'right') nextDirection = 'left';
        highlightButton(mobileLeft);
    });
    
    mobileRight.addEventListener('click', function() {
        if (!gameActive || gamePaused) return;
        if (direction !== 'left') nextDirection = 'right';
        highlightButton(mobileRight);
    });
    
    // Подсветка кнопки при нажатии
    function highlightButton(button) {
        button.classList.add('active');
        setTimeout(() => {
            button.classList.remove('active');
        }, 200);
    }
    
    // Кнопка паузы
    pauseBtn.addEventListener('click', function() {
        if (!gameActive) return;
        
        gamePaused = !gamePaused;
        
        if (gamePaused) {
            pauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            clearInterval(timer);
        } else {
            pauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
            timer = setInterval(() => {
                gameTime++;
                timeElement.textContent = gameTime;
            }, 1000);
            gameLoopId = requestAnimationFrame(gameLoop);
        }
    });
    
    // Кнопка рестарта
    restartBtn.addEventListener('click', initGame);
    
    // Кнопка "Играть снова"
    playAgainBtn.addEventListener('click', initGame);
    
    // Начало игры
    initGame();
    
    // Полифилл для roundRect
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function(x, y, w, h, r) {
            if (w < 2 * r) r = w / 2;
            if (h < 2 * r) r = h / 2;
            this.moveTo(x + r, y);
            this.arcTo(x + w, y, x + w, y + h, r);
            this.arcTo(x + w, y + h, x, y + h, r);
            this.arcTo(x, y + h, x, y, r);
            this.arcTo(x, y, x + w, y, r);
            this.closePath();
            return this;
        };
    }
});
</script>

<style>
.snake-game-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #1a1a2e;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    color: white;
}

.game-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(0,0,0,0.3);
    border-radius: 5px;
}

.stats {
    display: flex;
    gap: 20px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 18px;
}

.controls {
    display: flex;
    gap: 10px;
}

.btn-control {
    background: #3498db;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-control:hover {
    background: #2980b9;
}

.game-wrapper {
    position: relative;
}

#game-canvas {
    background: #2c3e50;
    border-radius: 5px;
    display: block;
    margin: 0 auto;
    border: 2px solid #34495e;
}

.game-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.overlay-content {
    background: #1a1a2e;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    max-width: 400px;
    width: 90%;
}

.overlay-content h2 {
    color: #f1c40f;
    margin-top: 0;
}

.btn-play-again {
    background: #2ecc71;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 15px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-play-again:hover {
    background: #27ae60;
}

/* Мобильное управление */
.mobile-controls {
    display: none; /* По умолчанию скрыто для десктопов */
    margin-top: 20px;
    padding: 10px;
    background: rgba(26, 32, 44, 0.7);
    border-radius: 8px;
    border: 1px solid #4e4e6d;
}

.control-row {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
}

.control-btn {
    width: 60px;
    height: 60px;
    background: #34495e;
    border: none;
    border-radius: 50%;
    color: white;
    font-size: 20px;
    margin: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.control-btn.active {
    background: #3498db;
    transform: scale(0.95);
}

.control-btn.up {
    margin-bottom: 10px;
}

/* Адаптивность */
@media (max-width: 768px) {
    .game-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .stats {
        width: 100%;
        justify-content: space-around;
    }
    
    #game-canvas {
        width: 100%;
        height: auto;
    }
    
    .mobile-controls {
        display: block; /* Показываем на мобильных устройствах */
    }
}
</style>

<?php include 'includes/footer.php'; ?>