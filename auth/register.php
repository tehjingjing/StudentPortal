<?php
// Start PHP session to store CSRF token & track authenticated login state
session_start();

// CSRF Protection: Generate unique security token once per user session to block cross-site request forgery attacks
if (empty($_SESSION['csrf_token'])) {
    // Generate 32 random secure bytes, convert to hex string for hidden form input
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Set timezone to Malaysia to match database datetime timestamps
date_default_timezone_set('Asia/Kuala_Lumpur');

// Import MySQL database connection configuration
require_once '../config/db.php';

// Redirect users who are already logged in away from registration page
// Prevent logged-in students/admins from accessing register form
if (!empty($_SESSION['role'])) {
    header('Location: ../dashboard/' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php'));
    exit();
}

// Variable to store all user-facing error messages for form display
$error = '';

// Handle form POST submission when user clicks "Create Account" button
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Validate CSRF token to block malicious cross-site requests
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        // Sanitize & extract input values from registration form
        $email             = trim($_POST['email'] ?? '');
        $password          = $_POST['password'] ?? '';
        $confirm_password  = $_POST['confirm_password'] ?? '';
        
        // All mandatory fields cannot be empty
        if (empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all required fields (marked *).';
        }
        // Input must follow standard email format
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        }
        // Enforce minimum password length of 6 characters
        elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        }
        // Ensure password and confirm password match exactly
        elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        }
        // All basic validation passed, proceed to database checks
        else {
            // Step 1: Check if student record exists in `student` table
            // System rule: Admin must first create student profile before student can self-register login
            $studentStmt = $conn->prepare('SELECT student_id FROM student WHERE email = ? LIMIT 1');
            $studentStmt->bind_param('s', $email); // Bind email string parameter
            $studentStmt->execute();
            $studentStmt->store_result(); // Save query result to count rows without fetching

            // No matching student profile found in student table
            if ($studentStmt->num_rows === 0) {
               $error = 'Email not registered. Please contact the admin to create your student profile.';
               $studentStmt->close(); 
            } else {
               // Extract the unique student_id linked to this email for user table foreign key
               $studentStmt->bind_result($student_id);
               $studentStmt->fetch();
               $studentStmt->close();

               // Step 2: Prevent duplicate user login accounts
               // Check if email OR student_id already exists inside `users` auth table
               $existsStmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR student_id = ? LIMIT 1');
               $existsStmt->bind_param('si', $email, $student_id); // String email, Integer student_id
               $existsStmt->execute();
               $existsStmt->store_result();

               // Duplicate account detected
               if ($existsStmt->num_rows > 0) {
                    $error = 'This email is already registered. Please log in instead.';
                    $existsStmt->close();
               } else {
                    $existsStmt->close();
                    // Step 3: Create new login record in `users` authentication table
                    // Hash plain-text password with BCrypt algorithm (auto generates secure random salt, irreversible encryption)
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    // Hardcode role as student (this registration page only for student accounts)
                    $role = 'student';

                    // Insert new user into users table: student_id (FK), email, encrypted password, role
                    $userStmt = $conn->prepare('INSERT INTO users (student_id, email, password_hash, role) VALUES (?, ?, ?, ?)');
                    $userStmt->bind_param('isss', $student_id, $email, $password_hash, $role);
                    $userOk = $userStmt->execute(); // Execute insert and capture success status
                    $userStmt->close();

                    // Registration insert success
                    if ($userOk) {
                        // Redirect to login page with success message via URL parameter
                        $success_msg = 'Registration successful! You can now log in.';
                        header('Location: login.php?message=' . urlencode($success_msg));
                        exit(); // Stop script execution after redirect
                    } else {
                        // Log raw database error to server log for admin debugging
                        error_log('Registration failed: ' . $conn->error);
                        // Show generic safe error to end user 
                        $error = 'A database error occurred during registration. Please try again.';
                    }
                }
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
    <title>Student Registration</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="register-box">
        <img src="/logo/Spacecollege.png" alt="Logo" class="college-logo">
        <h1>Student Registration</h1>
        <p class="subtitle">Register your account to get started.</p>

        <!-- Render error alert if error message variable is set -->
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- Self-submitting registration form -->
        <form method="post" action="register.php">
            <!-- Hidden CSRF token input to validate form origin on backend -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            
            <!-- Email input field, repopulate submitted value on validation failure -->
            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <!-- New password input, minimum 6 character client-side validation -->
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" minlength="6" required>
            </div>

            <!-- Password confirmation matching input -->
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" minlength="6" required>
            </div>

            <!-- Form submit button -->
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>

        <!-- Navigation link for existing registered users -->
        <div class="login-link">
            Already have an account? <a href="login.php">Log In Now</a>
        </div>
    </div>
</body>
</html>