<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Billing Software' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="app-shell">
    <!-- ['label' => 'Subscriptions', 'route' => 'subscriptions.index'], -->
    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Clients', 'route' => 'clients.index'],
            ['label' => 'Groups', 'route' => 'groups.index'],
            ['label' => 'Services', 'route' => 'services.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Estimates', 'route' => 'estimates.index'],
            ['label' => 'Settings', 'route' => 'settings.index'],
        ];
    @endphp

    <div class="layout-grid">
        <aside class="sidebar" data-sidebar>
            <div class="brand-block">
                <!-- <div class="brand-mark">SR</div> -->
                <div>
                    <p class="eyebrow">Billing</p>
                    <h5>Control Center</h5>
                </div>
            </div>

            <nav class="nav-list">
                @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="nav-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="sidebar-card">
                <p class="eyebrow">Collection Focus</p>
                <strong>Rs 1.18L still outstanding</strong>
                <span>Prioritize 6 accounts due this week.</span>
            </div>

            <div style="margin-top: auto; padding: 1.5rem; border-top: 1px solid var(--slate-800);">
                <strong>{{ auth()->user()->name }}</strong>
                <form action="{{ route('logout') }}" method="POST" style="margin-top: 0.5rem;">
                    @csrf
                    <button type="submit" class="text-link danger" style="font-size: 0.9rem;">Sign Out</button>
                </form>
            </div>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div>
                    <button type="button" class="menu-toggle" data-sidebar-toggle>Menu</button>
                    <h2>{{ $title ?? 'Dashboard' }}</h2>
                </div>

                <div class="topbar-actions">
                <div class="header-right" style="display: flex; align-items: center; gap: 1rem;">
                        <button type="button" class="icon-btn notification-btn" title="Notifications">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </button>
                        
                        <div class="dropdown">
                            <button type="button" class="icon-btn profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">{{ auth()->user()->name }}</h6></li>
                                <li><a class="dropdown-item" href="#">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            @if (session('success'))
                <div class="alert success">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert error">
                    {{ session('error') }}
                </div>
            @endif

            <main class="content-panel">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
