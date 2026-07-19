<?php
session_start();
require '../db_connect.php';

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

$stmt = $pdo->prepare("
    SELECT n.id, n.actor_id, n.post_id, n.type, n.content, n.is_read, n.created_at,
           u.username, u.profile_photo AS actor_photo
    FROM notifications n
    JOIN users u ON n.actor_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

// Mark notifications as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Notifications</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../profile_settings.css">
    <link rel="stylesheet" href="notification.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <header class="navbar">
    <div class="containers">
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
                    <a class="menu-item" href="../explora/explora.php">
                        <span><i class="bi bi-compass"></i></span><h3>Explore</h3>
                    </a>
                    <a class="menu-item active" href="../notification/notification.php">
                        <span><i class="bi bi-bell-fill"></i></span><h3>Notifications</h3>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-count"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="menu-item" href="../trending_topic/trending_topic.php">
                        <span><i class="bi bi-chat-fill"></i></span><h3>Trending Topics</h3>
                    </a>
                    <a class="menu-item" href="../bookmark/bookmark.php">
                        <span><i class="bi bi-bookmarks"></i></span><h3>Bookmarks</h3>
                    </a>
                    <a class="menu-item" href="../analytics/analytics.php">
                        <span><i class="bi bi-clipboard2-data"></i></span><h3>Analytics</h3>
                    </a>
                    
                    <a class="menu-item" href="../setting/setting.php">
                        <span><i class="bi bi-gear"></i></span><h3>Settings</h3>
                    </a>
                    <a class="menu-item" href="../logout.php">
                        <span><i class="bi bi-box-arrow-right"></i></span><h3>Logout</h3>
                    </a>
                </div>
            </div>

            <div class="middle">
                <div class="notifications-list" style="background: var(--color-white); padding: var(--card-padding); border-radius: var(--card-border-radius);">
                    <h2>Notifications</h2>
                    <?php if (empty($notifications)): ?>
                        <p>No notifications yet.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification <?php echo $notification['is_read'] ? '' : 'unread'; ?>" data-post-id="<?php echo $notification['post_id']; ?>">
                                <div class="profile-photo">
                                    <img src="<?php echo $notification['actor_photo'] ? '../uploads/' . htmlspecialchars($notification['actor_photo']) : '../blank-profile-picture.webp'; ?>" alt="Actor Photo">
                                </div>
                                <div class="notification-content">
                                    <p>
                                        <strong><?php echo htmlspecialchars($notification['username']); ?></strong>
                                        <?php echo $notification['type'] === 'like' ? 'liked your post' : 'commented on your post'; ?>
                                        <?php if ($notification['type'] === 'comment'): ?>
                                            : "<?php echo htmlspecialchars($notification['content']); ?>"
                                        <?php endif; ?>
                                    </p>
                                    <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right">
                <div class="trending-topic">
                    <div class="heading">
                        <h4>Trending Topics</h4>
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search Trending Topics" id="trending-topic">
                    </div>
                    <ul class="category">
                        <li><a href="#" class="text-[var(--primary-color)] hover:underline">#Technology</a></li>
                        <li><a href="#" class="text-[var(--primary-color)] hover:underline">#Programming</a></li>
                        <li><a href="#" class="text-[var(--primary-color)] hover:underline">#WebDevelopment</a></li>
                        <li><a href="#" class="text-[var(--primary-color)] hover:underline">#AI</a></li>
                        <li><a href="#" class="text-[var(--primary-color)] hover:underline">#CloudComputing</a></li>
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
                    <li><a href="../setting/setting.php">Settings</a></li>
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
    </script>
    <script src="../script.js"></script>
    <script src="notification.js"></script>
</body>
</html>