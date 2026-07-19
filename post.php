<?php
session_start();
require __DIR__ . '/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to create a post.']);
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}


if (empty($_POST['content']) || empty($_POST['category_id'])) {
    echo json_encode(['success' => false, 'message' => 'Content and category are required.']);
    exit;
}

$content = trim($_POST['content']);
$category_id = (int)$_POST['category_id'];
$user_id = $_SESSION['user_id'];

if (mb_strlen($content) > 5000) {
    echo json_encode(['success' => false, 'message' => 'Post content is too long.']);
    exit;
}

//  category_id exists in categories table validationss..
$stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Invalid category selected.']);
    exit;
}

try {
    // Insert post into database
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, category_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user_id, $content, $category_id]);
    $post_id = $dbDriver === 'pgsql' ? $pdo->lastInsertId('posts_id_seq') : $pdo->lastInsertId();

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                if ($_FILES['images']['size'][$key] > 5 * 1024 * 1024) {
                    continue;
                }

                $file_type = $finfo->file($_FILES['images']['tmp_name'][$key]);
                if (!isset($allowed_types[$file_type])) {
                    continue; 
                }

                $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($name, PATHINFO_FILENAME));
                $file_name = uniqid('post_', true) . '_' . $safe_name . '.' . $allowed_types[$file_type];
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $file_path)) {
                    $stmt = $pdo->prepare("INSERT INTO post_images (post_id, image) VALUES (?, ?)");
                    $stmt->execute([$post_id, $file_name]);
                }
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Post created successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error creating post: ' . $e->getMessage()]);
}
?>
