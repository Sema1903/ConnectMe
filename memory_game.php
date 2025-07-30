<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Найди пары | ConnectMe";
include 'includes/header.php';
?>

<div class="memory-game-container">
    <h1><i class="fas fa-brain"></i> Найди пары</h1>
    
    <div class="game-info">
        <div class="stats">
            <div class="stat"><i class="fas fa-clock"></i> <span id="timer">0</span> сек</div>
            <div class="stat"><i class="fas fa-exchange-alt"></i> <span id="moves">0</span> ходов</div>
        </div>
        <button id="restart-btn" class="btn-restart"><i class="fas fa-redo"></i> Новая игра</button>
    </div>
    
    <div class="game-board" id="game-board"></div>
    
    <div class="game-overlay" id="game-overlay">
        <div class="overlay-content">
            <h2 id="result-title">Победа!</h2>
            <p id="result-text"></p>
            <button id="play-again-btn" class="btn-play-again"><i class="fas fa-play"></i> Играть снова</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Конфигурация игры
    const config = {
        rows: 4,
        cols: 4,
        cardTypes: ['fa-heart', 'fa-star', 'fa-bolt', 'fa-music', 'fa-football-ball', 'fa-car', 'fa-cloud', 'fa-gem'],
        colors: ['#e74c3c', '#3498db', '#2ecc71', '#f1c40f', '#9b59b6', '#e67e22', '#1abc9c', '#d35400']
    };
    
    // Состояние игры
    let gameState = {
        cards: [],
        flippedCards: [],
        matchedPairs: 0,
        moves: 0,
        time: 0,
        timer: null,
        gameActive: false
    };
    
    // Элементы DOM
    const gameBoard = document.getElementById('game-board');
    const timerElement = document.getElementById('timer');
    const movesElement = document.getElementById('moves');
    const restartBtn = document.getElementById('restart-btn');
    const gameOverlay = document.getElementById('game-overlay');
    const resultTitle = document.getElementById('result-title');
    const resultText = document.getElementById('result-text');
    const playAgainBtn = document.getElementById('play-again-btn');
    
    // Инициализация игры
    function initGame() {
        // Сброс состояния
        gameState = {
            cards: [],
            flippedCards: [],
            matchedPairs: 0,
            moves: 0,
            time: 0,
            timer: null,
            gameActive: true
        };
        
        // Очистка доски
        gameBoard.innerHTML = '';
        
        // Обновление статистики
        updateStats();
        
        // Создание карт
        createCards();
        
        // Скрытие оверлея
        gameOverlay.style.display = 'none';
        
        // Запуск таймера
        startTimer();
    }
    
    // Создание карт
    function createCards() {
        // Создаем пары карт
        let cardPairs = [];
        for (let i = 0; i < (config.rows * config.cols) / 2; i++) {
            const typeIndex = i % config.cardTypes.length;
            cardPairs.push({
                type: config.cardTypes[typeIndex],
                color: config.colors[typeIndex]
            });
            cardPairs.push({
                type: config.cardTypes[typeIndex],
                color: config.colors[typeIndex]
            });
        }
        
        // Перемешиваем карты
        cardPairs = shuffleArray(cardPairs);
        
        // Создаем HTML-элементы карт
        cardPairs.forEach((card, index) => {
            const cardElement = document.createElement('div');
            cardElement.className = 'memory-card';
            cardElement.dataset.index = index;
            
            const cardInner = document.createElement('div');
            cardInner.className = 'card-inner';
            
            const cardFront = document.createElement('div');
            cardFront.className = 'card-front';
            cardFront.innerHTML = `<i class="fas ${card.type}"></i>`;
            cardFront.style.color = card.color;
            
            const cardBack = document.createElement('div');
            cardBack.className = 'card-back';
            
            cardInner.appendChild(cardFront);
            cardInner.appendChild(cardBack);
            cardElement.appendChild(cardInner);
            
            cardElement.addEventListener('click', () => flipCard(cardElement, index, card));
            
            gameBoard.appendChild(cardElement);
            gameState.cards.push({
                element: cardElement,
                type: card.type,
                flipped: false,
                matched: false
            });
        });
        
        // Установка размера сетки
        gameBoard.style.gridTemplateColumns = `repeat(${config.cols}, 1fr)`;
    }
    
    // Переворот карты
    function flipCard(cardElement, index, cardData) {
        if (!gameState.gameActive || gameState.cards[index].flipped || gameState.cards[index].matched) {
            return;
        }
        
        // Если уже перевернуто 2 карты, игнорируем
        if (gameState.flippedCards.length >= 2) {
            return;
        }
        
        // Переворачиваем карту
        cardElement.classList.add('flipped');
        gameState.cards[index].flipped = true;
        gameState.flippedCards.push({ index, element: cardElement, type: cardData.type });
        
        // Если перевернуто 2 карты, проверяем на совпадение
        if (gameState.flippedCards.length === 2) {
            gameState.moves++;
            updateStats();
            
            if (gameState.flippedCards[0].type === gameState.flippedCards[1].type) {
                // Совпадение
                setTimeout(() => {
                    gameState.flippedCards.forEach(card => {
                        gameState.cards[card.index].matched = true;
                        card.element.classList.add('matched');
                    });
                    
                    gameState.flippedCards = [];
                    gameState.matchedPairs++;
                    
                    // Проверка на победу
                    if (gameState.matchedPairs === (config.rows * config.cols) / 2) {
                        endGame(true);
                    }
                }, 500);
            } else {
                // Не совпали - переворачиваем обратно
                setTimeout(() => {
                    gameState.flippedCards.forEach(card => {
                        card.element.classList.remove('flipped');
                        gameState.cards[card.index].flipped = false;
                    });
                    gameState.flippedCards = [];
                }, 1000);
            }
        }
    }
    
    // Обновление статистики
    function updateStats() {
        movesElement.textContent = gameState.moves;
        timerElement.textContent = gameState.time;
    }
    
    // Таймер
    function startTimer() {
        clearInterval(gameState.timer);
        gameState.time = 0;
        updateStats();
        
        gameState.timer = setInterval(() => {
            gameState.time++;
            updateStats();
        }, 1000);
    }
    
    // Окончание игры
    function endGame(isWin) {
        clearInterval(gameState.timer);
        gameState.gameActive = false;
        
        if (isWin) {
            resultTitle.textContent = 'Победа!';
            resultText.textContent = `Вы нашли все пары за ${gameState.time} секунд и ${gameState.moves} ходов!`;
            
            <?php if (isLoggedIn()): ?>
                // Сохранение результата
                const score = calculateScore(gameState.time, gameState.moves);
                fetch('api/save_score.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        game_id: 'memory_game', 
                        score: score 
                    })
                });
            <?php endif; ?>
        } else {
            resultTitle.textContent = 'Игра прервана';
            resultText.textContent = 'Начните новую игру!';
        }
        
        gameOverlay.style.display = 'flex';
    }
    
    // Расчет очков
    function calculateScore(time, moves) {
        // Чем меньше времени и ходов, тем больше очков
        return Math.max(100, 1000 - (time * 5 + moves * 10));
    }
    
    // Перемешивание массива
    function shuffleArray(array) {
        const newArray = [...array];
        for (let i = newArray.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [newArray[i], newArray[j]] = [newArray[j], newArray[i]];
        }
        return newArray;
    }
    
    // Обработчики событий
    restartBtn.addEventListener('click', initGame);
    playAgainBtn.addEventListener('click', initGame);
    
    // Начало игры
    initGame();
});
</script>

<style>
.memory-game-container {
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

.btn-restart, .btn-play-again {
    background: #9b59b6;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-restart:hover, .btn-play-again:hover {
    background: #8e44ad;
}

.game-board {
    display: grid;
    grid-gap: 10px;
    perspective: 1000px;
    min-height: 500px;
}

.memory-card {
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.5s;
    cursor: pointer;
}

.memory-card.flipped {
    transform: rotateY(180deg);
}

.memory-card.matched {
    opacity: 0.5;
    cursor: default;
}

.card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: center;
    transition: transform 0.5s;
    transform-style: preserve-3d;
    border-radius: 10px;
}

.card-front, .card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.card-front {
    background: white;
    transform: rotateY(180deg);
    font-size: 2rem;
}

.card-back {
    background: #3498db;
}

.game-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.overlay-content {
    background: #1a1a2e;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    max-width: 500px;
    width: 90%;
}

.overlay-content h2 {
    color: #f1c40f;
    margin-top: 0;
}

@media (max-width: 768px) {
    .game-board {
        grid-template-columns: repeat(2, 1fr) !important;
        min-height: 400px;
    }
    
    .game-info {
        flex-direction: column;
        gap: 10px;
    }
    
    .stats {
        width: 100%;
        justify-content: space-around;
    }
}
</style>

<?php include 'includes/footer.php'; ?>