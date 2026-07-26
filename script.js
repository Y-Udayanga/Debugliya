document.addEventListener('DOMContentLoaded', () => {
    // Initialize variables
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const themeToggle = document.querySelector('#theme-toggle');
    const footer = document.querySelector('.footer');
    const themeMenuItem = document.querySelector('#theme');
    const themeModal = document.querySelector('.customize-theme');
    const escapeHTML = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    // Hamburger menu toggle
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }

    // Close mobile menu on nav link click
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            if (hamburger && navLinks) {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            }
        });
    });

    // Theme toggle functionality
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDarkMode = document.body.classList.contains('dark-mode') || document.body.classList.contains('dark-theme');
            if (isDarkMode) {
                changeBG('95%', '100%', '17%');
            } else {
                changeBG('20%', '28%', '95%');
            }
        });

        // Load saved theme preference
        const savedTheme = localStorage.getItem('theme');
        const savedLight = localStorage.getItem('lightColorLightness');
        const savedWhite = localStorage.getItem('whiteColorLightness');
        const savedDark = localStorage.getItem('darkColorLightness');

        if (savedTheme === 'dark' || (savedWhite && savedWhite !== '100%')) {
            changeBG(savedLight || '20%', savedWhite || '28%', savedDark || '95%');
        } else {
            changeBG(savedLight || '95%', savedWhite || '100%', savedDark || '17%');
        }
    }


    // Theme modal with footer hide orr show
    if (themeMenuItem && footer) {
        themeMenuItem.addEventListener('click', (e) => {
            e.preventDefault();
            if (themeModal) {
                themeModal.style.display = 'grid';
                footer.style.display = 'none';
            }
        });
    }

    if (themeModal) {
        themeModal.addEventListener('click', (e) => {
            if (e.target === themeModal || e.target.classList.contains('close-modal')) {
                themeModal.style.display = 'none';
                if (footer) footer.style.display = 'block';
            }
        });
    }

    // Post modals functionality..hga

    const postModal = document.querySelector('.post-modal');
    const postForm = document.getElementById('post-form');
    const openPostModalTriggers = document.querySelectorAll('[for="create-post-modal"], #create-post');
    const closePostModal = document.querySelector('.post-modal .close-modal');
    const emojiBtn = document.querySelector('.emoji-btn');
    const emojiPicker = document.querySelector('.emoji-picker');
    const textarea = postForm ? postForm.querySelector('textarea') : null;
    const imageUpload = document.getElementById('image-upload');
    const imagePreview = document.querySelector('.image-preview');
    const previewContainer = document.getElementById('image-preview-container');

    const openPostModal = () => {
        if (postModal) postModal.style.display = 'grid';
    };

    openPostModalTriggers.forEach(trigger => {
        trigger.addEventListener('click', openPostModal);
    });

    if (closePostModal) {
        closePostModal.addEventListener('click', () => {
            if (postModal) postModal.style.display = 'none';
            if (imagePreview) imagePreview.style.display = 'none';
            if (previewContainer) previewContainer.innerHTML = '';
        });
    }

    if (postModal) {
        window.addEventListener('click', (e) => {
            if (e.target === postModal) {
                postModal.style.display = 'none';
                if (imagePreview) imagePreview.style.display = 'none';
                if (previewContainer) previewContainer.innerHTML = '';
            }
        });
    }

    // Image preview for posts

    if (imageUpload) {
        imageUpload.addEventListener('change', (e) => {
            const files = e.target.files;
            if (previewContainer) previewContainer.innerHTML = '';
            if (files.length > 0) {
                if (imagePreview) imagePreview.style.display = 'block';
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.classList.add('post-image', files.length === 1 ? 'single-image' : 'multi-image');
                        if (previewContainer) previewContainer.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                if (imagePreview) imagePreview.style.display = 'none';
            }
        });
    }

    // Emoji picker functionality
    if (emojiBtn && emojiPicker) {
        emojiBtn.addEventListener('click', () => {
            emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'flex' : 'none';
        });
    }

    document.querySelectorAll('.emoji').forEach(emoji => {
        emoji.addEventListener('click', () => {
            if (textarea) textarea.value += emoji.dataset.emoji;
            if (emojiPicker) emojiPicker.style.display = 'none';
        });
    });

    // Post form submissions
    if (postForm) {
        postForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(postForm);

            try {
                const response = await fetch('post.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                try {
                    const result = JSON.parse(text);
                    if (result.success) {
                        postForm.reset();
                        if (imagePreview) imagePreview.style.display = 'none';
                        if (previewContainer) previewContainer.innerHTML = '';
                        if (postModal) postModal.style.display = 'none';
                        location.reload();
                    } else {
                        alert(result.message || 'Failed to create post.');
                    }
                } catch (jsonError) {
                    console.error('Raw response from post.php:', text);
                    alert('Error creating post: Invalid server response.');
                }
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Error creating post: Network issue.');
            }
        });
    }

    // Image modal for enlarged photos
    const imageModal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    const closeImageModal = document.getElementById('close-image-modal');

    function openImageModal(src) {
        if (imageModal && modalImage) {
            modalImage.src = src;
            imageModal.style.display = 'flex';
        }
    }

    if (closeImageModal) {
        closeImageModal.addEventListener('click', () => {
            if (imageModal) imageModal.style.display = 'none';
        });
    }

    if (imageModal) {
        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) {
                imageModal.style.display = 'none';
            }
        });
    }

    function initializePostImages() {
        document.querySelectorAll('.photo-gallery img').forEach(img => {
            img.classList.add('post-image');
            img.style.cursor = 'pointer';
            img.removeEventListener('click', openImageModal); // Prevent duplicate listeners
            img.addEventListener('click', () => openImageModal(img.src));
        });
    }

    // Initialize images and observe DOM changes
    initializePostImages();
    const observer = new MutationObserver(initializePostImages);
    observer.observe(document.body, { childList: true, subtree: true });

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
                const response = await fetch('bookmark/bookmark_action.php', {
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
                const response = await fetch('like.php', {
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
                        await fetch('notification/create_notification.php', {
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
                    const response = await fetch(`comments.php?post_id=${postId}`);
                    const result = await response.json();
                    const commentsList = commentsSection.querySelector('.comments-list');

                    if (result.success) {
                        const comments = result.comments || [];
                        commentsList.innerHTML = comments.map(comment => `
                            <div class="comment" data-comment-id="${comment.id}">
                                <div class="profile-photo">
                                    <img src="${comment.profile_photo ? 'uploads/' + escapeHTML(comment.profile_photo) : 'blank-profile-picture.webp'}">
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
                                                    <img src="${reply.profile_photo ? 'uploads/' + escapeHTML(reply.profile_photo) : 'blank-profile-picture.webp'}">
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
                    const response = await fetch('comment.php', {
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

                        await fetch('notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: postId, type: 'comment', content, csrf_token: window.csrfToken })
                        });

                        const commentsList = commentsSection.querySelector('.comments-list');
                        const commentsResponse = await fetch(`comments.php?post_id=${postId}`);
                        const commentsResult = await commentsResponse.json();
                        if (commentsResult.success) {
                            commentsList.innerHTML = commentsResult.comments.map(comment => `
                                <div class="comment" data-comment-id="${comment.id}">
                                    <div class="profile-photo">
                                        <img src="${comment.profile_photo ? 'uploads/' + escapeHTML(comment.profile_photo) : 'blank-profile-picture.webp'}">
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
                                                        <img src="${reply.profile_photo ? 'uploads/' + escapeHTML(reply.profile_photo) : 'blank-profile-picture.webp'}">
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
                    const response = await fetch('comment.php', {
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
                    const response = await fetch('comment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ post_id: section.closest('.feed').dataset.postId, content, parent_comment_id: commentId, csrf_token: window.csrfToken })
                    });

                    const result = await response.json();
                    if (result.success) {
                        errorMessage.style.display = 'none';
                        replyForm.reset();
                        replyForm.style.display = 'none';

                        await fetch('notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: section.closest('.feed').dataset.postId, type: 'comment', content, csrf_token: window.csrfToken })
                        });

                        const commentsList = section.querySelector('.comments-list');
                        const commentsResponse = await fetch(`comments.php?post_id=${section.closest('.feed').dataset.postId}`);
                        const commentsResult = await commentsResponse.json();
                        if (commentsResult.success) {
                            commentsList.innerHTML = commentsResult.comments.map(comment => `
                                <div class="comment" data-comment-id="${comment.id}">
                                    <div class="profile-photo">
                                        <img src="${comment.profile_photo ? 'uploads/' + escapeHTML(comment.profile_photo) : 'blank-profile-picture.webp'}">
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
                                                        <img src="${reply.profile_photo ? 'uploads/' + escapeHTML(reply.profile_photo) : 'blank-profile-picture.webp'}">
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
            const shareUrl = `${window.location.origin}${window.location.pathname.replace(/[^/]*$/, '')}post_display.php?id=${postId}`;
            
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

    // Sidebar and theme customization
    const menuItems = document.querySelectorAll('.menu-item');
    const fontSizes = document.querySelectorAll('.choose-size span');
    const root = document.documentElement;
    const colorPalette = document.querySelectorAll('.choose-color span');
    const bgOptions = document.querySelectorAll('.choose-bg > div');
    const joinButtons = document.querySelectorAll('.btn-join');

    const changeActiveItem = () => {
        menuItems.forEach(item => {
            item.classList.remove('active');
        });
    };

    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            changeActiveItem();
            item.classList.add('active');
            if (item.id !== 'Notifications') {
                const notificationsPopup = document.querySelector('.notifications-popup');
                if (notificationsPopup) notificationsPopup.style.display = 'none';
            } else {
                const notificationsPopup = document.querySelector('.notifications-popup');
                const notificationCount = document.querySelector('#Notifications .notification-count');
                if (notificationsPopup) notificationsPopup.style.display = 'block';
                if (notificationCount) notificationCount.style.display = 'none';
            }
        });
    });

    const removeSizeSelector = () => {
        fontSizes.forEach(size => {
            size.classList.remove('active');
        });
    };

    fontSizes.forEach(size => {
        size.addEventListener('click', () => {
            removeSizeSelector();
            size.classList.add('active');
            let fontSize;
            if (size.classList.contains('font-size-1')) fontSize = '12px';
            else if (size.classList.contains('font-size-2')) fontSize = '14px';
            else if (size.classList.contains('font-size-3')) fontSize = '16px';
            else if (size.classList.contains('font-size-4')) fontSize = '18px';
            else if (size.classList.contains('font-size-5')) fontSize = '20px';
            root.style.fontSize = fontSize;
            localStorage.setItem('fontSize', fontSize);
        });
    });

    // Load saved font size
    const savedFontSize = localStorage.getItem('fontSize');
    if (savedFontSize) {
        root.style.fontSize = savedFontSize;
        fontSizes.forEach(size => {
            size.classList.remove('active');
            if (savedFontSize === '12px' && size.classList.contains('font-size-1')) size.classList.add('active');
            else if (savedFontSize === '14px' && size.classList.contains('font-size-2')) size.classList.add('active');
            else if (savedFontSize === '16px' && size.classList.contains('font-size-3')) size.classList.add('active');
            else if (savedFontSize === '18px' && size.classList.contains('font-size-4')) size.classList.add('active');
            else if (savedFontSize === '20px' && size.classList.contains('font-size-5')) size.classList.add('active');
        });
    }

    const changeActiveColorClass = () => {
        colorPalette.forEach(color => {
            color.classList.remove('active');
        });
    };

    colorPalette.forEach(color => {
        color.addEventListener('click', () => {
            changeActiveColorClass();
            color.classList.add('active');
            let hue;
            if (color.classList.contains('color-1')) hue = 252;
            else if (color.classList.contains('color-2')) hue = 52;
            else if (color.classList.contains('color-3')) hue = 352;
            else if (color.classList.contains('color-4')) hue = 152;
            else if (color.classList.contains('color-5')) hue = 202;
            root.style.setProperty('--primary-color-hue', hue);
            localStorage.setItem('primaryColorHue', hue);
        });
    });

    // Load saved color
    const savedHue = localStorage.getItem('primaryColorHue');
    if (savedHue) {
        root.style.setProperty('--primary-color-hue', savedHue);
        colorPalette.forEach(color => {
            color.classList.remove('active');
            if (savedHue == 252 && color.classList.contains('color-1')) color.classList.add('active');
            else if (savedHue == 52 && color.classList.contains('color-2')) color.classList.add('active');
            else if (savedHue == 352 && color.classList.contains('color-3')) color.classList.add('active');
            else if (savedHue == 152 && color.classList.contains('color-4')) color.classList.add('active');
            else if (savedHue == 202 && color.classList.contains('color-5')) color.classList.add('active');
        });
    }

    function changeBG(light, white, dark) {
        root.style.setProperty('--light-color-lightness', light);
        root.style.setProperty('--white-color-lightness', white);
        root.style.setProperty('--dark-color-lightness', dark);

        const isDark = (white === '28%' || white === '17%' || light === '20%' || light === '10%');
        if (isDark) {
            document.body.classList.add('dark-mode');
            document.body.classList.add('dark-theme');
            localStorage.setItem('theme', 'dark');
            if (themeToggle) themeToggle.innerHTML = '<i class="bi bi-sun"></i>';
        } else {
            document.body.classList.remove('dark-mode');
            document.body.classList.remove('dark-theme');
            localStorage.setItem('theme', 'light');
            if (themeToggle) themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i>';
        }
        localStorage.setItem('lightColorLightness', light);
        localStorage.setItem('whiteColorLightness', white);
        localStorage.setItem('darkColorLightness', dark);

        if (bgOptions && bgOptions.length > 0) {
            bgOptions.forEach(bg => {
                bg.classList.remove('active');
                if (light === '95%' && bg.classList.contains('bg-1')) bg.classList.add('active');
                else if (light === '20%' && bg.classList.contains('bg-2')) bg.classList.add('active');
                else if (light === '10%' && bg.classList.contains('bg-3')) bg.classList.add('active');
            });
        }
    }

    bgOptions.forEach(bg => {
        bg.addEventListener('click', () => {
            bgOptions.forEach(option => option.classList.remove('active'));
            bg.classList.add('active');
            let light, white, dark;
            if (bg.classList.contains('bg-1')) {
                light = '95%';
                white = '100%';
                dark = '17%';
            } else if (bg.classList.contains('bg-2')) {
                light = '20%';
                white = '28%';
                dark = '95%';
            } else if (bg.classList.contains('bg-3')) {
                light = '10%';
                white = '17%';
                dark = '95%';
            }
            changeBG(light, white, dark);
        });
    });

    // Load saved background
    const savedLight = localStorage.getItem('lightColorLightness');
    const savedWhite = localStorage.getItem('whiteColorLightness');
    const savedDark = localStorage.getItem('darkColorLightness');
    if (savedLight && savedWhite && savedDark) {
        changeBG(savedLight, savedWhite, savedDark);
    }

    joinButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.textContent = button.textContent.trim() === 'Join' ? 'Joined' : 'Join';
            button.classList.toggle('joined');
        });
    });

    // Forum Category Pills Filtering
    const categoryPills = document.querySelectorAll('.category-pill');
    const feedPosts = document.querySelectorAll('#forum-feeds-list .feed');

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            const targetCategory = pill.dataset.category ? pill.dataset.category.toLowerCase().trim() : 'all';

            feedPosts.forEach(post => {
                const postCategory = post.dataset.category ? post.dataset.category.toLowerCase().trim() : '';
                if (targetCategory === 'all' || postCategory.includes(targetCategory) || targetCategory.includes(postCategory)) {
                    post.style.display = 'block';
                } else {
                    post.style.display = 'none';
                }
            });
        });
    });

    // Feed Tabs Sorting
    const feedTabBtns = document.querySelectorAll('.feed-tab-btn');
    feedTabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            feedTabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filterType = btn.dataset.filter;
            const feedsContainer = document.querySelector('#forum-feeds-list');
            if (!feedsContainer) return;

            const postsArray = Array.from(feedPosts);
            if (filterType === 'liked') {
                postsArray.sort((a, b) => parseInt(b.dataset.likes || 0) - parseInt(a.dataset.likes || 0));
            } else if (filterType === 'trending') {
                postsArray.sort((a, b) => parseInt(b.dataset.likes || 0) - parseInt(a.dataset.likes || 0));
            } else {
                // Default Latest Feed
                postsArray.sort((a, b) => parseInt(b.dataset.postId || 0) - parseInt(a.dataset.postId || 0));
            }

            postsArray.forEach(p => feedsContainer.appendChild(p));
        });
    });
});

// Global Helpers
window.filterForumPosts = () => {
    const query = (document.querySelector('#forum-feed-search')?.value || '').toLowerCase().trim();
    const posts = document.querySelectorAll('#forum-feeds-list .feed');
    posts.forEach(post => {
        const text = post.textContent.toLowerCase();
        if (text.includes(query)) {
            post.style.display = 'block';
        } else {
        };

        // Initialize Help Modal
        initHelpModal();
    });

    // Help Center Modal Handler
    function initHelpModal() {
        let helpModal = document.getElementById('help-modal');
        if (!helpModal) {
            helpModal = document.createElement('div');
            helpModal.id = 'help-modal';
            helpModal.className = 'modal help-modal-wrapper';
            helpModal.style.display = 'none';
            helpModal.innerHTML = `
            <div class="modal-content help-modal-content">
                <div class="modal-header">
                    <h2><i class="bi bi-question-circle-fill"></i> Help & Support Center</h2>
                    <span class="close-modal" onclick="closeHelpModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="help-search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="help-search-input" placeholder="Search FAQs or help topics..." oninput="filterFaqs()">
                    </div>

                    <div class="faq-accordion-list">
                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><i class="bi bi-code-square"></i> How do I post code snippets on Debuglia?</span>
                                <i class="bi bi-chevron-down faq-chevron"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Click the <strong>Post</strong> button on the Forum or Home page, choose a category, attach photos or code, and publish your discussion.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><i class="bi bi-person-gear"></i> How do I update my profile avatar and skills?</span>
                                <i class="bi bi-chevron-down faq-chevron"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Go to your Profile page, click <strong>Edit Profile</strong>, select a new avatar image, add your skills, and click <strong>Save Profile</strong>.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><i class="bi bi-bookmark-star"></i> How do Bookmarks work?</span>
                                <i class="bi bi-chevron-down faq-chevron"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Click the bookmark icon on any forum post to save it for quick reference. Access all saved items under your Bookmarks tab.</p>
                            </div>
                        </div>

                        <div class="faq-item">
                            <button class="faq-question" onclick="toggleFaq(this)">
                                <span><i class="bi bi-shield-check"></i> Is my account secure on Debuglia?</span>
                                <i class="bi bi-chevron-down faq-chevron"></i>
                            </button>
                            <div class="faq-answer">
                                <p>Yes, all passwords are encrypted with bcrypt, sessions are signed with secure tokens, and database calls use parameterized queries.</p>
                            </div>
                        </div>
                    </div>

                    <div class="help-contact-box">
                        <p><i class="bi bi-headset"></i> Need further assistance?</p>
                        <a href="mailto:support@debuglia.com" class="btn btn-primary btn-sm"><i class="bi bi-envelope-fill"></i> Contact Support Team</a>
                    </div>
                </div>
            </div>
        `;
            document.body.appendChild(helpModal);
        }

        document.querySelectorAll('.help-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                helpModal.style.display = 'flex';
            });
        });

        helpModal.addEventListener('click', (e) => {
            if (e.target === helpModal) {
                helpModal.style.display = 'none';
            }
        });
    }

    window.closeHelpModal = () => {
        const modal = document.getElementById('help-modal');
        if (modal) modal.style.display = 'none';
    };

    window.toggleFaq = (btn) => {
        const item = btn.closest('.faq-item');
        if (item) {
            item.classList.toggle('open');
        }
    };

    window.filterFaqs = () => {
        const input = document.getElementById('help-search-input');
        if (!input) return;
        const q = input.value.toLowerCase().trim();
        document.querySelectorAll('.faq-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(q) ? 'block' : 'none';
        });
    };

    // Global Helpers
    window.filterForumPosts = () => {
        const query = (document.querySelector('#forum-feed-search')?.value || '').toLowerCase().trim();
        const posts = document.querySelectorAll('#forum-feeds-list .feed');
        posts.forEach(post => {
            const text = post.textContent.toLowerCase();
            if (text.includes(query)) {
                post.style.display = 'block';
            } else {
                post.style.display = 'none';
            }
        });
    };

    window.toggleJoinCommunity = (btn) => {
        if (btn) {
            const isJoined = btn.classList.contains('joined');
            if (isJoined) {
                btn.textContent = 'Join';
                btn.classList.remove('joined');
            } else {
                btn.textContent = 'Joined';
                btn.classList.add('joined');
            }
        }
    };
