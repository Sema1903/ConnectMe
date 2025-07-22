<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = getCurrentUser($db);
if (!$user) {
    header('Location: /login.php');
    exit;
}

$friends = getFriends($db, $user['id']);
$messages = getRecentMessages($db, $user['id']);

// Проверяем, что выбранный пользователь - друг
if (isset($_GET['user_id'])) {
    $friend_id = (int)$_GET['user_id'];
    $is_friend = false;
    
    foreach ($friends as $friend) {
        if ($friend['id'] == $friend_id) {
            $is_friend = true;
            $current_friend = $friend;
            break;
        }
    }
    
    if (!$is_friend) {
        header('Location: /messages.php');
        exit;
    }
    
    $chat_messages = getMessagesBetweenUsers($db, $user['id'], $friend_id);
}

require_once 'includes/header.php';
?>

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
</style>

<div class="messages-container">
    <!-- Боковая панель контактов -->
    <div class="contacts-sidebar">
        <div class="contacts-header">
            <h2>Сообщения</h2>
            <div class="contacts-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Поиск в сообщениях">
            </div>
        </div>
        
        <div class="contacts-list">
            <?php foreach ($messages as $message): 
                $friend_id = ($message['sender_id'] == $user['id']) ? $message['receiver_id'] : $message['sender_id'];
                $friend = getUserById($db, $friend_id);
                $is_active = isset($_GET['user_id']) && $_GET['user_id'] == $friend_id;
            ?>
                <a href="/messages.php?user_id=<?= $friend_id ?>" class="contact-item <?= $is_active ? 'active' : '' ?>">
                    <div class="contact-avatar">
                        <img src="/assets/images/avatars/<?= $friend['avatar'] ?>" alt="<?= htmlspecialchars($friend['full_name']) ?>">
                        <?php if (isUserOnline($friend_id)): ?>
                            <div class="online-badge"></div>
                        <?php endif; ?>
                    </div>
                    <div class="contact-info">
                        <div class="contact-name"><?= htmlspecialchars($friend['full_name']) ?></div>
                        <div class="contact-preview">
                            <?= ($message['sender_id'] == $user['id'] ? 'Вы: ' : '') . htmlspecialchars(substr($message['content'], 0, 30)) ?>
                        </div>
                    </div>
                    <div class="contact-time">
                        <?= time_elapsed_string($message['created_at']) ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Область чата -->
    <div class="chat-container">
        <?php if (isset($_GET['user_id'])): ?>
            <!-- Заголовок чата -->
            <div class="chat-header">
                <a href="/messages.php" class="mobile-back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="contact-avatar">
                    <img src="/assets/images/avatars/<?= $current_friend['avatar'] ?>" alt="<?= htmlspecialchars($current_friend['full_name']) ?>">
                    <?php if (isUserOnline($current_friend['id'])): ?>
                        <div class="online-badge"></div>
                    <?php endif; ?>
                </div>
                <div style="margin-left: 10px;">
                    <div style="font-weight: 600;"><?= htmlspecialchars($current_friend['full_name']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                    </div>
                </div>
            </div>
            
            <!-- Сообщения -->
            <div class="chat-messages" id="chat-messages">
                <?php foreach ($chat_messages as $message): ?>
                    <div class="message <?= $message['sender_id'] == $user['id'] ? 'message-outgoing' : 'message-incoming' ?>">
                        <div class="message-bubble">
                            <?= htmlspecialchars($message['content']) ?>
                            <div class="message-time">
                                <?= date('H:i', strtotime($message['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Форма отправки -->
            <div class="chat-input">
                <form class="chat-form" id="send-message-form">
                    <input type="text" name="message" placeholder="Написать сообщение..." autocomplete="off">
                    <button type="submit">
                        <i class="fas fa-paper-plane send-icon"></i>
                    </button>
                </form>
            </div>
            
            <script>
            // Отправка сообщения
            document.getElementById('send-message-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const input = this.querySelector('input[name="message"]');
                const message = input.value.trim();
                
                if (message) {
                    fetch('/actions/send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            receiver_id: <?= $current_friend['id'] ?>,
                            message: message
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Добавляем сообщение в чат
                            const messagesContainer = document.getElementById('chat-messages');
                            const messageElement = document.createElement('div');
                            messageElement.className = 'message message-outgoing';
                            messageElement.innerHTML = `
                                <div class="message-bubble">
                                    ${message}
                                    <div class="message-time">Только что</div>
                                </div>
                            `;
                            messagesContainer.appendChild(messageElement);
                            input.value = '';
                            scrollToBottom();
                        }
                    });
                }
            });
            
            // Прокрутка вниз
            function scrollToBottom() {
                const messagesContainer = document.getElementById('chat-messages');
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // При загрузке страницы
            scrollToBottom();
            </script>
        <?php else: ?>
            <!-- Пустой чат -->
            <div class="empty-chat">
                <i class="fas fa-comments"></i>
                <h2>Выберите чат</h2>
                <p>Начните общение с друзьями</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>