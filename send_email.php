<?php
require 'db_connect.php';

function sendResetEmail($email, $reset_link) {
    // In a production environment, use a proper email library like PHPMailer
    $subject = "Debuglia Password Reset";
    $message = "Click the following link to reset your password: $reset_link\nThis link will expire in 1 hour.";
    $headers = "From: no-reply@debuglia.com";
    
    // Simulate sending email (replace with actual mail() or PHPMailer in production)
    if (mail($email, $subject, $message, $headers)) {
        return true;
    }
    return false;
}

// Example usage (called from forgot_password.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt->execute([$email, $token, $expires_at]);
        
        $reset_link = "http://".$_SERVER['HTTP_HOST']."/reset_password.php?token=$token";
        if (sendResetEmail($email, $reset_link)) {
            echo json_encode(['success' => true, 'message' => 'Reset link sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Email not found.']);
    }
    exit;
}
?>