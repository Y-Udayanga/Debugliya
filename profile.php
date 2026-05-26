<?php
ob_start();
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
$stmt = $pdo->prepare("SELECT username, email, profile_photo, bio, location, phone, skills, linkedin_url, github_url, twitter_url FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT p.id, p.user_id, p.content, p.created_at, p.category_id, c.name AS category,
           u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked,
           (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ?) AS user_bookmarked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$post_images = [];
foreach ($posts as $post) {
    $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
    $stmt->execute([$post['id']]);
    $post_images[$post['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

ob_end_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Debuglia - Profile</title>
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="../style.css">
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
                <li><a href="../Profile/profile.php">Profile</a></li>
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
                <a class="profile" href="profile.php">
                    <div class="profile-photo">
                        <img src="<?php echo $user['profile_photo'] ? '../Uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
                    </div>
                    <div class="handle">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </a>
                <div class="sidebar">
                    <a class="menu-item" href="../explora/explora.php"><i class="bi bi-compass"></i><h3>Explore</h3></a>
                    <a class="menu-item" href="../notification/notification.php"><i class="bi bi-bell-fill"></i><h3>Notifications</h3></a>
                    <a class="menu-item" href="../trending_topic/trending_topic.php"><i class="bi bi-chat-fill"></i><h3>Trending Topics</h3></a>
                    <a class="menu-item" href="../bookmark/bookmark.php"><i class="bi bi-bookmarks"></i><h3>Bookmarks</h3></a>
                    <a class="menu-item" href="../analytics/analytics.php"><i class="bi bi-clipboard2-data"></i><h3>Analytics</h3></a>
                    <a class="menu-item" href="../setting/setting.php"><i class="bi bi-gear"></i><h3>Settings</h3></a>
                    <a class="menu-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i><h3>Logout</h3></a>
                </div>
            </div>

            <div class="middle">
                <div class="profile-card">
                    <div class="profile-photo-large">
                        <img src="<?php echo $user['profile_photo'] ? '../Uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
                    </div>
                    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="bio"><?php echo htmlspecialchars($user['bio'] ?? 'No bio provided.'); ?></p>
                    <p class="location"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($user['location'] ?? 'No location provided.'); ?></p>
                    <p class="phone"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'No phone number provided.'); ?></p>
                    <p class="skills"><i class="bi bi-tools"></i> <?php echo htmlspecialchars($user['skills'] ?? 'No skills provided.'); ?></p>
                    <p class="social-links">
                        <a href="<?php echo htmlspecialchars($user['linkedin_url'] ?? '#'); ?><i class="bi bi-linkedin"></i></a>
                        <a href="<?php echo htmlspecialchars($user['github_url'] ?? '#'); ?><i class="bi bi-github"></i></a>
                        <a href="<?php echo htmlspecialchars($user['twitter_url'] ?? '#'); ?><i class="bi bi-twitter"></i></a>
                    </p>
                    <button class="btn btn-primary" onclick="openEditProfileModal()">Edit Profile</button>
                </div>

                <div class="posts-grid">
                    <h3>Your Posts</h3>
                    <?php if (empty($posts)): ?>
                        <p>No posts yet.</p>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <div class="user">
                                        <div class="profile-photo">
                                            <img src="<?php echo $post['profile_photo'] ? '../Uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>">
                                        </div>
                                        <div class="info">
                                            <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                            <small><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                                        </div>
                                    </div>
                                    <span class="category"><?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'No Category'; ?></span>
                                    <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                        <button class="delete-post-btn" data-post-id="<?php echo $post['id']; ?>"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                                <div class="post-content">
                                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                                    <?php if (!empty($post_images[$post['id']])): ?>
                                        <div class="post-images">
                                            <?php foreach ($post_images[$post['id']] as $image): ?>
                                                <img src="../Uploads/<?php echo htmlspecialchars($image); ?>" class="post-image" alt="Post Image">
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="post-actions">
                                     <span class="like-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="bi <?php echo $post['user_liked'] ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
                                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                                    </span>
                                    <span class="comment-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-chat-square-dots"></i>
                                        <span class="comment-count"><?php echo $post['comment_count']; ?></span>
                                    </span>
                                    <span class="share-btn" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi bi-share"></i>
                                    </span>
                                    <span class="bookmark-btn <?php echo $post['user_bookmarked'] ? 'bookmarked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                        <i class="bi <?php echo $post['user_bookmarked'] ? 'bi-bookmark-fill' : 'bi-bookmark'; ?>"></i>
                                    </span>
                                </div>
                                <div class="comments-section" style="display: none;" data-post-id="<?php echo $post['id']; ?>">
                                    <form class="comment-form">
                                        <input type="text" placeholder="Add a comment..." class="comment-input">
                                        <button type="submit" class="btn btn-primary">Comment</button>
                                    </form>
                                    <div class="comments-list"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="right">
                <div class="trending-topics">
                    <h4>Trending Topics</h4>
                    <div class="search-bar">
                        <i class="bi bi-search"></i>
                        <input type="search" placeholder="Search Trending Topics" id="trending-topic">
                    </div>
                    <ul>
                        <li><a href="#">#Technology</a></li>
                        <li><a href="#">#Programming</a></li>
                        <li><a href="#">#WebDevelopment</a></li>
                        <li><a href="#">#AI</a></li>
                        <li><a href="#">#CloudComputing</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <div class="modal" id="edit-profile-modal">
        <div class="modal-content">
            <span class="close-modal">×</span>
            <h2>Edit Profile</h2>
            <form id="edit-profile-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <textarea name="bio" placeholder="Enter your bio"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                <input type="text" name="location" placeholder="Enter your location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                <input type="text" name="phone" placeholder="Enter your phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                <textarea name="skills" placeholder="Enter your skills"><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                <input type="url" name="linkedin_url" placeholder="Enter LinkedIn URL" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>">
                <input type="url" name="github_url" placeholder="Enter GitHub URL" value="<?php echo htmlspecialchars($user['github_url'] ?? ''); ?>">
                <input type="url" name="twitter_url" placeholder="Enter Twitter URL" value="<?php echo htmlspecialchars($user['twitter_url'] ?? ''); ?>">
                <label for="profile-photo-upload" class="upload-btn">
                    <i class="bi bi-paperclip"></i> Update Profile Photo
                    <input type="file" id="profile-photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif" style="display: none;">
                </label>
                <div class="image-preview" style="display: none;">
                    <div id="profile-image-preview"></div>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="image-modal" id="image-modal">
        <span id="close-image-modal">×</span>
        <img id="modal-image" alt="Enlarged Image">
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
                    <li><a href="profile.php">Profile</a></li>
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
    <script src="profile.js"></script>
    <script src="../script.js"></script>
</body>
</html>