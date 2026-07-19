<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';

if (!isset($_GET['token'])) {
    header('Location: login.php');
    exit;
}

$token = $_GET['token'];
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Invalid or expired token.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->execute([$password, $reset['email']]);
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$reset['email']]);
    $_SESSION['message'] = "Password reset successfully.";
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Debuglia</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 class="log">Debuglia</h2>
            <h3>Reset Password</h3>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="input-group">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
            <div class="login-options">
                <p>Back to <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>
</body>
</html>
