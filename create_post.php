<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

$current_user_id = getCurrentUser($db)['id'];

if (!isset($current_user_id)) {
    //header('Location: login.php');
    exit;
}


$current_user_id = getCurrentUser($db)['id'];

// Check if this is for a challenge
$challenge_id = isset($_GET['challenge_id']) ? intval($_GET['challenge_id']) : null;
$challenge = null;

if ($challenge_id) {
    // Verify the challenge exists and user is a participant
    $stmt = $db->prepare("
        SELECT c.*, 
               u1.username as challenger_username, u1.full_name as challenger_name, u1.avatar as challenger_avatar,
               u2.username as opponent_username, u2.full_name as opponent_name, u2.avatar as opponent_avatar
        FROM challenges c
        JOIN users u1 ON c.challenger_id = u1.id
        JOIN users u2 ON c.opponent_id = u2.id
        WHERE c.id = ? AND (c.challenger_id = ? OR c.opponent_id = ?) AND c.status = 'active'
    ");
    $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $current_user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $challenge = $result->fetchArray(SQLITE3_ASSOC);

    if (!$challenge) {
        $_SESSION['error'] = "Invalid challenge or you're not a participant";
        header('Location: sport.php');
        exit;
    }

    // Check if user already has a post for this challenge
    $stmt = $db->prepare("SELECT id FROM posts WHERE user_id = ? AND challenge_id = ?");
    $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $challenge_id, SQLITE3_INTEGER);
    $existing_post = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $image = isset($_FILES['image']['name']) ? $_FILES['image']['name'] : null;
    $feeling = isset($_POST['feeling']) ? $_POST['feeling'] : null;
    $challenge_id = isset($_POST['challenge_id']) ? intval($_POST['challenge_id']) : null;

    if (!empty($content)) {
        // Handle image upload
        $image_path = null;
        if ($image) {
            $target_dir = "assets/images/posts/";
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;

            // Check if image file is a actual image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                $_SESSION['error'] = "File is not an image.";
            } elseif ($_FILES['image']['size'] > 5000000) { // 5MB limit
                $_SESSION['error'] = "Sorry, your file is too large.";
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $new_filename;
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            }
        }

        if (!isset($_SESSION['error'])) {
            // Check if we're updating an existing post
            if ($challenge_id && $existing_post) {
                $stmt = $db->prepare("
                    UPDATE posts 
                    SET content = ?, image = COALESCE(?, image), feeling = COALESCE(?, feeling), updated_at = datetime('now')
                    WHERE id = ?
                ");
                $stmt->bindValue(1, $content, SQLITE3_TEXT);
                $stmt->bindValue(2, $image_path, SQLITE3_TEXT);
                $stmt->bindValue(3, $feeling, SQLITE3_TEXT);
                $stmt->bindValue(4, $existing_post['id'], SQLITE3_INTEGER);
                $result = $stmt->execute();
            } else {
                // Create new post
                $stmt = $db->prepare("
                    INSERT INTO posts 
                    (user_id, content, image, feeling, challenge_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, datetime('now'))
                ");
                $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
                $stmt->bindValue(2, $content, SQLITE3_TEXT);
                $stmt->bindValue(3, $image_path, SQLITE3_TEXT);
                $stmt->bindValue(4, $feeling, SQLITE3_TEXT);
                $stmt->bindValue(5, $challenge_id, SQLITE3_INTEGER);
                $result = $stmt->execute();
            }

            if ($result) {
                $_SESSION['message'] = $existing_post ? "Post updated successfully!" : "Post created successfully!";
                
                if ($challenge_id) {
                    //header("Location: sport.php");
                    echo '<script> window.location.href = "sport.php" </script>';
                } else {
                    //header("Location: index.php");
                    echo '<script> window.location.href = "index.php" </script>';
                }
                exit;
            } else {
                $_SESSION['error'] = "Error saving post: " . $db->lastErrorMsg();
            }
        }
    } else {
        $_SESSION['error'] = "Post content cannot be empty";
    }
}

// Get existing post data if editing
$post_data = null;
if ($existing_post) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->bindValue(1, $existing_post['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $post_data = $result->fetchArray(SQLITE3_ASSOC);
}

// Close database connection

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $challenge ? 'Challenge Post' : 'Create Post'; ?> Meme Fight Club</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #d10000;
            --secondary-color: #000000;
            --accent-color: #f0f0f0;
            --text-color: #333;
            --light-text: #fff;
            --border-color: #ddd;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: var(--secondary-color);
            color: var(--light-text);
            padding: 15px 0;
            margin-bottom: 30px;
            border-bottom: 4px solid var(--primary-color);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--light-text);
            text-decoration: none;
        }
        
        .logo span {
            color: var(--primary-color);
        }
        
        .user-balance {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        
        .section-title {
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--secondary-color);
        }
        
        .post-form-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 10px;
            display: <?php echo $post_data && $post_data['image'] ? 'block' : 'none'; ?>;
        }
        
        .feeling-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        
        .feeling-option {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .feeling-option:hover {
            background-color: var(--accent-color);
        }
        
        .feeling-option.selected {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #b00000;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #333;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .challenge-info {
            background-color: var(--accent-color);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .fighters {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .fighter {
            text-align: center;
            flex: 1;
            padding: 10px;
        }
        
        .fighter-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border-color);
            margin-bottom: 10px;
        }
        
        .fighter-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .vs {
            font-size: 20px;
            font-weight: bold;
            color: var(--secondary-color);
            margin: 0 15px;
        }
        
        .challenge-details {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        .stake-amount {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .fighters {
                flex-direction: column;
            }
            
            .vs {
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <a href="index.php" class="logo">Meme<span>Fight Club</span></a>
            <div class="user-balance">
                <i class="fas fa-coins"></i> <?php echo number_format(getUserBalance($db, $current_user_id)); ?> CC
            </div>
        </div>
    </header>
    
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <h1 class="section-title">
            <?php echo $challenge ? 'Challenge Post' : 'Create New Post'; ?>
        </h1>
        
        <?php if ($challenge): ?>
            <div class="challenge-info">
                <div class="fighters">
                    <div class="fighter">
                        <img src="/assets/images/avatars/<?php echo $challenge['challenger_avatar']; ?>" alt="<?php echo $challenge['challenger_name']; ?>" class="fighter-avatar">
                        <div class="fighter-name"><?php echo $challenge['challenger_name']; ?></div>
                    </div>
                    
                    <div class="vs">VS</div>
                    
                    <div class="fighter">
                        <img src="/assets/images/avatars/<?php echo $challenge['opponent_avatar']; ?>" alt="<?php echo $challenge['opponent_name']; ?>" class="fighter-avatar">
                        <div class="fighter-name"><?php echo $challenge['opponent_name']; ?></div>
                    </div>
                </div>
                
                <div class="challenge-details">
                    <div class="detail-row">
                        <span class="detail-label">Призовой фонд:</span>
                        <span class="stake-amount"><?php echo number_format($challenge['stake_amount']); ?> CC</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Время окончания:</span>
                        <span><?php echo time_elapsed_string($challenge['expires_at']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Правила:</span>
                        <span>Получивший больше лайков и комменатриев побеждает!</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="post-form-card">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($challenge_id): ?>
                    <input type="hidden" name="challenge_id" value="<?php echo $challenge_id; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="content" class="form-label">Создание поста</label>
                    <textarea id="content" name="content" class="form-control" required><?php echo htmlspecialchars($post_data['content'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image" class="form-label">Изображение</label>
                    <input type="file" id="image" name="image" class="form-control" accept="image/*">
                    
                    <?php if ($post_data && $post_data['image']): ?>
                        <img src="/assets/images/posts/<?php echo $post_data['image']; ?>" alt="Current post image" class="image-preview" id="image-preview">
                    <?php else: ?>
                        <img src="" alt="Image preview" class="image-preview" id="image-preview">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Эмоции</label>
                    <div class="feeling-selector">
                        <div class="feeling-option <?php echo ($post_data['feeling'] ?? '') == 'happy' ? 'selected' : ''; ?>" onclick="selectFeeling('happy')">
                            <i class="far fa-smile"></i> Радость
                        </div>
                        <div class="feeling-option <?php echo ($post_data['feeling'] ?? '') == 'sad' ? 'selected' : ''; ?>" onclick="selectFeeling('excited')">
                            <i class="far fa-sad-tear"></i> Грусть
                        </div>
                        <div class="feeling-option <?php echo ($post_data['feeling'] ?? '') == 'angry' ? 'selected' : ''; ?>" onclick="selectFeeling('confident')">
                            <i class="far fa-angry"></i> Гнев
                        </div>
                        <div class="feeling-option <?php echo ($post_data['feeling'] ?? '') == 'loved' ? 'selected' : ''; ?>" onclick="selectFeeling('focused')">
                            <i class="far fa-heart"></i> Любовь
                        </div>
                    </div>
                    <input type="hidden" name="feeling" id="feeling" value="<?php echo $post_data['feeling'] ?? ''; ?>">
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $existing_post ? 'Update Post' : 'Create Post'; ?>
                    </button>
                    <a href="<?php echo $challenge ? 'sport.php' : 'index.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
        
        // Feeling selection
        function selectFeeling(feeling) {
            document.querySelectorAll('.feeling-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById('feeling').value = feeling;
        }
        
        // Mention detection
        document.getElementById('content').addEventListener('keyup', function(e) {
            if (e.key === '@') {
                // Here you could implement a mention dropdown
                console.log('Mention started');
            }
        });
    </script>
</body>
</html>