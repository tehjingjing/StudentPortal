/**
 * Controls the shared mobile navigation drawer. The script deliberately uses
 * classes rather than page-specific IDs so both admin and student sidebars
 * share one predictable interaction.
 */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    const backdrop = document.querySelector('.sidebar-backdrop');

    if (!sidebar || !toggle || !backdrop) {
        return;
    }

    const setDrawerOpen = (isOpen) => {
        document.body.classList.toggle('sidebar-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
    };

    toggle.addEventListener('click', () => {
        setDrawerOpen(!document.body.classList.contains('sidebar-open'));
    });

    backdrop.addEventListener('click', () => setDrawerOpen(false));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setDrawerOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setDrawerOpen(false);
        }
    });
});
