<?php
// Start output buffering with callback to capture any unexpected output
ob_start(function ($buffer) {
    if (trim($buffer) !== '') {
        error_log('Unexpected output captured in update_profile.php: ' . substr($buffer, 0, 1000));
    }
    return '';
});

// Suppress error display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Start session
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();

// Set JSON header
header('Content-Type: application/json');

// Define JSON response constant for db_connect.php
define('JSON_RESPONSE', true);

// Include database connection
try {
    $db_path = __DIR__ . '/../db_connect.php';
    if (!file_exists($db_path)) {
        throw new Exception('db_connect.php not found');
    }
    require $db_path;
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
    exit;
}

function format_social_url($url, $domain) {
    $url = trim($url);
    if (empty($url)) return '';
    if (!preg_match('~^https?://~i', $url)) {
        if (strpos($url, $domain) === false) {
            $url = 'https://' . $domain . '/' . ltrim($url, '/@');
        } else {
            $url = 'https://' . ltrim($url, '/');
        }
    }
    return $url;
}

$user_id = $_SESSION['user_id'];
$bio = trim($_POST['bio'] ?? '');
$location = trim($_POST['location'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$skills = trim($_POST['skills'] ?? '');
$linkedin_url = format_social_url($_POST['linkedin_url'] ?? '', 'linkedin.com');
$github_url = format_social_url($_POST['github_url'] ?? '', 'github.com');
$twitter_url = format_social_url($_POST['twitter_url'] ?? '', 'twitter.com');

// Query fresh existing profile photo from database
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_photo = $stmt->fetchColumn() ?: null;
$profile_photo = $current_photo;

try {
    // Validate upload directory
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0755, true);
    }

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $max_size) {
            throw new Exception('File size exceeds 5MB limit.');
        }

        $orig_name = strtolower($file['name']);
        $ext = pathinfo($orig_name, PATHINFO_EXTENSION);
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed_exts)) {
            throw new Exception('Invalid file format. Only JPG, PNG, GIF, or WebP allowed.');
        }

        // Verify image structure via getimagesize if available
        if (function_exists('getimagesize')) {
            $check = @getimagesize($file['tmp_name']);
            if ($check === false) {
                throw new Exception('Uploaded file is not a valid image.');
            }
        }

        $filename = 'profile_' . $user_id . '_' . time() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $upload_path = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to save uploaded image. Check folder permissions.');
        }

        // Delete old custom profile photo
        if ($current_photo && $current_photo !== 'blank-profile-picture.webp' && file_exists($upload_dir . $current_photo)) {
            @unlink($upload_dir . $current_photo);
        }

        $profile_photo = $filename;
        $_SESSION['profile_photo'] = $filename;
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception('File upload error code: ' . $_FILES['profile_photo']['error']);
    }

    // Update database record
    $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, phone = ?, skills = ?, linkedin_url = ?, github_url = ?, twitter_url = ?, profile_photo = ? WHERE id = ?");
    if (!$stmt->execute([$bio, $location, $phone, $skills, $linkedin_url, $github_url, $twitter_url, $profile_photo, $user_id])) {
        throw new Exception('Database update failed for user_id: ' . $user_id);
    }

    $photo_url = ($profile_photo && $profile_photo !== 'blank-profile-picture.webp')
        ? '../uploads/' . $profile_photo
        : '../blank-profile-picture.webp';

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'profile_photo' => $photo_url,
        'bio' => $bio,
        'location' => $location,
        'phone' => $phone,
        'skills' => $skills,
        'linkedin_url' => $linkedin_url,
        'github_url' => $github_url,
        'twitter_url' => $twitter_url
    ]);
} catch (Exception $e) {
    ob_end_clean();
    error_log('Profile update error: ' . $e->getMessage() . ' in ' . __FILE__);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
?>
