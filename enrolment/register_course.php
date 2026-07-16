<?php
// Student course registration handler (student only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';

// Kick out non-student users or users with no student ID
if ($_SESSION['role'] !== 'student' || empty($_SESSION['student_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

// Only accept POST requests — never register via a simple link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../enrolment/my_course.php');
    exit();
}

// Block fake requests from other websites
if (!csrf_validate()) {
    header('Location: my_course.php?error=' . urlencode('Invalid request. Please refresh and try again.'));
    exit();
}

// Get student ID from session (trusted, never from user input)
$student_id = (int)$_SESSION['student_id'];
$course_id = (int)($_POST['course_id'] ?? 0);
$semester = 0;
$academicYear = 0;

// Reject obviously invalid course IDs
if ($course_id <= 0) {
    header('Location: my_course.php?error=' . urlencode('Please select a course.'));
    exit();
}
$termResult = $conn->query("SELECT current_semester, current_academic_year FROM student WHERE student_id = $student_id LIMIT 1");
$term = $termResult ? $termResult->fetch_assoc() : null;

// No active term found — registration is not possible
if (!$term) {
    header('Location: my_course.php?error=' . urlencode('Course registration is unavailable because your academic term is not configured.'));
    exit();
}
$semester = (int)$term['current_semester'];
$academicYear = (int)$term['current_academic_year'];

// Verify course is active and offered by the student's own faculty
$stmt = $conn->prepare(
    "SELECT course.course_id
     FROM course
     JOIN program course_program ON course.program_id = course_program.program_id
     JOIN student ON student.student_id = ?
     JOIN program student_program ON student.program_id = student_program.program_id
     WHERE course.course_id = ?
       AND course_program.faculty_id = student_program.faculty_id
       AND course.status = 'active'
       AND student.status = 'active'
    LIMIT 1"
);
$stmt->bind_param('ii', $student_id, $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    header('Location: my_course.php?error=' . urlencode('This course is not available.'));
    exit();
}

// Check if student already has an enrolment record for this course + semester
$stmt = $conn->prepare('SELECT enroll_id, status FROM enrolment WHERE student_id = ? AND course_id = ? AND semester = ? AND academic_year = ? LIMIT 1');
$stmt->bind_param('iiii', $student_id, $course_id, $semester, $academicYear);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    // Already registered → show error
    if ($existing['status'] === 'registered') {
        header('Location: my_course.php?error=' . urlencode('You are already registered for this course in this semester.'));
        exit();
    }
    
    $stmt = $conn->prepare('UPDATE enrolment SET status = "registered" WHERE enroll_id = ?');
    $stmt->bind_param('i', $existing['enroll_id']);
    $ok = $stmt->execute();
    $stmt->close();
} else {
    $progStmt = $conn->prepare('SELECT program_id FROM student WHERE student_id = ? LIMIT 1');
    $progStmt->bind_param('i', $student_id);
    $progStmt->execute();
    $progRow = $progStmt->get_result()->fetch_assoc();
    $progStmt->close();

    if (!$progRow) {
        header('Location: my_course.php?error=' . urlencode('Course registration is unavailable because your student profile could not be found.'));
        exit();
    }

    $program_id = (int)$progRow['program_id'];
    $stmt = $conn->prepare(
        'INSERT INTO enrolment (student_id, course_id, program_id, semester, academic_year, status)
         VALUES (?, ?, ?, ?, ?, "registered")'
    );
    $stmt->bind_param('iiiii', $student_id, $course_id, $program_id, $semester, $academicYear);
    $ok = $stmt->execute();
    $stmt->close();
}

if ($ok) {
    $_SESSION['success_msg'] = 'You have registered for this course.';
} else {
    error_log('Course registration failed: ' . $conn->error);
    header('Location: my_course.php?error=' . urlencode('Could not complete registration. Please try again.'));
    exit();
}

// Go back to student course list
header('Location: my_course.php');
exit();
?>