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

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
        (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?) AS like_count,
        (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?) AS comment_count,
        (SELECT COUNT(*) FROM bookmarks b JOIN posts p ON b.post_id = p.id WHERE p.user_id = ?) AS bookmark_count
");
$stmt->execute([$userId, $userId, $userId, $userId]);
$analytics = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['post_count' => 0, 'like_count' => 0, 'comment_count' => 0, 'bookmark_count' => 0];

$posts = (int)$analytics['post_count'];
$interactions = (int)$analytics['like_count'] + (int)$analytics['comment_count'];
$analytics['engagement_rate'] = $posts > 0 ? round(($interactions / $posts) * 100, 1) : 0;

// Categories breakdown
$stmt = $pdo->prepare("
    SELECT COALESCE(c.name, 'Uncategorized') AS category, COUNT(p.id) AS post_count
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    GROUP BY c.id, c.name
    ORDER BY post_count DESC
");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Post activity (last 30 days default)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS date, COUNT(*) AS post_count
    FROM posts
    WHERE user_id = ? AND created_at >= " . ($dbDriver === 'pgsql' ? "CURRENT_DATE - INTERVAL '30 days'" : "DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . "
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$userId]);
$activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most liked / top performing post
$stmt = $pdo->prepare("
    SELECT p.id, p.content, p.created_at,
           (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    WHERE p.user_id = ?
    ORDER BY like_count DESC, comment_count DESC, p.created_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$most_liked_post = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

// Recent activity timeline
$stmt = $pdo->prepare("
    (SELECT 'post' AS type, content, created_at, id AS ref_id FROM posts WHERE user_id = ?)
    UNION ALL
    (SELECT 'comment' AS type, content, created_at, post_id AS ref_id FROM comments WHERE user_id = ?)
    ORDER BY created_at DESC
    LIMIT 6
");
$stmt->execute([$userId, $userId]);
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <div class="analytics-dashboard">
                    <!-- Header Action Bar -->
                    <div class="analytics-header">
                        <div class="analytics-header-title">
                            <h2><i class="bi bi-graph-up-arrow"></i> Analytics Dashboard</h2>
                            <p>Track performance, audience engagement, and community growth.</p>
                        </div>
                        <div class="analytics-actions">
                            <select id="timeframe-select" class="timeframe-select" aria-label="Select timeframe">
                                <option value="7">Last 7 Days</option>
                                <option value="30" selected>Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                            <button id="export-report" class="btn-analytics btn-analytics-secondary">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <button id="refresh-data" class="btn-analytics btn-analytics-primary">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- 5 KPI Stat Cards Grid -->
                    <div class="analytics-cards-grid">
                        <div class="analytics-stat-card" style="--card-accent-from:#3b82f6; --card-accent-to:#1d4ed8;">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Posts</span>
                                <div class="stat-card-icon icon-posts"><i class="bi bi-file-earmark-text-fill"></i></div>
                            </div>
                            <div class="stat-card-value" id="stat-posts"><?php echo (int)$analytics['post_count']; ?></div>
                            <div class="stat-card-footer">
                                <span class="badge-growth"><i class="bi bi-person-fill"></i> Posts</span>
                                <span>Published</span>
                            </div>
                        </div>

                        <div class="analytics-stat-card" style="--card-accent-from:#ec4899; --card-accent-to:#be185d;">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Likes</span>
                                <div class="stat-card-icon icon-likes"><i class="bi bi-heart-fill"></i></div>
                            </div>
                            <div class="stat-card-value" id="stat-likes"><?php echo (int)$analytics['like_count']; ?></div>
                            <div class="stat-card-footer">
                                <span class="badge-growth"><i class="bi bi-hand-thumbs-up-fill"></i> Likes</span>
                                <span>Received</span>
                            </div>
                        </div>

                        <div class="analytics-stat-card" style="--card-accent-from:#10b981; --card-accent-to:#047857;">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Total Comments</span>
                                <div class="stat-card-icon icon-comments"><i class="bi bi-chat-left-dots-fill"></i></div>
                            </div>
                            <div class="stat-card-value" id="stat-comments"><?php echo (int)$analytics['comment_count']; ?></div>
                            <div class="stat-card-footer">
                                <span class="badge-growth"><i class="bi bi-chat-fill"></i> Comments</span>
                                <span>Replies</span>
                            </div>
                        </div>

                        <div class="analytics-stat-card" style="--card-accent-from:#f59e0b; --card-accent-to:#d97706;">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Engagement Rate</span>
                                <div class="stat-card-icon icon-engagement"><i class="bi bi-lightning-charge-fill"></i></div>
                            </div>
                            <div class="stat-card-value" id="stat-engagement"><?php echo $analytics['engagement_rate']; ?>%</div>
                            <div class="stat-card-footer">
                                <span class="badge-growth"><i class="bi bi-speedometer2"></i> Ratio</span>
                                <span>Per post</span>
                            </div>
                        </div>

                        <div class="analytics-stat-card" style="--card-accent-from:#8b5cf6; --card-accent-to:#6d28d9;">
                            <div class="stat-card-header">
                                <span class="stat-card-title">Bookmarks Saved</span>
                                <div class="stat-card-icon icon-bookmarks"><i class="bi bi-bookmark-star-fill"></i></div>
                            </div>
                            <div class="stat-card-value" id="stat-bookmarks"><?php echo (int)$analytics['bookmark_count']; ?></div>
                            <div class="stat-card-footer">
                                <span class="badge-growth"><i class="bi bi-star-fill"></i> Saved</span>
                                <span>Bookmarks</span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Grid: Activity Chart & Category Breakdown -->
                    <div class="analytics-main-grid">
                        <div class="analytics-card-block">
                            <div class="block-header">
                                <h3><i class="bi bi-activity"></i> Post Activity Trend</h3>
                                <span class="text-xs text-gray-500 font-medium" id="activity-period-label">Last 30 Days</span>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="activityChart"></canvas>
                            </div>
                        </div>

                        <div class="analytics-card-block">
                            <div class="block-header">
                                <h3><i class="bi bi-pie-chart-fill"></i> Posts by Category</h3>
                            </div>
                            <div class="category-card-body">
                                <div class="category-chart-container">
                                    <canvas id="categoryChart"></canvas>
                                </div>
                                <ul class="category-progress-list" id="category-progress-list">
                                    <?php 
                                    $totalPostSum = array_sum(array_column($categories, 'post_count')) ?: 1;
                                    foreach ($categories as $cat): 
                                        $pct = round(($cat['post_count'] / $totalPostSum) * 100);
                                    ?>
                                        <li class="category-progress-item">
                                            <div class="category-info">
                                                <span class="category-name-tag"><?php echo htmlspecialchars($cat['category']); ?></span>
                                                <span class="category-count-badge"><?php echo (int)$cat['post_count']; ?> (<?php echo $pct; ?>%)</span>
                                            </div>
                                            <div class="progress-bar-bg">
                                                <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Grid: Top Performing Post & Recent Activity Stream -->
                    <div class="analytics-bottom-grid">
                        <div class="analytics-card-block spotlight-card">
                            <span class="spotlight-badge"><i class="bi bi-trophy-fill"></i> Top Post</span>
                            <div class="block-header">
                                <h3><i class="bi bi-star-fill"></i> Most Liked Contribution</h3>
                            </div>
                            <div class="spotlight-body" id="spotlight-container">
                                <?php if ($most_liked_post): ?>
                                    <p class="spotlight-text">"<?php echo htmlspecialchars(mb_strimwidth($most_liked_post['content'], 0, 140, '...')); ?>"</p>
                                    <div class="spotlight-stats">
                                        <span class="spotlight-stat likes"><i class="bi bi-heart-fill"></i> <?php echo (int)$most_liked_post['like_count']; ?> likes</span>
                                        <span class="spotlight-stat comments"><i class="bi bi-chat-dots-fill"></i> <?php echo (int)$most_liked_post['comment_count']; ?> comments</span>
                                    </div>
                                <?php else: ?>
                                    <p class="spotlight-text text-gray-400">No posts published yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="analytics-card-block">
                            <div class="block-header">
                                <h3><i class="bi bi-clock-history"></i> Recent Activity Stream</h3>
                            </div>
                            <div class="timeline-stream" id="timeline-stream">
                                <?php if (!empty($recent_activity)): ?>
                                    <?php foreach ($recent_activity as $act): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-icon <?php echo $act['type'] === 'post' ? 'type-post' : 'type-comment'; ?>">
                                                <i class="bi <?php echo $act['type'] === 'post' ? 'bi-file-earmark-plus' : 'bi-chat-left-text'; ?>"></i>
                                            </div>
                                            <div class="timeline-content">
                                                <span class="timeline-title"><?php echo $act['type'] === 'post' ? 'Published a new post' : 'Added a comment'; ?></span>
                                                <span class="timeline-snippet"><?php echo htmlspecialchars(mb_strimwidth($act['content'], 0, 70, '...')); ?></span>
                                                <span class="timeline-time"><?php echo date('M d, Y • g:i a', strtotime($act['created_at'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-400">No recent activity recorded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
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
            counts: <?php echo json_encode($analytics); ?>,
            most_liked_post: <?php echo json_encode($most_liked_post); ?>,
            recent_activity: <?php echo json_encode($recent_activity); ?>
        };
    </script>
    <script src="../script.js"></script>
    <script src="analytics.js"></script>
</body>
</html>
