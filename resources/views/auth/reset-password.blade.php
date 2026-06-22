<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | {{ config('app.name', 'SkoolReady') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        .password-toggle {
            cursor: pointer;
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
                                Reset Password
                            </h3>

                            <p class="text-dark mb-0">
                                Set a new password for your account
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

                        <form action="{{ route('password.store') }}" method="POST" id="resetPasswordForm" class="mainForm">

                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            {{-- Email --}}
                            <div class="mb-3">
                                <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Email Address<span class="text-danger">*</span>
                                </label>

                                <input type="email"
                                       name="email"
                                       id="email"
                                       value="{{ old('email', $email) }}"
                                       class="form-control"
                                       required
                                       autofocus>
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <label for="password" class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    New Password<span class="text-danger">*</span>
                                </label>

                                <div class="input-group">
                                    <input type="password"
                                           name="password"
                                           id="password"
                                           class="form-control"
                                           minlength="6"
                                           required>
                                    <span class="input-group-text password-toggle"
                                          data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Confirm Password<span class="text-danger">*</span>
                                </label>

                                <div class="input-group">
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           class="form-control"
                                           minlength="6"
                                           required>
                                    <span class="input-group-text password-toggle"
                                          data-target="password_confirmation">
                                        <i class="fas fa-eye"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between text-end">

                                <a href="{{ route('login') }}"
                                   class="text-decoration-none fw-semibold btn btn-outline-primary bg-white text-primary fw-medium text-center d-inline-flex align-items-center justify-content-center">
                                    <i class="fas fa-arrow-left btn-icon me-1"></i> Back to Login
                                </a>

                                <button type="submit"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium"
                                        id="resetSubmitBtn">
                                    Reset Password <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const target = document.getElementById(
                    this.dataset.target
                );
                const icon = this.querySelector('i');
                if (target.type === 'password') {
                    target.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    target.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        document.getElementById('resetPasswordForm').addEventListener('submit', function() {
            const btn = document.getElementById('resetSubmitBtn');
            btn.disabled = true;
            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Resetting...
            `;
        });
    </script>

</body>

</html>
