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
        'name' => 'VS Code & Dev Tools',
        'category' => 'Tools',
        'badge' => 'Essential',
        'description' => 'Essential developer tools like VS Code, Git, and Docker.',
        'image' => 'vs code.png',
        'link' => 'https://code.visualstudio.com/'
    ],
    [
        'name' => 'freeCodeCamp',
        'category' => 'Tutorials',
        'badge' => 'Free',
        'description' => 'Learn coding with free interactive tutorials and certifications.',
        'image' => 'tutorials.jpg',
        'link' => 'https://www.freecodecamp.org/'
    ],
    [
        'name' => 'GitHub REST & GraphQL API',
        'category' => 'APIs',
        'badge' => 'Popular',
        'description' => 'Explore GitHub public APIs and integration tools for software devs.',
        'image' => 'api.webp',
        'link' => 'https://api.github.com/'
    ],
    [
        'name' => 'DevDocs Documentation',
        'category' => 'Tools',
        'badge' => 'Docs',
        'description' => 'Fast, offline API documentation browser for all web & backend stacks.',
        'icon' => 'bi-journal-bookmark-fill',
        'link' => 'https://devdocs.io/'
    ],
    [
        'name' => 'Can I Use',
        'category' => 'Tools',
        'badge' => 'Utility',
        'description' => 'Up-to-date browser support tables for modern HTML5, CSS3, and JS APIs.',
        'icon' => 'bi-browser-chrome',
        'link' => 'https://caniuse.com/'
    ],
    [
        'name' => 'Public APIs Directory',
        'category' => 'APIs',
        'badge' => 'Curated',
        'description' => 'Collective index of free APIs for authentication, weather, AI, and finance.',
        'icon' => 'bi-hdd-network-fill',
        'link' => 'https://github.com/public-apis/public-apis'
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
                <li><a href="../home/home.php">Home</a></li>
                <li><a href="../about/about.php">About</a></li>
                <li><a href="../profile/profile.php">Profile</a></li>
                <li><a href="../index.php">Forum</a></li>
                <li><a href="../resources/resources.php" class="active">Resources</a></li>
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
            </div>
        </section>

        <!-- Glassmorphic Quick Stats Banner -->
        <section class="stats-banner">
            <div class="stats-container">
                <div class="stat-item">
                    <div class="stat-icon"><i class="bi bi-tools"></i></div>
                    <div class="stat-info">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">Curated Tools</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="bi bi-book-half"></i></div>
                    <div class="stat-info">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">Free Learning</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div class="stat-info">
                        <span class="stat-number">Instant</span>
                        <span class="stat-label">API References</span>
                    </div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-info">
                        <span class="stat-number">Active</span>
                        <span class="stat-label">Dev Community</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="resources" id="resources">
            <div class="container">
                <h2>Curated Resources</h2>
                
                <!-- Search & Category Filters -->
                <div class="resource-controls">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="resource-search" placeholder="Search tools, tutorials, APIs..." aria-label="Search resources">
                    </div>
                    <div class="filter-pills" role="tablist">
                        <button class="filter-btn active" data-filter="all"><i class="bi bi-grid-fill"></i> All</button>
                        <button class="filter-btn" data-filter="Tools"><i class="bi bi-tools"></i> Tools</button>
                        <button class="filter-btn" data-filter="Tutorials"><i class="bi bi-journal-code"></i> Tutorials</button>
                        <button class="filter-btn" data-filter="APIs"><i class="bi bi-code-slash"></i> APIs</button>
                    </div>
                </div>

                <div class="resource-grid">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-card" data-tilt data-category="<?php echo htmlspecialchars($resource['category']); ?>" data-name="<?php echo strtolower(htmlspecialchars($resource['name'])); ?>" data-desc="<?php echo strtolower(htmlspecialchars($resource['description'])); ?>">
                            <div class="resource-badge"><?php echo htmlspecialchars($resource['badge']); ?></div>
                            <?php if (isset($resource['image'])): ?>
                                <img src="<?php echo htmlspecialchars($resource['image']); ?>" alt="<?php echo htmlspecialchars($resource['name']); ?>">
                            <?php else: ?>
                                <div class="resource-icon-box">
                                    <i class="bi <?php echo htmlspecialchars($resource['icon']); ?>"></i>
                                </div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($resource['name']); ?></h3>
                            <p><?php echo htmlspecialchars($resource['description']); ?></p>
                            <a href="<?php echo htmlspecialchars($resource['link']); ?>" target="_blank" class="btn btn-secondary">Learn More</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Developer Cheat Sheets Section -->
        <section class="cheatsheets-section" id="cheatsheets">
            <div class="container">
                <h2><i class="bi bi-code-square"></i> Developer Quick References</h2>
                <p class="section-subtitle">Copy-paste essential commands and code snippets in one click.</p>
                
                <div class="snippet-grid">
                    <div class="snippet-card">
                        <div class="snippet-header">
                            <span class="snippet-tag tag-git"><i class="bi bi-git"></i> Git</span>
                            <h4>Undo Last Commit (Keep Changes)</h4>
                        </div>
                        <div class="snippet-code-box">
                            <code>git reset --soft HEAD~1</code>
                            <button class="copy-btn" data-copy="git reset --soft HEAD~1"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>

                    <div class="snippet-card">
                        <div class="snippet-header">
                            <span class="snippet-tag tag-css"><i class="bi bi-filetype-css"></i> CSS Grid</span>
                            <h4>Responsive Auto-Fit Grid</h4>
                        </div>
                        <div class="snippet-code-box">
                            <code>grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));</code>
                            <button class="copy-btn" data-copy="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>

                    <div class="snippet-card">
                        <div class="snippet-header">
                            <span class="snippet-tag tag-docker"><i class="bi bi-box-seam"></i> Docker</span>
                            <h4>Clean Unused Containers & Images</h4>
                        </div>
                        <div class="snippet-code-box">
                            <code>docker system prune -a --volumes</code>
                            <button class="copy-btn" data-copy="docker system prune -a --volumes"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>

                    <div class="snippet-card">
                        <div class="snippet-header">
                            <span class="snippet-tag tag-js"><i class="bi bi-filetype-js"></i> JavaScript</span>
                            <h4>Async Fetch with Error Check</h4>
                        </div>
                        <div class="snippet-code-box">
                            <code>const res = await fetch(url); if(!res.ok) throw new Error();</code>
                            <button class="copy-btn" data-copy="const res = await fetch(url); if(!res.ok) throw new Error();"><i class="bi bi-clipboard"></i> Copy</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="featured-threads">
            <div class="container">
                <h2>Featured Forum Threads</h2>
                <div class="thread-grid">
                    <?php foreach ($posts as $post): ?>
                        <div class="thread-card" data-tilt>
                            <div class="thread-header">
                                <div class="thread-avatar">
                                    <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                </div>
                                <h3><?php echo htmlspecialchars($post['username']); ?></h3>
                            </div>
                            <p class="thread-content">"<?php echo htmlspecialchars(substr($post['content'], 0, 100)) . (strlen($post['content']) > 100 ? '...' : ''); ?>"</p>
                            <div class="thread-footer">
                                <a href="../index.php" class="read-more">Join Discussion <i class="bi bi-arrow-right-short"></i></a>
                            </div>
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
