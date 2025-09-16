<?php
// Настройки базы данных
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
define('DB_PATH', __DIR__ . '/../db/connectme.db');

// Подключение к SQLite3
try {
    $db = new SQLite3(DB_PATH);
    $db->enableExceptions(true);
    
    // Создание таблиц, если они не существуют
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            avatar TEXT DEFAULT 'unknown.png',
            bio TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS leaderboard (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            game_type TEXT NOT NULL,
            score INTEGER NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            image TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        
        CREATE TABLE IF NOT EXISTS likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(post_id, user_id)
        );
        
        CREATE TABLE IF NOT EXISTS friends (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user1_id INTEGER NOT NULL,
            user2_id INTEGER NOT NULL,
            status INTEGER DEFAULT 0, -- 0: pending, 1: accepted
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user1_id) REFERENCES users(id),
            FOREIGN KEY (user2_id) REFERENCES users(id),
            UNIQUE(user1_id, user2_id)
        );
        
        CREATE TABLE IF NOT EXISTS groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            posts_count INTEGER DEFAULT 0,
            description TEXT,
            creator_id INTEGER NOT NULL,
            avatar TEXT DEFAULT 'group_default.jpg',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (creator_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS group_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES groups(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE(group_id, user_id)
        );
        
        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS live_streams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            stream_key TEXT NOT NULL,
            is_live INTEGER DEFAULT 0,
            started_at DATETIME,
            ended_at DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id));

            CREATE TABLE IF NOT EXISTS group_posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                image TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (group_id) REFERENCES groups(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                type TEXT NOT NULL,  -- 'mention', 'like', 'comment', etc
                from_user_id INTEGER,
                post_id INTEGER,
                is_read BOOLEAN DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (from_user_id) REFERENCES users(id),
                FOREIGN KEY (post_id) REFERENCES posts(id)
            );
            CREATE TABLE IF NOT EXISTS music (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                file_name TEXT NOT NULL,
                plays INTEGER DEFAULT 0,
                uploaded_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            CREATE TABLE IF NOT EXISTS group_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                action_type TEXT NOT NULL,
                action_data TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (group_id) REFERENCES groups(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            CREATE TABLE IF NOT EXISTS chat_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                stream_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                FOREIGN KEY (stream_id) REFERENCES live_streams(id),
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            CREATE TABLE IF NOT EXISTS game_scores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                game_id TEXT NOT NULL,
                score INTEGER NOT NULL,
                played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS user_achievements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                game_id TEXT NOT NULL,
                achievement_key TEXT NOT NULL,
                title TEXT NOT NULL,
                reward INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            
            CREATE TABLE IF NOT EXISTS game_currency_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                amount INTEGER NOT NULL,
                reason TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            );
            CREATE TABLE IF NOT EXISTS game_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                icon TEXT NOT NULL,
                price INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                type TEXT NOT NULL DEFAULT 'regular', -- 'regular', 'premium', 'avatar_frame', 'profile_cover'
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS user_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                purchase_date TIMESTAMP NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (item_id) REFERENCES game_items(id)
            );
            
            CREATE TABLE IF NOT EXISTS gifts_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                gift_date TIMESTAMP NOT NULL,
                FOREIGN KEY (sender_id) REFERENCES users(id),
                FOREIGN KEY (receiver_id) REFERENCES users(id),
                FOREIGN KEY (item_id) REFERENCES game_items(id)
            );
            CREATE TABLE IF NOT EXISTS polls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                question TEXT NOT NULL,
                is_multiple INTEGER DEFAULT 0,
                ends_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts(id)
            );
            
            CREATE TABLE IF NOT EXISTS poll_options (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poll_id INTEGER NOT NULL,
                option_text TEXT NOT NULL,
                votes INTEGER DEFAULT 0,
                FOREIGN KEY (poll_id) REFERENCES polls(id)
            );
            
            CREATE TABLE IF NOT EXISTS poll_votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poll_id INTEGER NOT NULL,
                option_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (poll_id) REFERENCES polls(id),
                FOREIGN KEY (option_id) REFERENCES poll_options(id),
                FOREIGN KEY (user_id) REFERENCES users(id),
                UNIQUE(poll_id, user_id) ON CONFLICT REPLACE
            );
CREATE TABLE IF NOT EXISTS challenges (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    challenger_id INTEGER NOT NULL,
    opponent_id INTEGER NOT NULL,
    stake_amount INTEGER NOT NULL,
    winner_id INTEGER,
    status TEXT NOT NULL DEFAULT 'pending', -- 'pending', 'active', 'completed', 'cancelled'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME,
    expires_at DATETIME,
    completed_at DATETIME,
    FOREIGN KEY (challenger_id) REFERENCES users(id),
    FOREIGN KEY (opponent_id) REFERENCES users(id),
    FOREIGN KEY (winner_id) REFERENCES users(id)
);

-- Challenge bets table
CREATE TABLE IF NOT EXISTS challenge_bets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    challenge_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    bet_on TEXT NOT NULL, -- 'challenger' or 'opponent'
    amount INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS reactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    emoji TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    -- Убираем UNIQUE constraint, чтобы разрешить multiple reactions
);

-- Добавляем индекс для быстрого подсчета
CREATE INDEX IF NOT EXISTS idx_reactions_user_post ON reactions (user_id, post_id);

-- Add challenge_id to posts table
--ALTER TABLE posts ADD COLUMN challenge_id INTEGER REFERENCES challenges(id);


--ALTER TABLE posts ADD COLUMN challenge_id INTEGER REFERENCES challenges(id);
--ALTER TABLE posts ADD COLUMN feeling TEXT;
--ALTER TABLE posts ADD COLUMN updated_at DATETIME;


--            INSERT INTO game_items (name, description, icon, price, quantity, type) VALUES
--('Бронзовое оформление', 'Оформление страницы 3-го уровня', 'square', 10, 10, '3rd lavel'),
--('Серебряное фофрмление', 'Офоормление страницы 2-го уровня', 'image', 20, 5, '2nd lavel'),
--('Золотой оформление', 'Офоормление страницы 1-го уровня', 'star', 50, 3, '1st lavel'),
--('VIP-оформление', 'Эксклюзивное оформление страницы', 'crown', 100, 1, 'premium');
    ");
} catch (Exception $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Начальные данные (для демонстрации)
function initializeDemoData($db) {
    // Проверяем, есть ли уже пользователи
    $result = $db->querySingle("SELECT COUNT(*) as count FROM users");
    
    if ($result == 0) {
        // Добавляем демо-пользователей
        $users = [
            ['ivan', password_hash('ivan123', PASSWORD_DEFAULT), 'Иван Петров', 'ivan@example.com', 'men/32.jpg', 'Веб-разработчик'],
            ['anna', password_hash('anna123', PASSWORD_DEFAULT), 'Анна Смирнова', 'anna@example.com', 'women/44.jpg', 'Дизайнер'],
            ['dmitry', password_hash('dmitry123', PASSWORD_DEFAULT), 'Дмитрий Иванов', 'dmitry@example.com', 'men/22.jpg', 'Блогер о технологиях'],
            ['elena', password_hash('elena123', PASSWORD_DEFAULT), 'Елена Ковалева', 'elena@example.com', 'women/12.jpg', 'Маркетолог'],
            ['alexey', password_hash('alexey123', PASSWORD_DEFAULT), 'Алексей Соколов', 'alexey@example.com', 'men/45.jpg', 'Программист'],
        ];
        
        foreach ($users as $user) {
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, email, avatar, bio) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $user[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $user[1], SQLITE3_TEXT);
            $stmt->bindValue(3, $user[2], SQLITE3_TEXT);
            $stmt->bindValue(4, $user[3], SQLITE3_TEXT);
            $stmt->bindValue(5, $user[4], SQLITE3_TEXT);
            $stmt->bindValue(6, $user[5], SQLITE3_TEXT);
            $stmt->execute();
        }
        
        // Добавляем демо-посты
        $posts = [
            [2, 'Только что вернулась из потрясающего путешествия по Италии! Вот несколько фотографий из Венеции. Каналы, гондолы, архитектура - все просто восхитительно! Кто-нибудь еще был там? Делитесь впечатлениями!', 'venice.jpg'],
            [3, 'Только что опубликовал новую статью на Medium о современных тенденциях в веб-разработке. Рассказываю о React, GraphQL и Serverless архитектуре. Буду рад услышать ваше мнение в комментариях!', NULL],
            [1, 'Сегодня закончил большой проект! Очень доволен результатом. Спасибо всей команде за отличную работу!', 'team.jpg']
        ];
        
        foreach ($posts as $post) {
            $stmt = $db->prepare("INSERT INTO posts (user_id, content, image) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $post[0], SQLITE3_INTEGER);
            $stmt->bindValue(2, $post[1], SQLITE3_TEXT);
            $stmt->bindValue(3, $post[2], SQLITE3_TEXT);
            $stmt->execute();
        }
        
        // Добавляем демо-группы
        $groups = [
            ['Программисты', 'Группа для обсуждения программирования и технологий', 1, 'lego/1.jpg'],
            ['Путешественники', 'Делимся впечатлениями о путешествиях', 2, 'lego/2.jpg'],
            ['Фотографы', 'Все о фотографии', 3, 'lego/3.jpg'],
        ];
        
        foreach ($groups as $group) {
            $stmt = $db->prepare("INSERT INTO groups (name, description, creator_id, avatar) VALUES (?, ?, ?, ?)");
            $stmt->bindValue(1, $group[0], SQLITE3_TEXT);
            $stmt->bindValue(2, $group[1], SQLITE3_TEXT);
            $stmt->bindValue(3, $group[2], SQLITE3_INTEGER);
            $stmt->bindValue(4, $group[3], SQLITE3_TEXT);
            $stmt->execute();
            
            // Добавляем создателя в группу
            $group_id = $db->lastInsertRowID();
            $stmt = $db->prepare("INSERT INTO group_members (group_id, user_id) VALUES (?, ?)");
            $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $group[2], SQLITE3_INTEGER);
            $stmt->execute();
        }
    }
    // Добавьте этот код в функцию initializeDemoData($db) после создания других таблиц
}

initializeDemoData($db);

// Функция для получения текущего пользователя
function getCurrentUser($db) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bindValue(1, $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    return $result->fetchArray(SQLITE3_ASSOC);
}
// Проверка и создание тестовых данных (удалите после проверки)