<?php
// Admin page to edit existing teacher record (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Load all faculties for dropdown list
$faculties = $conn->query("SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name ASC");

// Get teacher ID from URL (load page) or POST form submit
$teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : (int)($_POST['teacher_id'] ?? 0);

// Redirect if invalid ID
if ($teacher_id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid teacher.'));
    exit();
}

$error = '';

// Handle form update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF cross-site request forgery protection
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Get and trim all form input data
        $teacher_name = trim($_POST['teacher_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $joining_date = trim($_POST['joining_date'] ?? '');
        $status       = trim($_POST['status'] ?? 'active');

        // Validate all mandatory fields filled
        if (empty($teacher_name) || empty($email) || empty($joining_date)) {
            $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
        } 
        // Validate email format
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Restrict status to allowed values only
            if (!in_array($status, ['active', 'resigned'], true)) {
                $status = 'active';
            }

            // Empty faculty selection stored as NULL
            $faculty_id = trim($_POST['faculty_id'] ?? '');
            $faculty_id = $faculty_id === '' ? null : (int)$faculty_id;

            // Prepared UPDATE statement to modify teacher data
            $stmt = $conn->prepare(
                'UPDATE teacher SET teacher_name = ?, email = ?, phone = ?, faculty_id = ?, joining_date = ?, status = ? WHERE teacher_id = ?'
            );
            $stmt->bind_param('sssissi', $teacher_name, $email, $phone, $faculty_id, $joining_date, $status, $teacher_id);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'Teacher profile updated successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to save changes to database.';
                error_log('Teacher update failed: ' . $stmt->error);
            }
            $stmt->close();
        }
    }
}

// Fetch original teacher record from database
$stmt = $conn->prepare('SELECT * FROM teacher WHERE teacher_id = ? LIMIT 1');
$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if teacher record does not exist
if (!$teacher) {
    header('Location: index.php?error=' . urlencode('Teacher does not exist.'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile · Teacher Portal</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Edit Teacher Profile</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit.php" method="POST">
            <!-- CSRF hidden security token -->
            <?php csrf_field(); ?>
            <input type="hidden" name="teacher_id" value="<?= (int)$teacher['teacher_id'] ?>">

            <fieldset>
                <legend>Personal Profile</legend>
                <label>Full Name *</label>
                <input type="text" name="teacher_name" value="<?= htmlspecialchars($teacher['teacher_name']) ?>" required>

                <div class="grid-2">
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($teacher['email']) ?>" required>
                    </div>
                    <div>
                        <label>Contact</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Employment Details</legend>
                <div class="grid-2">
                    <div>
                        <label>Assigned Faculty</label>
                        <select name="faculty_id">
                            <option value="">-- Select Faculty --</option>
                            <?php while ($f = $faculties->fetch_assoc()): ?>
                                <?php 
                                // Keep selected value after form submit error, fallback to original teacher data
                                $selectedVal = isset($_POST['faculty_id']) ? $_POST['faculty_id'] : $teacher['faculty_id'];
                                $selected = ($selectedVal == $f['faculty_id']) ? 'selected' : '';
                                ?>
                                <option value="<?= (int)$f['faculty_id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($f['faculty_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>Admission Date *</label>
                        <input type="date" name="joining_date" value="<?= htmlspecialchars($teacher['joining_date']) ?>" required>
                    </div>
                </div>

                <label>Status *</label>
                <select name="status" required>
                    <option value="active" <?= $teacher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="resigned" <?= $teacher['status'] === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                </select>
            </fieldset>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>