document.addEventListener('DOMContentLoaded', () => {
    const joinButtons = document.querySelectorAll('.suggested-communities .btn-join');
    joinButtons.forEach(button => {
        button.addEventListener('click', () => {
            button.textContent = button.textContent === 'Join' ? 'Joined' : 'Join';
            button.classList.toggle('joined');
        });
    });

    // Search bar functionality 
    const searchInput = document.getElementById('explore-search');
    const feeds = document.querySelectorAll('.feeds .feed');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            feeds.forEach(feed => {
                const username = feed.querySelector('.info h3').textContent.toLowerCase();
                const content = feed.querySelector('.caption p').textContent.toLowerCase();
                const category = feed.querySelector('.category h3').textContent.toLowerCase();
                const isVisible = username.includes(query) || content.includes(query) || category.includes(query);
                feed.style.display = isVisible ? 'block' : 'none';
            });
        });
    }
});