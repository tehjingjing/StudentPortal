<?php
// Delete a student (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Only accept POST with a valid CSRF token; redirect all other access.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate()) {
    header('Location: index.php');
    exit;
}

$student_id = (int)($_POST['id'] ?? 0);

if ($student_id > 0) {
    $stmt = $conn->prepare('DELETE FROM student WHERE student_id = ?');
    $stmt->bind_param('i', $student_id);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Student record deleted successfully.';
    } else {
        $_SESSION['error'] = 'Cannot delete this student, related records exist in system.';
    }
    $stmt->close();
}

header('Location: index.php');
exit;
