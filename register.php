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

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please re-enter.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                // Auto login user after registration
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    app_persist_login($user);
                }
                header('Location: index.php');
                exit;
            } else {
                $error = "Account creation failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create Account - Debuglia</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="ui-polish.css">
    <link rel="stylesheet" href="style_login.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body class="login-body">
    <!-- Animated Interactive Particle Canvas -->
    <canvas id="login-particles"></canvas>

    <div class="login-wrapper">
        <!-- Top Navigation -->
        <div class="login-nav">
            <a href="home/home.php" class="back-link"><i class="bi bi-arrow-left-circle-fill"></i> Back to Home</a>
        </div>

        <div class="login-card-glass">
            <!-- Brand Header -->
            <div class="brand-header">
                <span class="debuglia-pill"><i class="bi bi-rocket-takeoff-fill"></i> JOIN THE DEBUGLIA COMMUNITY</span>
                <div class="logo-badge">
                    <i class="bi bi-code-slash logo-ico"></i>
                </div>
                <h1 class="brand-title">Debuglia</h1>
                <p class="brand-subtitle">Connect, code, and conquer challenges together.</p>
            </div>

            <!-- Register Form -->
            <div class="auth-box">
                <h2><i class="bi bi-person-plus-fill"></i> Create Your Account</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" id="register-form">
                    <div class="form-field-group">
                        <label for="reg-username"><i class="bi bi-person-fill"></i> Choose Username</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-at field-ico"></i>
                            <input type="text" id="reg-username" name="username" placeholder="e.g. alex_dev" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-field-group">
                        <label for="reg-email"><i class="bi bi-envelope-fill"></i> Email Address</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope field-ico"></i>
                            <input type="email" id="reg-email" name="email" placeholder="e.g. alex@debuglia.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-field-group">
                        <label for="reg-password"><i class="bi bi-lock-fill"></i> Password</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-key-fill field-ico"></i>
                            <input type="password" id="reg-password" name="password" placeholder="Min. 6 characters" required>
                            <button type="button" class="password-toggle-btn" id="toggle-reg-password" title="Toggle password visibility">
                                <i class="bi bi-eye-slash-fill" id="toggle-reg-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-field-group">
                        <label for="reg-confirm-password"><i class="bi bi-shield-check"></i> Confirm Password</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-check2-circle field-ico"></i>
                            <input type="password" id="reg-confirm-password" name="confirm_password" placeholder="Re-enter password" required>
                        </div>
                        <small id="password-match-msg" class="form-match-hint" style="display: none;"></small>
                    </div>

                    <div class="form-utility-row">
                        <label class="checkbox-container">
                            <input type="checkbox" name="terms" required checked>
                            <span class="checkmark"></span>
                            I agree to Debuglia Terms & Privacy Policy
                        </label>
                    </div>

                    <button type="submit" class="btn btn-login-submit" id="register-btn">
                        <span>Create Free Account</span> <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <!-- Social Sign Up Options -->
                <div class="auth-divider">
                    <span>Or register with</span>
                </div>

                <div class="social-auth-row">
                    <a href="auth_social.php?provider=github" class="btn-social github">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                    <a href="auth_social.php?provider=google" class="btn-social google">
                        <i class="bi bi-google"></i> Google
                    </a>
                </div>

                <!-- Footer Sign In Hint -->
                <div class="auth-footer-hint">
                    <p>Already have an account? <a href="login.php" class="signup-highlight">Sign In now <i class="bi bi-box-arrow-in-right"></i></a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="script_login.js"></script>
</body>
</html>
