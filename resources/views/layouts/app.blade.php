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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="//tiny.skoolready.com/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/location-picker.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

            <div class="user-section">
                <div class="user-actions">
                    <div class="dropdown">
                        <button type="button" class="icon-btn notification-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
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

                    <div class="dropdown">
                        <button type="button" class="icon-btn profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <strong class="user-name">{{ auth()->user()->name }}</strong>
                    <span class="user-email">{{ auth()->user()->email }}</span>
                </div>
            </div>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div class="topbar-title-wrap">
                    <!-- <button type="button" class="menu-toggle" data-sidebar-toggle>Menu</button> -->
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
</body>
</html>
