@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div>
        <p class="eyebrow">Clients</p>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">{{ $client->business_name ?? $client->contact_name }}</h3>
        <a href="{{ route('clients.index') }}" class="text-link" style="font-size: 0.85rem;">&larr; Back to clients</a>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="{{ route('clients.edit', $client) }}" class="icon-action-btn edit" title="Edit" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="fas fa-edit"></i>
        </a>
        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline-delete" onsubmit="return confirm('Delete this client?')">
            @csrf @method('DELETE')
            <button type="submit" class="icon-action-btn delete" title="Delete" style="width: 36px; height: 36px; font-size: 1rem;">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    </div>
</section>

<section class="panel-card" style="padding: 1.25rem;">
    <div style="display: flex; gap: 1.5rem; align-items: center;">
        @if($client->logo_path)
            <div style="width: 64px; height: 64px; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: white; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <img src="{{ $client->logo_path }}" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        @else
            <div style="width: 64px; height: 64px; border-radius: 10px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0;">
                {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
            </div>
        @endif
        <div style="flex: 1;">
            @if($client->group_name)
                <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">{{ $client->group_name }}</p>
            @endif
            <h1 style="margin: 0.25rem 0 0.25rem 0; font-size: 1.3rem; font-weight: 700;">{{ $client->business_name }}</h1>
            <p style="margin: 0; font-size: 0.85rem; color: #64748b;">{{ $client->email }}</p>
            <span class="status-pill {{ strtolower($client->status ?? 'active') }}" style="margin-top: 0.25rem; display: inline-block;">{{ ucfirst($client->status ?? 'Active') }}</span>
        </div>
        <div style="text-align: right;">
            <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Balance</p>
            <strong style="font-size: 1.3rem; display: block; margin-top: 0.25rem;">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding ?? 0) }}</strong>
        </div>
    </div>
</section>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
    <section class="panel-card" style="padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-id-card"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Client Profile</h4>
        </div>
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; font-size: 0.85rem;">
            <div style="color: #64748b;">Client ID</div>
            <div style="font-weight: 500;">{{ $client->clientid ?? '-' }}</div>

            <div style="color: #64748b;">Account ID</div>
            <div>{{ $client->accountid ?? '-' }}</div>

            <div style="color: #64748b;">Business</div>
            <div>{{ $client->business_name ?? '-' }}</div>

            <div style="color: #64748b;">Group</div>
            <div>{{ $client->group_name ?? '-' }}</div>

            <div style="color: #64748b;">Status</div>
            <div>{{ ucfirst($client->status ?? 'active') }}</div>

            <div style="color: #64748b;">Currency</div>
            <div>{{ $client->currency ?? 'INR' }}</div>

            <div style="color: #64748b;">Created</div>
            <div>{{ $client->created_at?->format('d M Y') ?? '-' }}</div>
        </div>
    </section>

    <section class="panel-card" style="padding: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-address-book"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Contact Information</h4>
        </div>
        <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; font-size: 0.85rem;">
            <div style="color: #64748b;">Contact</div>
            <div style="font-weight: 500;">{{ $client->contact_name ?? '-' }}</div>

            <div style="color: #64748b;">Email</div>
            <div>{{ $client->email ?? '-' }}</div>

            <div style="color: #64748b;">Phone</div>
            <div>{{ $client->phone ?? '-' }}</div>

            <div style="color: #64748b;">WhatsApp</div>
            <div>{{ $client->whatsapp_number ?? '-' }}</div>

            <div style="color: #64748b;">Address</div>
            <div>{{ $client->address_line_1 ?? '-' }}</div>

            <div style="color: #64748b;">City/State</div>
            <div>{{ $client->city ?? '-' }}{{ $client->state ? ', ' . $client->state : '' }}</div>

            <div style="color: #64748b;">Country</div>
            <div>{{ $client->country ?? '-' }}</div>

            <div style="color: #64748b;">Postal Code</div>
            <div>{{ $client->postal_code ?? '-' }}</div>
        </div>
    </section>

    <section class="panel-card" style="padding: 1rem; grid-column: 1 / -1;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Billing Details</h4>
        </div>
        <div style="display: grid; grid-template-columns: 120px 1fr 120px 1fr; gap: 0.5rem; font-size: 0.85rem;">
            <div style="color: #64748b;">Business</div>
            <div style="font-weight: 500;">{{ $client->billingDetail->business_name ?? '-' }}</div>

            <div style="color: #64748b;">GSTIN</div>
            <div>{{ $client->billingDetail->gstin ?? '-' }}</div>

            <div style="color: #64748b;">Email</div>
            <div>{{ $client->billingDetail->billing_email ?? $client->billing_email ?? '-' }}</div>

            <div style="color: #64748b;">Phone</div>
            <div>{{ $client->billingDetail->phone ?? '-' }}</div>

            <div style="color: #64748b;">Address</div>
            <div>{{ $client->billingDetail->address_line_1 ?? '-' }}</div>

            <div style="color: #64748b;">City/State</div>
            <div>{{ $client->billingDetail->city ?? '-' }}{{ $client->billingDetail?->state ? ', ' . $client->billingDetail->state : '' }}</div>

            <div style="color: #64748b;">Country</div>
            <div>{{ $client->billingDetail->country ?? '-' }}</div>

            <div style="color: #64748b;">Postal Code</div>
            <div>{{ $client->billingDetail->postal_code ?? '-' }}</div>
        </div>
    </section>
</div>

@if(isset($allInvoices) && $allInvoices->count())
<section class="panel-card" style="margin-top: 1rem; padding: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
        <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-file-invoice"></i></div>
        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Invoices ({{ $allInvoices->count() }})</h4>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="font-size: 0.8rem;">Invoice</th>
                <th style="font-size: 0.8rem;">Total</th>
                <th style="font-size: 0.8rem;">Status</th>
                <th style="font-size: 0.8rem;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allInvoices->take(5) as $invoice)
            <tr>
                <td style="font-size: 0.85rem;"><strong>{{ $invoice->invoice_number }}</strong></td>
                <td style="font-size: 0.85rem;">{{ $client->currency ?? 'INR' }} {{ number_format($invoice->grand_total ?? 0) }}</td>
                <td><span class="status-pill {{ strtolower($invoice->status ?? 'draft') }}">{{ ucfirst($invoice->status ?? 'Draft') }}</span></td>
                <td style="font-size: 0.85rem;">{{ $invoice->created_at?->format('d M Y') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif

@endsection
