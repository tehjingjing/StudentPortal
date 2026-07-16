<?php
// View course page (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

if (!empty($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$baseQuery = "SELECT course.*, faculty.faculty_name, program.program_name
              FROM course
              LEFT JOIN program ON course.program_id = program.program_id
              LEFT JOIN faculty ON program.faculty_id = faculty.faculty_id";

if ($search !== '') {
    $stmt = $conn->prepare($baseQuery . " WHERE course.course_name LIKE ? OR course.course_code LIKE ? OR faculty.faculty_name LIKE ? OR program.program_name LIKE ? ORDER BY faculty.faculty_name ASC, program.program_name ASC, course.course_name ASC");
    $searchTerm = '%' . $search . '%';
    $stmt->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($baseQuery . ' ORDER BY faculty.faculty_name ASC, program.program_name ASC, course.course_name ASC');
}

$groupedCourses = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facultyKey = $row['faculty_name'] ?? 'General / Unassigned Faculty';
        $groupedCourses[$facultyKey][] = $row;
    }
}

$total_res = $conn->query('SELECT COUNT(*) AS cnt FROM course');
$totalCourses = $total_res ? $total_res->fetch_assoc()['cnt'] : 0;

$active_res = $conn->query("SELECT COUNT(*) AS cnt FROM course WHERE status = 'active'");
$totalActiveCourses = $active_res ? $active_res->fetch_assoc()['cnt'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management System</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=4">
</head>
<body>
    <div class="container">
        <header>
            <div class="title-area">
                <h1>📚 Course Directory</h1>
            </div>
            <div class="nav-actions">
                <a href="../dashboard/admin_dashboard.php" class="btn btn-secondary">← Back Dashboard</a>
                <a href="create.php" class="btn btn-primary">+ Register Course</a>
            </div>
        </header>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">✨ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <section class="summary-bar">
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Courses Created</h4>
                    <p><?= (int)$totalCourses ?></p>
                </div>
                <div class="icon">📖</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Active Courses</h4>
                    <p><?= (int)$totalActiveCourses ?></p>
                </div>
                <div class="icon">🟢</div>
            </div>
        </section>

        <div class="workspace-toolbar">
            <div class="search-container">
                <form action="index.php" method="GET">
                    <span class="search-icon">
                        <svg style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" placeholder="Search by name, code, programme, or faculty..." value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </div>

        <?php if (empty($groupedCourses)): ?>
            <div class="empty-state">
                <p>No systematic course structures match your filter keywords.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedCourses as $facultyName => $courses): ?>
                <div class="section-group">
                    <h3 class="section-heading">🏛️ <?= htmlspecialchars($facultyName) ?></h3>
                    <div class="records">
                        <?php foreach ($courses as $row): ?>
                            <div class="record-card">
                                <div class="card-header">
                                    <a href="edit.php?course_id=<?= urlencode($row['course_id']) ?>" class="record-title">
                                       <?= htmlspecialchars($row['course_code']) ?> - <?= htmlspecialchars($row['course_name']) ?>
                                    </a>
                                <span class="status-pill <?= ($row['status'] ?? 'inactive') === 'active' ? 'status-active' : 'status-inactive' ?>">
                                       <?= ucfirst(htmlspecialchars($row['status'] ?? 'inactive')) ?>
                                </span>
                                </div>

                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Programme:</span>
                                        <span class="info-detail"><?= htmlspecialchars($row['program_name'] ?? 'General') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Credit Hour:</span>
                                        <span class="info-detail"><?= (int)$row['credit_hours'] ?> Credits</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Duration:</span>
                                        <span class="info-detail"><?= htmlspecialchars($row['duration'] ?? 'Not set') ?></span>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <a class="btn btn-secondary" href="edit.php?course_id=<?= urlencode($row['course_id']) ?>">Edit</a>
                                    <form action="delete.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this course record permanently? This cannot be undone.');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="course_id" value="<?= htmlspecialchars($row['course_id']) ?>">
                                        <button type="submit" class="btn btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
