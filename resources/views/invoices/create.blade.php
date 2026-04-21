@extends('layouts.app')

@section('content')
@php
    $selectedClientId = request('clientid', request('c'));
    $currentStep = (int) request('step', 1);
    $invoiceFor = request('invoice_for', session('invoice_for', ''));
    
    // Fix: Default empty invoiceFor to 'orders' when clientid present
    if(empty($invoiceFor) && $selectedClientId) {
        $invoiceFor = 'orders';
    }
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
        <a href="{{ $selectedClientId ? route('orders.index', ['c' => $selectedClientId]) : route('invoices.index') }}" class="secondary-button">
            <i class="fas fa-arrow-left" style="margin-right: 0.4rem;"></i>Back
        </a>
    @endif
@endsection

<section class="panel-card invoice-create-shell" style="padding: 0.95rem;">
    @if ($errors->any())
        <div style="margin-bottom: 0.9rem; padding: 0.7rem 0.85rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
            <strong style="display: block; margin-bottom: 0.4rem;">
                @if($errors->has('general')) Error: {{ $errors->first('general') }}
                @else Fix these issues before creating the invoice: @endif
            </strong>
            @unless($errors->has('general'))
            <ul style="margin: 0; padding-left: 1rem;">
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

<style>
.invoice-create-shell {
    max-width: 100%;
}
.invoice-create-header {
    gap: 1rem;
}
#invoiceForm .form-input,
#invoiceForm input[type="text"],
#invoiceForm input[type="number"],
#invoiceForm input[type="date"],
#invoiceForm select,
#invoiceForm textarea {
    width: 100%;
    min-width: 0;
    padding: 0.55rem 0.7rem;
    font-size: 0.84rem;
}
.invoice-meta-card { padding: 0.85rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.28rem; font-size: 0.68rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
.invoice-meta-value { color: #1e293b; font-size: 0.92rem; }
.field-label { display: block; margin-bottom: 0.32rem; font-size: 0.78rem; font-weight: 600; color: #374151; }
.invoice-grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.75rem; align-items: end; }
.invoice-span-2 { grid-column: span 2; }
.invoice-span-3 { grid-column: span 3; }
.source-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; }
.invoice-source-card { position: relative; display: flex; flex-direction: column; gap: 0.45rem; padding: 0.8rem 0.9rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; cursor: pointer; transition: 0.2s ease; }
.invoice-source-card:hover { border-color: #c7d2fe; background: #fafbff; }
.invoice-source-card input { position: absolute; opacity: 0; pointer-events: none; }
.invoice-source-card:has(input:checked) { border-color: #4f46e5; background: #f8faff; }
.source-icon { width: 34px; height: 34px; border-radius: 10px; background: #eef2ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; }
.invoice-source-card strong { color: #1e293b; }
.workflow-panel { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; }
.panel-heading-row { margin-bottom: 0.5rem; }
.table-shell { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #ffffff; }
.empty-state { padding: 1rem; text-align: center; color: #6b7280; font-size: 0.84rem; }
.builder-card { padding: 0.7rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; }
.manual-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.55rem; align-items: end; }
.totals-card { padding: 0.85rem; border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.45rem; font-size: 0.84rem; color: #4b5563; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.55rem; border-top: 1px solid #d1d5db; font-size: 0.95rem; font-weight: 700; color: #111827; }
.status-pill.paid { background: #dcfce7; color: #166534; }
.status-pill.unpaid { background: #fee2e2; color: #991b1b; }
.status-pill.partially-paid { background: #fef3c7; color: #92400e; }
.invoice-step-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
.invoice-step-badge { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.35rem 0.65rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #f9fafb; color: #374151; font-size: 0.72rem; font-weight: 600; }
.invoice-step-badge i { color: #4f46e5; }
.invoice-side-meta { text-align: right; }
.section-note { margin: 0.2rem 0 0; color: #6b7280; font-size: 0.8rem; }
.section-title-card { margin-bottom: 0.75rem; padding: 0.75rem 0.85rem; border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 12px; }
.section-title-card h4 { margin: 0; font-size: 0.9rem; color: #111827; }
.section-title-card p { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.78rem; }
@media (max-width: 1200px) {
    .source-grid,
    .manual-grid,
    .invoice-grid-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .invoice-span-3 {
        grid-column: span 2;
    }
}
@media (max-width: 720px) {
    .invoice-create-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .invoice-step-toolbar {
        flex-direction: column;
        align-items: flex-start;
    }
    .invoice-side-meta {
        text-align: left;
    }
    .source-grid,
    .manual-grid,
    .invoice-grid-4 {
        grid-template-columns: 1fr;
    }
    .invoice-span-2,
    .invoice-span-3 {
        grid-column: span 1;
    }
}
</style>
@endsection
