@extends('layouts.app')

@section('content')
@php
    $selectedClientId = request('c', request('clientid'));
    $currentStep = (int) request('step', 1);
    
    if($currentStep < 1 || $currentStep > 3) $currentStep = 1;

    $stepLabels = [
        'without_orders' => [1 => 'Client', 2 => 'Select Items', 3 => 'Review & Terms']
    ];

    $currentLabels = $stepLabels['without_orders'];
    $totalSteps = 3;
@endphp

@section('header_actions')
    @if($currentStep === 1)
        <a href="{{ $selectedClientId ? route('invoices.index', ['c' => $selectedClientId]) : route('invoices.index') }}" class="secondary-button">
            <i class="fas fa-arrow-left" class="icon-spaced"></i>Back
        </a>
    @endif
@endsection

<section class="panel-card invoice-create-shell" >
    @if ($errors->any())
        <div class="alert warning">
            <strong class="d-block mb-1">
                @if($errors->has('general')) Error: {{ $errors->first('general') }}
                @else Fix these issues before creating the invoice: @endif
            </strong>
            @unless($errors->has('general'))
            <ul class="plain-list">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
            @endunless
        </div>
    @endif

    <form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm">
        @csrf
        <input type="hidden" name="current_step" value="{{ $currentStep }}">

        @if($currentStep == 1)
            @include('invoices.steps.step1-client-source')
        @elseif($currentStep == 2)
            @include('invoices.steps.step2-add-items')
        @elseif($currentStep == 3)
            @include('invoices.steps.step4-preview-terms')
        @endif
    </form>
</section>
@endsection
