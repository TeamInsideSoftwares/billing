@extends('layouts.app')

@section('header_actions')
@if(collect($clients)->isNotEmpty())
<div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="text-muted small d-none d-md-inline"></span>
    <select class="form-select border-0 shadow-sm client-dashboard-picker"
        onchange="if(this.value) window.location.href='{{ url('/client-dashboard') }}/' + this.value;">
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
<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
    @if(!isset($client))
    @if(collect($clients)->isNotEmpty())
    {{-- Choose Client State --}}
    <div
        class="search-landing-container py-5 d-flex justify-content-center align-items-center client-dashboard-empty-shell">
        <div class="card p-4 border shadow-sm client-dashboard-empty-card">
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted fw-bold"></span>
                <select class="form-select border shadow-sm client-dashboard-picker client-dashboard-picker--large"
                    onchange="if(this.value) window.location.href='{{ url('/client-dashboard') }}/' + this.value;">
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
    <div class="search-landing-container py-5 text-center">
        <div class="mx-auto client-dashboard-empty-message">
            <div class="dashboard-welcome-icon mb-4">
                <i class="fas fa-users-slash text-muted client-dashboard-empty-icon"></i>
            </div>
            <h1 class="fw-bolder text-dark mb-2">No Clients Found</h1>
            <p class="text-muted mb-4 fs-5">You haven't added any clients to your billing account yet. Add a client
                first to view the dashboard.</p>
            <a href="{{ route('clients.create') }}"
                class="btn btn-primary text-white d-inline-flex align-items-center gap-2 py-2 px-4">
                <i class="fas fa-plus"></i> Add Client
            </a>
        </div>
    </div>
    @endif

    @else
    {{-- Selected Client Dashboard State --}}

    {{-- Profile Header Card --}}
    {{-- Client Header --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">

        <div class="row align-items-center g-3">

            {{-- Logo / Avatar --}}
            <div class="col-auto">

                @if($client->logo_path)

                    <div class="border rounded-circle overflow-hidden bg-white d-flex align-items-center justify-content-center"
                         style="width:80px;height:80px;">
                        <img src="{{ $client->logo_path }}"
                             alt="{{ $client->business_name }}"
                             class="w-100 h-100 object-fit-contain">
                    </div>

                @else

                    <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center"
                         style="width:80px;height:80px;font-size:1.5rem;">
                        {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
                    </div>

                @endif

            </div>

            {{-- Client Info --}}
            <div class="col">

                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">

                    <h3 class="fw-bold mb-0">
                        {{ $client->business_name }}
                    </h3>

                    <span class="badge bg-success">
                        {{ ucfirst($client->status ?? 'Active') }}
                    </span>

                    @if($client->type === 'trial')
                        <span class="badge bg-warning text-dark">
                            Trial
                        </span>
                    @endif

                </div>

                <p class="text-muted mb-2">
                    <i class="fas fa-envelope me-1"></i>
                    {{ $client->primary_email ?? $client->email }}

                    <span class="mx-2">|</span>

                    <i class="fas fa-phone me-1"></i>
                    {{ $client->phone ?? 'No Phone' }}
                </p>

                <div class="d-flex flex-wrap gap-3 text-muted small">

                    <span>
                        <i class="fas fa-map-marker-alt me-1"></i>
                        {{ $client->city ?? '-' }}
                        {{ $client->state ? ', '.$client->state : '' }}
                    </span>

                    <span>
                        <i class="fas fa-calendar-alt me-1"></i>
                        Joined:
                        {{ $client->created_at?->format('d M Y') ?? '-' }}
                    </span>

                </div>

            </div>

            {{-- Actions --}}
            <div class="col-12 col-xl-auto">

                <div class="d-flex flex-wrap gap-2 justify-content-xl-end">

                    <a href="{{ route('orders.create', ['c' => $client->clientid]) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-shopping-cart me-1"></i>
                        Add Order
                    </a>

                    <a href="{{ route('quotations.create', ['c' => $client->clientid]) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-file-alt me-1"></i>
                        Add Quotation
                    </a>

                    <a href="{{ route('invoices.create', ['clientid' => $client->clientid]) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-file-invoice-dollar me-1"></i>
                        Add Invoice
                    </a>

                    <a href="{{ route('payments.create', ['clientid' => $client->clientid]) }}"
                       class="btn btn-outline-primary">
                        <i class="fas fa-wallet me-1"></i>
                        Add Payment
                    </a>

                    <a href="{{ route('clients.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="fas fa-file-pdf me-1"></i>
                        Add PO
                    </a>

                    <a href="{{ route('clients.edit', $client) }}"
                       class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>
                        Edit Profile
                    </a>

                </div>

            </div>

        </div>

    </div>
</div>

{{-- Financial Metrics --}}
<div class="row g-3 mb-4">

    {{-- Outstanding --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">

                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

                <div>
                    <div class="text-muted small text-uppercase fw-semibold">
                        Outstanding
                    </div>
                    <h5 class="mb-0 fw-bold text-danger">
                        {{ $client->currency ?? 'INR' }}
                        {{ number_format($outstanding, 0) }}
                    </h5>
                </div>

            </div>
        </div>
    </div>

    {{-- Total Invoiced --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">

                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;">
                    <i class="fas fa-file-invoice"></i>
                </div>

                <div>
                    <div class="text-muted small text-uppercase fw-semibold">
                        Total Invoiced
                    </div>
                    <h5 class="mb-0 fw-bold">
                        {{ $client->currency ?? 'INR' }}
                        {{ number_format($invoicedTotal, 0) }}
                    </h5>
                </div>

            </div>
        </div>
    </div>

    {{-- Total Paid --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">

                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;">
                    <i class="fas fa-check-circle"></i>
                </div>

                <div>
                    <div class="text-muted small text-uppercase fw-semibold">
                        Total Paid
                    </div>
                    <h5 class="mb-0 fw-bold text-success">
                        {{ $client->currency ?? 'INR' }}
                        {{ number_format($paidTotal, 0) }}
                    </h5>
                </div>

            </div>
        </div>
    </div>

    {{-- Active Orders --}}
    <div class="col-6 col-lg-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">

                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3"
                     style="width:50px;height:50px;">
                    <i class="fas fa-clock"></i>
                </div>

                <div>
                    <div class="text-muted small text-uppercase fw-semibold">
                        Active Orders
                    </div>
                    <h5 class="mb-0 fw-bold text-warning">
                        {{ $activeOrdersCount }}
                    </h5>
                </div>

            </div>
        </div>
    </div>

</div>

    {{-- Tabs Content Area --}}
    <div class="card shadow-sm p-4 border-0 mb-4">
        <div class="row g-4">
            {{-- Vertical Tabs Header Navigation --}}
            <div class="col-12 col-md-2 border-end pe-md-3">
                <div class="nav flex-column nav-pills text-start dashboard-tabs-stack" id="dashboardTabs"
                    role="tablist">
                    <button class="nav-link active text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"
                        aria-controls="overview" aria-selected="true">
                        <i class="fas fa-user-circle dashboard-tab-icon"></i>
                        <span>Profile & Contacts</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab"
                        aria-controls="orders" aria-selected="false">
                        <i class="fas fa-shopping-cart dashboard-tab-icon"></i>
                        <span>Orders ({{ $orders->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab"
                        aria-controls="invoices" aria-selected="false">
                        <i class="fas fa-file-invoice-dollar dashboard-tab-icon"></i>
                        <span>Invoices ({{ $invoices->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="quotations-tab" data-bs-toggle="tab" data-bs-target="#quotations" type="button" role="tab"
                        aria-controls="quotations" aria-selected="false">
                        <i class="fas fa-file-alt dashboard-tab-icon"></i>
                        <span>Quotations ({{ $quotations->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab"
                        aria-controls="payments" aria-selected="false">
                        <i class="fas fa-wallet dashboard-tab-icon"></i>
                        <span>Payments ({{ $payments->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button" role="tab"
                        aria-controls="ledger" aria-selected="false">
                        <i class="fas fa-receipt dashboard-tab-icon"></i>
                        <span>Ledger ({{ $ledger->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab"
                        aria-controls="documents" aria-selected="false">
                        <i class="fas fa-folder dashboard-tab-icon"></i>
                        <span>Documents ({{ $documents->count() }})</span>
                    </button>
                    <button class="nav-link text-start py-2 px-3 border-0 d-flex align-items-center gap-2"
                        id="comms-tab" data-bs-toggle="tab" data-bs-target="#comms" type="button" role="tab"
                        aria-controls="comms" aria-selected="false">
                        <i class="fas fa-history dashboard-tab-icon"></i>
                        <span>Email History ({{ $communicationLogs->count() }})</span>
                    </button>
                </div>
            </div>

            {{-- Tab Content Panel --}}
            <div class="col-12 col-md-10 ps-md-3">
                <div class="tab-content" id="dashboardTabsContent">
                    {{-- 1. Profile & Contacts Tab --}}
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="row g-4">
                            <div class="col-md-6 text-start">
                                <div class="card border border-light rounded-3 p-3 h-100">
                                    <h5 class="fw-bold text-dark mb-3"><i
                                            class="fas fa-address-book text-muted-light me-1"></i> Contact Details</h5>
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Contact Person</div>
                                            <div class="col-8">{{ $client->contact_name ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Primary Email</div>
                                            <div class="col-8 text-truncate">{{ $client->primary_email ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Secondary Email</div>
                                            <div class="col-8">{{ $client->email ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Phone Number</div>
                                            <div class="col-8">{{ $client->phone ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">WhatsApp</div>
                                            <div class="col-8">{{ $client->whatsapp_number ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Currency</div>
                                            <div class="col-8"><span class="badge bg-secondary-light text-secondary">{{
                                                    $client->currency }}</span></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Tax Number / GST</div>
                                            <div class="col-8">{{ $client->tax_number ?: 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-start">
                                <div class="card border border-light rounded-3 p-3 h-100">
                                    <h5 class="fw-bold text-dark mb-3"><i
                                            class="fas fa-file-invoice text-muted-light me-1"></i> Billing Details</h5>
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Business Name</div>
                                            <div class="col-8">{{ $client->billingDetail->business_name ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">GSTIN</div>
                                            <div class="col-8"><span class="font-monospace text-primary fw-semibold">{{
                                                    $client->billingDetail->gstin ?? '—' }}</span></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Billing Email</div>
                                            <div class="col-8">{{ $client->billingDetail->billing_email ??
                                                $client->billing_email ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Billing Phone</div>
                                            <div class="col-8">{{ $client->billingDetail->billing_phone ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-semibold text-secondary">Address</div>
                                            <div class="col-8">
                                                {{ $client->billingDetail->address_line_1 ?? '-' }}<br>
                                                {{ $client->billingDetail->city ?? '' }}{{
                                                $client->billingDetail?->state ? ', ' . $client->billingDetail->state :
                                                '' }} {{ $client->billingDetail->postal_code ?? '' }}<br>
                                                {{ $client->billingDetail->country ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!empty($client->notes))
                            <div class="col-12 text-start mt-3">
                                <div class="card border border-light rounded-3 p-3 bg-light-soft">
                                    <h5 class="fw-bold text-dark mb-2"><i
                                            class="fas fa-sticky-note text-muted-light me-1"></i> Notes & Special
                                        Instructions</h5>
                                    <p class="mb-0 text-secondary pre-wrap">{{ $client->notes }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Orders Tab --}}
                    <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                            <div class="fw-semibold text-dark">{{ $order->item_name }}</div>
                                            @if($order->item_description)
                                            <small class="text-muted">{{ Str::limit($order->item_description, 60)
                                                }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>
                                            <div class="small">
                                                <i class="far fa-calendar-alt text-muted-light"></i>
                                                {{ $order->start_date?->format('d M Y') ?? 'N/A' }} to {{
                                                $order->end_date?->format('d M Y') ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-pill {{ strtolower($order->status ?? 'active') }}">{{
                                                ucfirst($order->status ?? 'Active') }}</span>
                                        </td>
                                        <td>
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('orders.edit', $order->orderid) }}"
                                                    class="bg03 color03">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No orders found for this
                                            client.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Invoices Tab --}}
                    <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                        <td>{{ $invoice->issue_date?->format('d M Y') ??
                                            $invoice->created_at?->format('d M Y') }}</td>
                                        <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                                        <td><strong>{{ $client->currency }} {{ number_format($invoice->grand_total, 2)
                                                }}</strong></td>
                                        <td>
                                            <span class="status-pill {{ strtolower($invoice->status ?? 'draft') }}">{{
                                                ucfirst($invoice->status ?? 'Draft') }}</span>
                                        </td>
                                        <td>
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('invoices.show', $invoice->invoiceid) }}"
                                                    class="bg01 color01">View</a>
                                                <a href="{{ route('invoices.edit', $invoice->invoiceid) }}"
                                                    class="bg03 color03">Edit</a>
                                                <a href="{{ route('invoices.pdf', $invoice->invoiceid) }}"
                                                    target="_blank" class="bg02 color02"><i class="fas fa-file-pdf"></i>
                                                    PDF</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No invoices found for this
                                            client.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 4. Quotations Tab --}}
                    <div class="tab-pane fade" id="quotations" role="tabpanel" aria-labelledby="quotations-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                        <td><strong>{{ $client->currency }} {{ number_format($quotation->grand_total, 2)
                                                }}</strong></td>
                                        <td>
                                            <span class="status-pill {{ strtolower($quotation->status ?? 'draft') }}">{{
                                                ucfirst($quotation->status ?? 'Draft') }}</span>
                                        </td>
                                        <td>
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('quotations.show', $quotation->quotationid) }}"
                                                    class="bg01 color01">View</a>
                                                <a href="{{ route('quotations.edit', $quotation->quotationid) }}"
                                                    class="bg03 color03">Edit</a>
                                                <a href="{{ route('quotations.pdf', $quotation->quotationid) }}"
                                                    target="_blank" class="bg02 color02"><i class="fas fa-file-pdf"></i>
                                                    PDF</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No quotations found for this
                                            client.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 5. Payments Tab --}}
                    <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                        <td>{{ $payment->payment_date?->format('d M Y') ??
                                            $payment->created_at?->format('d M Y') }}</td>
                                        <td><strong class="text-success">{{ $client->currency }} {{
                                                number_format($payment->received_amount, 2) }}</strong></td>
                                        <td>{{ $payment->tds_amount ? $client->currency . ' ' .
                                            number_format($payment->tds_amount, 2) : '—' }}</td>
                                        <td><span class="badge bg-light text-dark text-capitalize border">{{
                                                $payment->mode ?: '—' }}</span></td>
                                        <td><span class="font-monospace text-muted">{{ $payment->reference_number ?: '—'
                                                }}</span></td>
                                        <td>
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('payments.show', $payment->paymentid) }}"
                                                    class="bg01 color01">View Details</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">No payments found for this
                                            client.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 6. Ledger Tab --}}
                    <div class="tab-pane fade" id="ledger" role="tabpanel" aria-labelledby="ledger-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference ID</th>
                                        <th>Type</th>
                                        <th>Mode</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $runningBalance = 0;
                                    @endphp
                                    @forelse($ledger as $ledgerItem)
                                    <tr>
                                        <td>{{ $ledgerItem->date?->format('d M Y') ??
                                            $ledgerItem->created_at?->format('d M Y') }}</td>
                                        <td>
                                            <span class="font-monospace fw-semibold">{{ $ledgerItem->invoiceid_paymentid
                                                ?: '—' }}</span>
                                        </td>
                                        <td>
                                            @if($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice')
                                            <span
                                                class="badge bg-danger-light text-danger text-uppercase client-ledger-badge">Debit</span>
                                            @else
                                            <span
                                                class="badge bg-success-light text-success text-uppercase client-ledger-badge">Credit</span>
                                            @endif
                                        </td>
                                        <td><span class="text-capitalize small">{{ $ledgerItem->mode ?: '—' }}</span>
                                        </td>
                                        <td class="small">{{ $ledgerItem->description ?: '—' }}</td>
                                        <td
                                            class="text-end fw-bold {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ? 'text-danger' : 'text-success' }}">
                                            {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ? '-'
                                            : '+' }} {{ number_format($ledgerItem->amount, 2) }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No ledger transactions found
                                            for this client.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 7. Documents Tab --}}
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                        <td><span class="badge bg-light text-dark fw-bold text-uppercase border">{{
                                                $document->type }}</span></td>
                                        <td>{{ $document->title ?: '—' }}</td>
                                        <td><span class="font-monospace">{{ $document->document_number ?: '—' }}</span>
                                        </td>
                                        <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            <span class="status-pill {{ strtolower($document->status ?? 'active') }}">{{
                                                ucfirst($document->status ?? 'Active') }}</span>
                                        </td>
                                        <td>
                                            @if($document->file_path)
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                    target="_blank" class="bg01 color01">
                                                    <i class="fas fa-file-download me-1"></i> View File
                                                </a>
                                            </div>
                                            @else
                                            —
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No agreements or PO
                                            documents uploaded.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 8. Communication Log Tab --}}
                    <div class="tab-pane fade" id="comms" role="tabpanel" aria-labelledby="comms-tab">
                        <div class="table-responsive text-start">
                            <table class="table mainTable border align-middle mb-0">
                                <thead class="table-light">
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
                                            <span class="badge text-capitalize text-dark bg-light border">
                                                <i
                                                    class="fas {{ $log->channel === 'email' ? 'fa-envelope text-primary' : 'fa-mobile-alt text-success' }} me-1"></i>
                                                {{ $log->channel ?: 'Email' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark d-block">{{ $log->subject }}</span>
                                            @if($log->body)
                                            <small class="text-muted d-block text-truncate-2 client-log-preview">{{
                                                strip_tags($log->body) }}</small>
                                            @endif
                                        </td>
                                        <td class="small">{{ $log->to_email ?: $log->phone_number ?: '—' }}</td>
                                        <td>
                                            <span
                                                class="badge {{ $log->status === 'sent' || $log->status === 'success' ? 'bg-success-light text-success' : 'bg-warning-light text-warning' }} text-uppercase client-log-status-badge">
                                                {{ $log->status ?: 'Sent' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No emails or alerts logged
                                            for this client.</td>
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
    document.addEventListener('DOMContentLoaded', function () {
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
