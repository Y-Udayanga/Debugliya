document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    const themeToggle = document.querySelector('#theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            document.body.classList.toggle('dark-mode');
            themeToggle.style.transform = 'rotate(360deg)';
            setTimeout(() => { themeToggle.style.transform = 'rotate(0deg)'; }, 300);
            const isDark = document.body.classList.contains('dark-theme') || document.body.classList.contains('dark-mode');
            themeToggle.innerHTML = isDark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });

        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-theme');
            document.body.classList.add('dark-mode');
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
            const targetId = anchor.getAttribute('href');
            if (targetId && targetId !== '#') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // Particle background
    const canvas = document.getElementById('particles');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const resizeCanvas = () => {
            canvas.width = canvas.parentElement ? canvas.parentElement.clientWidth : window.innerWidth;
            canvas.height = canvas.parentElement ? canvas.parentElement.clientHeight : 500;
        };
        resizeCanvas();

        const particles = [];
        const particleCount = 60;

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
                ctx.fillStyle = 'rgba(255, 255, 255, 0.4)';
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
        window.addEventListener('resize', resizeCanvas);
    }

    // Help Center Modal Handler for Home page
    const helpLinks = document.querySelectorAll('.help-link');
    helpLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            let modal = document.getElementById('help-modal');
            if (modal) {
                modal.style.display = 'flex';
            } else if (typeof window.initHelpModal === 'function') {
                window.initHelpModal();
                modal = document.getElementById('help-modal');
                if (modal) modal.style.display = 'flex';
            }
        });
    });
});

// Interactive terminal code copy helper
window.copyTerminalCode = () => {
    const codeText = `const debuglia = require('@debuglia/core');\n\nasync function startSession() {\n  const developer = await debuglia.authenticate();\n  console.log(\`🚀 Welcome \${developer.username}!\`);\n  return debuglia.fetchTrendingDiscussions();\n}\n\nstartSession();`;
    navigator.clipboard.writeText(codeText).then(() => {
        const btn = document.querySelector('#terminal-copy-btn');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            btn.style.background = '#10b981';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                btn.style.background = '';
            }, 2200);
        }
    }).catch(() => {});
};

// Newsletter submission toast handler
window.handleNewsletterSubmit = (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Subscribed!';
        btn.style.background = '#10b981';
        form.reset();
        setTimeout(() => {
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Subscribe';
            btn.style.background = '';
        }, 3000);
    }
};