@extends('layouts.employee')

@section('content')
@php
    $profile = \App\Models\UserProfile::where('userid', $employee->userid)->first();
@endphp

@if(!$profile || $profile->status === 'rejected')
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <i class="fas fa-exclamation-circle text-warning" style="font-size: 4rem; opacity: 0.5;"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">Welcome, {{ $employee->name }}!</h4>
            <p class="text-muted mb-4">
                First fill your profile details, then continue.
            </p>
            <a href="{{ route('team-work.profile.edit') }}" class="btn btn-primary px-4 py-2">
                <i class="fas fa-user-edit me-2"></i>Complete Profile Now
            </a>
        </div>
    </div>
@elseif($profile->status === 'pending')
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-5 text-center">
            <div class="mb-4">
                <i class="fas fa-hourglass-half text-info" style="font-size: 4rem; opacity: 0.5;"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">Profile Under Review</h4>
            <p class="text-muted mb-4">
                Your profile details are currently being reviewed by an administrator.<br/> Please wait for approval.
            </p>
            <a href="{{ route('team-work.profile.edit') }}" class="btn btn-outline-primary px-4 py-2">
                <i class="fas fa-eye me-2"></i>View Submitted Details
            </a>
        </div>
    </div>
@else
    <div class="position-relative bg-white p-2 rounded-3 h-100">
        <div class="position-relative bg-DarkLight p-4 rounded-3 mb-2 text-center h-100 d-flex flex-column justify-content-center align-items-center" style="min-height: 60vh;">
            
            <div class="mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
                <h3 class="fw-bold text-dark mb-1">Welcome back, {{ $employee->name }}!</h3>
                <p class="text-muted">You are logged into your Employee Dashboard.</p>
            </div>

            <div class="row g-3 justify-content-center w-100" style="max-width: 900px;">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-clock fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block text-muted small fw-medium text-uppercase mb-1">Today's Shift</span>
                                <span class="d-block fw-semibold text-dark fs-5">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-check-circle fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block text-muted small fw-medium text-uppercase mb-1">Attendance</span>
                                <span class="d-block fw-semibold text-dark fs-5">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-start">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="fas fa-calendar-alt fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block text-muted small fw-medium text-uppercase mb-1">Leaves</span>
                                <span class="d-block fw-semibold text-dark fs-5">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif
</div>
@endsection
