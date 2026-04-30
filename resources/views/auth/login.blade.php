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
    <style>
        
/* Login Page Styles */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--bg) 0%, #0f172a 100%);
  padding: 1.5rem;
  font-family: var(--font-sans);
}

.login-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  justify-content: center;
}

.login-mark {
  display: grid;
  place-items: center;
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  color: white;
  font-weight: 800;
  font-size: 1.25rem;
  background: linear-gradient(135deg, var(--brand), #3b82f6);
  box-shadow: 0 0 30px rgba(37, 99, 235, 0.4);
}

.login-eyebrow {
  text-align: center;
  color: var(--text-muted);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 0.25rem;
}

.login-title {
  text-align: center;
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--text);
  margin: 0 0 1rem 0;
  letter-spacing: -0.025em;
}

.glass-card {
  width: 100%;
  max-width: 420px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 1.25rem;
  padding: 2.5rem;
  box-shadow: 0 25px 45px -10px rgba(0, 0, 0, 0.2);
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  position: relative;
  overflow: hidden;
}

.glass-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--brand), #3b82f6);
}

.login-form input {
  background: rgba(255, 255, 255, 0.9) !important;
  border: 2px solid transparent !important;
  transition: all 0.3s ease;
}

.login-form input:focus {
  border-color: var(--brand) !important;
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
  background: white !important;
  transform: translateY(-1px);
}

.login-form label {
  color: var(--text);
  font-weight: 600;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
  display: block;
}

.remember-row {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin: 1.25rem 0;
  font-size: 0.875rem;
  color: var(--text-muted);
}

.remember-row input[type="checkbox"] {
  width: auto;
  margin: 0.2rem 0 0 0;
}

.forgot-link {
  color: var(--brand);
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.login-loading {
  opacity: 0.7;
  pointer-events: none;
}

.login-loading .login-button {
  position: relative;
}

.login-button {
  margin-top: 0.5rem;
}

.spinner {
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  width: 1.25rem;
  height: 1.25rem;
  animation: spin 1s linear infinite;
  margin-right: 0.5rem;
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 480px) {
  .glass-card {
    padding: 2rem;
    margin: 0 1rem;
  }
}

    </style>
</head>
<body class="login-page">
    <main class="glass-card">
        <div class="login-brand">
            <!-- <div class="login-mark">SR</div> -->
        </div>
        <p class="login-eyebrow">SkoolReady Billing</p>
        <h1 class="login-title">Sign In</h1>
        <p style="text-align: center; color: rgba(255,255,255,0.8); margin-bottom: 2rem; font-size: 1rem;">Enter your credentials to access your account</p>

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

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
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
