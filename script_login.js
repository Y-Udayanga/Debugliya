document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const action = form.action || window.location.pathname;

            // Basic client-side validation
            let isValid = true;
            const inputs = form.querySelectorAll('input[required]');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#dc3545';
                } else {
                    input.style.borderColor = '#ddd';
                }
            });

            if (!isValid) {
                const errorDiv = form.querySelector('.error') || document.createElement('p');
                errorDiv.className = 'error';
                errorDiv.textContent = 'Please fill in all required fields.';
                if (!form.querySelector('.error')) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
                return;
            }

            try {
                const response = await fetch(action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                const errorDiv = form.querySelector('.error') || document.createElement('p');
                errorDiv.className = 'error';
                errorDiv.textContent = result.message || 'An error occurred.';
                
                if (result.success) {
                    window.location.href = result.redirect || 'index.php';
                } else {
                    if (!form.querySelector('.error')) {
                        form.insertBefore(errorDiv, form.firstChild);
                    }
                }
            } catch (error) {
                const errorDiv = form.querySelector('.error') || document.createElement('p');
                errorDiv.className = 'error';
                errorDiv.textContent = 'Error: ' + error.message;
                if (!form.querySelector('.error')) {
                    form.insertBefore(errorDiv, form.firstChild);
                }
            }
        });
    });
});
