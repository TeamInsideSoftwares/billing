<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In | {{ config('app.name', 'Billing Software') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <main class="glass-card">
        <div class="login-brand">
            <!-- <div class="login-mark">SR</div> -->
        </div>
        <p class="login-eyebrow">SkoolReady Billing</p>
        <h1 class="login-title">Sign In</h1>
        <p class="login-subtitle">Enter your credentials to access your account</p>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="login-form" id="loginForm">
            @csrf
            <div>
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>

            <div class="remember-row">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember for 30 days</label>
            </div>

            <div class="login-footer-row">
                <a href="#" class="forgot-link">Forgot Password?</a>
                <button type="submit" class="primary-button login-button" id="loginBtn">
                    Sign In
                </button>
            </div>
        </form>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const form = this;
            form.classList.add('login-loading');
            btn.innerHTML = '<span class="spinner"></span> Signing In...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
