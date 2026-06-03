@extends('layouts.app')

@section('content')
<div class="soft-card p-4" style="max-width: 640px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0 fw-700">Change Password</h5>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('password.change.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="password-field">
                <input type="password" class="form-control" id="current_password" name="current_password" required>
                <span class="password-eye" data-toggle-password="current_password" tabindex="-1" aria-hidden="true">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <div class="password-field">
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <span class="password-eye" data-toggle-password="password" tabindex="-1" aria-hidden="true">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <div class="password-field">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="6">
                <span class="password-eye" data-toggle-password="password_confirmation" tabindex="-1" aria-hidden="true">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="primary-button">Update Password</button>
        </div>
    </form>
</div>

<style>
    .password-field {
        position: relative;
    }

    .password-field .form-control {
        padding-right: 2.5rem;
    }

    .password-eye {
        position: absolute;
        right: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        cursor: pointer;
        line-height: 1;
        user-select: none;
    }
</style>

<script>
    document.querySelectorAll('.password-eye[data-toggle-password]').forEach(function(icon) {
        icon.addEventListener('click', function() {
            var inputId = icon.getAttribute('data-toggle-password');
            var input = document.getElementById(inputId);
            if (!input) return;
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            var i = icon.querySelector('i');
            if (i) {
                i.classList.toggle('fa-eye', !isHidden);
                i.classList.toggle('fa-eye-slash', isHidden);
            }
        });
    });
</script>
@endsection
