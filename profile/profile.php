<?php
ob_start();
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

$user_id = $_SESSION['user_id'];

// Fetch user profile info
$stmt = $pdo->prepare("SELECT username, email, profile_photo, bio, location, phone, skills, linkedin_url, github_url, twitter_url, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// User Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_posts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_likes_given = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_bookmarks = $stmt->fetchColumn();

// User's own posts
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

// Bookmarked posts
$stmt = $pdo->prepare("
    SELECT p.id, p.user_id, p.content, p.created_at, p.category_id, c.name AS category,
           u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked,
           1 AS user_bookmarked
    FROM bookmarks b
    JOIN posts p ON b.post_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$bookmarked_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Liked posts
$stmt = $pdo->prepare("
    SELECT p.id, p.user_id, p.content, p.created_at, p.category_id, c.name AS category,
           u.username, u.profile_photo,
           (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
           1 AS user_liked,
           (SELECT COUNT(*) FROM bookmarks bm WHERE bm.post_id = p.id AND bm.user_id = ?) AS user_bookmarked
    FROM likes l
    JOIN posts p ON l.post_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE l.user_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$liked_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch post images for all posts
$all_posts = array_merge($posts, $bookmarked_posts, $liked_posts);
$post_images = [];
foreach ($all_posts as $post) {
    if (!isset($post_images[$post['id']])) {
        $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $post_images[$post['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Calculate profile completeness score
$completeness = 20; // Base score for account
if (!empty($user['profile_photo'])) $completeness += 20;
if (!empty($user['bio'])) $completeness += 15;
if (!empty($user['skills'])) $completeness += 15;
if (!empty($user['location'])) $completeness += 10;
if (!empty($user['phone'])) $completeness += 10;
if (!empty($user['github_url']) || !empty($user['linkedin_url']) || !empty($user['twitter_url'])) $completeness += 10;

// Parse skills array
$skills_list = !empty($user['skills']) ? array_filter(array_map('trim', explode(',', $user['skills']))) : [];

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
    <link rel="stylesheet" href="../ui-polish.css">
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
                    <li><a href="../home/home.php">Home</a></li>
                    <li><a href="../about/about.php">About</a></li>
                    <li><a href="../profile/profile.php" class="active">Profile</a></li>
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
            <!-- Left Sidebar -->
            <div class="left">
                <a class="profile" href="profile.php">
                    <div class="profile-photo">
                        <img src="<?php echo $user['profile_photo'] ? '../uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Profile Photo">
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

            <!-- Middle Main Content -->
            <div class="middle">
                <!-- Modern Profile Card Banner -->
                <div class="profile-hero-card">
                    <div class="profile-cover-banner">
                        <div class="cover-overlay"></div>
                        <span class="cover-badge"><i class="bi bi-patch-check-fill"></i> Verified Developer</span>
                    </div>

                    <div class="profile-header-body">
                        <div class="avatar-wrapper">
                            <div class="profile-photo-large">
                                <img src="<?php echo $user['profile_photo'] ? '../uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>'s Profile Photo" id="main-avatar-img">
                            </div>
                            <span class="online-status-dot" title="Active Now"></span>
                        </div>

                        <div class="profile-identity-section">
                            <div class="name-actions-row">
                                <div class="name-group">
                                    <h2 class="user-display-name">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                        <i class="bi bi-patch-check-fill verified-icon" title="Verified Creator"></i>
                                    </h2>
                                    <p class="user-handle-text">@<?php echo htmlspecialchars($user['username']); ?> • <span class="role-pill"><i class="bi bi-code-slash"></i> Community Member</span></p>
                                </div>
                                <div class="profile-actions-group">
                                    <button class="btn btn-primary edit-profile-btn" onclick="openEditProfileModal()">
                                        <i class="bi bi-pencil-square"></i> Edit Profile
                                    </button>
                                    <button class="btn btn-outline share-btn-action" id="share-profile-btn" onclick="copyProfileLink()">
                                        <i class="bi bi-share-fill"></i> Share
                                    </button>
                                </div>
                            </div>

                            <p class="bio-text">
                                <?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : '<span class="empty-bio-hint"><i class="bi bi-chat-quote"></i> No bio provided yet. Add a short bio to introduce yourself!</span>'; ?>
                            </p>

                            <!-- User Info Bar -->
                            <div class="profile-info-grid">
                                <?php if (!empty($user['location'])): ?>
                                    <span class="info-item"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($user['location']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($user['phone'])): ?>
                                    <span class="info-item"><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($user['phone']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($user['email'])): ?>
                                    <span class="info-item"><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                                <?php endif; ?>
                                <span class="info-item"><i class="bi bi-calendar3"></i> Joined <?php echo !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : '2025'; ?></span>
                            </div>

                            <!-- Social Links -->
                            <div class="profile-social-row">
                                <span class="social-label">Connect:</span>
                                <?php if (!empty($user['github_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($user['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-btn github" title="GitHub Profile"><i class="bi bi-github"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($user['linkedin_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($user['linkedin_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-btn linkedin" title="LinkedIn Profile"><i class="bi bi-linkedin"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($user['twitter_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($user['twitter_url']); ?>" target="_blank" rel="noopener noreferrer" class="social-icon-btn twitter" title="Twitter / X Profile"><i class="bi bi-twitter-x"></i></a>
                                <?php endif; ?>
                                <?php if (empty($user['github_url']) && empty($user['linkedin_url']) && empty($user['twitter_url'])): ?>
                                    <span class="text-muted small">No social links added yet.</span>
                                <?php endif; ?>
                            </div>

                            <!-- Skills Chips -->
                            <div class="profile-skills-row">
                                <span class="skills-label"><i class="bi bi-cpu-fill"></i> Skills & Expertise:</span>
                                <div class="skills-chips-wrapper">
                                    <?php if (!empty($skills_list)): ?>
                                        <?php foreach ($skills_list as $skill): ?>
                                            <span class="skill-tag"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($skill); ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="skill-tag empty" onclick="openEditProfileModal()"><i class="bi bi-plus-circle-fill"></i> Add your skills</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Profile Completeness Bar -->
                            <div class="completeness-bar-container">
                                <div class="completeness-header">
                                    <span><i class="bi bi-speedometer2"></i> Profile Strength: <strong><?php echo $completeness; ?>%</strong></span>
                                    <?php if ($completeness < 100): ?>
                                        <a href="#" onclick="openEditProfileModal(); return false;" class="complete-link">Complete now</a>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: <?php echo $completeness; ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Statistics Overview -->
                <div class="profile-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon post-icon"><i class="bi bi-file-earmark-code-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $total_posts; ?></span>
                            <span class="stat-label">Total Posts</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon like-icon"><i class="bi bi-heart-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $total_likes_given; ?></span>
                            <span class="stat-label">Likes Given</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bookmark-icon"><i class="bi bi-bookmark-star-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $total_bookmarks; ?></span>
                            <span class="stat-label">Bookmarks</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon rank-icon"><i class="bi bi-trophy-fill"></i></div>
                        <div class="stat-info">
                            <span class="stat-value">Level 1</span>
                            <span class="stat-label">Community Rank</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="profile-tabs-container">
                    <div class="profile-tabs-nav" role="tablist">
                        <button class="tab-btn active" data-tab="posts" role="tab" aria-selected="true">
                            <i class="bi bi-grid-3x3-gap-fill"></i> Your Posts (<span id="posts-count-badge"><?php echo count($posts); ?></span>)
                        </button>
                        <button class="tab-btn" data-tab="liked" role="tab" aria-selected="false">
                            <i class="bi bi-heart-fill"></i> Liked Posts (<span id="liked-count-badge"><?php echo count($liked_posts); ?></span>)
                        </button>
                        <button class="tab-btn" data-tab="bookmarks" role="tab" aria-selected="false">
                            <i class="bi bi-bookmark-fill"></i> Bookmarks (<span id="bookmarks-count-badge"><?php echo count($bookmarked_posts); ?></span>)
                        </button>
                        <button class="tab-btn" data-tab="about" role="tab" aria-selected="false">
                            <i class="bi bi-person-badge-fill"></i> About Details
                        </button>
                    </div>

                    <div class="tab-contents-wrapper">
                        <!-- Tab 1: Your Posts -->
                        <div class="tab-pane active" id="tab-posts" role="tabpanel">
                            <div class="posts-grid">
                                <?php if (empty($posts)): ?>
                                    <div class="empty-state-card">
                                        <i class="bi bi-journal-plus empty-icon"></i>
                                        <h3>No Posts Yet</h3>
                                        <p>Share your ideas, code snippets, or tech questions with the Debuglia community!</p>
                                        <a href="../home/home.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create First Post</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($posts as $post): ?>
                                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                            <div class="post-header">
                                                <div class="user">
                                                    <div class="profile-photo">
                                                        <img src="<?php echo $post['profile_photo'] ? '../uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="User Avatar">
                                                    </div>
                                                    <div class="info">
                                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                                        <small><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <span class="category"><?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'General'; ?></span>
                                                <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                                                    <button class="delete-post-btn" data-post-id="<?php echo $post['id']; ?>" title="Delete Post"><i class="bi bi-trash"></i></button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="post-content">
                                                <p><?php echo htmlspecialchars($post['content']); ?></p>
                                                <?php if (!empty($post_images[$post['id']])): ?>
                                                    <div class="post-images">
                                                        <?php foreach ($post_images[$post['id']] as $image): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($image); ?>" class="post-image" alt="Post Image">
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

                        <!-- Tab 2: Liked Posts -->
                        <div class="tab-pane" id="tab-liked" role="tabpanel" style="display: none;">
                            <div class="posts-grid">
                                <?php if (empty($liked_posts)): ?>
                                    <div class="empty-state-card">
                                        <i class="bi bi-heart empty-icon"></i>
                                        <h3>No Liked Posts</h3>
                                        <p>Posts you like across the platform will be saved here for quick reference.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($liked_posts as $post): ?>
                                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                            <div class="post-header">
                                                <div class="user">
                                                    <div class="profile-photo">
                                                        <img src="<?php echo $post['profile_photo'] ? '../uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="User Avatar">
                                                    </div>
                                                    <div class="info">
                                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                                        <small><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <span class="category"><?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'General'; ?></span>
                                            </div>
                                            <div class="post-content">
                                                <p><?php echo htmlspecialchars($post['content']); ?></p>
                                                <?php if (!empty($post_images[$post['id']])): ?>
                                                    <div class="post-images">
                                                        <?php foreach ($post_images[$post['id']] as $image): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($image); ?>" class="post-image" alt="Post Image">
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="post-actions">
                                                <span class="like-btn liked" data-post-id="<?php echo $post['id']; ?>">
                                                    <i class="bi bi-heart-fill"></i>
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

                        <!-- Tab 3: Bookmarks -->
                        <div class="tab-pane" id="tab-bookmarks" role="tabpanel" style="display: none;">
                            <div class="posts-grid">
                                <?php if (empty($bookmarked_posts)): ?>
                                    <div class="empty-state-card">
                                        <i class="bi bi-bookmarks empty-icon"></i>
                                        <h3>No Bookmarks Saved</h3>
                                        <p>Bookmark interesting discussions and tutorials to quickly read them later.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($bookmarked_posts as $post): ?>
                                        <div class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                            <div class="post-header">
                                                <div class="user">
                                                    <div class="profile-photo">
                                                        <img src="<?php echo $post['profile_photo'] ? '../uploads/' . htmlspecialchars($post['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="User Avatar">
                                                    </div>
                                                    <div class="info">
                                                        <h4><?php echo htmlspecialchars($post['username']); ?></h4>
                                                        <small><?php echo date('M d, Y H:i', strtotime($post['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <span class="category"><?php echo !empty($post['category']) ? htmlspecialchars($post['category']) : 'General'; ?></span>
                                            </div>
                                            <div class="post-content">
                                                <p><?php echo htmlspecialchars($post['content']); ?></p>
                                                <?php if (!empty($post_images[$post['id']])): ?>
                                                    <div class="post-images">
                                                        <?php foreach ($post_images[$post['id']] as $image): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($image); ?>" class="post-image" alt="Post Image">
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
                                                <span class="bookmark-btn bookmarked" data-post-id="<?php echo $post['id']; ?>">
                                                    <i class="bi bi-bookmark-fill"></i>
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

                        <!-- Tab 4: About Details -->
                        <div class="tab-pane" id="tab-about" role="tabpanel" style="display: none;">
                            <div class="about-details-card">
                                <h3><i class="bi bi-person-lines-fill"></i> Profile & Account Overview</h3>
                                <div class="about-grid">
                                    <div class="about-item">
                                        <label><i class="bi bi-person-fill"></i> Username</label>
                                        <p><?php echo htmlspecialchars($user['username']); ?></p>
                                    </div>
                                    <div class="about-item">
                                        <label><i class="bi bi-envelope-fill"></i> Email Address</label>
                                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    <div class="about-item">
                                        <label><i class="bi bi-geo-alt-fill"></i> Location</label>
                                        <p><?php echo !empty($user['location']) ? htmlspecialchars($user['location']) : 'Not specified'; ?></p>
                                    </div>
                                    <div class="about-item">
                                        <label><i class="bi bi-telephone-fill"></i> Contact Phone</label>
                                        <p><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'Not specified'; ?></p>
                                    </div>
                                    <div class="about-item full-width">
                                        <label><i class="bi bi-card-text"></i> Biography</label>
                                        <p><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No biography provided.'; ?></p>
                                    </div>
                                    <div class="about-item full-width">
                                        <label><i class="bi bi-cpu-fill"></i> Skills & Expertise</label>
                                        <p><?php echo !empty($user['skills']) ? htmlspecialchars($user['skills']) : 'No skills listed yet.'; ?></p>
                                    </div>
                                </div>
                                <div class="about-card-footer">
                                    <button class="btn btn-primary" onclick="openEditProfileModal()"><i class="bi bi-pencil"></i> Update Details</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
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

    <!-- Upgraded Edit Profile Modal -->
    <div class="modal" id="edit-profile-modal">
        <div class="modal-content profile-modal-content">
            <div class="modal-header">
                <h2><i class="bi bi-person-bounding-box"></i> Edit Profile</h2>
                <span class="close-modal">&times;</span>
            </div>
            <form id="edit-profile-form" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-section">
                    <label class="form-label">Profile Photo</label>
                    <div class="photo-upload-container">
                        <div class="current-photo-preview" id="modal-photo-preview">
                            <img src="<?php echo $user['profile_photo'] ? '../uploads/' . htmlspecialchars($user['profile_photo']) : '../blank-profile-picture.webp'; ?>" alt="Current Avatar">
                        </div>
                        <div class="upload-controls">
                            <label for="profile-photo-upload" class="btn btn-outline upload-btn">
                                <i class="bi bi-cloud-arrow-up-fill"></i> Choose New Avatar
                                <input type="file" id="profile-photo-upload" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                            </label>
                            <small class="text-muted">JPG, PNG, GIF or WebP (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="input-bio"><i class="bi bi-card-text"></i> Bio / Introduction</label>
                    <textarea id="input-bio" name="bio" rows="3" placeholder="Tell the community about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="input-location"><i class="bi bi-geo-alt"></i> Location</label>
                        <input type="text" id="input-location" name="location" placeholder="e.g. London, UK" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group col">
                        <label for="input-phone"><i class="bi bi-telephone"></i> Phone Number</label>
                        <input type="text" id="input-phone" name="phone" placeholder="e.g. +44 7123 456789" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="input-skills"><i class="bi bi-tools"></i> Skills (Comma-separated)</label>
                    <input type="text" id="input-skills" name="skills" placeholder="e.g. JavaScript, PHP, React, Python, UI/UX" value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>">
                    <small class="form-hint">Separate multiple skills with commas to display them as stylish tags.</small>
                </div>

                <div class="form-section">
                    <label class="form-label"><i class="bi bi-link-45deg"></i> Social Profiles</label>
                    <div class="form-group input-with-icon">
                        <i class="bi bi-github"></i>
                        <input type="text" name="github_url" placeholder="https://github.com/yourusername" value="<?php echo htmlspecialchars($user['github_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group input-with-icon">
                        <i class="bi bi-linkedin"></i>
                        <input type="text" name="linkedin_url" placeholder="https://linkedin.com/in/yourusername" value="<?php echo htmlspecialchars($user['linkedin_url'] ?? ''); ?>">
                    </div>
                    <div class="form-group input-with-icon">
                        <i class="bi bi-twitter-x"></i>
                        <input type="text" name="twitter_url" placeholder="https://twitter.com/yourusername" value="<?php echo htmlspecialchars($user['twitter_url'] ?? ''); ?>">
                    </div>
                </div>

                <div class="modal-footer-actions">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="save-profile-submit-btn"><i class="bi bi-check-circle-fill"></i> Save Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="image-modal" id="image-modal">
        <span id="close-image-modal">&times;</span>
        <img id="modal-image" alt="Enlarged Image">
    </div>

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
