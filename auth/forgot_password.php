<?php
// Start session to detect logged-in users
session_start();
// Redirect user to dashboard if already authenticated (no need to reset password)
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header('Location: ../dashboard/student_dashboard.php');
    } else {
        header('Location: ../dashboard/admin_dashboard.php');
    }
    exit();
}

// Load database connection config
require_once '../config/db.php';
// Load custom mail sending function for reset emails
require_once '../includes/mailer.php';

// Initialize message variables for page feedback
$error = '';
$success = '';

// Handle form POST submission when user submits email to request reset link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Remove extra whitespace from input email
    $email = trim($_POST['email'] ?? '');

    // Validate email field is not empty
    if (empty($email)) {
        $error = "Please enter your registered student email address.";
    } else {
        // Step 1: Look up student record matching the submitted email to get full name
        $stmt = $conn->prepare("SELECT student_id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email); // Bind email as string parameter
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc(); // Fetch matching student data
        $stmt->close(); // Free database statement resource

        // No student profile found for this email
        if (!$student) {
            $error = "No student account found with this email.";
        } else {
            // Step 2: Generate cryptographically secure random 32-byte reset  (hex format)
            $reset_token = bin2hex(random_bytes(32));
            // Calculate token expiry time (valid for 1 hour from current time)
            $expireTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Fix: Save token into the same student table
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $reset_token, $expireTime, $email);
            $updateStmt->execute();
            $updateStmt->close();

            // Detect protocol, compatible with Railway reverse proxy
            $isHttps = 
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            $protocol = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $resetUrl = $protocol . '://' . $host . '/auth/reset_password.php?token=' . $reset_token;

            // Fix: correct array access syntax
            $recipientName = $student['full_name'] ?? 'Student';

            // Call mailer function to deliver reset email
            $emailSent = sendResetEmail($email, $recipientName, $resetUrl);
            if ($emailSent) {
                // Success message if mail delivery completes
                $success = "Reset link sent! Check your email inbox (valid for 1 hour).";
            } else {
                // Capture mail debug info for troubleshooting delivery failures
                $detail = $GLOBALS['mailDebug'] ?? 'Unknown mail error';
                $error = "Failed to send reset email. Debug Info: " . $detail;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>🔐 Request Password Reset</h2>

        <!-- Render error alert if error message exists -->
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <!-- Render success alert after email sent successfully -->
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Self-submitting form to request password reset link -->
        <form action="forgot_password.php" method="POST">
            <label>Registered Student Email *</label>
            <!-- Email input, repopulate previous submitted value after validation error -->
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Send Reset Link</button>
                <a href="login.php" class="btn btn-secondary">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
