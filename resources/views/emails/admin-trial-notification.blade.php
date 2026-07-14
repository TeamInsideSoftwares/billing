@extends('emails.layout')

@section('title', 'New Trial Registration')

@section('content')
    <h1 class="email-title">New Trial Registration</h1>
    <p class="email-text">A new client has started a {{ $trialDays }}-day trial.</p>

    <p class="email-text">Hello,</p>
    <p class="email-text">A new trial registration has been processed through the system. Here are the details:</p>

    <div class="info-card">
        <h3 class="info-title">TRIAL DETAILS</h3>
        <p class="info-row"><strong>Business Name:</strong> {{ $businessName }}</p>
        <p class="info-row"><strong>Contact Name:</strong> {{ $contactName }}</p>
        <p class="info-row"><strong>Email:</strong> {{ $email }}</p>
        @if($phone)
        <p class="info-row"><strong>Phone:</strong> {{ $phone }}</p>
        @endif
        <p class="info-row"><strong>Trial Product:</strong> {{ $trialItemName }}</p>
    </div>

    <p class="email-text">You can view more details in the admin dashboard.</p>
@endsection
