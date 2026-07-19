<?php
session_start();
require '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    // Fetch basic analytics data
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) AS post_count,
            (SELECT COUNT(*) FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?) AS like_count,
            (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.id WHERE p.user_id = ?) AS comment_count
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch posts by category
    $stmt = $pdo->prepare("
        SELECT c.name AS category, COUNT(p.id) AS post_count
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = ?
        GROUP BY c.id, c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch post activity (last 30 days)
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS date, COUNT(*) AS post_count
        FROM posts
        WHERE user_id = ? AND created_at >= " . ($dbDriver === 'pgsql' ? "CURRENT_DATE - INTERVAL '30 days'" : "DATE_SUB(CURDATE(), INTERVAL 30 DAY)") . "
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'counts' => $analytics,
            'categories' => $categories,
            'activity' => $activity
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
