<?php
// Admin delete program handler
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Block all GET access, only accept form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// CSRF protection to block fake cross-site delete requests
if (!csrf_validate()) {
    header('Location: index.php?error=' . urlencode('Invalid request. Refresh page and try again.'));
    exit();
}

// Get program ID from submitted form
$program_id = (int)($_POST['program_id'] ?? 0);

// Reject invalid ID value
if ($program_id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid program ID provided.'));
    exit();
}

// Safe delete statement with prepared statement to avoid SQL injection
$stmt = $conn->prepare("DELETE FROM program WHERE program_id = ?");
$stmt->bind_param("i", $program_id);

try {
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'Program deleted successfully.';
        $stmt->close();
        // Redirect back to program list page
        header('Location: index.php');
        exit();
    } else {
        // Foreign key constraint error (linked courses/students exist)
        header('Location: index.php?error=' . urlencode('Cannot delete this program, it has linked records in the system.'));
        exit();
    }
} catch (mysqli_sql_exception $e) {
    // Catch foreign key constraint violation
    $stmt->close();
    header('Location: index.php?error=' . urlencode('Cannot delete this program, it has linked courses in the system. Please delete the courses first.'));
    exit();
}