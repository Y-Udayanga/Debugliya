<?php
session_start();
require '../db_connect.php';

//  CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare("
    SELECT p.id, p.content, u.username
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 3
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, name FROM categories LIMIT 4");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT username, profile_photo FROM users ORDER BY RAND() LIMIT 1");
$stmt->execute();
$featured_user = $stmt->fetch(PDO::FETCH_ASSOC);

$community_images = [
    0 => 'Data-structures-and-algorithms-new.webp',
    1 => 'languages.jpg',
    2 => 'error handling.png',
    3 => 'systemdesign.png'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Welcome</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
        <section class="hero" id="hero">
            <canvas id="particles"></canvas>
            <div class="hero-content">
                <h1>Welcome to Debuglia, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Developer'; ?>!</h1>
                <p class="typewriter">Connect, code, and conquer challenges together.</p>
                <a href="../index.php" class="btn btn-primary">Join the Forum</a>
                <a href="#communities" class="scroll-down"><i class="bi bi-chevron-down"></i></a>
            </div>
        </section>

        <section class="communities" id="communities">
            <div class="container">
                <h2>Explore Our Communities</h2>
                <div class="community-grid">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="community-card" data-tilt>
                            <img src="<?php echo htmlspecialchars($community_images[$index]); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p>Discuss the latest in <?php echo htmlspecialchars($category['name']); ?> with experts.</p>
                            <a href="../index.php" class="btn btn-secondary">Join Now</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="featured-developer">
            <div class="container">
                <h2>Featured Developer</h2>
                <div class="developer-card">
                    <img src="<?php echo $featured_user['profile_photo'] ? '../uploads/' . htmlspecialchars($featured_user['profile_photo']) : 'https://source.unsplash.com/150x150/?portrait,developer'; ?>" alt="Developer">
                    <h3><?php echo htmlspecialchars($featured_user['username']); ?></h3>
                    <p>Join <?php echo htmlspecialchars($featured_user['username']); ?> in shaping the future of coding.</p>
                    <a href="../index.php" class="btn btn-secondary">View Profile</a>
                </div>
            </div>
        </section>

        <section class="forum-teaser">
            <div class="container">
                <h2>Recent Discussions</h2>
                <div class="post-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="post-card" data-tilt>
                            <i class="bi bi-code-slash post-icon"></i>
                            <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : ''); ?></p>
                            <a href="../index.php" class="read-more">Read More</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="forumbtn">
                <a href="../index.php" class="btn btn-secondary">Join the Conversation</a>
                </div>
            </div>
        </section>

        <section class="newsletter">
            <div class="container">
                <h2>Stay Updated</h2>
                 <p>Subscribe to our newsletter for the latest Debuglia updates and coding tips.</p>

                <form class="newsletter-form" action="#" method="post">
                   
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </section>
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
</body>
</html>