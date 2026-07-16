<?php
// Admin handle approve/reject leave requests (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Reject all GET access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// CSRF security check to block fake cross-site requests
if (!csrf_validate()) {
    header('Location: index.php?error=' . urlencode('Invalid request. Please refresh and try again.'));
    exit();
}

// Get submitted leave ID and action (approved / rejected)
$leave_id = (int)($_POST['leave_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Validate input values
if ($leave_id <= 0 || !in_array($action, ['approved', 'rejected'], true)) {
    header('Location: index.php?error=' . urlencode('Invalid request.'));
    exit();
}

// Get current logged admin ID from session
$approvedBy = $_SESSION['admin_id'] ?? null;

// Block if admin ID missing
if (empty($approvedBy)) {
    header('Location: index.php?error=' . urlencode('Your admin profile is not linked to this account.'));
    exit();
}

// Update leave status only if it is still pending (prevent re-edit old records)
// `leave` wrapped with backtick because it is MySQL reserved keyword
$stmt = $conn->prepare("UPDATE `leave` SET status = ?, approved_by = ? WHERE leave_id = ? AND status = 'pending'");
$stmt->bind_param('sii', $action, $approvedBy, $leave_id);

// Check if exactly one record was updated
if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();
    $_SESSION['success_msg'] = 'Leave application ' . $action . '.';
} else {
    // Log database error and redirect back
    error_log('Leave update failed: ' . $stmt->error);
    $stmt->close();
    header('Location: index.php?error=' . urlencode('Could not update this leave application.'));
    exit();
}

// Return to leave management page
header('Location: index.php');
exit();