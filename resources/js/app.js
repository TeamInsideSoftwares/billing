import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (!sidebar || !toggle) {
        return;
    }

    // Mobile: Toggle sidebar visibility
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('is-open');
    });
});
