<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | {{ config('app.name', 'Billing Software') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

            <div class="col-md-6 col-lg-4">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4 p-lg-5">

                        <div class="text-center mb-4">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                SkoolReady
                            </p>

                            <h2 class="fw-bold mb-2">
                                Sign In
                            </h2>

                            <p class="text-muted mb-0">
                                Enter your credentials to access your account
                            </p>

                        </div>

                        {{-- Validation Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">

                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>

                            </div>
                        @endif

                        {{-- Success Message --}}
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Error Message --}}
                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form action="{{ route('login.post') }}"
                              method="POST"
                              id="loginForm">

                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email Address
                                </label>

                                <input type="email"
                                       name="email"
                                       id="email"
                                       value="{{ old('email') }}"
                                       class="form-control"
                                       required
                                       autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>

                                <input type="password"
                                       name="password"
                                       id="password"
                                       class="form-control"
                                       required>
                            </div>

                            <div class="form-check mb-3">

                                <input class="form-check-input"
                                       type="checkbox"
                                       name="remember"
                                       id="remember">

                                <label class="form-check-label"
                                       for="remember">
                                    Remember for 30 days
                                </label>

                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">

                                <a href="{{ route('password.request') }}"
                                   class="text-decoration-none">
                                    Forgot Password?
                                </a>

                            </div>

                            <button type="submit"
                                    class="btn btn-primary w-100"
                                    id="loginBtn">

                                Sign In

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {

            const btn = document.getElementById('loginBtn');

            btn.disabled = true;

            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Signing In...
            `;
        });
    </script>

</body>

</html>
