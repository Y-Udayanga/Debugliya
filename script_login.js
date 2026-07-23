document.addEventListener('DOMContentLoaded', () => {
    // Password visibility toggle
    const toggleBtn = document.querySelector('#toggle-password');
    const passwordInput = document.querySelector('#password');
    const toggleIcon = document.querySelector('#toggle-icon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleIcon.className = isPassword ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
        });
    }

    // Submit button loading animation
    const loginForm = document.querySelector('#login-form');
    const loginBtn = document.querySelector('#login-btn');

    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', () => {
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Signing in...';
        });
    }

    // Particle canvas animation
    const canvas = document.getElementById('login-particles');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const resizeCanvas = () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        };
        resizeCanvas();

        const particles = [];
        const particleCount = 50;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 2 + 1;
                this.speedX = Math.random() * 0.4 - 0.2;
                this.speedY = Math.random() * 0.4 - 0.2;
                this.alpha = Math.random() * 0.5 + 0.2;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }

            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.alpha})`;
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
});
