<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$post_id = (int)$_GET['id'];
$stmt = $pdo->prepare("
    SELECT p.id, p.content, p.created_at, p.user_id, p.category_id,
           u.username, u.profile_photo,
           c.name AS category_name,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments cm WHERE cm.post_id = p.id) AS comment_count,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$user_id, $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
$stmt->execute([$post_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debuglia - Post</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body style="
    margin: 0;
    font-family: 'Poppins', sans-serif;
    font-size: 16px;
    background: #f7f7f7;
    color: #1a1a1a;
    --color-white: #fff;
    --color-dark: #1a1a1a;
    --color-gray: #7a7a7a;
    --color-light: #e0e0e0;
    --primary-color: hsl(252, 75%, 60%);
    --box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    --border-radius-1: 0.4rem;
    --card-padding: 1.5rem;
    --padding-2: 1rem;
    --sticky-top-left: -2rem;
    --sticky-top-right: -17rem;
">
    <header style="
        background: var(--color-white);
        box-shadow: var(--box-shadow);
        position: sticky;
        top: 0;
        z-index: 1000;
        padding: 1rem 0;
    ">
        <div style="
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        ">
            <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">Debuglia</div>
            <button style="
                display: none;
                background: none;
                border: none;
                cursor: pointer;
                flex-direction: column;
                gap: 0.3rem;
            " class="hamburger" aria-label="Toggle navigation">
                <span style="width: 1.5rem; height: 0.2rem; background: var(--color-dark);"></span>
                <span style="width: 1.5rem; height: 0.2rem; background: var(--color-dark);"></span>
                <span style="width: 1.5rem; height: 0.2rem; background: var(--color-dark);"></span>
            </button>
            <nav style="display: flex; align-items: center;" class="nav-links">
                <ul style="
                    display: flex;
                    list-style: none;
                    gap: 1.5rem;
                    margin: 0;
                    padding: 0;
                ">
                    <li><a href="home/home.php" style="text-decoration: none; color: var(--color-dark); font-weight: 500;">Home</a></li>
                    <li><a href="about/about.php" style="text-decoration: none; color: var(--color-dark); font-weight: 500;">About</a></li>
                    <li><a href="Profile/profile.php" style="text-decoration: none; color: var(--color-dark); font-weight: 500;">Profile</a></li>
                    <li><a href="index.php" style="text-decoration: none; color: var(--color-dark); font-weight: 500;">Forum</a></li>
                    <li><a href="resources/resources.php" style="text-decoration: none; color: var(--color-dark); font-weight: 500;">resources</a></li>
                </ul>
            </nav>
            <div style="display: flex; align-items: center; gap: 1rem;" class="nav-utils">
                <span style="cursor: pointer; color: var(--color-gray);" role="button" aria-label="Toggle language">EN</span>
                <a href="#" style="text-decoration: none; color: var(--primary-color);">Help</a>
                <a href="logout.php" style="text-decoration: none; color: var(--primary-color);">Logout</a>
                <button id="theme-toggle" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </header>

    <main style="padding: 2rem 0;">
        <div style="
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 1.5rem;
            padding: 0 1.5rem;
        " class="container">
            <div style="position: sticky; top: calc(80px + var(--sticky-top-left)); align-self: start;" class="left">
                <a style="display: flex; align-items: center; text-decoration: none; margin-bottom: 1.5rem;" class="profile" href="Profile/profile.php">
                    <div style="
                        width: 3rem;
                        height: 3rem;
                        border-radius: 50%;
                        overflow: hidden;
                        margin-right: 1rem;
                    " class="profile-photo">
                        <img src="<?php echo $user['profile_photo'] ? 'Uploads/' . htmlspecialchars($user['profile_photo']) : 'blank-profile-picture.webp'; ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Profile Photo">
                    </div>
                    <div class="handle">
                        <h4 style="margin: 0; color: var(--color-dark); font-size: 1rem;"><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p style="margin: 0; color: var(--color-gray); font-size: 0.85rem;">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </a>
                <div style="
                    background: var(--color-white);
                    border-radius: var(--border-radius-1);
                    padding: var(--card-padding);
                    box-shadow: var(--box-shadow);
                " class="sidebar">
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="explora/explora.html">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-compass"></i></span><h3 style="margin: 0; font-size: 1rem;">Explore</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        background: var(--color-light);
                    " class="menu-item active" href="notification/notification.php">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-bell-fill"></i></span><h3 style="margin: 0; font-size: 1rem;">Notifications</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="trending_topic/trending_topic.html">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-chat-fill"></i></span><h3 style="margin: 0; font-size: 1rem;">Trending Topics</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="bookmarK/bookmark.html">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-bookmarks"></i></span><h3 style="margin: 0; font-size: 1rem;">Bookmarks</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="analytics/analytics.html">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-clipboard2-data"></i></span><h3 style="margin: 0; font-size: 1rem;">Analytics</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" id="theme">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-palette-fill"></i></span><h3 style="margin: 0; font-size: 1rem;">Theme</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="setting/setting.php">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-gear"></i></span><h3 style="margin: 0; font-size: 1rem;">Settings</h3>
                    </a>
                    <a style="
                        display: flex;
                        align-items: center;
                        padding: 0.75rem;
                        text-decoration: none;
                        color: var(--color-dark);
                        border-radius: var(--border-radius-1);
                        transition: background 0.2s ease;
                    " class="menu-item" href="logout.php">
                        <span style="margin-right: 1rem; font-size: 1.2rem;"><i class="bi bi-box-arrow-right"></i></span><h3 style="margin: 0; font-size: 1rem;">Logout</h3>
                    </a>
                </div>
            </div>

            <div class="middle">
                <div style="
                    background: var(--color-white);
                    border-radius: var(--border-radius-1);
                    padding: var(--card-padding);
                    box-shadow: var(--box-shadow);
                    margin-bottom: 1.5rem;
                    transition: all 0.3s ease;
                " class="feed" data-post-id="<?php echo $post['id']; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;" class="head">
                        <div style="display: flex; align-items: center;" class="user">
                            <div style="
                                width: 2.5rem;
                                height: 2.5rem;
                                border-radius: 50%;
                                overflow: hidden;
                                margin-right: 0.75rem;
                            " class="profile-photo">
                                <img src="<?php echo $post['profile_photo'] ? 'Uploads/' . htmlspecialchars($post['profile_photo']) : 'blank-profile-picture.webp'; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="info">
                                <h3 style="margin: 0; font-size: 1rem; color: var(--color-dark);"><?php echo htmlspecialchars($post['username']); ?></h3>
                                <small style="color: var(--color-gray); font-size: 0.85rem;"><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php if ($post['user_id'] == $user_id): ?>
                            <span style="cursor: pointer; color: var(--color-gray); font-size: 1.2rem;" class="delete-post-btn" data-post-id="<?php echo $post['id']; ?>">
                                <i class="bi bi-trash"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div style="margin-bottom: 0.75rem;" class="category">
                        <span style="
                            background: var(--color-light);
                            padding: 0.3rem 0.75rem;
                            border-radius: var(--border-radius-1);
                            font-size: 0.85rem;
                            color: var(--color-dark);
                        "><?php echo htmlspecialchars($post['category_name']); ?></span>
                    </div>
                    <div style="margin-bottom: 1rem;" class="post-content">
                        <p style="margin: 0; color: var(--color-dark); line-height: 1.5;"><?php echo htmlspecialchars($post['content']); ?></p>
                        <?php if (!empty($images)): ?>
                            <div style="
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                                gap: 0.5rem;
                                margin-top: 1rem;
                            " class="post-images">
                                <?php foreach ($images as $image): ?>
                                    <img src="Uploads/<?php echo htmlspecialchars($image['image']); ?>" style="
                                        width: 100%;
                                        max-height: 300px;
                                        object-fit: cover;
                                        border-radius: var(--border-radius-1);
                                    " alt="Post Image">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;" class="action-buttons">
                        <div style="display: flex; gap: 1.5rem;" class="interaction-buttons">
                            <span style="
                                display: flex;
                                align-items: center;
                                cursor: pointer;
                                color: var(--color-gray);
                                font-size: 1.1rem;
                            " class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                <i style="margin-right: 0.3rem;" class="bi <?php echo $post['user_liked'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                <span style="font-size: 0.9rem;" class="like-count"><?php echo $post['like_count']; ?></span>
                            </span>
                            <span style="
                                display: flex;
                                align-items: center;
                                cursor: pointer;
                                color: var(--color-gray);
                                font-size: 1.1rem;
                            " class="comment-btn">
                                <i style="margin-right: 0.3rem;" class="bi bi-chat"></i>
                                <span style="font-size: 0.9rem;" class="comment-count"><?php echo $post['comment_count']; ?></span>
                            </span>
                            <span style="
                                display: flex;
                                align-items: center;
                                cursor: pointer;
                                color: var(--color-gray);
                                font-size: 1.1rem;
                                position: relative;
                            " class="share-btn">
                                <i class="bi bi-share"></i>
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 1rem; display: none;" class="comments-section">
                        <form style="display: flex; gap: 0.5rem; margin-bottom: 1rem;" class="comment-form">
                            <input type="text" style="
                                flex: 1;
                                padding: 0.5rem;
                                border: 1px solid var(--color-light);
                                border-radius: var(--border-radius-1);
                                font-size: 0.9rem;
                            " class="comment-input" placeholder="Add a comment...">
                            <button type="submit" style="
                                padding: 0.5rem 1rem;
                                background: var(--primary-color);
                                color: var(--color-white);
                                border: none;
                                border-radius: var(--border-radius-1);
                                cursor: pointer;
                                font-size: 0.9rem;
                            ">Post</button>
                        </form>
                        <div class="comments-list"></div>
                    </div>
                </div>
            </div>

            <div style="position: sticky; top: calc(80px + var(--sticky-top-right)); align-self: start;" class="right">
                <div style="
                    background: var(--color-white);
                    border-radius: var(--border-radius-1);
                    padding: var(--card-padding);
                    box-shadow: var(--box-shadow);
                " class="trending-topic">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;" class="heading">
                        <h4 style="margin: 0; color: var(--color-dark); font-size: 1rem;">Trending Topics</h4>
                        <i style="color: var(--color-gray); cursor: pointer;" class="bi bi-pencil-square"></i>
                    </div>
                    <div style="
                        display: flex;
                        align-items: center;
                        background: var(--color-light);
                        border-radius: var(--border-radius-1);
                        padding: 0.5rem;
                        margin-bottom: 1rem;
                    " class="search-bar">
                        <i style="color: var(--color-gray); margin-right: 0.5rem;" class="bi bi-search"></i>
                        <input type="search" style="
                            border: none;
                            background: none;
                            width: 100%;
                            font-size: 0.9rem;
                            color: var(--color-dark);
                        " placeholder="Search Trending Topics" id="trending-topic">
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 0;" class="category">
                        <li><a href="#" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">#Technology</a></li>
                        <li><a href="#" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">#Programming</a></li>
                        <li><a href="#" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">#WebDevelopment</a></li>
                        <li><a href="#" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">#AI</a></li>
                        <li><a href="#" style="text-decoration: none; color: var(--primary-color); font-size: 0.9rem;">#CloudComputing</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <footer style="
        background: var(--color-white);
        padding: 2rem 0;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
        margin-top: 2rem;
    " class="footer">
        <div style="
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 0 1.5rem;
        " class="footer-container">
            <div class="footer-logo">
                <h3 style="margin: 0; color: var(--color-dark); font-size: 1.2rem;">Debuglia</h3>
                <p style="color: var(--color-gray); font-size: 0.9rem;">Connect, share, and learn with creators worldwide.</p>
            </div>
            <div class="footer-links">
                <h4 style="margin: 0 0 1rem; color: var(--color-dark); font-size: 1rem;">Quick Links</h4>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li><a href="setting/setting.php" style="text-decoration: none; color: var(--color-gray); font-size: 0.9rem;">Settings</a></li>
                    <li><a href="Profile/profile.php" style="text-decoration: none; color: var(--color-gray); font-size: 0.9rem;">Profile</a></li>
                    <li><a href="analytics/analytics.html" style="text-decoration: none; color: var(--color-gray); font-size: 0.9rem;">Analytics</a></li>
                    <li><a href="logout.php" style="text-decoration: none; color: var(--color-gray); font-size: 0.9rem;">Logout</a></li>
                </ul>
            </div>
            <div class="footer-social">
                <h4 style="margin: 0 0 1rem; color: var(--color-dark); font-size: 1rem;">Follow Us</h4>
                <div style="display: flex; gap: 1rem;" class="social-icons">
                    <a href="#" style="color: var(--color-gray); font-size: 1.2rem;"><i class="bi bi-twitter"></i></a>
                    <a href="#" style="color: var(--color-gray); font-size: 1.2rem;"><i class="bi bi-github"></i></a>
                    <a href="#" style="color: var(--color-gray); font-size: 1.2rem;"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-contact">
                <h4 style="margin: 0 0 1rem; color: var(--color-dark); font-size: 1rem;">Contact Us</h4>
                <p style="margin: 0; color: var(--color-gray); font-size: 0.9rem;">
                    Email: <a href="mailto:support@debuglia.com" style="color: var(--primary-color); text-decoration: none;">support@debuglia.com</a>
                </p>
                <p style="margin: 0; color: var(--color-gray); font-size: 0.9rem;">Phone: +1-800-DEBUGLIA</p>
            </div>
        </div>
        <div style="text-align: center; margin-top: 2rem; color: var(--color-gray); font-size: 0.85rem;" class="footer-bottom">
            <p>© 2025 Debuglia. All rights reserved.</p>
        </div>
    </footer>

    <!-- Dark Mode Styles -->
    <style>
        body.dark-mode {
            background: #1a1a1a;
            color: #fff;
            --color-white: #2a2a2a;
            --color-dark: #fff;
            --color-gray: #b0b0b0;
            --color-light: #3a3a3a;
        }
        body.dark-mode .feed {
            background: var(--color-white);
            box-shadow: none;
            border: 1px solid var(--color-light);
        }
        body.dark-mode .sidebar,
        body.dark-mode .trending-topic,
        body.dark-mode .footer {
            background: var(--color-white);
            box-shadow: none;
            border: 1px solid var(--color-light);
        }
        body.dark-mode .category span {
            background: var(--color-light);
            color: var(--color-dark);
        }
        body.dark-mode .interaction-buttons span {
            color: var(--color-gray);
        }
        body.dark-mode .comment-form input {
            background: var(--color-white);
            border-color: var(--color-light);
            color: var(--color-dark);
        }
        body.dark-mode .comment-form button {
            background: var(--primary-color);
            color: var(--color-dark);
        }
    </style>

    <!-- Responsive Styles -->
    <style>
        @media (max-width: 1024px) {
            .container {
                grid-template-columns: 1fr 2fr;
            }
            .right {
                display: none;
            }
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            .left {
                position: static;
            }
            .hamburger {
                display: flex;
            }
            .nav-links {
                display: none;
                position: absolute;
                top: 80px;
                left: 0;
                width: 100%;
                background: var(--color-white);
                box-shadow: var(--box-shadow);
            }
            .nav-links.active {
                display: block;
            }
            .nav-links ul {
                flex-direction: column;
                padding: 1rem;
            }
            .nav-links ul li {
                margin: 0.5rem 0;
            }
            .feed {
                padding: var(--padding-2);
            }
            .profile-photo {
                width: 2rem !important;
                height: 2rem !important;
            }
            .user h3 {
                font-size: 0.9rem;
            }
            .user small {
                font-size: 0.8rem;
            }
            .post-content p {
                font-size: 0.9rem;
            }
            .interaction-buttons span {
                font-size: 1rem;
            }
            .comment-form input {
                font-size: 0.85rem;
            }
            .comment-form button {
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
            }
        }
        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }
            .feed {
                padding: 1rem;
            }
            .sidebar {
                padding: 1rem;
            }
            .footer-container {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        window.csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>';
    </script>
    <script src="script.js"></script>
</body>
</html>