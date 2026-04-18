@extends('layouts.app')

@section('content')
@php
    $currentStep = (int) request('step', 1);
    $invoiceFor = request('invoice_for', session('invoice_for', ''));
    
    // Fix: Default empty invoiceFor to 'orders' when clientid present
    if(empty($invoiceFor) && request('clientid')) {
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

<section class="panel-card invoice-create-shell" style="padding: 1.25rem;">
    <div class="invoice-create-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
        @if($currentStep === 1)
        <a href="{{ $clientId ? route('orders.index', ['c' => $clientId]) : route('invoices.index') }}" class="text-link">&larr; Back</a>
        @endif
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
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
    padding: 0.7rem 0.85rem;
    font-size: 0.87rem;
}
.invoice-meta-card { padding: 1rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.82rem; font-weight: 600; color: #374151; }
.invoice-grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; align-items: end; }
.invoice-span-2 { grid-column: span 2; }
.invoice-span-3 { grid-column: span 3; }
.source-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
.invoice-source-card { position: relative; display: flex; flex-direction: column; gap: 0.55rem; padding: 1rem 1.1rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; cursor: pointer; transition: 0.2s ease; }
.invoice-source-card:hover { border-color: #c7d2fe; background: #fafbff; }
.invoice-source-card input { position: absolute; opacity: 0; pointer-events: none; }
.invoice-source-card:has(input:checked) { border-color: #4f46e5; background: #f8faff; }
.source-icon { width: 40px; height: 40px; border-radius: 10px; background: #eef2ff; color: #4f46e5; display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem; }
.invoice-source-card strong { color: #1e293b; }
.workflow-panel { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.panel-heading-row { margin-bottom: 0.65rem; }
.table-shell { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; background: #ffffff; }
.empty-state { padding: 1.4rem; text-align: center; color: #6b7280; font-size: 0.88rem; }
.builder-card { padding: 0.85rem; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; }
.manual-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.7rem; align-items: end; }
.totals-card { padding: 1rem; border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #4b5563; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #d1d5db; font-size: 1rem; font-weight: 700; color: #111827; }
.status-pill.paid { background: #dcfce7; color: #166534; }
.status-pill.unpaid { background: #fee2e2; color: #991b1b; }
.status-pill.partially-paid { background: #fef3c7; color: #92400e; }
.invoice-step-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
.invoice-step-badge { display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.45rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 999px; background: #f9fafb; color: #374151; font-size: 0.76rem; font-weight: 600; }
.invoice-step-badge i { color: #4f46e5; }
.invoice-side-meta { text-align: right; }
.section-note { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.84rem; }
.section-title-card { margin-bottom: 0.95rem; padding: 0.9rem 1rem; border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 12px; }
.section-title-card h4 { margin: 0; font-size: 0.95rem; color: #111827; }
.section-title-card p { margin: 0.3rem 0 0; color: #6b7280; font-size: 0.82rem; }
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
