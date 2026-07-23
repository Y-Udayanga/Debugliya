<?php
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch 3 recent posts with full context
$stmt = $pdo->prepare("
    SELECT p.id, p.content, p.created_at, p.category_id, c.name AS category,
           u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 3
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch 4 categories for community grid
$stmt = $pdo->prepare("SELECT id, name FROM categories LIMIT 4");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch a featured developer with details
$stmt = $pdo->prepare("
    SELECT id, username, profile_photo, bio, skills, location,
           (SELECT COUNT(*) FROM posts WHERE user_id = users.id) AS post_count
    FROM users 
    ORDER BY " . ($dbDriver === 'pgsql' ? 'RANDOM()' : 'RAND()') . " 
    LIMIT 1
");
$stmt->execute();
$featured_user = $stmt->fetch(PDO::FETCH_ASSOC);

$community_images = [
    0 => 'Data-structures-and-algorithms-new.webp',
    1 => 'languages.jpg',
    2 => 'error handling.png',
    3 => 'systemdesign.png'
];

function get_home_avatar($photo) {
    $photo = trim($photo ?? '');
    if ($photo === '' || $photo === 'blank-profile-picture.webp') {
        return '../blank-profile-picture.webp';
    }
    if (preg_match('~^https?://~i', $photo)) {
        return $photo;
    }
    return '../uploads/' . $photo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Debuglia - Developer Hub & Community</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../ui-polish.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
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
                    <li><a href="home.php" class="active">Home</a></li>
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
        <!-- Modernized Compact Hero Section -->
        <section class="hero-redesign" id="hero">
            <canvas id="particles"></canvas>
            <div class="hero-container">
                <div class="hero-left">
                    <span class="hero-badge"><i class="bi bi-stars"></i> The Ultimate Developer Platform</span>
                    <h1 class="hero-title">
                        Welcome back, <span class="gradient-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Developer'; ?></span>!
                    </h1>
                    <p class="hero-subtitle">Connect with passionate creators, share code snippets, get instant answers, and level up your software engineering skills.</p>
                    
                    <div class="hero-cta-group">
                        <a href="../index.php" class="btn btn-hero-primary"><i class="bi bi-chat-left-code-fill"></i> Join the Forum</a>
                        <a href="#communities" class="btn btn-hero-outline"><i class="bi bi-compass-fill"></i> Explore Communities</a>
                    </div>

                    <!-- Platform Quick Stats Bar -->
                    <div class="hero-stats-row">
                        <div class="h-stat-item">
                            <span class="h-stat-num">10k+</span>
                            <span class="h-stat-lbl">Active Devs</span>
                        </div>
                        <div class="h-stat-divider"></div>
                        <div class="h-stat-item">
                            <span class="h-stat-num">5k+</span>
                            <span class="h-stat-lbl">Discussions</span>
                        </div>
                        <div class="h-stat-divider"></div>
                        <div class="h-stat-item">
                            <span class="h-stat-num">100+</span>
                            <span class="h-stat-lbl">Tech Categories</span>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Interactive Code Terminal Card -->
                <div class="hero-right">
                    <div class="code-terminal-card">
                        <div class="terminal-header">
                            <div class="terminal-dots">
                                <span class="dot red"></span>
                                <span class="dot yellow"></span>
                                <span class="dot green"></span>
                            </div>
                            <span class="terminal-title"><i class="bi bi-terminal"></i> debuglia.config.js</span>
                            <span class="terminal-badge">LIVE</span>
                        </div>
                        <div class="terminal-body">
                            <pre><code><span class="token-keyword">const</span> debuglia = <span class="token-keyword">require</span>(<span class="token-string">'@debuglia/core'</span>);

<span class="token-comment">// Initialize Developer Environment</span>
<span class="token-keyword">async function</span> <span class="token-function">startSession</span>() {
  <span class="token-keyword">const</span> developer = <span class="token-keyword">await</span> debuglia.<span class="token-function">authenticate</span>();
  
  console.<span class="token-function">log</span>(<span class="token-string">`🚀 Welcome ${developer.username}!`</span>);
  <span class="token-keyword">return</span> debuglia.<span class="token-function">fetchTrendingDiscussions</span>();
}

<span class="token-function">startSession</span>();</code></pre>
                        </div>
                        <div class="terminal-footer">
                            <span class="terminal-status"><i class="bi bi-check-circle-fill"></i> Connected to Debuglia Cloud</span>
                            <button class="terminal-copy-btn" id="terminal-copy-btn" onclick="copyTerminalCode()"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Communities Section -->
        <section class="communities-section" id="communities">
            <div class="container-width">
                <div class="section-header text-center">
                    <span class="section-pill"><i class="bi bi-grid-fill"></i> Hubs</span>
                    <h2>Explore Our Communities</h2>
                    <p class="section-subtext">Join specialized discussion spaces tailored for algorithms, languages, system architecture, and debugging.</p>
                </div>
                <div class="community-grid-4">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="community-card-modern">
                            <div class="community-img-wrapper">
                                <img src="<?php echo htmlspecialchars($community_images[$index]); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                                <span class="comm-badge"><i class="bi bi-hash"></i> Topic</span>
                            </div>
                            <div class="community-card-body">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p>Discuss the latest in <?php echo htmlspecialchars($category['name']); ?> with community experts.</p>
                                <a href="../index.php" class="btn btn-comm-join"><i class="bi bi-arrow-right-circle-fill"></i> Join Hub</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Featured Developer Showcase -->
        <section class="featured-dev-section">
            <div class="container-width">
                <div class="section-header text-center">
                    <span class="section-pill gold"><i class="bi bi-award-fill"></i> Spotlight</span>
                    <h2>Featured Creator of the Week</h2>
                </div>
                <div class="dev-spotlight-card">
                    <div class="dev-banner-accent"></div>
                    <div class="dev-body">
                        <div class="dev-avatar-wrapper">
                            <img src="<?php echo htmlspecialchars(get_home_avatar($featured_user['profile_photo'] ?? null)); ?>" alt="<?php echo htmlspecialchars($featured_user['username'] ?? 'Developer'); ?>">
                            <span class="dev-online-ring" title="Active Contributor"></span>
                        </div>
                        <div class="dev-info">
                            <div class="dev-title-row">
                                <h3><?php echo htmlspecialchars($featured_user['username'] ?? 'Developer'); ?></h3>
                                <span class="dev-badge"><i class="bi bi-patch-check-fill"></i> Top Contributor</span>
                            </div>
                            <p class="dev-bio"><?php echo !empty($featured_user['bio']) ? htmlspecialchars($featured_user['bio']) : 'Passionate developer contributing solutions and sharing knowledge on Debuglia.'; ?></p>
                            
                            <div class="dev-meta-row">
                                <?php if (!empty($featured_user['location'])): ?>
                                    <span><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($featured_user['location']); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-file-earmark-code-fill"></i> <?php echo intval($featured_user['post_count'] ?? 0); ?> Posts</span>
                                <?php if (!empty($featured_user['skills'])): ?>
                                    <span><i class="bi bi-cpu-fill"></i> <?php echo htmlspecialchars($featured_user['skills']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="dev-actions">
                                <a href="../index.php" class="btn btn-primary btn-sm"><i class="bi bi-person-fill"></i> View Activity</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Discussions Grid -->
        <section class="discussions-section">
            <div class="container-width">
                <div class="section-header text-center">
                    <span class="section-pill blue"><i class="bi bi-chat-quote-fill"></i> Trending Feed</span>
                    <h2>Recent Discussions</h2>
                    <p class="section-subtext">Engage with community questions, code snippets, and technical solutions.</p>
                </div>
                <div class="posts-grid-3">
                    <?php if (empty($posts)): ?>
                        <div class="empty-feed-state">
                            <i class="bi bi-chat-dots empty-ico"></i>
                            <p>No recent discussions yet. Be the first to post!</p>
                            <a href="../index.php" class="btn btn-primary">Start Discussion</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card-modern">
                                <div class="post-card-header">
                                    <div class="author-meta">
                                        <img src="<?php echo htmlspecialchars(get_home_avatar($post['profile_photo'])); ?>" alt="Author Avatar" class="author-avatar">
                                        <div class="author-details">
                                            <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                            <small><?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <span class="post-category-tag"><?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'General'; ?></span>
                                </div>
                                <div class="post-card-body">
                                    <p><?php echo htmlspecialchars(substr($post['content'], 0, 120)) . (strlen($post['content']) > 120 ? '...' : ''); ?></p>
                                </div>
                                <div class="post-card-footer">
                                    <div class="post-stats-mini">
                                        <span><i class="bi bi-heart-fill"></i> <?php echo $post['like_count']; ?></span>
                                        <span><i class="bi bi-chat-square-text-fill"></i> <?php echo $post['comment_count']; ?></span>
                                    </div>
                                    <a href="../index.php" class="read-link">Join Discussion <i class="bi bi-arrow-right"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="text-center margin-top-lg">
                    <a href="../index.php" class="btn btn-hero-primary"><i class="bi bi-chat-square-dots-fill"></i> Explore All Forum Topics</a>
                </div>
            </div>
        </section>

        <!-- Newsletter Subscription Card -->
        <section class="newsletter-section">
            <div class="container-width">
                <div class="newsletter-card-modern">
                    <div class="newsletter-content">
                        <span class="newsletter-pill"><i class="bi bi-envelope-open-fill"></i> Stay Ahead</span>
                        <h2>Subscribe to Debuglia Digest</h2>
                        <p>Get weekly curated tech tutorials, popular discussions, and platform feature releases delivered straight to your inbox.</p>
                        
                        <div class="newsletter-features">
                            <span><i class="bi bi-check-circle-fill"></i> Weekly Digest</span>
                            <span><i class="bi bi-check-circle-fill"></i> Zero Spam</span>
                            <span><i class="bi bi-check-circle-fill"></i> Unsubscribe Anytime</span>
                        </div>

                        <form class="newsletter-form-modern" onsubmit="handleNewsletterSubmit(event)">
                            <div class="input-input-wrapper">
                                <i class="bi bi-envelope mail-icon"></i>
                                <input type="email" name="email" placeholder="Enter your email address..." required>
                            </div>
                            <button type="submit" class="btn btn-hero-primary"><i class="bi bi-send-fill"></i> Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
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
                    <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-github"></i></a>
                    <a href="#" class="social-icon"><i class="bi bi-linkedin"></i></a>
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

    <script src="home.js"></script>
    <script src="../script.js"></script>
</body>
</html>
