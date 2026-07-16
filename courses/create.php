<?php
// Add new course (admin only)
session_start();

require_once '../includes/csrf_helper.php';
require_once '../config/db.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';

// Load all programmes for the dropdown (programme + faculty info)
$programs_res = $conn->query("
    SELECT program.program_id, program.program_name, faculty.faculty_name
    FROM program
    LEFT JOIN faculty ON program.faculty_id = faculty.faculty_id
    ORDER BY faculty.faculty_name ASC, program.program_name ASC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Block fake requests from other websites
    if (!csrf_validate()) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {

        // Read and clean form inputs
        $program_id   = trim($_POST['program_id'] ?? '');
        $course_name  = trim($_POST['course_name'] ?? '');
        $course_code  = trim($_POST['course_code'] ?? '');
        $credit_hours = trim($_POST['credit_hours'] ?? '');
        $duration     = trim($_POST['duration'] ?? '');
        $status       = trim($_POST['status'] ?? 'active');

        // Basic validation
        if ($course_name === '' || $course_code === '' || $credit_hours === '' || $duration === '' || $program_id === '') {
            $error = 'Please fill in all required fields, including programme assignment.';
        } elseif (!is_numeric($credit_hours) || (int)$credit_hours < 0) {
            $error = 'Credit Hours must be a positive integer value.';
        } elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = 'Please choose a valid course status.';
        } else {
            // Safe insert query (prevents SQL injection)
            $stmt = $conn->prepare("INSERT INTO course (program_id, course_name, course_code, credit_hours, duration, status) VALUES (?, ?, ?, ?, ?, ?)");
            $pId = (int)$program_id;
            $credits = (int)$credit_hours;

            $stmt->bind_param('ississ', $pId, $course_name, $course_code, $credits, $duration, $status);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'Course catalog item added successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Database storage mutation failed.';
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
    <title>Add Course Registry Structure</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📚 Register New Course</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <!-- CSRF protection token — required for validation to pass -->
            <?php csrf_field(); ?>

            <div>
                <label>Assigned Programme *</label>
                <select name="program_id" required>
                    <option value="">-- Select --</option>
                    <?php if ($programs_res): ?>
                        <?php while ($prog = $programs_res->fetch_assoc()): ?>
                            <option value="<?= (int)$prog['program_id'] ?>" <?= isset($_POST['program_id']) && $_POST['program_id'] == $prog['program_id'] ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($prog['faculty_name'] ?? 'General') ?>] <?= htmlspecialchars($prog['program_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label>Course Title *</label>
                <input type="text" name="course_name" value="<?= htmlspecialchars($_POST['course_name'] ?? '') ?>" placeholder="e.g., Database Systems Engineering">
            </div>

            <div>
                <label>Course Code Identifier *</label>
                <input type="text" name="course_code" value="<?= htmlspecialchars($_POST['course_code'] ?? '') ?>" placeholder="e.g., BIT204" style="text-transform: uppercase;">
            </div>

            <div>
                <label>Credit Hours Metric *</label>
                <input type="number" name="credit_hours" value="<?= htmlspecialchars($_POST['credit_hours'] ?? '') ?>" placeholder="e.g., 4" min="0" step="1">
            </div>

            <div>
                <label>Course Duration *</label>
                <input type="text" name="duration" value="<?= htmlspecialchars($_POST['duration'] ?? '') ?>" placeholder="e.g., 14 Weeks">
            </div>

            <div>
                <label>Course Status *</label>
                <select name="status" required>
                   <option value="active" <?= (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                   <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Add Course</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>