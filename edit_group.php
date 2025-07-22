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

<?php require_once 'includes/footer.php'; ?>

<?php require_once 'includes/footer.php'; ?>