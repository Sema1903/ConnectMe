document.addEventListener('DOMContentLoaded', function() {
    // В реальном приложении здесь была бы логика для подключения к серверу трансляции
    // и отображения видео потока
    
    // Для демонстрации просто показываем сообщение
    console.log('Live stream page loaded');
    
    // Инициализация чата (если есть)
    const chatForm = document.getElementById('stream-chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = this.querySelector('input[name="message"]');
            const message = messageInput.value.trim();
            
            if (message) {
                const chatMessages = document.getElementById('stream-chat');
                
                // В реальном приложении здесь было бы отправка сообщения на сервер
                const messageElement = document.createElement('div');
                messageElement.style.marginBottom = '15px';
                messageElement.style.display = 'flex';
                
                messageElement.innerHTML = `
                    <img src="https://randomuser.me/api/portraits/${document.body.getAttribute('data-user-avatar')}" alt="User" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                    <div>
                        <div style="font-weight: 600;">${document.body.getAttribute('data-user-name')}</div>
                        <div>${message}</div>
                        <div style="font-size: 0.8rem; color: var(--gray-color);">Только что</div>
                    </div>
                `;
                
                chatMessages.appendChild(messageElement);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                messageInput.value = '';
            }
        });
    }
});