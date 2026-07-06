@extends(auth()->check() ? 'layouts.app' : 'layouts.error')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body p-5 text-center">
        <div class="mb-4">
            <i class="fas fa-lock text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
        </div>
        <h4 class="fw-bold text-dark mb-3">Permission Denied</h4>
        <p class="text-muted mb-4">
            Sorry, you are not authorized to access this module.<br/> Please contact your administrator if you need access.
        </p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 py-2">
            <i class="fas fa-home me-2"></i>Return to Dashboard
        </a>
    </div>
</div>
@endsection
