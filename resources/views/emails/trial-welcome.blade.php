@extends('emails.layout')

@section('title', 'Welcome to SkoolReady')

@section('content')
    <h1 class="email-title">Welcome to SkoolReady</h1>
    <p class="email-text">Your {{ $trialDays }}-day trial is active and ready to use.</p>

    <p class="email-text">Hello {{ $name }},</p>
    <p class="email-text">Your trial account has been created successfully. Use the credentials below to sign in and get started.</p>

    <div class="info-card">
        <h3 class="info-title">YOUR LOGIN CREDENTIALS</h3>
        <p class="info-row"><strong>Login Email:</strong> {{ $email }}</p>
        <p class="info-row"><strong>Password:</strong> {{ $temporaryPassword }}</p>
    </div>

    <div class="btn-wrapper">
        <a href="{{ $loginUrl }}" class="primary-btn">Login to SkoolReady</a>
    </div>

    <p class="email-text">Please change your password after your first login for better security.</p>

    <p class="email-text" style="margin-top: 35px;">
        Best regards,<br>
        <strong>{{ $senderName ?? 'SkoolReady Team' }}</strong>
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
