<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Саманта</title>
    <style>
:root {
    --tg-primary: #0088cc;
    --tg-secondary: #6bc259;
    --tg-bg: #ffffff;
    --tg-surface: #f8f9fa;
    --tg-text-primary: #000000;
    --tg-text-secondary: #707579;
    --tg-border: #e7e8ec;
    --tg-hover: #f5f5f5;
    --tg-accent: #e3f2fd;
    --tg-radius: 16px;
    --tg-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    --tg-message-bg: #f0f2f5;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    background: var(--tg-bg);
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.main-container {
    width: 100%;
    max-width: 500px;
    height: 100%;
    max-height: 700px;
    margin: 0 auto;
}

.chat-container {
    background: var(--tg-bg);
    border-radius: var(--tg-radius);
    box-shadow: var(--tg-shadow);
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
    border: 1px solid var(--tg-border);
}

.chat-header {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    background: var(--tg-primary);
    color: white;
    gap: 12px;
}

.back-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
}

.chat-header-content {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-header svg {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.chat-header h1 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.2;
}

.chat-box {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
    background: var(--tg-surface);
}

.message-bubble {
    max-width: 80%;
    padding: 12px 16px;
    border-radius: 18px;
    line-height: 1.4;
    word-wrap: break-word;
    position: relative;
    animation: messageSlideIn 0.3s ease;
}

@keyframes messageSlideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-message {
    align-self: flex-end;
    background: var(--tg-primary);
    color: white;
    border-bottom-right-radius: 6px;
}

.bot-message {
    align-self: flex-start;
    background: var(--tg-message-bg);
    color: var(--tg-text-primary);
    border-bottom-left-radius: 6px;
}

.initial-message {
    background: var(--tg-primary);
    color: white;
    align-self: center;
    text-align: center;
    max-width: 90%;
}

.chat-input {
    padding: 16px 20px;
    background: var(--tg-bg);
    border-top: 1px solid var(--tg-border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-input input {
    flex: 1;
    padding: 12px 20px;
    border: 1px solid var(--tg-border);
    border-radius: 24px;
    outline: none;
    font-size: 15px;
    background: var(--tg-surface);
    transition: all 0.2s ease;
}

.chat-input input:focus {
    border-color: var(--tg-primary);
    background: var(--tg-bg);
}

.chat-input button {
    background: var(--tg-primary);
    border: none;
    border-radius: 50%;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: white;
}

.chat-input button:hover:not(:disabled) {
    background: #0066a4;
    transform: scale(1.05);
}

.chat-input button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.chat-input button svg {
    width: 20px;
    height: 20px;
    transform: rotate(90deg);
}

.loading-indicator {
    align-self: center;
    margin: 8px 0;
}

.loading-spinner {
    animation: spin 1s linear infinite;
    color: var(--tg-text-secondary);
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Анимации для сообщений */
.message-bubble {
    animation: messageSlideIn 0.3s ease forwards;
    opacity: 0;
}

/* Эффекты при наведении */
.message-bubble:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
    :root {
        --tg-bg: #1a1a1a;
        --tg-surface: #2a2a2a;
        --tg-text-primary: #ffffff;
        --tg-text-secondary: #a8a8a8;
        --tg-border: #3a3a3a;
        --tg-hover: #2a2a2a;
        --tg-message-bg: #2a2a2a;
    }
    
    .chat-input input {
        background: var(--tg-surface);
        color: var(--tg-text-primary);
    }
    
    .bot-message {
        background: var(--tg-surface);
        border: 1px solid var(--tg-border);
    }
}

/* Адаптивность */
@media (max-width: 768px) {
    .main-container {
        max-width: 100%;
        max-height: 100%;
        border-radius: 0;
    }
    
    .chat-container {
        border-radius: 0;
        border: none;
    }
    
    .chat-header {
        padding: 12px 16px;
    }
    
    .chat-box {
        padding: 16px;
    }
    
    .chat-input {
        padding: 12px 16px;
    }
    
    .message-bubble {
        max-width: 85%;
    }
}

@media (max-width: 480px) {
    .chat-header h1 {
        font-size: 14px;
    }
    
    .message-bubble {
        max-width: 90%;
        padding: 10px 14px;
        font-size: 14px;
    }
    
    .chat-input input {
        padding: 10px 16px;
        font-size: 14px;
    }
}

/* Кастомный скроллбар */
.chat-box::-webkit-scrollbar {
    width: 6px;
}

.chat-box::-webkit-scrollbar-track {
    background: transparent;
}

.chat-box::-webkit-scrollbar-thumb {
    background: var(--tg-border);
    border-radius: 3px;
}

.chat-box::-webkit-scrollbar-thumb:hover {
    background: var(--tg-text-secondary);
}

/* Эффект печатания для бота */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 12px 16px;
    background: var(--tg-message-bg);
    border-radius: 18px;
    align-self: flex-start;
    margin-bottom: 8px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    background: var(--tg-text-secondary);
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-4px); }
}

/* Анимация появления чата */
.chat-container {
    animation: chatAppear 0.3s ease;
}

@keyframes chatAppear {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Состояние фокуса для инпута */
.chat-input:focus-within {
    background: var(--tg-accent);
}

/* Выделение текста в сообщениях */
.message-bubble ::selection {
    background: rgba(0, 136, 204, 0.3);
}

/* Анимация отправки сообщения */
.message-bubble.sending {
    opacity: 0.7;
    transform: scale(0.95);
}

.message-bubble.sent {
    opacity: 1;
    transform: scale(1);
}
</style>

<script>
// Добавляем анимации для улучшения UX
document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    
    // Плавная прокрутка к новым сообщениям
    const scrollToBottom = () => {
        chatBox.scrollTo({
            top: chatBox.scrollHeight,
            behavior: 'smooth'
        });
    };
    
    // Анимация отправки сообщения
    const animateMessageSend = (messageDiv) => {
        messageDiv.classList.add('sending');
        setTimeout(() => {
            messageDiv.classList.remove('sending');
            messageDiv.classList.add('sent');
        }, 150);
    };
    
    // Автофокус на инпут при загрузке
    userInput.focus();
    
    // Анимация появления чата
    const chatContainer = document.querySelector('.chat-container');
    chatContainer.style.animation = 'chatAppear 0.3s ease';
});
</script>
</head>
<body>
    <div class="main-container">
        <div class="chat-container">
            <div class="chat-header">
                <!-- SVG иконки из heroicons -->
                <a href = 'games.php'><button class = 'back'>Назад</button></a>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.848 2.771A49.144 49.144 0 0 1 12 2.25c2.43 0 4.817.178 7.152.521A1.875 1.875 0 0 1 21.75 4.875v.29c-.734.195-1.465.39-2.195.584c-1.458.388-2.909.777-4.362 1.165a.75.75 0 0 0-.806.126l-7.796 7.796a.75.75 0 0 1-.53.22h-3.834a.75.75 0 0 1-.53-.22L2.946 9.426a49.163 49.163 0 0 1 1.902-6.655ZM18.57 20.31A49.144 49.144 0 0 1 12 20.75c-2.43 0-4.817-.178-7.152-.521a1.875 1.875 0 0 1-2.152-2.104v-.29c.734-.195 1.465-.39 2.195-.584c1.458-.388 2.909-.777 4.362-1.165a.75.75 0 0 0 .806-.126l7.796-7.796a.75.75 0 0 1 .53-.22h3.834a.75.75 0 0 1 .53.22l2.946 2.946a49.163 49.163 0 0 1-1.902 6.655Z" clip-rule="evenodd" />
                </svg>
                Саманта, твой помощник и лучший друг
            </div>
            
            <div class="chat-box" id="chat-box">
                <div class="message-bubble bot-message initial-message">
                    Привет! Я — Саманта, поболтаем?
                </div>
                <!-- Сюда будут добавляться новые сообщения -->
            </div>

            <div class="chat-input">
                <input type="text" id="user-input" placeholder="Введите ваше сообщение..." autocomplete="off">
                <button id="send-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5" style="transform: rotate(90deg);">
                        <path d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.917H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.917a.75.75 0 0 0 .926.94 60.542 60.542 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.542 60.542 0 0 0 3.478 2.405Z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chatBox = document.getElementById('chat-box');
            const userInput = document.getElementById('user-input');
            const sendBtn = document.getElementById('send-btn');
            
            // Здесь вы можете задать два разных характера
            const defaultPersona = "Ты — Саманта, добрый и полезный ИИ помощник и друг пользователя. Говори с ним на ты и не упоминай, что ты ИИ";
            const specialPersona = ""
            const triggerPhrase = "548Djdfj!";
            
            let currentPersona = defaultPersona;
            let messages = [];
            let isLoading = false;

            const scrollToBottom = () => {
                chatBox.scrollTop = chatBox.scrollHeight;
            };

            const addMessageToChat = (sender, text) => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message-bubble ${sender}-message`;
                messageDiv.textContent = text;
                chatBox.appendChild(messageDiv);
                scrollToBottom();
            };

            const showLoading = () => {
                isLoading = true;
                sendBtn.disabled = true;
                userInput.disabled = true;
                const loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'loading-indicator';
                loadingIndicator.innerHTML = `
                    <svg class="loading-spinner" style="width: 32px; height: 32px; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
                chatBox.appendChild(loadingIndicator);
                scrollToBottom();
            };

            const hideLoading = () => {
                isLoading = false;
                sendBtn.disabled = false;
                userInput.disabled = false;
                const loadingIndicator = chatBox.querySelector('.loading-indicator');
                if (loadingIndicator) {
                    chatBox.removeChild(loadingIndicator);
                }
            };
            
            // Функция для инициализации нового диалога с заданным характером
            const startNewChat = (persona) => {
                // Устанавливаем текущий характер
                currentPersona = persona;

                // Очищаем чат и историю сообщений
                chatBox.innerHTML = '';
                messages = [];

                // Добавляем системное сообщение для установки характера
                messages.push({ role: 'user', parts: [{ text: currentPersona }] });

                // Добавляем приветственное сообщение от бота
                const welcomeMessage = (currentPersona === defaultPersona) 
                    ? 'Привет! Я — Саманта, твоя лучшая подруга и твой ИИ-помощник. Поболтаем?'
                    : 'Приветик, о чем поболтаем?';
                
                addMessageToChat('bot', welcomeMessage);
            };

            const handleSendMessage = async () => {
                const messageText = userInput.value.trim();
                if (messageText === '' || isLoading) return;

                // Проверка на триггерную фразу для смены характера
                if (messageText === triggerPhrase) {
                    // Если пользователь ввел триггерную фразу, меняем характер на специальный
                    addMessageToChat('user', messageText); // Добавляем сообщение пользователя в чат
                    addMessageToChat('bot', 'Характер меняется... *звуки трансформации*');
                    setTimeout(() => startNewChat(specialPersona), 1000); // Задержка для эффекта
                    userInput.value = '';
                    return; // Завершаем функцию, чтобы не отправлять запрос в API
                }

                // Добавляем сообщение пользователя в чат
                addMessageToChat('user', messageText);
                messages.push({ role: 'user', parts: [{ text: messageText }] });
                userInput.value = '';

                showLoading();

                try {
                    // Формируем payload, включая всю историю сообщений
                    const payload = {
                        contents: messages,
                    };

                    // Здесь API Key будет автоматически предоставлен
                    const apiKey = "";
                    const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=${apiKey}`;

                    const response = await fetch(apiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        throw new Error(`API Error: ${response.status} ${response.statusText}`);
                    }

                    const result = await response.json();
                    const botResponse = result?.candidates?.[0]?.content?.parts?.[0]?.text || 'Извините, я не смог получить ответ.';
                    
                    // Добавляем ответ бота в чат и историю
                    addMessageToChat('bot', botResponse);
                    messages.push({ role: 'model', parts: [{ text: botResponse }] });

                } catch (error) {
                    console.error('Ошибка при обращении к API:', error);
                    addMessageToChat('bot', `Извини, пожалуйста, произошла ошибка: ${error.message}. Вероятнее всего, мне не рады в России, поэтому тебе придется воспользоваться VPN(((`);
                } finally {
                    hideLoading();
                }
            };
            
            // Обработчики событий
            sendBtn.addEventListener('click', handleSendMessage);
            userInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    handleSendMessage();
                }
            });

            // Инициализация при загрузке страницы с начальным характером
            startNewChat(defaultPersona);
        });
    </script>








    <style>
/* Убираем фокусировку на мобильных устройствах */
@media (max-width: 768px) {
    input:focus,
    textarea:focus,
    select:focus {
        outline: none !important;
        box-shadow: none !important;
        border-color: inherit !important;
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }
    
    /* Убираем подсветку при касании */
    input,
    textarea,
    select,
    button,
    a {
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
    }
    
    /* Предотвращаем увеличение на iOS */
    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="search"],
    input[type="tel"],
    input[type="number"],
    textarea {
        font-size: 16px !important;
        transform: translateZ(0);
    }
    
    /* Отключаем действие масштабирования при фокусе */
    input:focus,
    textarea:focus {
        transform: scale(1) !important;
    }
}

/* Дополнительные стили для улучшения UX */
@media (max-width: 768px) {
    /* Плавное появление клавиатуры */
    input,
    textarea {
        transition: all 0.3s ease !important;
    }
    
    /* Убираем стандартное поведение iOS */
    * {
        -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Для WebKit браузеров */
    input:focus,
    textarea:focus {
        -webkit-user-modify: read-write-plaintext-only;
    }
}
</style>

<script>
// Скрипт для предотвращения фокусировки и масштабирования
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем мобильное устройство
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Добавляем класс для мобильных устройств
        document.documentElement.classList.add('is-mobile');
        
        // Отключаем масштабирование при фокусе
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(element => {
            // Убираем outline при фокусе
            element.addEventListener('focus', function(e) {
                this.style.outline = 'none';
                this.style.boxShadow = 'none';
                this.style.webkitAppearance = 'none';
            });
            
            // Предотвращаем изменение масштаба
            element.addEventListener('touchstart', function(e) {
                // Сохраняем текущий масштаб
                const viewport = document.querySelector('meta[name="viewport"]');
                if (viewport) {
                    viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                }
            });
            
            // Восстанавливаем после потери фокуса
            element.addEventListener('blur', function(e) {
                setTimeout(() => {
                    const viewport = document.querySelector('meta[name="viewport"]');
                    if (viewport) {
                        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0');
                    }
                }, 300);
            });
        });
        
        // Дополнительная защита от zoom
        document.addEventListener('touchstart', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                document.documentElement.style.zoom = "reset";
            }
        });
        
        // Убираем выделение текста при касании
        document.addEventListener('touchstart', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                e.preventDefault();
                setTimeout(() => {
                    e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
                }, 0);
            }
        }, { passive: false });
    }
});

// Альтернативный подход - отключение масштабирования полностью
function disableZoom() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
}

// Включаем при загрузке для мобильных
if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
    disableZoom();
    
    // Переотключаем при изменении ориентации
    window.addEventListener('orientationchange', disableZoom);
    window.addEventListener('resize', disableZoom);
}

// Простой способ - просто убираем outline
document.addEventListener('focusin', function(e) {
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        if (e.target.matches('input, textarea, select')) {
            e.target.style.outline = 'none';
            e.target.style.boxShadow = 'none';
        }
    }
});

// Убираем стандартное поведение браузера
document.addEventListener('touchstart', function(e) {
    if (e.target.matches('input, textarea, select')) {
        e.target.style.webkitUserSelect = 'none';
        e.target.style.userSelect = 'none';
    }
}, { passive: true });

// Восстанавливаем selection после фокуса
document.addEventListener('focus', function(e) {
    if (e.target.matches('input, textarea') && /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        setTimeout(() => {
            e.target.selectionStart = e.target.selectionEnd = e.target.value.length;
        }, 10);
    }
}, true);
</script>

<style>
/* Дополнительные гарантированные стили */
.is-mobile input:focus,
.is-mobile textarea:focus,
.is-mobile select:focus {
    outline: none !important;
    outline-offset: 0 !important;
    box-shadow: none !important;
    border-color: inherit !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
}

/* Убираем подсветку для всех интерактивных элементов */
.is-mobile *:focus {
    outline: none !important;
}

/* Предотвращаем изменение масштаба */
.is-mobile {
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
}

/* Для iOS Safari */
@supports (-webkit-touch-callout: none) {
    .is-mobile input,
    .is-mobile textarea {
        font-size: 16px !important;
    }
}

/* Убираем стандартные стили форм в iOS */
.is-mobile input[type="text"],
.is-mobile input[type="password"],
.is-mobile input[type="email"],
.is-mobile input[type="search"],
.is-mobile textarea {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border-radius: 0;
}
</style>
</body>
</html>