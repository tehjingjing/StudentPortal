<?php
// Admin teacher list & search dashboard page (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Show success notification once then clear session
if (!empty($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Get search keyword from URL parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query: join teacher and faculty table to display faculty name
$baseQuery = "SELECT teacher.*, faculty.faculty_name 
FROM teacher 
LEFT JOIN faculty ON teacher.faculty_id = faculty.faculty_id";

// Search filter: match teacher name + email, sorted by teacher ID ascending
if ($search !== '') {
    $stmt = $conn->prepare($baseQuery . " 
WHERE teacher.teacher_name LIKE ? OR teacher.email LIKE ? 
ORDER BY teacher.teacher_id ASC");
    $searchTerm = '%' . $search . '%';
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // No search: show newest teachers first by teacher_id descending
    $result = $conn->query($baseQuery . ' ORDER BY teacher.teacher_id DESC');
}

// Calculate summary statistics for top statistic cards
$total_res = $conn->query('SELECT COUNT(*) AS cnt FROM teacher');
$total = $total_res ? $total_res->fetch_assoc()['cnt'] : 0;

$active_res = $conn->query("SELECT COUNT(*) AS cnt FROM teacher WHERE status = 'active'");
$activeTeachers = $active_res ? $active_res->fetch_assoc()['cnt'] : 0;

$resigned_res = $conn->query("SELECT COUNT(*) AS cnt FROM teacher WHERE status = 'resigned'");
$totalResigned = $resigned_res ? $resigned_res->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers · Admin Workspace</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=4">
</head>
<body>
    <div class="container">
        <header>
            <div class="title-area">
                <h1>🧑‍🏫 Teacher Directory</h1>
            </div>
            <div class="nav-actions">
                <a href="../dashboard/admin_dashboard.php" class="btn btn-secondary">← Back Dashboard</a>
                <a href="create.php" class="btn btn-primary">+ Register Teacher</a>
            </div>
        </header>

        <!-- Success alert after create/update/delete teacher -->
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
                    <h4>Total Registered Teachers</h4>
                    <p><?= (int)$total ?></p>
                </div>
                <div class="icon">🧑‍🏫</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Active Profiles</h4>
                    <p><?= (int)$activeTeachers ?></p>
                </div>
                <div class="icon">🟢</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Resigned Teachers</h4>
                    <p><?= (int)$totalResigned ?></p>
                </div>
                <div class="icon">✍🏻</div>
            </div>
        </section>
        <div class="workspace-toolbar">
            <div class="search-container">
                <form action="index.php" method="GET">
                    <span class="search-icon">
                        <svg class="meta-icon" style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" placeholder="Filter by teacher name or teacher email" value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </div>

        <?php if (!$result || $result->num_rows === 0): ?>
            <div class="empty-state">
                <p>No teacher records match the filter conditions.</p>
            </div>
        <?php else: ?>
            <div id="recordsContainer" class="records">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="record-card">
                        <div class="card-header">
                            <a href="edit.php?teacher_id=<?= urlencode($row['teacher_id']) ?>" class="record-title">
                                <?= htmlspecialchars($row['teacher_name']) ?>
                            </a>
                            <span class="status-pill <?= $row['status'] === 'active' ? 'status-active' : 'status-resigned' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Teacher ID:</span>
                                <span class="info-detail">#<?= htmlspecialchars($row['teacher_id']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['email']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Contact:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['phone'] ?? '—') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Faculty:</span>
                                <span class="info-detail"><?= htmlspecialchars($row['faculty_name'] ?? 'Unassigned') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Admission Date:</span>
                                <span class="info-detail"><?= htmlspecialchars(date('d M Y', strtotime($row['joining_date']))) ?></span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a class="btn btn-secondary" href="edit.php?teacher_id=<?= urlencode($row['teacher_id']) ?>">Edit</a>
                            <!-- Delete form with CSRF security token -->
                            <form action="delete.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this teacher record permanently? This cannot be undone.');">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="id" value="<?= (int)$row['teacher_id'] ?>">
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