<?php
// Admin page to add new academic program (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';
// Load all faculties for dropdown
$faculties_res = $conn->query("SELECT * FROM faculty ORDER BY faculty_name ASC");

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection check
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Get & clean input data
        $faculty_id             = trim($_POST['faculty_id'] ?? '');
        $program_name           = trim($_POST['program_name'] ?? '');
        $program_code           = trim($_POST['program_code'] ?? '');
        $level                  = trim($_POST['level'] ?? '');
        $total_credits_required = trim($_POST['total_credits_required'] ?? '');
        $status                 = trim($_POST['status'] ?? 'active');

        // Check all required fields filled
        if ($faculty_id === '' || $program_name === '' || $program_code === '' || $level === '' || $total_credits_required === '') {
            $error = 'Please fill in all required fields.';
        } 
        // Check credit number is valid positive integer
        elseif (!is_numeric($total_credits_required) || (int)$total_credits_required < 0) {
            $error = 'Total credits must be a positive whole number.';
        } 
        // Restrict status to only active/inactive
        elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = 'Please select a valid program status.';
        } 
        else {
            // Check duplicate program code
            $check = $conn->prepare("SELECT program_id FROM program WHERE program_code = ? LIMIT 1");
            $check->bind_param("s", $program_code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'This program code already exists.';
            }
            $check->close();

            // No duplicate, insert new program
            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO program (faculty_id, program_name, program_code, level, total_credits_required, status) VALUES (?, ?, ?, ?, ?, ?)");
                $facId = (int)$faculty_id;
                $credits = (int)$total_credits_required;
                
                $stmt->bind_param("isssis", $facId, $program_name, $program_code, $level, $credits, $status);
                
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = 'New program added successfully.';
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Failed to save program data to database.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Academic Program</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>Register New Program</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <?php csrf_field(); ?>

            <div>
                <label>Assigned faculty *</label>
                <select name="faculty_id" required>
                    <option value="">-- Select --</option>
                    <?php if ($faculties_res): ?>
                        <?php while ($fac = $faculties_res->fetch_assoc()): ?>
                            <option value="<?= (int)$fac['faculty_id'] ?>" <?= (isset($_POST['faculty_id']) && $_POST['faculty_id'] == $fac['faculty_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fac['faculty_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label>Program Name *</label>
                <input type="text" name="program_name" value="<?= htmlspecialchars($_POST['program_name'] ?? '') ?>" placeholder="e.g., Bachelor of Computer Science (Honours)" required>
            </div>

            <div>
                <label>Program Code *</label>
                <input type="text" name="program_code" value="<?= htmlspecialchars($_POST['program_code'] ?? '') ?>" placeholder="e.g., BCS" style="text-transform: uppercase;" required>
            </div>

            <div>
                <label>Academic Certification Level *</label>
                <select name="level" required>
                    <option value="">-- Select --</option>
                    <?php foreach (['Foundation', 'Diploma', 'Bachelor', 'Master', 'PhD'] as $lvl): ?>
                        <option value="<?= $lvl ?>" <?= (isset($_POST['level']) && $_POST['level'] === $lvl) ? 'selected' : '' ?>><?= $lvl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Total Graduation Credits Required *</label>
                <input type="number" name="total_credits_required" value="<?= htmlspecialchars($_POST['total_credits_required'] ?? '120') ?>" min="0" step="1" required>
            </div>

            <div>
                <label>Status *</label>
                <select name="status" required>
                   <option value="active" <?= (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                   <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Register Program</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>