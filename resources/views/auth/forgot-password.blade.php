<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | {{ config('app.name', 'Billing Software') }}</title>

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

            <div class="col-md-6 col-lg-4 mx-auto">
                <div class="card border-0">
                    <div class="card-body p-3">
                        <div class="text-start mb-3">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                <small>SkoolReady</small>
                            </p>

                            <h3 class="fw-bold text-dark mb-2">
                                Forgot Password
                            </h3>

                            <p class="text-dark mb-0">
                                Enter your email address to receive a password reset link.
                            </p>

                        </div>

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

                        <form action="{{ route('password.email') }}" method="POST" id="forgotPasswordForm" class="mainForm">

                            @csrf

                            <div class="mb-3">
                                <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Email Address<span class="text-danger">*</span>
                                </label>

                                <input type="email"
                                       name="email"
                                       id="email"
                                       value="{{ old('email') }}"
                                       class="form-control"
                                       required
                                       autofocus>
                            </div>

                            <div class="d-flex justify-content-between text-end">

                                <a href="{{ route('login') }}"
                                   class="text-decoration-none fw-semibold btn btn-outline-primary bg-white text-primary fw-medium text-center d-inline-flex align-items-center justify-content-center">
                                    <i class="fas fa-arrow-left btn-icon me-1"></i> Back to Login
                                </a>

                                <button type="submit"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium"
                                        id="resetBtn">
                                    Send Reset Link <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function() {
            const btn = document.getElementById('resetBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Sending...
            `;
        });
    </script>

</body>

</html>
