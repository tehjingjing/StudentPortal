<?php
session_start();
require_once '../config/db.php';
require_once '../includes/require_login.php';

if ($_SESSION['role'] !== 'student') {
    header('Location: admin_dashboard.php');
    exit();
}

// Last login — captured by login.php into the session BEFORE it overwrites the last_login cookie
$lastLogin = $_SESSION['previous_login'] ?? null;

$student = null;
// Initialize counter for pending leave applications, default 0
$pendingCount = 0;

// Check if student user is logged in via session student_id
if (!empty($_SESSION['student_id'])) {
    $stmt = $conn->prepare(
        'SELECT student.*, program.program_name FROM student
         LEFT JOIN program ON student.program_id = program.program_id
         WHERE student.student_id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $_SESSION['student_id']);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Prepare SQL to count pending leave records for logged-in student
    $pendingStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM `leave` WHERE student_id = ? AND status = 'pending'");
    $pendingStmt->bind_param('i', $_SESSION['student_id']);
    $pendingStmt->execute();
    $pendingCount = $pendingStmt->get_result()->fetch_assoc()['cnt'];
    $pendingStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>
    <div class="app-shell">
        <?php $activePage = 'dashboard'; require_once '../includes/student_sidebar.php'; ?>

        <main class="app-main">
            <header>
                <div class="header-title">
                    <h1>Welcome, <?= htmlspecialchars($student['full_name'] ?? $_SESSION['email']) ?></h1>
                    <div class="last-login">
                        <?php if ($lastLogin): ?>
                            Last login: <?= htmlspecialchars(date('d M Y, g:i A', strtotime($lastLogin))) ?>
                        <?php else: ?>
                            This is your first login
                        <?php endif; ?>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="logout-btn" href="../auth/logout.php">Log Out</a>
                </div>
            </header>

            <?php if (!$student): ?>
                <section class="panel">
                    <div class="empty-state">
                        <p>No student record is linked to this account yet.</p>
                        <p>Please contact the portal administrator so they can add your record.</p>
                    </div>
                </section>
            <?php else: ?>

                <div class="section-label">My Profile</div>
                <section class="panel">
                    <div class="profile-header">
                        <div>
                            <div class="profile-name"><?= htmlspecialchars($student['full_name']) ?></div>
                               <div class="profile-sub">
                                   <strong><?= htmlspecialchars($student['program_name'] ?? 'Unassigned Program') ?></strong>
                                   · Semester <?= (int)($student['current_semester'] ?? 1) ?> · Academic Year <?= (int)($student['current_academic_year'] ?? 1) ?>
                              </div>
                        </div>
                        <span class="badge <?= htmlspecialchars($student['status']) ?>"><?= htmlspecialchars(ucfirst($student['status'])) ?></span>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <div class="label">Student ID</div>
                            <div class="value">#<?= htmlspecialchars($student['student_id']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Admission Date</div>
                            <div class="value"><?= htmlspecialchars(date('d M Y', strtotime($student['admission_date']))) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Date of Birth</div>
                            <div class="value"><?= htmlspecialchars(date('d M Y', strtotime($student['dob']))) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Gender</div>
                            <div class="value"><?= htmlspecialchars($student['gender']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Email</div>
                            <div class="value"><?= htmlspecialchars($student['email']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="label">Contact No.</div>
                            <div class="value"><?= htmlspecialchars($student['contact_no'] ?: '—') ?></div>
                        </div>
                        <div class="info-item full-width">
                            <div class="label">Address</div>
                            <div class="value"><?= htmlspecialchars($student['address'] ?: '—') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($student['parent_name']) || !empty($student['parent_contact']) || !empty($student['parent_email'])): ?>
                        <div class="subsection-label">Guardian Emergency Contact Details</div>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="label">Name</div>
                                <div class="value"><?= htmlspecialchars($student['parent_name'] ?: '—') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Contact</div>
                                <div class="value"><?= htmlspecialchars($student['parent_contact'] ?: '—') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Email</div>
                                <div class="value"><?= htmlspecialchars($student['parent_email'] ?: '—') ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <div class="section-label">Quick Actions</div>
                <section class="quick-actions">
                    <a class="action-card" href="../students/profile.php">
                        <div class="icon">👤</div>
                        <div class="title">My Profile</div>
                        <div class="desc">View your full profile and parent/guardian details</div>
                    </a>
                    <a class="action-card" href="../enrolment/my_course.php">
                        <div class="icon">📘</div>
                        <div class="title">My Courses</div>
                        <div class="desc">See the courses you're enrolled in</div>
                    </a>
                    <a class="action-card" href="../leaves/apply.php">
                        <div class="icon">📝</div>
                        <div class="title">
                            My Leave
                            <?php if ($pendingCount > 0): ?><span class="pending-flag"><?= (int)$pendingCount ?> pending</span><?php endif; ?>
                        </div>
                        <div class="desc">Apply for leave and view your application history</div>
                    </a>
                </section>

            <?php endif; ?>
        </main>
    </div>
</body>
</html>
