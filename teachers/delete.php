<?php
// Delete a teacher (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Block direct URL access, only accept POST form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// CSRF security check to block fake cross-site delete requests
if (!csrf_validate()) {
    header('Location: index.php?error=' . urlencode('Invalid request. Refresh page and try again.'));
    exit();
}

// Get teacher ID from hidden form input, force convert to integer
$teacher_id = (int)($_POST['id'] ?? 0);

// Reject empty or invalid ID
if ($teacher_id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid teacher.'));
    exit();
}

// Prepare safe delete SQL statement to avoid SQL injection
$stmt = $conn->prepare('DELETE FROM teacher WHERE teacher_id = ?');
$stmt->bind_param('i', $teacher_id);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['success_msg'] = 'Teacher deleted.';
} else {
    // Log database error for developer debugging
    error_log('Teacher delete failed: ' . $stmt->error);
    $stmt->close();
    header('Location: index.php?error=' . urlencode('Could not delete teacher. Please try again.'));
    exit();
}

// Redirect back to teacher list page
header('Location: index.php');
exit();