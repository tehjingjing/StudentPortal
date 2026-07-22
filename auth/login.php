<?php
session_start();

// If user already logged in, redirect away to corresponding dashboard
if (!empty($_SESSION['role'])) {
    $target = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php';
    header('Location: ../dashboard/' . $target);
    exit();
}

require_once '../includes/csrf_helper.php';
require_once '../config/db.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$error = '';
$lockedOut = false;

// Check if user is currently locked out from login attempts
if (isset($_SESSION['lockout_until']) && time() < $_SESSION['lockout_until']) {
    $lockedOut = true;
    $secondLeft = $_SESSION['lockout_until'] - time();
    $error = "Too many failed attempts. Please try again in $secondLeft seconds.";
}

// Handle login form POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lockedOut) {
    // Validate CSRF token to block cross-site forgery
    if (!csrf_validate()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['rememberMe']);
        
        // Basic empty field validation
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Query users auth table for login credentials
            $stmt = $conn->prepare('SELECT id, email, password_hash, role, student_id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            // Verify account exists and password hash matches
            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate session ID to prevent session fixation attack
                session_regenerate_id(true);
                csrf_regenerate();

                // Clear failed login attempt counter after successful login
                unset($_SESSION['failed_attempts']);
                unset($_SESSION['lockout_until']);

                // Store user data into session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['admin_id'] = null;

                // Fetch admin ID if logged-in user is admin
                if ($user['role'] === 'admin') {
                    $adminStmt = $conn->prepare('SELECT admin_id FROM admin WHERE admin_email = ? LIMIT 1');
                    $adminStmt->bind_param('s', $user['email']);
                    $adminStmt->execute();
                    $admin = $adminStmt->get_result()->fetch_assoc();
                    $adminStmt->close();
                    $_SESSION['admin_id'] = $admin ? $admin['admin_id'] : null;
                }
                
                $_SESSION['last_activity'] = time();
                $_SESSION['previous_login'] = $_COOKIE['last_login'] ?? '';

                $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
                $cookieOptions = [
                   'expires'  => time() + (86400 * 30),
                   'path'     => '/',
                   'secure'   => $isHttps,
                   'httponly' => true,
                   'samesite' => 'Lax'
                ];

                $savedEmail = $_COOKIE['remember_email'] ?? '';
                if ($savedEmail !== '' && $savedEmail !== $email) {
                    $clearOpts = $cookieOptions;
                    $clearOpts['expires'] = time() - 3600;
                    setcookie('remember_email', '', $clearOpts);
                }

                // Set persistent cookie for remember me function (30 days)
                if ($rememberMe) {
                   setcookie('remember_email', $email, $cookieOptions);
                } else {
                   $cookieOptions['expires'] = time() - 3600 ;
                   setcookie('remember_email', '', $cookieOptions);
                }
                $cookieOptions['expires'] = time() + (86400 * 30);
                setcookie('last_login', date('Y-m-d H:i:s'), $cookieOptions);

                // Redirect to dashboard after successful login
                $dashboard = $user['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php';
                header('Location: ../dashboard/' . $dashboard);
                exit();
            } else {
                // Count failed login attempts
                $_SESSION['failed_attempts'] = ($_SESSION['failed_attempts'] ?? 0) + 1;
                if ($_SESSION['failed_attempts'] >= 3) {
                   $_SESSION['lockout_until'] = time() + 30;
                   $lockedOut = true;
                   $error = 'Too many failed attempts. Please try again in 30 seconds.';
                } else {
                   $remaining = 3 - $_SESSION['failed_attempts'];
                   $error = "Invalid email or password. You have $remaining attempt(s) left.";
                }
            }
        }
    }
}

// Auto-fill remembered email from cookie
$remember_email = htmlspecialchars($_COOKIE['remember_email'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="login-box">
        <img src="/public/logo/Spacecollege.png" alt="Logo" class="college-logo">
        <h1>Welcome Back</h1>
        <p class="subtitle">Log in to your account to continue.</p>

        <!-- Success message from password reset / registration -->
        <?php if (!empty($_GET['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['message']?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Login error alert -->
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <!-- Hidden CSRF protection field -->
            <?php csrf_field(); ?>

            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-box">
                    <input type="text" name="email" value="<?= $remember_email ?>" <?= $lockedOut ? 'disabled' : '' ?>>
                </div>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <div class="input-box">
                    <input type="password" name="password" <?= $lockedOut ? 'disabled' : '' ?>>
                </div>
            </div>

            <div class="remember">
                <label>
                    <input type="checkbox" name="rememberMe" <?= $remember_email ? 'checked' : '' ?> <?= $lockedOut ? 'disabled' : '' ?>>
                    Remember Me
                </label>
            </div>

            <!-- Link to forgot password page -->
            <div style="margin-top:15px; margin-bottom:20px;">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary" <?= $lockedOut ? 'disabled' : '' ?>>Log In</button>
        </form>

        <!-- Navigate to register page -->
        <div class="register-link">
            Don't have an account? <a href="register.php">Register Now</a>
        </div>
    </div>
</body>
</html>
