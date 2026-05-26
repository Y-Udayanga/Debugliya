document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;

            if (!window.csrfToken) {
                alert('Session expired. Please refresh.');
                console.error('CSRF token missing');
                return;
            }

            console.log(`Removing bookmark for post ID: ${postId}`);
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

                console.log(`Response status: ${response.status}`);
                const text = await response.text();
                console.log('Raw response:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e.message);
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    alert('Post removed from bookmarks.');
                    btn.closest('.feed').remove();
                    if (!document.querySelector('.feeds').children.length) {
                        document.querySelector('.feeds').innerHTML = '<p class="no-bookmarks">No bookmarked posts yet.</p>';
                    }
                } else {
                    alert(result.message || 'Failed to remove bookmark.');
                }
            } catch (error) {
                alert('Error removing bookmark: ' + error.message);
                console.error('Fetch error:', error);
            }
        });
    });
});