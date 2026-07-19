<?php
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Settings</title>
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="../ui-polish.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <div class="logo">Debuglia</div>
            <button class="hamburger" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="nav-links">
                <ul>
                    <li><a href="../home/home.php" class="active">Home</a></li>
                    <li><a href="../about/about.php">About</a></li>
                    <li><a href="../profile/profile.php">Profile</a></li>
                    <li><a href="../index.php">Forum</a></li>
                    <li><a href="../resources/resources.php">Resources</a></li>
                </ul>
            </nav>
            <nav class="nav-utils">
                <ul>
                    <li><span class="lang-toggle" role="button" aria-label="Toggle language">EN</span></li>
                    <li><a href="#" class="help-link">Help</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="../logout.php" class="logout">Logout</a></li>
                    <?php else: ?>
                        <li><a href="../login.php" class="login">Login</a></li>
                    <?php endif; ?>
                    <li><button id="theme-toggle" aria-label="Toggle theme"><i class="bi bi-moon-stars"></i></button></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="left">
                <a class="profile" href="../profile/profile.php">
                    <div class="profile-photo">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
                    </div>
                    <div class="handle">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </a>
                <div class="sidebar">
                    <a class="menu-item" href="../explora/explora.php"><i class="bi bi-compass"></i><h3>Explore</h3></a>
                    <a class="menu-item" href="../notification/notification.php"><i class="bi bi-bell-fill"></i><h3>Notifications</h3></a>
                    <a class="menu-item" href="../trending_topic/trending_topic.php"><i class="bi bi-chat-fill"></i><h3>Trending Topics</h3></a>
                    <a class="menu-item" href="../bookmark/bookmark.php"><i class="bi bi-bookmarks"></i><h3>Bookmarks</h3></a>
                    <a class="menu-item" href="../analytics/analytics.php"><i class="bi bi-clipboard2-data"></i><h3>Analytics</h3></a>
                    <a class="menu-item active" href="../setting/setting.php"><i class="bi bi-gear"></i><h3>Settings</h3></a>
                    <a class="menu-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i><h3>Logout</h3></a>
                </div>
            </div>

            <div class="middle">
                <div class="settings-card">
                    <h2>Settings</h2>
                    <div class="tabs">
                        <button class="tab-btn active" data-tab="account">Account</button>
                        <button class="tab-btn" data-tab="privacy">Privacy</button>
                        <button class="tab-btn" data-tab="notifications">Notifications</button>
                    </div>
                    <div class="tab-content" id="account">
                        <form id="settings-form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">New Password (leave blank to keep current)</label>
                                <input type="password" id="password" name="password" placeholder="Enter new password">
                            </div>
                            <div class="form-group">
                                <label for="profile-photo-upload" class="upload-btn">
                                    <i class="bi bi-paperclip"></i> Update Profile Photo
                                    <input type="file" id="profile-photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                </label>
                                <div class="image-preview" style="display: none;">
                                    <div id="profile-image-preview"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                        <div class="form-group delete-account">
                            <button id="delete-account-btn" class="btn btn-danger">Delete Account</button>
                        </div>
                    </div>
                    <div class="tab-content" id="privacy" style="display: none;">
                        <form id="privacy-form">
                            <div class="form-group">
                                <label><input type="checkbox" name="profile_public"> Make profile public</label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" name="posts_public"> Make posts public</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                        </form>
                    </div>
                    <div class="tab-content" id="notifications" style="display: none;">
                        <form id="notifications-form">
                            <div class="form-group">
                                <label><input type="checkbox" name="email_notifications"> Receive email notifications</label>
                            </div>
                            <div class="form-group">
                                <label><input type="checkbox" name="push_notifications"> Receive push notifications</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="right">
                <div class="trending-topics">
                    <h4>Trending Topics</h4>
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search Trending Topics" id="trending-topic">
                    </div>
                    <ul>
                        <li><a href="#">#Technology</a></li>
                        <li><a href="#">#Programming</a></li>
                        <li><a href="#">#WebDevelopment</a></li>
                        <li><a href="#">#AI</a></li>
                        <li><a href="#">#CloudComputing</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-logo">
                <h3>Debuglia</h3>
                <p>Connect, share, and learn with creators worldwide.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="setting.php">Settings</a></li>
                    <li><a href="../profile/profile.php">Profile</a></li>
                    <li><a href="../analytics/analytics.php">Analytics</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </div>
            <div class="footer-social">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="bi bi-twitter"></i></a>
                    <a href="#"><i class="bi bi-github"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-contact">
                <h4>Contact Us</h4>
                <p>Email: <a href="mailto:support@debuglia.com">support@debuglia.com</a></p>
                <p>Phone: +94711234567</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Debuglia. All rights reserved.</p>
        </div>
    </footer>

    <script>
        window.csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
        console.log('CSRF Token:', window.csrfToken);
    </script>
    <script src="settings.js"></script>
</body>
</html>
