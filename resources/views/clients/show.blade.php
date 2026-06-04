@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">
        Back to Clients
    </a>
    <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'po']) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">
        Add PO
    </a>
    <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'agreement']) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">
        Add Agreement
    </a>
    <a href="{{ route('orders.create', ['c' => $client->clientid]) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold text-sm bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all no-underline cursor-pointer">
        Add Order
    </a>
    <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold text-sm bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all no-underline cursor-pointer">
        Edit
    </a>
    <form method="POST" action="{{ route('clients.destroy', $client) }}" class="inline-flex m-0" onsubmit="return confirm('Delete this client?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline">
            Delete
        </button>
    </form>
@endsection

@section('content')

<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex gap-4 items-center">
        @if($client->logo_path)
            <div class="w-20 h-20 rounded-xl border border-slate-200 overflow-hidden flex items-center justify-center bg-slate-50 shrink-0">
                <img src="{{ $client->logo_path }}" alt="Logo" class="object-contain w-full h-full">
            </div>
        @else
            <div class="w-20 h-20 rounded-xl bg-blue-100 text-blue-800 text-2xl font-bold flex items-center justify-center shrink-0">
                {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
            </div>
        @endif
        <div class="flex-1">
            @if($client->group_name)
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold m-0">{{ $client->group_name }}</p>
            @endif
            <h1 class="text-xl font-bold my-1">{{ $client->business_name }}</h1>
            <p class="text-slate-500">{{ $client->primary_email ?? $client->email }}</p>
            <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
                {{ strtolower($client->status ?? 'active') === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ strtolower($client->status ?? 'active') === 'inactive' ? 'bg-red-100 text-red-800' : '' }}
                {{ strtolower($client->status ?? 'active') === 'review' ? 'bg-amber-100 text-amber-800' : '' }}
                {{ strtolower($client->status ?? 'active') === 'trial' ? 'bg-amber-100 text-amber-800' : '' }}
                {{ !in_array(strtolower($client->status ?? 'active'), ['active', 'inactive', 'review', 'trial']) ? 'bg-slate-100 text-slate-600' : '' }} inline-block mt-1">{{ ucfirst($client->status ?? 'Active') }}</span>
        </div>
        <div class="text-right">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold m-0">Balance</p>
            <strong class="text-xl block mt-1">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding ?? 0) }}</strong>
        </div>
    </div>
</section>

<div class="grid grid-cols-2 gap-4 mt-4">
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
            <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-id-card"></i></div>
            <h4 class="text-sm font-semibold text-slate-800 m-0">Client Profile</h4>
        </div>
        <div class="grid grid-cols-[120px_1fr] gap-2 text-sm">
            <div class="text-slate-500">Business</div>
            <div class="font-medium">{{ $client->business_name ?? '-' }}</div>

            <div class="text-slate-500">Group</div>
            <div class="font-medium">{{ $client->group_name ?? '-' }}</div>

            <div class="text-slate-500">Status</div>
            <div class="font-medium">{{ ucfirst($client->status ?? 'active') }}</div>

            <div class="text-slate-500">Currency</div>
            <div class="font-medium">{{ $client->currency ?? 'INR' }}</div>

            <div class="text-slate-500">Created</div>
            <div class="font-medium">{{ $client->created_at?->format('d M Y') ?? '-' }}</div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
            <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-address-book"></i></div>
            <h4 class="text-sm font-semibold text-slate-800 m-0">Contact Information</h4>
        </div>
        <div class="grid grid-cols-[120px_1fr] gap-2 text-sm">
            <div class="text-slate-500">Contact</div>
            <div class="font-medium">{{ $client->contact_name ?? '-' }}</div>

            <div class="text-slate-500">Primary Email</div>
            <div class="font-medium">{{ $client->primary_email ?? '-' }}</div>

            <div class="text-slate-500">Secondary Emails</div>
            <div class="font-medium">{{ $client->email ?? '-' }}</div>

            <div class="text-slate-500">Phone</div>
            <div class="font-medium">{{ $client->phone ?? '-' }}</div>

            <div class="text-slate-500">WhatsApp</div>
            <div class="font-medium">{{ $client->whatsapp_number ?? '-' }}</div>

            <div class="text-slate-500">Address</div>
            <div class="font-medium">{{ $client->address_line_1 ?? '-' }}</div>

            <div class="text-slate-500">City/State</div>
            <div class="font-medium">{{ $client->city ?? '-' }}{{ $client->state ? ', ' . $client->state : '' }}</div>

            <div class="text-slate-500">Country</div>
            <div class="font-medium">{{ $client->country ?? '-' }}</div>

            <div class="text-slate-500">Postal Code</div>
            <div class="font-medium">{{ $client->postal_code ?? '-' }}</div>
        </div>
    </section>

</div>
<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mt-3 mb-3">
    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
        <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-file-invoice-dollar"></i></div>
        <h4 class="text-sm font-semibold text-slate-800 m-0">Billing Details</h4>
    </div>
    <div class="grid grid-cols-[120px_1fr_120px_1fr] gap-2 text-sm">
        <div class="text-slate-500">Business</div>
        <div class="font-medium">{{ $client->billingDetail->business_name ?? '-' }}</div>

        <div class="text-slate-500">GSTIN</div>
        <div class="font-medium">{{ $client->billingDetail->gstin ?? '-' }}</div>

        <div class="text-slate-500">Email</div>
        <div class="font-medium">{{ $client->billingDetail->billing_email ?? $client->billing_email ?? '-' }}</div>

        <div class="text-slate-500">Phone</div>
        <div class="font-medium">{{ $client->billingDetail->billing_phone ?? '-' }}</div>

        <div class="text-slate-500">Address</div>
        <div class="font-medium">{{ $client->billingDetail->address_line_1 ?? '-' }}</div>

        <div class="text-slate-500">City/State</div>
        <div class="font-medium">{{ $client->billingDetail->city ?? '-' }}{{ $client->billingDetail?->state ? ', ' . $client->billingDetail->state : '' }}</div>

        <div class="text-slate-500">Country</div>
        <div class="font-medium">{{ $client->billingDetail->country ?? '-' }}</div>

        <div class="text-slate-500">Postal Code</div>
        <div class="font-medium">{{ $client->billingDetail->postal_code ?? '-' }}</div>
    </div>
</section>

@if($client->documents->count())
<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mt-3 mb-3">
    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
        <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-folder-open"></i></div>
        <h4 class="text-sm font-semibold text-slate-800 m-0">Client Documents</h4>
    </div>
    <table class="w-full border-collapse mt-3">
        <thead>
            <tr class="border-b border-slate-200">
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Type</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Title</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Number</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Date</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">File</th>
            </tr>
        </thead>
        <tbody>
            @foreach($client->documents->sortByDesc('created_at') as $document)
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="py-2 px-3 text-sm">{{ strtoupper($document->type) }}</td>
                    <td class="py-2 px-3 text-sm">{{ $document->title ?: '—' }}</td>
                    <td class="py-2 px-3 text-sm">{{ $document->document_number ?: '—' }}</td>
                    <td class="py-2 px-3 text-sm">{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                    <td class="py-2 px-3 text-sm">
                        @if($document->file_path)
                            <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}" target="_blank" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100">View File</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif

@if(isset($allInvoices) && $allInvoices->count())
<section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 mt-2">
    <div class="flex items-center gap-2 mb-4 pb-2 border-b border-slate-200">
        <div class="w-7 h-7 rounded-md bg-slate-100 text-slate-500 flex items-center justify-center text-xs shrink-0"><i class="fas fa-file-invoice"></i></div>
        <h4 class="text-sm font-semibold text-slate-800 m-0">Invoices ({{ $allInvoices->count() }})</h4>
    </div>
    <table class="w-full border-collapse mt-3">
        <thead>
            <tr class="border-b border-slate-200">
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Invoice</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Total</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Status</th>
                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 py-2 px-3">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allInvoices->take(5) as $invoice)
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="py-2 px-3"><strong class="text-sm">{{ $invoice->invoice_number }}</strong></td>
                <td class="py-2 px-3 text-sm">{{ $client->currency ?? 'INR' }} {{ number_format($invoice->grand_total ?? 0) }}</td>
                <td class="py-2 px-3"><span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
                    {{ strtolower($invoice->status ?? 'draft') === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'draft' ? 'bg-slate-100 text-slate-600' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'sent' ? 'bg-indigo-100 text-indigo-800' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'partial' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ strtolower($invoice->status ?? 'draft') === 'cancelled' ? 'bg-slate-100 text-slate-500' : '' }}">{{ ucfirst($invoice->status ?? 'Draft') }}</span></td>
                <td class="py-2 px-3 text-sm">{{ $invoice->created_at?->format('d M Y') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif

@endsection
