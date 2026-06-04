@extends('layouts.app')

@section('header_actions')
    @if(collect($clients)->isNotEmpty())
        <div class="flex items-center gap-2">
            <span class="text-slate-500 text-sm hidden md:inline"></span>
            <select class="bg-white border border-slate-200 rounded-lg px-3 py-1.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[220px] font-medium h-[38px]" onchange="if(this.value) window.location.href='{{ url('/client-dashboard') }}/' + this.value;">
                @if(!isset($client))
                    <option value="" selected disabled>-- Choose Client --</option>
                @else
                    <option value="" disabled>-- Choose Client --</option>
                @endif
                @foreach($clients as $c)
                    <option value="{{ $c->clientid }}" {{ isset($client) && $client->clientid === $c->clientid ? 'selected' : '' }}>
                        {{ $c->business_name ?? $c->contact_name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif
@endsection

@section('content')
<div class="w-full">
    @if(!isset($client))
        @if(collect($clients)->isNotEmpty())
            {{-- Choose Client State --}}
            <div class="search-landing-container py-12 flex justify-center items-center min-h-[50vh]">
                <div class="bg-white p-6 border border-slate-200 rounded-xl shadow-sm max-w-md w-full">
                    <div class="flex flex-col items-center gap-4">
                        <span class="text-slate-500 font-bold text-center">Select a client to view their dashboard</span>
                        <select class="w-full bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-base shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 min-w-[220px] font-medium h-[42px] text-base" onchange="if(this.value) window.location.href='{{ url('/client-dashboard') }}/' + this.value;">
                            <option value="" selected disabled>-- Choose Client --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->clientid }}">
                                    {{ $c->business_name ?? $c->contact_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @else
            {{-- Empty State (No clients found) --}}
            <div class="search-landing-container py-12 text-center">
                <div class="mx-auto max-w-md">
                    <div class="mb-4">
                        <i class="fas fa-users-slash text-slate-400 text-5xl"></i>
                    </div>
                    <h1 class="font-extrabold text-slate-800 text-2xl mb-2">No Clients Found</h1>
                    <p class="text-slate-500 mb-6 text-sm leading-relaxed">You haven't added any clients to your billing account yet. Add a client first to view the dashboard.</p>
                    <a href="{{ route('clients.create') }}" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold inline-flex items-center gap-2 shadow-sm transition-colors no-underline">
                        <i class="fas fa-plus"></i> Add Client
                    </a>
                </div>
            </div>
        @endif

    @else
        {{-- Selected Client Dashboard State --}}

        {{-- Profile Header Card --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center gap-6 justify-between">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="flex-none">
                        @if($client->logo_path)
                            <div class="w-16 h-16 rounded-full border border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50">
                                <img src="{{ $client->logo_path }}" alt="Logo" class="object-contain w-full h-full">
                            </div>
                        @else
                            <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-800 text-xl font-bold flex items-center justify-center">
                                {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div class="text-left">
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <h3 class="font-semibold text-slate-900 text-xl">{{ $client->business_name }}</h3>
                            @php
                                $statusClasses = [
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-red-100 text-red-800',
                                    'trial' => 'bg-amber-100 text-amber-800',
                                ];
                                $clientStatus = strtolower($client->status ?? 'active');
                                $badgeClass = $statusClasses[$clientStatus] ?? 'bg-slate-100 text-slate-800';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ ucfirst($client->status ?? 'Active') }}</span>
                            @if($client->type === 'trial')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Trial</span>
                            @endif
                        </div>
                        <p class="text-slate-500 text-sm mb-2"><i class="fas fa-envelope mr-1"></i> {{ $client->primary_email ?? $client->email }} | <i class="fas fa-phone mr-1"></i> {{ $client->phone ?? 'No Phone' }}</p>
                        <div class="flex flex-wrap items-center gap-3 text-slate-400 text-xs">
                            <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt"></i> {{ $client->city ?? '-' }}{{ $client->state ? ', ' . $client->state : '' }}</span>
                            <span class="hidden sm:inline">•</span>
                            <span class="flex items-center gap-1"><i class="fas fa-calendar-alt"></i> Joined: {{ $client->created_at?->format('d M Y') ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 justify-start lg:justify-end">
                    <a href="{{ route('orders.create', ['c' => $client->clientid]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-shopping-cart text-slate-400"></i> Add Order
                    </a>
                    <a href="{{ route('quotations.create', ['c' => $client->clientid]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-file-alt text-slate-400"></i> Add Quotation
                    </a>
                    <a href="{{ route('invoices.create', ['clientid' => $client->clientid]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-file-invoice-dollar text-slate-400"></i> Add Invoice
                    </a>
                    <a href="{{ route('payments.create', ['clientid' => $client->clientid]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-wallet text-slate-400"></i> Add Payment
                    </a>
                    <a href="{{ route('clients.documents.create', ['client' => $client->clientid, 'type' => 'po']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-300 hover:border-slate-400 bg-transparent text-slate-700 hover:bg-slate-50 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-file-pdf text-slate-400"></i> Add PO
                    </a>
                    <a href="{{ route('clients.edit', $client) }}" class="inline-flex items-center gap-1 px-3 py-1.5 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-full text-xs font-medium shadow-sm transition-colors">
                        <i class="fas fa-edit text-slate-400"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>

        {{-- Financial Metrics Bar --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 shadow-sm text-left">
                <div class="flex-none w-10 h-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-lg">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-0.5">Outstanding</p>
                    <h4 class="font-extrabold text-lg text-red-600 mb-0">{{ $client->currency ?? 'INR' }} {{ number_format($outstanding, 0) }}</h4>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 shadow-sm text-left">
                <div class="flex-none w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-0.5">Total Invoiced</p>
                    <h4 class="font-extrabold text-lg text-slate-800 mb-0">{{ $client->currency ?? 'INR' }} {{ number_format($invoicedTotal, 0) }}</h4>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 shadow-sm text-left">
                <div class="flex-none w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-lg">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-0.5">Total Paid</p>
                    <h4 class="font-extrabold text-lg text-green-600 mb-0">{{ $client->currency ?? 'INR' }} {{ number_format($paidTotal, 0) }}</h4>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-4 shadow-sm text-left">
                <div class="flex-none w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider mb-0.5">Active Orders</p>
                    <h4 class="font-extrabold text-lg text-amber-600 mb-0">{{ $activeOrdersCount }}</h4>
                </div>
            </div>
        </div>

        {{-- Tabs Content Area --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
            <div class="flex flex-col md:flex-row gap-6">
                {{-- Vertical Tabs Header Navigation --}}
                <div class="w-full md:w-1/5 md:border-r border-slate-200 md:pr-6">
                    <div class="flex flex-col gap-1 text-left" id="dashboardTabs" role="tablist">
                        <button class="nav-link active text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-blue-600 text-white" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile & Contacts</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders ({{ $orders->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="false">
                            <i class="fas fa-file-invoice-dollar"></i>
                            <span>Invoices ({{ $invoices->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="quotations-tab" data-bs-toggle="tab" data-bs-target="#quotations" type="button" role="tab" aria-controls="quotations" aria-selected="false">
                            <i class="fas fa-file-alt"></i>
                            <span>Quotations ({{ $quotations->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                            <i class="fas fa-wallet"></i>
                            <span>Payments ({{ $payments->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab" aria-controls="ledger" aria-selected="false">
                            <i class="fas fa-receipt"></i>
                            <span>Ledger ({{ $ledger->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                            <i class="fas fa-folder"></i>
                            <span>Documents ({{ $documents->count() }})</span>
                        </button>
                        <button class="nav-link text-left py-2 px-3.5 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors bg-slate-100 text-slate-600 hover:bg-slate-200" id="comms-tab" data-bs-toggle="tab" data-bs-target="#comms" type="button" role="tab" aria-controls="comms" aria-selected="false">
                            <i class="fas fa-history"></i>
                            <span>Email History ({{ $communicationLogs->count() }})</span>
                        </button>
                    </div>
                </div>

                {{-- Tab Content Panel --}}
                <div class="flex-1 md:pl-6 text-left">
                    <div class="tab-content" id="dashboardTabsContent">
                        {{-- 1. Profile & Contacts Tab --}}
                        <div class="tab-pane active show" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-sm text-left">
                                    <h5 class="font-bold text-slate-800 mb-4 text-base"><i class="fas fa-address-book text-slate-400 mr-1.5"></i> Contact Details</h5>
                                    <div class="flex flex-col gap-3 text-left">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Contact Person</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->contact_name ?: '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Primary Email</div>
                                            <div class="col-span-2 text-slate-850 text-sm truncate">{{ $client->primary_email ?: '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Secondary Email</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->email ?: '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Phone Number</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->phone ?: '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">WhatsApp</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->whatsapp_number ?: '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Currency</div>
                                            <div class="col-span-2 text-slate-850 text-sm">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600">{{ $client->currency }}</span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Tax Number / GST</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->tax_number ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-sm text-left">
                                    <h5 class="font-bold text-slate-800 mb-4 text-base"><i class="fas fa-file-invoice text-slate-400 mr-1.5"></i> Billing Details</h5>
                                    <div class="flex flex-col gap-3 text-left">
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Business Name</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->billingDetail->business_name ?? '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">GSTIN</div>
                                            <div class="col-span-2 text-slate-850 text-sm">
                                                <span class="font-mono text-blue-600 font-semibold text-sm">{{ $client->billingDetail->gstin ?? '—' }}</span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Billing Email</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->billingDetail->billing_email ?? $client->billing_email ?? '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Billing Phone</div>
                                            <div class="col-span-2 text-slate-850 text-sm">{{ $client->billingDetail->billing_phone ?? '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2">
                                            <div class="col-span-1 font-semibold text-slate-500 text-sm">Address</div>
                                            <div class="col-span-2 text-slate-850 text-sm leading-relaxed">
                                                {{ $client->billingDetail->address_line_1 ?? '-' }}<br>
                                                {{ $client->billingDetail->city ?? '' }}{{ $client->billingDetail?->state ? ', ' . $client->billingDetail->state : '' }} {{ $client->billingDetail->postal_code ?? '' }}<br>
                                                {{ $client->billingDetail->country ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if(!empty($client->notes))
                                    <div class="md:col-span-2 text-left">
                                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-5">
                                            <h5 class="font-bold text-slate-800 mb-2 text-base"><i class="fas fa-sticky-note text-slate-400 mr-1.5"></i> Notes & Special Instructions</h5>
                                            <p class="mb-0 text-slate-650 text-sm whitespace-pre-wrap leading-relaxed">{{ $client->notes }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- 2. Orders Tab --}}
                        <div class="tab-pane hidden" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Item Details</th>
                                            <th>Qty</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                            <tr>
                                                <td><strong>{{ $order->order_number }}</strong></td>
                                                <td>
                                                    <div class="font-semibold text-slate-800">{{ $order->item_name }}</div>
                                                    @if($order->item_description)
                                                        <small class="text-slate-500">{{ Str::limit($order->item_description, 60) }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $order->quantity }}</td>
                                                <td>
                                                    <div class="text-sm">
                                                        <i class="far fa-calendar-alt text-slate-400"></i>
                                                        {{ $order->start_date?->format('d M Y') ?? 'N/A' }} to {{ $order->end_date?->format('d M Y') ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
{{ strtolower($order->status ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : '' }}
{{ strtolower($order->status ?? 'active') === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
{{ strtolower($order->status ?? 'active') === 'completed' ? 'bg-green-100 text-green-800' : '' }}
{{ strtolower($order->status ?? 'active') === 'cancelled' ? 'bg-slate-100 text-slate-500' : '' }}
{{ strtolower($order->status ?? 'active') === 'paused' ? 'bg-amber-100 text-amber-800' : '' }}
{{ !in_array(strtolower($order->status ?? 'active'), ['active', 'pending', 'completed', 'cancelled', 'paused']) ? 'bg-blue-100 text-blue-800' : '' }}">{{ ucfirst($order->status ?? 'Active') }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('orders.edit', $order->orderid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100 mr-2">Edit</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-slate-500">No orders found for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 3. Invoices Tab --}}
                        <div class="tab-pane hidden" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Grand Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoices as $invoice)
                                            <tr>
                                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                                <td>{{ $invoice->issue_date?->format('d M Y') ?? $invoice->created_at?->format('d M Y') }}</td>
                                                <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                                                <td><strong>{{ $client->currency }} {{ number_format($invoice->grand_total, 2) }}</strong></td>
                                                <td>
                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
{{ strtolower($invoice->status ?? 'draft') === 'paid' ? 'bg-green-100 text-green-800' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'draft' ? 'bg-slate-100 text-slate-600' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'sent' ? 'bg-indigo-100 text-indigo-800' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'overdue' ? 'bg-red-100 text-red-800' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'partial' ? 'bg-blue-100 text-blue-800' : '' }}
{{ strtolower($invoice->status ?? 'draft') === 'cancelled' ? 'bg-slate-100 text-slate-500' : '' }}">{{ ucfirst($invoice->status ?? 'Draft') }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('invoices.show', $invoice->invoiceid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 mr-2">View</a>
                                                    <a href="{{ route('invoices.edit', $invoice->invoiceid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100 mr-2">Edit</a>
                                                    <a href="{{ route('invoices.pdf', $invoice->invoiceid) }}" target="_blank" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100"><i class="fas fa-file-pdf"></i> PDF</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-slate-500">No invoices found for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 4. Quotations Tab --}}
                        <div class="tab-pane hidden" id="quotations" role="tabpanel" aria-labelledby="quotations-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Quotation #</th>
                                            <th>Title</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Grand Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($quotations as $quotation)
                                            <tr>
                                                <td><strong>{{ $quotation->quotation_number }}</strong></td>
                                                <td>{{ $quotation->quo_title ?: '—' }}</td>
                                                <td>{{ $quotation->issue_date?->format('d M Y') ?? '—' }}</td>
                                                <td>{{ $quotation->due_date?->format('d M Y') ?? '—' }}</td>
                                                <td><strong>{{ $client->currency }} {{ number_format($quotation->grand_total, 2) }}</strong></td>
                                                <td>
                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
{{ strtolower($quotation->status ?? 'draft') === 'approved' ? 'bg-green-100 text-green-800' : '' }}
{{ strtolower($quotation->status ?? 'draft') === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
{{ strtolower($quotation->status ?? 'draft') === 'draft' ? 'bg-slate-100 text-slate-600' : '' }}
{{ strtolower($quotation->status ?? 'draft') === 'sent' ? 'bg-indigo-100 text-indigo-800' : '' }}
{{ strtolower($quotation->status ?? 'draft') === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
{{ strtolower($quotation->status ?? 'draft') === 'cancelled' ? 'bg-slate-100 text-slate-500' : '' }}">{{ ucfirst($quotation->status ?? 'Draft') }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('quotations.show', $quotation->quotationid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 mr-2">View</a>
                                                    <a href="{{ route('quotations.edit', $quotation->quotationid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-amber-50 text-amber-700 hover:bg-amber-100 mr-2">Edit</a>
                                                    <a href="{{ route('quotations.pdf', $quotation->quotationid) }}" target="_blank" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100"><i class="fas fa-file-pdf"></i> PDF</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-slate-500">No quotations found for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 5. Payments Tab --}}
                        <div class="tab-pane hidden" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>TDS Amount</th>
                                            <th>Mode</th>
                                            <th>Reference #</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payments as $payment)
                                            <tr>
                                                <td><strong>{{ $payment->paymentid }}</strong></td>
                                                <td>{{ $payment->payment_date?->format('d M Y') ?? $payment->created_at?->format('d M Y') }}</td>
                                                <td><strong class="text-green-600">{{ $client->currency }} {{ number_format($payment->received_amount, 2) }}</strong></td>
                                                <td>{{ $payment->tds_amount ? $client->currency . ' ' . number_format($payment->tds_amount, 2) : '—' }}</td>
                                                <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 capitalize">{{ $payment->mode ?: '—' }}</span></td>
                                                <td><span class="font-mono text-slate-500">{{ $payment->reference_number ?: '—' }}</span></td>
                                                <td>
                                                    <a href="{{ route('payments.show', $payment->paymentid) }}" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100 mr-2">View Details</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-slate-500">No payments found for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 6. Ledger Tab --}}
                        <div class="tab-pane hidden" id="ledger" role="tabpanel" aria-labelledby="ledger-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference ID</th>
                                            <th>Type</th>
                                            <th>Mode</th>
                                            <th>Description</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $runningBalance = 0;
                                        @endphp
                                        @forelse($ledger as $ledgerItem)
                                            <tr>
                                                <td>{{ $ledgerItem->date?->format('d M Y') ?? $ledgerItem->created_at?->format('d M Y') }}</td>
                                                <td>
                                                    <span class="font-mono font-semibold">{{ $ledgerItem->invoiceid_paymentid ?: '—' }}</span>
                                                </td>
                                                <td>
                                                    @if($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-red-700 bg-red-50 uppercase">Debit</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-green-700 bg-green-50 uppercase">Credit</span>
                                                    @endif
                                                </td>
                                                <td><span class="capitalize small-text">{{ $ledgerItem->mode ?: '—' }}</span></td>
                                                <td class="text-sm">{{ $ledgerItem->description ?: '—' }}</td>
                                                <td class="text-end font-bold {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ? 'text-danger' : 'text-success' }}">
                                                    {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ? '-' : '+' }} {{ number_format($ledgerItem->amount, 2) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-slate-500">No ledger transactions found for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 7. Documents Tab --}}
                        <div class="tab-pane hidden" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Title</th>
                                            <th>Document Number</th>
                                            <th>Document Date</th>
                                            <th>Status</th>
                                            <th>File Link</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($documents as $document)
                                            <tr>
                                                <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold text-slate-700 bg-slate-100 border border-slate-200 uppercase">{{ $document->type }}</span></td>
                                                <td>{{ $document->title ?: '—' }}</td>
                                                <td><span class="font-mono">{{ $document->document_number ?: '—' }}</span></td>
                                                <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                                <td>
                                                    <span class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
{{ strtolower($document->status ?? 'active') === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
{{ strtolower($document->status ?? 'active') === 'inactive' ? 'bg-slate-100 text-slate-500' : '' }}
{{ strtolower($document->status ?? 'active') === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
{{ !in_array(strtolower($document->status ?? 'active'), ['active', 'inactive', 'cancelled']) ? 'bg-slate-100 text-slate-600' : '' }}">{{ ucfirst($document->status ?? 'Active') }}</span>
                                                </td>
                                                <td>
                                                    @if($document->file_path)
                                                        <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}" target="_blank" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100">
                                                            <i class="fas fa-file-download mr-1"></i> View File
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-slate-500">No agreements or PO documents uploaded.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 8. Communication Log Tab --}}
                        <div class="tab-pane hidden" id="comms" role="tabpanel" aria-labelledby="comms-tab">
                            <div class="overflow-x-auto text-left">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Sent Date</th>
                                            <th>Channel</th>
                                            <th>Subject</th>
                                            <th>Recipient</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($communicationLogs as $log)
                                            <tr>
                                                <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                                <td>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 capitalize">
                                                        <i class="fas {{ $log->channel === 'email' ? 'fa-envelope text-primary' : 'fa-mobile-alt text-success' }} mr-1"></i>
                                                        {{ $log->channel ?: 'Email' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="font-semibold text-slate-800 block">{{ $log->subject }}</span>
                                                    @if($log->body)
                                                        <small class="text-slate-500 block text-truncate-2 max-w-[450px]">{{ strip_tags($log->body) }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-sm">{{ $log->to_email ?: $log->phone_number ?: '—' }}</td>
                                                <td>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $log->status === 'sent' || $log->status === 'success' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                                        {{ $log->status ?: 'Sent' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-slate-500">No emails or alerts logged for this client.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Maintain active tab state on reload if needed
        const hash = window.location.hash;
        if (hash) {
            const triggerEl = document.querySelector(`#dashboardTabs button[data-bs-target="${hash}"]`);
            if (triggerEl && typeof bootstrap !== 'undefined') {
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }
        }

        // Update URL hash when switching tabs
        const tabButtons = document.querySelectorAll('#dashboardTabs button');
        tabButtons.forEach(button => {
            button.addEventListener('shown.bs.tab', function (event) {
                const target = event.target.getAttribute('data-bs-target');
                window.location.hash = target;
            });
        });
    });
</script>
@endpush
