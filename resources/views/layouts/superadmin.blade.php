<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Superadmin' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

    <style>
        .superadmin-shell {
            min-height: 100vh;
            background: #f5f7fb;
        }
        .superadmin-topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.9rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .superadmin-brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
        }
        .superadmin-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.25rem;
        }
    </style>
</head>
<body class="superadmin-shell">
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

    <header class="superadmin-topbar">
        <h1 class="superadmin-brand">
            <i class="fas fa-shield-halved"></i>
            Superadmin
        </h1>
        <div class="d-flex align-items-center gap-2">
            @yield('header_actions')
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="secondary-button">Sign Out</button>
            </form>
        </div>
    </header>

    <main class="superadmin-content">
        @yield('content')
    </main>
</body>
</html>
