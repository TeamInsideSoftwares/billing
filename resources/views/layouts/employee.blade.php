<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Billing Software' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="//tiny.skoolready.com/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/location-picker.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body class="app-shell">
    {{-- Toast Container (outside layout-grid to avoid clipping) --}}
    @if (session('success') || session('error') || (isset($errors) && $errors->any()))
    <div id="app-toast-container" class="app-toast-container" style="pointer-events: auto;">
        @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show text-center rounded-0 border-0 fs-6 lh-sm" onclick="this.remove()" role="alert" style="cursor: pointer;"> 
            <strong>{{ session('success') }}</strong>
        </div>  
        @endif
        @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show text-center rounded-0 border-0 fs-6 lh-sm" onclick="this.remove()" role="alert" style="cursor: pointer;"> 
            <strong>{{ session('error') }}</strong>
        </div> 
        @endif
        @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-0 border-0 fs-6 lh-sm validation-errors" onclick="this.remove()" role="alert" style="cursor: pointer;">
           <strong>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                    <li class="small fw-bold">{{ $error }}</li>
                    @endforeach
                </ul>
            </strong>
        </div>
        @endif
    </div>
    @endif

    <!-- ['label' => 'Subscriptions', 'route' => 'subscriptions.index'], -->
    @php
    $navItems = [
        ['label' => 'Team Work', 'route' => 'team-work.dashboard', 'permission' => 'team_work.view'],
        ['label' => 'My Profile', 'route' => 'team-work.profile.edit', 'permission' => 'team_work.view']
    ];
    // ['label' => 'My Leaves', 'route' => 'team-work.leaves.index', 'permission' => 'team_work.view'],
    // ['label' => 'My Attendance', 'route' => 'team-work.attendance.index', 'permission' => 'team_work.view']
    @endphp
    <div class="layout-grid">
        <aside class="sidebar" id="app-sidebar" data-sidebar>
            <a href="{{ route('team-work.dashboard') }}" class="brand-block"
                style="text-decoration: none; color: inherit;">
                <div class="brand-mark-wrap">
                    <div class="brand-mark bg-primary rounded-circle">
                        SR
                    </div>
                </div>
                <div class="brand-text">
                    <h5>Skoolready</h5>
                    {{-- <h5>BILLING APP</h5> --}}
                </div>
            </a>

            <nav class="nav-list">
                @php
                $navIcons = [
                    'team-work.dashboard' => 'fa-chart-bar',
                    'team-work.profile.edit' => 'fa-user-circle',
                    // 'team-work.leaves.index' => 'fa-calendar-times',
                    // 'team-work.attendance.index' => 'fa-clock',
                ];
                @endphp
                @foreach ($navItems as $item)

                @php
                // Check if the current route is exactly this item's route, or is a child of it (but not matching siblings)
                // For team-work.dashboard, only active if exactly team-work.dashboard
                // For team-work.profile.edit, active if it's team-work.profile.*
                if ($item['route'] === 'team-work.dashboard') {
                    $isActive = request()->routeIs('team-work.dashboard');
                } else {
                    $routePrefix = explode('.', $item['route']);
                    array_pop($routePrefix); // remove 'index' or 'edit'
                    $routePrefix = implode('.', $routePrefix);
                    $isActive = request()->routeIs($item['route']) || request()->routeIs($routePrefix . '.*');
                }
                
                $icon = $navIcons[$item['route']] ?? 'fa-circle';
                @endphp
                <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'is-active' : '' }}"
                     data-tooltip="{{ $item['label'] }}">
                    <i class="far {{ $icon }} nav-icon opacity-50"></i>
                    <span class="nav-text">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </nav>

            <!-- <div class="sidebar-card">
                <p class="eyebrow">Collection Focus</p>
                <strong>Rs 1.18L still outstanding</strong>
                <span>Prioritize 6 accounts due this week.</span>
            </div> -->


            <div class="user-section">

                <!-- User Profile -->
                <div class="sidebar-user-item">
                    <div class="sidebar-icon-area">
                        <div class="dropdown">
                            <button type="button" class="icon-btn profile-btn bg-transparent" data-bs-toggle="dropdown"
                                aria-expanded="false" title="Account">
                                {{ strtoupper(substr(auth()->user()?->name ?? $employee?->first_name ?? 'U', 0, 1)) }}
                            </button>
                            <ul class="dropdown-menu profile-dropdown">
                                <li class="profile-header">
                                    <h6 class="profile-name">{{ auth()->user()?->name ?? ($employee?->name ?? 'User') }}</h6>
                                    <p class="profile-email">{{ auth()->user()?->email ?? $employee?->email }}</p>
                                </li>
                                <li><a class="dropdown-item profile-settings-link"
                                        href="{{ route('settings.index') }}#personal">Profile Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger logout-btn">Sign
                                            Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="user-info">
                        <strong class="user-name text-capitalize">{{ isset($employee) ? $employee->name : auth()->user()?->name }}</strong>
                        <span class="user-email">{{ isset($employee) ? $employee->email : auth()->user()?->email }}</span>
                    </div>
                </div>

                <a href="#" class="sidebar-user-item"
                    style="text-decoration: none; color: inherit;">
                    <div class="sidebar-icon-area">
                        <span class="icon-btn" title="Change Password">
                            <i class="fas fa-key"></i>
                        </span>
                    </div>
                    <span class="nav-text">Change Password</span>
                </a>

                <!-- Sign Out at bottom -->
                <form action="{{ route('logout') }}" method="POST" class="logout-form-inline">
                    @csrf
                    <button type="submit" class="sidebar-user-item signout-row-btn" title="Sign Out">
                        <div class="sidebar-icon-area">
                            <span class="icon-btn signout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                            </span>
                        </div>
                        <span class="nav-text">Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>
        <div class="sidebar-backdrop" data-sidebar-backdrop aria-hidden="true"></div>

        <div class="main-panel">
            @if(session()->has('impersonating_user') && isset($employee))
            <div class="bg-warning text-dark text-center py-2 fw-medium shadow-sm sticky-top" style="z-index: 1040;">
                <i class="fas fa-user-secret me-2"></i> You are currently viewing as <strong>{{ $employee->name ?? $employee->first_name }}</strong>.
                <form action="{{ route('leave-impersonation') }}" method="POST" class="d-inline ms-3">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark fw-bold">Leave Impersonation</button>
                </form>
            </div>
            @endif
            <header class="topbar">
                <div class="topbar-title-wrap">
                    <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle aria-label="Open navigation"
                        aria-controls="app-sidebar" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-block d-flex">
                        <h2 class="">{{ $title ?? 'Dashboard' }}</h2>
                        @if (!empty($subtitle))
                        <p class="fs-6 lh-sm text-dark mb-0">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <!-- <div class="topbar-actions">
                    @if (!empty($sharedFinancialYears) && $sharedFinancialYears->count() > 0)
                    <form method="POST" action="{{ route('financial-year.select') }}" class="m-0">
                        @csrf
                        <select id="topbarFinancialYear" name="fy_id" class="form-select w-auto">
                            @foreach ($sharedFinancialYears as $financialYear)
                            <option value="{{ $financialYear->fy_id }}" {{ (string) ($sharedSelectedFinancialYearId
                                ?? '' )===(string) $financialYear->fy_id ? 'selected' : '' }}>
                                {{ $financialYear->financial_year }}{{ $financialYear->default ? ' (Default)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                    @yield('header_actions')
                </div> -->
            </header>

            <main class="content-panel">
                @yield('content')
            </main>
        </div>
    </div>


    <script>
        window.showToast = function (type, message) {
            const text = String(message || '').trim();
            if (!text) return;
            let container = document.getElementById('app-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'app-toast-container';
                container.className = 'app-toast-container';
                container.style.pointerEvents = 'auto';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            const alertType = (type === 'error' || type === 'danger') ? 'danger' : 'success';
            toast.className = `alert alert-${alertType} alert-dismissible fade show text-center rounded-0 border-0 fs-6 lh-sm`;
            toast.style.cursor = 'pointer';
            toast.innerHTML = '<strong></strong>';
            const label = toast.querySelector('strong');
            if (label) label.textContent = text;
            toast.addEventListener('click', () => toast.remove());
            // Remove any previously shown JS toasts (non-session ones) to prevent stacking
            container.querySelectorAll('div:not([role="alert"])').forEach(function (el) {
                el.remove();
            });
            container.appendChild(toast);
            window.setTimeout(() => {
                toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                toast.classList.add('app-toast-leaving');
                window.setTimeout(() => toast.remove(), 300);
            }, 3500);
        };
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const topbarFySelect = document.getElementById('topbarFinancialYear');
            if (topbarFySelect) {
                topbarFySelect.addEventListener('change', function () {
                    const form = topbarFySelect.closest('form');
                    if (form) {
                        form.submit();
                    }
                });
            }

            const notificationsDataEl = document.getElementById('notifications-data');
            const notificationsList = document.getElementById('notificationsList');
            const notificationBadge = document.querySelector('.notification-badge');
            const notificationsButton = document.getElementById('openNotificationsModal');
            const notificationsRow = document.getElementById('openNotificationsModalRow');
            const notificationsModalEl = document.getElementById('notificationsModal');

            const notifications = notificationsDataEl ? JSON.parse(notificationsDataEl.textContent || '[]') : [];
            const unreadCount = notifications.filter((item) => !item.read).length;

            if (notificationBadge) {
                if (unreadCount > 0) {
                    notificationBadge.textContent = unreadCount;
                    notificationBadge.classList.add('has-count');
                    notificationBadge.style.display = 'inline-flex';
                } else {
                    notificationBadge.style.display = 'none';
                }
            }

            const renderNotificationItem = (item) => {
                const link = document.createElement('a');
                link.className = 'notification-item d-block mb-3';
                link.href = item.url || '#';
                link.innerHTML = `
                <div class="notification-content">
                    <div class="notification-indicator${item.read ? ' notification-read' : ''}"></div>
                    <div class="notification-text">
                        <p class="notification-title">${item.title || 'Notification'}</p>
                        ${item.description ? `<p class="notification-desc">${item.description}</p>` : ''}
                        ${item.time ? `<p class="notification-time">${item.time}</p>` : ''}
                    </div>
                </div>
            `;
                return link;
            };

            if (notificationsList) {
                notificationsList.innerHTML = '';
                if (notifications.length === 0) {
                    notificationsList.innerHTML = '<div class="text-muted w-100 d-flex align-items-center justify-content-center text-center rounded-3 bg-white" style="height: 200px; border: 2px dashed #ccc;">No notifications yet.</div>';
                } else {
                    notifications.forEach((item) => {
                        notificationsList.appendChild(renderNotificationItem(item));
                    });
                }
            }

            if (notificationsButton && notificationsModalEl && typeof bootstrap !== 'undefined') {
                const notificationModal = new bootstrap.Modal(notificationsModalEl);
                const showNotificationsModal = function () {
                    notificationModal.show();
                };
                notificationsButton.addEventListener('click', showNotificationsModal);
                if (notificationsRow) {
                    notificationsRow.addEventListener('click', showNotificationsModal);
                    notificationsRow.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            showNotificationsModal();
                        }
                    });
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            window.appAlert = function (message, options = {}) {
                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    window.alert(message);
                    return Promise.resolve();
                }

                return window.Swal.fire({
                    title: options.title || 'Notice',
                    text: String(message || ''),
                    icon: options.icon || 'info',
                    confirmButtonText: options.confirmButtonText || 'OK',
                    width: options.width || 340,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'app-swal-popup',
                        title: 'app-swal-title',
                        htmlContainer: 'app-swal-text',
                        confirmButton: 'app-swal-btn app-swal-btn-confirm',
                        cancelButton: 'app-swal-btn app-swal-btn-cancel',
                        icon: 'app-swal-icon',
                    },
                    ...options,
                });
            };

            window.appConfirm = function (message, options = {}) {
                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    return Promise.resolve(window.confirm(message));
                }

                return window.Swal.fire({
                    title: options.title || 'Please Confirm',
                    text: String(message || ''),
                    icon: options.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: options.confirmButtonText || 'Yes',
                    cancelButtonText: options.cancelButtonText || 'Cancel',
                    width: options.width || 360,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'app-swal-popup',
                        title: 'app-swal-title',
                        htmlContainer: 'app-swal-text',
                        confirmButton: 'app-swal-btn app-swal-btn-confirm',
                        cancelButton: 'app-swal-btn app-swal-btn-cancel',
                        icon: 'app-swal-icon',
                    },
                    ...options,
                }).then((result) => !!result.isConfirmed);
            };

            const nativeAlert = window.alert.bind(window);
            window.alert = function (message) {
                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    nativeAlert(message);
                    return;
                }
                window.appAlert(message);
            };

            document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
                const inlineSubmit = form.getAttribute('onsubmit') || '';
                const match = inlineSubmit.match(/confirm\((['"`])([\s\S]*?)\1\)/);
                if (!match) return;
                if (match[2].includes('+') || match[2].includes('this.')) {
                    form.dataset.swalConfirmExpression = match[1] + match[2] + match[1];
                }
                form.dataset.swalConfirmMessage = match[2];
                form.removeAttribute('onsubmit');
            });

            document.addEventListener('submit', async function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (!form.dataset.swalConfirmMessage) return;
                if (form.dataset.swalConfirmBypass === '1') {
                    form.dataset.swalConfirmBypass = '0';
                    return;
                }

                event.preventDefault();
                let message = form.dataset.swalConfirmMessage;
                if (form.dataset.swalConfirmExpression) {
                    try {
                        const fn = new Function('return (' + form.dataset.swalConfirmExpression + ')');
                        message = fn.call(form);
                    } catch (e) {
                        console.error('Failed to evaluate confirm expression:', e);
                    }
                }
                const isConfirmed = await window.appConfirm(message, {
                    title: 'Please Confirm',
                    icon: 'question',
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                });

                if (isConfirmed) {
                    form.dataset.swalConfirmBypass = '1';
                    form.requestSubmit();
                }
            }, true);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isManagedDateFilter = (element) => {
                return element.classList.contains('header-date-input') || element.classList.contains(
                    'module-date-input');
            };

            const buildDatePickerConfig = (element) => {
                const config = {
                    dateFormat: 'Y-m-d',
                    allowInput: true,
                    disableMobile: true,
                };

                const minDate = element.getAttribute('min');
                const maxDate = element.getAttribute('max');

                if (minDate) {
                    config.minDate = minDate;
                }

                if (maxDate) {
                    config.maxDate = maxDate;
                }

                if (isManagedDateFilter(element)) {
                    config.maxDate = 'today';
                }

                return config;
            };

            // Initialize Flatpickr on all date inputs.
            document.querySelectorAll('input[type="date"]').forEach(function (input) {
                if (input._flatpickr) {
                    return;
                }

                flatpickr(input, Object.assign(buildDatePickerConfig(input), {
                    onReady: function (selectedDates, dateStr, instance) {
                        if (isManagedDateFilter(instance.element)) {
                            instance.set('maxDate', 'today');
                            if (instance.element.name === 'to') {
                                const fromInput = instance.element.closest('form')
                                    .querySelector('input[name="from"]');
                                if (fromInput && fromInput.value) {
                                    instance.set('minDate', fromInput.value);
                                }
                            }
                        }
                    },
                    onChange: function (selectedDates, dateStr, instance) {
                        if (isManagedDateFilter(instance.element) && instance.element
                            .name === 'from') {
                            const toInput = instance.element.closest('form').querySelector(
                                'input[name="to"]');
                            if (toInput && toInput._flatpickr) {
                                toInput._flatpickr.set('minDate', dateStr);
                                if (toInput.value && toInput.value < dateStr) {
                                    toInput._flatpickr.setDate(dateStr);
                                }
                            }
                        }
                    }
                }));
            });

            // Also handle dynamically added date inputs using MutationObserver
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches && node.matches('input[type="date"]') && !node
                                ._flatpickr) {
                                flatpickr(node, buildDatePickerConfig(node));
                            }
                            // Check for date inputs inside added nodes
                            const dateInputs = node.querySelectorAll ? node
                                .querySelectorAll('input[type="date"]') : [];
                            dateInputs.forEach(function (input) {
                                if (!input._flatpickr) {
                                    flatpickr(input, buildDatePickerConfig(input));
                                }
                            });
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    </script>

    {{-- Auto-dismiss toasts --}}
    @if (session('success') || session('error') || (isset($errors) && $errors->any()))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.app-toast, #app-toast-container .alert:not(.validation-errors)').forEach(function (toast) {
                setTimeout(function () {
                    if (toast.parentNode) {
                        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        toast.classList.add('app-toast-leaving');
                        setTimeout(function () {
                            if (toast.parentNode) toast.remove();
                        }, 300);
                    }
                }, 3500);
            });
        });
    </script>
    @endif
    {{-- Global Header Date Filter Logic --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const enforceHeaderDates = () => {
                const fromInputs = document.querySelectorAll(
                    '.header-date-input[name="from"], .module-date-input[name="from"]');
                const toInputs = document.querySelectorAll(
                    '.header-date-input[name="to"], .module-date-input[name="to"]');
                const today = new Date().toISOString().split('T')[0];

                fromInputs.forEach((fromInput, index) => {
                    const toInput = toInputs[index];
                    if (!fromInput || !toInput) return;

                    // Max date is today
                    fromInput.setAttribute('max', today);
                    toInput.setAttribute('max', today);

                    // Min date for "To" is "From" value
                    if (fromInput.value) {
                        toInput.setAttribute('min', fromInput.value);
                    } else {
                        toInput.removeAttribute('min');
                    }

                    // Support for Flatpickr if it exists
                    if (fromInput._flatpickr) {
                        fromInput._flatpickr.set('maxDate', today);
                    }
                    if (toInput._flatpickr) {
                        toInput._flatpickr.set('maxDate', today);
                        toInput._flatpickr.set('minDate', fromInput.value || null);
                    }

                    // If "To" is before "From", reset "To" to "From"
                    if (fromInput.value && toInput.value && toInput.value < fromInput.value) {
                        toInput.value = fromInput.value;
                        if (toInput._flatpickr) {
                            toInput._flatpickr.setDate(toInput.value, false);
                        }
                    }
                });

                // Delegate events for dynamically added content or just general robustness
                document.querySelectorAll('.header-date-input, .module-date-input').forEach(input => {
                    if (!input.dataset.listenerAttached) {
                        input.addEventListener('change', enforceHeaderDates);
                        input.addEventListener('focus', enforceHeaderDates);
                        input.dataset.listenerAttached = 'true';
                    }
                });

            };

            // Initial run
            enforceHeaderDates();
            // Secondary run to catch any late initializations (like flatpickr)
            setTimeout(enforceHeaderDates, 500);

            // Expose to global window if needed manually
            window.reapplyDateFilters = enforceHeaderDates;
        });
    </script>
    @stack('scripts')
</body>

</html>
