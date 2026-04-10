@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">Edit Invoice</h3>
    </div>
    <a href="{{ route('invoices.index') }}" class="text-link">&larr; Back to invoices</a>
</section>

<section class="panel-card" style="padding: 1.5rem;">
    @include('invoices._edit_form', ['invoice' => $invoice, 'clients' => $clients, 'services' => $services, 'taxes' => $taxes, 'account' => $account, 'inline' => false])
</section>

@if($invoice->isProforma() && !$invoice->convertedTaxInvoice)
<form method="POST" action="{{ route('invoices.convert-to-tax', $invoice) }}" id="convertToTaxInvoiceForm" onsubmit="return confirm('Are you sure you want to convert this proforma invoice to a tax invoice? A new tax invoice will be created.')">
    @csrf
</form>
@endif
@endsection
