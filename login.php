<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/db_connect.php';

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($identifier) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            app_persist_login($user);
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sign In - Debuglia</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ui-polish.css">
    <link rel="stylesheet" href="style_login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <!-- Animated Particle Canvas -->
    <canvas id="login-particles"></canvas>

    <div class="login-wrapper">
        <!-- Top Navigation -->
        <div class="login-nav">
            <a href="home/home.php" class="back-link"><i class="bi bi-arrow-left-circle-fill"></i> Back to Home</a>
        </div>

        <div class="login-card-glass">
            <!-- Brand Header -->
            <div class="brand-header">
                <div class="logo-badge">
                    <i class="bi bi-code-slash logo-ico"></i>
                </div>
                <h1 class="brand-title">Debuglia</h1>
                <p class="brand-subtitle">Connect, code, and conquer challenges together.</p>
            </div>

            <!-- Login Form -->
            <div class="auth-box">
                <h2>Sign In</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" id="login-form">
                    <div class="form-field-group">
                        <label for="identifier"><i class="bi bi-person-fill"></i> Username or Email</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope-fill field-ico"></i>
                            <input type="text" id="identifier" name="identifier" placeholder="e.g. alex@debuglia.com" required value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-field-group">
                        <div class="label-row">
                            <label for="password"><i class="bi bi-key-fill"></i> Password</label>
                            <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                        </div>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock-fill field-ico"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle-btn" id="toggle-password" title="Toggle password visibility">
                                <i class="bi bi-eye-slash-fill" id="toggle-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-utility-row">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember" checked>
                            <span class="checkmark"></span>
                            Remember me on this device
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login-submit" id="login-btn">
                        <span>Sign In</span> <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <!-- Social Sign In Options -->
                <div class="auth-divider">
                    <span>Or continue with</span>
                </div>

                <div class="social-auth-row">
                    <a href="auth_social.php?provider=github" class="btn-social github">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                    <a href="auth_social.php?provider=google" class="btn-social google">
                        <i class="bi bi-google"></i> Google
                    </a>
                </div>

                <!-- Footer Sign Up Hint -->
                <div class="auth-footer-hint">
                    <p>Don't have an account? <a href="register.php" class="signup-highlight">Create one now <i class="bi bi-box-arrow-in-right"></i></a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="script_login.js"></script>
</body>
</html>
