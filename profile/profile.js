document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const themeToggle = document.querySelector('#theme-toggle');
    const themeMenuItem = document.querySelector('#theme');
    const modal = document.querySelector('.modal');
    const imageModal = document.querySelector('.image-modal');
    const modalImage = document.querySelector('#modal-image');
    const closeImageModal = document.querySelector('#close-image-modal');
    const escapeHTML = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    // Hamburger menu
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }

    // Theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            themeToggle.innerHTML = isDarkMode ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
        });

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="bi bi-sun"></i>';
        } else {
            themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i>';
        }
    }

    // Theme modal
    if (themeMenuItem) {
        themeMenuItem.addEventListener('click', (e) => {
            e.preventDefault();
            if (modal) modal.style.display = 'flex';
        });
    }

    // Image modal
    function initializePostImages() {
        document.querySelectorAll('.post-image').forEach(img => {
            img.addEventListener('click', () => {
                modalImage.src = img.src;
                imageModal.style.display = 'flex';
            });
        });
    }

    if (closeImageModal) {
        closeImageModal.addEventListener('click', () => {
            imageModal.style.display = 'none';
        });
    }

    if (imageModal) {
        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) imageModal.style.display = 'none';
        });
    }

    initializePostImages();

    // Edit profile modal
    window.openEditProfileModal = () => {
        document.querySelector('#edit-profile-modal').style.display = 'flex';
    };

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('#edit-profile-modal').style.display = 'none';
        });
    });

    // Profile photo upload preview
    const profilePhotoInput = document.querySelector('#profile-photo-upload');
    const imagePreview = document.querySelector('.image-preview');
    const previewContainer = document.querySelector('#profile-image-preview');

    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', () => {
            const file = profilePhotoInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
                previewContainer.innerHTML = '';
            }
        });
    }

    // Profile form submission
    const editProfileForm = document.querySelector('#edit-profile-form');
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editProfileForm);

            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    document.querySelector('#edit-profile-modal').style.display = 'none';
                    updateProfilePhotos(result.profile_photo);
                    localStorage.setItem('profile_updated', Date.now());
                    location.reload();
                } else {
                    showError(result.message || 'Failed to update profile.');
                }
            } catch (error) {
                showError('Error updating profile: ' + error.message);
            }
        });
    }

    // Update profile photos
    function updateProfilePhotos(newSrc) {
        document.querySelectorAll('.profile-photo img').forEach(img => {
            if (!newSrc.includes('blank-profile-picture.webp')) {
                img.src = newSrc + '?v=' + Date.now();
            }
        });
    }

    // Listen for profile updates
    window.addEventListener('storage', (e) => {
        if (e.key === 'profile_updated') {
            fetch('update_profile.php', {
                method: 'GET'
            }).then(response => response.json()).then(data => {
                if (data.profile_photo) updateProfilePhotos(data.profile_photo);
            });
        }
    });

    // Bookmark functionality
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            const action = btn.classList.contains('bookmarked') ? 'remove' : 'add';

            if (!window.csrfToken) {
                alert('Session expired. Please refresh.');
                console.error('CSRF token missing');
                return;
            }

            console.log(`Bookmark action: ${action} for post ID: ${postId}`);
            try {
                const response = await fetch('../bookmark/bookmark_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        post_id: postId,
                        action: action,
                        csrf_token: window.csrfToken
                    })
                });

                console.log(`Bookmark response status: ${response.status} ${response.statusText}`);
                const text = await response.text();
                console.log('Bookmark raw response:', text);

                if (!response.ok) {
                    throw new Error(`Network error: ${response.status} ${response.statusText}`);
                }

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Bookmark JSON parse error:', e.message);
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    if (action === 'add') {
                        btn.classList.add('bookmarked');
                        btn.querySelector('i').classList.replace('bi-bookmark', 'bi-bookmark-fill');
                        alert('Post bookmarked!');
                    } else {
                        btn.classList.remove('bookmarked');
                        btn.querySelector('i').classList.replace('bi-bookmark-fill', 'bi-bookmark');
                        alert('Bookmark removed.');
                    }
                } else {
                    alert(result.message || 'Failed to update bookmark.');
                }
            } catch (error) {
                alert('Error updating bookmark: ' + error.message);
                console.error('Bookmark fetch error:', error);
            }
        });
    });


    // Like functionality
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            const action = btn.classList.contains('liked') ? 'unlike' : 'like';

            if (!window.csrfToken) {
                alert('CSRF token is missing. Please refresh the page.');
                return;
            }

            try {
                const response = await fetch('../like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken
                    },
                    body: JSON.stringify({ post_id: postId, action, csrf_token: window.csrfToken })
                });

                const result = await response.json();
                if (result.success) {
                    const likeCountElement = btn.querySelector('.like-count');
                    likeCountElement.textContent = result.like_count;
                    if (result.action === 'like') {
                        btn.classList.add('liked');
                        btn.querySelector('i').classList.add('bi-heart-fill');
                        btn.querySelector('i').classList.remove('bi-heart');
                        await fetch('../notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: postId, type: 'like', csrf_token: window.csrfToken })
                        });
                    } else {
                        btn.classList.remove('liked');
                        btn.querySelector('i').classList.add('bi-heart');
                        btn.querySelector('i').classList.remove('bi-heart-fill');
                    }
                } else {
                    alert(result.message || 'Failed to like/unlike post.');
                }
            } catch (error) {
                console.error('Like error:', error);
                alert('Error liking/unliking post: Network issue.');
            }
        });
    });

    // Comment functionality
    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const feed = btn.closest('.feed');
            const commentsSection = feed.querySelector('.comments-section');
            const postId = feed.dataset.postId;

            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                try {
                    const response = await fetch(`../comments.php?post_id=${postId}`);
                    const result = await response.json();
                    const commentsList = commentsSection.querySelector('.comments-list');

                    if (result.success) {
                        const comments = result.comments || [];
                        commentsList.innerHTML = comments.map(comment => `
                            <div class="comment" data-comment-id="${comment.id}">
                                <div class="profile-photo">
                                    <img src="${comment.profile_photo ? '../uploads/' + escapeHTML(comment.profile_photo) : '../blank-profile-picture.webp'}">
                                </div>
                                <div class="comment-content">
                                    <p><b>${escapeHTML(comment.username)}</b>: ${escapeHTML(comment.content)}</p>
                                    <small>${new Date(comment.created_at).toLocaleString()}</small>
                                    <div class="comment-actions">
                                        <span class="comment-like-btn ${comment.user_liked ? 'liked' : ''}" data-comment-id="${comment.id}">
                                            <i class="bi ${comment.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                            <span class="comment-like-count">${comment.like_count}</span>
                                        </span>
                                        <span class="comment-reply-btn" data-comment-id="${comment.id}">
                                            <i class="bi bi-reply"></i> Reply
                                        </span>
                                    </div>
                                    <div class="reply-form" style="display: none;">
                                        <form class="comment-form reply-form-inner">
                                            <input type="text" placeholder="Add a reply..." class="comment-input">
                                            <button type="submit" class="btn btn-primary">Reply</button>
                                        </form>
                                    </div>
                                    <div class="replies">
                                        ${comment.replies ? comment.replies.map(reply => `
                                            <div class="comment reply" data-comment-id="${reply.id}">
                                                <div class="profile-photo">
                                                    <img src="${reply.profile_photo ? '../uploads/' + escapeHTML(reply.profile_photo) : '../blank-profile-picture.webp'}">
                                                </div>
                                                <div class="comment-content">
                                                    <p><b>${escapeHTML(reply.username)}</b>: ${escapeHTML(reply.content)}</p>
                                                    <small>${new Date(reply.created_at).toLocaleString()}</small>
                                                    <div class="comment-actions">
                                                        <span class="comment-like-btn ${reply.user_liked ? 'liked' : ''}" data-comment-id="${reply.id}">
                                                            <i class="bi ${reply.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                                            <span class="comment-like-count">${reply.like_count}</span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('') : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        alert(result.message || 'Failed to load comments.');
                    }
                } catch (error) {
                    console.error('Comment load error:', error);
                    alert('Error loading comments: Network issue.');
                }
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });

    // Comment form submission
    document.querySelectorAll('.comments-section').forEach(section => {
        const form = section.querySelector('.comment-form:not(.reply-form-inner)');
        const errorMessage = document.createElement('div');
        errorMessage.className = 'error-message';
        if (form) form.prepend(errorMessage);

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const feed = form.closest('.feed');
                const postId = feed.dataset.postId;
                const content = form.querySelector('.comment-input').value;
                const commentsSection = form.closest('.comments-section');
                const commentCountSpan = feed.querySelector('.comment-btn .comment-count');

                if (!content.trim()) {
                    errorMessage.textContent = 'Comment cannot be empty.';
                    errorMessage.style.display = 'block';
                    return;
                }

                if (!window.csrfToken) {
                    errorMessage.textContent = 'CSRF token is missing. Please refresh the page.';
                    errorMessage.style.display = 'block';
                    return;
                }

                try {
                    const response = await fetch('../comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ post_id: postId, content, csrf_token: window.csrfToken })
                    });

                    const result = await response.json();
                    if (result.success) {
                        errorMessage.style.display = 'none';
                        form.reset();
                        if (commentCountSpan) {
                            commentCountSpan.textContent = result.comment_count;
                        }

                        await fetch('../notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: postId, type: 'comment', content, csrf_token: window.csrfToken })
                        });

                        const commentsList = commentsSection.querySelector('.comments-list');
                        const commentsResponse = await fetch(`../comments.php?post_id=${postId}`);
                        const commentsResult = await commentsResponse.json();
                        if (commentsResult.success) {
                            commentsList.innerHTML = commentsResult.comments.map(comment => `
                                <div class="comment" data-comment-id="${comment.id}">
                                    <div class="profile-photo">
                                        <img src="${comment.profile_photo ? '../uploads/' + escapeHTML(comment.profile_photo) : '../blank-profile-picture.webp'}">
                                    </div>
                                    <div class="comment-content">
                                        <p><b>${escapeHTML(comment.username)}</b>: ${escapeHTML(comment.content)}</p>
                                        <small>${new Date(comment.created_at).toLocaleString()}</small>
                                        <div class="comment-actions">
                                            <span class="comment-like-btn ${comment.user_liked ? 'liked' : ''}" data-comment-id="${comment.id}">
                                                <i class="bi ${comment.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                                <span class="comment-like-count">${comment.like_count}</span>
                                            </span>
                                            <span class="comment-reply-btn" data-comment-id="${comment.id}">
                                                <i class="bi bi-reply"></i> Reply
                                            </span>
                                        </div>
                                        <div class="reply-form" style="display: none;">
                                            <form class="comment-form reply-form-inner">
                                                <input type="text" placeholder="Add a reply..." class="comment-input">
                                                <button type="submit" class="btn btn-primary">Reply</button>
                                            </form>
                                        </div>
                                        <div class="replies">
                                            ${comment.replies ? comment.replies.map(reply => `
                                                <div class="comment reply" data-comment-id="${reply.id}">
                                                    <div class="profile-photo">
                                                        <img src="${reply.profile_photo ? '../uploads/' + escapeHTML(reply.profile_photo) : '../blank-profile-picture.webp'}">
                                                    </div>
                                                    <div class="comment-content">
                                                        <p><b>${escapeHTML(reply.username)}</b>: ${escapeHTML(reply.content)}</p>
                                                        <small>${new Date(reply.created_at).toLocaleString()}</small>
                                                        <div class="comment-actions">
                                                            <span class="comment-like-btn ${reply.user_liked ? 'liked' : ''}" data-comment-id="${reply.id}">
                                                                <i class="bi ${reply.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                                                <span class="comment-like-count">${reply.like_count}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            `).join('') : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        }
                    } else {
                        errorMessage.textContent = result.message || 'Failed to post comment.';
                        errorMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Comment error:', error);
                    errorMessage.textContent = 'Error posting comment: Network issue.';
                    errorMessage.style.display = 'block';
                }
            });
        }

        section.addEventListener('click', async (e) => {
            if (e.target.closest('.comment-like-btn')) {
                const likeBtn = e.target.closest('.comment-like-btn');
                const commentId = likeBtn.dataset.commentId;
                const action = likeBtn.classList.contains('liked') ? 'unlike' : 'like';

                if (!window.csrfToken) {
                    alert('CSRF token is missing. Please refresh the page.');
                    return;
                }

                try {
                    const response = await fetch('../comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ comment_id: commentId, action, csrf_token: window.csrfToken })
                    });

                    const result = await response.json();
                    if (result.success) {
                        const likeCountElement = likeBtn.querySelector('.comment-like-count');
                        likeCountElement.textContent = result.like_count;
                        if (result.action === 'like') {
                            likeBtn.classList.add('liked');
                            likeBtn.querySelector('i').classList.add('bi-heart-fill');
                            likeBtn.querySelector('i').classList.remove('bi-heart');
                        } else {
                            likeBtn.classList.remove('liked');
                            likeBtn.querySelector('i').classList.add('bi-heart');
                            likeBtn.querySelector('i').classList.remove('bi-heart-fill');
                        }
                    } else {
                        alert(result.message || 'Failed to like/unlike comment.');
                    }
                } catch (error) {
                    console.error('Comment like error:', error);
                    alert('Error liking/unliking comment: Network issue.');
                }
            }

            if (e.target.closest('.comment-reply-btn')) {
                const replyBtn = e.target.closest('.comment-reply-btn');
                const commentId = replyBtn.dataset.commentId;
                const replyForm = replyBtn.closest('.comment').querySelector('.reply-form');
                replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
            }

            if (e.target.closest('.reply-form-inner')) {
                const replyForm = e.target.closest('.reply-form-inner');
                e.preventDefault();
                const commentId = replyForm.closest('.comment').dataset.commentId;
                const content = replyForm.querySelector('.comment-input').value;
                const errorMessage = replyForm.querySelector('.error-message') || document.createElement('div');
                errorMessage.className = 'error-message';
                replyForm.prepend(errorMessage);

                if (!content.trim()) {
                    errorMessage.textContent = 'Reply cannot be empty.';
                    errorMessage.style.display = 'block';
                    return;
                }

                if (!window.csrfToken) {
                    errorMessage.textContent = 'CSRF token is missing. Please refresh the page.';
                    errorMessage.style.display = 'block';
                    return;
                }

                try {
                    const response = await fetch('../comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ post_id: section.closest('.feed').dataset.postId, content, parent_comment_id: commentId, csrf_token: window.csrfToken })
                    });

                    const result = await response.json();
                    if (result.success) {
                        errorMessage.style.display = 'none';
                        replyForm.reset();
                        replyForm.style.display = 'none';

                        await fetch('../notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: section.closest('.feed').dataset.postId, type: 'comment', content, csrf_token: window.csrfToken })
                        });

                        const commentsList = section.querySelector('.comments-list');
                        const commentsResponse = await fetch(`../comments.php?post_id=${section.closest('.feed').dataset.postId}`);
                        const commentsResult = await commentsResponse.json();
                        if (commentsResult.success) {
                            commentsList.innerHTML = commentsResult.comments.map(comment => `
                                <div class="comment" data-comment-id="${comment.id}">
                                    <div class="profile-photo">
                                        <img src="${comment.profile_photo ? '../uploads/' + escapeHTML(comment.profile_photo) : '../blank-profile-picture.webp'}">
                                    </div>
                                    <div class="comment-content">
                                        <p><b>${escapeHTML(comment.username)}</b>: ${escapeHTML(comment.content)}</p>
                                        <small>${new Date(comment.created_at).toLocaleString()}</small>
                                        <div class="comment-actions">
                                            <span class="comment-like-btn ${comment.user_liked ? 'liked' : ''}" data-comment-id="${comment.id}">
                                                <i class="bi ${comment.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                                <span class="comment-like-count">${comment.like_count}</span>
                                            </span>
                                            <span class="comment-reply-btn" data-comment-id="${comment.id}">
                                                <i class="bi bi-reply"></i> Reply
                                            </span>
                                        </div>
                                        <div class="reply-form" style="display: none;">
                                            <form class="comment-form reply-form-inner">
                                                <input type="text" placeholder="Add a reply..." class="comment-input">
                                                <button type="submit" class="btn btn-primary">Reply</button>
                                            </form>
                                        </div>
                                        <div class="replies">
                                            ${comment.replies ? comment.replies.map(reply => `
                                                <div class="comment reply" data-comment-id="${reply.id}">
                                                    <div class="profile-photo">
                                                        <img src="${reply.profile_photo ? '../uploads/' + escapeHTML(reply.profile_photo) : '../blank-profile-picture.webp'}">
                                                    </div>
                                                    <div class="comment-content">
                                                        <p><b>${escapeHTML(reply.username)}</b>: ${escapeHTML(reply.content)}</p>
                                                        <small>${new Date(reply.created_at).toLocaleString()}</small>
                                                        <div class="comment-actions">
                                                            <span class="comment-like-btn ${reply.user_liked ? 'liked' : ''}" data-comment-id="${reply.id}">
                                                                <i class="bi ${reply.user_liked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                                                <span class="comment-like-count">${reply.like_count}</span>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            `).join('') : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        }
                    } else {
                        errorMessage.textContent = result.message || 'Failed to post reply.';
                        errorMessage.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Reply error:', error);
                    errorMessage.textContent = 'Error posting reply: Network issue.';
                    errorMessage.style.display = 'block';
                }
            }
        });
    });

    // Share functionality
    document.querySelectorAll('.share-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.closest('.feed').dataset.postId;
            const shareUrl = `${window.location.origin}${window.location.pathname.replace(/profile\/[^/]*$/, '')}post_display.php?id=${postId}`;
            
            let popup = btn.querySelector('.share-popup');
            if (!popup) {
                popup = document.createElement('div');
                popup.className = 'share-popup';
                popup.innerHTML = `
                    <input type="text" value="${shareUrl}" readonly>
                    <button onclick="navigator.clipboard.writeText('${shareUrl}'); this.textContent='Copied!'">Copy</button>
                `;
                btn.appendChild(popup);
            }
            
            popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
        });
    });

    // Delete post functionality
    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            if (confirm('Are you sure you want to delete this post?')) {
                if (!window.csrfToken) {
                    alert('CSRF token is missing. Please refresh the page.');
                    return;
                }

                try {
                    const response = await fetch('delete_post.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ post_id: postId, csrf_token: window.csrfToken })
                    });

                    const text = await response.text();
                    try {
                        const result = JSON.parse(text);
                        if (result.success) {
                            btn.closest('.feed').remove();
                        } else {
                            alert(result.message || 'Failed to delete post.');
                        }
                    } catch (jsonError) {
                        console.error('Raw response from delete_post.php:', text);
                        alert('Error deleting post: Invalid server response.');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('Error deleting post: Network issue.');
                }
            }
        });
    });

    // Error display
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.position = 'fixed';
        errorDiv.style.top = '20px';
        errorDiv.style.right = '20px';
        errorDiv.style.background = '#ff4d4d';
        errorDiv.style.color = '#fff';
        errorDiv.style.padding = '10px';
        errorDiv.style.borderRadius = '5px';
        document.body.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
});
