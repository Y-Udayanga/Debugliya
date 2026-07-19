<?php
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// getto the thereee recent posts)
$stmt = $pdo->prepare("
    SELECT p.id, p.content, u.username
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 3
");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resources = [
    [
        'name' => 'Tools',
        'description' => 'Essential developer tools like VS Code, Git, and Docker.',
        'image' => 'vs code.png',
        'link' => 'https://code.visualstudio.com/'
    ],
    [
        'name' => 'Tutorials',
        'description' => 'Learn coding with free tutorials from freeCodeCamp and more.',
        'image' => 'tutorials.jpg',
        'link' => 'https://www.freecodecamp.org/'
    ],
    [
        'name' => 'APIs',
        'description' => 'Explore public APIs for your projects.',
        'image' => 'api.webp',
        'link' => 'https://api.github.com/'
    ]
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Resources</title>
    <link rel="stylesheet" href="resources.css">
    <link rel="stylesheet" href="../ui-polish.css">
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
        <section class="hero" id="hero">
            <canvas id="particles"></canvas>
            <div class="hero-content">
                <h1>Developer Resources</h1>
                <p class="typewriter">Discover tools, tutorials, and APIs to elevate your coding journey.</p>
                <a href="#resources" class="btn btn-primary">Explore Resources</a>
                <a href="#resources" class="scroll-down"><i class="bi bi-chevron-down"></i></a>
            </div>
        </section>

        <section class="resources" id="resources">
            <div class="container">
                <h2>Curated Resources</h2>
                <div class="resource-grid">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-card" data-tilt>
                            <img src="<?php echo htmlspecialchars($resource['image']); ?>" alt="<?php echo htmlspecialchars($resource['name']); ?>">
                            <h3><?php echo htmlspecialchars($resource['name']); ?></h3>
                            <p><?php echo htmlspecialchars($resource['description']); ?></p>
                            <a href="<?php echo htmlspecialchars($resource['link']); ?>" target="_blank" class="btn btn-secondary">Learn More</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="featured-threads">
            <div class="container">
                <h2>Featured Forum Threads</h2>
                <div class="thread-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="thread-card" data-tilt>
                            <img src="https://source.unsplash.com/150x150/?forum,<?php echo $post['id']; ?>" alt="Thread">
                            <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : ''); ?></p>
                            <a href="../index.php" class="read-more">Join Discussion</a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="explorabtn">
                <a href="../index.php" class="btn btn-primary">Explore Forum</a>
            </div>
        </section>

        <section class="newsletter">
            <div class="container">
                <h2>Stay in the Loop</h2>
                <p>Subscribe to our newsletter for the latest resources and coding tips.</p>
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

    <script src="resources.js"></script>
</body>
</html>
