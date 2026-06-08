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

            <div class="col-md-6 col-lg-4">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4 p-lg-5">

                        <div class="text-center mb-4">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                SkoolReady
                            </p>

                            <h2 class="fw-bold mb-2">
                                Reset Password
                            </h2>

                            <p class="text-muted mb-0">
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

                        <form action="{{ route('password.store') }}" method="POST">

                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            {{-- Email --}}
                            <div class="mb-3">

                                <label for="email" class="form-label">
                                    Email Address
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

                                <label for="password" class="form-label">
                                    New Password
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
                            <div class="mb-4">

                                <label for="password_confirmation" class="form-label">
                                    Confirm Password
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

                            <div class="d-flex justify-content-between align-items-center">

                                <a href="{{ route('login') }}"
                                   class="text-decoration-none">
                                    Back to Sign In
                                </a>

                                <button type="submit"
                                        class="btn btn-primary">
                                    Reset Password
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
    </script>

</body>

</html>
