@extends('layouts.app')

@section('content')
<section class="section-bar">
    <a href="{{ route('invoices.index') }}" class="text-link">&larr; Back to invoices</a>
</section>

<section class="panel-card" style="padding: 1.5rem;">
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; color: #334155; font-size: 0.9rem; font-weight: 600;">
        <i class="fas fa-edit" style="margin-right: 0.45rem; color: #4f46e5;"></i>
        Editing Invoice: {{ $invoice->invoice_number }}
    </div>
    @include('invoices._edit_form', ['invoice' => $invoice, 'clients' => $clients, 'services' => $services, 'taxes' => $taxes, 'account' => $account, 'inline' => false])
</section>

@if($invoice->isProforma() && !$invoice->convertedTaxInvoice)
<form method="POST" action="{{ route('invoices.convert-to-tax', $invoice) }}" id="convertToTaxInvoiceForm" onsubmit="return confirm('Are you sure you want to convert this proforma invoice to a tax invoice? A new tax invoice will be created.')">
    @csrf
</form>
@endif
@endsection
