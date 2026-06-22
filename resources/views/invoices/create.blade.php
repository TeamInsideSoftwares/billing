@extends('layouts.app')

@section('header_actions')
<a href="{{ route('invoices.index') }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Invoice List
</a>
@endsection

@section('content')
@php
$title = 'Manage Invoices';
$selectedClientId = request('c', request('clientid'));
$currentStep = (int) request('step', 1);

if($currentStep < 1 || $currentStep> 3) $currentStep = 1;

    $stepLabels = [
    'without_orders' => [1 => 'Client', 2 => 'Select Items', 3 => 'Review & Terms']
    ];

    $currentLabels = $stepLabels['without_orders'];
    $totalSteps = 3;
    @endphp

    <section class="position-relative {{ $currentStep !== 1 ? 'bg-white p-2' : '' }} rounded-3">


        <form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm" class="mainForm">
            @csrf
            <input type="hidden" name="current_step" value="{{ $currentStep }}">

            @if($currentStep == 1)
            @include('invoices.steps.step1-client')
            @elseif($currentStep == 2)
            @include('invoices.steps.step2-items')
            @elseif($currentStep == 3)
            @include('invoices.steps.step3-preview-terms')
            @endif
        </form>
    </section>
    @if($currentStep == 2)
        @include('orders.partials.edit-order-modal')
    @endif
    @endsection
