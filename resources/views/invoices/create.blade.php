@extends('layouts.app')

@section('content')
@php
    $selectedClientId = request('c', request('clientid'));
    $currentStep = (int) request('step', 1);
    $invoiceFor = request('invoice_for', session('invoice_for', ''));

    session(['invoice_for' => $invoiceFor]);
    
    if($currentStep < 1 || $currentStep > 4) $currentStep = 1;

    $stepLabels = [
        'orders' => [1 => 'Client & Source', 2 => 'Select Orders', 3 => 'Edit Items', 4 => 'Review & Terms'],
        'renewal' => [1 => 'Client & Source', 2 => 'Select Renewals', 3 => 'Edit Items', 4 => 'Review & Terms'],
        'without_orders' => [1 => 'Client & Source', 2 => 'Add Items', 3 => 'Review & Terms']
    ];

    $currentLabels = $stepLabels[$invoiceFor] ?? $stepLabels['orders'];
    $totalSteps = $invoiceFor === 'without_orders' ? 3 : 4;
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
        <input type="hidden" name="invoice_for" value="{{ $invoiceFor }}">

        @if($currentStep == 1)
            @include('invoices.steps.step1-client-source')
        @elseif($currentStep == 2)
            @if($invoiceFor === 'without_orders')
                @include('invoices.steps.step2-add-items')
            @else
                @if(view()->exists("invoices.steps.step2-select-{$invoiceFor}"))
                    @include("invoices.steps.step2-select-{$invoiceFor}")
                @else
                    @include('invoices.steps.step2-select-orders')
                @endif
            @endif
        @elseif($currentStep == 3 && $invoiceFor !== 'without_orders')
            @include('invoices.steps.step3-edit-items')
        @elseif(($invoiceFor === 'without_orders' && $currentStep == 3) || $currentStep == 4)
            @include('invoices.steps.step4-preview-terms')
        @endif
    </form>
</section>
@endsection
