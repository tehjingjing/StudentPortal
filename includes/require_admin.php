<?php
// includes/require_admin.php
// Include AFTER require_login.php on pages only admins should reach.
// Works from any folder that sits one level under the project root
// (students/, dashboard/, etc.) since it always redirects via ../dashboard/...

if ($_SESSION['role'] !== 'admin') {
    $target = ($_SESSION['role'] ?? '') === 'student'
        ? '../dashboard/student_dashboard.php'
        : '../auth/login.php';
    header('Location: ' . $target . '?error=' . urlencode('You do not have permission to do that.'));
    exit();
}