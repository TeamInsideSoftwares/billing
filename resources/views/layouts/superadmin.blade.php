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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

    <header class="superadmin-topbar">
        <h1 class="superadmin-brand">
            <i class="fas fa-shield-halved"></i>
            Superadmin
        </h1>
        <div class="d-flex align-items-center gap-2">
            @yield('header_actions')
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="btn btn-secondary">Sign Out</button>
            </form>
        </div>
    </header>

    <main class="superadmin-content">
        @yield('content')
    </main>

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
</body>

</html>
