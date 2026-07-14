@extends('emails.layout')

@section('title', 'Welcome, ' . $user->name)

@section('content')
    <h1 class="email-title">Welcome, {{ $user->name }}!</h1>
    
    <p class="email-text">An account has been created for you. Below are your login credentials:</p>

    <div class="info-card">
        <h3 class="info-title">YOUR LOGIN CREDENTIALS</h3>
        <p class="info-row"><strong>Email:</strong> {{ $user->email }}</p>
        <p class="info-row"><strong>Password:</strong> {{ $password }}</p>
    </div>

    <div class="btn-wrapper">
        <a href="{{ config('app.team_url') }}/login" class="primary-btn">Login to your account</a>
    </div>

    <p class="email-text" style="margin-top: 35px;">
        Best regards,<br>
        <strong>{{ $senderName ?? 'Team' }}</strong>
    </p>

    @if (!empty($senderAddressLine) || !empty($senderPhone) || !empty($senderEmail))
        <div style="margin-top: 10px;">
            @if (!empty($senderAddressLine))
                <div class="info-row" style="font-size: 13px;">{{ $senderAddressLine }}</div>
            @endif
            @if (!empty($senderPhone))
                <div class="info-row" style="font-size: 13px;"><strong>Mob:</strong> {{ $senderPhone }}</div>
            @endif
            @if (!empty($senderEmail))
                <div class="info-row" style="font-size: 13px;"><strong>E-mail:</strong> <a href="mailto:{{ $senderEmail }}" style="color: #2563eb; text-decoration: none;">{{ $senderEmail }}</a></div>
            @endif
        </div>
    @endif
@endsection
