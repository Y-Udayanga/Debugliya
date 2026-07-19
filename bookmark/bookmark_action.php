<?php
ob_start();
session_start();
require __DIR__ . '/../db_connect.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request.';
    ob_end_clean();
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please log in.';
    ob_end_clean();
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['post_id'], $input['action'], $input['csrf_token'])) {
    $response['message'] = 'Invalid request data.';
    error_log('Invalid input: ' . print_r($input, true));
    ob_end_clean();
    echo json_encode($response);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $input['csrf_token'])) {
    $response['message'] = 'Invalid CSRF token.';
    error_log('CSRF token mismatch: ' . $input['csrf_token']);
    ob_end_clean();
    echo json_encode($response);
    exit;
}

$post_id = filter_var($input['post_id'], FILTER_VALIDATE_INT);
$action = $input['action'];
$user_id = $_SESSION['user_id'];

if (!$post_id || !in_array($action, ['add', 'remove'])) {
    $response['message'] = 'Invalid post or action.';
    error_log("Invalid post_id: $post_id or action: $action");
    ob_end_clean();
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Post not found.';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }

    if ($action === 'add') {
        $sql = $dbDriver === 'pgsql'
            ? "INSERT INTO bookmarks (user_id, post_id, created_at) VALUES (?, ?, NOW()) ON CONFLICT (user_id, post_id) DO NOTHING"
            : "INSERT IGNORE INTO bookmarks (user_id, post_id, created_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $post_id]);
        $response['success'] = true;
        $response['message'] = 'Post bookmarked!';
    } elseif ($action === 'remove') {
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $post_id]);
        $response['success'] = true;
        $response['message'] = 'Bookmark removed.';
    }
} catch (Exception $e) {
    $response['message'] = 'Server error.';
    error_log('Bookmark error: ' . $e->getMessage());
    ob_end_clean();
    echo json_encode($response);
    exit;
}

ob_end_clean();
echo json_encode($response);
?>
