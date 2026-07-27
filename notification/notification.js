document.addEventListener('DOMContentLoaded', () => {
    // Notification item navigation click
    const notifItems = document.querySelectorAll('.notification-item');
    notifItems.forEach(item => {
        item.addEventListener('click', () => {
            const postId = item.dataset.postId;
            if (postId) {
                window.location.href = `../post_display.php?id=${postId}`;
            }
        });
    });

    // Tab Filter Logic
    const tabs = document.querySelectorAll('.notif-tab');
    const emptyState = document.getElementById('notif-empty-state');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const filter = tab.dataset.filter || 'all';
            let visibleCount = 0;

            notifItems.forEach(item => {
                const itemType = item.dataset.type || '';
                if (filter === 'all' || itemType === filter) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            if (emptyState) {
                emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
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
