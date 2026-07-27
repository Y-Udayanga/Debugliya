document.addEventListener('DOMContentLoaded', () => {
    const topicSearchInput = document.getElementById('topic-search-input');
    const clearTopicSearchBtn = document.getElementById('clear-topic-search');
    const categorySearchInput = document.getElementById('category-search');
    const categoryItemLinks = document.querySelectorAll('.category-item-link');
    const feeds = document.querySelectorAll('#category-posts .feed');
    const emptyCard = document.querySelector('.topic-empty-card');

    // Utility function to sanitize HTML
    function sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Filter Posts by Search Input
    function filterPosts() {
        const query = (topicSearchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        feeds.forEach(feed => {
            const username = feed.querySelector('.info h3')?.textContent.toLowerCase() || '';
            const content = feed.querySelector('.post-content p')?.textContent.toLowerCase() || '';
            const categoryBadge = feed.querySelector('.category-pill-badge')?.textContent.toLowerCase() || '';

            const matches = !query || username.includes(query) || content.includes(query) || categoryBadge.includes(query);

            if (matches) {
                feed.style.display = 'block';
                visibleCount++;
            } else {
                feed.style.display = 'none';
            }
        });

        if (emptyCard) {
            emptyCard.style.display = visibleCount === 0 ? 'flex' : 'none';
        }

        if (clearTopicSearchBtn) {
            clearTopicSearchBtn.style.display = query.length > 0 ? 'inline-flex' : 'none';
        }
    }

    if (topicSearchInput) {
        topicSearchInput.addEventListener('input', filterPosts);
    }

    if (clearTopicSearchBtn) {
        clearTopicSearchBtn.addEventListener('click', () => {
            if (topicSearchInput) topicSearchInput.value = '';
            filterPosts();
        });
    }

    // Filter Categories in Right Sidebar
    if (categorySearchInput && categoryItemLinks) {
        categorySearchInput.addEventListener('input', () => {
            const query = categorySearchInput.value.toLowerCase().trim();
            categoryItemLinks.forEach(link => {
                const text = link.textContent.toLowerCase();
                link.style.display = text.includes(query) ? 'flex' : 'none';
            });
        });
    }

    // Like Button Interaction
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            if (!postId) return;
            try {
                const action = btn.classList.contains('liked') ? 'unlike' : 'like';
                const response = await fetch('../like.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId, action, csrf_token: window.csrfToken })
                });
                const result = await response.json();
                if (result.success) {
                    const likeCount = btn.querySelector('.like-count');
                    if (likeCount) likeCount.textContent = result.like_count;
                    btn.classList.toggle('liked');
                    const icon = btn.querySelector('i');
                    if (icon) {
                        icon.className = btn.classList.contains('liked') ? 'bi bi-heart-fill' : 'bi bi-heart';
                    }
                }
            } catch (error) {
                console.error('Error liking post:', error);
            }
        });
    });

    // Comment Toggle & Load
    document.querySelectorAll('.comment-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const postId = btn.dataset.postId;
            const commentsSection = document.getElementById(`comments-${postId}`);
            if (commentsSection) {
                const isHidden = commentsSection.style.display === 'none' || !commentsSection.style.display;
                commentsSection.style.display = isHidden ? 'block' : 'none';
                if (isHidden) {
                    fetchComments(postId);
                }
            }
        });
    });

    // Fetch comments for a post
    async function fetchComments(postId) {
        const commentsSection = document.getElementById(`comments-${postId}`);
        if (!commentsSection) return;
        try {
            const response = await fetch(`../comments.php?post_id=${postId}`);
            const result = await response.json();
            if (result.success) {
                const comments = result.comments || [];
                if (comments.length === 0) {
                    commentsSection.innerHTML = '<p class="no-comments-msg" style="font-size:0.85rem; color:var(--ui-muted); padding:0.5rem 0;">No comments yet. Be the first to comment!</p>';
                } else {
                    commentsSection.innerHTML = comments.map(c => `
                        <div class="comment-item" style="padding:0.5rem 0; border-bottom:1px solid var(--ui-border);">
                            <div class="comment-meta" style="font-size:0.85rem; font-weight:600; color:var(--ui-text);">
                                <strong>${sanitizeHTML(c.username)}</strong>
                                <small style="color:var(--ui-muted); font-weight:normal; margin-left:6px;">${new Date(c.created_at).toLocaleString()}</small>
                            </div>
                            <p style="font-size:0.9rem; color:var(--ui-text); margin:0.2rem 0 0 0;">${sanitizeHTML(c.content)}</p>
                        </div>
                    `).join('');
                }
            }
        } catch (error) {
            console.error('Error fetching comments:', error);
        }
    }

    // Comment Form Submit
    document.querySelectorAll('.comment-submit').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const postId = btn.dataset.postId;
            const feed = btn.closest('.feed');
            const input = feed ? feed.querySelector('.comment-input') : null;
            const content = input ? input.value.trim() : '';

            if (!content || !postId) return;

            try {
                const response = await fetch('../comment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ post_id: postId, content, csrf_token: window.csrfToken })
                });
                const result = await response.json();
                if (result.success) {
                    input.value = '';
                    const commentsSection = document.getElementById(`comments-${postId}`);
                    if (commentsSection) {
                        commentsSection.style.display = 'block';
                        fetchComments(postId);
                    }
                    const commentCount = feed.querySelector('.comment-count');
                    if (commentCount) {
                        commentCount.textContent = parseInt(commentCount.textContent || 0) + 1;
                    }
                }
            } catch (error) {
                console.error('Error adding comment:', error);
            }
        });
    });
});
