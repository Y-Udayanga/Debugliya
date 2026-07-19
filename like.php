<?php
session_start();
require __DIR__ . '/db_connect.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to like a post.';
    echo json_encode($response);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
    $response['message'] = 'Invalid CSRF token.';
    echo json_encode($response);
    exit;
}

// Validate input
if (!isset($input['post_id']) || !isset($input['action']) || !in_array($input['action'], ['like', 'unlike'])) {
    $response['message'] = 'Invalid request.';
    echo json_encode($response);
    exit;
}

$post_id = (int)$input['post_id'];
$user_id = $_SESSION['user_id'];
$action = $input['action'];

try {
    // Check if post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Post not found.';
        echo json_encode($response);
        exit;
    }

    if ($action === 'like') {
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        if ($stmt->fetch()) {
            $response['message'] = 'You have already liked this post.';
            echo json_encode($response);
            exit;
        }

        //  like and delecte 
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $user_id]);
    } else {
        // Delete like
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
    }

    // Get to the updated like count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetchColumn();

    $response['success'] = true;
    $response['action'] = $action;
    $response['like_count'] = $like_count;
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'Error processing like: ' . $e->getMessage();
    echo json_encode($response);
}

exit;
?>
