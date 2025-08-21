<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
checkExpiredChallenges($db);

if (!isset($_SESSION['user_id'])) {
    echo '<script> window.location.href = "login.php"</script>';
    exit;
}
$current_user_id = getCurrentUser($db)['id'];
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_challenge'])) {
        $opponent_id = $_POST['opponent_id'];
        $stake_amount = $_POST['stake_amount'];
        $duration = $_POST['duration'];
        
        if (validateUserId($opponent_id) && $opponent_id != $current_user_id && $stake_amount > 0) {
            $user_balance = getUserBalance($db, $current_user_id);
            
            if ($user_balance >= $stake_amount) {
                // Create challenge
                $stmt = $db->prepare("
                    INSERT INTO challenges 
                    (challenger_id, opponent_id, stake_amount, status, created_at, expires_at) 
                    VALUES (?, ?, ?, 'pending', datetime('now'), datetime('now', ?))
                ");
                $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
                $stmt->bindValue(2, $opponent_id, SQLITE3_INTEGER);
                $stmt->bindValue(3, $stake_amount, SQLITE3_INTEGER);
                $stmt->bindValue(4, "+$duration hours", SQLITE3_TEXT);
                
                if ($stmt->execute()) {
                    $challenge_id = $db->lastInsertRowID();
                    
                    // Add notification
                    addNotification($db, $opponent_id, 'challenge', $current_user_id, null, $challenge_id);
                    
                    $_SESSION['message'] = "Запрос отправлен!";
                }
            } else {
                $_SESSION['error'] = "Не достаточно СС для участия в поединке";
            }
        }
    } elseif (isset($_POST['accept_challenge'])) {
        $challenge_id = $_POST['challenge_id'];
        
        $stmt = $db->prepare("SELECT * FROM challenges WHERE id = ? AND opponent_id = ? AND status = 'pending'");
        $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $challenge = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($challenge) {
            $stake_amount = $challenge['stake_amount'];
            $user_balance = getUserBalance($db, $current_user_id);
            
            if ($user_balance >= $stake_amount) {
                // Lock the stake amounts
                addCurrency($db, $current_user_id, -$stake_amount, "Locked for challenge #$challenge_id");
                addCurrency($db, $challenge['challenger_id'], -$stake_amount, "Locked for challenge #$challenge_id");
                
                // Update challenge status
                $stmt = $db->prepare("UPDATE challenges SET status = 'active', started_at = datetime('now') WHERE id = ?");
                $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
                $stmt->execute();
                
                $_SESSION['message'] = "Вызов принят. Удачи!";
            } else {
                $_SESSION['error'] = "Недостатоно СС для участия в поединке";
            }
        }
    } elseif (isset($_POST['place_bet'])) {
        $challenge_id = $_POST['challenge_id'];
        $bet_amount = $_POST['bet_amount'];
        $bet_on = $_POST['bet_on'];
        
        if ($bet_amount > 0) {
            $user_balance = getUserBalance($db, $current_user_id);
            
            if ($user_balance >= $bet_amount) {
                $stmt = $db->prepare("
                    INSERT INTO challenge_bets 
                    (challenge_id, user_id, bet_on, amount, created_at) 
                    VALUES (?, ?, ?, ?, datetime('now'))
                ");
                $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
                $stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
                $stmt->bindValue(3, $bet_on, SQLITE3_TEXT);
                $stmt->bindValue(4, $bet_amount, SQLITE3_INTEGER);
                
                if ($stmt->execute()) {
                    addCurrency($db, $current_user_id, -$bet_amount, "Bet on challenge #$challenge_id");
                    $_SESSION['message'] = "Ставка сыграна успешно!";
                }
            } else {
                $_SESSION['error'] = "Недостаточно СС для ставки";
            }
        }
    }
}

// Get user's current balance
$user_balance = getUserBalance($db, $current_user_id);

// Get all active challenges
$active_challenges = [];

$stmt = $db->prepare("
    SELECT c.*, 
           u1.username as challenger_username, u1.full_name as challenger_name, u1.avatar as challenger_avatar,
           u2.username as opponent_username, u2.full_name as opponent_name, u2.avatar as opponent_avatar
    FROM challenges c
    JOIN users u1 ON c.challenger_id = u1.id
    JOIN users u2 ON c.opponent_id = u2.id
    WHERE c.status IN ('pending', 'active')
    ORDER BY c.created_at DESC
");
$result = $stmt->execute();






function checkExpiredChallenges($db) {
    // Находим активные челленджи, у которых истёк срок
    $stmt = $db->prepare("
        SELECT * FROM challenges 
        WHERE status = 'active' AND expires_at <= datetime('now')
    ");
    $result = $stmt->execute();

    while ($challenge = $result->fetchArray(SQLITE3_ASSOC)) {
        // Определяем победителя
        $challenger_post = getChallengePost($db, $challenge['id'], $challenge['challenger_id']);
        $opponent_post = getChallengePost($db, $challenge['id'], $challenge['opponent_id']);

        $challenger_score = ($challenger_post ? $challenger_post['likes_count'] + $challenger_post['comments_count'] : 0);
        $opponent_score = ($opponent_post ? $opponent_post['likes_count'] + $opponent_post['comments_count'] : 0);

        if ($challenger_score > $opponent_score) {
            $winner_id = $challenge['challenger_id'];
        } elseif ($opponent_score > $challenger_score) {
            $winner_id = $challenge['opponent_id'];
        } else {
            $winner_id = null; // Ничья
        }

        // Обновляем статус челленджа
        $stmt = $db->prepare("
            UPDATE challenges 
            SET status = 'completed', 
                winner_id = ?,
                completed_at = datetime('now')
            WHERE id = ?
        ");
        $stmt->bindValue(1, $winner_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $challenge['id'], SQLITE3_INTEGER);
        $stmt->execute();

        // Начисляем приз (если не ничья)
        if ($winner_id) {
            $prize = $challenge['stake_amount'] * 1.8; // Например, 80% от общей ставки
            addCurrency($db, $winner_id, $prize, "Prize for winning challenge #{$challenge['id']}");
        }
    }

    // Отменяем просроченные ожидающие челленджи
    $stmt = $db->prepare("
        UPDATE challenges 
        SET status = 'cancelled'
        WHERE status = 'pending' AND expires_at <= datetime('now')
    ");
    $stmt->execute();
}








while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    // Get challenge posts if challenge is active
    if ($row['status'] == 'active') {
        $row['challenger_post'] = getChallengePost($db, $row['id'], $row['challenger_id']);
        $row['opponent_post'] = getChallengePost($db, $row['id'], $row['opponent_id']);
        
        // Calculate scores
        if ($row['challenger_post']) {
            $row['challenger_score'] = $row['challenger_post']['likes_count'] + $row['challenger_post']['comments_count'];
        } else {
            $row['challenger_score'] = 0;
        }
        
        if ($row['opponent_post']) {
            $row['opponent_score'] = $row['opponent_post']['likes_count'] + $row['opponent_post']['comments_count'];
        } else {
            $row['opponent_score'] = 0;
        }
    }
    
    // Get bet information
    $row['user_bet'] = getUserBetOnChallenge($db, $row['id'], $current_user_id);
    $row['total_bets'] = getChallengeTotalBets($db, $row['id']);
    
    $active_challenges[] = $row;
}

// Get completed challenges
$completed_challenges = [];
$stmt = $db->prepare("
    SELECT c.*, 
           u1.username as challenger_username, u1.full_name as challenger_name, u1.avatar as challenger_avatar,
           u2.username as opponent_username, u2.full_name as opponent_name, u2.avatar as opponent_avatar
    FROM challenges c
    JOIN users u1 ON c.challenger_id = u1.id
    JOIN users u2 ON c.opponent_id = u2.id
    WHERE (c.challenger_id = ? OR c.opponent_id = ?) 
    AND c.status IN ('completed', 'cancelled')
    ORDER BY c.completed_at DESC
    LIMIT 10
");
$stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
$stmt->bindValue(2, $current_user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($row['status'] == 'completed') {
        $row['winner_post'] = getChallengePost($db, $row['id'], $row['winner_id']);
        $row['loser_post'] = getChallengePost($db, $row['id'], $row['winner_id'] == $row['challenger_id'] ? $row['opponent_id'] : $row['challenger_id']);
        
        // Calculate scores
        if ($row['winner_post']) {
            $row['winner_score'] = $row['winner_post']['likes_count'] + $row['winner_post']['comments_count'];
        } else {
            $row['winner_score'] = 0;
        }
        
        if ($row['loser_post']) {
            $row['loser_score'] = $row['loser_post']['likes_count'] + $row['loser_post']['comments_count'];
        } else {
            $row['loser_score'] = 0;
        }
    }
    
    $row['user_bet'] = getUserBetOnChallenge($db, $row['id'], $current_user_id);
    $row['total_bets'] = getChallengeTotalBets($db, $row['id']);
    
    $completed_challenges[] = $row;
}

// Get list of users for challenge creation
$users = [];
$stmt = $db->prepare("SELECT id, username, full_name, avatar FROM users WHERE id != ? ORDER BY username");
$stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $users[] = $row;
}

// Helper functions
function getChallengePost($db, $challenge_id, $user_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
        FROM posts p
        WHERE p.user_id = ? AND p.challenge_id = ?
        LIMIT 1
    ");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $challenge_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function getUserBetOnChallenge($db, $challenge_id, $user_id) {
    $stmt = $db->prepare("
        SELECT * FROM challenge_bets 
        WHERE challenge_id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function getChallengeTotalBets($db, $challenge_id) {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN bet_on = 'challenger' THEN amount ELSE 0 END) as challenger_bets,
            SUM(CASE WHEN bet_on = 'opponent' THEN amount ELSE 0 END) as opponent_bets,
            COUNT(*) as total_bets
        FROM challenge_bets 
        WHERE challenge_id = ?
    ");
    $stmt->bindValue(1, $challenge_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

// Close database connection

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meme Fight Club - Challenges</title>
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
            max-width: 1200px;
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
        
        .challenge-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }
        
        .challenge-header {
            background-color: var(--secondary-color);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .challenge-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: var(--secondary-color);
        }
        
        .status-active {
            background-color: var(--info-color);
            color: white;
        }
        
        .status-completed {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-cancelled {
            background-color: var(--danger-color);
            color: white;
        }
        
        .challenge-body {
            padding: 20px;
        }
        
        .fighters {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .fighter {
            text-align: center;
            flex: 1;
            padding: 10px;
        }
        
        .fighter-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border-color);
            margin-bottom: 10px;
        }
        
        .fighter-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .fighter-score {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .vs {
            font-size: 24px;
            font-weight: bold;
            color: var(--secondary-color);
            margin: 0 20px;
        }
        
        .challenge-details {
            margin: 20px 0;
            padding: 15px;
            background-color: var(--accent-color);
            border-radius: 5px;
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
            font-size: 18px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .time-remaining {
            color: var(--danger-color);
            font-weight: bold;
        }
        
        .post-preview {
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            background-color: white;
        }
        
        .post-content {
            margin-bottom: 10px;
        }
        
        .post-stats {
            display: flex;
            justify-content: space-between;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 15px;
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
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #333;
        }
        
        .betting-section {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--accent-color);
            border-radius: 5px;
        }
        
        .betting-odds {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }
        
        .bet-option {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            margin: 0 5px;
        }
        
        .bet-option:hover {
            background-color: #e0e0e0;
        }
        
        .bet-option.selected {
            background-color: var(--primary-color);
            color: white;
        }
        
        .bet-amount {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .bet-total {
            font-weight: bold;
            margin-top: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
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
        
        .tab-container {
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            border-bottom-color: var(--primary-color);
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 768px) {
            .fighters {
                flex-direction: column;
            }
            
            .vs {
                margin: 10px 0;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <a href="index.php" class="logo">Meme <span> Fight Club</span></a>
            <div class="user-balance">
                <i class="fas fa-coins"></i> <?php echo number_format($user_balance); ?> CC
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
        
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('active-challenges')">Активные поединки</div>
                <div class="tab" onclick="switchTab('completed-challenges')">Завершенный поединки</div>
                <div class="tab" onclick="switchTab('create-challenge')">Бросить вызов</div>
            </div>
            
            <div id="active-challenges" class="tab-content active">
                <h2 class="section-title">Активные поединки</h2>
                
                <?php if (empty($active_challenges)): ?>
                    <p>Нет активных поединков.</p>
                <?php else: ?>
                    <?php foreach ($active_challenges as $challenge): ?>
                        <div class="challenge-card">
                            <div class="challenge-header">
                                <div>
                                    <span class="challenge-status status-<?php echo $challenge['status']; ?>">
                                        <?php echo strtoupper($challenge['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="time-remaining">
                                        <?php if ($challenge['status'] == 'pending'): ?>
                                            Истекает: <?php echo time_elapsed_string($challenge['expires_at']); ?>
                                        <?php elseif ($challenge['status'] == 'active'): ?>
                                            Конец: <?php echo time_elapsed_string($challenge['expires_at']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="challenge-body">
                                <div class="fighters">
                                    <div class="fighter">
                                        <img src="/assets/images/avatars/<?php echo $challenge['challenger_avatar']; ?>" alt="<?php echo $challenge['challenger_name']; ?>" class="fighter-avatar">
                                        <div class="fighter-name"><?php echo $challenge['challenger_name']; ?></div>
                                        <?php if ($challenge['status'] == 'active'): ?>
                                            <div class="fighter-score"><?php echo $challenge['challenger_score'] ?? 0; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="vs">VS</div>
                                    
                                    <div class="fighter">
                                        <img src="/assets/images/avatars/<?php echo $challenge['opponent_avatar']; ?>" alt="<?php echo $challenge['opponent_name']; ?>" class="fighter-avatar">
                                        <div class="fighter-name"><?php echo $challenge['opponent_name']; ?></div>
                                        <?php if ($challenge['status'] == 'active'): ?>
                                            <div class="fighter-score"><?php echo $challenge['opponent_score'] ?? 0; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="challenge-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Призовой фонд:</span>
                                        <span class="stake-amount"><?php echo number_format($challenge['stake_amount']); ?> CC</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Создан:</span>
                                        <span><?php echo time_elapsed_string($challenge['created_at']); ?></span>
                                    </div>
                                    <?php if ($challenge['status'] == 'active'): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Время окончания:</span>
                                            <span><?php echo time_elapsed_string($challenge['expires_at']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($challenge['status'] == 'active'): ?>
                                    <?php if (!empty($challenge['challenger_post'])): ?>
                                        <div class="post-preview">
                                            <h4>Пост mr. <?php echo $challenge['challenger_name']; ?></h4>
                                            <div class="post-content"><?php echo $challenge['challenger_post']['content']; ?></div>
                                            <div class="post-stats">
                                                <span><i class="fas fa-thumbs-up"></i> <?php echo $challenge['challenger_post']['likes_count']; ?> Likes</span>
                                                <span><i class="fas fa-comment"></i> <?php echo $challenge['challenger_post']['comments_count']; ?> Comments</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="post-preview">
                                            <p>Mr. <?php echo $challenge['challenger_name']; ?> еще не создал(а) пост для этого поединка.</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($challenge['opponent_post'])): ?>
                                        <div class="post-preview">
                                            <h4>Пост mr. <?php echo $challenge['opponent_name']; ?></h4>
                                            <div class="post-content"><?php echo $challenge['opponent_post']['content']; ?></div>
                                            <div class="post-stats">
                                                <span><i class="fas fa-thumbs-up"></i> <?php echo $challenge['opponent_post']['likes_count']; ?> Likes</span>
                                                <span><i class="fas fa-comment"></i> <?php echo $challenge['opponent_post']['comments_count']; ?> Comments</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="post-preview">
                                            <p>Mr. <?php echo $challenge['opponent_name']; ?> еще не создал(а) пост для этого поединка.</p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="betting-section">
                                        <h4>Ставки</h4>
                                        
                                        <?php if ($challenge['user_bet']): ?>
                                            <p>Ты уже поставил <?php echo number_format($challenge['user_bet']['amount']); ?> CC на mr.  
                                            <?php echo $challenge['user_bet']['bet_on'] == 'challenger' ? $challenge['challenger_name'] : $challenge['opponent_name']; ?>.</p>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                                
                                                <div class="betting-odds">
                                                    <div class="bet-option" onclick="selectBetOption(this, 'challenger')">
                                                        <h5><?php echo $challenge['challenger_name']; ?></h5>
                                                        <p>Счет: <?php echo $challenge['challenger_score']; ?></p>
                                                    </div>
                                                    
                                                    <div class="bet-option" onclick="selectBetOption(this, 'opponent')">
                                                        <h5><?php echo $challenge['opponent_name']; ?></h5>
                                                        <p>Счет: <?php echo $challenge['opponent_score']; ?></p>
                                                    </div>
                                                </div>
                                                
                                                <input type="hidden" name="bet_on" id="bet_on" value="">
                                                <input type="number" name="bet_amount" class="bet-amount" placeholder="Введи сумму ставки" min="1" max="<?php echo $user_balance; ?>">
                                                
                                                <div class="action-buttons">
                                                    <button type="submit" name="place_bet" class="btn btn-primary">Place Bet</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <div class="bet-total">
                                            Ставки: <?php echo number_format($challenge['total_bets']['challenger_bets']); ?> CC на mr. <?php echo $challenge['challenger_name']; ?> | 
                                            <?php echo number_format($challenge['total_bets']['opponent_bets']); ?> CC на mr. <?php echo $challenge['opponent_name']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <?php if ($challenge['status'] == 'pending' && $challenge['opponent_id'] == $current_user_id): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                            <button type="submit" name="accept_challenge" class="btn btn-success">Принять вызов</button>
                                        </form>
                                        <button class="btn btn-danger">Отклонить</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($challenge['status'] == 'active' && ($challenge['challenger_id'] == $current_user_id || $challenge['opponent_id'] == $current_user_id)): ?>
                                        <a href="create_post.php?challenge_id=<?php echo $challenge['id']; ?>" class="btn btn-primary">
                                            <?php echo ($challenge['challenger_id'] == $current_user_id && empty($challenge['challenger_post'])) || 
                                                  ($challenge['opponent_id'] == $current_user_id && empty($challenge['opponent_post'])) ? 
                                                  'Create Post' : 'Update Post'; ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="challenge_details.php?id=<?php echo $challenge['id']; ?>" class="btn btn-secondary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="completed-challenges" class="tab-content">
                <h2 class="section-title">Completed Challenges</h2>
                
                <?php if (empty($completed_challenges)): ?>
                    <p>Нет завершенных поединков.</p>
                <?php else: ?>
                    <?php foreach ($completed_challenges as $challenge): ?>
                        <div class="challenge-card">
                            <div class="challenge-header">
                                <div>
                                    <span class="challenge-status status-<?php echo $challenge['status']; ?>">
                                        <?php echo strtoupper($challenge['status']); ?>
                                    </span>
                                </div>
                                <div>
                                    <?php if ($challenge['status'] == 'completed'): ?>
                                        <span>Победитель: <?php echo $challenge['winner_id'] == $challenge['challenger_id'] ? $challenge['challenger_name'] : $challenge['opponent_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="challenge-body">
                                <div class="fighters">
                                    <div class="fighter">
                                        <img src="/assets/images/avatars/<?php echo $challenge['challenger_avatar']; ?>" alt="<?php echo $challenge['challenger_name']; ?>" class="fighter-avatar">
                                        <div class="fighter-name"><?php echo $challenge['challenger_name']; ?></div>
                                        <?php if ($challenge['status'] == 'completed'): ?>
                                            <div class="fighter-score"><?php echo $challenge['challenger_id'] == $challenge['winner_id'] ? $challenge['winner_score'] : $challenge['loser_score']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="vs">VS</div>
                                    
                                    <div class="fighter">
                                        <img src="/assets/images/avatars/<?php echo $challenge['opponent_avatar']; ?>" alt="<?php echo $challenge['opponent_name']; ?>" class="fighter-avatar">
                                        <div class="fighter-name"><?php echo $challenge['opponent_name']; ?></div>
                                        <?php if ($challenge['status'] == 'completed'): ?>
                                            <div class="fighter-score"><?php echo $challenge['opponent_id'] == $challenge['winner_id'] ? $challenge['winner_score'] : $challenge['loser_score']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="challenge-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Призовой фонд:</span>
                                        <span class="stake-amount"><?php echo number_format($challenge['stake_amount']); ?> CC</span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Окончен:</span>
                                        <span><?php echo time_elapsed_string($challenge['completed_at']); ?></span>
                                    </div>
                                    <?php if ($challenge['status'] == 'completed'): ?>
                                        <div class="detail-row">
                                            <span class="detail-label">Победитель заработал:</span>
                                            <span><?php echo number_format($challenge['stake_amount'] * 2); ?> CC</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($challenge['status'] == 'completed'): ?>
                                    <?php if (!empty($challenge['winner_post'])): ?>
                                        <div class="post-preview">
                                            <h4>Пост победителя mr <?php echo $challenge['winner_id'] == $challenge['challenger_id'] ? $challenge['challenger_name'] : $challenge['opponent_name']; ?></h4>
                                            <div class="post-content"><?php echo $challenge['winner_post']['content']; ?></div>
                                            <div class="post-stats">
                                                <span><i class="fas fa-thumbs-up"></i> <?php echo $challenge['winner_post']['likes_count']; ?> Лайки</span>
                                                <span><i class="fas fa-comment"></i> <?php echo $challenge['winner_post']['comments_count']; ?> Комментарии</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="betting-section">
                                        <h4>Результат ставок</h4>
                                        <?php if ($challenge['user_bet']): ?>
                                            <?php if ($challenge['user_bet']['bet_on'] == ($challenge['winner_id'] == $challenge['challenger_id'] ? 'challenger' : 'opponent')): ?>
                                                <p>Ты выиграл <?php echo number_format($challenge['user_bet']['amount'] * 1.8); ?> CC на своей ставке!</p>
                                            <?php else: ?>
                                                <p>Ты потерял <?php echo number_format($challenge['user_bet']['amount']); ?> CC.</p>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p>Ты не ставил на этот поединок.</p>
                                        <?php endif; ?>
                                        
                                        <div class="bet-total">
                                            Ставки: <?php echo number_format($challenge['total_bets']['challenger_bets']); ?> CC на mr. <?php echo $challenge['challenger_name']; ?> | 
                                            <?php echo number_format($challenge['total_bets']['opponent_bets']); ?> CC на mr. <?php echo $challenge['opponent_name']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="action-buttons">
                                    <a href="challenge_details.php?id=<?php echo $challenge['id']; ?>" class="btn btn-secondary">Смотреть детали</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div id="create-challenge" class="tab-content">
                <h2 class="section-title">Бросить вызов</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="opponent_id">Противник:</label>
                        <select name="opponent_id" id="opponent_id" class="form-control" required>
                            <option value="">Выбери противника</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?> (@<?php echo htmlspecialchars($user['username']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stake_amount">Призовой фонд (CC):</label>
                        <input type="number" name="stake_amount" id="stake_amount" class="form-control" min="1" max="<?php echo $user_balance; ?>" required>
                        <small>Твой баланс: <?php echo number_format($user_balance); ?> CC</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Длительность поединка:</label>
                        <select name="duration" id="duration" class="form-control" required>
                            <option value="24">24 часа</option>
                            <option value="48">48 часов</option>
                            <option value="72">72 часа</option>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" name="create_challenge" class="btn btn-primary">Бросить вызов</button>
                    </div>
                </form>
                
                <div class="challenge-rules" style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                    <h4>Правила боев:</h4>
                    <ol>
                        <li>Каждый участник должен создать публикацию специально для этого поединка</li>
                        <li>Победитель определяется по общему количеству лайков и комментариев к его публикации</li>
                        <li>Если челлендж завершится, а оба участника не опубликуют публикации, ставка возвращается</li>
                        <li>Если публикацию создаст только один участник, он автоматически выигрывает</li>
                        <li>В случае ничьей ставка возвращается обоим участникам</li>
                        <li>Победитель получает обе суммы ставки (за вычетом 10% комиссии платформы)</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        function selectBetOption(element, betOn) {
            // Remove selected class from all options
            document.querySelectorAll('.bet-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Set the hidden input value
            document.getElementById('bet_on').value = betOn;
        }
    </script>
</body>
</html>