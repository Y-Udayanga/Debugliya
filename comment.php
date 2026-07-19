<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to comment.';
    echo json_encode($response);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);


if (!isset($input['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    $response['message'] = 'Invalid CSRF token.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    if (isset($input['post_id'], $input['content'])) {
        // Create the comment or replys
        $post_id = (int)$input['post_id'];
        $content = trim($input['content']);
        $parent_comment_id = isset($input['parent_comment_id']) ? (int)$input['parent_comment_id'] : null;

        if (empty($content)) {
            $response['message'] = 'Comment content cannot be empty.';
            echo json_encode($response);
            exit;
        }

        if (mb_strlen($content) > 1000) {
            $response['message'] = 'Comment is too long.';
            echo json_encode($response);
            exit;
        }

        // Check if post exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Post not found.';
            echo json_encode($response);
            exit;
        }

        // If reply, sub-com
        if ($parent_comment_id) {
            $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
            $stmt->execute([$parent_comment_id]);
            if (!$stmt->fetch()) {
                $response['message'] = 'Parent comment not found.';
                echo json_encode($response);
                exit;
            }
        }

        // Insert comment
        $stmt = $pdo->prepare("
            INSERT INTO comments (post_id, user_id, content, parent_comment_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$post_id, $user_id, $content, $parent_comment_id]);

        // Get updated comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $comment_count = $stmt->fetchColumn();

        $response['success'] = true;
        $response['message'] = $parent_comment_id ? 'Reply posted successfully.' : 'Comment posted successfully.';
        $response['comment_count'] = $comment_count;
    } elseif (isset($input['comment_id'], $input['action']) && in_array($input['action'], ['like', 'unlike'])) {
        // Like or unlike a comment
        $comment_id = (int)$input['comment_id'];
        $action = $input['action'];

        // Check comment exists
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Comment not found.';
            echo json_encode($response);
            exit;
        }

        if ($action === 'like') {
            // Checks to the already likeds
            $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            $stmt->execute([$comment_id, $user_id]);
            if ($stmt->fetch()) {
                $response['message'] = 'You have already liked this comment.';
                echo json_encode($response);
                exit;
            }

            // Insert like & delete section
            $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
            $stmt->execute([$comment_id, $user_id]);
        } else {
            
            $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
            $stmt->execute([$comment_id, $user_id]);
        }

        // Get updated like count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
        $stmt->execute([$comment_id]);
        $like_count = $stmt->fetchColumn();

        $response['success'] = true;
        $response['action'] = $action;
        $response['like_count'] = $like_count;
    } else {
        $response['message'] = 'Invalid request.';
    }

    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'Error processing comment: ' . $e->getMessage();
    echo json_encode($response);
}

exit;
?>
