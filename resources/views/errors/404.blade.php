@extends(auth()->check() ? 'layouts.app' : 'layouts.error')

@section('content')
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body p-5 text-center">
        <div class="mb-3">
            <h1 class="display-1 fw-bolder text-muted mb-0" style="opacity: 0.5;">404</h1>
        </div>
        <h4 class="fw-bold text-dark mb-3">Page Not Found</h4>
        <p class="text-muted mb-4">
            Sorry, the page you are looking for does not exist or has been moved.
        </p>
    </div>
</div>
@endsection
