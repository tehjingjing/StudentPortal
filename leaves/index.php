<?php
// Manage leave applications (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Show one-time success message, then remove it from session
if (!empty($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Load all leave applications with student names
$query = "SELECT `leave`.*, student.full_name
          FROM `leave`
          JOIN student ON `leave`.student_id = student.student_id
          ORDER BY leave.approved_by DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave Applications</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body>

<div class="container">
    <header>
        <div class="title-area">
            <h1>📋 Leave Application Management</h1>
        </div>
        <a href="../dashboard/admin_dashboard.php" class="btn btn-secondary">← Back Dashboard</a>
    </header>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>

    <div class="leave-list">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="leave-row">
                    <div class="leave-row-info">
                        <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                        <p><strong>Reason:</strong> <?= htmlspecialchars($row['reason']) ?></p>
                        <p><strong>Duration:</strong> <?= htmlspecialchars($row['start_date']) ?> to <?= htmlspecialchars($row['end_date']) ?></p>
                        <?php if (!empty($row['evidence'])): ?>
                            <p><a class="link-evidence" href="../uploads/leavedoc/<?= urlencode($row['evidence']) ?>" target="_blank">📎 View Attached Document</a></p>
                        <?php else: ?>
                            <p style="color:#94a3b8; font-style: italic;">No document attached</p>
                        <?php endif; ?>
                    </div>

                    <div class="leave-row-actions">
                        <?php if ($row['status'] === 'pending'): ?>
                            <form action="process_leave.php" method="POST" style="display: flex; gap: 8px;">
                                <!-- CSRF protection token -->
                                <?php csrf_field(); ?>
                                
                                <input type="hidden" name="leave_id" value="<?= (int)$row['leave_id'] ?>">
                                <button type="submit" name="action" value="approved" class="btn btn-approve">Approve</button>
                                <button type="submit" name="action" value="rejected" class="btn btn-reject">Reject</button>
                            </form>
                        <?php else: ?>
                            <span class="status-pill status-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: #64748b; padding: 40px;">No leave applications found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>