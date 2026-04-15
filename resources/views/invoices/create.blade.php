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

<section class="panel-card" style="padding: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1px solid #e5e7eb;">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">
                <i class="fas fa-file-invoice" style="color: #f59e0b; margin-right: 0.5rem;"></i> Create Proforma Invoice
            </h3>
            <p style="margin: 0.25rem 0 0; font-size: 0.8rem; color: #64748b;">
                <i class="fas fa-info-circle" style="margin-right: 0.3rem;"></i> {{ $currentLabels[$currentStep] ?? '' }}
            </p>
        </div>
        <a href="{{ $clientId ? route('orders.index', ['c' => $clientId]) : route('invoices.index') }}" class="text-link">&larr; Back</a>
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
.invoice-meta-card { padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.85rem; font-weight: 600; color: #475569; }
.source-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.invoice-source-card { position: relative; display: flex; flex-direction: column; gap: 0.45rem; padding: 1rem 1.1rem; border: 1px solid #dbe4ee; border-radius: 14px; background: #ffffff; cursor: pointer; transition: 0.2s ease; }
.invoice-source-card:hover { border-color: #93c5fd; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); }
.invoice-source-card input { position: absolute; opacity: 0; pointer-events: none; }
.invoice-source-card:has(input:checked) { border-color: #2563eb; background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); box-shadow: 0 12px 32px rgba(37, 99, 235, 0.12); }
.source-icon { width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; }
.invoice-source-card strong { color: #1e293b; }
.workflow-panel { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
.panel-heading-row { margin-bottom: 0.8rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.empty-state { padding: 1.4rem; text-align: center; color: #64748b; font-size: 0.88rem; }
.builder-card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.manual-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem; align-items: end; }
.totals-card { padding: 1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #cbd5e1; font-size: 1rem; font-weight: 700; color: #1e293b; }
.status-pill.paid { background: #dcfce7; color: #166534; }
.status-pill.unpaid { background: #fee2e2; color: #991b1b; }
.status-pill.partially-paid { background: #fef3c7; color: #92400e; }
@media (max-width: 1100px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>
@endsection
