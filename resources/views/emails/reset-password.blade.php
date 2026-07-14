@extends('emails.layout')

@section('title', 'Reset your password')

@section('content')
    <h1 class="email-title">Reset your password</h1>
    <p class="email-text">Hi {{ $notifiable->name ?? $notifiable->email }},</p>
    
    <p class="email-text">We received a request to reset the password for your account. Tap the button below to choose a new password.</p>
    
    <div class="btn-wrapper">
        <a href="{{ $url }}" class="primary-btn">Reset Password</a>
    </div>
    
    <p class="fallback-text">If you are having trouble clicking the button, copy and paste this URL into your browser:</p>
    <a href="{{ $url }}" class="fallback-link">{{ $url }}</a>
    
    <br><br>
    
    <p class="email-text">If you did not request this password reset, please ignore this email and your password will remain unchanged.</p>
    
    <p class="email-text" style="margin-top: 24px;">
        Thanks,<br>
        {{ config('app.name') }} Team
    </p>
@endsection

@section('footer')
    If you don’t recognize this request, no action is needed. This email was sent to {{ $notifiable->email }}.
@endsection
