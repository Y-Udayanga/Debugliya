<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to create a post.']);
    exit;
}

// CSRF token valid test
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
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
    $post_id = $pdo->lastInsertId();

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_type = $_FILES['images']['type'][$key];
                if (!in_array($file_type, $allowed_types)) {
                    continue; 
                }

                $file_name = uniqid() . '_' . basename($name);
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