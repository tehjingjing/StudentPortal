<?php
// Delete a course (admin only)
session_start();

// Load helpers, database, and auth checks
require_once '../includes/csrf_helper.php';
require_once '../config/db.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Only accept POST requests — never delete via GET link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Block fake requests from other websites
if (!csrf_validate()) {
    header('Location: index.php?error=' . urlencode('Invalid request. Please refresh and try again.'));
    exit();
}

// Get course ID and make sure it's a number
$course_id = (int)($_POST['course_id'] ?? 0);

// Reject bad IDs before touching the database
if ($course_id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid course tracking parameters context provided.'));
    exit();
}

// Safe delete query (prevents SQL injection)
$stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
$stmt->bind_param("i", $course_id);

try {
    if ($stmt->execute()) {
        // Success: show message on the list page
        $_SESSION['success_msg'] = 'Course catalog item dropped from database configuration matrix successfully.';
    } else {
        header('Location: index.php?error=' . urlencode('An unexpected tracking transactional execution halt occurred.'));
        exit();
    }
} catch (mysqli_sql_exception $e) {
    // Can't delete — course is used by other records (foreign key)
    header('Location: index.php?error=' . urlencode('Cannot remove course: Active entity dependencies or historical program relations rely on this course footprint key.'));
    exit();
}

$stmt->close();

// Go back to course list
header('Location: index.php');
exit();