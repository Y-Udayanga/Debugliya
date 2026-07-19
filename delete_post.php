<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $post_id = filter_var($input['post_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid post']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['user_id'] == $_SESSION['user_id']) {
        try {
            $pdo->beginTransaction();
            // Delete post images
            $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id = ?");
            $stmt->execute([$post_id]);
            $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
            foreach ($images as $image) {
                $path = __DIR__ . '/uploads/' . basename($image);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            // Delete post (cascades to comments, likes, post_images)
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error deleting post: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized or post not found']);
    }
}
?>
