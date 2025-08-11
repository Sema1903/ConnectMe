<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$current_user = getCurrentUser($db);
$search_query = trim($_GET['q'] ?? '');

// Поиск пользователей и групп
$users = [];
$groups = [];
$show_results = false;

if (!empty($search_query)) {
    $show_results = true;
    
    // Поиск пользователей (по имени и ID)
    $stmt = $db->prepare("SELECT id, full_name, avatar FROM users 
                         WHERE full_name LIKE :query OR username LIKE :username
                         LIMIT 10");
    $stmt->bindValue(':query', "%$search_query%", SQLITE3_TEXT);
    $stmt->bindValue(':username', "%$search_query%", SQLITE3_TEXT);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }

    // Поиск групп (по названию и ID)
    $stmt = $db->prepare("SELECT id, name, avatar FROM groups 
                         WHERE name LIKE :query OR id = :id
                         LIMIT 10");
    $stmt->bindValue(':query', "%$search_query%", SQLITE3_TEXT);
    $stmt->bindValue(':id', is_numeric($search_query) ? (int)$search_query : 0, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $groups[] = $row;
    }
}

require_once 'includes/header.php';
?>

<div class="container">
    <h2>Результаты поиска: "<?= htmlspecialchars($search_query) ?>"</h2>
    
    <?php if ($show_results): ?>
        <!-- Результаты поиска пользователей -->
        <?php if (!empty($users)): ?>
            <div class="search-section">
                <h3>Люди</h3>
                <div class="users-list">
                    <?php foreach ($users as $user): ?>
                        <a href="/profile.php?id=<?= $user['id'] ?>" class="search-card">
                            <img src="/assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($user['full_name']) ?>"
                                 onerror="this.src='/assets/images/avatars/default.jpg'">
                            <div class="search-info">
                                <span class="search-name"><?= htmlspecialchars($user['full_name']) ?></span>
                                <span class="search-id">ID: <?= $user['id'] ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Результаты поиска групп -->
        <?php if (!empty($groups)): ?>
            <div class="search-section">
                <h3>Группы</h3>
                <div class="groups-list">
                    <?php foreach ($groups as $group): ?>
                        <a href="/group.php?id=<?= $group['id'] ?>" class="search-card">
                            <img src="/assets/images/groups/<?= htmlspecialchars($group['avatar']) ?>" 
                                 alt="<?= htmlspecialchars($group['name']) ?>"
                                 onerror="this.src='/assets/images/groups/default.jpg'">
                            <div class="search-info">
                                <span class="search-name"><?= htmlspecialchars($group['name']) ?></span>
                                <span class="search-id">ID: <?= $group['id'] ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($users) && empty($groups)): ?>
            <p>Ничего не найдено</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Введите поисковый запрос в поле выше</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>