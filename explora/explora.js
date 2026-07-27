document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('explore-search');
    const clearBtn = document.getElementById('clear-search');
    const categoryPills = document.querySelectorAll('.cat-pill');
    const feeds = document.querySelectorAll('#explore-feeds-list .feed');
    const emptyState = document.getElementById('explore-empty');
    const resetBtn = document.getElementById('reset-explore-btn');

    let currentCategory = 'all';

    function filterPosts() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        let visibleCount = 0;

        feeds.forEach(feed => {
            const username = feed.querySelector('.info h3')?.textContent.toLowerCase() || '';
            const content = feed.querySelector('.caption p')?.textContent.toLowerCase() || '';
            const postCat = (feed.dataset.category || feed.querySelector('.category-tag')?.textContent || '').toLowerCase().trim();

            const matchesQuery = !query || username.includes(query) || content.includes(query) || postCat.includes(query);
            const matchesCategory = currentCategory === 'all' || postCat.includes(currentCategory.toLowerCase());

            if (matchesQuery && matchesCategory) {
                feed.style.display = 'block';
                visibleCount++;
            } else {
                feed.style.display = 'none';
            }
        });

        // Show/hide empty state
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // Show/hide clear search button
        if (clearBtn) {
            clearBtn.style.display = query.length > 0 ? 'inline-flex' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterPosts);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            filterPosts();
        });
    }

    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentCategory = pill.dataset.category || 'all';
            filterPosts();
        });
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            categoryPills.forEach(p => p.classList.remove('active'));
            const allPill = document.querySelector('.cat-pill[data-category="all"]');
            if (allPill) allPill.classList.add('active');
            currentCategory = 'all';
            filterPosts();
        });
    }
});

// Community Join Handler
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