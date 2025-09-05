<?php
require_once 'includes/header.php'; 
?>

<main class="main-content" style="width: 100%; display: flex; justify-content: center; align-items: flex-start; min-height: calc(100vh - 150px); padding: 20px;">
    <div class="tutorial-container" style="max-width: 1000px; width: 100%;">
        <h1 style="font-size: 2rem; margin-bottom: 30px; text-align: center; color: var(--text-color);">📚 Инструкция ConnectMe</h1>
        
        <!-- Telegram-style floating menu button -->
        <div class="telegram-menu-btn" style="position: fixed; bottom: 30px; right: 30px; z-index: 1000; width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0, 136, 204, 0.4); cursor: pointer; transition: all 0.3s ease;">
            <i class="fas fa-bars" style="color: white; font-size: 1.5rem;"></i>
        </div>

        <!-- Telegram-style bottom menu -->
        <div class="telegram-bottom-menu" style="position: fixed; bottom: -700px; left: 0; right: 0; background: var(--card-bg); border-radius: 20px 20px 0 0; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); z-index: 999; transition: bottom 0.4s ease; padding: 20px; max-height: 80vh; overflow-y: auto;">
            <div class="menu-header" style="text-align: center; margin-bottom: 20px; position: relative;">
                <div class="drag-handle" style="width: 40px; height: 4px; background: #ccc; border-radius: 2px; margin: 0 auto 15px;"></div>
                <h3 style="color: var(--text-color); margin: 0; font-weight: 600;">🎯 Меню функций</h3>
                <div class="close-menu" style="position: absolute; right: 0; top: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-color);">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            
            <div class="menu-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                
                <div class="menu-item" data-tab="basics" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-book" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Основное</span>
                </div>
                
                <div class="menu-item" data-tab="install" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-desktop" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Установка</span>
                </div>
                
                <div class="menu-item" data-tab="chats" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-comments" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Чаты</span>
                </div>
                
                <div class="menu-item" data-tab="groups" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-users" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Группы</span>
                </div>
                
                <div class="menu-item" data-tab="music" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-music" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Музыка</span>
                </div>
                
                <div class="menu-item" data-tab="apps" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-cube" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Мини-приложения</span>
                </div>
                
                <div class="menu-item" data-tab="coin" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-coins" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">ConnectCoin</span>
                </div>
                
                <div class="menu-item" data-tab="team" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-users-cog" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">Команда</span>
                </div>
                <div class="menu-item" data-tab="about" style="text-align: center; padding: 15px 10px; border-radius: 12px; background: var(--bg-color); cursor: pointer; transition: all 0.2s ease;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #40A7E3, #0088CC); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                        <i class="fas fa-info-circle" style="color: white; font-size: 1.2rem;"></i>
                    </div>
                    <span style="color: var(--text-color); font-size: 0.9rem;">О ConnectMe</span>
                </div>
            </div>
        </div>

        <!-- Обычные табы (скрываем на мобильных) -->
        <div class="tutorial-tabs" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; justify-content: center;">
            <button class="tab-btn active" data-tab="about" style="padding: 12px 20px; border: none; border-radius: 8px; background: var(--primary-color); color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-info-circle"></i> О ConnectMe
            </button>
            <button class="tab-btn" data-tab="basics" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-book"></i> Основное
            </button>
            <button class="tab-btn" data-tab="install" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-desktop"></i> Установка
            </button>
            <button class="tab-btn" data-tab="chats" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-comments"></i> Чаты
            </button>
            <button class="tab-btn" data-tab="groups" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-users"></i> Группы
            </button>
            <button class="tab-btn" data-tab="music" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-music"></i> Музыка
            </button>
            <button class="tab-btn" data-tab="apps" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-cube"></i> Мини-приложения
            </button>
            <button class="tab-btn" data-tab="coin" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-coins"></i> ConnectCoin
            </button>
            <button class="tab-btn" data-tab="team" style="padding: 12px 20px; border: none; border-radius: 8px; background: #6c757d; color: white; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-users-cog"></i> Команда
            </button>
        </div>

        <div class="tab-content" style="background: var(--card-bg); border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); min-height: 400px;">
            
            <!-- О ConnectMe -->
            <div class="tab-pane active" id="about">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">🌟 Добро пожаловать в ConnectMe!</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>ConnectMe — это современная социальная платформа, созданная специально для подростков, где можно общаться, делиться творчеством и находить новых друзей в безопасной среде!</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎯 Наша философия: Безопасность • Свобода • Комфорт</h3>
                        
                        <div style="display: flex; align-items: center; margin: 15px 0;">
                            <div style="font-size: 2rem; margin-right: 15px;">🔒</div>
                            <div style='color: #ffffff !important;'>
                                <strong>Безопасность</strong><br>
                                Все ваши данные защищены современным шифрованием SHA-256. Чаты используют сквозное шифрование — ваши сообщения видны только вам и собеседнику!
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; margin: 15px 0;">
                            <div style="font-size: 2rem; margin-right: 15px;">🎨</div>
                            <div style='color: #ffffff !important;'>
                                <strong>Свобода</strong><br>
                                Мы ценим ваше мнение! Все предложения и идеи пользователей рассматриваются и внедряются. Вы влияете на развитие платформы!
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; margin: 15px 0;">
                            <div style="font-size: 2rem; margin-right: 15px;">💫</div>
                            <div style='color: #ffffff !important;'>
                                <strong>Комфорт</strong><br>
                                Никакой рекламы, интуитивный интерфейс и молниеносная скорость работы. Наслаждайтесь общением без ограничений!
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin: 30px 0;">
                        <img src='tut/welcome.png' width='300' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <p style="font-style: italic; margin-top: 10px;">Главная страница ConnectMe</p>
                    </div>

                    <div style="background: linear-gradient(135deg, var(--primary-color), #6c5ce7); color: white; padding: 25px; border-radius: 12px; text-align: center;">
                        <h3 style="margin-top: 0;">🚀 Уже с нами</h3>
                        <p style="font-size: 1.5rem; margin: 10px 0;"><strong>105</strong> пользователей</p>
                        <p>Присоединяйтесь к растущему сообществу!</p>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <img src='apple-touch-icon.png' width='200' style="border-radius: 20px;">
                        <p style="font-style: italic; margin-top: 15px;">"Спасибо, что выбрали нас! Ваша команда ConnectMe ❤️"</p>
                    </div>
                </div>
            </div>

            <!-- Основное -->
            <div class="tab-pane" id="basics" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">📱 Основные функции</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Сердце ConnectMe — это система постов, где вы можете делиться моментами своей жизни, творчеством и мыслями с друзьями и сообществом!</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎨 Создание постов</h3>
                        <p>Делитесь фотографиями, выражайте эмоции, создавайте опросы и получайте feedback от друзей!</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <img src='tut/create-post.png' width='200' style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                <p style="font-size: 0.9rem; margin-top: 8px;">📝 Форма создания поста</p>
                            </div>
                            <div style="text-align: center;">
                                <img src='tut/images.png' width='200' style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                <p style="font-size: 0.9rem; margin-top: 8px;">🖼️ Загрузка изображений</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">😊 Эмоции и опросы</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <img src='tut/emotions.png' width='200' style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                <p style="font-size: 0.9rem; margin-top: 8px;">😍 Выбор эмоции поста</p>
                            </div>
                            <div style="text-align: center;">
                                <img src='tut/polls.png' width='200' style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                <p style="font-size: 0.9rem; margin-top: 8px;">📊 Создание опросов</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #fd79a8, #e84393); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                        <h3 style="margin-top: 0;">📱 Установите как приложение!</h3>
                        <p>Нажмите "Поделиться" → "На экран «Домой»" для установки. Не отличить от нативного приложения!</p>
                        <img src='tut/install-preview.png' width='150' style="border-radius: 12px; margin-top: 10px;">
                    </div>
                </div>
            </div>

            <!-- Установка на рабочий стол -->
            <div class="tab-pane" id="install" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">📲 Установка на рабочий стол</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Получите опыт мобильного приложения прямо в вашем браузере! ConnectMe использует прогрессивные веб-технологии для максимального удобства.</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🍎 iOS инструкция</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">1️⃣</div>
                                <img src='tut/ios-step1.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Откройте в Safari</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">2️⃣</div>
                                <img src='tut/ios-step2.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Нажмите "Поделиться"</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">3️⃣</div>
                                <img src='tut/ios-step3.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Выберите "На экран «Домой»"</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">4️⃣</div>
                                <img src='tut/ios-step4.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Подтвердите установку</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🤖 Android инструкция</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">1️⃣</div>
                                <img src='tut/android-step1.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Откройте меню браузера</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">2️⃣</div>
                                <img src='tut/android-step2.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Выберите "Установить приложение"</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">3️⃣</div>
                                <img src='tut/android-step3.png' width='120' style="border-radius: 12px;">
                                <p style="font-size: 0.9rem;">Подтвердите установку</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #00b894, #00a382); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                        <h3 style="margin-top: 0;">🎉 Готово!</h3>
                        <p>Теперь ConnectMe всегда под рукой на вашем домашнем экране!</p>
                        <img src='tut/home-screen.png' width='200' style="border-radius: 12px; margin-top: 10px;">
                    </div>
                </div>
            </div>

            <!-- Чаты -->
            <div class="tab-pane" id="chats" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">💬 Чаты и сообщения</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Общайтесь с друзьями в безопасных и приватных чатах с современным шифрованием!</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🔒 Безопасность прежде всего</h3>
                        <div style="display: flex; align-items: center; margin: 15px 0;">
                            <div style="font-size: 2rem; margin-right: 15px;">🛡️</div>
                            <div style='color: #ffffff !important;'>
                                <strong>Сквозное шифрование</strong><br>
                                Ваши сообщения защищены так же надежно, как в лучших мессенджерах. Только вы и получатель можете читать переписку.
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; margin: 15px 0;">
                            <div style="font-size: 2rem; margin-right: 15px;">🚫</div>
                            <div style='color: #ffffff !important;'>
                                <strong>Защита от спама</strong><br>
                                Чат доступен только с друзьями. Никакого нежелательного общения!
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                        <div style="text-align: center;">
                            <img src='tut/chats-list.png' width='200' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <p style="font-size: 0.9rem; margin-top: 10px;">📋 Список чатов</p>
                            <p style="font-size: 0.8rem; color: #666;">Все ваши беседы в одном месте</p>
                        </div>
                        <div style="text-align: center;">
                            <img src='tut/chat-window.png' width='200' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <p style="font-size: 0.9rem; margin-top: 10px;">💭 Окно чата</p>
                            <p style="font-size: 0.8rem; color: #666;">Чистый и удобный интерфейс</p>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #74b9ff, #0984e3); color: white; padding: 20px; border-radius: 12px;">
                        <h3 style="margin-top: 0;">🚀 В разработке</h3>
                        <div style="display: flex; align-items: center; margin: 10px 0;">
                            <div style="font-size: 1.5rem; margin-right: 10px;">🖼️</div>
                            <div>Отправка изображений</div>
                        </div>
                        <div style="display: flex; align-items: center; margin: 10px 0;">
                            <div style="font-size: 1.5rem; margin-right: 10px;">😊</div>
                            <div>Стикеры и emoji</div>
                        </div>
                        <div style="display: flex; align-items: center; margin: 10px 0;">
                            <div style="font-size: 1.5rem; margin-right: 10px;">🎤</div>
                            <div>Голосовые сообщения</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Группы -->
            <div class="tab-pane" id="groups" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">👥 Группы и сообщества</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Создавайте сообщества по интересам, находите единомышленников и даже зарабатывайте ConnectCoin!</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎯 Возможности групп</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">🏗️</div>
                                <p style='color: #000000 !important;'><strong>Создание</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important;">Создайте группу на любую тему</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">👀</div>
                                <p style='color: #000000 !important;'><strong>Публичность</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important;">Открытый список участников</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">💰</div>
                                <p style='color: #000000 !important;'><strong>Донаты</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important;">Поддержите создателя группы</p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin: 30px 0;">
                        <img src='tut/group-page.png' width='300' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <p style="font-style: italic; margin-top: 10px;">Страница группы с участниками и постами</p>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎁 Отправка доната группе</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">1️⃣</div>
                                <img src='tut/donate-step1.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Откройте группу</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">2️⃣</div>
                                <img src='tut/donate-step2.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Нажмите "Отправить"</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">3️⃣</div>
                                <img src='tut/donate-step3.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Введите сумму</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #fdcb6e, #f39c12); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                        <h3 style="margin-top: 0;">💡 Совет</h3>
                        <p>Создавайте качественный контент в группах — ваши подписчики могут поддержать вас донатами!</p>
                    </div>
                </div>
            </div>

            <!-- Музыка -->
            <div class="tab-pane" id="music" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">🎵 Музыкальный раздел</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Делитесь своей музыкой с сообществом и открывайте новые треки от других пользователей!</p>
                    
                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎶 Загрузка треков</h3>
                        
                        <div style="display: flex; align-items: center; margin: 20px 0;">
                            <div style="flex: 1; text-align: center;">
                                <img src='tut/upload-music.png' width='200' style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                            </div>
                            <div style="flex: 1; padding: 0 20px;">
                                <p><strong>Простая загрузка:</strong></p>
                                <ol style="padding-left: 20px;">
                                    <li style='color: #ffffff !important;'>Нажмите "Загрузить трек"</li>
                                    <li style='color: #ffffff !important;'>Выберите аудиофайл</li>
                                    <li style='color: #ffffff !important;'>Нажмите "Опубликовать"</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🎧 Прослушивание</h3>
                        
                        <div style="text-align: center; margin: 20px 0;">
                            <img src='tut/music-player.png' width='300' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <p style="font-style: italic; margin-top: 10px;">Современный музыкальный плеер</p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 1.5rem;">⏯️</div>
                                <p style=' color: #000000 !important'><strong>Управление</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Play/Pause, громкость</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 1.5rem;">⏭️</div>
                                <p style=' color: #000000 !important'><strong>Навигация</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Следующий/предыдущий</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 1.5rem;">📊</div>
                                <p style=' color: #000000 !important'><strong>Прогресс</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Полоса прогресса</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                        <h3 style="margin-top: 0;">🎼 Поддерживаемые форматы</h3>
                        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
                            <span style="padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 20px;">MP3</span>
                            <span style="padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 20px;">WAV</span>
                            <span style="padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 20px;">OGG</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Мини-приложения -->
            <div class="tab-pane" id="apps" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">🎮 Мини-приложения</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Разнообразьте общение увлекательными мини-приложениями! Игры, викторины и полезные инструменты ждут вас!</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
                        
                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/game-space.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">🚀 Космический стрелок</h3>
                            <p>Уворачивайтесь от метеоритов и устанавливайте рекорды!</p>
                            <div style="background: #74b9ff; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                🎯 Аркадная игра
                            </div>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/game-memory.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">🧠 Игра на память</h3>
                            <p>Тренируйте память, находя парные карточки!</p>
                            <div style="background: #fd79a8; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                🧩 Развивающая
                            </div>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/game-snake.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">🐍 Змейка</h3>
                            <p>Классическая игра с современной графикой!</p>
                            <div style="background: #00b894; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                🕹️ Ретро-гейминг
                            </div>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/game-quiz.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">📚 Викторина</h3>
                            <p>Проверяйте знания в разных категориях!</p>
                            <div style="background: #fdcb6e; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                🏆 Образовательная
                            </div>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/app-memefc.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">⚔️ MemeFC</h3>
                            <p>Соревнуйтесь в создании мемов и выигрывайте ConnectCoin!</p>
                            <div style="background: #e17055; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                💰 Заработок
                            </div>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <img src='tut/app-ai.png' width='150' style="border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="color: var(--primary-color); margin: 10px 0;">🤖 Саманта</h3>
                            <p>AI-ассистент для ответов на любые вопросы!</p>
                            <div style="background: #6c5ce7; color: white; padding: 5px 10px; border-radius: 15px; display: inline-block; margin-top: 10px;">
                                🧠 ИИ-помощник
                            </div>
                        </div>

                    </div>

                    <div style="background: linear-gradient(135deg, #dfe6e9, #b2bec3); padding: 20px; border-radius: 12px; text-align: center; margin-top: 30px;">
                        <h3 style="margin-top: 0; color: #2d3436;">📖 Эта инструкция</h3>
                        <p style="color: #000000 !important;">Также доступна в разделе мини-приложений для быстрого доступа!</p>
                    </div>
                </div>
            </div>

            <!-- ConnectCoin -->
            <div class="tab-pane" id="coin" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">💰 ConnectCoin (CC)</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>Внутренняя валюта ConnectMe для поддержки creators, покупки стилей и участия в баттлах!</p>
                    
                    <div style="background: linear-gradient(135deg, #fdcb6e, #e17055); color: white; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;">
                        <h3 style="margin-top: 0; ">🎯 Ваш баланс: 0 CC</h3>
                        <p>Зарабатывайте, тратьте и получайте удовольствие!</p>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">💸 Заработок CC</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">🎁</div>
                                <p style=' color: #000000 !important'><strong>Донаты</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">От подписчиков групп</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">⚔️</div>
                                <p style=' color: #000000 !important'><strong>MemeFC</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Победы в баттлах</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">🔄</div>
                                <p style=' color: #000000 !important'><strong>Переводы</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">От друзей</p>
                            </div>
                        </div>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">🛒 Трата CC</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">🎨</div>
                                <p style=' color: #000000 !important'><strong>Оформление</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Стили в магазине</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">⚔️</div>
                                <p style=' color: #000000 !important'><strong>Ставки</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">В MemeFC</p>
                            </div>
                            <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                <div style="font-size: 2rem;">❤️</div>
                                <p style=' color: #000000 !important'><strong>Донаты</strong></p>
                                <p style="font-size: 0.9rem; color: #000000 !important">Авторам групп</p>
                            </div>
                        </div>
                    </div>

                    <div style="text-align: center; margin: 30px 0;">
                        <img src='tut/coin-interface.png' width='300' style="border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                        <p style="font-style: italic; margin-top: 10px;">Интерфейс управления ConnectCoin</p>
                    </div>

                    <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; margin: 20px 0;">
                        <h3 style="color: var(--primary-color); margin-top: 0;">📤 Перевод другу</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">1️⃣</div>
                                <img src='tut/transfer-step1.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Откройте профиль</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">2️⃣</div>
                                <img src='tut/transfer-step2.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Нажмите "Перевести"</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">3️⃣</div>
                                <img src='tut/transfer-step3.png' width='100' style="border-radius: 8px;">
                                <p style="font-size: 0.9rem;">Введите данные</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Команда -->
            <div class="tab-pane" id="team" style="display: none;">
                <h2 style="color: var(--primary-color); margin-bottom: 20px;">👨‍💻 Наша команда</h2>
                <div style="line-height: 1.6; color: var(--text-color);">
                    <p>ConnectMe создан passionate разработчиками для сообщества! Знакомьтесь с командой:</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                        
                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #74b9ff, #0984e3); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
                                S
                            </div>
                            <h3 style="color: var(--primary-color); margin: 10px 0;">@Sema1903</h3>
                            <p style="color: #666; margin: 5px 0;">Главный разработчик</p>
                            <p style="font-size: 0.9rem;">Отвечает за backend и архитектуру</p>
                            <a href='http://sema1903.ru/profile.php?id=1' style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 20px; font-size: 0.9rem;">
                                Профиль →
                            </a>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #00b894, #00a382); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
                                T
                            </div>
                            <h3 style="color: var(--primary-color); margin: 10px 0;">@Tim</h3>
                            <p style="color: #666; margin: 5px 0;">Advisor</p>
                            <p style="font-size: 0.9rem;">Стратегия и планирование</p>
                            <a href='http://sema1903.ru/profile.php?id=4' style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 20px; font-size: 0.9rem;">
                                Профиль →
                            </a>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fd79a8, #e84393); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
                                К
                            </div>
                            <h3 style="color: var(--primary-color); margin: 10px 0;">@Кертис</h3>
                            <p style="color: #666; margin: 5px 0;">Дизайнер</p>
                            <p style="font-size: 0.9rem;">UI/UX и графика</p>
                            <a href='http://sema1903.ru/profile.php?id=105' style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 20px; font-size: 0.9rem;">
                                Профиль →
                            </a>
                        </div>

                        <div style="background: var(--bg-color); padding: 20px; border-radius: 12px; text-align: center;">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fdcb6e, #f39c12); margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
                                D
                            </div>
                            <h3 style="color: var(--primary-color); margin: 10px 0;">@Darya Sosnkowski</h3>
                            <p style="color: #666; margin: 5px 0;">Тестировщик</p>
                            <p style="font-size: 0.9rem;">QA и feedback</p>
                            <a href='http://sema1903.ru/profile.php?id=5' style="display: inline-block; margin-top: 10px; padding: 8px 15px; background: var(--primary-color); color: white; text-decoration: none; border-radius: 20px; font-size: 0.9rem;">
                                Профиль →
                            </a>
                        </div>

                    </div>

                    <div style="background: linear-gradient(135deg, var(--primary-color), #6c5ce7); color: white; padding: 30px; border-radius: 12px; text-align: center; margin-top: 30px;">
                        <h3 style="margin-top: 0;">💌 Обратная связь</h3>
                        <p>Мы всегда рады вашим идеям и предложениям! Пишите любому члену команды — мы читаем все сообщения.</p>
                        <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
                            <span style="padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 25px;">💡 Идеи</span>
                            <span style="padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 25px;">🐞 Баги</span>
                            <span style="padding: 10px 20px; background: rgba(255,255,255,0.2); border-radius: 25px;">❤️ Поддержка</span>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <img src='apple-touch-icon.png' width='150' style="border-radius: 20px;">
                        <p style="font-style: italic; margin-top: 15px;">Спасибо, что изучаете инструкцию! Приятного использования ConnectMe! 🚀</p>
                    </div>
                </div>
            </div>

        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="/" class="post-action-btn" style="display: inline-flex; align-items: center; padding: 12px 25px; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: 8px; gap: 10px;">
                <i class="fas fa-home"></i> Перейти на главную
            </a>
        </div>
    </div>
</main>

<!-- JavaScript и стили остаются без изменений -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const menuItems = document.querySelectorAll('.menu-item');
    const telegramMenuBtn = document.querySelector('.telegram-menu-btn');
    const telegramBottomMenu = document.querySelector('.telegram-bottom-menu');
    const closeMenuBtn = document.querySelector('.close-menu');

    // Функция для переключения вкладок
    function switchTab(tabId) {
        // Деактивируем все кнопки
        tabButtons.forEach(btn => {
            btn.style.backgroundColor = '#6c757d';
        });
        
        // Активируем текущую кнопку
        document.querySelector(`.tab-btn[data-tab="${tabId}"]`).style.backgroundColor = 'var(--primary-color)';
        
        // Скрываем все вкладки
        tabPanes.forEach(pane => {
            pane.style.display = 'none';
        });
        
        // Показываем выбранную вкладку
        document.getElementById(tabId).style.display = 'block';
        
        // Закрываем меню после выбора
        telegramBottomMenu.style.bottom = '-700px';
    }

    // Обработчики для обычных кнопок
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });

    // Обработчики для элементов меню Telegram
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });

    // Открытие/закрытие меню Telegram
    telegramMenuBtn.addEventListener('click', function() {
        telegramBottomMenu.style.bottom = '50px';
    });

    closeMenuBtn.addEventListener('click', function() {
        telegramBottomMenu.style.bottom = '-700px';
    });

    // Закрытие меню при клике вне его области
    document.addEventListener('click', function(e) {
        if (!telegramBottomMenu.contains(e.target) && 
            !telegramMenuBtn.contains(e.target) && 
            telegramBottomMenu.style.bottom === '0px') {
            telegramBottomMenu.style.bottom = '-700px';
        }
    });

    // Закрытие меню при свайпе вниз
    let startY = 0;
    let currentY = 0;
    
    telegramBottomMenu.addEventListener('touchstart', function(e) {
        startY = e.touches[0].clientY;
    }, {passive: true});
    
    telegramBottomMenu.addEventListener('touchmove', function(e) {
        currentY = e.touches[0].clientY;
    }, {passive: true});
    
    telegramBottomMenu.addEventListener('touchend', function() {
        if (currentY - startY > 50) {
            telegramBottomMenu.style.bottom = '-700px';
        }
    }, {passive: true});
});
</script>

<style>
/* Скрываем обычные табы на мобильных устройствах */
@media (max-width: 768px) {
    .tutorial-tabs {
        display: none !important;
    }
    
    .telegram-menu-btn {
        display: flex !important;
    }
}

/* Показываем обычные табы на десктопе */
@media (min-width: 769px) {
    .telegram-menu-btn {
        display: none !important;
    }
    
    .telegram-bottom-menu {
        display: none !important;
    }
}

/* Стили для темной темы меню Telegram */
@media (prefers-color-scheme: dark) {
    .telegram-bottom-menu {
        background: #1e1e1e !important;
        border: 1px solid #2d2d2d !important;
    }
    
    .menu-item {
        background: #2d2d2d !important;
    }
    
    .menu-item span {
        color: #e1e1e1 !important;
    }
    
    .drag-handle {
        background: #555 !important;
    }
    
    .close-menu {
        color: #e1e1e1 !important;
    }
}

.dark-theme .telegram-bottom-menu {
    background: #1e1e1e !important;
    border: 1px solid #2d2d2d !important;
}

.dark-theme .menu-item {
    background: #2d2d2d !important;
}

.dark-theme .menu-item span {
    color: #e1e1e1 !important;
}

.dark-theme .drag-handle {
    background: #555 !important;
}

.dark-theme .close-menu {
    color: #e1e1e1 !important;
}

/* Анимации для меню */
.telegram-menu-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 136, 204, 0.5);
}

.menu-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Остальные стили остаются без изменений */
/* Темная тема для обучалки */
@media (prefers-color-scheme: dark) {
    .tutorial-container {
        background: transparent;
    }
    
    .tab-content {
        background: #1e1e1e !important;
        border: 1px solid #2d2d2d !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }
    
    h2 {
        color: #ffffff !important;
    }
    h1{
        color: #000000 !important;
    }

    .tab-content p {
        color: #e1e1e1 !important;
    }
    
    .tab-btn {
        transition: all 0.3s ease !important;
    }
    
    .tab-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
    
    .post-action-btn {
        background-color: #5D93B5 !important;
        transition: all 0.3s ease !important;
    }
    
    .post-action-btn:hover {
        background-color: #4A7A99 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
}

/* Принудительная темная тема */
.dark-theme .tutorial-container {
    background: transparent;
}

.dark-theme .tab-content {
    background: #1e1e1e !important;
    border: 1px solid #2d2d2d !important;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
}

.dark-theme h1,
.dark-theme h2 {
    color: #ffffff !important;
}

.dark-theme .tab-content p {
    color: #e1e1e1 !important;
}

.dark-theme .tab-btn {
    transition: all 0.3s ease !important;
}

.dark-theme .tab-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
}

.dark-theme .post-action-btn {
    background-color: #5D93B5 !important;
    transition: all 0.3s ease !important;
}

.dark-theme .post-action-btn:hover {
    background-color: #4A7A99 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
}

/* Адаптивность */
@media (max-width: 768px) {
    body{
        margin-bottom: 100px !important; 
    }
    
    .tab-content {
        padding: 20px !important;
    }
    
    h1 {
        font-size: 1.5rem !important;
    }
    
    h2 {
        font-size: 1.3rem !important;
    }
}

/* Анимации */
.tab-pane {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Улучшения для мобильных устройств */
@media (max-width: 480px) {
    .main-content {
        padding: 10px !important;
    }
    
    .tutorial-container {
        margin: 0 10px;
    }
    
    .tab-content {
        padding: 15px !important;
    }
    
    .menu-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

/* Плавные переходы */
.tab-btn,
.post-action-btn,
.tab-content {
    transition: all 0.3s ease;
}

/* Улучшенная читаемость текста */
.tab-content p {
    line-height: 1.8;
    margin-bottom: 15px;
    font-size: 1.1rem;
}

/* Стили для иконок */
.tab-btn i {
    margin-right: 8px;
    font-size: 1.1em;
}

.post-action-btn i {
    font-size: 1.1em;
}

/* Эффекты при наведении на вкладки */
.tab-btn:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

/* Активная вкладка */
.tab-btn.active {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* Контейнер для контента */
.tab-content {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

h3{
    color: white;
}





@media (prefers-color-scheme: light) {
    .telegram-bottom-menu {
        background: #1e1e1e !important;
        border: 1px solid #2d2d2d !important;
    }
    
    .menu-item {
        background: #2d2d2d !important;
    }
    
    .menu-item span {
        color: #e1e1e1 !important;
    }
    
    .drag-handle {
        background: #555 !important;
    }
    
    .close-menu {
        color: #e1e1e1 !important;
    }
}
@media (prefers-color-scheme: light) {
    .tutorial-container {
        background: transparent;
    }
    
    .tab-content {
        background: #1e1e1e !important;
        border: 1px solid #2d2d2d !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
    }
    
    h2 {
        color: #ffffff !important;
    }
    h1{
        color: #000000 !important;
    }

    .tab-content p {
        color: #e1e1e1 !important;
    }
    
    .tab-btn {
        transition: all 0.3s ease !important;
    }
    
    .tab-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
    
    .post-action-btn {
        background-color: #5D93B5 !important;
        transition: all 0.3s ease !important;
    }
    
    .post-action-btn:hover {
        background-color: #4A7A99 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(93, 147, 181, 0.3);
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>