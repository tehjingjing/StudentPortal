<?php
session_start();
require_once '../config/db.php';
require_once '../includes/require_login.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: student_dashboard.php');
    exit();
}

$adminName = null;

if (!empty($_SESSION['admin_id'])) {
    $stmt = $conn->prepare('SELECT admin_name FROM admin WHERE admin_id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $adminName = $row['admin_name'];
    }
    $stmt->close();
}

// Last login — captured by login.php into the session BEFORE it overwrites the last_login cookie.
$lastLogin = $_SESSION['previous_login'] ?? null;

// --- Summary cards: active-only counts ---
// Assumes teacher / program / course each have a `status` column with an 'active' value
$active_students_res = $conn->query("SELECT COUNT(*) AS cnt FROM student WHERE status = 'active'");
$activeStudents = $active_students_res ? $active_students_res->fetch_assoc()['cnt'] : 0;

$active_teachers_res = $conn->query("SELECT COUNT(*) AS cnt FROM teacher WHERE status = 'active'");
$activeTeachers = $active_teachers_res ? $active_teachers_res->fetch_assoc()['cnt'] : 0;

$active_programs_res = $conn->query("SELECT COUNT(*) AS cnt FROM program WHERE status = 'active'");
$activePrograms = $active_programs_res ? $active_programs_res->fetch_assoc()['cnt'] : 0;

$active_courses_res = $conn->query("SELECT COUNT(*) AS cnt FROM course WHERE status = 'active'");
$activeCourses = $active_courses_res ? $active_courses_res->fetch_assoc()['cnt'] : 0;

// Pending leave count 
$leave_res = $conn->query("SELECT COUNT(*) AS cnt FROM `leave` WHERE status = 'pending'");
$pendingLeave = $leave_res ? $leave_res->fetch_assoc()['cnt'] : 0;

$recent = $conn->query('SELECT student_id, full_name, email, status, admission_date FROM student ORDER BY student_id ASC LIMIT 50');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>
    <div class="app-shell">
        <?php $activePage = 'dashboard'; require_once '../includes/admin_sidebar.php'; ?>

        <main class="app-main">
            <header>
                <div class="header-title">
                    <h1>Welcome, <?= htmlspecialchars($adminName ?? '') ?></h1>
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

            <div class="section-label">Overview</div>
            <section class="stats">
                <div class="stat-card">
                    <div class="icon">🎓</div>
                    <div class="num"><?= (int)$activeStudents ?></div>
                    <div class="label">Active Students</div>
                </div>
                <div class="stat-card">
                    <div class="icon">🧑‍🏫</div>
                    <div class="num"><?= (int)$activeTeachers ?></div>
                    <div class="label">Active Teachers</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📘</div>
                    <div class="num"><?= (int)$activePrograms ?></div>
                    <div class="label">Active Programs</div>
                </div>
                <div class="stat-card">
                    <div class="icon">📚</div>
                    <div class="num"><?= (int)$activeCourses ?></div>
                    <div class="label">Active Courses</div>
                </div>
            </section>

            <div class="section-label">Needs Your Attention</div>
            <section class="callout">
                <div class="callout-info">
                    <div class="num"><?= (int)$pendingLeave ?></div>
                    <div class="label">Pending Leave Request<?= $pendingLeave === 1 ? '' : 's' ?></div>
                </div>
                <a class="callout-link" href="../leaves/index.php">Review Requests →</a>
            </section>

            <div class="section-label">Quick Actions</div>
            <section class="quick-actions">
                <a class="action-card" href="../students/index.php">
                    <div class="icon">🎓</div>
                    <div class="title">Manage Students</div>
                    <div class="desc">Add, edit, or remove student records</div>
                </a>
                <a class="action-card" href="../teachers/index.php">
                    <div class="icon">🧑‍🏫</div>
                    <div class="title">Manage Teachers</div>
                    <div class="desc">Add, edit, or remove teacher records</div>
                </a>
                <a class="action-card" href="../programs/index.php">
                    <div class="icon">📘</div>
                    <div class="title">Manage Programs</div>
                    <div class="desc">Create or update academic programs</div>
                </a>
                <a class="action-card" href="../courses/index.php">
                    <div class="icon">📚</div>
                    <div class="title">Manage Courses</div>
                    <div class="desc">Create or update individual courses</div>
                </a>
            </section>

            <div class="section-label">Recently Added Students</div>
            <section class="panel">
                <?php if (!$recent || $recent->num_rows === 0): ?>
                    <p>No students yet.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Status</th><th>Admission Date</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $recent->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><span class="badge <?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                                    <td><?= htmlspecialchars($row['admission_date']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
