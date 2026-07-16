<?php
// create a student (admin only)
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';

// Load all programs for dropdown list
$programs_res = $conn->query("SELECT program_id, program_name FROM program ORDER BY program_name ASC");

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF security check
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Get and trim all form inputs
        $program_id     = trim($_POST['program_id'] ?? '');
        $full_name      = trim($_POST['full_name'] ?? '');
        $dob            = $_POST['dob'] ?? '';
        $gender         = $_POST['gender'] ?? '';
        $address        = trim($_POST['address'] ?? '');
        $contact_no     = trim($_POST['contact_no'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $admission_date = $_POST['admission_date'] ?? '';
        $status         = $_POST['status'] ?? 'active';
        $current_semester = trim($_POST['current_semester'] ?? '1');
        $current_academic_year = trim($_POST['current_academic_year'] ?? '1');
        $parent_name    = trim($_POST['parent_name'] ?? '');
        $parent_contact = trim($_POST['parent_contact'] ?? '');
        $parent_email   = trim($_POST['parent_email'] ?? '');

        // Required field validation
        if (empty($program_id) || empty($full_name) || empty($admission_date) || empty($dob) || empty($gender) || empty($email)) {
            $error = 'Please fill in all mandatory fields.';
        } 
        // Email format check
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Prepare insert statement for new student record
            $stmt = $conn->prepare(
                "INSERT INTO student (program_id, full_name, dob, gender, address, contact_no, email, admission_date, status, current_semester, current_academic_year, parent_name, parent_contact, parent_email)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            // Cast numeric fields to integer
            $progIdMapped = (int)$program_id;
            $currentSemMapped = (int)$current_semester;
            $currentYearMapped = (int)$current_academic_year;

            $stmt->bind_param(
                "issssssssiisss",
                $progIdMapped, $full_name, $dob, $gender, $address, $contact_no, $email,$admission_date, $status, $currentSemMapped, $currentYearMapped, $parent_name, $parent_contact, $parent_email
            );

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'New student registered successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to save student data.';
                error_log('Student insert failed: ' . $stmt->error);
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
    <title>New Student Enrollment</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Register New Student</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <?php csrf_field(); ?>
            
            <fieldset>
                <legend>Personal Profile</legend>
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

                <div class="grid-2">
                    <div>
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">-- Select Gender --</option>
                            <option value="Male" <?= isset($_POST['gender']) && $_POST['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= isset($_POST['gender']) && $_POST['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label>Contact</label>
                        <input type="text" name="contact_no" value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>">
                    </div>
                </div>

                <label>Address</label>
                <textarea name="address" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Academic Parameters</legend>

                <label>Assigned Program *</label>
                <select name="program_id" required>
                    <option value="">-- Choose Program --</option>
                    <?php if ($programs_res): ?>
                        <?php while ($prog = $programs_res->fetch_assoc()): ?>
                            <option value="<?= (int)$prog['program_id'] ?>" <?= isset($_POST['program_id']) && $_POST['program_id'] == $prog['program_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prog['program_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>

                <div class="grid-2">
                    <div>
                        <label>Admission Date *</label>
                        <input type="date" name="admission_date" value="<?= htmlspecialchars($_POST['admission_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div>
                        <label>Enrollment Status *</label>
                        <select name="status" required>
                            <option value="active" <?= !isset($_POST['status']) || $_POST['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="graduated" <?= isset($_POST['status']) && $_POST['status'] === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                            <option value="dropout" <?= isset($_POST['status']) && $_POST['status'] === 'dropout' ? 'selected' : '' ?>>Dropout</option>
                        </select>
                    </div>
                </div>

                <label>Current Semester (Progress) *</label>
                <input type="number" name="current_semester" min="1" max="5"
                       value="<?= htmlspecialchars($_POST['current_semester'] ?? '1') ?>" required>
                <label>Current Academic Year (Progress) *</label>
                <input type="number" name="current_academic_year" min="1" max="2099"
                       value="<?= htmlspecialchars($_POST['current_academic_year'] ?? '2026') ?>" required>
            </fieldset>

            <fieldset>
                <legend>Guardian Emergency Contact Details</legend>
                <label>Guardian Full Name</label>
                <input type="text" name="parent_name" value="<?= htmlspecialchars($_POST['parent_name'] ?? '') ?>">

                <div class="grid-2">
                    <div>
                        <label>Guardian Contact</label>
                        <input type="text" name="parent_contact" value="<?= htmlspecialchars($_POST['parent_contact'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Guardian Email</label>
                        <input type="email" name="parent_email" value="<?= htmlspecialchars($_POST['parent_email'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Register Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>