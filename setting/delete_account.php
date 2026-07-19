<?php
ob_start(); 
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();
require __DIR__ . '/../db_connect.php';

//remove in production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['csrf_token']) || !hash_equals($input['csrf_token'], $_SESSION['csrf_token'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$uploads_dir = '../uploads/';

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete profile photo
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profile_photo = $stmt->fetchColumn();
    if ($profile_photo && is_file($uploads_dir . $profile_photo)) {
        if (!unlink($uploads_dir . $profile_photo)) {
            throw new Exception('Failed to delete profile photo');
        }
    }

    // Delete post images
    $stmt = $pdo->prepare("SELECT image FROM post_images WHERE post_id IN (SELECT id FROM posts WHERE user_id = ?)");
    $stmt->execute([$user_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($images as $image) {
        if ($image && is_file($uploads_dir . $image)) {
            if (!unlink($uploads_dir . $image)) {
                throw new Exception('Failed to delete post image: ' . $image);
            }
        }
    }

    // Delete user data 
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

   
    session_unset();
    session_destroy();

    ob_end_clean();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Account deletion error: ' . $e->getMessage());
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Error deleting account: ' . $e->getMessage()]);
}
?>
