<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | {{ config('app.name', 'Billing Software') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="login-page">
    <main class="glass-card">
        <p class="login-eyebrow">SkoolReady</p>
        <h1 class="login-title">Forgot Password</h1>
        <p class="login-subtitle">Enter your email to receive a reset link</p>

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

        <form action="{{ route('password.email') }}" method="POST" class="login-form">
            @csrf
            <div>
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="login-footer-row">
                <a href="{{ route('login') }}" class="forgot-link">Back to Sign In</a>
                <button type="submit" class="primary-button login-button">
                    Send Reset Link
                </button>
            </div>
        </form>
    </main>
</body>

</html>
