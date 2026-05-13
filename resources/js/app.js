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

document.addEventListener('DOMContentLoaded', () => {
    const normalizeIsoDate = (rawValue) => {
        const value = String(rawValue || '').trim();
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[1]}-${match[2]}-${match[3]}` : '';
    };

    const applyDateToInput = (input, isoDate) => {
        if (!input) return;
        const iso = normalizeIsoDate(isoDate);
        input.value = iso;
        input.setAttribute('value', iso);
        input.dataset.prefillDate = iso;

        if (input._flatpickr && iso) {
            input._flatpickr.setDate(iso, true, 'Y-m-d');
        }
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.js-renew-order-btn');
        if (!button) return;

        const endDateInput = document.getElementById('renew_order_end_date');
        if (!endDateInput) return;

        applyDateToInput(endDateInput, button.dataset.endDate || '');
    });

    document.addEventListener('shown.bs.modal', (event) => {
        const modal = event.target;
        if (!modal || modal.id !== 'renewOrderModal') return;

        const endDateInput = document.getElementById('renew_order_end_date');
        if (!endDateInput) return;

        const iso = normalizeIsoDate(endDateInput.dataset.prefillDate || endDateInput.value);
        if (!iso) return;

        requestAnimationFrame(() => {
            applyDateToInput(endDateInput, iso);
            setTimeout(() => applyDateToInput(endDateInput, iso), 0);
        });
    });

    const syncDateInputWithPicker = (input) => {
        if (!input || input.tagName !== 'INPUT' || input.type !== 'date') return;
        const iso = normalizeIsoDate(input.value || input.dataset.prefillDate || input.getAttribute('value'));
        if (!iso) return;

        input.dataset.prefillDate = iso;
        input.value = iso;
        input.setAttribute('value', iso);

        if (input._flatpickr) {
            input._flatpickr.setDate(iso, false, 'Y-m-d');
        }
    };

    document.addEventListener('focusin', (event) => {
        const input = event.target;
        if (!input || input.tagName !== 'INPUT' || input.type !== 'date') return;
        syncDateInputWithPicker(input);
    });

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!input || input.tagName !== 'INPUT' || input.type !== 'date') return;
        syncDateInputWithPicker(input);
    });

    document.addEventListener('shown.bs.modal', (event) => {
        const modal = event.target;
        if (!modal || !modal.querySelectorAll) return;

        modal.querySelectorAll('input[type="date"]').forEach((input) => {
            syncDateInputWithPicker(input);
            requestAnimationFrame(() => syncDateInputWithPicker(input));
        });
    });
});
