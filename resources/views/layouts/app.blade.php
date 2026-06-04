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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Synchronous Bootstrap compatibility shim for deferred modules / inline scripts
        (function() {
            window.openModal = function(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.dispatchEvent(new CustomEvent('show.bs.modal', { bubbles: true, cancelable: true }));
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                modal.dispatchEvent(new CustomEvent('shown.bs.modal', { bubbles: true, cancelable: true }));
            };
            window.closeModal = function(id) {
                const modal = document.getElementById(id);
                if (!modal) return;
                modal.dispatchEvent(new CustomEvent('hide.bs.modal', { bubbles: true, cancelable: true }));
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                const openModals = document.querySelectorAll('.flex[id$="Modal"], .flex[id*="modal"]');
                if (openModals.length <= 1) {
                    document.body.classList.remove('overflow-hidden');
                }
                modal.dispatchEvent(new CustomEvent('hidden.bs.modal', { bubbles: true, cancelable: true }));
            };
            class TailwindModal {
                constructor(element) {
                    this.element = typeof element === 'string' ? document.querySelector(element) : element;
                }
                show() {
                    if (this.element && this.element.id) {
                        window.openModal(this.element.id);
                    }
                }
                hide() {
                    if (this.element && this.element.id) {
                        window.closeModal(this.element.id);
                    }
                }
                static getInstance(element) {
                    return new TailwindModal(element);
                }
                static getOrCreateInstance(element) {
                    return new TailwindModal(element);
                }
            }
            class TailwindTab {
                constructor(element) {
                    this.element = typeof element === 'string' ? document.querySelector(element) : element;
                }
                show() {
                    const triggerEl = this.element;
                    if (!triggerEl) return;
                    const targetSelector = triggerEl.getAttribute('data-app-target') || triggerEl.getAttribute('data-bs-target') || triggerEl.getAttribute('href');
                    if (!targetSelector) return;
                    const targetEl = document.querySelector(targetSelector);
                    if (!targetEl) return;
                    const navContainer = triggerEl.closest('.nav, nav, .invoice-tabs, .tabs-container, #dashboardTabs');
                    if (navContainer) {
                        navContainer.querySelectorAll('[data-bs-toggle="tab"], [data-app-toggle="tab"], .nav-link').forEach(link => {
                            link.classList.remove('active', 'is-active', 'bg-blue-600', 'text-white');
                            link.classList.add('bg-slate-100', 'text-slate-600');
                            link.setAttribute('aria-selected', 'false');
                        });
                    }
                    triggerEl.classList.remove('bg-slate-100', 'text-slate-600');
                    triggerEl.classList.add('active', 'is-active', 'bg-blue-600', 'text-white');
                    triggerEl.setAttribute('aria-selected', 'true');
                    const tabContent = targetEl.closest('.tab-content');
                    if (tabContent) {
                        tabContent.querySelectorAll('.tab-pane').forEach(pane => {
                            pane.classList.add('hidden');
                            pane.classList.remove('active', 'show');
                        });
                    }
                    targetEl.classList.remove('hidden');
                    targetEl.classList.add('active', 'show');
                }
                static getInstance(element) {
                    return new TailwindTab(element);
                }
                static getOrCreateInstance(element) {
                    return new TailwindTab(element);
                }
            }
            class TailwindOffcanvas {
                constructor(element) {
                    this.element = typeof element === 'string' ? document.querySelector(element) : element;
                }
                show() {
                    if (!this.element) return;
                    this.element.dispatchEvent(new CustomEvent('show.bs.offcanvas', { bubbles: true, cancelable: true }));
                    this.element.classList.remove('translate-x-full');
                    this.element.classList.add('translate-x-0');
                    const backdrop = document.getElementById(this.element.id + '-backdrop');
                    if (backdrop) {
                        backdrop.classList.remove('hidden');
                    }
                    this.element.dispatchEvent(new CustomEvent('shown.bs.offcanvas', { bubbles: true, cancelable: true }));
                }
                hide() {
                    if (!this.element) return;
                    this.element.dispatchEvent(new CustomEvent('hide.bs.offcanvas', { bubbles: true, cancelable: true }));
                    this.element.classList.remove('translate-x-0');
                    this.element.classList.add('translate-x-full');
                    const backdrop = document.getElementById(this.element.id + '-backdrop');
                    if (backdrop) {
                        backdrop.classList.add('hidden');
                    }
                    this.element.dispatchEvent(new CustomEvent('hidden.bs.offcanvas', { bubbles: true, cancelable: true }));
                }
                static getInstance(element) {
                    return new TailwindOffcanvas(element);
                }
                static getOrCreateInstance(element) {
                    return new TailwindOffcanvas(element);
                }
            }
            window.bootstrap = {
                Modal: TailwindModal,
                Tab: TailwindTab,
                Offcanvas: TailwindOffcanvas
            };
        })();
    </script>
    <script src="//tiny.skoolready.com/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="{{ asset('js/location-picker.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    @if (request()->query('iframe') == 1 || request()->query('layout') === 'modal')
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

            .sidebar,
            .topbar,
            .sidebar-backdrop {
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
            ['label' => 'Client Dashboard', 'route' => 'clients.dashboard'],
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
            <a href="{{ route('clients.dashboard') }}" class="brand-block"
                style="text-decoration: none; color: inherit;">
                <div class="brand-mark-wrap">
                    <div class="brand-mark">
                        <i class="fas fa-file-invoice-dollar"></i>
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
                        'dashboard' => 'fa-tachometer-alt',
                        'clients.dashboard' => 'fa-address-card',
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
                        // Highlight the whole module for nested routes like create/show/edit/pdf.
                        if ($item['route'] === 'clients.dashboard') {
                            $isActive = request()->routeIs('clients.dashboard');
                        } elseif ($item['route'] === 'clients.index') {
                            $isActive =
                                (request()->routeIs('clients.*') || request()->routeIs($item['route'])) &&
                                !request()->routeIs('clients.dashboard');
                        } else {
                            $isActive = request()->routeIs($baseRoute . '.*') || request()->routeIs($item['route']);
                        }

                        $icon = $navIcons[$item['route']] ?? ($navIcons[$baseRoute] ?? 'fa-circle');
                    @endphp
                    <a href="{{ route($item['route']) }}" class="nav-link {{ $isActive ? 'is-active' : '' }}"
                        data-tooltip="{{ $item['label'] }}">
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

            @php
                $notificationData = collect();
                if (auth()->check()) {
                    try {
                        $notificationData = auth()
                            ->user()
                            ->unreadNotifications()
                            ->latest('created_at')
                            ->take(5)
                            ->get()
                            ->map(function ($notification) {
                                $data = is_array($notification->data) ? $notification->data : [];
                                return [
                                    'id' => $notification->id,
                                    'title' => $data['title'] ?? ($data['message'] ?? 'New notification'),
                                    'description' => $data['description'] ?? ($data['message'] ?? ''),
                                    'time' => optional($notification->created_at)->diffForHumans() ?? '',
                                    'url' => $data['url'] ?? '#',
                                    'read' => !empty($notification->read_at),
                                ];
                            });
                    } catch (\Throwable $e) {
                        $notificationData = collect();
                    }
                }
            @endphp
            <div class="user-section">
                <!-- Notifications -->
                <div class="sidebar-user-item notification-row" id="openNotificationsModalRow" role="button"
                    tabindex="0">
                    <div class="sidebar-icon-area">
                        <button type="button" class="icon-btn notification-btn" id="openNotificationsModal"
                            title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge"></span>
                        </button>
                    </div>
                    <span class="nav-text">Notifications</span>
                </div>

                <!-- User Profile -->
                <div class="sidebar-user-item">
                    <div class="sidebar-icon-area">
                        <div class="dropdown relative">
                            <button type="button" class="icon-btn profile-btn" data-bs-toggle="dropdown"
                                aria-expanded="false" title="Account">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </button>
                            <ul class="dropdown-menu profile-dropdown absolute right-0 bottom-full mb-2 bg-white border border-slate-200 rounded-lg shadow-lg z-50 p-2 hidden list-none text-left">
                                <li class="profile-header">
                                    <h6 class="profile-name">{{ auth()->user()->name }}</h6>
                                    <p class="profile-email">{{ auth()->user()->email }}</p>
                                </li>
                                <li><a class="dropdown-item profile-settings-link block w-full px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                                        href="{{ route('settings.index') }}#personal">Profile Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider profile-divider my-1 border-t border-slate-100">
                                </li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="logout-form m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger logout-btn block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition-colors">Sign
                                            Out</button>
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

                <a href="{{ route('password.change') }}" class="sidebar-user-item"
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
            <header class="topbar">
                <div class="topbar-title-wrap">
                    <button type="button" class="sidebar-toggle-btn" data-sidebar-toggle
                        aria-label="Open navigation" aria-controls="app-sidebar" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title-block">
                        <h2 class="page-title">{{ $title ?? 'Dashboard' }}</h2>
                        @if (!empty($subtitle))
                            <p class="page-subtitle">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <div class="topbar-actions">
                    @if (!empty($sharedFinancialYears) && $sharedFinancialYears->count() > 0)
                        <form method="POST" action="{{ route('financial-year.select') }}" class="topbar-fy-form">
                            @csrf
                            <select id="topbarFinancialYear" name="fy_id" class="topbar-fy-select">
                                @foreach ($sharedFinancialYears as $financialYear)
                                    <option value="{{ $financialYear->fy_id }}"
                                        {{ (string) ($sharedSelectedFinancialYearId ?? '') === (string) $financialYear->fy_id ? 'selected' : '' }}>
                                        {{ $financialYear->financial_year }}{{ $financialYear->default ? ' (Default)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    @yield('header_actions')
                </div>
            </header>

            <main class="content-panel">
                @yield('content')
            </main>
        </div>
    </div>

    <script type="application/json" id="notifications-data">{!! json_encode($notificationData->values()->all()) !!}</script>

    <!-- Notifications Modal -->
    <div class="fixed inset-0 z-50 hidden items-center justify-center p-4" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel" aria-hidden="true">
        <!-- Backdrop overlay -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm modal-close-overlay"></div>
        
        <!-- Dialog container -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden z-10 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
                <h5 class="text-sm font-bold text-slate-800" id="notificationsModalLabel">Notifications</h5>
                <button type="button" class="text-slate-400 hover:text-slate-600 text-lg font-light leading-none" onclick="closeModal('notificationsModal')" aria-label="Close">&times;</button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <div id="notificationsList" class="notification-list"></div>
            </div>
            <!-- Footer -->
            <div class="flex justify-end items-center gap-2 p-4 border-t border-slate-100 bg-slate-50">
                <button type="button" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors" onclick="closeModal('notificationsModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const topbarFySelect = document.getElementById('topbarFinancialYear');
            if (topbarFySelect) {
                topbarFySelect.addEventListener('change', function() {
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
                    notificationsList.innerHTML = '<div class="text-muted">No notifications yet.</div>';
                } else {
                    notifications.forEach((item) => {
                        notificationsList.appendChild(renderNotificationItem(item));
                    });
                }
            }

            if (notificationsButton && notificationsModalEl && typeof bootstrap !== 'undefined') {
                const notificationModal = new bootstrap.Modal(notificationsModalEl);
                const showNotificationsModal = function() {
                    notificationModal.show();
                };
                notificationsButton.addEventListener('click', showNotificationsModal);
                if (notificationsRow) {
                    notificationsRow.addEventListener('click', showNotificationsModal);
                    notificationsRow.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            showNotificationsModal();
                        }
                    });
                }
            }
        });

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
            document.querySelectorAll('input[type="date"]').forEach(function(input) {
                if (input._flatpickr) {
                    return;
                }

                flatpickr(input, Object.assign(buildDatePickerConfig(input), {
                    onReady: function(selectedDates, dateStr, instance) {
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
                    onChange: function(selectedDates, dateStr, instance) {
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
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            if (node.matches && node.matches('input[type="date"]') && !node
                                ._flatpickr) {
                                flatpickr(node, buildDatePickerConfig(node));
                            }
                            // Check for date inputs inside added nodes
                            const dateInputs = node.querySelectorAll ? node
                                .querySelectorAll('input[type="date"]') : [];
                            dateInputs.forEach(function(input) {
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
    @if (session('success') || session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.app-toast').forEach(function(toast) {
                    setTimeout(function() {
                        if (toast.parentNode) {
                            toast.classList.add('app-toast-leaving');
                            setTimeout(function() {
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
