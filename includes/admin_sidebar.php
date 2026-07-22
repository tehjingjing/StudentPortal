<?php
if (!isset($activePage)) {
    $activePage = '';
}
?>
<!-- The drawer controls are only shown on small screens; sidebar.js keeps
     their expanded state accessible and closes the menu after navigation. -->
<button class="sidebar-toggle" type="button" aria-controls="portal-sidebar" aria-expanded="false">
    <span class="sidebar-toggle-icon" aria-hidden="true"></span>
    <span>Menu</span>
</button>
<button class="sidebar-backdrop" type="button" aria-label="Close navigation"></button>
<aside id="portal-sidebar" class="sidebar" aria-label="Administrator navigation">
    <div class="sidebar-brand">
        <img src="/public/logo/Spacecollege.png" alt="Logo" class="logo-mark">
        <div class="brand-name">Student<span>Portal</span></div>
    </div>

    <div class="nav-section-label">Main</div>
    <a class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="../dashboard/admin_dashboard.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span class="nav-label">Dashboard</span>
    </a>

    <div class="nav-section-label">Peoples</div>
    <a class="nav-item <?= $activePage === 'students' ? 'active' : '' ?>" href="../students/index.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>
        <span class="nav-label">Student</span>
    </a>
    <a class="nav-item <?= $activePage === 'teachers' ? 'active' : '' ?>" href="../teachers/index.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="nav-label">Teacher</span>
    </a>

    <div class="nav-section-label">Academic</div>
    <a class="nav-item <?= $activePage === 'programs' ? 'active' : '' ?>" href="../programs/index.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2Z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15h6"/></svg>
        <span class="nav-label">Program</span>
    </a>
    <a class="nav-item <?= $activePage === 'courses' ? 'active' : '' ?>" href="../courses/index.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
        <span class="nav-label">Course</span>
    </a>

    <div class="nav-section-label">Management</div>
    <a class="nav-item <?= $activePage === 'leaves' ? 'active' : '' ?>" href="../leaves/index.php">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m14 14 3 3m0-3-3 3"/></svg>
        <span class="nav-label">Leaves</span>
    </a>
</aside>
<script src="../public/js/sidebar.js" defer></script>
