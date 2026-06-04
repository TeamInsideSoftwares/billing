<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Access | {{ config('app.name', 'Billing Software') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="glass-card login-choice-card">
        <p class="login-eyebrow mb-3">SkoolReady</p>
        <h1 class="login-title">Choose Access</h1>
        <p class="login-subtitle login-choice-subtitle">This login is valid for both Superadmin and Panel. Select where to continue.</p>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.choice.post') }}" method="POST" class="login-form login-choice-form">
            @csrf
            <input type="hidden" name="target" value="superadmin">
            <button type="submit" class="primary-button login-button w-100 login-choice-btn">Continue to Superadmin</button>
        </form>

        <form action="{{ route('login.choice.post') }}" method="POST" class="login-form login-choice-form">
            @csrf
            <input type="hidden" name="target" value="panel">
            <button type="submit" class="primary-button login-button w-100 login-choice-btn">Continue to Panel</button>
        </form>

        <div class="login-footer-row login-choice-footer">
            <a href="{{ route('login') }}" class="forgot-link">Back to Sign In</a>
        </div>
    </main>

    <style>
        body.login-page {
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
        }

        .login-choice-card {
            max-width: 520px;
            padding: 2.75rem 2.6rem 2.35rem;
            margin-inline: auto;
            justify-self: center;
        }

        .login-choice-subtitle {
            max-width: 420px;
            margin: 0 auto 2.2rem;
            line-height: 1.45;
        }

        .login-choice-form + .login-choice-form {
            margin-top: 1rem;
        }

        .login-choice-btn {
            margin-top: 0;
            min-height: 2.5rem;
            border-radius: 0.55rem;
            font-weight: 700;
        }

        .login-choice-footer {
            justify-content: flex-start;
            margin-top: 1.2rem;
        }
    </style>
</body>
</html>
