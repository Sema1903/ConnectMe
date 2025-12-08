<?php
require_once 'config.php';
function validateUserId($id) {
    return is_numeric($id) && $id > 0;
}


function getUserById($db, $id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
                             
    if ($user) {
                                 // Если аватар не установлен или файл отсутствует - используем стандартный
        if (empty($user['avatar']) || !file_exists(__DIR__ . '/../assets/images/avatars/' . $user['avatar'])) {
                $user['avatar'] = 'unknown.png';
        }
    }
                             
    return $user;
}

function getFriends($db, $user_id) {
    $stmt = $db->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar 
        FROM friends f
        JOIN users u ON (f.user1_id = u.id OR f.user2_id = u.id) AND u.id != ?
        WHERE (f.user1_id = ? OR f.user2_id = ?) AND f.status = 1
    ");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $friends = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $friends[] = $row;
    }
    
    return $friends;
}

function getGroups($db, $user_id = null) {
    if ($user_id) {
        $stmt = $db->prepare("
            SELECT g.*, u.username as creator_name
            FROM groups g
            JOIN users u ON g.creator_id = u.id
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ?
        ");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    } else {
        $stmt = $db->prepare("
            SELECT g.*, u.username as creator_name
            FROM groups g
            JOIN users u ON g.creator_id = u.id
            ORDER BY g.created_at DESC
            LIMIT 10
        ");
    }
    
    $result = $stmt->execute();
    
    $groups = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $groups[] = $row;
    }
    
    return $groups;
}

function getOnlineUsers($db) {
    // В реальном приложении здесь была бы проверка времени последней активности
    // Для демонстрации просто вернем несколько случайных пользователей
    $stmt = $db->prepare("
        SELECT id, username, full_name, avatar 
        FROM users 
        ORDER BY RANDOM() 
        LIMIT 5
    ");
    $result = $stmt->execute();
    
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    
    return $users;
}

function addPost($db, $user_id, $content, $image = null, $feeling = null) {
    $stmt = $db->prepare("INSERT INTO posts (user_id, content, image, feeling, created_at) VALUES (:user_id, :content, :image, :feeling, datetime('now'))");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':content', $content, SQLITE3_TEXT);
    $stmt->bindValue(':image', $image, SQLITE3_TEXT);
    $stmt->bindValue(':feeling', $feeling, SQLITE3_TEXT);
    
    $result = $stmt->execute();
    
    if ($result) {
        $post_id = $db->lastInsertRowID();
        //rewardForAction($db, $user_id, 'post');
        // Обрабатываем упоминания
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $mentioned_usernames = array_unique($matches[1]);
        
        foreach ($mentioned_usernames as $username) {
            $mentioned_user = getUserByUsername($db, $username);
            if ($mentioned_user && $mentioned_user['id'] != $user_id) {
                addNotification(
                    $db,
                    $mentioned_user['id'], // Кому уведомление
                    'mention',             // Тип уведомления
                    $user_id,              // Кто упомянул
                    $post_id               // ID поста
                );
            }
        }
        
        return $post_id;
    }
    
    return false;
}

function likePost($db, $user_id, $post_id) {
    // Проверяем, не поставил ли уже пользователь лайк
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($row['count'] > 0) {
        // Удаляем лайк
        $stmt = $db->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $post_id, SQLITE3_INTEGER);
        $stmt->execute();
        return ['success' => true, 'action' => 'unlike'];
    } else {
        // Добавляем лайк
        $stmt = $db->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $post_id, SQLITE3_INTEGER);
        $stmt->execute();
        $dop = $db -> prepare('SELECT * FROM posts WHERE id = :id');
        $dop -> bindValue(':id', $post_id, SQLITE3_INTEGER);
        $records = $dop -> execute() -> fetchArray(SQLITE3_ASSOC);
        $post_author = $records['user_id'];
        //rewardForAction($db, $post_author, 'like_received');
        return ['success' => true, 'action' => 'like'];
    }
}

function addComment($db, $post_id, $user_id, $content) {
    $dop = $db -> prepare('SELECT * FROM posts WHERE id = :id');
    $dop -> bindValue(':id', $post_id, SQLITE3_INTEGER);
    $records = $dop -> execute() -> fetchArray(SQLITE3_ASSOC);
    $post_author = $records['user_id'];
    //rewardForAction($db, $post_author, 'comment');
    $stmt = $db->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $content, SQLITE3_TEXT);
    return $stmt->execute();
}

function getComments($db, $post_id) {
    $stmt = $db->prepare("
        SELECT c.*, u.username, u.full_name, u.avatar
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $comments = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $comments[] = $row;
    }
    
    return $comments;
}

function getLiveStreams($db) {
    $stmt = $db->prepare("
        SELECT l.*, u.username, u.full_name, u.avatar
        FROM live_streams l
        JOIN users u ON l.user_id = u.id
        WHERE l.is_live = 1
        ORDER BY l.started_at DESC
    ");
    $result = $stmt->execute();
    
    $streams = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $streams[] = $row;
    }
    
    return $streams;
}
                         // Добавляем в конец файла functions.php
                         function getPostsByUser($db, $user_id, $current_user_id = null) {
                            $query = "
                                SELECT p.*, u.username, u.full_name, u.avatar,
                                       (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";
                            
                            // Добавляем информацию о том, лайкнул ли текущий пользователь пост
                            if ($current_user_id) {
                                $query .= ", EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = $current_user_id) as is_liked";
                            } else {
                                $query .= ", 0 as is_liked";
                            }
                            
                            $query .= " FROM posts p
                                       JOIN users u ON p.user_id = u.id
                                       WHERE p.user_id = ?
                                       ORDER BY p.created_at DESC";
                            
                            $stmt = $db->prepare($query);
                            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            
                            $posts = [];
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                // Обрабатываем упоминания в тексте
                                $row['content'] = processMentions(nl2br(htmlspecialchars($row['content'])), $db);
                                
                                // Загружаем опрос, если он есть
                                $poll = getPollByPostId($db, $row['id']);
                                if ($poll) {
                                    $row['poll'] = $poll;
                                    
                                    // Проверяем, голосовал ли текущий пользователь
                                    if ($current_user_id) {
                                        $row['poll']['has_voted'] = hasUserVoted($db, $poll['id'], $current_user_id);
                                    } else {
                                        $row['poll']['has_voted'] = false;
                                    }
                                }
                                
                                $posts[] = $row;
                            }
                            
                            return $posts;
                        }
                         function getRecentMessages($db, $user_id) {
                            $stmt = $db->prepare("
                                SELECT m.*, 
                                       CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id
                                FROM messages m
                                WHERE m.sender_id = ? OR m.receiver_id = ?
                                ORDER BY m.created_at DESC
                            ");
                            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(3, $user_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            
                            $messages = [];
                            $processed_users = [];
                            
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                if (!in_array($row['other_user_id'], $processed_users)) {
                                    $messages[] = $row;
                                    $processed_users[] = $row['other_user_id'];
                                }
                            }
                            
                            return $messages;
                        }

                         function getMessagesBetweenUsers($db, $user1_id, $user2_id) {
                             $stmt = $db->prepare("
                                 SELECT m.*, u.username, u.full_name, u.avatar
                                 FROM messages m
                                 JOIN users u ON m.sender_id = u.id
                                 WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                                 ORDER BY m.created_at ASC
                             ");
                             $stmt->bindValue(1, $user1_id, SQLITE3_INTEGER);
                             $stmt->bindValue(2, $user2_id, SQLITE3_INTEGER);
                             $stmt->bindValue(3, $user2_id, SQLITE3_INTEGER);
                             $stmt->bindValue(4, $user1_id, SQLITE3_INTEGER);
                             $result = $stmt->execute();
                             
                             $messages = [];
                             while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                 $messages[] = $row;
                             }
                             
                             return $messages;
                         }

                         function isUserOnline($user_id) {
                             // В реальном приложении здесь была бы проверка времени последней активности
                             // Для демонстрации возвращаем случайное значение
                             return rand(0, 1) == 1;
                         }
                         function time_elapsed_string($datetime, $full = false) {
                            $now = new DateTime;
                            $ago = new DateTime($datetime);
                            $diff = $now->diff($ago);
                        
                            // Вычисляем недели отдельно, не создавая динамическое свойство
                            $weeks = floor($diff->d / 7);
                            $diff->d -= $weeks * 7;
                        
                            $string = array(
                                'y' => 'год',
                                'm' => 'месяц',
                                'w' => 'неделя',
                                'd' => 'день', 
                                'h' => 'час',
                                'i' => 'минута',
                                's' => 'секунда',
                            );
                            
                            foreach ($string as $k => &$v) {
                                if ($k === 'w') {
                                    $value = $weeks;
                                } else {
                                    $value = $diff->$k ?? 0;
                                }
                        
                                if ($value) {
                                    $v = $value . ' ' . $v . get_plural_suffix($k, $value);
                                } else {
                                    unset($string[$k]);
                                }
                            }
                        
                            if (!$full) $string = array_slice($string, 0, 1);
                            return $string ? implode(', ', $string) . ' назад' : 'только что';
                        }
                        
                        // Вспомогательная функция для склонений
                        function get_plural_suffix($key, $value) {
                            if ($value > 1) {
                                switch ($key) {
                                    case 'm': return 'ев';
                                    case 'y':
                                    case 'd': return 'ов';
                                    case 'h': return 'ов';
                                    default: return '';
                                }
                            }
                            return '';
                        }
                         function getFriendshipStatus($db, $current_user_id, $profile_user_id) {
                            $stmt = $db->prepare("SELECT * FROM friends WHERE 
                                (user1_id = ? AND user2_id = ?) OR 
                                (user1_id = ? AND user2_id = ?)");
                            $stmt->bindValue(1, $current_user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(2, $profile_user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(3, $profile_user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(4, $current_user_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            
                            if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                if ($row['status'] == 1) {
                                    return 'friends';
                                } elseif ($row['user1_id'] == $current_user_id) {
                                    return 'request_sent';
                                } else {
                                    return 'request_received';
                                }
                            }
                            return 'not_friends';
                        }
                        
                        function sendFriendRequest($db, $from_user_id, $to_user_id) {
                            $stmt = $db->prepare("INSERT INTO friends (user1_id, user2_id, status, created_at) 
                                                 VALUES (?, ?, 0, datetime('now'))");
                            $stmt->bindValue(1, $from_user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(2, $to_user_id, SQLITE3_INTEGER);
                            return $stmt->execute();
                        }
                        function getGroupMembers($db, $group_id) {
                            $stmt = $db->prepare("SELECT u.id, u.full_name, u.avatar 
                                                 FROM group_members gm
                                                 JOIN users u ON gm.user_id = u.id
                                                 WHERE gm.group_id = ?");
                            $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            
                            $members = [];
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $members[] = $row;
                            }
                            return $members;
                        }
                        
                        function getGroupPosts($db, $group_id) {
                            $stmt = $db->prepare("SELECT p.*, u.full_name, u.avatar 
                                                 FROM group_posts p
                                                 JOIN users u ON p.user_id = u.id
                                                 WHERE p.group_id = ?
                                                 ORDER BY p.created_at DESC");
                            $stmt->bindValue(1, $group_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            
                            $posts = [];
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $posts[] = $row;
                            }
                            return $posts;
                        }
                        
                        function getUserNameById($db, $user_id) {
                            $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                            $result = $stmt->execute();
                            return $result->fetchArray(SQLITE3_ASSOC)['full_name'] ?? 'Неизвестный';
                        }
                        function hasUnreadNotifications($db, $user_id) {
                            $stmt = $db->prepare("SELECT 1 FROM notifications WHERE user_id = ? AND is_read = 0 LIMIT 1");
                            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                            return (bool) $stmt->execute()->fetchArray();
                        }





                        function getUserByUsername($db, $username) {
                            $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                            $stmt->bindValue(1, $username, SQLITE3_TEXT);
                            $result = $stmt->execute();
                            return $result->fetchArray(SQLITE3_ASSOC);
                        }
                        
                        function addNotification($db, $user_id, $type, $from_user_id, $post_id = null) {
                            $stmt = $db->prepare("INSERT INTO notifications 
                                                 (user_id, type, from_user_id, post_id, created_at) 
                                                 VALUES (?, ?, ?, ?, datetime('now'))");
                            $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(2, $type, SQLITE3_TEXT);
                            $stmt->bindValue(3, $from_user_id, SQLITE3_INTEGER);
                            $stmt->bindValue(4, $post_id, SQLITE3_INTEGER);
                            return $stmt->execute();
                        }
                        function processMentions($text, $db) {
                            return preg_replace_callback(
                                '/@([a-zA-Z0-9_]+)/',
                                function($matches) use ($db) {
                                    $username = $matches[1];
                                    $user = getUserByUsername($db, $username);
                                    if ($user) {
                                        //return '<a href="/profile.php?username=' . urlencode($username) . '" class="mention">@' . htmlspecialchars($username) . '</a>';
                                        return '@' . htmlspecialchars($username);

                                    }
                                    return '@' . htmlspecialchars($username);
                                },
                                $text
                            );
                        }
                        function getPosts($db, $limit = 10, $offset = 0, $user_id = null) {
                            $query = "SELECT p.*, u.username, u.full_name, u.avatar,
                                      (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                                      (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count";
                            
                            if ($user_id) {
                                $query .= ", EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = $user_id) as is_liked";
                            } else {
                                $query .= ", 0 as is_liked";
                            }
                            
                            $query .= " FROM posts p
                                       JOIN users u ON p.user_id = u.id
                                       ORDER BY p.created_at DESC
                                       LIMIT $limit OFFSET $offset";
                            
                            $result = $db->query($query);
                            
                            $posts = [];
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $row['content'] = processMentions(nl2br(htmlspecialchars($row['content'])), $db);
                                
                                // Загружаем опрос, если он есть
                                $poll = getPollByPostId($db, $row['id']);
                                if ($poll) {
                                    $row['poll'] = $poll;
                                }
                                
                                $posts[] = $row;
                            }
                            
                            return $posts;
                        }
// Добавляем в functions.php

// Получить баланс пользователя
function getUserBalance($db, $user_id) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as balance FROM game_currency_history WHERE user_id = ?");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC)['balance'] ?? 0;
}

// Добавить валюту пользователю
function addCurrency($db, $user_id, $amount, $reason) {
    $stmt = $db->prepare("INSERT INTO game_currency_history (user_id, amount, reason) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $amount, SQLITE3_INTEGER);
    $stmt->bindValue(3, $reason, SQLITE3_TEXT);
    return $stmt->execute();
}

// Перевести валюту другому пользователю
function transferCurrency($db, $from_user_id, $to_user_id, $amount) {
    // Принудительно завершаем все возможные транзакции
$db->exec('ROLLBACK');
    if ($amount <= 0) return false;
    
    $balance = getUserBalance($db, $from_user_id);
    if ($balance < $amount) return false;
    
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Снимаем у отправителя
        addCurrency($db, $from_user_id, -$amount, "Перевод пользователю ID $to_user_id");
        
        // Добавляем получателю
        addCurrency($db, $to_user_id, $amount, "Перевод от пользователя ID $from_user_id");
        
        $db->exec('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

// Начислить валюту за действия
function rewardForAction($db, $user_id, $action_type) {
    $rewards = [
        'post' => 5,       // 5 монет за пост
        'like_received' => 1, // 1 монета за полученный лайк
        'comment' => 2     // 2 монеты за комментарий
    ];
    
    if (isset($rewards[$action_type])) {
        return addCurrency($db, $user_id, $rewards[$action_type], "Награда за $action_type");
    }
    return false;
}
function createPoll($db, $post_id, $question, $options, $is_multiple = false, $ends_at = null) {
    // Создаем опрос
    $stmt = $db->prepare("INSERT INTO polls (post_id, question, is_multiple, ends_at) VALUES (?, ?, ?, ?)");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $question, SQLITE3_TEXT);
    $stmt->bindValue(3, $is_multiple ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(4, $ends_at, SQLITE3_TEXT);
    $stmt->execute();
    
    $poll_id = $db->lastInsertRowID();
    
    // Добавляем варианты ответов
    foreach ($options as $option_text) {
        $option_text = trim($option_text);
        if (!empty($option_text)) {
            $stmt = $db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            $stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $option_text, SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    
    return $poll_id;
}

function getPollByPostId($db, $post_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id) as total_votes
        FROM polls p
        WHERE p.post_id = ?
    ");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $poll = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($poll) {
        // Получаем варианты ответов
        $stmt = $db->prepare("
            SELECT o.*, 
                   (SELECT COUNT(*) FROM poll_votes pv WHERE pv.option_id = o.id) as votes
            FROM poll_options o
            WHERE o.poll_id = ?
            ORDER BY o.id
        ");
        $stmt->bindValue(1, $poll['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $options = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $options[] = $row;
        }
        
        $poll['options'] = $options;
        return $poll;
    }
    
    return null;
}

function voteInPoll($db, $poll_id, $option_ids, $user_id) {
    if (empty($option_ids)) return false;
    
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Удаляем предыдущие голоса пользователя
        $stmt = $db->prepare("DELETE FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
        $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
        $stmt->execute();
        
        // Добавляем новые голоса
        $stmt = $db->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
        
        foreach ($option_ids as $option_id) {
            $stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $option_id, SQLITE3_INTEGER);
            $stmt->bindValue(3, $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        $db->exec('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        error_log("Poll vote error: " . $e->getMessage());
        return false;
    }
}

function hasUserVoted($db, $poll_id, $user_id) {
    $stmt = $db->prepare("SELECT 1 FROM poll_votes WHERE poll_id = ? AND user_id = ? LIMIT 1");
    $stmt->bindValue(1, $poll_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    return (bool) $stmt->execute()->fetchArray();
}
// Добавьте эти функции в includes/functions.php
function getPostReactions($db, $post_id) {
    $stmt = $db->prepare("
        SELECT r.*, u.full_name, u.avatar 
        FROM reactions r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.post_id = ?
    ");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $reactions = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $reactions[] = $row;
    }
    return $reactions;
}


function getReactionCounts($db, $post_id) {
    $stmt = $db->prepare("
        SELECT emoji, COUNT(*) as count 
        FROM reactions 
        WHERE post_id = ? 
        GROUP BY emoji
    ");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $counts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $counts[$row['emoji']] = $row['count'];
    }
    return $counts;
}
function getUserReactions($db, $post_id, $user_id) {
    $stmt = $db->prepare("SELECT * FROM reactions WHERE post_id = ? AND user_id = ?");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $reactions = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $reactions[] = $row;
    }
    return $reactions;
}

function getUserReactionCount($db, $post_id, $user_id) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reactions WHERE post_id = ? AND user_id = ?");
    $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['count'] ?? 0;
}















// В конец файла functions.php добавляем:

// Функции для API ботов
function authenticateBot($db, $bot_token) {
    $stmt = $db->prepare("SELECT * FROM bots WHERE token = ? AND is_active = 1");
    $stmt->bindValue(1, $bot_token, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function createBotPost($db, $bot_id, $content, $user_id = null, $image = null) {
    $stmt = $db->prepare("INSERT INTO posts (user_id, content, image, created_at, is_bot_post, bot_id) 
                         VALUES (?, ?, ?, datetime('now'), 1, ?)");
    $stmt->bindValue(1, $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $content, SQLITE3_TEXT);
    $stmt->bindValue(3, $image, SQLITE3_TEXT);
    $stmt->bindValue(4, $bot_id, SQLITE3_INTEGER);
    return $stmt->execute();
}

function sendBotMessage($db, $bot_id, $receiver_id, $content) {
    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at, is_bot_message) 
                         VALUES (?, ?, ?, datetime('now'), 1)");
    $stmt->bindValue(1, $bot_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $receiver_id, SQLITE3_INTEGER);
    $stmt->bindValue(3, $content, SQLITE3_TEXT);
    return $stmt->execute();
}

function getBotStats($db, $bot_id) {
    $stats = [];
    
    // Количество постов
    $stmt = $db->prepare("SELECT COUNT(*) as post_count FROM posts WHERE bot_id = ?");
    $stmt->bindValue(1, $bot_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $stats['post_count'] = $result->fetchArray(SQLITE3_ASSOC)['post_count'];
    
    // Количество сообщений
    $stmt = $db->prepare("SELECT COUNT(*) as message_count FROM messages WHERE sender_id = ? AND is_bot_message = 1");
    $stmt->bindValue(1, $bot_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $stats['message_count'] = $result->fetchArray(SQLITE3_ASSOC)['message_count'];
    
    // Последняя активность
    $stmt = $db->prepare("SELECT MAX(created_at) as last_activity FROM (
                          SELECT created_at FROM posts WHERE bot_id = ?
                          UNION ALL
                          SELECT created_at FROM messages WHERE sender_id = ? AND is_bot_message = 1
                      )");
    $stmt->bindValue(1, $bot_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $bot_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $stats['last_activity'] = $result->fetchArray(SQLITE3_ASSOC)['last_activity'];
    
    return $stats;
}

function getBotByUsername($db, $bot_username) {
    $stmt = $db->prepare("SELECT * FROM bots WHERE username = ?");
    $stmt->bindValue(1, $bot_username, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function registerBotWebhook($db, $bot_id, $webhook_url, $events) {
    $stmt = $db->prepare("INSERT OR REPLACE INTO bot_webhooks (bot_id, webhook_url, events, created_at) 
                         VALUES (?, ?, ?, datetime('now'))");
    $stmt->bindValue(1, $bot_id, SQLITE3_INTEGER);
    $stmt->bindValue(2, $webhook_url, SQLITE3_TEXT);
    $stmt->bindValue(3, json_encode($events), SQLITE3_TEXT);
    return $stmt->execute();
}