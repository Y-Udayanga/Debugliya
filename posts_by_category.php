<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

if (!isset($_GET['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
    exit;
}

$category_id = (int)$_GET['category_id'];
$user_id = $_SESSION['user_id'];

try {
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
        WHERE p.category_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $category_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $post['images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($post);

    echo json_encode(['success' => true, 'posts' => $posts]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching posts: ' . $e->getMessage()]);
}
?>
