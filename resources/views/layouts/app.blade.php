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
    <style>
    /* Flatpickr custom styling to match project design */
    .flatpickr-calendar {
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
        font-family: 'Inter', sans-serif;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year {
        font-weight: 600;
        font-size: 0.95rem;
    }
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: #3b82f6;
        border-color: #3b82f6;
    }
    .flatpickr-day.today {
        border-color: #3b82f6;
    }
    .flatpickr-day:hover {
        background: #f1f5f9;
        border-color: #e2e8f0;
    }
    .flatpickr-day.selected:hover {
        background: #3b82f6;
        border-color: #3b82f6;
    }
    .flatpickr-months .flatpickr-prev-month:hover svg,
    .flatpickr-months .flatpickr-next-month:hover svg {
        fill: #3b82f6;
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed !important;
        top: 20px !important;
        right: 20px !important;
        z-index: 999999 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        pointer-events: none !important;
    }
    .toast {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 14px 20px !important;
        border-radius: 10px !important;
        font-size: 0.9rem !important;
        font-weight: 600 !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
        border: 2px solid !important;
        pointer-events: auto !important;
        animation: toastSlideIn 0.4s ease-out !important;
        cursor: pointer !important;
        min-width: 300px !important;
        max-width: 400px !important;
        backdrop-filter: blur(12px) !important;
    }
    .toast.toast-success {
        background: #ecfdf5 !important;
        border-color: #34d399 !important;
        color: #065f46 !important;
    }
    .toast.toast-error {
        background: #fef2f2 !important;
        border-color: #f87171 !important;
        color: #991b1b !important;
    }
    .toast.toast-leaving {
        animation: toastSlideOut 0.3s ease-in forwards !important;
    }
    .toast .toast-icon {
        font-size: 1.2rem !important;
        flex-shrink: 0 !important;
    }
    .toast.toast-success .toast-icon { color: #10b981 !important; }
    .toast.toast-error .toast-icon { color: #ef4444 !important; }

    @keyframes toastSlideIn {
        from { opacity: 0; transform: translateX(40px); }
        to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes toastSlideOut {
        from { opacity: 1; transform: translateX(0); }
        to   { opacity: 0; transform: translateX(40px); }
    }
    </style>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="//tiny.skoolready.com/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/location-picker.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

</head>
<body class="app-shell">
    {{-- Toast Container (outside layout-grid to avoid clipping) --}}
    @if (session('success') || session('error'))
        <div id="toast-container" class="toast-container">
            @if (session('success'))
                <div class="toast toast-success" onclick="this.remove()">
                    <i class="fas fa-check-circle toast-icon"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="toast toast-error" onclick="this.remove()">
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
            ['label' => 'Items', 'route' => 'services.index'],
            ['label' => 'Orders', 'route' => 'orders.index'],
            ['label' => 'Quotations', 'route' => 'quotations.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Settings', 'route' => 'settings.index'],
        ];
    @endphp

    <div class="layout-grid">
        <aside class="sidebar" data-sidebar>
            <div class="brand-block">
                <div class="brand-mark">SR</div>
                <div class="brand-text">
                    <p class="eyebrow">Billing</p>
                    <h5>Control Center</h5>
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

            <div class="user-section" style="margin-top: auto; padding: 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div class="user-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: #3b82f6; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.85rem; flex-shrink: 0;">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="user-text">
                    <strong style="display: block; font-size: 0.9rem;">{{ auth()->user()->name }}</strong>
                    <form action="{{ route('logout') }}" method="POST" style="margin-top: 0.25rem;">
                        @csrf
                        <button type="submit" class="text-link danger" style="font-size: 0.8rem; padding: 0; background: none; border: none; cursor: pointer;">Sign Out</button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    <!-- <button type="button" class="menu-toggle" data-sidebar-toggle>Menu</button> -->
                    <div style="display: flex; flex-direction: column; gap: 0.2rem;">
                        <h2 style="margin: 0;">{{ $title ?? 'Dashboard' }}</h2>
                        @if(!empty($subtitle))
                            <p style="margin: 0; font-size: 0.82rem; color: #64748b;">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                <div class="topbar-actions">
                <div class="header-right" style="display: flex; align-items: center; gap: 0.5rem;">
                        <div class="dropdown">
                            <button type="button" class="icon-btn notification-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications" style="position: relative; width: 40px; height: 40px; border-radius: 10px; border: none; background: #f1f5f9; color: #475569; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                </svg>
                                <span style="position: absolute; top: 8px; right: 8px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 2px solid white;"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto; padding: 0; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;">
                                <li style="padding: 1rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                                    <h6 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Notifications</h6>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" style="padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; display: block; transition: background 0.2s;">
                                        <div style="display: flex; gap: 0.75rem;">
                                            <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; margin-top: 0.5rem; flex-shrink: 0;"></div>
                                            <div style="flex: 1;">
                                                <p style="margin: 0 0 0.25rem 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">New invoice payment received</p>
                                                <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Payment of Rs 15,000 received for Invoice #INV-001</p>
                                                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #94a3b8;">2 hours ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" style="padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; display: block; transition: background 0.2s;">
                                        <div style="display: flex; gap: 0.75rem;">
                                            <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%; margin-top: 0.5rem; flex-shrink: 0;"></div>
                                            <div style="flex: 1;">
                                                <p style="margin: 0 0 0.25rem 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Invoice overdue reminder</p>
                                                <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Invoice #INV-045 is 5 days overdue</p>
                                                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #94a3b8;">1 day ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" style="padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; display: block; transition: background 0.2s;">
                                        <div style="display: flex; gap: 0.75rem;">
                                            <div style="width: 8px; height: 8px; background: transparent; border-radius: 50%; margin-top: 0.5rem; flex-shrink: 0;"></div>
                                            <div style="flex: 1;">
                                                <p style="margin: 0 0 0.25rem 0; font-size: 0.85rem; font-weight: 500; color: #475569;">New client added</p>
                                                <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Acme Corp has been added to your clients</p>
                                                <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #94a3b8;">3 days ago</p>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li style="padding: 0.75rem 1rem; text-align: center; border-top: 1px solid #e2e8f0;">
                                    <a href="#" style="font-size: 0.85rem; color: #3b82f6; text-decoration: none; font-weight: 500;">View all notifications</a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="dropdown">
                            <button type="button" class="icon-btn profile-btn" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px; border-radius: 10px; border: none; background: #3b82f6; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-weight: 600; font-size: 0.9rem;" onmouseover="this.style.background='#2563eb'; this.style.transform='scale(1.05)';" onmouseout="this.style.background='#3b82f6'; this.style.transform='scale(1)';">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 200px; padding: 0.5rem 0; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;">
                                <li style="padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0;">
                                    <h6 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #1e293b;">{{ auth()->user()->name }}</h6>
                                    <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #64748b;">{{ auth()->user()->email }}</p>
                                </li>
                                <li><a class="dropdown-item" href="{{ route('settings.index') }}#personal" style="padding: 0.6rem 1rem; font-size: 0.9rem; transition: background 0.2s;">Profile Settings</a></li>
                                <li><hr class="dropdown-divider" style="margin: 0.5rem 0;"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger" style="padding: 0.6rem 1rem; font-size: 0.9rem; width: 100%; text-align: left; background: none; border: none; cursor: pointer; transition: background 0.2s;">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <main class="content-panel">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr on all date inputs
        flatpickr('input[type="date"]', {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
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
        document.querySelectorAll('.toast').forEach(function (toast) {
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.classList.add('toast-leaving');
                    setTimeout(function () {
                        if (toast.parentNode) toast.remove();
                    }, 300);
                }
            }, 3500);
        });
    });
    </script>
    @endif
</body>
</html>
