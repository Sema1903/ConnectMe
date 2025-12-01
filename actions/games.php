<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/header.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = "Мини-приложения";

$currentUser = getCurrentUser($db);

// Получаем мини-приложения из базы данных
$miniApps = [];
try {
    $stmt = $db->prepare("
        SELECT * FROM mini_apps 
        WHERE status = 'approved' OR (user_id = ? AND status = 'pending')
        ORDER BY 
            CASE WHEN status = 'approved' THEN 1 ELSE 2 END,
            created_at DESC
    ");
    $stmt->bindValue(1, $currentUser['id'] ?? 0, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $miniApps[] = $row;
    }
} catch (Exception $e) {
    error_log("Error loading mini apps: " . $e->getMessage());
}
?>

<div class="games-container">
    <h1><i class="fas fa-server"></i> Мини-приложения</h1>

<?php 
$games = [
    [
        'id' => 'space_shooter',
        'title' => 'Космический стрелок',
        'description' => 'Уничтожайте вражеские корабли и собирайте бонусы',
        'icon' => 'fas fa-space-shuttle',
        'color' => 'blue', 
        'href' => 'space_shooter.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'memory_game',
        'title' => 'Игра на память',
        'description' => 'Найдите все пары карточек за минимальное время',
        'icon' => 'fas fa-brain',
        'color' => 'green',
        'href' => 'memory_game.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'snake',
        'title' => 'Змейка',
        'description' => 'Классическая змейка с новыми возможностями',
        'icon' => 'fas fa-snake',
        'color' => 'red',
        'href' => 'snake.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'quiz',
        'title' => 'Викторина',
        'description' => 'Проверьте свои знания в различных темах',
        'icon' => 'fas fa-question-circle',
        'color' => 'purple',
        'href' => 'quiz.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'sport',
        'title' => 'MemeFC',
        'description' => 'Погрузитесь в мир смешных единоборств',
        'icon' => 'fas fa-futbol',
        'color' => 'red',
        'href' => 'sport.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'ai',
        'title' => 'Саманта',
        'description' => 'Твой ИИ-помощник',
        'icon' => 'fas fa-female',
        'color' => 'purple',
        'href' => 'ai.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'tutorial',
        'title' => 'Инструкция ConnectMe',
        'description' => 'Обучалка по основным функциям платформы',
        'icon' => 'fas fa-book',
        'color' => 'blue',
        'href' => 'tutorial.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'developer',
        'title' => 'Разработчикам',
        'description' => 'Создавай свои мини-приложения',
        'icon' => 'fas fa-code',
        'color' => 'green',
        'href' => 'developer.php',
        'type' => 'builtin'
    ],
    [
        'id' => 'ball',
        'title' => 'Шар судьбы',
        'description' => 'Узнай ответы на свои вопросы',
        'icon' => 'fas fa-ball',
        'color' => 'blue',
        'href' => 'ball.php',
        'type' => 'builtin'
    ]
];
?>

    <!-- Доступные игры -->
    <div class="game-section">
        <h2>Встроенные приложения</h2>
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
                            Открыть <i class="fas fa-play"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Пользовательские мини-приложения -->
    <?php if (!empty($miniApps)): ?>
    <div class="game-section">
        <h2>Пользовательские приложения</h2>
        <div class="games-grid">
            <?php foreach ($miniApps as $app): ?>
                <div class="game-card game-card-custom app-status-<?= $app['status'] ?>">
                    <div class="game-icon">
                        <i class="<?= $app['icon'] ?: 'fas fa-cube' ?>"></i>
                        <?php if ($app['status'] == 'pending'): ?>
                            <div class="app-status-badge pending">
                                <i class="fas fa-clock"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="game-info">
                        <h3>
                            <?= htmlspecialchars($app['name']) ?>
                            <?php if ($app['status'] == 'pending'): ?>
                                <span class="status-label pending">На модерации</span>
                            <?php endif; ?>
                        </h3>
                        <p><?= htmlspecialchars($app['description'] ?: 'Без описания') ?></p>
                        <div class="app-meta">
                            <span class="app-category">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($app['category']) ?>
                            </span>
                            <?php if ($app['status'] == 'approved'): ?>
                                <span class="app-date">
                                    <i class="fas fa-calendar"></i>
                                    <?= time_elapsed_string($app['created_at']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($app['status'] == 'approved'): ?>
                            <a href="<?= htmlspecialchars($app['url']) ?>" target="_blank" class="btn btn-play">
                                Открыть <i class="fas fa-external-link-alt"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>
                                Ожидает модерации <i class="fas fa-clock"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="game-section">
        <h2>Пользовательские приложения</h2>
        <div class="no-apps-message">
            <i class="fas fa-inbox"></i>
            <h3>Пока нет пользовательских приложений</h3>
            <p>Станьте первым разработчиком! Создайте своё мини-приложение.</p>
            <a href="developer.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Создать приложение
            </a>
        </div>
    </div>
    <?php endif; ?>

<!-- Модальное окно для игры -->
<div id="gameModal" class="modal">
    <div class="modal-content game-modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-header">
            <h3 id="modalTitle">Загрузка...</h3>
        </div>
        <div class="game-frame-container">
            <iframe id="gameFrame" src="" frameborder="0"></iframe>
        </div>
    </div>
</div>

<style>
:root {
    --tg-primary: #0088cc;
    --tg-secondary: #6bc259;
    --tg-bg: #ffffff;
    --tg-surface: #f8f9fa;
    --tg-text-primary: #000000;
    --tg-text-secondary: #707579;
    --tg-border: #e7e8ec;
    --tg-hover: #f5f5f5;
    --tg-accent: #e3f2fd;
    --tg-radius: 16px;
    --tg-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    --tg-card-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.games-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 16px;
}

.games-container h1 {
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.8rem;
}

.game-section {
    margin-bottom: 32px;
}

.game-section h2 {
    margin-bottom: 20px;
    color: var(--tg-text-primary);
    font-weight: 600;
    font-size: 1.3rem;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--tg-border);
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.game-card {
    background: var(--tg-bg);
    border-radius: var(--tg-radius);
    padding: 24px;
    transition: all 0.3s ease;
    border: 1px solid var(--tg-border);
    box-shadow: var(--tg-shadow);
    position: relative;
    overflow: hidden;
}

.game-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--tg-card-shadow);
    border-color: var(--tg-primary);
}

.game-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--tg-primary);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.game-card:hover::before {
    opacity: 1;
}

/* Стили для пользовательских приложений */
.game-card-custom {
    border-left: 4px solid #ff9800;
}

.game-card-custom .game-icon {
    color: #ff9800;
    background: rgba(255, 152, 0, 0.1);
}

.app-status-pending {
    opacity: 0.8;
    border-left-color: #ffc107;
}

.app-status-pending .game-icon {
    color: #ffc107;
    background: rgba(255, 193, 7, 0.1);
}

.app-status-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ffc107;
    color: #000;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-label {
    font-size: 0.8rem;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: 8px;
    font-weight: 500;
}

.status-label.pending {
    background: #fff3cd;
    color: #856404;
}

.game-card-blue { border-left: 4px solid #3498db; }
.game-card-green { border-left: 4px solid #2ecc71; }
.game-card-red { border-left: 4px solid #e74c3c; }
.game-card-purple { border-left: 4px solid #9b59b6; }

.game-icon {
    font-size: 2.5rem;
    margin-bottom: 16px;
    color: var(--tg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--tg-accent);
    position: relative;
}

.game-card-blue .game-icon { color: #3498db; background: rgba(52, 152, 219, 0.1); }
.game-card-green .game-icon { color: #2ecc71; background: rgba(46, 204, 113, 0.1); }
.game-card-red .game-icon { color: #e74c3c; background: rgba(231, 76, 60, 0.1); }
.game-card-purple .game-icon { color: #9b59b6; background: rgba(155, 89, 182, 0.1); }

.game-info h3 {
    margin: 0 0 12px 0;
    color: var(--tg-text-primary);
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.game-info p {
    color: var(--tg-text-secondary);
    margin-bottom: 20px;
    font-size: 0.95rem;
    line-height: 1.5;
}

.app-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 0.85rem;
    color: var(--tg-text-secondary);
}

.app-category, .app-date {
    display: flex;
    align-items: center;
    gap: 4px;
}

.btn-play {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--tg-primary);
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-play:hover {
    background: #0066a4;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    color: white;
    text-decoration: none;
}

.btn-disabled {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--tg-primary);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #0066a4;
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.no-apps-message {
    text-align: center;
    padding: 60px 20px;
    background: var(--tg-surface);
    border-radius: var(--tg-radius);
    border: 2px dashed var(--tg-border);
}

.no-apps-message i {
    font-size: 4rem;
    color: var(--tg-text-secondary);
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-apps-message h3 {
    color: var(--tg-text-primary);
    margin-bottom: 10px;
}

.no-apps-message p {
    color: var(--tg-text-secondary);
    margin-bottom: 20px;
}

/* Модальное окно в стиле Telegram */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.game-modal-content {
    background-color: var(--tg-bg);
    margin: 5% auto;
    padding: 0;
    border: none;
    width: 90%;
    max-width: 800px;
    border-radius: var(--tg-radius);
    position: relative;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    overflow: hidden;
}

.close-modal {
    position: absolute;
    right: 16px;
    top: 16px;
    width: 32px;
    height: 32px;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--tg-text-secondary);
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
    transition: all 0.2s ease;
    border: none;
}

.close-modal:hover {
    background: rgba(0, 0, 0, 0.2);
    color: var(--tg-text-primary);
}

.modal-header {
    padding: 20px;
    background: var(--tg-surface);
    border-bottom: 1px solid var(--tg-border);
}

.modal-header h3 {
    margin: 0;
    color: var(--tg-text-primary);
    font-weight: 600;
}

.game-frame-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    height: 0;
    overflow: hidden;
    background: #000;
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

/* Анимации */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.game-card {
    animation: fadeInUp 0.5s ease forwards;
    opacity: 0;
}

.game-card:nth-child(1) { animation-delay: 0.05s; }
.game-card:nth-child(2) { animation-delay: 0.1s; }
.game-card:nth-child(3) { animation-delay: 0.15s; }
.game-card:nth-child(4) { animation-delay: 0.2s; }
.game-card:nth-child(5) { animation-delay: 0.25s; }
.game-card:nth-child(6) { animation-delay: 0.3s; }

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.modal.show .game-modal-content {
    animation: modalSlideIn 0.3s ease;
}

/* Адаптивность */
@media (max-width: 1024px) {
    .games-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
}

@media (max-width: 768px) {
    .games-container {
        padding: 0 12px;
    }
    
    .games-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 100px;
    }
    
    .game-card {
        padding: 20px;
    }
    
    .game-modal-content {
        width: 95%;
        margin: 10% auto;
        border-radius: 12px;
    }
    
    .games-container h1 {
        font-size: 1.5rem;
        justify-content: center;
    }
    
    .app-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .game-icon {
        font-size: 2rem;
        width: 50px;
        height: 50px;
    }
    
    .btn-play, .btn-disabled {
        width: 100%;
        justify-content: center;
    }
    
    .modal-header {
        padding: 16px;
    }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
    :root {
        --tg-bg: #1a1a1a;
        --tg-surface: #2a2a2a;
        --tg-text-primary: #ffffff;
        --tg-text-secondary: #a8a8a8;
        --tg-border: #3a3a3a;
        --tg-hover: #2a2a2a;
        --tg-accent: rgba(0, 136, 204, 0.2);
    }
    
    .game-card:hover {
        border-color: var(--tg-primary);
    }
    
    .no-apps-message {
        background: var(--tg-surface);
    }
}

/* Эффекты при наведении */
.game-card {
    cursor: pointer;
}

.game-card:active {
    transform: scale(0.98);
}

/* Состояние загрузки */
.game-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.game-card.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid var(--tg-border);
    border-top: 2px solid var(--tg-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Открытие игры в модальном окне
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('gameModal');
    const gameFrame = document.getElementById('gameFrame');
    const modalTitle = document.getElementById('modalTitle');
    const closeModal = document.querySelector('.close-modal');
    
    // Обработчики для кнопок "Открыть" (встроенные приложения)
    document.querySelectorAll('.game-card[href] .btn-play').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const gameUrl = this.closest('.game-card').getAttribute('href');
            const gameName = this.closest('.game-card').querySelector('h3').textContent;
            
            modalTitle.textContent = gameName;
            gameFrame.src = gameUrl;
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        });
    });
    
    // Обработчики для пользовательских приложений
    document.querySelectorAll('.game-card-custom .btn-play').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const appUrl = this.getAttribute('href');
            const appName = this.closest('.game-card').querySelector('h3').textContent;
            
            // Открываем в новой вкладке
            window.open(appUrl, '_blank');
        });
    });
    
    // Закрытие модального окна
    closeModal.addEventListener('click', function() {
        closeGameModal();
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeGameModal();
        }
    });
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeGameModal();
        }
    });
    
    function closeGameModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            gameFrame.src = '';
            document.body.style.overflow = 'auto';
        }, 300);
    }
    
    // Плавная загрузка карточек
    const gameCards = document.querySelectorAll('.game-card');
    gameCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });
});
</script>

<!-- Остальные стили для sidebar и темной темы остаются без изменений -->
<style>
/* Исправление текста в боковом меню для темной темы */
@media (prefers-color-scheme: dark) {
    .sidebar-item {
        color: #ffffff !important;
    }
    
    .sidebar-item:hover,
    .sidebar-item.active {
        color: var(--tg-primary) !important;
    }
    
    .sidebar-item i {
        color: #a8a8a8 !important;
    }
    
    .sidebar-item:hover i,
    .sidebar-item.active i {
        color: var(--tg-primary) !important;
    }
    
    .sidebar-item span {
        color: inherit !important;
    }
    
    /* Улучшение контрастности */
    .sidebar-header {
        background: var(--tg-card-bg) !important;
        border-bottom: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-items {
        background: var(--tg-bg);
    }
    
    .sidebar-footer {
        background: var(--tg-card-bg) !important;
        border-top: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-user-name {
        color: #ffffff !important;
    }
    
    .sidebar-user-status {
        color: #a8a8a8 !important;
    }
    
    /* Дополнительное улучшение видимости */
    .sidebar-item {
        border-left: 3px solid transparent;
    }
    
    .sidebar-item:hover,
    .sidebar-item.active {
        background: rgba(0, 136, 204, 0.1) !important;
        border-left-color: var(--tg-primary) !important;
    }
    
    /* Улучшение иконок */
    .sidebar-item i {
        filter: brightness(1.2);
    }
}

/* Дополнительные гарантии видимости текста */
.sidebar-item {
    font-weight: 500 !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.sidebar-item span {
    font-weight: 500 !important;
}

/* Принудительное применение стилей если автоматическая тема не сработала */
.sidebar-menu {
    color-scheme: light dark;
}

/* Резервный вариант для темной темы */
[data-theme="dark"] .sidebar-item,
.dark-mode .sidebar-item,
body.dark .sidebar-item {
    color: #ffffff !important;
}

[data-theme="dark"] .sidebar-item:hover,
[data-theme="dark"] .sidebar-item.active,
.dark-mode .sidebar-item:hover,
.dark-mode .sidebar-item.active,
body.dark .sidebar-item:hover,
body.dark .sidebar-item.active {
    color: var(--tg-primary) !important;
}

/* Улучшение для мобильной версии */
@media (max-width: 768px) {
    .sidebar-item {
        font-size: 16px !important;
        padding: 16px 20px !important;
        margin-left: 50px;
    }
    
    .sidebar-item i {
        font-size: 20px !important;
        width: 28px !important;
    }
    
    .sidebar-item span {
        font-size: 16px !important;
        font-weight: 500 !important;
    }
}

/* Повышение контрастности для accessibility */
.sidebar-item {
    contrast: 4.5 !important;
}

/* Гарантия что текст всегда будет виден */
.sidebar-items {
    background: var(--tg-bg) !important;
}

.sidebar-item {
    background: transparent !important;
}

.sidebar-item:hover {
    background: var(--tg-hover) !important;
}

.sidebar-item.active {
    background: var(--tg-accent) !important;
}
</style>

<script>
// Дополнительный скрипт для гарантии видимости текста
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем темную тему и принудительно применяем стили
    const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (isDarkMode) {
        // Добавляем класс для темной темы
        document.body.classList.add('dark-mode');
        
        // Принудительно обновляем стили sidebar
        const sidebarItems = document.querySelectorAll('.sidebar-item');
        sidebarItems.forEach(item => {
            item.style.color = '#ffffff';
            item.style.fontWeight = '500';
        });
        
        const sidebarActiveItems = document.querySelectorAll('.sidebar-item.active');
        sidebarActiveItems.forEach(item => {
            item.style.color = 'var(--tg-primary)';
        });
    }
    
    // Слушаем изменения темы
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (e.matches) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    });
});
</script>
<style>
    @media (prefers-color-scheme: light) {
        .mobile-menu-btn{
            color: black;
        }
        .mobile-menu-btn:hover{
            background: #f5f5f5;
        }
        .sidebar-items{
            background: #ffffff !important;
        }
        .sidebar-item{
            background: #ffffff !important;
        }
        .sidebar-item.active{
            background: #e3f2fc !important;
            border-left-color: #0589c6 !important;
            color: #000000 !important;
        }
        .sidebar-item:hover{
            background: #f5f5f5 !important;
            border-left-color: #0589c6 !important;
        }
        .mobile-nav-item.active{
            color: #0589c6;
        }
        .mobile-nav-item:hover{
            background: #f5f5f5;
        }
        .sidebar-badge{
            background: #0589c6;
        }
    }
</style>
<?php include 'includes/footer.php'; ?>