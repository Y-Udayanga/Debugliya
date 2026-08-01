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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="../ui-polish.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-theme', 'dark-mode');
            }
        })();
    </script>
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
                        <button class="tab-btn active" data-tab="account"><i class="bi bi-person-gear"></i> Account</button>
                        <button class="tab-btn" data-tab="privacy"><i class="bi bi-shield-lock"></i> Privacy</button>
                        <button class="tab-btn" data-tab="notifications"><i class="bi bi-bell"></i> Notifications</button>
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
                            <!-- Profile & Search Privacy -->
                            <div class="settings-section">
                                <h3><i class="bi bi-person-bounding-box"></i> Profile & Search Privacy</h3>
                                
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label for="profile_visibility">Profile Visibility</label>
                                        <p class="setting-desc">Control who can view your full developer profile and bio.</p>
                                    </div>
                                    <div class="setting-control">
                                        <select id="profile_visibility" name="profile_visibility" class="form-select">
                                            <option value="public" selected>Public (Everyone)</option>
                                            <option value="members">Community Members Only</option>
                                            <option value="private">Private (Only You)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Show Online Status</label>
                                        <p class="setting-desc">Display an active indicator badge when you are online in the forum.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="show_online_status" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Search Engine Indexing</label>
                                        <p class="setting-desc">Allow Google and other search engines to index your profile page.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="search_indexing" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity & Content Privacy -->
                            <div class="settings-section">
                                <h3><i class="bi bi-journal-text"></i> Activity & Content Privacy</h3>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Default Post Visibility</label>
                                        <p class="setting-desc">Default privacy setting for new questions and code snippets.</p>
                                    </div>
                                    <div class="setting-control">
                                        <select name="default_post_visibility" class="form-select">
                                            <option value="public" selected>Public</option>
                                            <option value="followers">Registered Members Only</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Show Saved Bookmarks Publicly</label>
                                        <p class="setting-desc">Allow other developers to view your curated bookmark collection.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="show_bookmarks_publicly">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Display Forum Activity Log</label>
                                        <p class="setting-desc">Show your recent forum comments and likes on your profile page.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="display_activity_log" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Direct Messages & Tagging -->
                            <div class="settings-section">
                                <h3><i class="bi bi-chat-dots-fill"></i> Interactions & Direct Messages</h3>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Who Can Tag You</label>
                                        <p class="setting-desc">Specify who can @mention you in forum discussions.</p>
                                    </div>
                                    <div class="setting-control">
                                        <select name="who_can_tag" class="form-select">
                                            <option value="everyone" selected>Everyone</option>
                                            <option value="following">Members You Follow</option>
                                            <option value="none">Nobody</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Security & Active Sessions -->
                            <div class="settings-section security-box">
                                <h3><i class="bi bi-shield-check"></i> Account Security & Active Sessions</h3>
                                
                                <div class="security-card">
                                    <div class="security-card-header">
                                        <i class="bi bi-shield-lock-fill"></i>
                                        <div>
                                            <h4>Two-Factor Authentication (2FA)</h4>
                                            <p>Add an extra layer of security using an authenticator app (Google Authenticator / Authy).</p>
                                        </div>
                                        <span class="badge badge-inactive">Disabled</span>
                                    </div>
                                    <button type="button" class="btn btn-outline" id="enable-2fa-btn">Set Up 2FA</button>
                                </div>

                                <div class="session-list-box">
                                    <h4><i class="bi bi-laptop"></i> Active Logged-in Sessions</h4>
                                    <div class="session-item">
                                        <div class="session-icon"><i class="bi bi-display"></i></div>
                                        <div class="session-info">
                                            <strong>Windows PC &bull; Chrome Browser</strong>
                                            <small>Current Session &bull; Sri Lanka (Active Now)</small>
                                        </div>
                                        <span class="badge badge-active">This Device</span>
                                    </div>
                                    <button type="button" class="btn btn-secondary-sm" id="logout-other-sessions-btn"><i class="bi bi-box-arrow-right"></i> Log Out All Other Devices</button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-save-privacy"><i class="bi bi-check2-circle"></i> Save Privacy Settings</button>
                        </form>
                    </div>
                    <div class="tab-content" id="notifications" style="display: none;">
                        <form id="notifications-form">
                            <!-- Notification Channels & Delivery -->
                            <div class="settings-section">
                                <h3><i class="bi bi-bell-fill"></i> Notification Channels & Delivery</h3>
                                
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Email Notifications Digest</label>
                                        <p class="setting-desc">Choose how often Debuglia sends activity summaries to your inbox.</p>
                                    </div>
                                    <div class="setting-control">
                                        <select name="email_digest_frequency" class="form-select">
                                            <option value="realtime">Real-time (Instant)</option>
                                            <option value="daily" selected>Daily Digest Summary</option>
                                            <option value="weekly">Weekly Developer Recap</option>
                                            <option value="off">Off (Never Send Emails)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Push Notifications</label>
                                        <p class="setting-desc">Receive instant browser desktop alerts for important updates.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="push_notifications" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>In-App Sound Alerts</label>
                                        <p class="setting-desc">Play a subtle audio chime when a new notification arrives while browsing.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="sound_alerts" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Activity & Interaction Triggers -->
                            <div class="settings-section">
                                <h3><i class="bi bi-chat-square-heart-fill"></i> Activity & Interaction Triggers</h3>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Replies & @Mentions</label>
                                        <p class="setting-desc">Get notified when another developer replies to your post or tags your username.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="notify_replies" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Post Likes & Upvotes</label>
                                        <p class="setting-desc">Get notified when someone upvotes or likes your technical post.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="notify_likes" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Trending Discussions in Followed Topics</label>
                                        <p class="setting-desc">Receive alerts for hot threads in topics like #WebDevelopment or #AI.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="notify_trending" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Newsletter & Product Releases</label>
                                        <p class="setting-desc">Receive monthly developer tools digests, cheat sheets, and platform updates.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="notify_newsletter" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Quiet Hours Schedule -->
                            <div class="settings-section">
                                <h3><i class="bi bi-moon-stars-fill"></i> Quiet Hours / Do Not Disturb Schedule</h3>
                                
                                <div class="setting-item">
                                    <div class="setting-info">
                                        <label>Enable Quiet Hours Schedule</label>
                                        <p class="setting-desc">Automatically mute non-urgent notifications during your sleeping or deep work hours.</p>
                                    </div>
                                    <div class="setting-control">
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="quiet_hours_enabled">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="quiet-hours-picker">
                                    <div class="form-group-inline">
                                        <label for="quiet_start">From:</label>
                                        <select id="quiet_start" name="quiet_start" class="form-select">
                                            <option value="22:00" selected>10:00 PM</option>
                                            <option value="23:00">11:00 PM</option>
                                            <option value="00:00">12:00 AM</option>
                                        </select>
                                    </div>
                                    <div class="form-group-inline">
                                        <label for="quiet_end">To:</label>
                                        <select id="quiet_end" name="quiet_end" class="form-select">
                                            <option value="07:00">07:00 AM</option>
                                            <option value="08:00" selected>08:00 AM</option>
                                            <option value="09:00">09:00 AM</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-save-notifications"><i class="bi bi-check2-circle"></i> Save Notification Settings</button>
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
    <script src="../script.js"></script>
    <script src="settings.js"></script>
</body>
</html>
