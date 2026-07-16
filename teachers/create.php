<?php
// register new teacher (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

// Load all faculties for dropdown selection
$faculties = $conn->query("SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name ASC");

$error = '';

// Handle form POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF cross-site request protection
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Get and clean all form inputs
        $teacher_name = trim($_POST['teacher_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $joining_date = $_POST['joining_date'] ?? '';
        $status       = $_POST['status'] ?? 'active';

        // Mandatory field validation
        if (empty($teacher_name) || empty($email) || empty($joining_date)) {
            $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
        } 
        // Validate email format
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Restrict status to only allowed values
            if (!in_array($status, ['active', 'resigned'], true)) {
                $status = 'active';
            }

            // Handle empty faculty selection as NULL
            $faculty_id = trim($_POST['faculty_id'] ?? '');
            $faculty_id = $faculty_id === '' ? null : (int)$faculty_id;

            // Prepared SQL insert for new teacher record
            $stmt = $conn->prepare(
                'INSERT INTO teacher (teacher_name, email, phone, faculty_id, joining_date, status) 
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            // Bind data types: string, string, string, integer|null, string, string
            $stmt->bind_param('sssiss', $teacher_name, $email, $phone, $faculty_id, $joining_date, $status);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'New teacher registered successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to save teacher data to database.';
                error_log('Teacher insert failed: ' . $stmt->error);
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
    <title>New Teacher Registration</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Register New Teacher</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <!-- CSRF hidden security token -->
            <?php csrf_field(); ?>
            
            <fieldset>
                <legend>Personal Profile</legend>
                <label>Full Name *</label>
                <input type="text" name="teacher_name" value="<?= htmlspecialchars($_POST['teacher_name'] ?? '') ?>" required>

                <div class="grid-2">
                    <div>
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>Contact Number</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
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
                                <option value="<?= (int)$f['faculty_id'] ?>" <?= (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $f['faculty_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['faculty_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>Admission Date *</label>
                        <input type="date" name="joining_date" value="<?= htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                </div>

                <label>Status *</label>
                <select name="status" required>
                    <option value="active" <?= (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="resigned" <?= (isset($_POST['status']) && $_POST['status'] === 'resigned') ? 'selected' : '' ?>>Resigned</option>
                </select>
            </fieldset>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Register Teacher</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>