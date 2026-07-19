<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Total Posts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_posts = $stmt->fetchColumn();

    // Total Likes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM likes l 
        JOIN posts p ON l.post_id = p.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_likes = $stmt->fetchColumn();

    // Total Comments
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM comments c 
        JOIN posts p ON c.post_id = p.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_comments = $stmt->fetchColumn();

    // Total Followers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $stmt->execute([$user_id]);
    $total_followers = $stmt->fetchColumn();

    // Posts by Category
    $stmt = $pdo->prepare("
        SELECT c.name, COUNT(p.id) AS post_count 
        FROM categories c 
        LEFT JOIN posts p ON c.id = p.category_id 
        WHERE p.user_id = ? 
        GROUP BY c.id
    ");
    $stmt->execute([$user_id]);
    $posts_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Most Liked Post
    $stmt = $pdo->prepare("
        SELECT p.content, COUNT(l.post_id) AS like_count 
        FROM posts p 
        LEFT JOIN likes l ON p.id = l.post_id 
        WHERE p.user_id = ? 
        GROUP BY p.id 
        ORDER BY like_count DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $most_liked_post = $stmt->fetch(PDO::FETCH_ASSOC);

    // Recent Activity
    $stmt = $pdo->prepare("
        SELECT 'post' AS type, content, created_at 
        FROM posts 
        WHERE user_id = ? 
        UNION 
        SELECT 'comment' AS type, content, created_at 
        FROM comments 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id, $user_id]);
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'total_posts' => $total_posts,
        'total_likes' => $total_likes,
        'total_comments' => $total_comments,
        'total_followers' => $total_followers,
        'posts_by_category' => $posts_by_category,
        'most_liked_post' => $most_liked_post,
        'recent_activity' => $recent_activity
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching analytics: ' . $e->getMessage()]);
}
?>