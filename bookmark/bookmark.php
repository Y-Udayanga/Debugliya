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

// get bookmarked posts 
$stmt = $pdo->prepare("
    SELECT p.id, p.user_id, p.content, p.created_at, p.category_id, c.name AS category,
           u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) AS comment_count,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked,
           (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ?) AS is_bookmarked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN bookmarks b ON p.id = b.post_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get toimages for each post
$post_images = [];
foreach ($posts as $post) {
    $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
    $stmt->execute([$post['id']]);
    $post_images[$post['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Bookmarks</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="bookmark.css">
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
                    <a class="menu-item active" href="../bookmark/bookmark.php">
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
                <!-- Bookmarks Hero Card -->
                <div class="bookmark-hero-card">
                    <div class="hero-text-content">
                        <span class="hero-badge"><i class="bi bi-bookmark-star-fill"></i> Saved Archive</span>
                        <h1>Your Saved Bookmarks</h1>
                        <p>Access and review your saved technical posts, code snippets, and discussion guides.</p>
                    </div>
                    <div class="hero-status-box">
                        <span class="bookmarks-count-pill" id="saved-count-pill"><i class="bi bi-bookmarks-fill"></i> <?php echo count($posts); ?> Saved Items</span>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="bookmark-search-wrapper">
                    <div class="search-bar">
                        <i class="bi bi-search search-icon"></i>
                        <input type="search" placeholder="Search saved bookmarks by keyword or user..." id="bookmark-search-input">
                        <button class="clear-search-btn" id="clear-bookmark-search" style="display: none;" aria-label="Clear search">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Feeds Container -->
                <div class="feeds" id="bookmark-feeds-list">
                    <?php if (empty($posts)): ?>
                        <div class="bookmark-empty-card" id="bookmark-empty-state">
                            <div class="empty-icon-box"><i class="bi bi-bookmark-dash"></i></div>
                            <h3>No Bookmarks Saved</h3>
                            <p>Click the bookmark ribbon on any forum post to save it here for easy reference anytime.</p>
                            <a href="../index.php" class="btn btn-primary btn-sm"><i class="bi bi-compass"></i> Explore Forum Posts</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="feed" data-post-id="<?php echo $post['id']; ?>" data-category="<?php echo htmlspecialchars($post['category'] ?? ''); ?>">
                                <div class="feed-header-top">
                                    <span class="category-pill-badge"><i class="bi bi-tag-fill"></i> <?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'General'; ?></span>
                                    <span class="saved-ribbon-tag"><i class="bi bi-bookmark-check-fill"></i> Saved</span>
                                </div>

                                <div class="head">
                                    <div class="user">
                                        <div class="profile-photo">
                                            <img src="<?php echo $post['profile_photo'] ? '../uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="User Photo">
                                        </div>
                                        <div class="info">
                                            <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                                            <small><i class="bi bi-clock"></i> <?php echo date('M d, Y • H:i', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                        <span class="edit">
                                            <div class="post-menu">
                                                <button class="delete-post-btn" data-post-id="<?php echo $post['id']; ?>"><i class="bi bi-trash"></i> Delete</button>
                                            </div>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="caption">
                                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                                </div>
                                <?php if (!empty($post_images[$post['id']])): ?>
                                    <div class="photo-gallery <?php echo count($post_images[$post['id']]) === 1 ? 'single-image' : ''; ?>">
                                        <?php foreach ($post_images[$post['id']] as $image): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($image); ?>" alt="Post Image" class="post-image">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="action-buttons">
                                    <div class="interaction-buttons">
                                        <span class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="bi <?php echo $post['user_liked'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                            <span class="like-count"><?php echo $post['like_count']; ?></span>
                                        </span>
                                        <span class="comment-btn">
                                            <i class="bi bi-chat-square-dots"></i>
                                            <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                        </span>
                                        <span class="share-btn">
                                            <i class="bi bi-share"></i>
                                        </span>
                                    </div>
                                    <div class="bookmark">
                                        <button class="btn-remove-bookmark bookmark-btn bookmarked" data-post-id="<?php echo $post['id']; ?>" title="Remove Bookmark">
                                            <i class="bi bi-bookmark-x-fill"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="comments-section" style="display: none;">
                                    <form class="comment-form">
                                        <input type="text" placeholder="Add a comment..." class="comment-input">
                                        <button type="submit" class="btn btn-primary">Comment</button>
                                    </form>
                                    <div class="comments-list">
                                        <!-- Comments will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="bookmark-empty-card" id="bookmark-empty-state" style="display: none;">
                            <div class="empty-icon-box"><i class="bi bi-bookmark-dash"></i></div>
                            <h3>No Bookmarks Saved</h3>
                            <p>Click the bookmark ribbon on any forum post to save it here for easy reference anytime.</p>
                            <a href="../index.php" class="btn btn-primary btn-sm"><i class="bi bi-compass"></i> Explore Forum Posts</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right">
                <div class="sidebar-card trending-sidebar-card mb-4">
                    <div class="card-header-box">
                        <h3><i class="bi bi-hash"></i> Trending Topics</h3>
                        <i class="bi bi-fire text-coral"></i>
                    </div>
                    <div class="sidebar-search-box">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search Topics..." id="trending-topic-search">
                    </div>
                    <div class="topic-tags-list" id="topic-tags-list">
                        <a href="../trending_topic/trending_topic.php" class="topic-tag">#Technology</a>
                        <a href="../trending_topic/trending_topic.php" class="topic-tag">#Programming</a>
                        <a href="../trending_topic/trending_topic.php" class="topic-tag">#WebDevelopment</a>
                        <a href="../trending_topic/trending_topic.php" class="topic-tag">#AI</a>
                        <a href="../trending_topic/trending_topic.php" class="topic-tag">#CloudComputing</a>
                    </div>
                </div>

                <div class="sidebar-card communities-sidebar-card">
                    <div class="card-header-box">
                        <h3><i class="bi bi-people-fill"></i> Communities</h3>
                        <span class="badge-count">Featured</span>
                    </div>
                    <div class="communities-list">
                        <div class="community-item-card">
                            <div class="community-header">
                                <span class="community-emoji">🏢</span>
                                <div class="community-meta">
                                    <h4>Microsoft Azure</h4>
                                    <span class="community-members"><i class="bi bi-people"></i> 26 Members</span>
                                </div>
                            </div>
                            <p class="community-desc">A collective for developers to engage, share, and learn about Microsoft Azure.</p>
                            <button class="btn-community-join" onclick="toggleJoinCommunity(this)"><i class="bi bi-plus-lg"></i> Join</button>
                        </div>
                        <div class="community-item-card">
                            <div class="community-header">
                                <span class="community-emoji">💻</span>
                                <div class="community-meta">
                                    <h4>React Developers</h4>
                                    <span class="community-members"><i class="bi bi-people"></i> 42 Members</span>
                                </div>
                            </div>
                            <p class="community-desc">Join React enthusiasts to discuss components, hooks, and performance.</p>
                            <button class="btn-community-join" onclick="toggleJoinCommunity(this)"><i class="bi bi-plus-lg"></i> Join</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="customize-theme">
        <div class="card">
            <h2>Customize Your View</h2>
            <p class="text-muted">Manage your font size, color, and background</p>
            <div class="font-size">
                <h4>Font Size</h4>
                <div>
                    <h6>Aa</h6>
                    <div class="choose-size">
                        <span class="font-size-1"></span>
                        <span class="font-size-2 active"></span>
                        <span class="font-size-3"></span>
                        <span class="font-size-4"></span>
                        <span class="font-size-5"></span>
                    </div>
                    <h3>Aa</h3>
                </div>
            </div>
            <div class="color">
                <h4>Color</h4>
                <div class="choose-color">
                    <span class="color-1 active"></span>
                    <span class="color-2"></span>
                    <span class="color-3"></span>
                    <span class="color-4"></span>
                    <span class="color-5"></span>
                </div>
            </div>
            <div class="background">
                <h4>Background</h4>
                <div class="choose-bg">
                    <div class="bg-1 active">
                        <span></span>
                        <h5>Light</h5>
                    </div>
                    <div class="bg-2">
                        <span></span>
                        <h5>Dim</h5>
                    </div>
                    <div class="bg-3">
                        <span></span>
                        <h5>Lights Out</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="image-modal" style="display: none;">
        <span id="close-image-modal">×</span>
        <img id="modal-image">
    </div>

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
    <script src="bookmark.js"></script>
</body>
</html>
