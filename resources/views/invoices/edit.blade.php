@extends('layouts.app')

@section('header_actions')
    <div class="section-header-wrap">
        <!-- <div style="padding: 0.55rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 999px; background: #f8fafc; color: #0f172a; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.01em;">
            #{{ $invoice->invoice_number }}
        </div> -->
        <a href="{{ route('invoices.index', request('c') ? ['c' => request('c')] : []) }}" class="secondary-button">
            <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Invoices
        </a>
    </div>
@endsection

@section('content')
<section class="panel-card" style="padding: 1.5rem;">
    @include('invoices._edit_form', ['invoice' => $invoice, 'clients' => $clients, 'services' => $services, 'taxes' => $taxes, 'account' => $account, 'inline' => false])
</section>

@endsection
