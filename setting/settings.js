document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const themeToggle = document.querySelector('#theme-toggle');

    // Hamburger menu
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }

    // Theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDarkMode = document.body.classList.contains('dark-mode');
            themeToggle.innerHTML = isDarkMode ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
            localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
        });

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="bi bi-sun"></i>';
        }
    }

    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');

            btn.classList.add('active');
            document.querySelector(`#${btn.dataset.tab}`).style.display = 'block';
        });
    });

    // Profile photo upload preview
    const profilePhotoInput = document.querySelector('#profile-photo-upload');
    const imagePreview = document.querySelector('.image-preview');
    const previewContainer = document.querySelector('#profile-image-preview');

    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', () => {
            const file = profilePhotoInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
                previewContainer.innerHTML = '';
            }
        });
    }

    // Settings form submission
    const settingsForm = document.querySelector('#settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(settingsForm);

            try {
                const response = await fetch('update_settings.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    updateProfilePhotos(result.profile_photo);
                    localStorage.setItem('profile_updated', Date.now());
                    showSuccess('Settings updated successfully.');
                } else {
                    showError(result.message || 'Failed to update settings.');
                }
            } catch (error) {
                showError('Error updating settings: ' + error.message);
            }
        });
    }

    // Delete account
    const deleteAccountBtn = document.querySelector('#delete-account-btn');
    if (deleteAccountBtn) {
        console.log('Delete account button found');
        deleteAccountBtn.addEventListener('click', () => {
            console.log('Delete button clicked');
            if (!window.csrfToken) {
                console.error('CSRF token is missing');
                showError('CSRF token is missing. Please reload the page.');
                return;
            }
            console.log('Fetching delete_account.php with CSRF token:', window.csrfToken);
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                fetch('delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ csrf_token: window.csrfToken })
                })
                .then(response => {
                    console.log('Response received:', response);
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error ${response.status}: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    if (data.success) {
                        showSuccess('Account deleted successfully.');
                        setTimeout(() => {
                            window.location.href = '../login.php';
                        }, 2000);
                    } else {
                        showError(data.message || 'Failed to delete account.');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showError('Error deleting account: ' + error.message);
                });
            }
        });
    } else {
        console.error('Delete account button not found');
    }

    // Update profile photos
    function updateProfilePhotos(newSrc) {
        document.querySelectorAll('.profile-photo img').forEach(img => {
            if (!newSrc.includes('blank-profile-picture.webp')) {
                img.src = newSrc + '?v=' + Date.now();
            }
        });
    }

    // Listen for profile updates
    window.addEventListener('storage', (e) => {
        if (e.key === 'profile_updated') {
            fetch('update_settings.php', {
                method: 'GET'
            }).then(response => response.json()).then(data => {
                if (data.profile_photo) updateProfilePhotos(data.profile_photo);
            });
        }
    });

    // Success and error messages
    function showSuccess(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.textContent = message;
        successDiv.style.position = 'fixed';
        successDiv.style.top = '20px';
        successDiv.style.right = '20px';
        successDiv.style.background = '#4caf50';
        successDiv.style.color = '#fff';
        successDiv.style.padding = '10px';
        successDiv.style.borderRadius = '5px';
        successDiv.style.zIndex = '1000';
        document.body.appendChild(successDiv);
        setTimeout(() => successDiv.remove(), 5000);
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.position = 'fixed';
        errorDiv.style.top = '20px';
        errorDiv.style.right = '20px';
        errorDiv.style.background = '#ff4d4d';
        errorDiv.style.color = '#fff';
        errorDiv.style.padding = '10px';
        errorDiv.style.borderRadius = '5px';
        errorDiv.style.zIndex = '1000';
        document.body.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
});
