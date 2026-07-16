<?php
// Delete a student (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Block direct URL access, only accept form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF security check to block fake delete requests
if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Refresh page and try again.';
    header('Location: index.php');
    exit;
}

// Get student ID from hidden form input
$student_id = (int)($_POST['id'] ?? 0);

if ($student_id > 0) {
    // Safe prepared delete statement to avoid SQL injection
    $stmt = $conn->prepare("DELETE FROM student WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Student record deleted successfully.';
    } else {
        // Usually triggered by foreign key constraints (linked leave/enrolment records)
        $_SESSION['error'] = 'Cannot delete this student, related records exist in system.';
    }
    $stmt->close();
}

$conn->close();
// Redirect back to student list page
header('Location: index.php');
exit;