<?php
if (!isset($activePage)) {
    $activePage = '';
}
?>
<button class="sidebar-toggle" type="button" aria-controls="portal-sidebar" aria-expanded="false">
    <span class="sidebar-toggle-icon" aria-hidden="true"></span>
    <span>Menu</span>
</button>
<button class="sidebar-backdrop" type="button" aria-label="Close navigation"></button>
<aside id="portal-sidebar" class="sidebar" aria-label="Student navigation">
    <div class="sidebar-brand">
        <img src="/public/logo/Spacecollege.png" alt="Logo" class="logo-mark">
        <div class="brand-name">Student<span>Portal</span></div>
    </div>

    <div class="nav-section-label">Main</div>
    <a class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="../dashboard/student_dashboard.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Personal</div>
    <a class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>" href="../students/profile.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span class="nav-label">My Profile</span>
    </a>

    <div class="nav-section-label">Academic</div>
    <a class="nav-item <?= $activePage === 'my_course' ? 'active' : '' ?>" href="../enrolment/my_course.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
        <span class="nav-label">My Courses</span>
    </a>

    <div class="nav-section-label">Management</div>
    <a class="nav-item <?= $activePage === 'leave' ? 'active' : '' ?>" href="../leaves/apply.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><path d="M12 12h6"/><path d="M12 16h6"/></svg>
        <span class="nav-label">My Leave</span>
    </a>
</aside>
<script src="../public/js/sidebar.js" defer></script>
