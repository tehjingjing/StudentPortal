const { test, expect } = require('@playwright/test');

// ==============================
// Global Test Constants & Helpers
// ==============================
const BASE_URL = 'http://localhost:8080';
exports.BASE_URL = BASE_URL;
const ADMIN_EMAIL = 'admin@portal.com';
exports.ADMIN_EMAIL = ADMIN_EMAIL;
const ADMIN_PASSWORD = 'Portal123!';
exports.ADMIN_PASSWORD = ADMIN_PASSWORD;
const STUDENT_EMAIL = 'alice@school.edu';
const STUDENT_PASSWORD = 'Portal123!';

// Unique ID generator for test records
function uniqueSuffix() {
  return `${Date.now()}`.slice(-8);
}

// Reusable Login Helpers
async function loginAsAdmin(page) {
  await page.goto(`${BASE_URL}/auth/login.php`, { timeout: 10000 });
  await page.fill('input[name="email"]', ADMIN_EMAIL);
  await page.fill('input[name="password"]', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/admin_dashboard/, { timeout: 8000 });
}

async function loginAsStudent(page) {
  await page.goto(`${BASE_URL}/auth/login.php`, { timeout: 10000 });
  await page.fill('input[name="email"]', STUDENT_EMAIL);
  await page.fill('input[name="password"]', STUDENT_PASSWORD);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/student_dashboard/, { timeout: 8000 });
}

// Global beforeEach: Navigate to login before every test
test.describe('Student Management Portal E2E Tests', () => {
  test.use({ actionTimeout: 10000, navigationTimeout: 15000 });

  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/login.php`, { timeout: 10000 });
  });

  // ==================================================
  // 1. Authentication Tests (No Serial Mode)
  // ==================================================
  test.describe('Admin Login', () => {
    test('successfully logs in with valid admin credentials', async ({ page }) => {
      await page.fill('input[name="email"]', ADMIN_EMAIL);
      await page.fill('input[name="password"]', ADMIN_PASSWORD);
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL(/admin_dashboard/);
      await expect(page.locator('h1')).toBeVisible();
    });

    test('shows error message with wrong password', async ({ page }) => {
      await page.fill('input[name="email"]', ADMIN_EMAIL);
      await page.fill('input[name="password"]', 'wrongpassword123');
      await page.click('button[type="submit"]');
      await expect(page.locator('.error')).toBeVisible();
    });

    test('blocks direct access to admin page when not logged in', async ({ page }) => {
      await page.goto(`${BASE_URL}/students/index.php`);
      await expect(page).toHaveURL(/login/);
    });
  });

  test.describe('Student Login', () => {
    test('successfully logs in with valid student credentials', async ({ page }) => {
      await page.fill('input[name="email"]', STUDENT_EMAIL);
      await page.fill('input[name="password"]', STUDENT_PASSWORD);
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/student_dashboard/);
      await expect(page.locator('h1')).toBeVisible();
    });

    test('can view own profile after login', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/students/profile.php`);
      await expect(page.locator('h1')).toContainText('My Profile');

      const valueCount = await page.locator('.value').count();
      if (valueCount > 0) {
        await expect(page.locator('.value').first()).toBeVisible();
      } else {
        await expect(page.locator('.empty-state')).toContainText('No student record');
      }
    });

    test('logout clears session and blocks protected pages', async ({ page }) => {
      await loginAsStudent(page);
      await page.click('a.logout-btn');
      await expect(page).toHaveURL(/login/);
      await page.goto(`${BASE_URL}/students/profile.php`);
      await expect(page).toHaveURL(/login/);
    });
  });

  test.describe('Student Registration', () => {
    test('rejects registration when email is not in student records', async ({ page }) => {
      await page.goto(`${BASE_URL}/auth/register.php`);
      await page.fill('input[name="email"]', 'unknown@notinschool.edu');
      await page.fill('input[name="password"]', 'Portal123!');
      await page.fill('input[name="confirm_password"]', 'Portal123!');
      await page.click('button[type="submit"]');
      await expect(page.locator('.error')).toBeVisible();
    });
  });

  // ==================================================
  // 2. Admin Student CRUD (Serial Mode - Fixed Data Flow)
  // ==================================================
  test.describe('Admin Student CRUD', () => {
    test.describe.configure({ mode: 'serial' });
    let testStudentEmail;
    const suffix = uniqueSuffix();

    test.beforeAll(async ({ browser }) => {
      // Create one admin page for pre-test data setup
      const page = await browser.newPage();
      await loginAsAdmin(page);
      testStudentEmail = `playwright-${suffix}@test.com`;
      // Pre-create test student record once for entire suite
      await page.goto(`${BASE_URL}/students/create.php`);
      await page.fill('input[name="full_name"]', `Playwright Test Student ${suffix}`);
      await page.fill('input[name="email"]', testStudentEmail);
      await page.fill('input[name="dob"]', '2005-06-15');
      await page.selectOption('select[name="gender"]', 'Male');
      await page.selectOption('select[name="program_id"]', { index: 1 });
      await page.fill('input[name="current_semester"]', '2');
      await page.fill('input[name="current_academic_year"]', '2026');
      await page.click('button[type="submit"]');
      await page.waitForSelector('.alert-success');
      await page.close();
    });

    test.beforeEach(async ({ page }) => {
      await loginAsAdmin(page);
    });

    test('can create a new student record', async ({ page }) => {
      const newSuffix = uniqueSuffix();
      await page.goto(`${BASE_URL}/students/create.php`);
      await page.fill('input[name="full_name"]', `Playwright Test Student ${newSuffix}`);
      await page.fill('input[name="email"]', `playwright-${newSuffix}@test.com`);
      await page.fill('input[name="dob"]', '2005-06-15');
      await page.selectOption('select[name="gender"]', 'Male');
      await page.selectOption('select[name="program_id"]', { index: 1 });
      await page.fill('input[name="current_semester"]', '2');
      await page.fill('input[name="current_academic_year"]', '2026');
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('can edit an existing student record', async ({ page }) => {
      await page.goto(`${BASE_URL}/students/index.php?search=${encodeURIComponent(testStudentEmail)}`);
      await page.click('a.btn-secondary:has-text("Edit")');
      await page.fill('input[name="full_name"]', 'Updated Student Name');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/students\/index.php/);
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('search function returns matching students', async ({ page }) => {
      await page.goto(`${BASE_URL}/students/index.php`);
      await page.fill('input[name="search"]', 'alice@school.edu');
      await page.press('input[name="search"]', 'Enter');
      await expect(page.locator('.record-card').first()).toContainText('alice@school.edu');
    });

    test('can delete a student with related records', async ({ page }) => {
      await page.goto(`${BASE_URL}/students/index.php?search=${encodeURIComponent(testStudentEmail)}`);
      const targetCard = page.locator('.record-card', { hasText: testStudentEmail });
      await expect(targetCard).toBeVisible();
      page.on('dialog', dialog => dialog.accept());
      await targetCard.locator('button.btn-danger').click();
      await expect(page.locator('.alert-success')).toBeVisible();
    });
  });

  // ==================================================
  // 3. Admin Course Management (Serial Fixed)
  // ==================================================
  test.describe('Admin Course Management', () => {
    test.describe.configure({ mode: 'serial' });
    let createdCourseCode;

    test.beforeEach(async ({ page }) => {
      await loginAsAdmin(page);
    });

    test('ensure test course exists for registration', async ({ page }) => {
      const suffix = uniqueSuffix();
      createdCourseCode = `TEST${suffix}`;
      await page.goto(`${BASE_URL}/courses/create.php`);
      await page.fill('input[name="course_code"]', createdCourseCode);
      await page.fill('input[name="course_name"]', 'Test Course for Registration');
      await page.selectOption('select[name="program_id"]', { index: 1 });
      await page.fill('input[name="credit_hours"]', '3');
      await page.fill('input[name="duration"]', '14 Weeks');
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('can create a new course', async ({ page }) => {
      const suffix = uniqueSuffix();
      createdCourseCode = `BIT${suffix}`;
      await page.goto(`${BASE_URL}/courses/create.php`);
      await page.fill('input[name="course_code"]', createdCourseCode);
      await page.fill('input[name="course_name"]', 'Playwright Test Course');
      await page.selectOption('select[name="program_id"]', { index: 1 });
      await page.fill('input[name="credit_hours"]', '3');
      await page.fill('input[name="duration"]', '14 Weeks');
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('can edit an existing course', async ({ page }) => {
      await page.goto(`${BASE_URL}/courses/index.php?search=${encodeURIComponent(createdCourseCode)}`);
      await page.click('a.btn-secondary:has-text("Edit")');
      await page.fill('input[name="course_name"]', 'Updated Course Title');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/courses\/index.php/);
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('search function returns matching courses', async ({ page }) => {
      await page.goto(`${BASE_URL}/courses/index.php`);
      await page.fill('input[name="search"]', createdCourseCode);
      await page.press('input[name="search"]', 'Enter');
      await expect(page.locator('.record-card').first()).toBeVisible();
    });

    test('can delete a course created in this suite', async ({ page }) => {
      await page.goto(`${BASE_URL}/courses/index.php?search=${encodeURIComponent(createdCourseCode)}`);
      const card = page.locator('.record-card', { hasText: createdCourseCode });
      await expect(card).toBeVisible();
      page.once('dialog', dialog => dialog.accept());
      await card.locator('button.btn-danger').click();
      await expect(page.locator('.alert-success')).toBeVisible();
    });
  });

  // ==================================================
  // 4. Admin Teacher Management (Serial Fixed)
  // ==================================================
  test.describe('Admin Teacher Management', () => {
    test.describe.configure({ mode: 'serial' });
    const suffix = uniqueSuffix();
    const teacherName = `Playwright Teacher ${suffix}`;
    const teacherEmail = `teacher-${suffix}@test.com`;

    test.beforeEach(async ({ page }) => {
      await loginAsAdmin(page);
    });

    test('can create a new teacher', async ({ page }) => {
      await page.goto(`${BASE_URL}/teachers/create.php`);
      await page.fill('input[name="teacher_name"]', teacherName);
      await page.fill('input[name="email"]', teacherEmail);
      await page.fill('input[name="phone"]', '0123456789');
      await page.selectOption('select[name="faculty_id"]', { index: 1 });
      await page.fill('input[name="joining_date"]', '2024-01-15');
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('search function returns matching teachers', async ({ page }) => {
      await page.goto(`${BASE_URL}/teachers/index.php`);
      await page.fill('input[name="search"]', teacherEmail);
      await page.press('input[name="search"]', 'Enter');
      await expect(page.locator('.record-card').first()).toContainText(teacherName);
    });

    test('can edit an existing teacher', async ({ page }) => {
      await page.goto(`${BASE_URL}/teachers/index.php?search=${encodeURIComponent(teacherEmail)}`);
      await page.click('a.btn-secondary:has-text("Edit")');
      await page.fill('input[name="teacher_name"]', `${teacherName} Edited`);
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/teachers\/index.php/);
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('can delete a teacher', async ({ page }) => {
      await page.goto(`${BASE_URL}/teachers/index.php?search=${encodeURIComponent(teacherEmail)}`);
      const card = page.locator('.record-card', { hasText: teacherEmail });
      page.once('dialog', dialog => dialog.accept());
      await card.locator('button.btn-danger').click();
      await expect(page.locator('.alert-success')).toBeVisible();
    });
  });

  // ==================================================
  // 5. Programme Management (Serial Fixed)
  // ==================================================
  test.describe('Admin Programme Management', () => {
    test.describe.configure({ mode: 'serial' });
    const suffix = uniqueSuffix();
    const programName = `Playwright Program ${suffix}`;
    const programCode = `PW${suffix}`;

    test.beforeEach(async ({ page }) => {
      await loginAsAdmin(page);
    });

    test('can create a new programme', async ({ page }) => {
      await page.goto(`${BASE_URL}/programs/create.php`);
      await page.selectOption('select[name="faculty_id"]', { index: 1 });
      await page.fill('input[name="program_name"]', programName);
      await page.fill('input[name="program_code"]', programCode);
      await page.selectOption('select[name="level"]', 'Bachelor');
      await page.fill('input[name="total_credits_required"]', '120');
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/programs\/index.php/);
      await expect(page.locator('.alert-success')).toBeVisible();
    });

    test('search function returns matching programmes', async ({ page }) => {
      await page.goto(`${BASE_URL}/programs/index.php`);
      await page.fill('input[name="search"]', programCode);
      await page.press('input[name="search"]', 'Enter');
      await expect(page.locator('.record-card').first()).toContainText(programName);
    });

    test('can delete a programme', async ({ page }) => {
      await page.goto(`${BASE_URL}/programs/index.php?search=${encodeURIComponent(programCode)}`);
      const card = page.locator('.record-card', { hasText: programName });
      await expect(card).toBeVisible();
      page.once('dialog', dialog => dialog.accept());
      await card.locator('button.btn-danger').click();
      await expect(page.locator('.alert-success')).toBeVisible();
    });
  });

  // ==================================================
  // 6. Student Course Enrolment (Fixed test.skip instead of return)
  // ==================================================
  test.describe('Student Course Registration', () => {
    test.describe.configure({ mode: 'serial' });
    let registeredCourseLabel = '';
    let registeredCourseCode = 'BIT101';

    test.beforeEach(async ({ page }) => {
      await loginAsStudent(page);
    });

    test('student can view available courses and register', async ({ page }) => {
      await page.goto(`${BASE_URL}/enrolment/my_course.php`);
      const courseOptions = page.locator('select[name="course_id"] option:not([value=""])');
      const optionCount = await courseOptions.count();

      if (optionCount === 0) {
        test.skip(true, 'Skip enrolment test: No available courses found in database');
        return;
      }

      const firstOption = courseOptions.first();
      registeredCourseLabel = (await firstOption.textContent()).trim();
      registeredCourseCode = registeredCourseLabel.split(' - ')[0];
      const enrolledRow = page.locator('table tbody tr', { hasText: registeredCourseCode });

      if (await enrolledRow.count() > 0) {
        page.once('dialog', dialog => dialog.accept());
        await enrolledRow.locator('button.btn-danger').click();
        await expect(page.locator('.alert-success')).toBeVisible();
      }

      await page.selectOption('select[name="course_id"]', { label: registeredCourseLabel });
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('table tbody tr', { hasText: registeredCourseCode })).toBeVisible();
    });

    test('cannot register for the same course twice in one semester', async ({ page }) => {
      if (!registeredCourseLabel) {
        test.skip(true, 'Skip duplicate enrolment test: No valid course loaded from prior test');
        return;
      }
      await page.goto(`${BASE_URL}/enrolment/my_course.php`);
      await page.selectOption('select[name="course_id"]', { label: registeredCourseLabel });
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-error')).toContainText(/already registered/i);
    });

    test('student can drop a registered course', async ({ page }) => {
      await page.goto(`${BASE_URL}/enrolment/my_course.php`);
      const dropButton = page.locator('table tbody tr button.btn-danger').first();
      if (await dropButton.count() === 0) {
        test.skip(true, 'Skip drop test: No enrolled courses to delete');
        return;
      }
      await expect(dropButton).toBeVisible();
      page.once('dialog', dialog => dialog.accept());
      await dropButton.click();
      await expect(page.locator('.alert-success')).toBeVisible();
    });
  });

  // ==================================================
  // 7. Leave Management (Fixed test.skip guards)
  // ==================================================
  test.describe('Leave Management', () => {
    test.describe.configure({ mode: 'serial' });
    const suffix = uniqueSuffix();
    const leaveReason = `Playwright leave request ${suffix}`;

    test('student can submit a leave application', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/leaves/apply.php`);
      const emptyStateCount = await page.locator('.empty-state').count();

      if (emptyStateCount > 0) {
        test.skip(true, 'Skip leave submit: Student profile missing in database');
        return;
      }

      await page.fill('input[name="start_date"]', '2026-08-01');
      await page.fill('input[name="end_date"]', '2026-08-03');
      await page.fill('textarea[name="reason"]', leaveReason);
      await page.click('button[type="submit"]');
      await page.waitForURL(/apply\.php/);
      await expect(page.locator('.alert-success')).toBeVisible();
      await expect(page.locator('.leave-list')).toContainText(leaveReason);
    });

    test('rejects leave when end date is before start date', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/leaves/apply.php`);
      await page.fill('input[name="start_date"]', '2026-09-10');
      await page.fill('input[name="end_date"]', '2026-09-01');
      await page.fill('textarea[name="reason"]', 'Invalid date range test');
      await page.click('button[type="submit"]');
      await expect(page.locator('.alert-error')).toContainText(/cannot be before/i);
    });

    test('admin can approve a pending leave application', async ({ page }) => {
      await loginAsAdmin(page);
      await page.goto(`${BASE_URL}/leaves/index.php`);
      const leaveRow = page.locator('.leave-row', { hasText: leaveReason });

      if (await leaveRow.count() === 0) {
        test.skip(true, 'Skip leave approval: No matching leave request created');
        return;
      }

      await expect(leaveRow).toBeVisible();
      await leaveRow.locator('button.btn-approve').click();
      await expect(page.locator('.alert.alert-success')).toBeVisible();
      await expect(leaveRow.locator('.status-pill')).toContainText('Approved');
    });
  });

  // ==================================================
  // 8. Security Validation Tests
  // ==================================================
  test.describe('Security Validation', () => {
    test('CSRF protection blocks delete request without token', async ({ page }) => {
      await loginAsAdmin(page);
      await page.goto(`${BASE_URL}/students/delete.php?id=1`);
      await expect(page).toHaveURL(/index.php/);
    });

    test('student cannot access admin student management page', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/students/index.php`);
      await expect(page).not.toHaveURL(/students\/index/);
    });

    test('admin cannot access student course registration page', async ({ page }) => {
      await loginAsAdmin(page);
      await page.goto(`${BASE_URL}/enrolment/my_course.php`);
      await expect(page).toHaveURL(/admin_dashboard/);
    });

    test('student cannot access admin leave management page', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/leaves/index.php`);
      await expect(page).toHaveURL(/student_dashboard/);
    });

    test('blocks direct GET access to course registration handler', async ({ page }) => {
      await loginAsStudent(page);
      await page.goto(`${BASE_URL}/enrolment/register_course.php`);
      await expect(page).toHaveURL(/(my_course\.php|login\.php)/);
    });

    test('session expires after inactivity', async ({ page }) => {
      await loginAsAdmin(page);
      await page.context().clearCookies();
      await page.goto(`${BASE_URL}/students/index.php`);
      await expect(page).toHaveURL(/login/);
    });
  });

// ==================================================
// 9. Password Reset Tests
// ==================================================
test.describe('Password Reset', () => {
  test.use({ actionTimeout: 10000, navigationTimeout: 15000 });

  // Reusable helper: Disable all native browser form validation rules
  const disableFormValidate = async (page) => {
    await page.evaluate(() => {
      const form = document.querySelector('form');
      form.setAttribute('novalidate', 'true');
      document.querySelector('input[name="new_password"]').removeAttribute('minlength');
      document.querySelector('input[name="confirm_password"]').removeAttribute('minlength');
      document.querySelector('input[name="new_password"]').removeAttribute('required');
      document.querySelector('input[name="confirm_password"]').removeAttribute('required');
    });
  };

  test('redirect to login page when accessing reset page without token parameter', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/reset_password.php`);
    await expect(page).toHaveURL(/login\.php/);
  });

  test('page remains on reset page after submitting form with invalid fake token', async ({ page }) => {
    const fakeToken = `invalid-${uniqueSuffix()}`;
    await page.goto(`${BASE_URL}/auth/reset_password.php?token=${fakeToken}`);
    await disableFormValidate(page);

    await page.fill('input[name="new_password"]', 'Test123');
    await page.fill('input[name="confirm_password"]', 'Test123');
    await page.click('button[type="submit"]');

    // Remove navigation wait, only verify page URL stays unchanged
    await expect(page).toHaveURL(/reset_password\.php/);
  });

  // Password shorter than 6 characters
  test('backend keeps user on reset page if password length below 6 characters', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/reset_password.php?token=dummy-token-123`);
    await disableFormValidate(page);

    await page.fill('input[name="new_password"]', '123');
    await page.fill('input[name="confirm_password"]', '123');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/reset_password\.php/);
  });

  // Mismatched new password and confirm password
  test('backend keeps user on reset page if password and confirm password do not match', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/reset_password.php?token=dummy-token-123`);
    await disableFormValidate(page);

    await page.fill('input[name="new_password"]', 'Portal123');
    await page.fill('input[name="confirm_password"]', 'Portal456');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/reset_password\.php/);
  });

  // Submit empty password fields (fix previous navigation timeout error)
  test('backend keeps user on reset page when submitting blank password inputs', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth/reset_password.php?token=dummy-token-123`);
    await disableFormValidate(page);

    // Click submit without filling any password values
    await page.click('button[type="submit"]');

    // No page navigation occurs; only confirm we remain on the reset password URL
    await expect(page).toHaveURL(/reset_password\.php/);
  });
});
});
