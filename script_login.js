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

    // Register page password toggle
    const regToggleBtn = document.querySelector('#toggle-reg-password');
    const regPasswordInput = document.querySelector('#reg-password');
    const regToggleIcon = document.querySelector('#toggle-reg-icon');

    if (regToggleBtn && regPasswordInput && regToggleIcon) {
        regToggleBtn.addEventListener('click', () => {
            const isPassword = regPasswordInput.type === 'password';
            regPasswordInput.type = isPassword ? 'text' : 'password';
            regToggleIcon.className = isPassword ? 'bi bi-eye-fill' : 'bi bi-eye-slash-fill';
        });
    }

    // Password match indicator
    const confirmInput = document.querySelector('#reg-confirm-password');
    const matchMsg = document.querySelector('#password-match-msg');

    if (regPasswordInput && confirmInput && matchMsg) {
        const validateMatch = () => {
            const pass = regPasswordInput.value;
            const confirm = confirmInput.value;
            if (!confirm) {
                matchMsg.style.display = 'none';
                return;
            }
            matchMsg.style.display = 'block';
            if (pass === confirm) {
                matchMsg.textContent = '✓ Passwords match';
                matchMsg.style.color = '#10b981';
            } else {
                matchMsg.textContent = '✕ Passwords do not match';
                matchMsg.style.color = '#ef4444';
            }
        };
        regPasswordInput.addEventListener('input', validateMatch);
        confirmInput.addEventListener('input', validateMatch);
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

    const registerForm = document.querySelector('#register-form');
    const registerBtn = document.querySelector('#register-btn');

    if (registerForm && registerBtn) {
        registerForm.addEventListener('submit', () => {
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Creating Account...';
        });
    }

    // High-Tech Interactive Mouse-Tracking Constellation Canvas
    const canvas = document.getElementById('login-particles');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;

        const mouse = {
            x: width / 2,
            y: height / 2,
            radius: 180,
            active: false
        };

        window.addEventListener('mousemove', (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
            mouse.active = true;
        });

        window.addEventListener('mouseleave', () => {
            mouse.active = false;
        });

        const resizeCanvas = () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        };
        window.addEventListener('resize', resizeCanvas);

        class InteractiveParticle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.baseSize = Math.random() * 2.5 + 1;
                this.size = this.baseSize;
                this.speedX = Math.random() * 0.8 - 0.4;
                this.speedY = Math.random() * 0.8 - 0.4;
                this.color = Math.random() > 0.5 ? 'rgba(0, 212, 255, ' : 'rgba(99, 102, 241, ';
                this.alpha = Math.random() * 0.5 + 0.3;
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x < 0 || this.x > width) this.speedX *= -1;
                if (this.y < 0 || this.y > height) this.speedY *= -1;

                // Mouse interaction - expand & gravitate
                if (mouse.active) {
                    const dx = mouse.x - this.x;
                    const dy = mouse.y - this.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < mouse.radius) {
                        const force = (mouse.radius - dist) / mouse.radius;
                        this.size = this.baseSize + force * 3;
                        this.x += (dx / dist) * force * 0.8;
                        this.y += (dy / dist) * force * 0.8;
                    } else {
                        this.size = this.baseSize;
                    }
                } else {
                    this.size = this.baseSize;
                }
            }

            draw() {
                ctx.fillStyle = this.color + this.alpha + ')';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        const particleCount = Math.min(Math.floor((width * height) / 10000), 85);
        const particles = [];
        for (let i = 0; i < particleCount; i++) {
            particles.push(new InteractiveParticle());
        }

        function connectParticles() {
            const maxDistance = 120;
            for (let a = 0; a < particles.length; a++) {
                for (let b = a + 1; b < particles.length; b++) {
                    const dx = particles[a].x - particles[b].x;
                    const dy = particles[a].y - particles[b].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < maxDistance) {
                        const opacity = 1 - (distance / maxDistance);
                        ctx.strokeStyle = `rgba(0, 212, 255, ${opacity * 0.25})`;
                        ctx.lineWidth = 0.8;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(particles[b].x, particles[b].y);
                        ctx.stroke();
                    }
                }

                // Connect to mouse cursor
                if (mouse.active) {
                    const dx = particles[a].x - mouse.x;
                    const dy = particles[a].y - mouse.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);

                    if (distance < mouse.radius) {
                        const opacity = 1 - (distance / mouse.radius);
                        ctx.strokeStyle = `rgba(99, 102, 241, ${opacity * 0.55})`;
                        ctx.lineWidth = 1.2;
                        ctx.beginPath();
                        ctx.moveTo(particles[a].x, particles[a].y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.stroke();
                    }
                }
            }
        }

        function animate() {
            ctx.clearRect(0, 0, width, height);
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            connectParticles();
            requestAnimationFrame(animate);
        }

        animate();
    }
});
