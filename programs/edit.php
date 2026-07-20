<?php
// Admin edit existing program page (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';
// Get program ID from URL (load page) or POST (submit form)
$program_id = isset($_GET['program_id']) ? (int)$_GET['program_id'] : (int)($_POST['program_id'] ?? 0);

// Reject invalid ID
if ($program_id <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid program.'));
    exit();
}

// Load all faculties for dropdown list
$faculties_res = $conn->query("SELECT faculty_id, faculty_name FROM faculty ORDER BY faculty_name ASC");

// Fetch target program original data
$stmt = $conn->prepare("SELECT * FROM program WHERE program_id = ? LIMIT 1");
$stmt->bind_param("i", $program_id);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if program record not found
if (!$program) {
    header('Location: index.php?error=' . urlencode('Program does not exist.'));
    exit();
}

// Handle form update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF cross-site request protection
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Read and trim all form inputs
        $faculty_id             = trim($_POST['faculty_id'] ?? '');
        $program_name           = trim($_POST['program_name'] ?? '');
        $program_code           = trim($_POST['program_code'] ?? '');
        $level                  = trim($_POST['level'] ?? '');
        $total_credits_required = trim($_POST['total_credits_required'] ?? '');
        $status                 = trim($_POST['status'] ?? 'active');

        // Validate all required fields filled
        if ($faculty_id === '' || $program_name === '' || $program_code === '' || $level === '' || $total_credits_required === '') {
            $error = 'Please fill in all mandatory program fields.';
        } 
        // Check credit value is non-negative integer
        elseif (!is_numeric($total_credits_required) || (int)$total_credits_required < 0) {
            $error = 'Total graduation credits must be a positive integer value.';
        } 
        // Restrict status to only allowed values
        elseif (!in_array($status, ['active', 'inactive'], true)) {
            $error = 'Please choose a valid program status.';
        } 
        else {
            // Check duplicate program code, skip current editing record
            $check = $conn->prepare("SELECT program_id FROM program WHERE program_code = ? AND program_id != ? LIMIT 1");
            $check->bind_param("si", $program_code, $program_id);
            $check->execute();

            if ($check->get_result()->num_rows > 0) {
                $error = 'This Program Code is already registered within another pathway.';
            }
            $check->close();

            // No duplicate found, run update query
            if ($error === '') {
                $stmt = $conn->prepare("UPDATE program SET faculty_id = ?, program_name = ?, program_code = ?, level = ?, total_credits_required = ?, status = ? WHERE program_id = ?");
                $facId = (int)$faculty_id;
                $credits = (int)$total_credits_required;

                $stmt->bind_param("isssisi", $facId, $program_name, $program_code, $level, $credits, $status, $program_id);

                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = 'Program catalog configuration item updated successfully.';
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Database update failed.';
                }
                $stmt->close();
            }
        }
    }
}

// Keep user input after validation error, fallback to original program data
$form = array_merge($program, $_POST);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Academic Program</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Edit Program</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit.php" method="POST">
            <?php csrf_field(); ?>
            
            <input type="hidden" name="program_id" value="<?= (int)$program['program_id'] ?>">

            <div>
                <label>Assigned Faculty *</label>
                <select name="faculty_id" required>
                    <option value="">-- Select --</option>
                    <?php if ($faculties_res): ?>
                        <?php while ($fac = $faculties_res->fetch_assoc()): ?>
                            <option value="<?= $fac['faculty_id'] ?>" <?= (string)($form['faculty_id'] ?? '') === (string)$fac['faculty_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fac['faculty_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label>Program Name *</label>
                <input type="text" name="program_name" value="<?= htmlspecialchars($form['program_name'] ?? '') ?>" placeholder="e.g., Bachelor of Computer Science (Honours)" required>
            </div>

            <div>
                <label>Program Code *</label>
                <input type="text" name="program_code" value="<?= htmlspecialchars($form['program_code'] ?? '') ?>" placeholder="e.g., BCS" style="text-transform: uppercase;" required>
            </div>

            <div>
                <label>Academic Certification Level *</label>
                <select name="level" required>
                    <option value="">-- Select --</option>
                    <?php foreach (['Foundation', 'Diploma', 'Bachelor', 'Master', 'PhD'] as $lvl): ?>
                        <option value="<?= $lvl ?>" <?= ($form['level'] ?? '') === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Total Graduation Credits Required *</label>
                <input type="number" name="total_credits_required" value="<?= htmlspecialchars($form['total_credits_required'] ?? '120') ?>" min="0" step="1" required>
            </div>

            <div>
                <label>Status *</label>
                <select name="status" required>
                    <option value="active" <?= ($form['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($form['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
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
