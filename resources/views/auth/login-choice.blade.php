<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Access | {{ config('app.name', 'Billing Software') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container">
        <div class="row min-vh-100 justify-content-center align-items-center">

            <div class="col-md-7 col-lg-5">

                <div class="card border-0 shadow-lg">

                    <div class="card-body p-4 p-lg-5 text-center">

                        <p class="text-primary fw-semibold text-uppercase small mb-2">
                            SkoolReady
                        </p>

                        <h2 class="fw-bold mb-2">
                            Choose Access
                        </h2>

                        <p class="text-muted mb-4">
                            This login is valid for both Superadmin and Panel.
                            Select where you would like to continue.
                        </p>

                        {{-- Errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger text-start">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Superadmin --}}
                        <form action="{{ route('login.choice.post') }}"
                              method="POST"
                              class="mb-3">

                            @csrf

                            <input type="hidden"
                                   name="target"
                                   value="superadmin">

                            <button type="submit"
                                    class="btn btn-primary w-100 py-2 fw-semibold">

                                <i class="fas fa-user-shield me-2"></i>
                                Continue to Superadmin

                            </button>

                        </form>

                        {{-- Panel --}}
                        <form action="{{ route('login.choice.post') }}"
                              method="POST">

                            @csrf

                            <input type="hidden"
                                   name="target"
                                   value="panel">

                            <button type="submit"
                                    class="btn btn-outline-primary w-100 py-2 fw-semibold">

                                <i class="fas fa-desktop me-2"></i>
                                Continue to Panel

                            </button>

                        </form>

                        <hr class="my-4">

                        <a href="{{ route('login') }}"
                           class="text-decoration-none">

                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Sign In

                        </a>

                    </div>

                </div>

            </div>

        </div>
    </div>

</body>

</html>
