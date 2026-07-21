document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    const themeToggle = document.querySelector('#theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            themeToggle.style.transform = 'rotate(360deg)';
            setTimeout(() => { themeToggle.style.transform = 'rotate(0deg)'; }, 300);
            themeToggle.innerHTML = document.body.classList.contains('dark-theme') ?
                '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            themeToggle.innerHTML = '<i class="bi bi-sun"></i>';
        }
    }

    // Hamburger menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('open');
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            document.querySelector(anchor.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Particle background
    const canvas = document.getElementById('particles');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const particles = [];
        const particleCount = 80;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedX = Math.random() * 0.4 - 0.2;
                this.speedY = Math.random() * 0.4 - 0.2;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }

            draw() {
                ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            requestAnimationFrame(animate);
        }

        animate();
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    }

    // 3D tilt effect
    document.querySelectorAll('[data-tilt]').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const tiltX = (y - centerY) / 12;
            const tiltY = (centerX - x) / 12;
            card.style.transform = `perspective(1000px) rotateX(${tiltX}deg) rotateY(${tiltY}deg)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });

    // Resource Search and Filter System
    const resourceSearch = document.querySelector('#resource-search');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const resourceCards = document.querySelectorAll('.resource-card');

    let currentFilter = 'all';
    let currentQuery = '';

    function filterResources() {
        resourceCards.forEach(card => {
            const category = card.getAttribute('data-category') || '';
            const name = card.getAttribute('data-name') || '';
            const desc = card.getAttribute('data-desc') || '';
            const bookmarkBtn = card.querySelector('.bookmark-btn');
            const isBookmarked = bookmarkBtn && bookmarkBtn.classList.contains('active');

            let matchesCategory = false;
            if (currentFilter === 'all') {
                matchesCategory = true;
            } else if (currentFilter === 'bookmarked') {
                matchesCategory = isBookmarked;
            } else {
                matchesCategory = (category.toLowerCase() === currentFilter.toLowerCase());
            }

            const matchesSearch = (name.includes(currentQuery) || desc.includes(currentQuery));

            if (matchesCategory && matchesSearch) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.4s ease-out';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (resourceSearch) {
        resourceSearch.addEventListener('input', (e) => {
            currentQuery = e.target.value.toLowerCase().trim();
            filterResources();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            filterResources();
        });
    });

    // Bookmark Toggle Logic with LocalStorage
    let savedBookmarks = JSON.parse(localStorage.getItem('saved_resources') || '[]');

    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        const id = btn.getAttribute('data-id');
        if (savedBookmarks.includes(id)) {
            btn.classList.add('active');
            btn.innerHTML = '<i class="bi bi-star-fill"></i>';
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="bi bi-star"></i>';
                savedBookmarks = savedBookmarks.filter(item => item !== id);
            } else {
                btn.classList.add('active');
                btn.innerHTML = '<i class="bi bi-star-fill"></i>';
                if (!savedBookmarks.includes(id)) savedBookmarks.push(id);
            }
            localStorage.setItem('saved_resources', JSON.stringify(savedBookmarks));
            if (currentFilter === 'bookmarked') filterResources();
        });
    });

    // Copy Code Snippet Handler
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const textToCopy = btn.getAttribute('data-copy');
            if (textToCopy) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.classList.remove('copied');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        });
    });

    // Suggest Resource Modal Logic
    const suggestModal = document.querySelector('#suggest-modal');
    const openSuggestBtn = document.querySelector('#open-suggest-modal');
    const closeSuggestBtn = document.querySelector('#close-suggest-modal');
    const suggestForm = document.querySelector('#suggest-resource-form');

    if (openSuggestBtn && suggestModal) {
        openSuggestBtn.addEventListener('click', () => {
            suggestModal.classList.add('active');
        });
    }

    if (closeSuggestBtn && suggestModal) {
        closeSuggestBtn.addEventListener('click', () => {
            suggestModal.classList.remove('active');
        });
        suggestModal.addEventListener('click', (e) => {
            if (e.target === suggestModal) suggestModal.classList.remove('active');
        });
    }

    if (suggestForm) {
        suggestForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = suggestForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Submitted! Thank you!';
            submitBtn.style.background = '#10b981';

            setTimeout(() => {
                suggestForm.reset();
                submitBtn.innerHTML = originalText;
                submitBtn.style.background = '';
                suggestModal.classList.remove('active');
            }, 1800);
        });
    }
});