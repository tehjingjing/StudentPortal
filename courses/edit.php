<?php
// Edit course page (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';

// Get course ID from URL (page load) or form (submit)
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : (int)($_POST['course_id'] ?? 0);

// Reject bad IDs immediately
if ($course_id <= 0) {
    header('Location: index.php');
    exit();
}

// Load all programmes for the dropdown menu
$programs_res = $conn->query("
    SELECT program.program_id, program.program_name, faculty.faculty_name
    FROM program
    LEFT JOIN faculty ON program.faculty_id = faculty.faculty_id
    ORDER BY faculty.faculty_name ASC, program.program_name ASC
");

// Load existing course data from database
$stmt = $conn->prepare("SELECT * FROM course WHERE course_id = ? LIMIT 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If course doesn't exist, go back to list
if (!$course) {
    header('Location: index.php?error=' . urlencode('Target course record not found.'));
    exit();
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Block fake requests from other websites
    if (!csrf_validate()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {

        // Read form inputs
        $program_id   = trim($_POST['program_id'] ?? '');
        $course_name  = trim($_POST['course_name'] ?? '');
        $course_code  = trim($_POST['course_code'] ?? '');
        $credit_hours = trim($_POST['credit_hours'] ?? '');
        $duration     = trim($_POST['duration'] ?? '');
        $status       = trim($_POST['status'] ?? 'active');

        // Validate inputs
        if ($course_name === '' || $course_code === '' || $credit_hours === '' || $duration === '' || $program_id === '') {
            $error = 'Please fill in all required parameter fields.';
        } elseif (!is_numeric($credit_hours) || (int)$credit_hours < 0) {
            $error = 'Credit Hours must be a valid positive number.';
        } elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = 'Please choose a valid course status.';
        } else {
            // Safe update query (no SQL injection)
            $stmt = $conn->prepare("UPDATE course SET program_id = ?, course_name = ?, course_code = ?, credit_hours = ?, duration = ?, status = ? WHERE course_id = ?");
            $pId = (int)$program_id;
            $credits = (int)$credit_hours;
            $stmt->bind_param('ississi', $pId, $course_name, $course_code, $credits, $duration, $status, $course_id);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'Course configuration altered successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to update course information.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Edit Course</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit.php" method="POST">
            <?php csrf_field(); ?>
            
            <input type="hidden" name="course_id" value="<?= (int)$course['course_id'] ?>">

            <div>
                <label>Assigned Programme *</label>
                <select name="program_id" required>
                    <option value="">-- Select --</option>
                    <?php if ($programs_res): ?>
                        <?php while ($prog = $programs_res->fetch_assoc()): ?>
                            <option value="<?= (int)$prog['program_id'] ?>" <?= ($course['program_id'] == $prog['program_id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($prog['faculty_name'] ?? 'General') ?>] <?= htmlspecialchars($prog['program_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label>Course Name *</label>
                <input type="text" name="course_name" value="<?= htmlspecialchars($course['course_name']) ?>" placeholder="e.g., Database Systems Engineering">
            </div>

            <div>
                <label>Course Code *</label>
                <input type="text" name="course_code" value="<?= htmlspecialchars($course['course_code']) ?>" placeholder="e.g., BIT204" style="text-transform: uppercase;">
            </div>

            <div>
                <label>Credit Hours *</label>
                <input type="number" name="credit_hours" value="<?= htmlspecialchars($course['credit_hours']) ?>" placeholder="e.g., 4" min="0" step="1">
            </div>

            <div>
                <label>Course Duration *</label>
                <input type="text" name="duration" value="<?= htmlspecialchars($course['duration'] ?? '') ?>" placeholder="e.g., 14 Weeks">
            </div>

            <div>
                <label>Status *</label>
                <select name="status" required>
                  <option value="active" <?= ($course['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                  <option value="inactive" <?= ($course['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Update Record</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>