<?php
// Admin program list & search page (admin only)
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

// Base SQL query: join program + faculty, count linked courses
$baseQuery = "SELECT program.*, faculty.faculty_name,
              (SELECT COUNT(*) FROM course WHERE course.program_id = program.program_id) AS course_count
              FROM program
              LEFT JOIN faculty ON program.faculty_id = faculty.faculty_id";

// Handle search filter
if ($search !== '') {
    $stmt = $conn->prepare($baseQuery . " WHERE program.program_name LIKE ? OR program.program_code LIKE ? OR faculty.faculty_name LIKE ? ORDER BY faculty.faculty_name ASC, program.program_name ASC");
    $searchTerm = '%' . $search . '%';
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $conn->query($baseQuery . ' ORDER BY faculty.faculty_name ASC, program.program_name ASC');
}

// Group all programs by faculty name
$groupedPrograms = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $facultyName = $row['faculty_name'] ?? 'Unassigned / General Clusters';
        $groupedPrograms[$facultyName][] = $row;
    }
}

// Get dashboard summary metrics
$total_res = $conn->query('SELECT COUNT(*) AS cnt FROM program');
$totalPrograms = $total_res ? $total_res->fetch_assoc()['cnt'] : 0;

$active_res = $conn->query("SELECT COUNT(*) AS cnt FROM program WHERE status = 'active'");
$totalActivePrograms = $active_res ? $active_res->fetch_assoc()['cnt'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Programs</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=4">
</head>
<body>
    <div class="container">
        <header>
            <div class="title-area">
                <h1>🎓 Program Directory</h1>
            </div>
            <div class="nav-actions">
                <a href="../dashboard/admin_dashboard.php" class="btn btn-secondary">← Back Dashboard</a>
                <a href="create.php" class="btn btn-primary">+ Register Program</a>
            </div>
        </header>

        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success">✨ <?= htmlspecialchars($successMsg) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <!-- Summary statistic cards -->
        <section class="summary-bar">
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Programs Created</h4>
                    <p><?= (int)$totalPrograms ?></p>
                </div>
                <div class="icon">📘</div>
            </div>
            <div class="summary-card">
                <div class="metrics">
                    <h4>Total Active Programs</h4>
                    <p><?= (int)$totalActivePrograms ?></p>
                </div>
                <div class="icon">🟢</div>
            </div>
        </section>

        <!-- Search bar form -->
        <div class="workspace-toolbar">
            <div class="search-container">
                <form action="index.php" method="GET">
                     <span class="search-icon">
                        <svg style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" name="search" placeholder="Search program name, code, or faculty..." value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </div>

        <?php if (empty($groupedPrograms)): ?>
            <div class="empty-state">
                <p>No active programs found matching criteria constraints.</p>
            </div>
        <?php else: ?>
            <!-- Loop each faculty group -->
            <?php foreach ($groupedPrograms as $facultyName => $programs): ?>
                <div class="section-group">
                    <h3 class="section-heading">🏛️ <?= htmlspecialchars($facultyName) ?></h3>
                    
                    <div class="records">
                        <!-- Loop single program card -->
                        <?php foreach ($programs as $row): ?>
                            <div class="record-card">
                                <div class="card-header">
                                    <a href="edit.php?program_id=<?= urlencode($row['program_id']) ?>" class="record-title">
                                        <?= htmlspecialchars($row['program_name']) ?>
                                    </a>
                                    <span class="status-pill <?= ($row['status'] ?? 'inactive') === 'active' ? 'status-active' : 'status-inactive' ?>">
                                        <?= ucfirst(htmlspecialchars($row['status'] ?? 'inactive')) ?>
                                    </span>
                                </div>

                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Program Code:</span>
                                        <span class="info-detail" style="font-family: monospace; font-weight:600; color:#4f46e5; text-transform:uppercase;"><?= htmlspecialchars($row['program_code']) ?></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Academic Level:</span>
                                        <span class="info-detail"><span class="badge badge-info"><?= htmlspecialchars($row['level']) ?></span></span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Credits Required:</span>
                                        <span class="info-detail"><?= (int)$row['total_credits_required'] ?> Units</span>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label">Linked Courses:</span>
                                        <span class="info-detail" style="font-weight: 600; color: #0f172a;"><?= (int)$row['course_count'] ?> Active</span>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <a class="btn btn-secondary" href="edit.php?program_id=<?= urlencode($row['program_id']) ?>">Edit</a>
                                    <!-- Delete form with CSRF token -->
                                    <form action="delete.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this program record permanently? This cannot be undone.');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="program_id" value="<?= htmlspecialchars($row['program_id']) ?>">
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