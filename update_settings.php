<?php
session_start();
require '../db_connect.php';
define('JSON_RESPONSE', true);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$profile_photo = $_SESSION['profile_photo'];

try {
    // Validate inputs
    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Username and email are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if username or email is taken
    $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already taken']);
        exit;
    }

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, or GIF allowed.']);
            exit;
        }

        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../Uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Delete old profile photo if exists
            if ($profile_photo && file_exists('../Uploads/' . $profile_photo)) {
                unlink('../Uploads/' . $profile_photo);
            }
            $profile_photo = $filename;
            $_SESSION['profile_photo'] = $filename;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            exit;
        }
    }

    // Prepare update query
    $updates = ['username' => $username, 'email' => $email, 'profile_photo' => $profile_photo];
    $params = [$username, $email, $profile_photo, $user_id];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updates['password'] = $hashed_password;
        $params = [$username, $email, $hashed_password, $profile_photo, $user_id];
        $query = "UPDATE users SET username = ?, email = ?, password = ?, profile_photo = ? WHERE id = ?";
    } else {
        $query = "UPDATE users SET username = ?, email = ?, profile_photo = ? WHERE id = ?";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $_SESSION['username'] = $username;

    echo json_encode([
        'success' => true,
        'message' => 'Settings updated successfully',
        'profile_photo' => $profile_photo ? '../Uploads/' . $profile_photo : '../blank-profile-picture.webp',
        'username' => $username
    ]);
} catch (Exception $e) {
    error_log('Settings update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating settings']);
}
?>