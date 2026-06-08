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

            <div class="col-md-6 col-lg-4">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4 p-lg-5">

                        <div class="text-center mb-4">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                SkoolReady
                            </p>

                            <h2 class="fw-bold mb-2">
                                Forgot Password
                            </h2>

                            <p class="text-muted mb-0">
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

                        <form action="{{ route('password.email') }}" method="POST">

                            @csrf

                            <div class="mb-4">
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

                            <div class="d-flex justify-content-between align-items-center">

                                <a href="{{ route('login') }}"
                                   class="text-decoration-none">
                                    Back to Sign In
                                </a>

                                <button type="submit"
                                        class="btn btn-primary">
                                    Send Reset Link
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        </div>
    </div>

</body>

</html>
