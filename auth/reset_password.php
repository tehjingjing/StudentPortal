<?php
session_start();
require_once '../config/db.php';

if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header('Location: ../dashboard/student_dashboard.php');
    } else {
        header('Location: ../dashboard/admin_dashboard.php');
    }
    exit();
}

$error = '';
$success = '';
// Get reset token passed via URL parameter, remove extra spaces
$token = trim($_GET['token'] ?? '');
// Get current datetime for token expiration comparison
$now = date('Y-m-d H:i:s');

// If no token exists in URL, redirect user back to login page
if (empty($token)) {
    header('Location: login.php');
    exit();
}

// Handle form submission when user submits new password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw input from password fields
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    // Form validation rules
    if (empty($newPass) || empty($confirmPass)) {
        $error = "Fill both password fields.";
    } elseif ($newPass !== $confirmPass) {
        $error = "New password and confirm password do not match.";
    } elseif (strlen($newPass) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Prepare SQL to find user with matching unexpired reset token from users table
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expiry > ? LIMIT 1");
        // Bind token and current time as string parameters to SQL query
        $stmt->bind_param("ss", $token, $now);
        // Execute prepared statement
        $stmt->execute();
        // Fetch matching user record
        $targetUser = $stmt->get_result()->fetch_assoc();
        // Close database statement to free resources
        $stmt->close();

        // Check if token is invalid / expired / no matching user found
        if (!$targetUser) {
            $error = "Reset link invalid or expired. Request a new link.";
        } else {
            // Hash plain text new password with PHP default bcrypt algorithm (auto salted, irreversible)
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            // Prepare update SQL: save new password hash, clear reset token & expiry after successful reset
            $update = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
            // Bind hashed password(string) and user id(int) to query
            $update->bind_param("si", $newHash, $targetUser['id']);
            // Run update query to save changes to database
            $update->execute();
            // Close update statement
            $update->close();

            // Set success message for page display
            $success = "Password reset complete! You may now log in.";
            // Destroy existing logged-in session to avoid redirect loop after password change
            session_unset();
            session_destroy();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>🔑 Set New Password</h2>

        <!-- Display error alert if error message exists -->
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Show success message + login button after password update -->
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
            <div class="btn-box">
                <a href="login.php" class="btn btn-primary">Go to Login</a>
            </div>
        <?php else: ?>
            <!-- Password reset form, keep token in URL after form submit -->
            <form action="reset_password.php?token=<?= htmlspecialchars(urlencode($token)) ?>" method="POST">
                <label>New Password *</label>
                <!-- Password input with minimum length validation -->
                <input type="password" name="new_password" minlength="6" required>

                <label>Confirm New Password *</label>
                <input type="password" name="confirm_password" minlength="6" required>

                <div class="btn-box">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <a href="login.php" class="btn btn-secondary">Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
