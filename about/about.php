<?php
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//  community stats
$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$post_count = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

$team_members = [
    [
        'name' => 'Alice Code',
        'role' => 'Founder & Visionary',
        'photo' => 'Alice Code.jpg',
        'bio' => 'Passionate about building inclusive developer ecosystems and collaborative platforms.'
    ],
    [
        'name' => 'Bob Debug',
        'role' => 'Lead Full-Stack Dev',
        'photo' => 'Lead Developer.jpg',
        'bio' => 'Architecting high-performance serverless backends and real-time community systems.'
    ],
    [
        'name' => 'Clara Script',
        'role' => 'Community Director',
        'photo' => 'Community Manager.jpg',
        'bio' => 'Empowering developers worldwide through events, open-source projects, and mentorship.'
    ]
];
$mission_image = 'Community Collaboration.jpg';
$timeline_images = [
    '2023' => 'image (2).jpg',
    '2024' => 'image.jpg',
    '2025' => 'image (1).jpg'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - About Us</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="../ui-polish.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
                <li><a href="../home/home.php">Home</a></li>
                <li><a href="../about/about.php" class="active">About</a></li>
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
                <h1>About Debuglia</h1>
                <p class="typewriter">Empowering developers to connect, learn, and innovate.</p>
                <a href="../index.php" class="btn btn-primary">Join Our Community</a>
            </div>
        </section>

        

        <section class="mission" id="mission">
            <div class="container">
                <h2>Our Mission & Core Pillars</h2>
                <div class="mission-content">
                    <p class="mission-text">At Debuglia, we're passionate about fostering a global community where developers can share knowledge, solve problems, and build the future of technology together.</p>
                    <div class="mission-image">
                        <img src="<?php echo htmlspecialchars($mission_image); ?>" alt="Community Collaboration">
                    </div>
                </div>

                <div class="values-grid">
                    <div class="value-card" data-tilt>
                        <div class="value-icon icon-blue"><i class="bi bi-globe2"></i></div>
                        <h3>Global Knowledge Sharing</h3>
                        <p>Connecting developers across borders to share solutions and learn from real-world debugging scenarios.</p>
                    </div>
                    <div class="value-card" data-tilt>
                        <div class="value-icon icon-purple"><i class="bi bi-lightning-charge-fill"></i></div>
                        <h3>Open Source Innovation</h3>
                        <p>Empowering creators with curated developer tools, tutorials, cheat sheets, and free API references.</p>
                    </div>
                    <div class="value-card" data-tilt>
                        <div class="value-icon icon-teal"><i class="bi bi-code-square"></i></div>
                        <h3>Peer Code Reviews</h3>
                        <p>Building a constructive space for code feedback, technical discussions, and collaborative troubleshooting.</p>
                    </div>
                    <div class="value-card" data-tilt>
                        <div class="value-icon icon-amber"><i class="bi bi-rocket-takeoff-fill"></i></div>
                        <h3>Inclusive Dev Growth</h3>
                        <p>Fostering an encouraging platform accessible to self-taught coders, students, and senior architects alike.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="team" id="team">
            <div class="container">
                <h2>Meet Our Team</h2>
                <div class="team-grid">
                    <?php foreach ($team_members as $member): ?>
                        <div class="team-card" data-tilt>
                            <div class="team-img-box">
                                <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo htmlspecialchars($member['name']); ?>">
                            </div>
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <span class="role-badge"><?php echo htmlspecialchars($member['role']); ?></span>
                            <p class="team-bio"><?php echo htmlspecialchars($member['bio']); ?></p>
                            <div class="team-socials">
                                <a href="#" aria-label="GitHub"><i class="bi bi-github"></i></a>
                                <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                <a href="#" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="community" id="community">
            <div class="container">
                <h2>Impact & Global Reach</h2>
                <div class="stats-banner-grid">
                    <div class="impact-stat-card" data-tilt>
                        <div class="impact-icon-badge icon-blue"><i class="bi bi-people-fill"></i></div>
                        <div class="impact-stat-details">
                            <h3><span class="counter" data-target="<?php echo $user_count > 0 ? $user_count : 1500; ?>">0</span>+</h3>
                            <p>Global Developers</p>
                        </div>
                    </div>
                    <div class="impact-stat-card" data-tilt>
                        <div class="impact-icon-badge icon-purple"><i class="bi bi-chat-left-text-fill"></i></div>
                        <div class="impact-stat-details">
                            <h3><span class="counter" data-target="<?php echo $post_count > 0 ? $post_count : 480; ?>">0</span>+</h3>
                            <p>Forum Discussions</p>
                        </div>
                    </div>
                    <div class="impact-stat-card" data-tilt>
                        <div class="impact-icon-badge icon-teal"><i class="bi bi-code-slash"></i></div>
                        <div class="impact-stat-details">
                            <h3><span class="counter" data-target="85">0</span>+</h3>
                            <p>Shared Projects</p>
                        </div>
                    </div>
                    <div class="impact-stat-card" data-tilt>
                        <div class="impact-icon-badge icon-amber"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="impact-stat-details">
                            <h3><span class="counter" data-target="99">0</span>%</h3>
                            <p>Resolved Queries</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="journey" id="journey">
            <div class="container">
                <h2>Our Evolution & Milestones</h2>
                <div class="timeline-container">
                    <div class="timeline-line"></div>

                    <div class="timeline-item" data-animate>
                        <div class="timeline-node"><i class="bi bi-lightbulb-fill"></i></div>
                        <div class="timeline-content" data-tilt>
                            <div class="timeline-header">
                                <span class="year-badge">2023</span>
                                <h3>Platform Foundations</h3>
                            </div>
                            <p>Debuglia was founded to connect developers worldwide, solving code bugs collaboratively.</p>
                            <div class="timeline-img-wrapper">
                                <img src="<?php echo htmlspecialchars($timeline_images['2023']); ?>" alt="Founded">
                            </div>
                        </div>
                    </div>

                    <div class="timeline-item" data-animate>
                        <div class="timeline-node"><i class="bi bi-chat-quote-fill"></i></div>
                        <div class="timeline-content" data-tilt>
                            <div class="timeline-header">
                                <span class="year-badge">2024</span>
                                <h3>Community & Forum Launch</h3>
                            </div>
                            <p>Launched the community forum, reaching over 1,000 active software engineers.</p>
                            <div class="timeline-img-wrapper">
                                <img src="<?php echo htmlspecialchars($timeline_images['2024']); ?>" alt="Forum Launch">
                            </div>
                        </div>
                    </div>

                    <div class="timeline-item" data-animate>
                        <div class="timeline-node"><i class="bi bi-stars"></i></div>
                        <div class="timeline-content" data-tilt>
                            <div class="timeline-header">
                                <span class="year-badge">2025</span>
                                <h3>Developer Ecosystem Expansion</h3>
                            </div>
                            <p>Expanded with Developer Resources, Cheatsheet Hubs, and global community channels.</p>
                            <div class="timeline-img-wrapper">
                                <img src="<?php echo htmlspecialchars($timeline_images['2025']); ?>" alt="Expansion">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta" id="cta">
            <div class="container">
                <div class="join-cta-card">
                    <h2>Want to Build Debuglia With Us?</h2>
                    <p>We are always looking for passionate developers, technical writers, and open-source contributors to grow our ecosystem.</p>
                    <div class="join-cta-actions">
                        <a href="../index.php" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Join Community</a>
                        <a href="mailto:careers@debuglia.com" class="btn btn-secondary-glass"><i class="bi bi-envelope-heart-fill"></i> Contact Team</a>
                    </div>
                </div>
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

    <script src="../script.js"></script>
    <script src="about.js"></script>
</body>
</html>
