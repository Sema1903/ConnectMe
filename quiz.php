<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Викторина | ConnectMe";
include 'includes/header.php';
?>

<div class="quiz-container">
    <h1><i class="fas fa-question-circle"></i> Викторина</h1>
    
    <div class="quiz-info">
        <div class="stats">
            <div class="stat"><i class="fas fa-check-circle"></i> <span id="correct">0</span></div>
            <div class="stat"><i class="fas fa-times-circle"></i> <span id="wrong">0</span></div>
            <div class="stat"><i class="fas fa-clock"></i> <span id="timer">30</span> сек</div>
            <div class="stat"><i class="fas fa-star"></i> <span id="score">0</span></div>
        </div>
        <div class="controls">
            <button id="pause-btn" class="btn-control"><i class="fas fa-pause"></i></button>
            <button id="restart-btn" class="btn-control"><i class="fas fa-redo"></i></button>
        </div>
    </div>
    
    <div class="quiz-area">
        <div class="question-container">
            <h2 id="question-text">Готовы начать?</h2>
            <div class="answers-grid" id="answers-container">
                <!-- Варианты ответов будут здесь -->
            </div>
        </div>
        
        <div class="quiz-overlay" id="quiz-overlay">
            <div class="overlay-content">
                <h2 id="result-title">Результат</h2>
                <p id="result-text"></p>
                <button id="start-quiz-btn" class="btn-play-again"><i class="fas fa-play"></i> Начать викторину</button>
            </div>
        </div>
    </div>
    
    <div class="quiz-categories">
        <h3><i class="fas fa-layer-group"></i> Категории:</h3>
        <div class="categories-grid">
            <button class="category-btn" data-category="general">Общие знания</button>
            <button class="category-btn" data-category="science">Наука</button>
            <button class="category-btn" data-category="history">История</button>
            <button class="category-btn" data-category="movies">Кино</button>
        </div>
    </div>
</div>

<script>
// База вопросов (можно расширить)
const questions = {
    general: [
        {
            question: "Какая самая длинная река в мире?",
            answers: ["Амазонка", "Нил", "Янцзы", "Миссисипи"],
            correct: 1
        },
        {
            question: "Сколько континентов на Земле?",
            answers: ["5", "6", "7", "8"],
            correct: 2
        }
    ],
    science: [
        {
            question: "Какой химический элемент обозначается как 'O'?",
            answers: ["Золото", "Кислород", "Олово", "Осмий"],
            correct: 1
        },
        {
            question: "Кто открыл закон всемирного тяготения?",
            answers: ["Эйнштейн", "Ньютон", "Галилей", "Тесла"],
            correct: 1
        }
    ],
    history: [
        {
            question: "В каком году началась Вторая мировая война?",
            answers: ["1937", "1939", "1941", "1943"],
            correct: 1
        }
    ],
    movies: [
        {
            question: "Кто сыграл роль Нео в 'Матрице'?",
            answers: ["Брэд Питт", "Киану Ривз", "Том Круз", "Леонардо ДиКаприо"],
            correct: 1
        }
    ]
};

// Состояние викторины
let quizState = {
    currentCategory: 'general',
    currentQuestion: 0,
    correctAnswers: 0,
    wrongAnswers: 0,
    score: 0,
    timeLeft: 30,
    timer: null,
    isRunning: false,
    isPaused: false
};

// Элементы DOM
const questionText = document.getElementById('question-text');
const answersContainer = document.getElementById('answers-container');
const correctElement = document.getElementById('correct');
const wrongElement = document.getElementById('wrong');
const scoreElement = document.getElementById('score');
const timerElement = document.getElementById('timer');
const pauseBtn = document.getElementById('pause-btn');
const restartBtn = document.getElementById('restart-btn');
const quizOverlay = document.getElementById('quiz-overlay');
const resultTitle = document.getElementById('result-title');
const resultText = document.getElementById('result-text');
const startQuizBtn = document.getElementById('start-quiz-btn');
const categoryBtns = document.querySelectorAll('.category-btn');

// Инициализация викторины
function initQuiz(category = 'general') {
    quizState = {
        currentCategory: category,
        currentQuestion: 0,
        correctAnswers: 0,
        wrongAnswers: 0,
        score: 0,
        timeLeft: 30,
        timer: null,
        isRunning: true,
        isPaused: false
    };
    
    updateStats();
    loadQuestion();
    startTimer();
    quizOverlay.style.display = 'none';
}

// Загрузка вопроса
function loadQuestion() {
    const categoryQuestions = questions[quizState.currentCategory];
    if (!categoryQuestions || quizState.currentQuestion >= categoryQuestions.length) {
        endQuiz();
        return;
    }
    
    const question = categoryQuestions[quizState.currentQuestion];
    questionText.textContent = question.question;
    
    answersContainer.innerHTML = '';
    question.answers.forEach((answer, index) => {
        const answerBtn = document.createElement('button');
        answerBtn.className = 'answer-btn';
        answerBtn.textContent = answer;
        answerBtn.addEventListener('click', () => checkAnswer(index));
        answersContainer.appendChild(answerBtn);
    });
}

// Проверка ответа
function checkAnswer(selectedIndex) {
    if (!quizState.isRunning || quizState.isPaused) return;
    
    const categoryQuestions = questions[quizState.currentCategory];
    const question = categoryQuestions[quizState.currentQuestion];
    
    const answerBtns = document.querySelectorAll('.answer-btn');
    answerBtns.forEach((btn, index) => {
        if (index === question.correct) {
            btn.classList.add('correct');
        }
        if (index === selectedIndex && index !== question.correct) {
            btn.classList.add('wrong');
        }
        btn.disabled = true;
    });
    
    if (selectedIndex === question.correct) {
        quizState.correctAnswers++;
        quizState.score += 10 * Math.max(1, Math.floor(quizState.timeLeft / 5));
    } else {
        quizState.wrongAnswers++;
    }
    
    updateStats();
    
    setTimeout(() => {
        quizState.currentQuestion++;
        if (quizState.currentQuestion < categoryQuestions.length) {
            loadQuestion();
            resetTimer();
        } else {
            endQuiz();
        }
    }, 1500);
}

// Таймер
function startTimer() {
    clearInterval(quizState.timer);
    quizState.timer = setInterval(() => {
        if (!quizState.isPaused && quizState.isRunning) {
            quizState.timeLeft--;
            timerElement.textContent = quizState.timeLeft;
            
            if (quizState.timeLeft <= 0) {
                timeUp();
            }
        }
    }, 1000);
}

function resetTimer() {
    quizState.timeLeft = 30;
    timerElement.textContent = quizState.timeLeft;
}

function timeUp() {
    quizState.wrongAnswers++;
    updateStats();
    
    const answerBtns = document.querySelectorAll('.answer-btn');
    answerBtns.forEach(btn => btn.disabled = true);
    
    setTimeout(() => {
        quizState.currentQuestion++;
        const categoryQuestions = questions[quizState.currentCategory];
        if (quizState.currentQuestion < categoryQuestions.length) {
            loadQuestion();
            resetTimer();
        } else {
            endQuiz();
        }
    }, 1000);
}

// Обновление статистики
function updateStats() {
    correctElement.textContent = quizState.correctAnswers;
    wrongElement.textContent = quizState.wrongAnswers;
    scoreElement.textContent = quizState.score;
}

// Окончание викторины
function endQuiz() {
    clearInterval(quizState.timer);
    quizState.isRunning = false;
    
    resultTitle.textContent = "Викторина завершена!";
    resultText.innerHTML = `
        <p>Правильных ответов: <strong>${quizState.correctAnswers}</strong></p>
        <p>Неправильных ответов: <strong>${quizState.wrongAnswers}</strong></p>
        <p>Ваш счет: <strong>${quizState.score}</strong></p>
    `;
    
    quizOverlay.style.display = 'flex';
    
    <?php if (isLoggedIn()): ?>
        fetch('api/save_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                game_id: 'quiz',
                score: quizState.score
            })
        });
    <?php endif; ?>
}

// Обработчики событий
startQuizBtn.addEventListener('click', () => initQuiz(quizState.currentCategory));

pauseBtn.addEventListener('click', () => {
    if (!quizState.isRunning) return;
    
    quizState.isPaused = !quizState.isPaused;
    pauseBtn.innerHTML = quizState.isPaused ? '<i class="fas fa-play"></i>' : '<i class="fas fa-pause"></i>';
});

restartBtn.addEventListener('click', () => initQuiz(quizState.currentCategory));

categoryBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        quizState.currentCategory = btn.dataset.category;
        quizOverlay.style.display = 'flex';
        resultTitle.textContent = "Готовы начать?";
        resultText.textContent = `Категория: ${btn.textContent}`;
        startQuizBtn.style.display = 'inline-flex';
    });
});

// Первоначальная настройка
quizOverlay.style.display = 'flex';
resultTitle.textContent = "Добро пожаловать в викторину!";
resultText.textContent = "Выберите категорию для начала игры";
startQuizBtn.style.display = 'none';
</script>

<style>
.quiz-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #1a1a2e;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    color: white;
}

.quiz-info {
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
    gap: 20px;
}

.stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
}

.stat:nth-child(1) { color: #2ecc71; } /* Правильные */
.stat:nth-child(2) { color: #e74c3c; } /* Неправильные */
.stat:nth-child(3) { color: #f1c40f; } /* Таймер */
.stat:nth-child(4) { color: #3498db; } /* Счет */

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

.quiz-area {
    position: relative;
    min-height: 300px;
    margin-bottom: 20px;
    padding: 20px;
    background: rgba(0, 20, 40, 0.5);
    border-radius: 8px;
    border: 1px solid #1a3a5a;
}

.question-container {
    margin-bottom: 20px;
}

#question-text {
    color: #f1c40f;
    margin-bottom: 20px;
    font-size: 24px;
    min-height: 72px;
}

.answers-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.answer-btn {
    background: linear-gradient(145deg, #2c3e50, #34495e);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.answer-btn:hover {
    background: linear-gradient(145deg, #3c4e60, #44596e);
    transform: translateY(-2px);
}

.answer-btn.correct {
    background: linear-gradient(145deg, #27ae60, #2ecc71);
    color: white;
}

.answer-btn.wrong {
    background: linear-gradient(145deg, #c0392b, #e74c3c);
    color: white;
}

.quiz-overlay {
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
    border-radius: 8px;
}

.overlay-content {
    background: linear-gradient(145deg, #0d2b45, #1a3a5a);
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    max-width: 500px;
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

.quiz-categories {
    margin-top: 20px;
    padding: 20px;
    background: rgba(0, 20, 40, 0.7);
    border-radius: 8px;
    border: 1px solid #1a3a5a;
}

.quiz-categories h3 {
    margin-top: 0;
    color: #4fc3f7;
    display: flex;
    align-items: center;
    gap: 10px;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.category-btn {
    background: linear-gradient(145deg, #1a3a5a, #0d2b45);
    color: #4fc3f7;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.category-btn:hover {
    background: linear-gradient(145deg, #2a4a6a, #1d3b55);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .quiz-info {
        flex-direction: column;
        gap: 15px;
    }
    
    .stats {
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .answers-grid {
        grid-template-columns: 1fr;
    }
    
    .categories-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>