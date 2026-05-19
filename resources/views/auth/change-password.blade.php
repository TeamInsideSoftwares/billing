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
            <input type="password" class="form-control" id="current_password" name="current_password" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="primary-button">Update Password</button>
        </div>
    </form>
</div>
@endsection
