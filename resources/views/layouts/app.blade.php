<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Billing Software' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell">
    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Clients', 'route' => 'clients.index'],
            ['label' => 'Services', 'route' => 'services.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
            ['label' => 'Subscriptions', 'route' => 'subscriptions.index'],
            ['label' => 'Estimates', 'route' => 'estimates.index'],
            ['label' => 'Settings', 'route' => 'settings.index'],
        ];
    @endphp

    <div class="layout-grid">
        <aside class="sidebar" data-sidebar>
            <div class="brand-block">
                <div class="brand-mark">SR</div>
                <div>
                    <p class="eyebrow">SkoolReady Billing</p>
                    <h1>Control Center</h1>
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
                    <p class="eyebrow">Operations Workspace</p>
                    <h2>{{ $title ?? 'Dashboard' }}</h2>
                </div>

                <div class="topbar-actions">
                    <form action="{{ route('clients.index') }}" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search clients, invoices, payments..." class="search-chip">
                    </form>
                    <a href="{{ route('invoices.create' ?? 'invoices.index') }}" class="primary-button">New Invoice</a>
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
