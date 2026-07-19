document.addEventListener('DOMContentLoaded', () => {
    const categoryLinks = document.querySelectorAll('.category-link');
    const postsContainer = document.getElementById('category-posts');
    const searchInput = document.getElementById('category-search');

   // Utility function to sanitize HTML
function sanitizeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Fetch posts for a category
async function fetchPosts(categoryId, link) {
    try {
        postsContainer.innerHTML = '<h2>Loading...</h2>';
        const response = await fetch(`../posts_by_category.php?category_id=${encodeURIComponent(categoryId)}&csrf_token=${encodeURIComponent(window.csrfToken)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const result = await response.json();

        categoryLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        if (result.success) {
            const posts = Array.isArray(result.posts) ? result.posts : [];
            if (posts.length === 0) {
                postsContainer.innerHTML = '<h2>No posts in this category.</h2>';
            } else {
                postsContainer.innerHTML = posts.map(post => {
                    // Validate and sanitize data
                    const username = sanitizeHTML(post.username || 'Unknown');
                    const content = sanitizeHTML(post.content || '');
                    const categoryName = sanitizeHTML(post.category_name || 'Uncategorized');
                    const profilePhoto = post.profile_photo ? `../uploads/${sanitizeHTML(post.profile_photo)}` : '../blank-profile-picture.webp';
                    const createdAt = post.created_at ? new Date(post.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric' }) : 'Unknown date';
                    const images = Array.isArray(post.images) ? post.images : [];
                    const likeCount = Number.isInteger(post.like_count) ? post.like_count : 0;
                    const commentCount = Number.isInteger(post.comment_count) ? post.comment_count : 0;
                    const userLiked = !!post.user_liked;

                    return `
                        <div class="feed" data-post-id="${post.id}">
                            <div class="head">
                                <div class="user">
                                    <div class="profile-photo">
                                        <img src="${profilePhoto}" alt="Profile Photo">
                                    </div>
                                    <div class="info">
                                        <h3>${username}</h3>
                                        <small>${createdAt}</small>
                                    </div>
                                </div>
                                ${post.user_id === window.userId ? `
                                    <span class="delete-post-btn" data-post-id="${post.id}">
                                        <i class="bi bi-trash"></i>
                                    </span>
                                ` : ''}
                            </div>
                            <div class="category">
                                <span>${categoryName}</span>
                            </div>
                            <div class="post-content">
                                <p>${content}</p>
                                ${images.length > 0 ? `
                                    <div class="post-images">
                                        ${images.map(image => `
                                            <img src="../uploads/${sanitizeHTML(image)}" alt="Post Image">
                                        `).join('')}
                                    </div>
                                ` : ''}
                            </div>
                            <div class="action-buttons">
                                <div class="interaction-buttons">
                                    <span class="like-btn ${userLiked ? 'liked' : ''}" data-post-id="${post.id}">
                                        <i class="bi ${userLiked ? 'bi-heart-fill' : 'bi-heart'}"></i>
                                        <span class="like-count">${likeCount}</span>
                                    </span>
                                    <span class="comment-btn" data-post-id="${post.id}">
                                        <i class="bi bi-chat"></i>
                                        <span class="comment-count">${commentCount}</span>
                                    </span>
                                    <span class="share-btn" data-post-id="${post.id}">
                                        <i class="bi bi-share"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="comments-section" data-post-id="${post.id}" style="display: none;">
                                <form class="comment-form" data-post-id="${post.id}">
                                    <input type="text" class="comment-input" placeholder="Add a comment..." required>
                                    <button type="submit" class="btn btn-primary">Post</button>
                                </form>
                                <div class="comments-list"></div>
                            </div>
                        </div>
                    `;
                }).join('');
                attachEventListeners();
            }
        } else {
            postsContainer.innerHTML = '<h2>Error loading posts: ' + sanitizeHTML(result.message || 'Unknown error') + '</h2>';
        }
    } catch (error) {
        postsContainer.innerHTML = '<h2>Error loading posts: ' + sanitizeHTML(error.message) + '</h2>';
    }
}

    // Attach event listeners to dynamically loaded elements
    function attachEventListeners() {
        // Like button
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const postId = btn.dataset.postId;
                try {
                    const response = await fetch('../like_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&csrf_token=${window.csrfToken}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        const likeCount = btn.querySelector('.like-count');
                        likeCount.textContent = result.like_count;
                        btn.classList.toggle('liked');
                        btn.querySelector('i').classList.toggle('bi-heart');
                        btn.querySelector('i').classList.toggle('bi-heart-fill');
                    } else {
                        alert('Error liking post: ' + result.message);
                    }
                } catch (error) {
                    alert('Error liking post: ' + error.message);
                }
            });
        });

        // Comment button (toggle comments)
        document.querySelectorAll('.comment-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const postId = btn.dataset.postId;
                const commentsSection = document.querySelector(`.comments-section[data-post-id="${postId}"]`);
                commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
                if (commentsSection.style.display === 'block') {
                    fetchComments(postId);
                }
            });
        });

        // Comment form submission
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const postId = form.dataset.postId;
                const input = form.querySelector('.comment-input');
                const content = input.value.trim();
                if (!content) return;

                try {
                    const response = await fetch('../add_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&content=${encodeURIComponent(content)}&csrf_token=${window.csrfToken}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        input.value = '';
                        fetchComments(postId);
                        const commentCount = document.querySelector(`.comment-btn[data-post-id="${postId}"] .comment-count`);
                        commentCount.textContent = parseInt(commentCount.textContent) + 1;
                    } else {
                        alert('Error adding comment: ' + result.message);
                    }
                } catch (error) {
                    alert('Error adding comment: ' + error.message);
                }
            });
        });

        // Delete post
        document.querySelectorAll('.delete-post-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to delete this post?')) return;
                const postId = btn.dataset.postId;
                try {
                    const response = await fetch('../delete_post.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `post_id=${postId}&csrf_token=${window.csrfToken}`
                    });
                    const result = await response.json();
                    if (result.success) {
                        document.querySelector(`.feed[data-post-id="${postId}"]`).remove();
                    } else {
                        alert('Error deleting post: ' + result.message);
                    }
                } catch (error) {
                    alert('Error deleting post: ' + error.message);
                }
            });
        });

        // Share button
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const postId = btn.dataset.postId;
                const url = `${window.location.origin}${window.location.pathname.replace(/trending_topic\/[^/]*$/, '')}post_display.php?id=${postId}`;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Post URL copied to clipboard!');
                }).catch(err => {
                    alert('Failed to copy URL: ' + err.message);
                });
            });
        });
    }

    // Fetch comments for a post
    async function fetchComments(postId) {
        try {
            const response = await fetch(`../get_comments.php?post_id=${postId}`);
            const result = await response.json();
            const commentsList = document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`);
            if (result.success) {
                commentsList.innerHTML = result.comments.map(comment => `
                    <div class="comment">
                        <div class="comment-user">
                            <strong>${comment.username}</strong>
                            <small>${new Date(comment.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric' })}</small>
                        </div>
                        <p>${comment.content}</p>
                    </div>
                `).join('');
            } else {
                commentsList.innerHTML = '<p>Error loading comments: ' + result.message + '</p>';
            }
        } catch (error) {
            document.querySelector(`.comments-section[data-post-id="${postId}"] .comments-list`).innerHTML = '<p>Error loading comments: ' + error.message + '</p>';
        }
    }

    // Category link click
    categoryLinks.forEach(link => {
        link.addEventListener('click', async (e) => {
            e.preventDefault();
            const categoryId = link.dataset.categoryId;
            await fetchPosts(categoryId, link);
        });
    });

    // Search functionality
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        document.querySelectorAll('.category li').forEach(li => {
            const categoryName = li.querySelector('a').textContent.toLowerCase();
            li.style.display = categoryName.includes(query) ? 'block' : 'none';
        });
    });
});
