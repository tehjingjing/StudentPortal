<?php
// Admin edit existing student profile page
session_start();

require_once '../config/db.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/require_login.php';
require_once '../includes/require_admin.php';

$error = '';
// Get student ID from URL (load page) or POST form submit
$student_id = (int)($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

// Redirect if invalid student ID
if ($student_id <= 0) {
    header('Location: index.php');
    exit;
}

// Load all programs for dropdown selection
$programs_res = $conn->query("SELECT program_id, program_name FROM program ORDER BY program_name ASC");

// Fetch target student original data from database
$stmt = $conn->prepare('SELECT * FROM student WHERE student_id = ? LIMIT 1');
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if student record not found
if (!$student) {
    header('Location: index.php?error=' . urlencode('Student does not exist'));
    exit;
}

// Handle form update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cross-site request forgery protection check
    if (!csrf_validate()) {
        $error = 'Invalid request. Refresh page and try again.';
    } else {
        // Get and trim all form input data
        $program_id     = trim($_POST['program_id'] ?? '');
        $full_name      = trim($_POST['full_name'] ?? '');
        $dob            = $_POST['dob'] ?? '';
        $gender         = $_POST['gender'] ?? '';
        $address        = trim($_POST['address'] ?? '');
        $contact_no     = trim($_POST['contact_no'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $admission_date = $_POST['admission_date'] ?? '';
        $status         = $_POST['status'] ?? '';
        $current_semester = trim($_POST['current_semester'] ?? '1');
        $current_academic_year = trim($_POST['current_academic_year'] ?? '1');
        $parent_name    = trim($_POST['parent_name'] ?? '');
        $parent_contact = trim($_POST['parent_contact'] ?? '');
        $parent_email   = trim($_POST['parent_email'] ?? '');

        // Validate all required fields are filled
        if (empty($program_id) || empty($full_name) || empty($admission_date) || empty($dob) || empty($gender) || empty($email)) {
            $error = 'Please fill in all required fields including assigned program.';
        } else {
            // Prepare update SQL statement to modify student record
            $stmt = $conn->prepare("UPDATE student SET program_id=?, full_name=?, dob=?, gender=?, address=?, contact_no=?, email=?, admission_date=?, status=?, current_semester=?, current_academic_year=?, parent_name=?, parent_contact=?, parent_email=? WHERE student_id=?");

            // Convert numeric fields to integer type
            $progIdMapped = (int)$program_id;
            $currentSemMapped = (int)$current_semester;
            $currentYearMapped = (int)$current_academic_year;
            $stmt->bind_param("issssssssiisssi", $progIdMapped, $full_name, $dob, $gender, $address, $contact_no, $email, $admission_date, $status, $currentSemMapped, $currentYearMapped, $parent_name, $parent_contact, $parent_email, $student_id);

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = 'Student profile updated successfully.';
                header('Location: index.php');
                exit();
            } else {
                $error = 'Failed to save changes to database.';
                error_log('Student update failed: ' . $stmt->error);
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
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=3">
</head>
<body class="form-page">
    <div class="form-panel">
        <h2>📝 Edit Student Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit.php" method="POST">
            <?php csrf_field(); ?>
            <input type="hidden" name="student_id" value="<?= (int)$student['student_id'] ?>">

            <fieldset>
                <legend>Personal Profile</legend>
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name']) ?>" required>

                <div class="grid-2">
                    <div>
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" value="<?= htmlspecialchars($student['dob']) ?>" required>
                    </div>
                    <div>
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div>
                        <label>Email *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required>
                    </div>
                    <div>
                        <label>Contact</label>
                        <input type="text" name="contact_no" value="<?= htmlspecialchars($student['contact_no'] ?? '') ?>">
                    </div>
                </div>

                <label>Address</label>
                <textarea name="address"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Academic Parameters</legend>

                <label>Assigned Program *</label>
                <select name="program_id" required>
                    <option value="">-- Choose Program --</option>
                    <?php if ($programs_res): ?>
                        <?php while ($prog = $programs_res->fetch_assoc()): ?>
                            <option value="<?= (int)$prog['program_id'] ?>" <?= ($student['program_id'] == $prog['program_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prog['program_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>

                <div class="grid-2">
                    <div>
                        <label>Admission Date *</label>
                        <input type="date" name="admission_date" value="<?= htmlspecialchars($student['admission_date']) ?>" required>
                    </div>
                    <div>
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="dropout" <?= $student['status'] === 'dropout' ? 'selected' : '' ?>>Dropout</option>
                            <option value="graduated" <?= $student['status'] === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                        </select>
                    </div>
                </div>

                <label>Current Semester (Progress) *</label>
                <input type="number" name="current_semester" min="1" max="5"
                       value="<?= htmlspecialchars($student['current_semester'] ?? '1') ?>" required>
                <label>Current Academic Year (Progress) *</label>
                <input type="number" name="current_academic_year" min="1" max="2099"
                       value="<?= htmlspecialchars($student['current_academic_year'] ?? '1') ?>" required>
            </fieldset>

            <fieldset>
                <legend>Guardian Emergency Contact Details</legend>
                <label>Guardian Full Name</label>
                <input type="text" name="parent_name" value="<?= htmlspecialchars($student['parent_name'] ?? '') ?>">

                <div class="grid-2">
                    <div>
                        <label>Guardian Contact</label>
                        <input type="text" name="parent_contact" value="<?= htmlspecialchars($student['parent_contact'] ?? '') ?>">
                    </div>
                    <div>
                        <label>Guardian Email</label>
                        <input type="email" name="parent_email" value="<?= htmlspecialchars($student['parent_email'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <div class="btn-box">
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>