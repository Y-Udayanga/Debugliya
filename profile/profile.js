document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const themeToggle = document.querySelector('#theme-toggle');
    const themeMenuItem = document.querySelector('#theme');
    const modal = document.querySelector('.modal');
    const imageModal = document.querySelector('.image-modal');
    const modalImage = document.querySelector('#modal-image');
    const closeImageModal = document.querySelector('#close-image-modal');

    const escapeHTML = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    // Hamburger menu toggle
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
        } else {
            themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i>';
        }
    }

    // Theme modal
    if (themeMenuItem) {
        themeMenuItem.addEventListener('click', (e) => {
            e.preventDefault();
            if (modal) modal.style.display = 'flex';
        });
    }

    // Interactive Tab Switching System
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.dataset.tab;

            // Update active states on tab buttons
            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            // Show corresponding tab content pane
            tabPanes.forEach(pane => {
                if (pane.id === `tab-${targetTab}`) {
                    pane.style.display = 'block';
                    pane.classList.add('active');
                } else {
                    pane.style.display = 'none';
                    pane.classList.remove('active');
                }
            });
        });
    });

    // Image Modal Enlargement
    function initializePostImages() {
        document.querySelectorAll('.post-image').forEach(img => {
            img.addEventListener('click', () => {
                if (modalImage && imageModal) {
                    modalImage.src = img.src;
                    imageModal.style.display = 'flex';
                }
            });
        });
    }

    if (closeImageModal) {
        closeImageModal.addEventListener('click', () => {
            if (imageModal) imageModal.style.display = 'none';
        });
    }

    if (imageModal) {
        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) imageModal.style.display = 'none';
        });
    }

    initializePostImages();

    // Edit Profile Modal handlers
    window.openEditProfileModal = () => {
        const editModal = document.querySelector('#edit-profile-modal');
        if (editModal) editModal.style.display = 'flex';
    };

    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const editModal = document.querySelector('#edit-profile-modal');
            if (editModal) editModal.style.display = 'none';
        });
    });

    const editModalElement = document.querySelector('#edit-profile-modal');
    if (editModalElement) {
        editModalElement.addEventListener('click', (e) => {
            if (e.target === editModalElement) {
                editModalElement.style.display = 'none';
            }
        });
    }

    // Profile photo upload preview in modal
    const profilePhotoInput = document.querySelector('#profile-photo-upload');
    const photoPreviewContainer = document.querySelector('#modal-photo-preview');

    if (profilePhotoInput && photoPreviewContainer) {
        profilePhotoInput.addEventListener('change', () => {
            const file = profilePhotoInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    photoPreviewContainer.innerHTML = `<img src="${e.target.result}" alt="New Avatar Preview">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Profile form submission handler
    const editProfileForm = document.querySelector('#edit-profile-form');
    if (editProfileForm) {
        const handleProfileSubmit = async (e) => {
            if (e) e.preventDefault();
            const submitBtn = editProfileForm.querySelector('#save-profile-submit-btn') || editProfileForm.querySelector('button[type="submit"]');
            const originalBtnHTML = submitBtn ? submitBtn.innerHTML : 'Save Profile';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Saving...';
            }

            const formData = new FormData(editProfileForm);

            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (jsonErr) {
                    console.error('Invalid response from update_profile.php:', text);
                    throw new Error('Server returned invalid data format.');
                }

                if (result.success) {
                    const editModal = document.querySelector('#edit-profile-modal');
                    if (editModal) editModal.style.display = 'none';
                    if (result.profile_photo) {
                        updateProfilePhotos(result.profile_photo);
                    }
                    localStorage.setItem('profile_updated', Date.now());
                    location.reload();
                } else {
                    showError(result.message || 'Failed to update profile.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHTML;
                    }
                }
            } catch (error) {
                console.error('Save Profile error:', error);
                showError('Error updating profile: ' + error.message);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHTML;
                }
            }
        };

        editProfileForm.addEventListener('submit', handleProfileSubmit);
    }

    // Helper to update all profile photos dynamically across page
    function updateProfilePhotos(newSrc) {
        document.querySelectorAll('.profile-photo img, .profile-photo-large img').forEach(img => {
            if (newSrc && !newSrc.includes('blank-profile-picture.webp')) {
                img.src = newSrc + '?v=' + Date.now();
            }
        });
    }

    // Listen for cross-tab storage profile updates
    window.addEventListener('storage', (e) => {
        if (e.key === 'profile_updated') {
            fetch('update_profile.php', {
                method: 'GET'
            }).then(response => response.json()).then(data => {
                if (data.profile_photo) updateProfilePhotos(data.profile_photo);
            }).catch(() => {});
        }
    });

    // Copy profile share link
    window.copyProfileLink = () => {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            const btn = document.querySelector('#share-profile-btn');
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2-circle"></i> Copied!';
                btn.style.background = '#10b981';
                btn.style.color = '#ffffff';
                btn.style.borderColor = '#10b981';
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                    btn.style.color = '';
                    btn.style.borderColor = '';
                }, 2200);
            }
        }).catch(err => {
            console.error('Failed to copy link:', err);
            showError('Unable to copy profile link to clipboard.');
        });
    };

    // Bookmark functionality
    document.querySelectorAll('.bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            const action = btn.classList.contains('bookmarked') ? 'remove' : 'add';

            if (!window.csrfToken) {
                alert('Session expired. Please refresh the page.');
                return;
            }

            try {
                const response = await fetch('../bookmark/bookmark_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        post_id: postId,
                        action: action,
                        csrf_token: window.csrfToken
                    })
                });

                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid server response');
                }

                if (result.success) {
                    if (action === 'add') {
                        btn.classList.add('bookmarked');
                        const icon = btn.querySelector('i');
                        if (icon) icon.className = 'bi bi-bookmark-fill';
                    } else {
                        btn.classList.remove('bookmarked');
                        const icon = btn.querySelector('i');
                        if (icon) icon.className = 'bi bi-bookmark';
                    }
                } else {
                    alert(result.message || 'Failed to update bookmark.');
                }
            } catch (error) {
                console.error('Bookmark fetch error:', error);
                alert('Error updating bookmark: ' + error.message);
            }
        });
    });

    // Like functionality
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            const action = btn.classList.contains('liked') ? 'unlike' : 'like';

            if (!window.csrfToken) {
                alert('CSRF token is missing. Please refresh the page.');
                return;
            }

            try {
                const response = await fetch('../like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken
                    },
                    body: JSON.stringify({ post_id: postId, action, csrf_token: window.csrfToken })
                });

                const result = await response.json();
                if (result.success) {
                    const likeCountElement = btn.querySelector('.like-count');
                    if (likeCountElement) likeCountElement.textContent = result.like_count;
                    const icon = btn.querySelector('i');
                    if (result.action === 'like') {
                        btn.classList.add('liked');
                        if (icon) icon.className = 'bi bi-heart-fill';
                        fetch('../notification/create_notification.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-Token': window.csrfToken
                            },
                            body: JSON.stringify({ post_id: postId, type: 'like', csrf_token: window.csrfToken })
                        }).catch(() => {});
                    } else {
                        btn.classList.remove('liked');
                        if (icon) icon.className = 'bi bi-heart';
                    }
                } else {
                    alert(result.message || 'Failed to like/unlike post.');
                }
            } catch (error) {
                console.error('Like error:', error);
                alert('Error liking/unliking post: Network issue.');
            }
        });
    });

    // Comment Section Toggle
    document.querySelectorAll('.comment-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postCard = btn.closest('.post-card') || btn.closest('.feed');
            if (!postCard) return;

            const commentsSection = postCard.querySelector('.comments-section');
            const postId = btn.dataset.postId || postCard.dataset.postId;

            if (commentsSection.style.display === 'none' || !commentsSection.style.display) {
                commentsSection.style.display = 'block';
                try {
                    const response = await fetch(`../comments.php?post_id=${postId}`);
                    const result = await response.json();
                    const commentsList = commentsSection.querySelector('.comments-list');

                    if (result.success) {
                        const comments = result.comments || [];
                        commentsList.innerHTML = comments.map(comment => `
                            <div class="comment" data-comment-id="${comment.id}">
                                <div class="profile-photo">
                                    <img src="${comment.profile_photo ? '../uploads/' + escapeHTML(comment.profile_photo) : '../blank-profile-picture.webp'}">
                                </div>
                                <div class="comment-content">
                                    <p><b>${escapeHTML(comment.username)}</b>: ${escapeHTML(comment.content)}</p>
                                    <small>${new Date(comment.created_at).toLocaleString()}</small>
                                </div>
                            </div>
                        `).join('');
                    }
                } catch (error) {
                    console.error('Comment load error:', error);
                }
            } else {
                commentsSection.style.display = 'none';
            }
        });
    });

    // Delete Post Functionality
    document.querySelectorAll('.delete-post-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const postId = btn.dataset.postId;
            if (confirm('Are you sure you want to delete this post?')) {
                if (!window.csrfToken) {
                    alert('CSRF token is missing. Please refresh the page.');
                    return;
                }

                try {
                    const response = await fetch('../delete_post.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.csrfToken },
                        body: JSON.stringify({ post_id: postId, csrf_token: window.csrfToken })
                    });

                    const text = await response.text();
                    try {
                        const result = JSON.parse(text);
                        if (result.success) {
                            const postCard = btn.closest('.post-card') || btn.closest('.feed');
                            if (postCard) postCard.remove();
                        } else {
                            alert(result.message || 'Failed to delete post.');
                        }
                    } catch (jsonError) {
                        console.error('Raw response from delete_post.php:', text);
                        alert('Error deleting post: Invalid server response.');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('Error deleting post: Network issue.');
                }
            }
        });
    });

    // Notification toast helper
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.position = 'fixed';
        errorDiv.style.top = '20px';
        errorDiv.style.right = '20px';
        errorDiv.style.background = '#ef4444';
        errorDiv.style.color = '#ffffff';
        errorDiv.style.padding = '12px 20px';
        errorDiv.style.borderRadius = '10px';
        errorDiv.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
        errorDiv.style.zIndex = '9999';
        document.body.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 4000);
    }
});
