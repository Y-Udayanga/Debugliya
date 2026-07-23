<?php
// Start output buffering with callback to capture any output
ob_start(function ($buffer) {
    if ($buffer !== '') {
        error_log('Unexpected output captured in update_profile.php: ' . substr($buffer, 0, 1000));
    }
    return '';
});

// Suppress error display
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Start session
require_once __DIR__ . '/../session_bootstrap.php';
app_session_start();

// Set JSON header
header('Content-Type: application/json');

// Define JSON response constant for db_connect.php
define('JSON_RESPONSE', true);

// Debug: Log script start
error_log('Starting update_profile.php for user_id: ' . ($_SESSION['user_id'] ?? 'not set'));

// Include database connection
try {
    $db_path = __DIR__ . '/../db_connect.php';
    if (!file_exists($db_path)) {
        throw new Exception('db_connect.php not found at ' . $db_path);
    }
    require $db_path;
} catch (Exception $e) {
    ob_end_clean();
    error_log('Database connection error: ' . $e->getMessage() . ' in ' . __FILE__ . ' on line ' . __LINE__);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    error_log('Unauthorized access attempt in update_profile.php');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    ob_end_clean();
    error_log('Invalid CSRF token in update_profile.php');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
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

$profile_photo = $_SESSION['profile_photo'] ?? null;
if (!$profile_photo) {
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profile_photo = $stmt->fetchColumn() ?: null;
}

try {
    // Validate upload directory
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create uploads directory');
        }
    }
    if (!is_writable($upload_dir)) {
        throw new Exception('uploads directory is not writable at ' . $upload_dir);
    }

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_photo'];
        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $max_size = 5 * 1024 * 1024; // 5MB

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $file_type = $finfo->file($file['tmp_name']);
        if (!isset($allowed_types[$file_type])) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, or WebP allowed.');
        }

        if ($file['size'] > $max_size) {
            throw new Exception('File size exceeds 5MB limit.');
        }

        $ext = $allowed_types[$file_type];
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload file to ' . $upload_path);
        }

        // Delete old profile photo if exists
        if ($profile_photo && file_exists($upload_dir . $profile_photo)) {
            if (!unlink($upload_dir . $profile_photo)) {
                error_log('Failed to delete old profile photo: ' . $upload_dir . $profile_photo);
            }
        }
        $profile_photo = $filename;
        $_SESSION['profile_photo'] = $filename;
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception('File upload error code: ' . $_FILES['profile_photo']['error']);
    }

    // Update user in database
    $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, phone = ?, skills = ?, linkedin_url = ?, github_url = ?, twitter_url = ?, profile_photo = ? WHERE id = ?");
    if (!$stmt->execute([$bio, $location, $phone, $skills, $linkedin_url, $github_url, $twitter_url, $profile_photo, $user_id])) {
        throw new Exception('Database update failed for user_id: ' . $user_id);
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'profile_photo' => $profile_photo ? '../uploads/' . $profile_photo : '../blank-profile-picture.webp'
    ]);
} catch (Exception $e) {
    ob_end_clean();
    error_log('Profile update error: ' . $e->getMessage() . ' in ' . __FILE__ . ' on line ' . __LINE__);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
?>
