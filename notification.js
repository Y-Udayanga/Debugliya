document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.notification').forEach(notification => {
        notification.addEventListener('click', () => {
            const postId = notification.dataset.postId;
            window.location.href = `../post.php?id=${postId}`;
        });
    });
});