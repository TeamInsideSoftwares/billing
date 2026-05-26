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

    @if(request()->query('iframe') == 1 || request()->query('layout') === 'modal')
        <style>
            .app-shell {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                height: auto !important;
                min-height: unset !important;
            }
            .layout-grid {
                display: block !important;
                grid-template-columns: none !important;
            }
            .sidebar, .topbar, .sidebar-backdrop {
                display: none !important;
            }
            .main-panel {
                padding: 0 !important;
                margin: 0 !important;
            }
            .content-panel {
                padding: 1rem !important;
            }
            .panel-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        </style>
    @endif
</head>
<body class="app-shell">
    {{-- Toast Container (outside layout-grid to avoid clipping) --}}
    @if (session('success') || session('error'))
        <div id="app-toast-container" class="app-toast-container">
            @if (session('success'))
                <div class="app-toast app-toast-success" onclick="this.remove()">
                    <i class="fas fa-check-circle toast-icon"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="app-toast app-toast-error" onclick="this.remove()">
                    <i class="fas fa-times-circle toast-icon"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>
    @endif

    <!-- ['label' => 'Subscriptions', 'route' => 'subscriptions.index'], -->
    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Clients', 'route' => 'clients.index'],
            ['label' => 'Orders', 'route' => 'orders.index'],
            ['label' => 'Quotations', 'route' => 'quotations.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'GST Report', 'route' => 'gst-report.index'],
            ['label' => 'Items', 'route' => 'services.index'],
            // ['label' => 'Users', 'route' => 'users.index'],
            ['label' => 'Settings', 'route' => 'settings.index'],
        ];
    @endphp

    <div class="layout-grid">
        <aside class="sidebar" id="app-sidebar" data-sidebar>
        <div class="brand-block">
            <div class="brand-mark-wrap">
                <div class="brand-mark">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
            <div class="brand-text">
                <h5>Skoolready</h5>
                {{-- <h5>BILLING APP</h5> --}}
            </div>
        </div>

            <nav class="nav-list">
                @php
                    $navIcons = [
                        'dashboard' => 'fa-tachometer-alt',
                        'clients' => 'fa-users',
                        'services' => 'fa-box',
                        'orders' => 'fa-shopping-cart',
                        'quotations' => 'fa-file-alt',
                        'invoices' => 'fa-file-invoice-dollar',
                        'payments' => 'fa-money-bill-wave',
                        'gst-report' => 'fa-receipt',
                        // 'users' => 'fa-user-tie',
                        'settings' => 'fa-cog',
                    ];
                @endphp
                @foreach ($navItems as $item)
                    @php
                        // Extract the base route name (e.g., 'services' from 'services.index')
                        $baseRoute = explode('.', $item['route'])[0];
                        // Check if current route starts with the base route name
                        $isActive = request()->routeIs($baseRoute . '.*') || request()->routeIs($item['route']);
                        $icon = $navIcons[$baseRoute] ?? 'fa-circle';
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="nav-link {{ $isActive ? 'is-active' : '' }}"
                        data-tooltip="{{ $item['label'] }}"
                    >
                        <i class="fas {{ $icon }} nav-icon"></i>
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
                <!-- Notifications -->
                <div class="sidebar-user-item">
                    <div class="sidebar-icon-area">
                        <div class="dropdown">
                            <button type="button" class="icon-btn notification-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                                <i class="fas fa-bell"></i>
                                <span class="notification-badge"></span>
                            </button>
                            <ul class="dropdown-menu notification-dropdown">
                                <li class="dropdown-header">
                                    <h6>Notifications</h6>
                                </li>
                                <li>
                                    <a class="dropdown-item notification-item" href="#">
                                        <div class="notification-content">
                                            <div class="notification-indicator"></div>
                                            <div class="notification-text">
                                                <p class="notification-title">New invoice payment received</p>
                                                <p class="notification-desc">Payment of Rs 15,000 received for Invoice #INV-001</p>
                                                <p class="notification-time">2 hours ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item notification-item" href="#">
                                        <div class="notification-content">
                                            <div class="notification-indicator"></div>
                                            <div class="notification-text">
                                                <p class="notification-title">Invoice overdue reminder</p>
                                                <p class="notification-desc">Invoice #INV-045 is 5 days overdue</p>
                                                <p class="notification-time">1 day ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="dropdown-footer">
                                    <a href="#">View all notifications</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <span class="nav-text">Notifications</span>
                </div>

                <!-- User Profile -->
                <div class="sidebar-user-item">
                    <div class="sidebar-icon-area">
                        <div class="dropdown">
                            <button type="button" class="icon-btn profile-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Account">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </button>
                            <ul class="dropdown-menu profile-dropdown">
                                <li class="profile-header">
                                    <h6 class="profile-name">{{ auth()->user()->name }}</h6>
                                    <p class="profile-email">{{ auth()->user()->email }}</p>
                                </li>
                                <li><a class="dropdown-item profile-settings-link" href="{{ route('settings.index') }}#personal">Profile Settings</a></li>
                                <li><hr class="dropdown-divider profile-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger logout-btn">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="user-info">
                        <strong class="user-name">{{ auth()->user()->slug }}</strong>
                        <span class="user-email">{{ auth()->user()->email }}</span>
                    </div>
                </div>

                <a href="{{ route('password.change') }}" class="sidebar-user-item" style="text-decoration: none; color: inherit;">
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
            <header class="topbar">
                <div class="topbar-title-wrap">
                    <button
                        type="button"
                        class="sidebar-toggle-btn"
                        data-sidebar-toggle
                        aria-label="Open navigation"
                        aria-controls="app-sidebar"
                        aria-expanded="false"
                    >
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-block">
                        <h2 class="page-title">{{ $title ?? 'Dashboard' }}</h2>
                        @if(!empty($subtitle))
                            <p class="page-subtitle">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <div class="topbar-actions">
                    @yield('header_actions')
                </div>
            </header>

            <main class="content-panel">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        window.appAlert = function(message, options = {}) {
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

        window.appConfirm = function(message, options = {}) {
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
        window.alert = function(message) {
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
            form.dataset.swalConfirmMessage = match[2];
            form.removeAttribute('onsubmit');
        });

        document.addEventListener('submit', async function(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (!form.dataset.swalConfirmMessage) return;
            if (form.dataset.swalConfirmBypass === '1') {
                form.dataset.swalConfirmBypass = '0';
                return;
            }

            event.preventDefault();
            const isConfirmed = await window.appConfirm(form.dataset.swalConfirmMessage, {
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
    document.addEventListener('DOMContentLoaded', function() {
        const isManagedDateFilter = (element) => {
            return element.classList.contains('header-date-input') || element.classList.contains('module-date-input');
        };

        // Initialize Flatpickr on all date inputs
        flatpickr('input[type="date"]', {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
            onReady: function(selectedDates, dateStr, instance) {
                if (isManagedDateFilter(instance.element)) {
                    instance.set('maxDate', 'today');
                    if (instance.element.name === 'to') {
                        const fromInput = instance.element.closest('form').querySelector('input[name="from"]');
                        if (fromInput && fromInput.value) {
                            instance.set('minDate', fromInput.value);
                        }
                    }
                }
            },
            onChange: function(selectedDates, dateStr, instance) {
                if (isManagedDateFilter(instance.element) && instance.element.name === 'from') {
                    const toInput = instance.element.closest('form').querySelector('input[name="to"]');
                    if (toInput && toInput._flatpickr) {
                        toInput._flatpickr.set('minDate', dateStr);
                        if (toInput.value && toInput.value < dateStr) {
                            toInput._flatpickr.setDate(dateStr);
                        }
                    }
                }
            }
        });

        // Also handle dynamically added date inputs using MutationObserver
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.matches && node.matches('input[type="date"]') && !node._flatpickr) {
                            flatpickr(node, {
                                dateFormat: 'Y-m-d',
                                allowInput: true,
                                disableMobile: true,
                                onReady: function(selectedDates, dateStr, instance) {
                                    if (isManagedDateFilter(instance.element)) {
                                        instance.set('maxDate', 'today');
                                    }
                                }
                            });
                        }
                        // Check for date inputs inside added nodes
                        const dateInputs = node.querySelectorAll ? node.querySelectorAll('input[type="date"]') : [];
                        dateInputs.forEach(function(input) {
                            if (!input._flatpickr) {
                                flatpickr(input, {
                                    dateFormat: 'Y-m-d',
                                    allowInput: true,
                                    disableMobile: true,
                                    onReady: function(selectedDates, dateStr, instance) {
                                        if (isManagedDateFilter(instance.element)) {
                                            instance.set('maxDate', 'today');
                                        }
                                    }
                                });
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
    @if (session('success') || session('error'))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.app-toast').forEach(function (toast) {
            setTimeout(function () {
                if (toast.parentNode) {
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
    document.addEventListener('DOMContentLoaded', function() {
        const enforceHeaderDates = () => {
            const fromInputs = document.querySelectorAll('.header-date-input[name="from"], .module-date-input[name="from"]');
            const toInputs = document.querySelectorAll('.header-date-input[name="to"], .module-date-input[name="to"]');
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
            };

            // Delegate events for dynamically added content or just general robustness
            document.querySelectorAll('.header-date-input, .module-date-input').forEach(input => {
                if (!input.dataset.listenerAttached) {
                    input.addEventListener('change', enforceHeaderDates);
                    input.addEventListener('focus', enforceHeaderDates);
                    input.dataset.listenerAttached = 'true';
                }
            });

            enforceHeaderDates();
        };

        // Initial run
        enforceHeaderDates();
        // Secondary run to catch any late initializations (like flatpickr)
        setTimeout(enforceHeaderDates, 500);

        // Expose to global window if needed manually
        window.reapplyDateFilters = enforceHeaderDates;
    });
    </script>
</body>
</html>
