<?php
session_start();
require '../db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT n.id, n.actor_id, n.post_id, n.type, n.content, n.is_read, n.created_at,
               u.username, u.profile_photo AS actor_photo
        FROM notifications n
        JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Exception $e) {
    error_log('Notification fetch error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching notifications']);
}
?>