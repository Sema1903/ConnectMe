<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);

if (!$current_user) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'utility';
    
    // Валидация
    if (empty($name) || empty($url)) {
        $error = 'Название и URL обязательны для заполнения';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'Пожалуйста, введите корректный URL';
    } else {
        // Сохраняем мини-приложение
        try {
            $stmt = $db->prepare("
                INSERT INTO mini_apps (name, url, description, category, user_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, datetime('now'), datetime('now'))
            ");
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $url, SQLITE3_TEXT);
            $stmt->bindValue(3, $description, SQLITE3_TEXT);
            $stmt->bindValue(4, $category, SQLITE3_TEXT);
            $stmt->bindValue(5, $current_user['id'], SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $success = 'Мини-приложение успешно добавлено и ожидает модерации!';
                $_POST = []; // Очищаем форму
            } else {
                $error = 'Ошибка при сохранении приложения';
            }
        } catch (Exception $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Получаем приложения текущего пользователя
$user_apps = [];
try {
    $stmt = $db->prepare("
        SELECT * FROM mini_apps 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bindValue(1, $current_user['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $user_apps[] = $row;
    }
} catch (Exception $e) {
    $error = 'Ошибка при загрузке приложений: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель разработчика - ConnectMe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<style>
    .developer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.developer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.developer-header h1 {
    color: #2c3e50;
    margin: 0;
    font-size: 2rem;
}

.back-btn {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    transition: background 0.3s;
}

.back-btn:hover {
    background: #5a6268;
    text-decoration: none;
    color: white;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    transition: opacity 0.3s;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.developer-content {
    display: grid;
    gap: 40px;
}

.add-app-section, .my-apps-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.add-app-section h2, .my-apps-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5rem;
}

.app-form {
    display: grid;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #495057;
}

.form-group input,
.form-group textarea,
.form-group select {
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
}

.btn-primary {
    background: #007bff;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    justify-self: start;
}

.btn-primary:hover {
    background: #0056b3;
}

.no-apps {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.no-apps i {
    font-size: 3rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.apps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.app-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
    background: white;
}

.app-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.app-icon {
    font-size: 2rem;
    color: #007bff;
    margin-bottom: 15px;
}

.app-info h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 1.2rem;
}

.app-description {
    color: #6c757d;
    margin-bottom: 10px;
    line-height: 1.4;
}

.app-url {
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.app-url a {
    color: #007bff;
    text-decoration: none;
    word-break: break-all;
}

.app-url a:hover {
    text-decoration: underline;
}

.app-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

.app-category {
    background: #e9ecef;
    padding: 3px 8px;
    border-radius: 12px;
    color: #495057;
}

.app-status {
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.8rem;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.app-date {
    font-size: 0.8rem;
    color: #6c757d;
    text-align: right;
}

/* Адаптивность */
@media (max-width: 768px) {
    .developer-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .apps-grid {
        grid-template-columns: 1fr;
    }
    
    .add-app-section, .my-apps-section {
        padding: 20px;
    }
}
</style>
<body>
    <div class="developer-container">
        <div class="developer-header">
            <h1><i class="fas fa-code"></i> Панель разработчика</h1>
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> На главную</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="developer-content">
            <!-- Форма добавления приложения -->
            <div class="add-app-section">
                <h2><i class="fas fa-plus-circle"></i> Добавить новое мини-приложение</h2>
                <form method="POST" class="app-form">
                    <div class="form-group">
                        <label for="name">Название приложения *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="url">URL приложения *</label>
                        <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($_POST['url'] ?? ''); ?>" placeholder="https://example.com/app" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="3" placeholder="Краткое описание вашего приложения..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Категория</label>
                        <select id="category" name="category">
                            <option value="utility">Утилиты</option>
                            <option value="game">Игры</option>
                            <option value="social">Социальное</option>
                            <option value="education">Образование</option>
                            <option value="entertainment">Развлечения</option>
                            <option value="other">Другое</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Отправить на модерацию
                    </button>
                </form>
            </div>

            <!-- Список приложений пользователя -->
            <div class="my-apps-section">
                <h2><i class="fas fa-list"></i> Мои приложения</h2>
                
                <?php if (empty($user_apps)): ?>
                    <div class="no-apps">
                        <i class="fas fa-inbox"></i>
                        <p>У вас пока нет добавленных приложений</p>
                    </div>
                <?php else: ?>
                    <div class="apps-grid">
                        <?php foreach ($user_apps as $app): ?>
                            <div class="app-card" data-status="<?php echo htmlspecialchars($app['status']); ?>">
                                <div class="app-icon">
                                    <i class="<?php echo htmlspecialchars($app['icon']); ?>"></i>
                                </div>
                                <div class="app-info">
                                    <h3><?php echo htmlspecialchars($app['name']); ?></h3>
                                    <p class="app-description"><?php echo htmlspecialchars($app['description'] ?? 'Без описания'); ?></p>
                                    <p class="app-url">
                                        <i class="fas fa-link"></i>
                                        <a href="<?php echo htmlspecialchars($app['url']); ?>" target="_blank">
                                            <?php echo htmlspecialchars($app['url']); ?>
                                        </a>
                                    </p>
                                    <div class="app-meta">
                                        <span class="app-category">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($app['category']); ?>
                                        </span>
                                        <span class="app-status status-<?php echo htmlspecialchars($app['status']); ?>">
                                            <?php 
                                            $status_labels = [
                                                'pending' => 'На модерации',
                                                'approved' => 'Одобрено',
                                                'rejected' => 'Отклонено'
                                            ];
                                            echo $status_labels[$app['status']] ?? $app['status'];
                                            ?>
                                        </span>
                                    </div>
                                    <div class="app-date">
                                        Добавлено: <?php echo time_elapsed_string($app['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Автоматическое скрытие сообщений
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>