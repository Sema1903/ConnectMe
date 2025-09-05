<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
if (!$current_user) {
    header("Location: login.php");
    exit;
}

$group_id = $_GET['id'] ?? 0;

// Получаем данные группы
$stmt = $db->prepare("SELECT * FROM groups WHERE id = ?");
$stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$group = $result->fetchArray(SQLITE3_ASSOC);

// Проверяем права доступа
if (!$group || $group['creator_id'] != $current_user['id']) {
    header("HTTP/1.0 403 Forbidden");
    exit("У вас нет прав для редактирования этой группы");
}

// Обработка формы
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Валидация
    if (empty($name)) {
        $errors[] = "Название группы обязательно";
    }
    
    if (mb_strlen($name) > 100) {
        $errors[] = "Название слишком длинное (макс. 100 символов)";
    }
    
    if (empty($errors)) {
        // Обработка загрузки аватарки
        $avatar = $group['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $upload = uploadImage($_FILES['avatar'], 'groups');
            if ($upload['success']) {
                $avatar = $upload['file_name'];
                // Удаляем старый аватар, если это не дефолтный
                if ($group['avatar'] != 'group_default.jpg') {
                    @unlink("assets/images/groups/" . $group['avatar']);
                }
            } else {
                $errors[] = $upload['error'];
            }
        }
        
        if (empty($errors)) {
            // Обновляем группу в БД
            $stmt = $db->prepare("UPDATE groups SET name = ?, description = ?, avatar = ? WHERE id = ?");
            $stmt->bindValue(1, $name, SQLITE3_TEXT);
            $stmt->bindValue(2, $description, SQLITE3_TEXT);
            $stmt->bindValue(3, $avatar, SQLITE3_TEXT);
            $stmt->bindValue(4, $group_id, SQLITE3_INTEGER);
            
            if ($stmt->execute()) {
                $success = true;
                $group['name'] = $name;
                $group['description'] = $description;
                $group['avatar'] = $avatar;
            } else {
                $errors[] = "Ошибка при обновлении группы";
            }
        }
    }
}

// Функция для загрузки изображения (добавьте в functions.php)
function uploadImage($file, $folder) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Допустимые форматы: JPG, PNG, GIF'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Максимальный размер файла: 2MB'];
    }
    
    $file_name = uniqid() . '.' . $ext;
    $upload_path = "assets/images/$folder/" . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'file_name' => $file_name];
    }
    
    return ['success' => false, 'error' => 'Ошибка загрузки файла'];
}

require_once 'includes/header.php';
?>

<?php
// [Предыдущий PHP-код остается без изменений]
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование группы | <?= htmlspecialchars($group['name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1b263b;
            --light: #f8f9fa;
            --danger: #ef233c;
            --success: #4cc9f0;
            --border-radius: 12px;
            --shadow: 0 10px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f5f7ff;
            color: var(--dark);
            line-height: 1.6;
        }

        .edit-group-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .group-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            border: none;
        }

        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
            pointer-events: none;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-size: 1rem;
            margin-bottom: 1.25rem;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--shadow);
            display: block;
            margin: 1rem 0;
            transition: var(--transition);
        }

        .avatar-preview:hover {
            transform: scale(1.05);
        }

        .file-upload {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--light);
            color: var(--dark);
            border-radius: var(--border-radius);
            border: 1px dashed #ccc;
            text-align: center;
            width: 100%;
            transition: var(--transition);
        }

        .file-upload-label:hover {
            background: #e9ecef;
            border-color: var(--primary);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .edit-group-container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .group-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        /* Кастомный чекбокс для переключателей */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-left: 10px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <div class="edit-group-container">
        <div class="group-card">
            <div class="card-header">
                <h2><i class="fas fa-users-cog"></i> Редактирование группы</h2>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Группа успешно обновлена!
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">
                            <i class="fas fa-heading"></i> Название группы
                        </label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($group['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">
                            <i class="fas fa-align-left"></i> Описание
                        </label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="5"><?= htmlspecialchars($group['description']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-image"></i> Аватар группы
                        </label>
                        <div class="file-upload">
                            <label for="avatar" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i> Выберите новое изображение
                                <input type="file" id="avatar" name="avatar" class="file-upload-input" accept="image/*">
                            </label>
                        </div>
                        
                        <?php if ($group['avatar']): ?>
                            <div class="current-avatar">
                                <p class="form-label">Текущий аватар:</p>
                                <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                                     alt="Текущий аватар" class="avatar-preview">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="/group.php?id=<?= $group_id ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Назад к группе
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Динамическое отображение превью при выборе файла
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    let preview = document.querySelector('.avatar-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.className = 'avatar-preview';
                        document.querySelector('.current-avatar').prepend(preview);
                    }
                    preview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Плавная анимация при загрузке
        document.addEventListener('DOMContentLoaded', () => {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s ease';
                document.body.style.opacity = '1';
            }, 50);
        });
    </script>
</body>
</html>
<style>
/* Темная тема в стиле Telegram для страницы редактирования группы */
@media (prefers-color-scheme: dark) {
    body {
        background-color: #0f0f0f !important;
        color: #e1e1e1 !important;
    }

    .group-card {
        background-color: #1e1e1e !important;
        border: 1px solid #2d2d2d !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
    }

    .card-header {
        background: linear-gradient(135deg, #2b5278, #1e3a5f) !important;
        border-bottom: 1px solid #2d2d2d !important;
    }

    .card-header::after {
        background: rgba(255, 255, 255, 0.05) !important;
    }

    .form-label {
        color: #e1e1e1 !important;
    }

    .form-control {
        background-color: #2d2d2d !important;
        border: 1px solid #3d3d3d !important;
        color: #e1e1e1 !important;
    }

    .form-control:focus {
        border-color: #5D93B5 !important;
        background-color: #3d3d3d !important;
        box-shadow: 0 0 0 3px rgba(93, 147, 181, 0.2) !important;
    }

    .form-control::placeholder {
        color: #a0a0a0 !important;
    }

    .avatar-preview {
        border: 3px solid #3d3d3d !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
    }

    .file-upload-label {
        background-color: #2d2d2d !important;
        border: 1px dashed #5D93B5 !important;
        color: #e1e1e1 !important;
    }

    .file-upload-label:hover {
        background-color: #3d3d3d !important;
        border-color: #7ab0d3 !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #5D93B5, #4A7A99) !important;
        border: none !important;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #4A7A99, #3a6280) !important;
        box-shadow: 0 4px 15px rgba(93, 147, 181, 0.3) !important;
    }

    .btn-secondary {
        background-color: #4a5568 !important;
        border: none !important;
    }

    .btn-secondary:hover {
        background-color: #3d4758 !important;
    }

    .alert-success {
        background-color: #242 !important;
        color: #51cf66 !important;
        border-left: 4px solid #2ed573 !important;
    }

    .alert-danger {
        background-color: #422 !important;
        color: #ff6b6b !important;
        border-left: 4px solid #ff4757 !important;
    }

    /* Стили для переключателей в темной теме */
    .slider {
        background-color: #4a5568 !important;
    }

    input:checked + .slider {
        background-color: #5D93B5 !important;
    }

    /* Иконки в кнопках */
    .btn i {
        color: inherit !important;
    }
}

/* Принудительное применение темной темы */
.dark-theme body {
    background-color: #0f0f0f !important;
    color: #e1e1e1 !important;
}

.dark-theme .group-card {
    background-color: #1e1e1e !important;
    border: 1px solid #2d2d2d !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
}

.dark-theme .card-header {
    background: linear-gradient(135deg, #2b5278, #1e3a5f) !important;
    border-bottom: 1px solid #2d2d2d !important;
}

.dark-theme .card-header::after {
    background: rgba(255, 255, 255, 0.05) !important;
}

.dark-theme .form-label {
    color: #e1e1e1 !important;
}

.dark-theme .form-control {
    background-color: #2d2d2d !important;
    border: 1px solid #3d3d3d !important;
    color: #e1e1e1 !important;
}

.dark-theme .form-control:focus {
    border-color: #5D93B5 !important;
    background-color: #3d3d3d !important;
    box-shadow: 0 0 0 3px rgba(93, 147, 181, 0.2) !important;
}

.dark-theme .form-control::placeholder {
    color: #a0a0a0 !important;
}

.dark-theme .avatar-preview {
    border: 3px solid #3d3d3d !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
}

.dark-theme .file-upload-label {
    background-color: #2d2d2d !important;
    border: 1px dashed #5D93B5 !important;
    color: #e1e1e1 !important;
}

.dark-theme .file-upload-label:hover {
    background-color: #3d3d3d !important;
    border-color: #7ab0d3 !important;
}

.dark-theme .btn-primary {
    background: linear-gradient(135deg, #5D93B5, #4A7A99) !important;
    border: none !important;
}

.dark-theme .btn-primary:hover {
    background: linear-gradient(135deg, #4A7A99, #3a6280) !important;
    box-shadow: 0 4px 15px rgba(93, 147, 181, 0.3) !important;
}

.dark-theme .btn-secondary {
    background-color: #4a5568 !important;
    border: none !important;
}

.dark-theme .btn-secondary:hover {
    background-color: #3d4758 !important;
}

.dark-theme .alert-success {
    background-color: #242 !important;
    color: #51cf66 !important;
    border-left: 4px solid #2ed573 !important;
}

.dark-theme .alert-danger {
    background-color: #422 !important;
    color: #ff6b6b !important;
    border-left: 4px solid #ff4757 !important;
}

.dark-theme .slider {
    background-color: #4a5568 !important;
}

.dark-theme input:checked + .slider {
    background-color: #5D93B5 !important;
}

/* Плавные переходы для темной темы */
.group-card,
.form-control,
.file-upload-label,
.btn,
.avatar-preview {
    transition: all 0.3s ease !important;
}

/* Улучшенные стили для мобильных устройств в темной теме */
@media (max-width: 768px) {
    @media (prefers-color-scheme: dark),
    .dark-theme {
        .edit-group-container {
            padding: 0 10px !important;
        }
        
        .group-card {
            margin: 10px 0 !important;
        }
        
        .card-body {
            padding: 1.5rem !important;
        }
        
        .action-buttons {
            flex-direction: column !important;
        }
        
        .btn {
            width: 100% !important;
            margin-bottom: 10px;
        }
    }
}

/* Дополнительные улучшения для формы редактирования группы */
@media (prefers-color-scheme: dark),
.dark-theme {
    /* Эффект при наведении на поля ввода */
    .form-control:hover {
        border-color: #5D93B5 !important;
    }
    
    /* Анимация для кнопок */
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    /* Эффект для карточки */
    .group-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5) !important;
    }
    
    /* Стили для текста в textarea */
    textarea.form-control {
        background-color: #2d2d2d !important;
        color: #e1e1e1 !important;
    }
    
    /* Стили для иконок в форме */
    .form-label i {
        color: #5D93B5 !important;
    }
}

/* Анимация появления формы */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.group-card {
    animation: slideInUp 0.6s ease-out !important;
}

/* Валидация полей ввода */
@media (prefers-color-scheme: dark),
.dark-theme {
    input:invalid,
    textarea:invalid {
        border-color: #ff4757 !important;
    }
    
    input:valid,
    textarea:valid {
        border-color: #2ed573 !important;
    }
    
    input:focus:invalid,
    textarea:focus:invalid {
        box-shadow: 0 0 0 3px rgba(255, 71, 87, 0.2) !important;
    }
    
    input:focus:valid,
    textarea:focus:valid {
        box-shadow: 0 0 0 3px rgba(46, 213, 115, 0.2) !important;
    }
}

/* Специальные стили для загрузки файла */
@media (prefers-color-scheme: dark),
.dark-theme {
    .file-upload-input::-webkit-file-upload-button {
        background-color: #5D93B5;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
    }
    
    .file-upload-input::-webkit-file-upload-button:hover {
        background-color: #4A7A99;
    }
}

/* Эффект для превью аватарки */
@media (prefers-color-scheme: dark),
.dark-theme {
    .avatar-preview:hover {
        transform: scale(1.08) !important;
        box-shadow: 0 6px 20px rgba(93, 147, 181, 0.4) !important;
    }
}

/* Стили для заголовка */
@media (prefers-color-scheme: dark),
.dark-theme {
    .card-header h2 {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }
}
</style>





<style>
:root {
    --primary-color: #1877f2;
    --secondary-color: #f0f2f5;
    --text-color: #050505;
    --text-secondary: #65676b;
    --card-bg: #ffffff;
    --border-color: #ddd;
}

.messages-container {
    display: flex;
    height: calc(100vh - 80px);
    max-width: 1200px;
    margin: 0 auto;
    background: var(--card-bg);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

/* Контакты */
.contacts-sidebar {
    width: 350px;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    height: 100%;
}

.contacts-header {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
}

.contacts-search {
    position: relative;
    margin-top: 10px;
}

.contacts-search input {
    width: 100%;
    padding: 10px 15px 10px 35px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    outline: none;
    background: var(--secondary-color);
}

.contacts-search i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.contacts-list {
    flex: 1;
    overflow-y: auto;
}

.contact-item {
    display: flex;
    padding: 12px 15px;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.2s;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-color);
}

.contact-item:hover, .contact-item.active {
    background: var(--secondary-color);
}

.contact-avatar {
    position: relative;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.contact-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.online-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #31a24c;
    border-radius: 50%;
    border: 2px solid var(--card-bg);
}

.contact-info {
    flex: 1;
    margin-left: 12px;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-preview {
    font-size: 0.9rem;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.contact-time {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-left: 10px;
}

/* Чат */
.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.chat-header {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: var(--secondary-color);
    background-image: url('assets/images/chat-bg-pattern.png');
    background-repeat: repeat;
    background-blend-mode: overlay;
}

.message {
    margin-bottom: 15px;
    display: flex;
}

.message-outgoing {
    justify-content: flex-end;
}

.message-incoming {
    justify-content: flex-start;
}

.message-bubble {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message-outgoing .message-bubble {
    background: var(--primary-color);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-incoming .message-bubble {
    background: var(--card-bg);
    color: var(--text-color);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.message-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 5px;
    text-align: right;
}

.message-incoming .message-time {
    color: var(--text-secondary);
    text-align: left;
}

.chat-input {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    background: var(--card-bg);
}

.chat-form {
    display: flex;
    align-items: center;
}

.chat-form input {
    flex: 1;
    padding: 12px 15px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    outline: none;
    background: var(--secondary-color);
}

.chat-form button {
    background: none;
    border: none;
    margin-left: 10px;
    cursor: pointer;
}

.send-icon {
    font-size: 1.5rem;
    color: var(--primary-color);
}

/* Пустой чат */
.empty-chat {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    text-align: center;
    padding: 20px;
}

.empty-chat i {
    font-size: 5rem;
    margin-bottom: 20px;
    color: var(--secondary-color);
}

.empty-chat h2 {
    font-size: 1.5rem;
    margin-bottom: 10px;
    color: var(--text-color);
}

/* Адаптивность */
@media (max-width: 992px) {
    .contacts-sidebar {
        width: 300px;
    }
}

@media (max-width: 768px) {
    .messages-container {
        height: calc(100vh - 60px);
    }
    
    .contacts-sidebar {
        width: 100%;
        display: <?= isset($_GET['user_id']) ? 'none' : 'flex' ?>;
    }
    
    .chat-container {
        display: <?= isset($_GET['user_id']) ? 'flex' : 'none' ?>;
    }
    
    .mobile-back-btn {
        display: block;
        margin-right: 15px;
        font-size: 1.2rem;
    }
}

@media (min-width: 769px) {
    .mobile-back-btn {
        display: none !important;
    }
}
/* Чат */
.chat-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative; /* Добавляем для позиционирования */
    margin-right: -160px;
}

.chat-messages {
    flex: 1;
    padding: 15px;
    overflow-y: auto;
    background: var(--secondary-color);
    background-image: url('assets/images/chat-bg-pattern.png');
    background-repeat: repeat;
    background-blend-mode: overlay;
    padding-bottom: 80px; /* Добавляем отступ снизу для input */
}

.message {
    margin-bottom: 15px;
    display: flex;
    width: 100%; /* Занимаем всю ширину */
}

.message-outgoing {
    justify-content: flex-end;
    padding-left: 15%; /* Уменьшаем отступ справа */
}

.message-incoming {
    justify-content: flex-start;
    padding-right: 15%; /* Уменьшаем отступ слева */
}

.message-bubble {
    max-width: 85%; /* Увеличиваем максимальную ширину */
    min-width: 30%; /* Добавляем минимальную ширину */
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

/* Фиксированное поле ввода */
.chat-input {
    padding: 15px;
    border-top: 1px solid var(--border-color);
    background: var(--card-bg);
    position: fixed; /* Фиксируем внизу */
    bottom: 0;
    left: 0;
    right: 0;
    max-width: 1200px;
    margin: 0 auto;
    box-sizing: border-box;
}

/* Адаптация для мобильных */
@media (max-width: 768px) {
    .messages-container {
        height: calc(100vh - 60px);
    }
    
    .contacts-sidebar {
        width: 100%;
        display: <?= isset($_GET['user_id']) ? 'none' : 'flex' ?>;
    }
    
    .chat-container {
        display: <?= isset($_GET['user_id']) ? 'flex' : 'none' ?>;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        height: 100vh;
        margin-top: 70px;
    }
    .chat-input {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        top: 600px;
        max-width: 100%;
    }
    
    .message-outgoing {
        padding-left: 10%;
    }
    
    .message-incoming {
        padding-right: 10%;
    }
    
    .message-bubble {
        max-width: 90%;
    }
    .chat-messages{
        margin-bottom: 200px;
    }
}

/* Для iPhone с "челкой" */
@supports(padding-bottom: env(safe-area-inset-bottom)) {
    .chat-input {
        padding-bottom: calc(15px + env(safe-area-inset-bottom));
    }
    
    .chat-messages {
        padding-bottom: calc(80px + env(safe-area-inset-bottom));
    }
}
:root {
    --tg-primary: #182533;
    --tg-secondary: #17212b;
    --tg-accent: #2b5278;
    --tg-message-out: #2b5278;
    --tg-message-in: #182533;
    --tg-text-primary: #ffffff;
    --tg-text-secondary: #8696a8;
    --tg-border: #1e2c3a;
    --tg-hover: #1e2c3a;
    --tg-active: #2b5278;
}

/* Применяем темную тему */
@media (prefers-color-scheme: dark) {
    .messages-container {
        background: var(--tg-secondary);
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }
    .navbar{
        background: black;
    }
    /* Контакты */
    .contacts-sidebar {
        background: var(--tg-primary);
        border-right-color: var(--tg-border);
    }
    .mobile-nav-item.active{
        color: '#0088cc';
    }
    .contacts-header {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .contacts-search input {
        background: var(--tg-secondary);
        border-color: var(--tg-border);
        color: var(--tg-text-primary);
    }

    .contacts-search input::placeholder {
        color: var(--tg-text-secondary);
    }

    .contact-item {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .contact-item:hover {
        background: var(--tg-hover);
    }

    .contact-item.active {
        background: var(--tg-active);
    }

    .contact-name {
        color: var(--tg-text-primary);
    }

    .contact-preview {
        color: var(--tg-text-secondary);
    }

    .contact-time {
        color: var(--tg-text-secondary);
    }

    /* Чат */
    .chat-container {
        background: var(--tg-secondary);
    }

    .chat-header {
        background: var(--tg-primary);
        border-bottom-color: var(--tg-border);
    }

    .chat-messages {
        background: var(--tg-secondary);
        background-image: none;
    }

    .message-outgoing .message-bubble {
        background: var(--tg-message-out);
        color: var(--tg-text-primary);
        border-bottom-right-radius: 4px;
    }

    .message-incoming .message-bubble {
        background: var(--tg-message-in);
        color: var(--tg-text-primary);
        border: 1px solid var(--tg-border);
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .message-time {
        color: var(--tg-text-secondary);
    }

    .chat-input {
        background: var(--tg-primary);
        border-top-color: var(--tg-border);
    }

    .chat-form input {
        background: var(--tg-secondary);
        border-color: var(--tg-border);
        color: var(--tg-text-primary);
    }

    .chat-form input::placeholder {
        color: var(--tg-text-secondary);
    }

    .send-icon {
        color: var(--tg-accent);
    }

    /* Пустой чат */
    .empty-chat {
        color: var(--tg-text-secondary);
        background: var(--tg-secondary);
    }

    .empty-chat h2 {
        color: var(--tg-text-primary);
    }

    .empty-chat i {
        color: var(--tg-text-secondary);
    }

    /* Скроллбар */
    .contacts-list::-webkit-scrollbar,
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .contacts-list::-webkit-scrollbar-track,
    .chat-messages::-webkit-scrollbar-track {
        background: var(--tg-primary);
    }

    .contacts-list::-webkit-scrollbar-thumb,
    .chat-messages::-webkit-scrollbar-thumb {
        background: var(--tg-border);
        border-radius: 3px;
    }

    .contacts-list::-webkit-scrollbar-thumb:hover,
    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: var(--tg-text-secondary);
    }

    /* Мобильная версия */
    @media (max-width: 768px) {
        .messages-container {
            background: var(--tg-secondary);
        }
        
        .contacts-sidebar {
            background: var(--tg-primary);
        }
        
        .chat-container {
            background: var(--tg-secondary);
        }
        
        .chat-input {
            background: var(--tg-primary);
        }
    }
}

/* Дополнительные улучшения для Telegram-like стиля */
.message-bubble {
    max-width: 65%;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.95rem;
    line-height: 1.4;
}

.message-outgoing .message-bubble {
    border-bottom-right-radius: 0;
}

.message-incoming .message-bubble {
    border-bottom-left-radius: 0;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
    margin-top: 3px;
}

.contact-avatar {
    width: 40px;
    height: 40px;
}

.online-badge {
    background: #00c853;
    width: 10px;
    height: 10px;
    border: 2px solid var(--tg-primary);
}

.contacts-search i {
    color: var(--tg-text-secondary);
}

.mobile-back-btn {
    color: var(--tg-text-primary);
}
</style>
<style>
/* Исправление текста в боковом меню для темной темы */
@media (prefers-color-scheme: dark) {
    .sidebar-item {
        color: #ffffff !important;
    }
    .mobile-bottom-nav{
        background: #1a1a1a;
    }
    .sidebar-item:hover,
    .sidebar-item.active {
        color: var(--tg-primary) !important;
    }
    .sidebar-menu{
        background: black;
    }
    .sidebar-item i {
        color: #a8a8a8 !important;
    }
    .sidebar-item:hover i,
    .sidebar-item.active i {
        color: #0088cc !important;
    }
    
    .sidebar-item span {
        color: inherit !important;
    }
    .mobile-nav-item.active{
        color: #0088cc !important;
    }
    /* Улучшение контрастности */
    .sidebar-header {
        background: #1a1a1a !important;
        border-bottom: 1px solid var(--tg-border) !important;
    }
    
    .sidebar-items {
        background: #1a1a1a !important;
        color: #1a1a1a1a !important;
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
        border-left-color: #0088cc !important;
        color: #0088cc !important;
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
    background-color: #1a1a1a !important;
}

[data-theme="dark"] .sidebar-item:hover,
[data-theme="dark"] .sidebar-item.active,
.dark-mode .sidebar-item:hover,

body.dark .sidebar-item:hover,
body.dark .sidebar-item.active {
    color: #0088cc !important;
}

/* Улучшение для мобильной версии */
@media (max-width: 768px) {
    body{
        margin-bottom: 70px !important;
    }
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
        background: #1a1a1a !important;
        color: #1a1a1a1a !important;
    }

.sidebar-item {
    background: transparent !important;
}

.sidebar-item:hover {
    background: #2a2a2a !important;
}

.sidebar-item.active {
    background: #16303d !important;
    color: #0088cc !important;
}
</style>
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
<?php require_once 'includes/footer.php'; ?>