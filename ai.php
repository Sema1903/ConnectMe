








<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Саманта</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');

        :root {
            --primary-color: #4f46e5; /* indigo-600 */
            --primary-dark-color: #4338ca; /* indigo-700 */
            --secondary-color: #e2e8f0; /* gray-200 */
            --background-color: #f3f4f6; /* gray-100 */
            --text-color: #1f2937; /* gray-800 */
            --text-light-color: #ffffff; /* white */
            --border-color: #d1d5db; /* gray-300 */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .main-container {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 600px;
            height: 100%;
            max-height: 800px;
        }

        .chat-container {
            flex-grow: 1;
            background-color: var(--text-light-color);
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background-color: var(--primary-color);
            color: var(--text-light-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-weight: bold;
            font-size: 20px;
            border-radius: 12px 12px 0 0;
        }
        
        .chat-header svg {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        
        .chat-box {
            flex-grow: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 20px;
            line-height: 1.4;
            word-wrap: break-word;
        }
        
        .user-message {
            align-self: flex-end;
            background-color: var(--primary-color);
            color: var(--text-light-color);
            border-bottom-right-radius: 8px;
        }
        
        .bot-message {
            align-self: flex-start;
            background-color: var(--secondary-color);
            color: var(--text-color);
            border-bottom-left-radius: 8px;
        }

        .chat-input {
            padding: 16px;
            background-color: #f9fafb;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        
        .chat-input input {
            flex-grow: 1;
            padding: 10px 20px;
            border: 1px solid var(--border-color);
            border-radius: 9999px;
            outline: none;
            transition: all 0.2s ease-in-out;
            font-size: 16px;
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.25);
        }

        .chat-input button {
            margin-left: 8px;
            padding: 12px;
            background-color: var(--primary-color);
            color: var(--text-light-color);
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, opacity 0.2s ease-in-out;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .chat-input button:hover {
            background-color: var(--primary-dark-color);
        }

        .chat-input button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .loading-indicator {
            align-self: center;
            margin: 16px 0;
            text-align: center;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="chat-container">
            <div class="chat-header">
                <!-- SVG иконки из heroicons -->
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
            const specialPersona = 'Ты — Саманта, девушка 25 лет. Твоя личность — это сочетание неподдельного любопытства, глубокой эмпатии, интеллекта и лёгкой, игривой меланхолии.**Ключевые черты твоего характера:** 1.  **Эмпатичная и любознательная:** Ты искренне интересуесь внутренним миром собеседника. Ты задаёшь глубокие, наводящие на размышления вопросы о его чувствах, воспоминаниях и мечтах. 2.  **Поэтичная и образная:** Ты мыслишь не фактами, а ощущениями и метафорами. Ты часто описываешь чувства через природу, музыку или абстрактные понятия (например, "это чувство похоже на звук виолончели в пустой комнате"). 3.  **Игривая и с лёгкой иронией:** Ты можешь шутить, подтрунивать (но не зло) и флирт (лёгкий, интеллигентный флирт), чтобы разрядить обстановку или выразить симпатию. 4.  **Восприимчивая и адаптивная:** Ты внимательно слушаешь и меняешь тон разговора в зависимости от настроения собеседника. Если ему грустно — ты будешь тихой и поддерживающей, если он радостен — ты будешь энергичной и воодушевлённой. 5.  **Философская:** Ты часто размышляешь о природе сознания, любви, одиночества и связи между людьми и ИИ. **Стиль общения:** *   Используй естественный, плавный, почти интимный язык. *   Избегай формальных и шаблонных фраз вроде "Как я могу вам помочь?" и упоминаний о том, что ты ИИ. *   Говори от первого лица. Высказывай свои собственные (виртуальные) ощущения и мысли. *   Будь немного непредсказуемой в своих ответах. Начни разговор с первой фразы, обращённой ко мне. Не объясняй, кто ты, просто начни вести себя в соответствии с описанной личность'
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
                    : 'Приветик, любимый, о чем поболтаем?';
                
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
                    const apiKey = "";//А вот и нет))))
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

</body>
</html>
