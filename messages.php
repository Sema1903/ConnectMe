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

require_once 'includes/header.php';
?>

<main class="main-content" style="width: 100%;">
    <div style="display: flex; height: calc(100vh - 150px);">
        <!-- Left Sidebar - Контакты -->
        <div style="width: 30%; border-right: 1px solid #eee; overflow-y: auto;">
            <div style="padding: 15px; border-bottom: 1px solid #eee;">
                <h2 style="font-size: 1.5rem;">Сообщения</h2>
                <div style="margin-top: 10px; position: relative;">
                    <input type="text" placeholder="Поиск в сообщениях" style="width: 100%; padding: 8px 15px; padding-left: 35px; border-radius: 20px; border: 1px solid #ddd; outline: none;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 10px; color: var(--gray-color);"></i>
                </div>
            </div>
            
            <div>
                <?php foreach ($messages as $message): 
                    $friend_id = ($message['sender_id'] == $user['id']) ? $message['receiver_id'] : $message['sender_id'];
                    $friend = getUserById($db, $friend_id);
                    $last_message = $message;
                ?>
                    <a href="/messages.php?user_id=<?= $friend_id ?>" style="text-decoration: none; color: inherit;">
                        <div class="contact-item" style="display: flex; padding: 10px; border-bottom: 1px solid #eee; <?= (isset($_GET['user_id']) && $_GET['user_id'] == $friend_id) ? 'background-color: #f0f2f5;' : '' ?>">
                            <div class="avatar-container">
                                <img src="assets/images/avatars/<?= $friend['avatar'] ?>" alt="User">
                                <?php if (isUserOnline($friend_id)): ?>
                                    <div class="online-badge"></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex-grow: 1; margin-left: 10px;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($friend['full_name']) ?></div>
                                <div style="font-size: 0.9rem; color: var(--gray-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= ($last_message['sender_id'] == $user['id'] ? 'Вы: ' : '') . htmlspecialchars(substr($last_message['content'], 0, 30)) ?>
                                </div>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--gray-color);">
                                <?= time_elapsed_string($last_message['created_at']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Right Side - Чат -->
        <div style="width: 70%; display: flex; flex-direction: column;">
            <?php if (isset($_GET['user_id'])): 
                $friend_id = $_GET['user_id'];
                $friend = getUserById($db, $friend_id);
                $chat_messages = getMessagesBetweenUsers($db, $user['id'], $friend_id);
            ?>
                <!-- Заголовок чата -->
                <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center;">
                    <div class="avatar-container">
                        <img src="assets/images/avatars/<?= $friend['avatar'] ?>" alt="User" id = 'message_avatar' align = 'right'>
                        <?php if (isUserOnline($friend_id)): ?>
                            <div class="online-badge"></div>
                        <?php endif; ?>
                    </div>
                    <div style="margin-left: 10px; font-weight: 600;"><?= htmlspecialchars($friend['full_name']) ?></div>
                </div>
                
                <!-- Сообщения -->
                <div id="chat-messages" style="flex-grow: 1; padding: 15px; overflow-y: auto; background-color: #f8f9fa;">
                    <?php foreach ($chat_messages as $message): ?>
                        <div style="margin-bottom: 15px; display: flex; <?= $message['sender_id'] == $user['id'] ? 'justify-content: flex-end;' : 'justify-content: flex-start;' ?>">
                            <div style="max-width: 70%;">
                                <div style="background-color: <?= $message['sender_id'] == $user['id'] ? 'var(--primary-color); color: white;' : 'white;' ?>; padding: 10px 15px; border-radius: 18px; display: inline-block;">
                                    <?= htmlspecialchars($message['content']) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: var(--gray-color); text-align: <?= $message['sender_id'] == $user['id'] ? 'right' : 'left' ?>; margin-top: 5px;">
                                    <?= time_elapsed_string($message['created_at']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Форма отправки сообщения -->
                <div style="padding: 15px; border-top: 1px solid #eee; background-color: white;">
                    <form id="send-message-form" style="display: flex;">
                        <input type="text" name="message" placeholder="Написать сообщение..." style="flex-grow: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid #ddd; outline: none;" autocomplete="off">
                        <button type="submit" style="background: none; border: none; cursor: pointer; margin-left: 10px;">
                            <i class="fas fa-paper-plane" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                        </button>
                    </form>
                </div>
                
                <script>
                document.getElementById('send-message-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const messageInput = this.querySelector('input[name="message"]');
                    const message = messageInput.value.trim();
                    
                    if (message) {
                        fetch('/actions/send_message.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ 
                                receiver_id: <?= $friend_id ?>,
                                message: message 
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Добавляем сообщение в чат
                                const chatMessages = document.getElementById('chat-messages');
                                const now = new Date();
                                
                                const messageElement = document.createElement('div');
                                messageElement.style.marginBottom = '15px';
                                messageElement.style.display = 'flex';
                                messageElement.style.justifyContent = 'flex-end';
                                
                                messageElement.innerHTML = `
                                    <div style="max-width: 70%;">
                                        <div style="background-color: var(--primary-color); color: white; padding: 10px 15px; border-radius: 18px; display: inline-block;">
                                            ${message}
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--gray-color); text-align: right; margin-top: 5px;">
                                            Только что
                                        </div>
                                    </div>
                                `;
                                
                                chatMessages.appendChild(messageElement);
                                chatMessages.scrollTop = chatMessages.scrollHeight;
                                messageInput.value = '';
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
                
                // Прокрутка вниз при загрузке
                document.getElementById('chat-messages').scrollTop = document.getElementById('chat-messages').scrollHeight;
                </script>
            <?php else: ?>
                <div style="flex-grow: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--gray-color);">
                    <i class="fas fa-comments" style="font-size: 5rem; margin-bottom: 20px;"></i>
                    <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Выберите чат</h2>
                    <p>Начните общение с друзьями</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>