<?php
// Student view own profile page (student only)
session_start();
require_once '../config/db.php';
require_once '../includes/require_login.php';

// Block admin from accessing student page
if ($_SESSION['role'] !== 'student') {
    header('Location: ../dashboard/admin_dashboard.php');
    exit();
}

$student = null;
// Load student data using student_id stored in session after login
if (!empty($_SESSION['student_id'])) {
    $stmt = $conn->prepare(
        'SELECT student.*, program.program_name 
         FROM student
         LEFT JOIN program 
         ON student.program_id = program.program_id
         WHERE student.student_id = ? 
         LIMIT 1'
    );
    $stmt->bind_param('i', $_SESSION['student_id']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>
    <div class="app-shell">
        <?php $activePage = 'profile'; require_once '../includes/student_sidebar.php'; ?>

        <main class="app-main">
            <header>
                <h1>👤 My Profile</h1>
                <div class="header-actions">
                    <a class="logout-btn" href="../auth/logout.php">Log Out</a>
                </div>
            </header>

            <?php if (!$student): ?>
                <!-- No student record linked to this login account -->
                <section class="panel">
                    <div class="empty-state">
                        <p>No student record is linked to this account yet.</p>
                        <p>Please contact the portal administrator so they can add your record.</p>
                    </div>
                </section>
            <?php else: ?>
                <!-- Personal & Academic Info Panel -->
                <section class="panel">
                    <div class="field">
                        <span class="label">Student ID</span>
                        <span class="value"><?= htmlspecialchars($student['student_id']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Full Name</span>
                        <span class="value"><?= htmlspecialchars($student['full_name']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Date of Birth</span>
                        <span class="value"><?= htmlspecialchars(date('d M Y', strtotime($student['dob']))) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Gender</span>
                        <span class="value"><?= htmlspecialchars($student['gender']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Address</span>
                        <span class="value"><?= htmlspecialchars($student['address'] ?? '—') ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Contact</span>
                        <span class="value"><?= htmlspecialchars($student['contact_no'] ?? '—') ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Email</span>
                        <span class="value"><?= htmlspecialchars($student['email']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Program Enrolled</span>
                        <span class="value"><?= htmlspecialchars($student['program_name'] ?? 'Unassigned') ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Current Semester</span>
                        <span class="value"><?= htmlspecialchars($student['current_semester']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Current Academic Year</span>
                        <span class="value"><?= htmlspecialchars($student['current_academic_year']) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Admission Date</span>
                        <span class="value"><?= htmlspecialchars(date('d M Y', strtotime($student['admission_date']))) ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Status</span>
                        <span class="value">
                            <span class="badge <?= htmlspecialchars($student['status']) ?>">
                                <?= htmlspecialchars(ucfirst($student['status'])) ?>
                            </span>
                        </span>
                    </div>
                </section>

                <!-- Guardian Emergency Contact Panel -->
                <section class="panel" style="margin-top: 20px;">
                    <h2>Guardian Emergency Contact Details</h2>
                    <div class="field">
                        <span class="label">Name</span>
                        <span class="value"><?= htmlspecialchars($student['parent_name'] ?? '—') ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Contact</span>
                        <span class="value"><?= htmlspecialchars($student['parent_contact'] ?? '—') ?></span>
                    </div>
                    <div class="field">
                        <span class="label">Email</span>
                        <span class="value"><?= htmlspecialchars($student['parent_email'] ?? '—') ?></span>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>