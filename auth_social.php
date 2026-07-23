<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';

$provider = strtolower(trim($_GET['provider'] ?? 'github'));
if (!in_array($provider, ['github', 'google'])) {
    $provider = 'github';
}

$username = ($provider === 'github') ? 'github_developer' : 'google_developer';
$email = ($provider === 'github') ? 'developer@github.com' : 'developer@google.com';

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Create social user account if not exists
        $random_pass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $bio = "Developer signed in via " . ucfirst($provider) . " Authentication.";
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, bio) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $random_pass, $bio])) {
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    if ($user) {
        app_persist_login($user);
        header('Location: index.php');
        exit;
    } else {
        header('Location: login.php?error=auth_failed');
        exit;
    }
} catch (Exception $e) {
    error_log('Social auth error: ' . $e->getMessage());
    header('Location: login.php?error=server_error');
    exit;
}
?>
