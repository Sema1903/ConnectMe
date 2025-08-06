/**
 * Главный скрипт для работы с лентой постов
 * Обрабатывает лайки, комментарии, упоминания и бесконечную ленту
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация обработчиков событий
    initEventHandlers();
    
    // Запуск бесконечной ленты
    initInfiniteScroll();
    
    // Обработка уже загруженных упоминаний
    processMentions();
});

// ======================
// ОСНОВНЫЕ ОБРАБОТЧИКИ
// ======================

function initEventHandlers() {
    // Делегирование событий для всей страницы
    document.addEventListener('click', function(e) {
        // Обработка лайков
        if (e.target.closest('.like-btn')) {
            handleLikeClick(e);
            return;
        }
        
        // Обработка кнопки комментариев
        if (e.target.closest('.comment-btn')) {
            const commentBtn = e.target.closest('.comment-btn');
            const postId = commentBtn.dataset.postId;
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection) {
                commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
                
                if (commentsSection.style.display === 'block') {
                    loadComments(postId);
                }
            }
            return;
        }
        
        // Обработка кликов по упоминаниям
        if (e.target.classList.contains('mention')) {
            handleMentionClick(e);
            return;
        }
    });
    
    // Обработка отправки комментариев
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('comment-form')) {
            e.preventDefault();
            const form = e.target;
            const postId = form.dataset.postId;
            const input = form.querySelector('input[name="comment"]');
            const comment = input.value.trim();
            
            if (comment) {
                addComment(postId, comment);
                input.value = '';
            }
        }
    });
}

// ======================
// ФУНКЦИИ ДЛЯ ЛАЙКОВ
// ======================

async function handleLikeClick(e) {
    const likeBtn = e.target.closest('.like-btn');
    const postId = likeBtn.dataset.postId;
    
    try {
        // Показываем состояние загрузки
        const originalHTML = likeBtn.innerHTML;
        likeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        const response = await fetch('/actions/like_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateLikeUI(postId, likeBtn, data);
        } else {
            throw new Error(data.message || 'Ошибка при обработке лайка');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        likeBtn.innerHTML = originalHTML;
    }
}

function updateLikeUI(postId, likeBtn, data) {
    const icon = likeBtn.querySelector('i');
    const likesCount = document.querySelector(`#post-${postId} .likes`);
    
    if (data.action === 'like') {
        icon.className = 'fas fa-thumbs-up';
        likeBtn.classList.add('liked');
    } else {
        icon.className = 'far fa-thumbs-up';
        likeBtn.classList.remove('liked');
    }
    
    if (likesCount) {
        likesCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count}`;
    }
}

// ======================
// ФУНКЦИИ ДЛЯ КОММЕНТАРИЕВ
// ======================

async function addComment(postId, comment) {
    try {
        const response = await fetch('/actions/add_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                post_id: postId,
                comment: comment
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Обновляем список комментариев
            await loadComments(postId);
            
            // Обновляем счетчик комментариев
            const counter = document.querySelector(`#post-${postId} .comments`);
            if (counter) {
                counter.textContent = `${data.comments_count} комментариев`;
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function loadComments(postId) {
    try {
        const commentsList = document.getElementById(`comments-list-${postId}`);
        if (!commentsList) return;
        
        commentsList.innerHTML = '<div class="loading">Загрузка комментариев...</div>';
        
        const response = await fetch(`/actions/get_comments.php?post_id=${postId}`);
        const data = await response.json();
        
        if (data.success) {
            renderComments(postId, data.comments);
            processMentions();
        }
    } catch (error) {
        console.error('Ошибка загрузки комментариев:', error);
        const commentsList = document.getElementById(`comments-list-${postId}`);
        if (commentsList) {
            commentsList.innerHTML = '<div class="error">Ошибка загрузки комментариев</div>';
        }
    }
}

function renderComments(postId, comments) {
    const commentsList = document.getElementById(`comments-list-${postId}`);
    if (!commentsList) return;
    
    if (comments.length === 0) {
        commentsList.innerHTML = '<div class="no-comments">Пока нет комментариев</div>';
        return;
    }
    
    commentsList.innerHTML = comments.map(comment => `
        <div class="comment">
            <img src="assets/images/avatars/${comment.avatar}" class="comment-avatar">
            <div class="comment-body">
                <div class="comment-author">${comment.full_name}</div>
                <div class="comment-text">${processMentionsText(comment.content)}</div>
                <div class="comment-time">${formatTime(comment.created_at)}</div>
            </div>
        </div>
    `).join('');
}

// ======================
// ФУНКЦИИ ДЛЯ УПОМИНАНИЙ (@username)
// ======================

function processMentions() {
    document.querySelectorAll('.post-text, .comment-text').forEach(element => {
        element.innerHTML = processMentionsText(element.textContent);
    });
}

function processMentionsText(text) {
    return text.replace(/@(\w+)/g, '<span class="mention" data-username="$1">@$1</span>');
}

function handleMentionClick(e) {
    e.preventDefault();
    const username = e.target.dataset.username;
    window.location.href = `/profile.php?username=${username}`;
}

// ======================
// БЕСКОНЕЧНАЯ ЛЕНТА
// ======================

function initInfiniteScroll() {
    let isLoading = false;
    let currentPage = 1;
    const postsFeed = document.querySelector('.posts-feed');
    
    if (!postsFeed) return;
    
    window.addEventListener('scroll', async function() {
        if (isLoading) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const pageHeight = document.body.offsetHeight;
        const threshold = 500;
        
        if (scrollPosition < pageHeight - threshold) return;
        
        isLoading = true;
        currentPage++;
        
        const loader = document.createElement('div');
        loader.className = 'loader';
        loader.innerHTML = '<div class="spinner"></div>';
        postsFeed.appendChild(loader);
        
        try {
            const response = await fetch(`/actions/load_posts.php?page=${currentPage}`);
            const html = await response.text();
            
            loader.remove();
            
            if (html.trim()) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newPosts = tempDiv.querySelectorAll('.post-card');
                
                newPosts.forEach(post => {
                    postsFeed.appendChild(post);
                });
                
                // Обрабатываем упоминания и формы в новых постах
                processMentions();
                initCommentForms();
            }
        } catch (error) {
            console.error('Ошибка загрузки постов:', error);
            loader.remove();
        } finally {
            isLoading = false;
        }
    });
}

// Инициализация форм комментариев в новых постах
function initCommentForms() {
    document.querySelectorAll('.post-card').forEach(post => {
        const form = post.querySelector('.comment-form');
        if (form && !form.dataset.initialized) {
            form.dataset.initialized = 'true';
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const input = this.querySelector('input[name="comment"]');
                const comment = input.value.trim();
                
                if (comment) {
                    addComment(postId, comment);
                    input.value = '';
                }
            });
        }
    });
}

// ======================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ======================

function formatTime(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'только что';
    if (diff < 3600) return `${Math.floor(diff / 60)} мин. назад`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ч. назад`;
    
    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}