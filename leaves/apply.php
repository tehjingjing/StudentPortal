<?php
// Student leave application page (student only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';

// Redirect non-student users to admin dashboard
if ($_SESSION['role'] !== 'student') {
    header('Location: ../dashboard/admin_dashboard.php');
    exit();
}

// Show one-time success message, then clear it from session
$successMsg = $_SESSION['success_msg'] ?? null;
unset($_SESSION['success_msg']);

// Load all leave applications for the current student
$leaves = null;
if (!empty($_SESSION['student_id'])) {
    $stmt = $conn->prepare('SELECT * FROM `leave` WHERE student_id = ? ORDER BY start_date DESC');
    $stmt->bind_param('i', $_SESSION['student_id']);
    $stmt->execute();
    $leaves = $stmt->get_result();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave Applications</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>
    <div class="app-shell">
        <?php $activePage = 'leave'; require_once '../includes/student_sidebar.php'; ?>

        <main class="app-main">
            <header>
                <h1>📝 My Leave Applications</h1>
                <div class="header-actions">
                    <a class="logout-btn" href="../auth/logout.php">Log Out</a>
                </div>
            </header>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>

            <section class="panel">
                <h2>Leave Application Form</h2>
                <form action="submit_leave.php" method="POST" enctype="multipart/form-data">
                    <?php csrf_field(); ?>

                    <div class="form-row">
                        <div>
                            <label>Start Date *</label>
                            <input type="date" name="start_date" required>
                        </div>
                        <div>
                            <label>End Date *</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>

                    <label>Reason *</label>
                    <textarea name="reason" required></textarea>

                    <label>Supporting Document (PDF, JPG, or PNG — optional, max 5MB)</label>
                    <input type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png">

                    <div class="btn-box">
                        <button type="submit" class="btn btn-primary">Submit Application</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2>Application History</h2>
                <?php if (!$leaves || $leaves->num_rows === 0): ?>
                    <div class="empty-state">
                        <strong>No applications found</strong>
                        <p>You haven't submitted any leave applications yet.</p>
                    </div>
                <?php else: ?>
                    <div class="leave-list">
                        <?php while ($lv = $leaves->fetch_assoc()): ?>
                            <div class="leave-row">
                                <div class="leave-row-info">
                                    <p><strong>Reason:</strong> <?= nl2br(htmlspecialchars($lv['reason'])) ?></p>
                                    <p><strong>Period:</strong> <?= htmlspecialchars($lv['start_date']) ?> to <?= htmlspecialchars($lv['end_date']) ?></p>
                                    <?php if (!empty($lv['evidence'])):
                                        $evidenceFile = basename($lv['evidence']);
                                        $evidencePath = __DIR__ . '/../uploads/leavedoc/' . $evidenceFile;
                                        $evidenceUrl = '../uploads/leavedoc/' . rawurlencode($evidenceFile);
                                    ?>
                                        <?php if (file_exists($evidencePath)): ?>
                                            <p><a class="link-evidence" href="<?= $evidenceUrl ?>" target="_blank">📎 View Uploaded Document</a></p>
                                        <?php else: ?>
                                            <p><span class="attachment-missing">⚠ Attached document not found on server</span></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="leave-row-actions">
                                    <span class="status-pill status-<?= htmlspecialchars($lv['status']) ?>">
                                        <?= htmlspecialchars(ucfirst($lv['status'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>