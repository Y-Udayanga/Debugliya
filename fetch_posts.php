<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';
define('JSON_RESPONSE', true);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;

try {
    $query = "
        SELECT p.id, p.user_id, p.content, p.created_at, p.category_id, c.name AS category,
               u.username, u.profile_photo,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked,
               (SELECT COUNT(*) FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ?) AS is_bookmarked
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
    ";
    $params = [$_SESSION['user_id'], $_SESSION['user_id']];

    if ($category_id) {
        $query .= " WHERE p.category_id = ?";
        $params[] = $category_id;
    }

    $query .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $post_data = [];
    foreach ($posts as $post) {
        $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
        $stmt->execute([$post['id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $post_data[] = [
            'id' => $post['id'],
            'user_id' => $post['user_id'],
            'username' => $post['username'],
            'profile_photo' => $post['profile_photo'],
            'content' => $post['content'],
            'created_at' => $post['created_at'],
            'category' => $post['category'],
            'like_count' => $post['like_count'],
            'comment_count' => $post['comment_count'],
            'user_liked' => $post['user_liked'],
            'is_bookmarked' => $post['is_bookmarked'],
            'images' => $images
        ];
    }

    echo json_encode(['success' => true, 'posts' => $post_data]);
} catch (Exception $e) {
    error_log('Fetch posts error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching posts']);
}
?>
