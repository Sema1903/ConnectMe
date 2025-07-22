document.addEventListener('DOMContentLoaded', function() {
    // Обработка лайков
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            likePost(postId);
        });
    });
    
    // Обработка комментариев
    document.querySelectorAll('.comment-btn').forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const commentsSection = document.getElementById(`comments-${postId}`);
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                loadComments(postId);
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });
    
    // Отправка комментариев
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const postId = this.getAttribute('data-post-id');
            const commentInput = this.querySelector('input[name="comment"]');
            const comment = commentInput.value.trim();
            
            if (comment) {
                addComment(postId, comment);
                commentInput.value = '';
            }
        });
    });
});

function likePost(postId) {
    fetch('/actions/like_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeBtn = document.querySelector(`.like-btn[data-post-id="${postId}"]`);
            const likesCount = document.querySelector(`#post-${postId} .likes`);
            
            if (data.action === 'like') {
                likeBtn.innerHTML = '<i class="fas fa-thumbs-up"></i><span>Нравится</span>';
                likesCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count}`;
            } else {
                likeBtn.innerHTML = '<i class="far fa-thumbs-up"></i><span>Нравится</span>';
                likesCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count}`;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function loadComments(postId) {
    fetch(`/actions/get_comments.php?post_id=${postId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentsList = document.getElementById(`comments-list-${postId}`);
            commentsList.innerHTML = '';
            
            data.comments.forEach(comment => {
                const commentElement = document.createElement('div');
                commentElement.className = 'comment';
                commentElement.style.display = 'flex';
                commentElement.style.marginBottom = '10px';
                
                commentElement.innerHTML = `
                    <img src="/assets/images/avatars/${comment.avatar}" alt="User" style="width: 32px; height: 32px; border-radius: 50%; margin-right: 10px;">
                    <div>
                        <div style="font-weight: 600;">${comment.full_name}</div>
                        <div>${comment.content}</div>
                        <div style="font-size: 0.8rem; color: #5f6368;">${comment.created_at}</div>
                    </div>
                `;
                
                commentsList.appendChild(commentElement);
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

function addComment(postId, comment) {
    fetch('/actions/add_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ post_id: postId, comment: comment })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadComments(postId);
            
            // Обновляем счетчик комментариев
            const commentsCount = document.querySelector(`#post-${postId} .comments`);
            commentsCount.textContent = `${data.comments_count} комментариев`;
        }
    })
    .catch(error => console.error('Error:', error));
}