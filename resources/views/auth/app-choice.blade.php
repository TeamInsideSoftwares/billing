<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose Application | {{ config('app.name', 'Billing Software') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

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

            <div class="col-md-5 mx-auto">
                <div class="card border-0">
                    <div class="card-body p-4">
                        <div class="text-start mb-4">

                            <p class="text-primary fw-semibold text-uppercase small mb-2">
                                <small>SkoolReady</small>
                            </p>

                            <h3 class="fw-bold text-dark mb-2">
                                Choose Application
                            </h3>

                            <p class="text-dark mb-0">
                                You have access to both the Billing Panel and Team Work. Select where you would like to continue.
                            </p>

                        </div>

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

                        
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="btn bg-white text-primary btn-outline-primary fw-medium d-inline-flex align-items-center py-2 h-100 px-3">
                                        <i class="fas fa-sign-out-alt btn-icon me-2"></i> Logout
                                    </button>
                                </form>
                            </div>
                            <div class="d-flex justify-content-between gap-2 flex-grow-1">
                                <a href="{{ route('team-work.dashboard') }}"
                                   class="btn btn-outline-primary btn-primary text-white fw-medium py-2 d-inline-flex align-items-center justify-content-center w-50">
                                    Team Work <i class="fas fa-arrow-right btn-icon ms-2"></i>
                                </a>
                                
                                <a href="{{ route('dashboard') }}"
                                   class="btn btn-outline-primary btn-primary text-white fw-medium py-2 d-inline-flex align-items-center justify-content-center w-50">
                                    Billing Panel <i class="fas fa-arrow-right btn-icon ms-2"></i>
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>

</body>

</html>
