<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Создание и подключение к SQLite базе данных
class ExamDB {
    private $db;
    
    public function __construct() {
        $this->db = new SQLite3('exam_scores.db');
        $this->initDatabase();
    }
    public function getLastError() {
        return $this->db->lastErrorMsg();
    }
    private function initDatabase() {
        // Таблица пользователей
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE,
                email TEXT UNIQUE,
                password_hash TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Таблица результатов пробников
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS scores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                subject TEXT,
                score INTEGER,
                attempt_number INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ");
    }
    
    // Регистрация нового пользователя
    public function registerUser($username, $email, $password) {
        // Проверяем, существует ли пользователь
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            return ['success' => false, 'error' => 'Пользователь с таким именем или email уже существует'];
        }
        
        // Хешируем пароль
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Создаем пользователя
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash) 
            VALUES (:username, :email, :password_hash)
        ");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password_hash', $passwordHash, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $userId = $this->db->lastInsertRowID();
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'error' => 'Ошибка при создании пользователя'];
    }
    
    // Авторизация пользователя
    public function loginUser($username, $password) {
        $stmt = $this->db->prepare("SELECT id, username, password_hash FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'error' => 'Неверное имя пользователя или пароль'];
    }
    
    // Получение информации о пользователе по ID
    public function getUserById($userId) {
        $stmt = $this->db->prepare("SELECT id, username, email, created_at FROM users WHERE id = :id");
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    // Добавление результата
    public function addScore($userId, $subject, $score) {
        try {
            // Простая вставка без подсчета попыток
            $stmt = $this->db->prepare("
                INSERT INTO scores (user_id, subject, score, attempt_number)
                VALUES (:user_id, :subject, :score, 
                       (SELECT COALESCE(MAX(attempt_number), 0) + 1 
                        FROM scores 
                        WHERE user_id = :user_id2 AND subject = :subject2))
            ");
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
            $stmt->bindValue(':score', $score, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id2', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':subject2', $subject, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("Ошибка в addScore: " . $e->getMessage());
            return false;
        }
    }
    
    // Получение результатов пользователя
    public function getUserScores($userId, $subject = null) {
        $sql = "
            SELECT subject, score, attempt_number 
            FROM scores 
            WHERE user_id = :user_id
        ";
        
        if ($subject) {
            $sql .= " AND subject = :subject";
        }
        
        $sql .= " ORDER BY subject, attempt_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        
        if ($subject) {
            $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
        }
        
        $result = $stmt->execute();
        $scores = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $scores[] = $row;
        }
        
        return $scores;
    }
    
    // Глобальная статистика
    public function getGlobalStats() {
        $result = $this->db->query("
            SELECT 
                u.id as user_id,
                u.username,
                COUNT(s.id) as mock_count,
                AVG(s.score) as avg_score,
                MAX(s.score) as max_score
            FROM users u
            LEFT JOIN scores s ON u.id = s.user_id
            GROUP BY u.id, u.username
            HAVING mock_count > 0
            ORDER BY max_score DESC
            LIMIT 10
        ");
        
        $stats = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new ExamDB();
    $action = $_POST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'register':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            
            $result = $db->registerUser($username, $email, $password);
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['username'] = $username;
            }
            echo json_encode($result);
            break;
            
        case 'login':
            $username = $_POST['username'];
            $password = $_POST['password'];
            
            $result = $db->loginUser($username, $password);
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['username'] = $result['user']['username'];
            }
            echo json_encode($result);
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        case 'add_score':
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
                    break;
                }
                
                $subject = $_POST['subject'];
                $score = intval($_POST['score']);
                
                error_log("Добавление балла: user_id={$_SESSION['user_id']}, subject=$subject, score=$score");
                
                if ($score < 0 || $score > 100) {
                    echo json_encode(['success' => false, 'error' => 'Некорректный балл']);
                    break;
                }
                
                $success = $db->addScore($_SESSION['user_id'], $subject, $score);
                
                if ($success) {
                    error_log("Балл успешно добавлен в БД");
                    echo json_encode(['success' => true]);
                } else {
                    $error = $db->getLastError();
                    error_log("Ошибка добавления балла: " . $error);
                    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $error]);
                }
                break;
            
        case 'get_scores':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
                break;
            }
            
            $subject = $_POST['subject'] ?? null;
            $scores = $db->getUserScores($_SESSION['user_id'], $subject);
            echo json_encode(['success' => true, 'scores' => $scores]);
            break;
            
        case 'get_global_stats':
            $stats = $db->getGlobalStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'get_user_info':
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
                break;
            }
            
            $user = $db->getUserById($_SESSION['user_id']);
            echo json_encode(['success' => true, 'user' => $user]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
    }
    exit;
}

// Проверяем авторизацию при загрузке страницы
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Прогноз результатов ЕГЭ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .container {
            max-width: 900px;
        }
        .auth-form {
            display: none;
        }
        .auth-form.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">

    <div class="container mx-auto p-8 bg-white rounded-xl shadow-2xl space-y-8">
        <!-- Шапка с авторизацией -->
        <header class="text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">Прогноз ЕГЭ и пробников</h1>
            <p class="text-gray-500 text-lg">Введите результаты ваших пробных экзаменов, чтобы увидеть прогноз.</p>
            
            <div id="authSection" class="mt-4">
                <?php if ($isLoggedIn): ?>
                    <div class="flex justify-between items-center">
                        <div id="userIdDisplay" class="text-sm text-gray-600">
                            Вы вошли как: <span class="font-bold"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                        <button id="logoutBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            Выйти
                        </button>
                    </div>
                <?php else: ?>
                    <div class="flex space-x-4 justify-center">
                        <button id="showLoginBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Войти
                        </button>
                        <button id="showRegisterBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            Регистрация
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Формы авторизации -->
        <div id="loginForm" class="auth-form bg-gray-50 p-6 rounded-lg border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Вход в систему</h2>
            <form id="loginFormElement">
                <div class="space-y-4">
                    <input type="text" name="username" placeholder="Имя пользователя" class="w-full p-3 rounded-lg border border-gray-300" required>
                    <input type="password" name="password" placeholder="Пароль" class="w-full p-3 rounded-lg border border-gray-300" required>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 transition">
                        Войти
                    </button>
                </div>
            </form>
            <button id="hideLoginBtn" class="w-full mt-2 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400 transition">
                Отмена
            </button>
        </div>

        <div id="registerForm" class="auth-form bg-gray-50 p-6 rounded-lg border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Регистрация</h2>
            <form id="registerFormElement">
                <div class="space-y-4">
                    <input type="text" name="username" placeholder="Имя пользователя" class="w-full p-3 rounded-lg border border-gray-300" required>
                    <input type="email" name="email" placeholder="Email" class="w-full p-3 rounded-lg border border-gray-300" required>
                    <input type="password" name="password" placeholder="Пароль" class="w-full p-3 rounded-lg border border-gray-300" required>
                    <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 transition">
                        Зарегистрироваться
                    </button>
                </div>
            </form>
            <button id="hideRegisterBtn" class="w-full mt-2 bg-gray-300 text-gray-700 py-2 rounded-lg hover:bg-gray-400 transition">
                Отмена
            </button>
        </div>

        <!-- Основной контент (только для авторизованных) -->
        <div id="mainContent" class="<?php echo $isLoggedIn ? '' : 'hidden'; ?>">
            <!-- Секция ввода данных -->
            <section class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Ввод данных</h2>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <select id="subjectSelect" class="p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                        <option value="Математика">Математика</option>
                        <option value="Русский язык">Русский язык</option>
                        <option value="Физика">Физика</option>
                        <option value="Информатика">Информатика</option>
                        <option value="Биология">Биология</option>
                        <option value="Химия">Химия</option>
                        <option value="История">История</option>
                        <option value="Обществознание">Обществознание</option>
                        <option value="Литература">Литература</option>
                        <option value="География">География</option>
                        <option value="Английский язык">Английский язык</option>
                    </select>
                    <input id="scoreInput" type="number" placeholder="Введите балл (0-100)" class="flex-grow p-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300" min="0" max="100">
                    <button id="addScoreBtn" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg shadow-md hover:bg-blue-700 transition duration-300 transform hover:scale-105">Добавить результат</button>
                </div>
                <div class="mt-4 text-center">
                    <button id="predictBtn" class="w-full bg-green-600 text-white font-bold py-3 px-6 rounded-lg shadow-md hover:bg-green-700 transition duration-300 transform hover:scale-105">Спрогнозировать</button>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Секция статистики и результатов -->
                <section class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Ваши результаты и прогноз</h2>
                    <div id="results" class="space-y-4">
                        <!--<p id="finalScore" class="text-lg font-medium text-gray-700">Прогноз итогового результата ЕГЭ: <span class="font-bold text-gray-900">-</span></p>-->
                        <p id="nextScore" class="text-lg font-medium text-gray-700">Прогноз следующего пробника: <span class="font-bold text-gray-900">-</span></p>
                        <p id="error" class="text-lg font-medium text-gray-700">Погрешность (RMSE): <span class="font-bold text-gray-900">-</span></p>
                    </div>
                    <div id="stats" class="mt-6 space-y-2 text-gray-600">
                        <p>Всего пробников: <span id="mockCount" class="font-bold text-gray-800">0</span></p>
                        <p>Средний балл: <span id="avgScore" class="font-bold text-gray-800">-</span></p>
                        <p>Максимальный балл: <span id="maxScore" class="font-bold text-gray-800">-</span></p>
                        <p>Минимальный балл: <span id="minScore" class="font-bold text-gray-800">-</span></p>
                    </div>
                </section>

                <!-- Секция таблицы лидеров -->
                <section class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Общая статистика (Лидерборд)</h2>
                    <div id="leaderboard" class="space-y-2">
                        <p class="text-gray-500">Загрузка...</p>
                    </div>
                </section>
            </div>

            <!-- Секция графика -->
            <section>
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Динамика результатов</h2>
                <div class="bg-white p-4 rounded-lg shadow-inner">
                    <canvas id="scoreChart"></canvas>
                </div>
            </section>
        </div>

        <!-- Сообщение для неавторизованных -->
        <div id="guestMessage" class="<?php echo $isLoggedIn ? 'hidden' : 'bg-yellow-100 p-6 rounded-lg border border-yellow-200 text-center'; ?>">
            <p class="text-yellow-800 text-lg">Для работы с приложением необходимо войти в систему или зарегистрироваться.</p>
        </div>
        
        <!-- Секция для сообщений -->
        <div id="messageBox" class="p-4 bg-red-100 text-red-700 rounded-lg hidden"></div>
    </div>

    <script>
        // Глобальные переменные
        let currentUserId = <?php echo $isLoggedIn ? $_SESSION['user_id'] : 'null'; ?>;
        let currentUsername = '<?php echo $isLoggedIn ? htmlspecialchars($username) : ''; ?>';
        let allScores = {};
        let currentScores = [];
        let chartInstance;

        // Элементы DOM
        const authSection = document.getElementById('authSection');
        const mainContent = document.getElementById('mainContent');
        const guestMessage = document.getElementById('guestMessage');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const showLoginBtn = document.getElementById('showLoginBtn');
        const showRegisterBtn = document.getElementById('showRegisterBtn');
        const hideLoginBtn = document.getElementById('hideLoginBtn');
        const hideRegisterBtn = document.getElementById('hideRegisterBtn');
        const loginFormElement = document.getElementById('loginFormElement');
        const registerFormElement = document.getElementById('registerFormElement');
        const logoutBtn = document.getElementById('logoutBtn');

        // Остальные элементы...
        const scoreInput = document.getElementById('scoreInput');
        const addScoreBtn = document.getElementById('addScoreBtn');
        const predictBtn = document.getElementById('predictBtn');
        const messageBox = document.getElementById('messageBox');
        const subjectSelect = document.getElementById('subjectSelect');

        // Элементы для отображения результатов
        const finalScoreElem = document.getElementById('finalScore')?.querySelector('span');
        const nextScoreElem = document.getElementById('nextScore')?.querySelector('span');
        const errorElem = document.getElementById('error')?.querySelector('span');
        
        // Элементы для отображения статистики
        const mockCountElem = document.getElementById('mockCount');
        const avgScoreElem = document.getElementById('avgScore');
        const maxScoreElem = document.getElementById('maxScore');
        const minScoreElem = document.getElementById('minScore');
        const leaderboardElem = document.getElementById('leaderboard');

        // Функция для отправки AJAX запросов
        async function apiCall(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            
            for (const key in data) {
                formData.append(key, data[key]);
            }
            
            console.log("Отправка запроса:", action, data);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                console.log("Статус ответа:", response.status);
                
                const result = await response.json();
                console.log("Данные ответа:", result);
                
                return result;
            } catch (error) {
                console.error('API Error:', error);
                showMessage('Ошибка соединения с сервером', 'error');
                return { success: false, error: 'Network error: ' + error.message };
            }
        }

        // Функция для отображения сообщений
        function showMessage(message, type = 'error') {
            if (!messageBox) return;
            
            messageBox.textContent = message;
            messageBox.style.display = 'block';
            messageBox.className = type === 'error' 
                ? 'p-4 bg-red-100 text-red-700 rounded-lg' 
                : 'p-4 bg-green-100 text-green-700 rounded-lg';
            
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }

        // Функции для управления интерфейсом авторизации
        function showLoginForm() {
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
        }

        function showRegisterForm() {
            registerForm.classList.add('active');
            loginForm.classList.remove('active');
        }

        function hideAuthForms() {
            loginForm.classList.remove('active');
            registerForm.classList.remove('active');
        }

        function updateUIAfterLogin(userId, username) {
            currentUserId = userId;
            currentUsername = username;
            
            // Обновляем шапку
            authSection.innerHTML = `
                <div class="flex justify-between items-center">
                    <div id="userIdDisplay" class="text-sm text-gray-600">
                        Вы вошли как: <span class="font-bold">${username}</span>
                    </div>
                    <button id="logoutBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Выйти
                    </button>
                </div>
            `;
            
            // Показываем основной контент
            mainContent.classList.remove('hidden');
            guestMessage.classList.add('hidden');
            hideAuthForms();
            
            // Загружаем данные пользователя
            loadScores();
            loadGlobalStats();
            
            // Обновляем обработчик кнопки выхода
            document.getElementById('logoutBtn').addEventListener('click', logout);
        }

        function updateUIAfterLogout() {
            currentUserId = null;
            currentUsername = '';
            
            // Обновляем шапку
            authSection.innerHTML = `
                <div class="flex space-x-4 justify-center">
                    <button id="showLoginBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Войти
                    </button>
                    <button id="showRegisterBtn" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        Регистрация
                    </button>
                </div>
            `;
            
            // Скрываем основной контент
            mainContent.classList.add('hidden');
            guestMessage.classList.remove('hidden');
            
            // Обновляем обработчики
            document.getElementById('showLoginBtn').addEventListener('click', showLoginForm);
            document.getElementById('showRegisterBtn').addEventListener('click', showRegisterForm);
        }

        // Обработчики авторизации
        async function login(event) {
            event.preventDefault();
            const formData = new FormData(loginFormElement);
            const result = await apiCall('login', {
                username: formData.get('username'),
                password: formData.get('password')
            });
            
            if (result.success) {
                updateUIAfterLogin(result.user.id, result.user.username);
                showMessage('Успешный вход!', 'success');
            } else {
                showMessage(result.error);
            }
        }

        async function register(event) {
            event.preventDefault();
            const formData = new FormData(registerFormElement);
            const result = await apiCall('register', {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password')
            });
            
            if (result.success) {
                updateUIAfterLogin(result.user_id, formData.get('username'));
                showMessage('Регистрация успешна!', 'success');
            } else {
                showMessage(result.error);
            }
        }

        async function logout() {
            const result = await apiCall('logout');
            if (result.success) {
                updateUIAfterLogout();
                showMessage('Вы вышли из системы', 'success');
            }
        }

        // Функция для загрузки результатов
        async function loadScores() {
            if (!currentUserId) return;
            
            const result = await apiCall('get_scores');
            if (result.success) {
                // Группируем результаты по предметам
                allScores = {};
                result.scores.forEach(score => {
                    if (!allScores[score.subject]) {
                        allScores[score.subject] = [];
                    }
                    allScores[score.subject].push({
                        x: score.attempt_number,
                        y: score.score
                    });
                });
                updateUIForSubject();
            } else {
                showMessage(result.error);
            }
        }

        // Функция для загрузки глобальной статистики
        async function loadGlobalStats() {
            const result = await apiCall('get_global_stats');
            if (result.success) {
                leaderboardElem.innerHTML = '';
                
                if (result.stats.length === 0) {
                    leaderboardElem.innerHTML = '<p class="text-gray-500">Нет данных для лидерборда.</p>';
                    return;
                }

                result.stats.forEach((stat, index) => {
                    const isCurrentUser = stat.user_id == currentUserId;
                    const item = document.createElement('div');
                    item.className = `p-3 rounded-lg ${isCurrentUser ? 'bg-blue-100' : 'bg-gray-100'} shadow-sm`;
                    item.innerHTML = `
                        <p class="font-semibold">${index + 1}. ${stat.username} ${isCurrentUser ? '(Вы)' : ''}</p>
                        <p class="text-sm text-gray-600">Средний балл: ${parseFloat(stat.avg_score || 0).toFixed(2)}</p>
                        <p class="text-sm text-gray-600">Максимальный балл: ${stat.max_score || 0}</p>
                        <p class="text-sm text-gray-600">Пробников: ${stat.mock_count || 0}</p>
                    `;
                    leaderboardElem.appendChild(item);
                });
            } else {
                showMessage(result.error);
            }
        }

        // Обновление интерфейса при смене предмета
        function updateUIForSubject() {
            const currentSubject = subjectSelect.value;
            currentScores = allScores[currentSubject] || [];
            updateStats();
            updateChart();
            
            // Сброс прогнозов
            if (finalScoreElem) finalScoreElem.textContent = '-';
            if (nextScoreElem) nextScoreElem.textContent = '-';
            if (errorElem) errorElem.textContent = '-';
        }

        // Обновление статистики
        function updateStats() {
            if (!mockCountElem) return;
            
            mockCountElem.textContent = currentScores.length;
            if (currentScores.length > 0) {
                const total = currentScores.reduce((sum, current) => sum + current.y, 0);
                const avg = total / currentScores.length;
                const max = Math.max(...currentScores.map(s => s.y));
                const min = Math.min(...currentScores.map(s => s.y));
                if (avgScoreElem) avgScoreElem.textContent = avg.toFixed(2);
                if (maxScoreElem) maxScoreElem.textContent = max;
                if (minScoreElem) minScoreElem.textContent = min;
            } else {
                if (avgScoreElem) avgScoreElem.textContent = '0';
                if (maxScoreElem) maxScoreElem.textContent = '0';
                if (minScoreElem) minScoreElem.textContent = '0';
            }
        }

        // Полиномиальная регрессия
        function polyfit(x, y, degree) {
            const n = x.length;
            const X = [];
            for (let i = 0; i < n; i++) {
                X[i] = [];
                for (let j = 0; j <= degree; j++) {
                    X[i][j] = Math.pow(x[i], j);
                }
            }

            const XT = [];
            for (let i = 0; i <= degree; i++) {
                XT[i] = [];
                for (let j = 0; j < n; j++) {
                    XT[i][j] = X[j][i];
                }
            }

            const XT_X = [];
            for (let i = 0; i <= degree; i++) {
                XT_X[i] = [];
                for (let j = 0; j <= degree; j++) {
                    let sum = 0;
                    for (let k = 0; k < n; k++) {
                        sum += XT[i][k] * X[k][j];
                    }
                    XT_X[i][j] = sum;
                }
            }

            const XT_y = [];
            for (let i = 0; i <= degree; i++) {
                let sum = 0;
                for (let j = 0; j < n; j++) {
                    sum += XT[i][j] * y[j];
                }
                XT_y[i] = sum;
            }

            function solve(A, b) {
                const n = A.length;
                for (let i = 0; i < n; i++) {
                    let maxRow = i;
                    for (let k = i + 1; k < n; k++) {
                        if (Math.abs(A[k][i]) > Math.abs(A[maxRow][i])) {
                            maxRow = k;
                        }
                    }
                    [A[i], A[maxRow]] = [A[maxRow], A[i]];
                    [b[i], b[maxRow]] = [b[maxRow], b[i]];

                    for (let k = i + 1; k < n; k++) {
                        const factor = A[k][i] / A[i][i];
                        for (let j = i; j < n; j++) {
                            A[k][j] -= factor * A[i][j];
                        }
                        b[k] -= factor * b[i];
                    }
                }

                const result = new Array(n).fill(0);
                for (let i = n - 1; i >= 0; i--) {
                    let sum = 0;
                    for (let j = i + 1; j < n; j++) {
                        sum += A[i][j] * result[j];
                    }
                    result[i] = (b[i] - sum) / A[i][i];
                }
                return result;
            }

            return solve(XT_X, XT_y);
        }

        // Функция для обновления графика
        function updateChart(regressionPoints = null) {
            const ctx = document.getElementById('scoreChart');
            if (!ctx) return;
            
            if (chartInstance) {
                chartInstance.destroy();
            }

            const datasets = [{
                label: 'Результаты пробников',
                data: currentScores,
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgba(59, 130, 246, 1)',
                pointRadius: 6,
                type: 'scatter',
            }];

            if (regressionPoints) {
                datasets.push({
                    label: 'Линия прогноза',
                    data: regressionPoints,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    fill: false,
                    showLine: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    type: 'line',
                });
            }

            chartInstance = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            type: 'linear',
                            position: 'bottom',
                            title: {
                                display: true,
                                text: 'Номер пробника'
                            },
                            ticks: {
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            }
                        },
                        y: {
                            min: 0,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Баллы'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        },
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Динамика результатов пробников и прогноз',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // Обработчик кнопки "Добавить результат"
        if (addScoreBtn) {
            addScoreBtn.addEventListener('click', async () => {
                if (!currentUserId) {
                    showMessage("Необходимо войти в систему");
                    return;
                }
                
                const score = parseInt(scoreInput.value);
                const subject = subjectSelect.value;
                
                if (isNaN(score) || score < 0 || score > 100) {
                    showMessage("Пожалуйста, введите корректный балл от 0 до 100.");
                    return;
                }
                
                const result = await apiCall('add_score', { subject, score });
                if (result.success) {
                    scoreInput.value = '';
                    await loadScores();
                    await loadGlobalStats();
                    showMessage("Результат успешно добавлен!", "success");
                } else {
                    showMessage(result.error);
                }
            });
        }

        // Обработчик кнопки "Спрогнозировать"
        if (predictBtn) {
            predictBtn.addEventListener('click', () => {
                if (!currentUserId) {
                    showMessage("Необходимо войти в систему");
                    return;
                }
                
                if (currentScores.length < 3) {
                    showMessage("Для прогнозирования необходимо как минимум 3 результата пробников.");
                    return;
                }

                const x_data = currentScores.map(s => s.x);
                const y_data = currentScores.map(s => s.y);
                const degree = 2;

                try {
                    const coefficients = polyfit(x_data, y_data, degree);

                    const predictScore = (x) => {
                        let sum = 0;
                        for (let i = 0; i < coefficients.length; i++) {
                            sum += coefficients[i] * Math.pow(x, i);
                        }
                        return sum;
                    };

                    const finalExamIndex = currentScores.length + 1;
                    const finalPrediction = predictScore(finalExamIndex);

                    const nextMockIndex = currentScores.length + 1;
                    const nextPrediction = predictScore(nextMockIndex);

                    let sumSquaredError = 0;
                    for (let i = 0; i < currentScores.length; i++) {
                        const predicted = predictScore(currentScores[i].x);
                        sumSquaredError += Math.pow(predicted - currentScores[i].y, 2);
                    }
                    const rmse = Math.sqrt(sumSquaredError / currentScores.length);

                    if (finalScoreElem) finalScoreElem.textContent = Math.round(Math.max(0, Math.min(100, finalPrediction)));
                    if (nextScoreElem) nextScoreElem.textContent = Math.round(Math.max(0, Math.min(100, nextPrediction)));
                    if (errorElem) errorElem.textContent = rmse.toFixed(2);
                    
                    const regressionPoints = [];
                    const minX = 1;
                    const maxX = currentScores.length + 2;
                    for (let i = minX; i <= maxX; i += 0.1) {
                        regressionPoints.push({ x: i, y: predictScore(i) });
                    }

                    updateChart(regressionPoints);
                    showMessage("Прогноз успешно рассчитан!", "success");

                } catch (e) {
                    showMessage("Ошибка при расчете прогноза.", "error");
                    console.error(e);
                }
            });
        }

        // Обработчик смены предмета
        if (subjectSelect) {
            subjectSelect.addEventListener('change', updateUIForSubject);
        }

        // Инициализация при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация обработчиков авторизации
            if (showLoginBtn) showLoginBtn.addEventListener('click', showLoginForm);
            if (showRegisterBtn) showRegisterBtn.addEventListener('click', showRegisterForm);
            if (hideLoginBtn) hideLoginBtn.addEventListener('click', hideAuthForms);
            if (hideRegisterBtn) hideRegisterBtn.addEventListener('click', hideAuthForms);
            if (loginFormElement) loginFormElement.addEventListener('submit', login);
            if (registerFormElement) registerFormElement.addEventListener('submit', register);
            if (logoutBtn) logoutBtn.addEventListener('click', logout);
            
            // Если пользователь авторизован, загружаем данные
            if (currentUserId) {
                loadScores();
                loadGlobalStats();
            }
        });
    </script>
</body>
</html>