import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const tabletQuery = window.matchMedia('(max-width: 1024px)');

    if (!sidebar || !toggle) {
        return;
    }

    const closeSidebar = () => {
        sidebar.classList.remove('is-open');
        document.body.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const openSidebar = () => {
        sidebar.classList.add('is-open');
        document.body.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
    };

    const toggleSidebar = () => {
        if (!tabletQuery.matches) {
            return;
        }

        if (sidebar.classList.contains('is-open')) {
            closeSidebar();
            return;
        }

        openSidebar();
    };

    toggle.addEventListener('click', toggleSidebar);

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    const handleViewportChange = (event) => {
        if (!event.matches) {
            closeSidebar();
        }
    };

    if (typeof tabletQuery.addEventListener === 'function') {
        tabletQuery.addEventListener('change', handleViewportChange);
    } else {
        tabletQuery.addListener(handleViewportChange);
    }
});
