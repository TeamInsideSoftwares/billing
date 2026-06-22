@extends('layouts.app')

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <div class="row g-2">
        <div class="col-12 col-md-3">
            <div class="bg-light p-2 rounded-3 h-100">
                <div class="mb-2">
                    <h5 class="fw-semibold text-primary small lh-sm mb-0">Change Password</h5>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li class="small">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.change.update') }}" method="POST" id="changePasswordForm" class="mainForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-2">
                        <label for="current_password" class="form-label small lh-sm fw-semibold text-dark mb-1">
                            Current Password<span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <span class="input-group-text password-toggle" data-target="current_password" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label small lh-sm fw-semibold text-dark mb-1">
                            New Password<span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <span class="input-group-text password-toggle" data-target="password" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="password_confirmation" class="form-label small lh-sm fw-semibold text-dark mb-1">
                            Confirm New Password<span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="6">
                            <span class="input-group-text password-toggle" data-target="password_confirmation" style="cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="submitBtn">
                            Update Password <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.password-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const target = document.getElementById(
                this.dataset.target
            );
            const icon = this.querySelector('i');
            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    document.getElementById('changePasswordForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Updating...
        `;
    });
</script>
@endsection
