<?php
// Student course registration page (student only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';

// Secure role check: redirect non-student users
// Uses null coalescing to avoid "undefined array key" warnings
if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../dashboard/admin_dashboard.php');
    exit();
}

// Flash message handling
$successMsg = '';
if (!empty($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
$errorMsg = $_GET['error'] ?? '';

// Get authenticated student ID from session
$student_id = $_SESSION['student_id'] ?? null;

// Initialize profile state variables (prevents undefined variable warnings)
$currentSemester = null;
$currentAcademicYear = null;
$studentFacultyId = null;
$studentIsActive = false;
$studentRow = null; 

if (!empty($student_id)) {
    $stmt = $conn->prepare(
        'SELECT student.current_semester, student.current_academic_year, student.status, program.faculty_id
         FROM student
         LEFT JOIN program ON student.program_id = program.program_id
         WHERE student.student_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $studentRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($studentRow) {
        $studentFacultyId = (int)$studentRow['faculty_id'];
        $studentIsActive = $studentRow['status'] === 'active';
        $currentSemester = (int)$studentRow['current_semester'];
        $currentAcademicYear = (int)$studentRow['current_academic_year'];
    }
}

// Load active courses available for the student's faculty
$courses = [];
$coursesRes = null;
if ($studentFacultyId && $studentIsActive) {
    $coursesStmt = $conn->prepare(
        'SELECT course.course_id, course.course_code, course.course_name, course.credit_hours
         FROM course
         JOIN program ON course.program_id = program.program_id
         WHERE program.faculty_id = ? AND course.status = \'active\'
         ORDER BY course.course_code ASC'
    );
    $coursesStmt->bind_param('i', $studentFacultyId);
    $coursesStmt->execute();
    $coursesRes = $coursesStmt->get_result();
}
if ($coursesRes) {
    while ($row = $coursesRes->fetch_assoc()) {
        $courses[] = $row;
    }
}
if (isset($coursesStmt)) {
    $coursesStmt->close();
}

// Load courses the student has already registered for
$myCourses = [];
if (!empty($student_id)) {
    $stmt = $conn->prepare(
        "SELECT enrolment.enroll_id, enrolment.semester, enrolment.academic_year,
                course.course_code, course.course_name, course.credit_hours
         FROM enrolment
         JOIN course ON enrolment.course_id = course.course_id
         WHERE enrolment.student_id = ? AND enrolment.status = 'registered'
         ORDER BY enrolment.academic_year DESC, enrolment.semester ASC, course.course_name ASC"
    );
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $myCourses[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Course</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>
    <div class="app-shell">
        <?php $activePage = 'my_course'; require_once '../includes/student_sidebar.php'; ?>

        <main class="app-main">
            <header>
                <h1>📖 My Course</h1>
                <div class="header-actions">
                    <a class="logout-btn" href="../auth/logout.php">Log Out</a>
                </div>
            </header>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="section-label">Register for a Course</div>
            <section class="panel">
                <?php if (!$studentRow): ?>
                    <p class="empty">Course registration is unavailable because your student profile could not be found.</p>
                <?php elseif (!$studentIsActive): ?>
                    <p class="empty">Course registration is unavailable because your student profile is marked as <?= htmlspecialchars($studentRow['status']) ?>. Please contact an administrator.</p>
                <?php elseif (!$currentSemester || !$currentAcademicYear): ?>
                    <p class="empty">Course registration is blocked. Your profile is missing a current semester or academic year assignment.</p>
                <?php elseif (empty($courses)): ?>
                    <p class="empty">No active courses are currently available.</p>
                <?php else: ?>
                    <p style="margin-bottom: 16px; color: var(--muted); font-size: 0.9rem;">
                        Registering for: <strong>Semester <?= htmlspecialchars((string)$currentSemester) ?>, Academic Year <?= htmlspecialchars((string)$currentAcademicYear) ?></strong>
                    </p>
                    <form action="register_course.php" method="POST">
                        <?php csrf_field(); ?>

                        <label>Course *</label>
                        <select name="course_id" required>
                            <option value="">-- Select a course --</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['course_id'] ?>">
                                    <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
                                    (<?= (int)$c['credit_hours'] ?> Credit Hours)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="btn-box">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <div class="section-label">My Registered Courses</div>
            <section class="panel">
                <?php if (empty($myCourses)): ?>
                    <div class="empty-state">
                        <strong>No courses registered</strong>
                        You haven't registered for any courses yet.
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Code</th><th>Course Name</th><th>Credits</th><th>Semester</th><th>Academic Year</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myCourses as $mc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mc['course_code']) ?></td>
                                    <td><?= htmlspecialchars($mc['course_name']) ?></td>
                                    <td><?= (int)$mc['credit_hours'] ?></td>
                                    <td><?= htmlspecialchars($mc['semester']) ?></td>
                                    <td><?= htmlspecialchars($mc['academic_year']) ?></td>
                                    <td>
                                        <form action="drop_course.php" method="POST" onsubmit="return confirm('Drop this course?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="enroll_id" value="<?= (int)$mc['enroll_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Drop</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>