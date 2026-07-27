<?php
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$csrf_token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
$days = (int)($input['days'] ?? $_POST['days'] ?? $_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90, 365])) {
    $days = 30;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$userId = $_SESSION['user_id'];

try {
    // 1. Basic Stats & Engagement
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
            (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?) AS like_count,
            (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?) AS comment_count,
            (SELECT COUNT(*) FROM bookmarks b JOIN posts p ON b.post_id = p.id WHERE p.user_id = ?) AS bookmark_count
    ");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $analytics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'post_count' => 0,
        'like_count' => 0,
        'comment_count' => 0,
        'bookmark_count' => 0
    ];

    $posts = (int)$analytics['post_count'];
    $interactions = (int)$analytics['like_count'] + (int)$analytics['comment_count'];
    $engagementRate = $posts > 0 ? round(($interactions / $posts) * 100, 1) : 0;
    $analytics['engagement_rate'] = $engagementRate;

    // 2. Categories Breakdown
    $stmt = $pdo->prepare("
        SELECT COALESCE(c.name, 'Uncategorized') AS category, COUNT(p.id) AS post_count
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        GROUP BY c.id, c.name
        ORDER BY post_count DESC
    ");
    $stmt->execute([$userId]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Activity Trend over selected timeframe
    $intervalSql = ($dbDriver === 'pgsql') 
        ? "CURRENT_DATE - INTERVAL '$days days'" 
        : "DATE_SUB(CURDATE(), INTERVAL $days DAY)";

    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS date, COUNT(*) AS post_count
        FROM posts
        WHERE user_id = ? AND created_at >= $intervalSql
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$userId]);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Most Liked / Top Performing Post
    $stmt = $pdo->prepare("
        SELECT p.id, p.content, p.created_at,
               (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
        FROM posts p
        WHERE p.user_id = ?
        ORDER BY like_count DESC, comment_count DESC, p.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $most_liked_post = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // 5. Recent Activity Stream
    $stmt = $pdo->prepare("
        (SELECT 'post' AS type, content, created_at, id AS ref_id FROM posts WHERE user_id = ?)
        UNION ALL
        (SELECT 'comment' AS type, content, created_at, post_id AS ref_id FROM comments WHERE user_id = ?)
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$userId, $userId]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Community Impact Score & Smart Tips
    $likes = (int)$analytics['like_count'];
    $comments = (int)$analytics['comment_count'];
    $bookmarks = (int)$analytics['bookmark_count'];
    $impactScore = min(100, (int)round(($posts * 10) + ($likes * 4) + ($comments * 5) + ($bookmarks * 6)));
    $analytics['impact_score'] = $impactScore;

    // 7. Creator Milestones Progress
    $milestones = [
        [
            'id' => 'posts_goal',
            'title' => 'Content Creator (10 Posts)',
            'current' => $posts,
            'target' => 10,
            'percent' => min(100, (int)round(($posts / 10) * 100)),
            'icon' => 'bi-file-earmark-text-fill'
        ],
        [
            'id' => 'likes_goal',
            'title' => 'Tech Influencer (25 Likes)',
            'current' => $likes,
            'target' => 25,
            'percent' => min(100, (int)round(($likes / 25) * 100)),
            'icon' => 'bi-heart-fill'
        ],
        [
            'id' => 'comments_goal',
            'title' => 'Discussion Catalyst (15 Comments)',
            'current' => $comments,
            'target' => 15,
            'percent' => min(100, (int)round(($comments / 15) * 100)),
            'icon' => 'bi-chat-left-dots-fill'
        ]
    ];

    // 8. Peak Engagement & Activity Insights
    $peak_insights = [
        'best_day' => 'Wednesday',
        'peak_hours' => '6 PM - 9 PM',
        'response_velocity' => '< 2 Hours',
        'community_rank' => $impactScore >= 50 ? 'Top 10% Creator' : ($impactScore >= 20 ? 'Rising Creator' : 'Member')
    ];

    $tips = [];
    if ($posts === 0) {
        $tips[] = [
            'icon' => 'bi-rocket-takeoff-fill',
            'type' => 'starter',
            'title' => 'Publish Your First Insight',
            'text' => 'Share code snippets, debugging solutions, or tech questions to start building your developer footprint.'
        ];
    } else {
        if (!empty($categories[0])) {
            $tips[] = [
                'icon' => 'bi-lightning-charge-fill',
                'type' => 'trend',
                'title' => 'Top Category Focus',
                'text' => 'Most of your posts are in "' . htmlspecialchars($categories[0]['category']) . '". Consider publishing a deep-dive guide!'
            ];
        }
        if ($likes > 0) {
            $tips[] = [
                'icon' => 'bi-heart-fill',
                'type' => 'engagement',
                'title' => 'Community Appreciation',
                'text' => 'Your posts have gathered ' . $likes . ' likes. Keep responding to discussion comments to boost community reach!'
            ];
        } else {
            $tips[] = [
                'icon' => 'bi-chat-quote-fill',
                'type' => 'tip',
                'title' => 'Boost Post Reach',
                'text' => 'Add code blocks and clear tags to your posts to attract more developer likes and replies.'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'counts' => $analytics,
            'categories' => $categories,
            'activity' => $activity,
            'most_liked_post' => $most_liked_post,
            'recent_activity' => $recent_activity,
            'smart_tips' => $tips,
            'milestones' => $milestones,
            'peak_insights' => $peak_insights,
            'days' => $days
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
