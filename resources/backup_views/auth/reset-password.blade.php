<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | {{ config('app.name', 'Skoolready') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <main class="glass-card">
        <p class="login-eyebrow">SkoolReady</p>
        <h1 class="login-title">Reset Password</h1>
        <p class="login-subtitle">Set a new password for your account</p>

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

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form action="{{ route('password.store') }}" method="POST" class="login-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $email) }}" required autofocus>
            </div>

            <div>
                <label for="password">New Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" required minlength="6">
                    <span class="password-eye" data-toggle-password="password" tabindex="-1" aria-hidden="true">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div>
                <label for="password_confirmation">Confirm Password</label>
                <div class="password-field">
                    <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6">
                    <span class="password-eye" data-toggle-password="password_confirmation" tabindex="-1" aria-hidden="true">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <div class="login-footer-row">
                <a href="{{ route('login') }}" class="forgot-link">Back to Sign In</a>
                <button type="submit" class="primary-button login-button">
                    Reset Password
                </button>
            </div>
        </form>
    </main>
    <style>
        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 2.5rem;
        }

        .password-eye {
            position: absolute;
            right: 0.8rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            line-height: 1;
            user-select: none;
        }
    </style>
    <script>
        document.querySelectorAll('.password-eye[data-toggle-password]').forEach(function(icon) {
            icon.addEventListener('click', function() {
                var inputId = icon.getAttribute('data-toggle-password');
                var input = document.getElementById(inputId);
                if (!input) return;
                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                var i = icon.querySelector('i');
                if (i) {
                    i.classList.toggle('fa-eye', !isHidden);
                    i.classList.toggle('fa-eye-slash', isHidden);
                }
            });
        });
    </script>
</body>
</html>
