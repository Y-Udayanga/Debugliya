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
    SELECT c.id, c.name, COUNT(p.id) AS post_count
    FROM categories c
    LEFT JOIN posts p ON c.id = p.category_id
    GROUP BY c.id
    ORDER BY post_count DESC
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$posts = [];
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$query = "
    SELECT p.*, u.username, u.profile_photo, c.name AS category_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
";
$params = [];
if ($category_id) {
    $query .= " WHERE p.category_id = ?";
    $params[] = $category_id;
}
$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Trending Topics</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="trending_topic.css">
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
                <a class="profile" href="../Profile/profile.php">
                    <div class="profile-photo">
                        <img src="<?php echo $user['profile_photo'] ? '../Uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
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
                    <a class="menu-item" href="../notification/notification.php">
                        <span><i class="bi bi-bell-fill"></i></span><h3>Notifications</h3>
                    </a>
                    <a class="menu-item active" href="../trending_topic/trending_topic.php">
                        <span><i class="bi bi-chat-fill"></i></span><h3>Trending Topics</h3>
                    </a>
                    <a class="menu-item" href="../bookmarK/bookmark.php">
                        <span><i class="bi bi-bookmarks"></i></span><h3>Bookmarks</h3>
                    </a>
                    <a class="menu-item" href="../analytics/analytics.php">
                        <span><i class="bi bi-clipboard2-data"></i></span><h3>Analytics</h3>
                    </a>
                    <a class="menu-item" id="theme">
                        <span><i class="bi bi-palette-fill"></i></span><h3>Theme</h3>
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
                <div class="feeds" id="category-posts">
                    <?php if (empty($posts)): ?>
                        <h2>No posts available. Select a category to view posts.</h2>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="feed">
                                <div class="category-tag"><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></div>
                                <div class="head">
                                    <div class="user">
                                        <div class="profile-photo">
                                            <img src="<?php echo $post['profile_photo'] ? '../Uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
                                        </div>
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                                            <small><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="post-content">
                                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                                </div>
                                <?php if (!empty($post['image'])): ?>
                                    <div class="post-images single-image">
                                        <img src="../Uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
                                    </div>
                                <?php endif; ?>
                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <span class="like-btn" data-post-id="<?php echo $post['id']; ?>"><i class="bi bi-heart"></i> <span class="like-count"><?php echo $post['likes'] ?? 0; ?></span></span>
                                        <span><i class="bi bi-chat"></i> <span class="comment-count"><?php echo $post['comments'] ?? 0; ?></span></span>
                                    </div>
                                </div>
                                <div class="comments-section" id="comments-<?php echo $post['id']; ?>">
                                    <!-- Comments will be loaded via JavaScript -->
                                </div>
                                <div class="comment-form">
                                    <input type="text" class="comment-input" placeholder="Add a comment..." data-post-id="<?php echo $post['id']; ?>">
                                    <button class="btn btn-primary comment-submit">Post</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right">
                <div class="trending-topic">
                    <div class="heading">
                        <h4>Categories</h4>
                    </div>
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search Categories" id="category-search">
                    </div>
                    <ul class="category">
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="?category_id=<?php echo $category['id']; ?>" class="category-link" data-category-id="<?php echo $category['id']; ?>">
                                    #<?php echo htmlspecialchars($category['name']); ?>
                                    <span class="post-count"><?php echo $category['post_count']; ?> posts</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
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
                    <li><a href="../Profile/profile.php">Profile</a></li>
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
    <script src="trending_topic.js"></script>
</body>
</html>
