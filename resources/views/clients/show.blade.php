@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Clients
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="primary-button small">
        <i class="fas fa-edit" class="icon-spaced-sm"></i>Edit
    </a>
    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline-delete" onsubmit="return confirm('Delete this client?')" class="inline-delete">
        @csrf
        @method('DELETE')
        <button type="submit" class="secondary-button">
            <i class="fas fa-trash" class="icon-spaced-sm"></i>Delete
        </button>
    </form>
@endsection

@section('content')

<section class="panel-card" class="panel-card-lg">
    <div class="flex-gap-lg items-center">
        @if($client->logo_path)
            <div class="logo-preview-large">
                <img src="{{ $client->logo_path }}" alt="Logo" class="img-contain">
            </div>
        @else
            <div class="avatar-large">
                {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
            </div>
        @endif
        <div class="flex-fill">
            @if($client->group_name)
                <p class="text-muted-uppercase">{{ $client->group_name }}</p>
            @endif
            <h1 class="heading-lg">{{ $client->business_name }}</h1>
            <p class="text-muted">{{ $client->email }}</p>
            <span class="status-pill {{ strtolower($client->status ?? 'active') }}" class="status-pill-inline">{{ ucfirst($client->status ?? 'Active') }}</span>
        </div>
        <div class="text-right">
            <p class="text-muted-uppercase">Balance</p>
            <strong class="heading-value">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding ?? 0) }}</strong>
        </div>
    </div>
</section>

<div class="grid-cols-2-lg">
    <section class="panel-card">
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-id-card"></i></div>
            <h4 class="section-title">Client Profile</h4>
        </div>
        <div class="info-grid-2col">
            <div class="info-label">Client ID</div>
            <div class="info-value">{{ $client->clientid ?? '-' }}</div>

            <div class="info-label">Account ID</div>
            <div>{{ $client->accountid ?? '-' }}</div>

            <div class="info-label">Business</div>
            <div>{{ $client->business_name ?? '-' }}</div>

            <div class="info-label">Group</div>
            <div>{{ $client->group_name ?? '-' }}</div>

            <div class="info-label">Status</div>
            <div>{{ ucfirst($client->status ?? 'active') }}</div>

            <div class="info-label">Currency</div>
            <div>{{ $client->currency ?? 'INR' }}</div>

            <div class="info-label">Created</div>
            <div>{{ $client->created_at?->format('d M Y') ?? '-' }}</div>
        </div>
    </section>

    <section class="panel-card">
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-address-book"></i></div>
            <h4 class="section-title">Contact Information</h4>
        </div>
        <div class="info-grid-2col">
            <div class="info-label">Contact</div>
            <div class="info-value">{{ $client->contact_name ?? '-' }}</div>

            <div class="info-label">Email</div>
            <div>{{ $client->email ?? '-' }}</div>

            <div class="info-label">Phone</div>
            <div>{{ $client->phone ?? '-' }}</div>

            <div class="info-label">WhatsApp</div>
            <div>{{ $client->whatsapp_number ?? '-' }}</div>

            <div class="info-label">Address</div>
            <div>{{ $client->address_line_1 ?? '-' }}</div>

            <div class="info-label">City/State</div>
            <div>{{ $client->city ?? '-' }}{{ $client->state ? ', ' . $client->state : '' }}</div>

            <div class="info-label">Country</div>
            <div>{{ $client->country ?? '-' }}</div>

            <div class="info-label">Postal Code</div>
            <div>{{ $client->postal_code ?? '-' }}</div>
        </div>
    </section>

</div>
<section class="panel-card mt-3 mb-3">
    <div class="section-header">
        <div class="section-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <h4 class="section-title">Billing Details</h4>
    </div>
    <div class="info-grid-4col">
        <div class="info-label">Business</div>
        <div class="info-value">{{ $client->billingDetail->business_name ?? '-' }}</div>

        <div class="info-label">GSTIN</div>
        <div>{{ $client->billingDetail->gstin ?? '-' }}</div>

        <div class="info-label">Email</div>
        <div>{{ $client->billingDetail->billing_email ?? $client->billing_email ?? '-' }}</div>

        <div class="info-label">Phone</div>
        <div>{{ $client->billingDetail->billing_phone ?? '-' }}</div>

        <div class="info-label">Address</div>
        <div>{{ $client->billingDetail->address_line_1 ?? '-' }}</div>

        <div class="info-label">City/State</div>
        <div>{{ $client->billingDetail->city ?? '-' }}{{ $client->billingDetail?->state ? ', ' . $client->billingDetail->state : '' }}</div>

        <div class="info-label">Country</div>
        <div>{{ $client->billingDetail->country ?? '-' }}</div>

        <div class="info-label">Postal Code</div>
        <div>{{ $client->billingDetail->postal_code ?? '-' }}</div>
    </div>
</section>

@if(isset($allInvoices) && $allInvoices->count())
<section class="panel-card" class="panel-card mt-2">
    <div class="section-header">
        <div class="section-icon"><i class="fas fa-file-invoice"></i></div>
        <h4 class="section-title">Invoices ({{ $allInvoices->count() }})</h4>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-sm">Invoice</th>
                <th class="text-sm">Total</th>
                <th class="text-sm">Status</th>
                <th class="text-sm">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allInvoices->take(5) as $invoice)
            <tr>
                <td class="small-text"><strong>{{ $invoice->invoice_number }}</strong></td>
                <td class="small-text">{{ $client->currency ?? 'INR' }} {{ number_format($invoice->grand_total ?? 0) }}</td>
                <td><span class="status-pill {{ strtolower($invoice->status ?? 'draft') }}">{{ ucfirst($invoice->status ?? 'Draft') }}</span></td>
                <td class="small-text">{{ $invoice->created_at?->format('d M Y') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif

@endsection
