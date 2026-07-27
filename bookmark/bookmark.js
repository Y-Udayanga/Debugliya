document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('bookmark-search-input');
    const clearBtn = document.getElementById('clear-bookmark-search');
    const feedsContainer = document.getElementById('bookmark-feeds-list');
    const emptyState = document.getElementById('bookmark-empty-state');
    const savedCountPill = document.getElementById('saved-count-pill');

    function updateCountPill() {
        const remainingFeeds = document.querySelectorAll('#bookmark-feeds-list .feed');
        if (savedCountPill) {
            savedCountPill.innerHTML = `<i class="bi bi-bookmarks-fill"></i> ${remainingFeeds.length} Saved Items`;
        }
        if (emptyState) {
            emptyState.style.display = remainingFeeds.length === 0 ? 'flex' : 'none';
        }
    }

    function filterBookmarks() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const feeds = document.querySelectorAll('#bookmark-feeds-list .feed');
        let visibleCount = 0;

        feeds.forEach(feed => {
            const username = feed.querySelector('.info h3')?.textContent.toLowerCase() || '';
            const content = feed.querySelector('.caption p')?.textContent.toLowerCase() || '';
            const category = feed.querySelector('.category-pill-badge')?.textContent.toLowerCase() || '';

            const matches = !query || username.includes(query) || content.includes(query) || category.includes(query);

            if (matches) {
                feed.style.display = 'block';
                visibleCount++;
            } else {
                feed.style.display = 'none';
            }
        });

        if (emptyState) {
            const totalFeeds = document.querySelectorAll('#bookmark-feeds-list .feed').length;
            if (totalFeeds === 0 || visibleCount === 0) {
                emptyState.style.display = 'flex';
            } else {
                emptyState.style.display = 'none';
            }
        }

        if (clearBtn) {
            clearBtn.style.display = query.length > 0 ? 'inline-flex' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterBookmarks);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            filterBookmarks();
        });
    }

    // Bookmark Removal Listener
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            if (!postId) return;

            if (!window.csrfToken) {
                console.error('CSRF token missing');
                return;
            }

            try {
                const response = await fetch('bookmark_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        post_id: postId,
                        action: 'remove',
                        csrf_token: window.csrfToken
                    })
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    const feed = btn.closest('.feed');
                    if (feed) {
                        feed.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        feed.style.opacity = '0';
                        feed.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            feed.remove();
                            updateCountPill();
                        }, 300);
                    }
                } else {
                    alert(result.message || 'Failed to remove bookmark.');
                }
            } catch (error) {
                console.error('Fetch error:', error);
            }
        });
    });

    // Sidebar Trending Topic Search
    const topicSearch = document.getElementById('trending-topic-search');
    const topicTags = document.querySelectorAll('#topic-tags-list .topic-tag');

    if (topicSearch && topicTags) {
        topicSearch.addEventListener('input', () => {
            const query = topicSearch.value.toLowerCase().trim();
            topicTags.forEach(tag => {
                const text = tag.textContent.toLowerCase();
                tag.style.display = text.includes(query) ? 'inline-block' : 'none';
            });
        });
    }
});

// Global Join Community Toggle
window.toggleJoinCommunity = (btn) => {
    if (!btn) return;
    const isJoined = btn.classList.contains('joined');
    if (isJoined) {
        btn.classList.remove('joined');
        btn.innerHTML = '<i class="bi bi-plus-lg"></i> Join';
    } else {
        btn.classList.add('joined');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Joined';
    }
};