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

$user = [
    'username' => $_SESSION['username'],
    'profile_photo' => $_SESSION['profile_photo']
];

$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
        (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?) AS like_count,
        (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?) AS comment_count
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$analytics = $stmt->fetch(PDO::FETCH_ASSOC);

// geting posts by category
$stmt = $pdo->prepare("
    SELECT c.name AS category, COUNT(p.id) AS post_count
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    GROUP BY c.id, c.name
");
$stmt->execute([$_SESSION['user_id']]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// getto post activity (last 30 days)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS date, COUNT(*) AS post_count
    FROM posts
    WHERE user_id = ? AND created_at >= " . ($dbDriver === 'pgsql' ? "CURRENT_DATE - INTERVAL '30 days'" : "DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . "
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$_SESSION['user_id']]);
$activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Analytics</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="analytics.css">
    <link rel="stylesheet" href="../ui-polish.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a class="menu-item" id="Notifications" href="../notification/notification.php">
                        <span><i class="bi bi-bell-fill"></i></span><h3>Notifications</h3>
                    </a>
                    <a class="menu-item" id="Trending Topics" href="../trending_topic/trending_topic.php">
                        <span><i class="bi bi-chat-fill"></i></span><h3>Trending Topics</h3>
                    </a>
                    <a class="menu-item" href="../bookmark/bookmark.php">
                        <span><i class="bi bi-bookmarks"></i></span><h3>Bookmarks</h3>
                    </a>
                    <a class="menu-item active" href="analytics.php">
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
                <div class="analytics-header">
                    <h2>Your Analytics Dashboard</h2>
                    <button id="refresh-data" class="btn btn-primary">Refresh Data</button>
                </div>
                <div class="analytics-cards">
                    <div class="card">
                        <h3>Total Posts</h3>
                        <p class="count"><?php echo $analytics['post_count']; ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Likes</h3>
                        <p class="count"><?php echo $analytics['like_count']; ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Comments</h3>
                        <p class="count"><?php echo $analytics['comment_count']; ?></p>
                    </div>
                </div>
                <div class="analytics-chart">
                    <h3>Post Activity (Last 30 Days)</h3>
                    <canvas id="activityChart"></canvas>
                </div>
                <div class="analytics-categories">
                    <h3>Posts by Category</h3>
                    <ul class="category-list">
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <span class="category-name"><?php echo htmlspecialchars($category['category'] ?: 'No Category'); ?></span>
                                <span class="category-count"><?php echo $category['post_count']; ?> posts</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
                <div class="communities">
                    <h3 class="font-semibold mb-3">Communities</h3>
                    <div class="community-item mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🏢</span>
                            <h4 class="font-semibold">Microsoft Azure</h4>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">26 Members</p>
                        <p class="text-sm mt-1">A collective for developers to engage, share, and learn about Microsoft Azure.</p>
                        <button class="btn-join">Join</button>
                    </div>
                    <div class="community-item mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">💻</span>
                            <h4 class="font-semibold">React Developers</h4>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">42 Members</p>
                        <p class="text-sm mt-1">Join React enthusiasts to discuss components, hooks, and more.</p>
                        <button class="btn-join">Join</button>
                    </div>
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
                    <li><a href="analytics.php">Analytics</a></li>
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
        window.analyticsData = {
            activity: <?php echo json_encode($activity); ?>,
            categories: <?php echo json_encode($categories); ?>,
            counts: <?php echo json_encode($analytics); ?>
        };
    </script>
    <script src="../script.js"></script>
    <script src="analytics.js"></script>
</body>
</html>
