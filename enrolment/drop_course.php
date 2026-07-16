<?php
// Drop enrolled course (student only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';

// Kick out non-student users
if ($_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Only accept POST requests — never drop via a simple link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_course.php');
    exit();
}

// Block fake requests from other websites
if (!csrf_validate()) {
    header('Location: my_course.php?error=' . urlencode('Invalid request. Please refresh and try again.'));
    exit();
}

// Get student ID from session (trusted, not from user input)
$student_id = (int)$_SESSION['student_id'];
// Get enrolment record ID from the submitted form
$enroll_id = (int)($_POST['enroll_id'] ?? 0);

// Reject obviously invalid IDs
if ($enroll_id <= 0) {
    header('Location: my_course.php?error=' . urlencode('Invalid course registration.'));
    exit();
}

// Soft-delete: mark enrolment as "dropped" (keep the record, just change status)
// Student ID in WHERE prevents tampering with another student's enrolment
$stmt = $conn->prepare("UPDATE enrolment SET status = 'dropped' WHERE enroll_id = ? AND student_id = ? AND status = 'registered'");
$stmt->bind_param('ii', $enroll_id, $student_id);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($ok && $affected > 0) {
    $_SESSION['success_msg'] = 'Course dropped.';
} else {
    header('Location: my_course.php?error=' . urlencode('Could not drop this course. It may have already been dropped.'));
    exit();
}

// Go back to the student's course list
header('Location: my_course.php');
exit();