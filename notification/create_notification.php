<?php
session_start();
require __DIR__ . '/../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$post_id = $input['post_id'] ?? null;
$type = $input['type'] ?? null;
$content = $input['content'] ?? null;
$csrf_token = $input['csrf_token'] ?? null;

if (!$post_id || !$type || !in_array($type, ['like', 'comment'], true) || !$csrf_token || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get post owner's user_id
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post && $post['user_id'] != $user_id) {
        // Create notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, post_id, type, content, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $message = $type === 'like' ? 'liked your post' : trim((string)$content);
        $stmt->execute([$post['user_id'], $user_id, $post_id, $type, $message]);
        echo json_encode(['success' => true, 'message' => 'Notification created']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No notification needed']);
    }
} catch (Exception $e) {
    error_log('Notification creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error creating notification']);
}
?>
