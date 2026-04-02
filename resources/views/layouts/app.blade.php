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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<script src="//tiny.skoolready.com/tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/location-picker.js') }}"></script>

</head>
<body class="app-shell">
    <!-- ['label' => 'Subscriptions', 'route' => 'subscriptions.index'], -->
    @php
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard'],
            ['label' => 'Clients', 'route' => 'clients.index'],
            ['label' => 'Services', 'route' => 'services.index'],
            ['label' => 'Quotations', 'route' => 'quotations.index'],
            ['label' => 'Invoices', 'route' => 'invoices.index'],
            ['label' => 'Payments', 'route' => 'payments.index'],
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
                    @php
                        // Extract the base route name (e.g., 'services' from 'services.index')
                        $baseRoute = explode('.', $item['route'])[0];
                        // Check if current route starts with the base route name
                        $isActive = request()->routeIs($baseRoute . '.*') || request()->routeIs($item['route']);
                    @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="nav-link {{ $isActive ? 'is-active' : '' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <!-- <div class="sidebar-card">
                <p class="eyebrow">Collection Focus</p>
                <strong>Rs 1.18L still outstanding</strong>
                <span>Prioritize 6 accounts due this week.</span>
            </div> -->

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
                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    @php
                        $searchConfig = [
                            'clients' => ['route' => 'clients.index', 'placeholder' => 'Search clients...'],
                            'services' => ['route' => 'services.index', 'placeholder' => 'Search services...'],
                            'invoices' => ['route' => 'invoices.index', 'placeholder' => 'Search invoices...'],
                            'quotations' => ['route' => 'quotations.index', 'placeholder' => 'Search quotations...'],
                            'payments' => ['route' => 'payments.index', 'placeholder' => 'Search payments...'],
                            'groups' => ['route' => 'groups.index', 'placeholder' => 'Search groups...'],
                        ];
                        
                        $currentSection = null;
                        foreach ($searchConfig as $section => $config) {
                            if (request()->routeIs($section . '.*')) {
                                $currentSection = $section;
                                break;
                            }
                        }
                    @endphp
                    
                    @if($currentSection)
                        <form method="GET" action="{{ route($searchConfig[$currentSection]['route']) }}" id="header-search-form" style="margin: 0; position: relative; max-width: 450px; flex: 1;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                            <input 
                                type="search" 
                                name="search" 
                                id="header-search-input"
                                placeholder="{{ $searchConfig[$currentSection]['placeholder'] }}" 
                                value="{{ request('search') }}"
                                style="width: 100%; padding: 0.6rem 0.75rem 0.6rem 2.5rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; background: #f8fafc; transition: all 0.2s;"
                                onfocus="this.style.background='white'; this.style.borderColor='#3b82f6'; this.previousElementSibling.setAttribute('stroke', '#3b82f6');"
                                onblur="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'; this.previousElementSibling.setAttribute('stroke', '#94a3b8');">
                        </form>
                        <script>
                            document.getElementById('header-search-input').addEventListener('input', function(e) {
                                if (e.target.value === '') {
                                    document.getElementById('header-search-form').submit();
                                }
                            });
                        </script>
                    @else
                        <button type="button" class="menu-toggle" data-sidebar-toggle>Menu</button>
                        <h2>{{ $title ?? 'Dashboard' }}</h2>
                    @endif
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
