<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (isset($_GET['post_id'])) {
    $post_id = $_GET['post_id'];

    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, u.username, u.profile_photo,
               (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) as like_count,
               (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?) as user_liked
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ? AND c.parent_comment_id IS NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $post_id]);
    $comments = $stmt->fetchAll();

    foreach ($comments as &$comment) {
        $comment['user_liked'] = (bool)$comment['user_liked'];
        $stmt = $pdo->prepare("
            SELECT c.id, c.content, c.created_at, u.username, u.profile_photo,
                   (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ?) as user_liked
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.parent_comment_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$_SESSION['user_id'], $comment['id']]);
        $comment['replies'] = $stmt->fetchAll();
        foreach ($comment['replies'] as &$reply) {
            $reply['user_liked'] = (bool)$reply['user_liked'];
        }
    }

    echo json_encode(['success' => true, 'comments' => $comments]);
} else {
    echo json_encode(['success' => false, 'message' => 'Post ID not provided']);
}
?>