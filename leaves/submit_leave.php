<?php
// Student submit new leave application handler (student only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';

// Restrict access to logged-in students only
if ($_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header('Location: apply.php?error=' . urlencode('Only students with a linked profile can apply for leave.'));
    exit();
}

// Block direct URL access, only accept form POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apply.php');
    exit();
}

// CSRF security check to block fake cross-site requests
if (!csrf_validate()) {
    header('Location: apply.php?error=' . urlencode('Invalid request. Refresh page and try again.'));
    exit();
}

// Get and clean form text inputs
$start_date = trim($_POST['start_date'] ?? '');
$end_date   = trim($_POST['end_date'] ?? '');
$reason     = trim($_POST['reason'] ?? '');
$student_id = (int)$_SESSION['student_id'];

// Check all required fields are filled
if (empty($start_date) || empty($end_date) || empty($reason)) {
    header('Location: apply.php?error=' . urlencode('Please fill in start date, end date, and reason.'));
    exit();
}

// Validate date format is strictly Y-m-d from date input
$start = DateTime::createFromFormat('!Y-m-d', $start_date);
$end = DateTime::createFromFormat('!Y-m-d', $end_date);
$validStart = $start && $start->format('Y-m-d') === $start_date;
$validEnd = $end && $end->format('Y-m-d') === $end_date;

if (!$validStart || !$validEnd) {
    header('Location: apply.php?error=' . urlencode('Please enter valid start and end dates.'));
    exit();
}

// Prevent end date earlier than start date
if ($end < $start) {
    header('Location: apply.php?error=' . urlencode('End date cannot be before start date.'));
    exit();
}

// File upload settings
$evidence = null;
$allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
$maxBytes = 5 * 1024 * 1024; // Max file size 5MB

// Process attachment if user uploaded a file
if (!empty($_FILES['evidence']['name'])) {
    $file = $_FILES['evidence'];

    // Check upload has no errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: apply.php?error=' . urlencode('File upload failed. Please try again.'));
        exit();
    }

    // Check file size limit
    if ($file['size'] > $maxBytes) {
        header('Location: apply.php?error=' . urlencode('File is too large. Maximum size is 5MB.'));
        exit();
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // Check real file MIME type to block fake extensions
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedTypes, true)) {
        header('Location: apply.php?error=' . urlencode('Only PDF, JPG, or PNG files are allowed.'));
        exit();
    }

    $uploadDir = __DIR__ . '/../uploads/leavedoc/';
    // Create upload folder if it does not exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            header('Location: apply.php?error=' . urlencode('Server configuration error: Cannot create upload folder.'));
            exit();
        }
    }

    // Generate unique safe filename to avoid overwriting files
    $safeName = 'leave_' . $student_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

    // Move uploaded file to target folder
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
        header('Location: apply.php?error=' . urlencode('Could not save the uploaded file. Check folder permissions.'));
        exit();
    }
    $evidence = $safeName;
}

// Insert new leave record, default status pending
// `leave` wrapped with backtick because it is MySQL reserved keyword
$stmt = $conn->prepare(
    'INSERT INTO `leave` (student_id, start_date, end_date, reason, evidence, status)
     VALUES (?, ?, ?, ?, ?, \'pending\')'
);
$stmt->bind_param('issss', $student_id, $start_date, $end_date, $reason, $evidence);

if ($stmt->execute()) {
    $stmt->close();
    $_SESSION['success_msg'] = 'Leave application submitted successfully.';
} else {
    error_log('Leave insert failed: ' . $stmt->error);
    $stmt->close();
    header('Location: apply.php?error=' . urlencode('Could not submit your leave application.'));
    exit();
}

// Back to leave page after successful submission
header('Location: apply.php');
exit();