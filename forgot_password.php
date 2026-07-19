<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
require __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires_at]);

        $reset_link = "http://yourdomain.com/reset_password.php?token=$token";
        // Send email 
        $message = "Password reset link sent to $email. Check your inbox.";
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Debuglia</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="style_login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2 class="log">Debuglia</h2>
            <h3>Forgot Password</h3>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if (isset($message)): ?>
                <p class="success"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="input-group">
                    <i class="bi bi-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
            </form>
            <div class="login-options">
                <p>Back to <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>
</body>
</html>
