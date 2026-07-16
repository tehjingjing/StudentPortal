<?php
// Admin student list dashboard page (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Show success message once then clear session
if (!empty($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Get search keyword from URL parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query: join student with program to display program name
$baseQuery = "SELECT student.*, program.program_name
              FROM student
              LEFT JOIN program ON student.program_id = program.program_id";

// Search filter: match student full name or email

if ($search !== '') {
    $stmt = $conn->prepare($baseQuery . " 
        WHERE student.full_name LIKE ? OR student.email LIKE ? 
        ORDER BY student.full_name ASC");
    $searchTerm = '%' . $search . '%';
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($baseQuery . " ORDER BY student.student_id DESC");
}

// Count summary metrics for top statistic cards
$total_res = $conn->query('SELECT COUNT(*) AS cnt FROM student');
$total = $total_res ? $total_res->fetch_assoc()['cnt'] : 0;

$active_res = $conn->query("SELECT COUNT(*) AS cnt FROM student WHERE status = 'active'");
$activeStudents = $active_res ? $active_res->fetch_assoc()['cnt'] : 0;

$graduated_res = $conn->query("SELECT COUNT(*) AS cnt FROM student WHERE status = 'graduated'");
$graduatedStudents = $graduated_res ? $graduated_res->fetch_assoc()['cnt'] : 0;

// Load all programs (unused on this page, kept if you later add filter dropdown)
$programs = $conn->query('SELECT program_id, program_name FROM program ORDER BY program_name ASC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students · Admin Workspace</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=4">
</head>
<body>
    <div class="container">
        <header>
            <div class="title-area">
                <h1>🎓 Student Directory</h1>
            </div>
            <div class="nav-actions">
                <a href="../dashboard/admin_dashboard.php" class="btn btn-secondary">← Back Dashboard</a>
                <a href="create.php" class="btn btn-primary">+ Register Student</a>
            </div>
        </header>

        <!-- Success alert after create/update/delete -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">✨ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <!-- Error alert redirected from delete/edit page -->
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Statistic summary cards -->
        <section class="summary-bar">
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Enrolled Students</h4>
                    <p><?= (int)$total ?></p>
                </div>
                <div class="icon">📁</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Active Profiles</h4>
                    <p><?= (int)$activeStudents ?></p>
                </div>
                <div class="icon">🟢</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Graduated Students</h4>
                    <p><?= (int)$graduatedStudents ?></p>
                </div>
                <div class="icon">🎓</div>
            </div>
        </section>

        <!-- Search bar form -->
        <div class="workspace-toolbar">
            <div class="search-container">
                <form action="index.php" method="GET">
                    <span class="search-icon">
                        <svg class="meta-icon" style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" placeholder="Filter by student name or student email" value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </div>

        <?php if (!$result || $result->num_rows === 0): ?>
            <div class="empty-state">
                No student records found matching your search.
            </div>
        <?php else: ?>
            <div id="recordsContainer" class="records">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        // Assign different CSS class for status label color
                        $statusClass = 'status-active';
                        if ($row['status'] === 'graduated') $statusClass = 'status-graduated';
                        if ($row['status'] === 'dropout') $statusClass = 'status-dropout';
                    ?>
                    <div class="record-card">
                        <div class="card-header">
                            <a href="edit.php?student_id=<?= urlencode($row['student_id']) ?>" class="record-title">
                                <?= htmlspecialchars($row['full_name']) ?>
                            </a>
                            <span class="status-pill <?= $statusClass ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Student ID:</span>
                                <span class="info-detail">#<?= htmlspecialchars($row['student_id']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">DOB:</span>
                                <span class="info-detail"><?= htmlspecialchars(date('d M Y', strtotime($row['dob']))) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Program:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['program_name'] ?? 'Unassigned') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Current Semester:</span>
                                <span class="info-detail"><?= htmlspecialchars((string)($row['current_semester'] ?? 1)) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Current Academic Year:</span>
                                <span class="info-detail"><?= htmlspecialchars((string)($row['current_academic_year'] ?? 1)) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['email']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Contact:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['contact_no'] ?? '—') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Admission Date:</span>
                                <span class="info-detail"><?= htmlspecialchars(date('d M Y', strtotime($row['admission_date']))) ?></span>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a class="btn btn-secondary" href="edit.php?student_id=<?= urlencode($row['student_id']) ?>">Edit</a>
                            <form action="delete.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this student record permanently? This cannot be undone.');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="id" value="<?= (int)$row['student_id'] ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                         </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>