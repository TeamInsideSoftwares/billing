<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Access | {{ config('app.name', 'Billing Software') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">

            <div class="col-md-5 mx-auto">
                <div class="card border-0">
                    <div class="card-body p-3">
                        <div class="text-start mb-3">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                <small>SkoolReady</small>
                            </p>

                            <h3 class="fw-bold text-dark mb-2">
                                Choose Access
                            </h3>

                            <p class="text-dark mb-0">
                                This login is valid for both Superadmin and Panel. Select where you would like to continue.
                            </p>

                        </div>

                        {{-- Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger text-start">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <a href="{{ route('login') }}"
                                class="text-decoration-none btn bg-white text-primary btn-outline-primary fw-medium d-inline-flex align-items-center py-2">
                                    <i class="fas fa-arrow-left btn-icon me-1"></i> Back to Login
                                </a>
                            </div>
                            <div class="d-flex justify-content-between gap-2">
                        {{-- Superadmin --}}
                        <form action="{{ route('login.choice.post') }}"
                              method="POST"
                              id="superadminForm"
                              class="mb-3">

                            @csrf

                            <input type="hidden"
                                   name="target"
                                   value="superadmin">

                            <button type="submit"
                                    id="superadminBtn"
                                    class="btn btn-outline-primary btn-primary text-white fw-medium w-100 py-2 d-inline-flex align-items-center justify-content-center">
                                Superadmin <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>

                        </form>
                        
                        {{-- Panel --}}
                        <form action="{{ route('login.choice.post') }}"
                              method="POST"
                              id="panelForm">

                            @csrf

                            <input type="hidden"
                                   name="target"
                                   value="panel">

                            <button type="submit"
                                    id="panelBtn"
                                    class="btn btn-outline-primary btn-primary text-white fw-medium w-100 py-2 d-inline-flex align-items-center justify-content-center">
                                Panel <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>

                        </form>
                        </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <script>
        document.getElementById('superadminForm').addEventListener('submit', function() {
            const btn = document.getElementById('superadminBtn');
            const panelBtn = document.getElementById('panelBtn');
            btn.disabled = true;
            panelBtn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Connecting...
            `;
        });

        document.getElementById('panelForm').addEventListener('submit', function() {
            const btn = document.getElementById('panelBtn');
            const superadminBtn = document.getElementById('superadminBtn');
            btn.disabled = true;
            superadminBtn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Connecting...
            `;
        });
    </script>

</body>

</html>
