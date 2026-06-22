@extends('layouts.app')

@php
if (isset($client)) {
$title = 'Client Dashboard';
}
@endphp

@section('header_actions')
@if(isset($client) && collect($clients)->isNotEmpty())
<div class="d-flex align-items-center gap-2 flex-wrap">

    <select class="form-select border-0 shadow-sm client-dashboard-picker"
        onchange="if(this.value) window.location.href='{{ url('/client-dashboard') }}/' + this.value;">
        <option value="" disabled>-- Choose Client --</option>
        @foreach($clients as $c)
        <option value="{{ $c->clientid }}" {{ $client->clientid === $c->clientid ? 'selected' : '' }}>
            {{ $c->business_name ?? $c->contact_name }}
        </option>
        @endforeach
    </select>
</div>
@endif
@endsection

@section('content')
<div class="position-relative {{ isset($client) ? 'bg-white p-2' : '' }} rounded-3">
    @if(!isset($client))
    @if(collect($clients)->isNotEmpty())
    {{-- Choose Client State --}}
    <div id="step1" class="position-relative d-flex align-items-center justify-content-center"
        style="min-height: calc(100vh - 160px);">
        <div class="row w-100">
            <div class="col-12 col-md-3 mx-auto">
                <div class="bg-white p-4 rounded-3 mx-auto mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
                        <div class="min-w-0">
                            <h5 class="fw-semibold text-black mb-0">Client Dashboard</h5>
                            <p class="text-dark mb-0">Choose a client to view their profile dashboard</p>
                        </div>
                    </div>

                    <div class="row g-2 mainForm">
                        <div class="col-12">
                            <label for="dashboardClientId"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Clients ({{
                                collect($clients)->count() }}) <span class="text-danger">*</span></label>
                            <select id="dashboardClientId" name="clientid" required class="form-select">
                                <option value="">Choose client</option>
                                @foreach($clients as $c)
                                <option value="{{ $c->clientid }}">
                                    {{ $c->business_name ?? $c->contact_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                        <button type="button" id="btnViewDashboard"
                            class="btn btn-outline-primary btn-primary text-white fw-medium">
                            View Dashboard <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const clientSelect = document.getElementById('dashboardClientId');
            const btnView = document.getElementById('btnViewDashboard');

            btnView.addEventListener('click', function () {
                const selectedClientId = clientSelect.value;
                if (!selectedClientId) {
                    alert('Please select a client first.');
                    clientSelect.focus();
                    return;
                }

                window.location.href = "{{ url('/client-dashboard') }}/" + encodeURIComponent(selectedClientId);
            });
        })();
    </script>
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
    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-2">
        <div class="card-body bg-white rounded-3 p-3">

            <div class="row align-items-center g-0">

                {{-- Logo / Avatar --}}
                <div class="col-auto">
                    <div class="position-relative" style="width:80px; height:80px;">
                        @if($client->logo_path)
                        <div
                            class="border rounded-circle overflow-hidden bg-white d-flex align-items-center justify-content-center w-100 h-100">
                            <img src="{{ $client->logo_path }}" alt="{{ $client->business_name }}"
                                class="w-100 h-100 object-fit-cover">
                        </div>
                        @else
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center w-100 h-100"
                            style="font-size:1.5rem;">
                            {{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}
                        </div>
                        @endif
                        <div class="status-dot {{ strtolower($client->status ?? 'active') }}"
                            title="{{ ucfirst($client->status ?? 'Active') }}"
                            style="width: 16px; height: 16px; border-width: 3px; top: 4px; right: 4px;"></div>
                    </div>
                </div>

                {{-- Client Info --}}
                <div class="col ms-3">

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1 ">

                        <h4 class="fw-bold mb-0">
                            {{ $client->business_name }}
                        </h4>

                        @if($client->type === 'trial')
                        <span class="badge bg-warning text-dark">
                            Trial
                        </span>
                        @endif

                    </div>

                    <p class="text-black mb-1">
                        <i class="fas fa-envelope text-muted small lh-sm"></i>
                        {{ $client->primary_email ?? $client->email }}

                        <span class="mx-2 text-muted">|</span>

                        <i class="fas fa-phone text-muted small lh-sm"></i>
                        {{ $client->phone ?? 'No Phone' }}
                    </p>

                    <div class="d-flex flex-wrap gap-0 text-black ">

                        <span>
                            <i class="fas fa-map-marker-alt text-muted small lh-sm"></i>
                            {{ $client->city ?? '-' }}
                            {{ $client->state ? ', '.$client->state : '' }}
                        </span>
                        <span class="mx-2 text-muted">|</span>
                        <span>
                            <i class="fas fa-calendar-alt text-muted small lh-sm"></i>
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
                            Add Order
                        </a>

                        <a href="{{ route('quotations.create', ['c' => $client->clientid, 'step' => 2]) }}"
                            class="btn btn-outline-primary">

                            Add Quotation
                        </a>

                        <a href="{{ route('invoices.create', ['c' => $client->clientid, 'step' => 2]) }}"
                            class="btn btn-outline-primary">

                            Add Invoice
                        </a>

                        <a href="{{ route('payments.create', ['clientid' => $client->clientid]) }}"
                            class="btn btn-outline-primary">

                            Add Payment
                        </a>

                        <a href="#" class="btn btn-outline-primary open-documents-modal" data-bs-toggle="modal" data-bs-target="#documentsModal" data-client-id="{{ $client->clientid }}" data-client-name="{{ $client->business_name ?? $client->contact_name }}">Add PO</a>

                        <a href="{{ route('clients.edit', $client) }}" class="btn btn-primary ">
                            Edit Profile <i class="fas fa-arrow-right btn-icon ms-1"></i>
                        </a>

                    </div>

                </div>

            </div>

        </div>
    </div>



    {{-- Tabs Content Area --}}


    <div class="row g-2">
        <div class="col-12 col-md-10">
            <div class="card overflow-hidden border-0 bg-DarkLight rounded-3 h-100">
                <div class="card-body bg-transparent rounded-3 p-2">
                    <!-- Category Tabs Slider -->
                    <div class="tabs-slider-container position-relative d-flex align-items-center mb-3">
                        <!-- Left Navigation Arrow -->
                        <button type="button"
                            class="btn btn-sm btn-outline-primary tab-nav-btn tab-nav-prev d-none me-2">
                            <i class="fas fa-chevron-left"></i>
                        </button>

                        <!-- Tabs Scrollable Container -->
                        <div class="tabs-scroll-container flex-grow-1">
                            <div class="btn-group d-flex flex-row flex-nowrap bg-light" id="dashboardTabs"
                                role="tablist" style="width: max-content;">
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2 active"
                                    id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button"
                                    role="tab" aria-controls="overview" aria-selected="true">
                                    <i class="far fa-user-circle dashboard-tab-icon"></i>
                                    <span>Profile & Contacts</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button"
                                    role="tab" aria-controls="orders" aria-selected="false">
                                    <i class="far fa-clipboard dashboard-tab-icon"></i>
                                    <span>Orders</span>
                                    <span class="badge rounded-pill">{{ $orders->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button"
                                    role="tab" aria-controls="invoices" aria-selected="false">
                                    <i class="far fa-file-alt dashboard-tab-icon"></i>
                                    <span>Invoices</span>
                                    <span class="badge rounded-pill">{{ $invoices->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="quotations-tab" data-bs-toggle="tab" data-bs-target="#quotations" type="button"
                                    role="tab" aria-controls="quotations" aria-selected="false">
                                    <i class="far fa-file dashboard-tab-icon"></i>
                                    <span>Quotations</span>
                                    <span class="badge rounded-pill">{{ $quotations->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button"
                                    role="tab" aria-controls="payments" aria-selected="false">
                                    <i class="far fa-credit-card dashboard-tab-icon"></i>
                                    <span>Payments</span>
                                    <span class="badge rounded-pill">{{ $payments->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger" type="button"
                                    role="tab" aria-controls="ledger" aria-selected="false">
                                    <i class="far fa-list-alt dashboard-tab-icon"></i>
                                    <span>Ledger</span>
                                    <span class="badge rounded-pill">{{ $ledger->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button"
                                    role="tab" aria-controls="documents" aria-selected="false">
                                    <i class="far fa-folder dashboard-tab-icon"></i>
                                    <span>Documents</span>
                                    <span class="badge rounded-pill">{{ $documents->count() }}</span>
                                </button>
                                <button
                                    class="btn btn-md px-3 category-tab-btn flex-shrink-0 d-inline-flex align-items-center gap-2"
                                    id="comms-tab" data-bs-toggle="tab" data-bs-target="#comms" type="button" role="tab"
                                    aria-controls="comms" aria-selected="false">
                                    <i class="far fa-envelope dashboard-tab-icon"></i>
                                    <span>Email History</span>
                                    <span class="badge rounded-pill">{{ $communicationLogs->count() }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Right Navigation Arrow -->
                        <button type="button"
                            class="btn btn-sm btn-outline-primary tab-nav-btn tab-nav-next d-none ms-2">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Tab Content Panel -->
                    <div class="tab-content" id="dashboardTabsContent">
                        {{-- 1. Profile & Contacts Tab --}}
                        <div class="tab-pane fade show active" id="overview" role="tabpanel"
                            aria-labelledby="overview-tab">
                            <div class="row g-2">
                                <div class="col-12 col-md-4 text-start">
                                    <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                        <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3">Business
                                            Details</h5>
                                        <div class="d-flex flex-column gap-2 text-start">
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Business
                                                    Name
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->business_name ?:
                                                    '-' }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Primary
                                                    Email</div>
                                                <div
                                                    class="col-8 fw-semibold text-dark fs-6 lh-sm text-truncate my-auto">
                                                    {{
                                                    $client->primary_email ?: '-' }}
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Secondary
                                                    Email
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->email ?:
                                                    '-' }}
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Phone Number
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->phone ?:
                                                    '-' }}
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">WhatsApp
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->whatsapp_number ?:
                                                    '-' }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Currency
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto"><span
                                                        class="badge bg-secondary-light text-secondary">{{
                                                        $client->currency }}</span></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Tax Number /
                                                    GST
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->tax_number ?: 'N/A'
                                                    }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Address
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">
                                                    @if($client->address_line_1 || $client->city || $client->state ||
                                                    $client->postal_code || $client->country)
                                                    {{ $client->address_line_1 }}@if($client->address_line_2)<br>{{
                                                    $client->address_line_2 }}@endif<br>
                                                    {{ $client->city }}{{ $client->state ? ', ' . $client->state : '' }}
                                                    {{ $client->postal_code }}<br>
                                                    {{ $client->country }}
                                                    @else
                                                    -
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 text-start">
                                    <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                        <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3"> Billing
                                            Details
                                        </h5>
                                        <div class="d-flex flex-column gap-2 text-start">
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Business
                                                    Name</div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->billingDetail->business_name ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">GSTIN</div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto"><span
                                                        class="text-dark fw-semibold">{{
                                                        $client->billingDetail->gstin ?? '—' }}</span></div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Billing
                                                    Email</div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->billingDetail->billing_email ??
                                                    $client->billing_email ?? '-' }}</div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Billing
                                                    Phone</div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $client->billingDetail->billing_phone ?? '-' }}
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Address
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">
                                                    @if($client->billingDetail?->address_line_1 ||
                                                    $client->billingDetail?->city || $client->billingDetail?->state ||
                                                    $client->billingDetail?->postal_code ||
                                                    $client->billingDetail?->country)
                                                    {{ $client->billingDetail->address_line_1 ?? '-'
                                                    }}@if($client->billingDetail->address_line_2)<br>{{
                                                    $client->billingDetail->address_line_2 }}@endif<br>
                                                    {{ $client->billingDetail->city ?? '' }}{{
                                                    $client->billingDetail?->state ? ', ' .
                                                    $client->billingDetail->state :
                                                    '' }} {{ $client->billingDetail->postal_code ?? '' }}<br>
                                                    {{ $client->billingDetail->country ?? '' }}
                                                    @else
                                                    -
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Contact Persons Card --}}
                                <div class="col-12 col-md-4 text-start">
                                    <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                        <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3"> Contact
                                            Persons
                                        </h5>
                                        @forelse($client->contacts->sortByDesc('is_primary') as $loop_index => $contact)
                                        @if(!$loop->first)
                                        <hr class="my-3">
                                        @endif
                                        <div class="d-flex flex-column gap-2 text-start">
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Name</div>
                                                <div
                                                    class="col-8 fw-semibold text-dark fs-6 lh-sm d-flex align-items-center gap-2 my-auto">
                                                    {{ $contact->name }}
                                                    @if($contact->is_primary)
                                                    <span class="text-white bg-success px-2 py-1 rounded-pill lh-sm"
                                                        style="font-size: 0.6rem;">Primary</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($contact->designation)
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Designation
                                                </div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $contact->designation }}
                                                </div>
                                            </div>
                                            @endif
                                            @if($contact->email)
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Email</div>
                                                <div
                                                    class="col-8 fw-semibold text-dark fs-6 lh-sm text-truncate my-auto">
                                                    {{
                                                    $contact->email }}</div>
                                            </div>
                                            @endif
                                            @if($contact->phone)
                                            <div class="row">
                                                <div class="col-4 fw-normal text-muted small lh-sm my-auto">Phone</div>
                                                <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{
                                                    $contact->phone
                                                    }}</div>
                                            </div>
                                            @endif
                                        </div>
                                        @empty
                                        <p class="text-muted mb-0">No contacts found for this client.</p>
                                        @endforelse
                                    </div>
                                </div>

                                @if(!empty($client->notes))
                                <div class="col-12 text-start mt-3">
                                    <div class="card border-0 bg-light-soft rounded-3 p-3">
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
                            <div class="table-responsive p-2 border-0 bg-DarkLight rounded-3 text-start">
                                <table class="table table-striped border mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="6%">Order</th>
                                            <th width="40%">Item Details</th>
                                            <th class="text-center" width="15%">Create Date</th>
                                            <th class="text-center" width="15%">Expiry</th>
                                            <th class="text-end" width="20%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td class="fw-semibold text-dark">#{{ $order->order_number }}</td>
                                            <td>
                                                <div class="d-flex align-items-center flex-wrap gap-1">
                                                    <span class="fw-bold text-dark">{{ $order->item_name ?? 'Item'
                                                        }}</span>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        @if($order->status === 'cancelled')
                                                        <span
                                                            class="status-pill rounded-pill border border-danger text-danger bg-light is-cancelled py-0 px-2 small"
                                                            style="font-size: 11px;">Cancelled</span>
                                                        @endif
                                                    </div>
                                                    @if(!empty($order->item_description))
                                                    <button type="button"
                                                        class="btn p-0 border-0 bg-transparent btn-desc-toggle d-inline-flex align-items-center"
                                                        style="outline: none; box-shadow: none;">
                                                        <i class="fas fa-arrow-up text-primary ms-2 desc-toggle-icon"
                                                            style="transition: transform 0.2s ease; font-size: 0.8rem;"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                @if(!empty($order->item_description))
                                                <div class="text-dark mt-1 d-none desc-container">{{
                                                    $order->item_description }}</div>
                                                @endif
                                                <div class="d-flex flex-wrap text-black mt-2">
                                                    <div
                                                        class="border-end border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                                        <small>Qty:</small>
                                                        <span class="fw-semibold">{{
                                                            rtrim(rtrim(number_format((float) ($order->quantity
                                                            ?? 1), 2, '.',
                                                            ''), '0'), '.') }}</span>
                                                    </div>
                                                    @if(!empty($order->no_of_users))
                                                    <div
                                                        class="border-end border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                                        <small>Users:</small>
                                                        <span class="fw-semibold">{{ $order->no_of_users }}</span>
                                                    </div>
                                                    @endif
                                                    @if(!empty($order->delivery_date))
                                                    <div
                                                        class="border-end border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                                        <small>Delivery Date:</small> <span class="fw-semibold">{{
                                                            $order->delivery_date->format('d M Y')
                                                            }}</span>
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">{{ !empty($order->start_date) ?
                                                $order->start_date->format('d M Y') : '-' }}</td>
                                            <td class="text-center">
                                                @php
                                                $orderEndDate = $order->end_date ? $order->end_date->format('Y-m-d') :
                                                null;

                                                $showDays = $orderEndDate
                                                && !in_array($orderEndDate, ['9999-12-31', '2099-12-31']);

                                                $daysLeft = null;

                                                if ($showDays) {
                                                $daysLeft = now()->startOfDay()->diffInDays(
                                                $order->end_date->startOfDay(),
                                                false
                                                );
                                                }
                                                @endphp

                                                @if(in_array($orderEndDate, ['9999-12-31', '2099-12-31']))
                                                No Expiry
                                                @else
                                                {{ $order->end_date ? $order->end_date->format('d M Y') : '-' }}
                                                @endif

                                                @if($showDays)
                                                <br>
                                                @if($daysLeft >= 0)
                                                <small class="text-success fw-semibold">
                                                    {{ $daysLeft }} day(s)
                                                </small>
                                                @else
                                                <small class="text-danger fw-semibold">
                                                    - {{ abs($daysLeft) }} day(s)
                                                </small>
                                                @endif
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                     <button type="button" class="bg03 color03 border-0 js-edit-order-btn"
                                                        data-order-id="{{ $order->orderid }}"
                                                        data-order-number="{{ $order->order_number }}"
                                                        data-client-id="{{ $order->clientid }}"
                                                        data-client-name="{{ $client->business_name ?? $client->contact_name }}"
                                                        data-item-id="{{ $order->itemid ?? '' }}"
                                                        data-item-name="{{ $order->item_name ?: ($order->item?->name ?? 'Item') }}"
                                                        data-item-description="{{ $order->item_description ?? '' }}"
                                                        data-quantity="{{ $order->quantity ?? 1 }}"
                                                        data-no-of-users="{{ $order->no_of_users ?? '' }}"
                                                        data-start-date="{{ $order->start_date ? $order->start_date->format('Y-m-d') : '' }}"
                                                        data-end-date="{{ $order->end_date ? $order->end_date->format('Y-m-d') : '' }}"
                                                        data-delivery-date="{{ $order->delivery_date ? $order->delivery_date->format('Y-m-d') : '' }}"
                                                        data-client-docid="{{ $order->client_docid ?? '' }}">
                                                        Edit
                                                     </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted bg-white">No orders found
                                                for this client.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 3. Invoices Tab --}}
                        <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                            <div class="table-responsive p-2 border-0 bg-DarkLight rounded-3 text-start">
                                <table class="table table-striped border mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 10%;">Issue Date</th>
                                            <th style="width: 10%;">Due Date</th>
                                            <th style="width: 35%;">Invoice</th>
                                            <th style="width: 12%;" class="text-end">Invoice Amount</th>
                                            <th style="width: 13%;" class="text-end">Balance Due</th>
                                            <th style="width: 20%;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($invoices as $invoice)
                                        @php
                                        $documentId = $invoice->invoiceid;
                                        $documentNumber = $invoice->ti_number ?: $invoice->pi_number ?:
                                        $invoice->invoice_number;
                                        $invoiceAmount = (float) ($invoice->grand_total ?? 0);
                                        $amountPaid = (float) ($invoice->amount_paid ?? 0);
                                        $balanceDue = (float) ($invoice->balance_due ?? max(0, $invoiceAmount -
                                        $amountPaid));
                                        $paymentStatus = strtolower(trim((string) ($invoice->payment_status ?? '')));
                                        if (!in_array($paymentStatus, ['paid', 'partly_paid', 'unpaid'], true)) {
                                        $paymentStatus = 'unpaid';
                                        if ($amountPaid > 0 && $balanceDue <= 0 && $invoiceAmount> 0) {
                                            $paymentStatus = 'paid';
                                            } elseif ($amountPaid > 0) {
                                            $paymentStatus = 'partly_paid';
                                            }
                                            }
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $invoice->issue_date?->format('d M Y') ?? '-' }}
                                                </td>
                                                <td>
                                                    {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                                                </td>
                                                <td>
                                                    <div class="invoice-row-title">
                                                        <div class="invoice-row-text">
                                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                                <strong class="text-dark">{{ $invoice->invoice_title ?:
                                                                    $invoice->invoice_number }}</strong>
                                                                @if ($paymentStatus === 'paid')
                                                                <span
                                                                    class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                                                    style="font-size: 11px;line-height:18px;">Paid</span>
                                                                @elseif($paymentStatus === 'partly_paid')
                                                                <span
                                                                    class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                                                    style="font-size: 11px;line-height:18px;">Partly
                                                                    Paid</span>
                                                                @else
                                                                <span
                                                                    class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2"
                                                                    style="font-size: 11px;line-height:18px;">Unpaid</span>
                                                                @endif
                                                            </div>
                                                            @if ($documentNumber)
                                                            <div
                                                                class="invoice-number-line mt-1 d-flex align-items-center gap-2">
                                                                @if (!empty($invoice->ti_number))
                                                                <span
                                                                    class="status-pill d-inline-block paid py-0.5 px-2 rounded-pill bg-success-subtle text-success fw-semibold"
                                                                    style="font-size: 11px;line-height:18px;">TI</span>
                                                                @else
                                                                <span
                                                                    class="status-pill d-inline-block partial bg-primary-subtle text-primary fw-semibold rounded-pill py-0.5 px-2"
                                                                    style="font-size: 11px;line-height:18px;">PI</span>
                                                                @endif
                                                                <span class="text-dark small">{{ $documentNumber
                                                                    }}</span>
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end text-dark">
                                                    <span>{{ number_format($invoiceAmount, 0) }}</span>
                                                    <span class="currency-code-small d-block text-muted">{{
                                                        $client->currency }}</span>
                                                </td>
                                                <td class="text-end text-dark">
                                                    <span class="text-danger fs-6 lh-sm fw-semibold">{{
                                                        number_format($balanceDue, 0) }}</span>
                                                    <span class="currency-code-small d-block text-muted">{{
                                                        $client->currency }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="tableActionButton d-inline-flex gap-1">
                                                        @if (($invoice->status ?? '') === 'cancelled')
                                                        <form method="POST"
                                                            action="{{ route('invoices.restore', array_filter(['invoice' => $documentId, 'c' => $client->clientid])) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Restore this invoice?')">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="bg02 color02">Restore</button>
                                                        </form>
                                                        @elseif (strtolower($invoice->status ?? '') === 'draft')
                                                        <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $client->clientid])) }}"
                                                            class="bg02 color02">Continue</a>
                                                        <form method="POST"
                                                            action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $client->clientid])) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Delete this draft?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="bg04 color04">Delete</button>
                                                        </form>
                                                        @else
                                                        <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                                            data-pdf-url="{{ route('invoices.pdf', $invoice->invoiceid) }}">
                                                            View
                                                        </button>
                                                        <a href="{{ route('invoices.email-compose', $invoice->invoiceid) }}"
                                                            class="bg03 color03">Send</a>
                                                        <a href="{{ route('invoices.edit', array_filter(['invoice' => $documentId, 'c' => $client->clientid])) }}"
                                                            class="bg03 color03">Edit</a>
                                                        <form method="POST"
                                                            action="{{ route('invoices.destroy', array_filter(['invoice' => $documentId, 'c' => $client->clientid])) }}"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Cancel this invoice?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="bg04 color04">Cancel</button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted bg-white">No invoices
                                                    found for this client.</td>
                                            </tr>
                                            @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 4. Quotations Tab --}}
                        <div class="tab-pane fade" id="quotations" role="tabpanel" aria-labelledby="quotations-tab">
                            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-0">
                                <div class="table-responsive text-start">
                                    <table class="table table-striped mainTable align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="10%">Issue Date</th>
                                                <th width="25%">Client</th>
                                                <th width="25%">Quotation Details</th>
                                                <th class="text-center" width="10%">Due Date</th>
                                                <th class="text-end" width="15%">Amount</th>
                                                <th class="text-end" width="15%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($quotations as $quotation)
                                            <tr>
                                                <td>{{ $quotation->issue_date?->format('d M Y') ?? '—' }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                                            <span class="d-block position-absolute">{{ strtoupper(substr($client->business_name ?? $client->contact_name, 0, 2)) }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="d-block fw-semibold">{{ $client->business_name ?? $client->contact_name }}</span>
                                                            @if($client->primary_email ?? $client->email)
                                                            <span class="d-block text-dark small lh-sm">{{ $client->primary_email ?? $client->email }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark">
                                                        {{ $quotation->quo_title ?: '—' }}
                                                    </div>
                                                    <small class="text-dark d-block">#{{ $quotation->quotation_number }}</small>
                                                </td>
                                                <td class="text-center">{{ $quotation->due_date?->format('d M Y') ?? '—' }}</td>
                                                <td class="text-end">
                                                    <span class="fw-semibold text-dark">
                                                        {{ number_format($quotation->grand_total, 2) }}
                                                        <span class="currency-code-small text-muted d-block">{{ $client->currency }}</span>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="tableActionButton d-inline-flex gap-1 align-items-center">
                                                        <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                                            data-pdf-url="{{ route('quotations.pdf', $quotation->quotationid) }}">
                                                            View
                                                        </button>
                                                         <a href="{{ route('quotations.create', ['step' => 2, 'c' => $client->clientid, 'd' => $quotation->quotationid]) }}"
                                                             class="bg03 color03">Edit</a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted bg-white">No quotations found for this client.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- 5. Payments Tab --}}
                        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                            <div class="table-responsive p-2 border-0 bg-DarkLight rounded-3 text-start">
                                <table class="table table-striped border mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Payment Details</th>
                                            <th style="width: 30%;">Invoice Details</th>
                                            <th style="width: 20%;" class="text-end">Settlement</th>
                                            <th style="width: 20%;" class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payments as $payment)
                                        <tr>
                                            <td>
                                                <strong class="fw-semibold text-dark">#{{ $payment->paymentid }}</strong>
                                                <div class="text-dark small lh-sm mt-1">
                                                    {{ $payment->payment_date?->format('d M Y') ?? $payment->created_at?->format('d M Y') }}
                                                </div>
                                                <div class="text-dark small lh-sm mt-1">
                                                    <span class="badge bg-light text-primary border text-uppercase fw-bold">{{ $payment->mode ?: 'Payment' }}</span>
                                                    @if (!empty($payment->receipt_number))
                                                    <span class="mx-1">|</span>
                                                    <span class="badge text-bg-primary">{{ $payment->receipt_number }}</span>
                                                    @endif
                                                </div>
                                                @if (!empty($payment->reference_number))
                                                <div class="text-dark small lh-sm mt-1">Ref: {{ $payment->reference_number }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                $linkedInvoice = $payment->invoice;
                                                $invoiceDisplay = null;
                                                if ($linkedInvoice) {
                                                $invoiceDisplay = $linkedInvoice->ti_number ?: $linkedInvoice->pi_number
                                                ?: $linkedInvoice->invoice_number;
                                                if ($linkedInvoice->invoice_title) {
                                                $invoiceDisplay = $linkedInvoice->invoice_title . ' (' . $invoiceDisplay
                                                . ')';
                                                }
                                                }
                                                @endphp
                                                @if ($invoiceDisplay)
                                                <span class="d-block fw-semibold text-dark">{{ $invoiceDisplay }}</span>
                                                @else
                                                <span class="d-block fw-normal text-muted small">—</span>
                                                @endif
                                                @if (!empty($payment->description) && trim((string)
                                                $payment->description) !== trim((string) $payment->paymentid))
                                                <span class="d-block text-dark small mt-1">{{ $payment->description
                                                    }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex flex-column align-items-end">
                                                    <strong class="text-dark fw-bold">
                                                        {{ $client->currency }} {{ number_format((float)
                                                        ($payment->received_amount ?? 0) + (float) ($payment->tds_amount
                                                        ?? 0), 0) }}
                                                    </strong>
                                                    @if ((float) ($payment->received_amount ?? 0) > 0)
                                                    <div class="text-muted small">
                                                        Received {{ number_format((float) ($payment->received_amount ??
                                                        0), 0) }}
                                                    </div>
                                                    @endif
                                                    @if ((float) ($payment->tds_amount ?? 0) > 0)
                                                    <div class="text-danger small">
                                                        TDS {{ number_format((float) ($payment->tds_amount ?? 0), 0) }}
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <button type="button" class="bg01 color01 border-0 view-pdf-btn"
                                                        data-pdf-url="{{ route('payments.show', $payment->paymentid) }}">
                                                        View
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted bg-white">No payments
                                                found for this client.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 6. Ledger Tab --}}
                        <div class="tab-pane fade" id="ledger" role="tabpanel" aria-labelledby="ledger-tab">
                            <div class="table-responsive text-start">
                                <table class="table table-striped mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="15%">Date</th>
                                            <th width="15%" class="text-center">Reference ID</th>
                                            <th width="15%" class="text-center">Type</th>
                                            <th width="15%" class="text-center">Mode</th>
                                            <th width="20%">Description</th>
                                            <th width="20%" class="text-end">Amount</th>
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
                                            <td class="text-center">
                                                <span class="font-monospace fw-semibold">{{
                                                    $ledgerItem->invoiceid_paymentid
                                                    ?: '—' }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice')
                                                <span
                                                    class="badge bg-danger-light text-danger text-uppercase client-ledger-badge">Debit</span>
                                                @else
                                                <span
                                                    class="badge bg-success-light text-success text-uppercase client-ledger-badge">Credit</span>
                                                @endif
                                            </td>
                                            <td class="text-center"><span class="text-capitalize small">{{ $ledgerItem->mode ?: '—'
                                                    }}</span>
                                            </td>
                                            <td class="small">{{ $ledgerItem->description ?: '—' }}</td>
                                            <td
                                                class="text-end fw-bold {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ? 'text-danger' : 'text-success' }}">
                                                {{ ($ledgerItem->type === 'debit' || $ledgerItem->type === 'invoice') ?
                                                '-'
                                                : '+' }} {{ number_format($ledgerItem->amount, 2) }}
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No ledger transactions
                                                found
                                                for this client.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 7. Documents Tab --}}
                        <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                            <div class="table-responsive p-2 border-0 bg-DarkLight rounded-3 text-start">
                                <table class="table table-striped border mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="15%">Type</th>
                                            <th width="25%">Title</th>
                                            <th width="15%">Document Number</th>
                                            <th width="15%">Document Date</th>
                                            <th class="text-end" width="30%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($documents as $document)
                                        <tr>
                                            <td>
                                                @if($document->type === 'po')
                                                <span class="border border-primary rounded-pill small lh-sm px-2 py-1 bg-primary text-white">PO</span>
                                                @else
                                                <span class="border rounded-pill small lh-sm px-2 py-1 text-white" style="background-color: #346739; border-color: #346739;">Agreement</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="d-block fw-semibold text-dark">{{ $document->title ?: '—' }}</span>
                                            </td>
                                            <td>{{ $document->document_number ?: '—' }}</td>
                                            <td>{{ $document->document_date?->format('d M Y') ?? '—' }}</td>
                                            <td class="text-end">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    @if($document->file_path)
                                                    <a href="{{ route('clients.documents.file', ['client' => $client->clientid, 'document' => $document->client_docid]) }}"
                                                        target="_blank" class="bg01 color01 text-decoration-none">View</a>
                                                    @else
                                                    —
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted bg-white">No agreements or PO documents uploaded.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 8. Communication Log Tab --}}
                        <div class="tab-pane fade" id="comms" role="tabpanel" aria-labelledby="comms-tab">
                            <div class="table-responsive text-start">
                                <table class="table table-striped mainTable align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="15%">Date & Time</th>
                                            <th width="10%" class="text-center">Channel</th>
                                            <th width="40%">Subject</th>
                                            <th width="20%">Recipient</th>
                                            <th width="15%" class="text-center">Status</th>
                                        </tr> 
                                    </thead>
                                    <tbody>
                                        @forelse($communicationLogs as $log)
                                        <tr>
                                            <td>
                                                <span class="d-block text-dark">{{ $log->created_at?->format('d M Y') }}</span>
                                                <span class="d-block text-dark mt-1">{{ $log->created_at?->format('H:i') }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge text-capitalize text-dark bg-light border rounded-pill">
                                                    <i
                                                        class="fas {{ $log->channel === 'email' ? 'fa-envelope text-primary' : 'fa-mobile-alt text-success' }} me-1"></i>
                                                    {{ $log->channel ?: 'Email' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark d-block">{{ $log->subject }}</span>
                                                @if($log->body)
                                                <p class="text-dark d-block text-truncate-2 client-log-preview mt-1 mb-0">{{
                                                    strip_tags($log->body) }}</p>
                                                @endif
                                            </td>
                                            <td class="small">{{ $log->to_email ?: $log->phone_number ?: '—' }}</td>
                                            <td class="text-center">
                                                <span
                                                    class="badge {{ $log->status === 'sent' || $log->status === 'success' ? 'bg-success-light text-success border border-success rounded-pill' : ($log->status === 'failed' ? 'bg-danger-light text-danger border border-danger rounded-pill' : 'bg-secondary-light text-secondary border border-secondary rounded-pill') }} text-uppercase client-log-status-badge">
                                                    {{ $log->status ?: 'Sent' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No emails or alerts
                                                logged
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
        <div class="col-12 col-md-2">
            <div class="card overflow-hidden border-0 bg-DarkLight p-2 p-xxl-3 rounded-3 h-100">
                <div class="card-body bg-transparent rounded-3 p-2">
                    {{-- Financial Metrics --}}
                    <div class="row g-3">

                        {{-- Outstanding --}}
                        <div class="col-12 col-lg-12">
                            <div class="card border-0 bg-DarkLight rounded-3 h-100">
                                <div class="card-body bg-white rounded-3 p-3 d-flex align-items-center">

                                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width:50px;height:50px;">
                                        <i class="far fa-bell fs-4 lh-sm"></i>
                                    </div>

                                    <div>
                                        <div class="text-dark small lh-sm text-uppercase fw-normal mb-1">
                                            Outstanding
                                        </div>
                                        <h5 class="mb-0 fw-bold text-danger">
                                            {{ number_format($outstanding, 0) }}
                                            <span class="text-muted small fw-normal fs-6 lh-sm">{{ $client->currency
                                                ?? 'INR'
                                                }}</span>
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Total Invoiced --}}
                        <div class="col-12 col-lg-12">
                            <div class="card border-0 bg-DarkLight rounded-3 h-100">
                                <div class="card-body bg-white rounded-3 p-3 d-flex align-items-center">

                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width:50px;height:50px;">
                                        <i class="far fa-file-alt fs-4 lh-sm"></i>
                                    </div>

                                    <div>
                                        <div class="text-dark small lh-sm text-uppercase fw-normal mb-1">
                                            Total Invoiced
                                        </div>
                                        <h5 class="mb-0 fw-bold text-primary">
                                            {{ number_format($invoicedTotal, 0) }}
                                            <span class="text-muted small fw-normal fs-6 lh-sm">{{ $client->currency
                                                ?? 'INR'
                                                }}</span>
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Total Paid --}}
                        <div class="col-12 col-lg-12">
                            <div class="card border-0 bg-DarkLight rounded-3 h-100">
                                <div class="card-body bg-white rounded-3 p-3 d-flex align-items-center">

                                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width:50px;height:50px;">
                                        <i class="far fa-check-circle fs-4 lh-sm"></i>
                                    </div>

                                    <div>
                                        <div class="text-dark small lh-sm text-uppercase fw-normal mb-1">
                                            Total Paid
                                        </div>
                                        <h5 class="mb-0 fw-bold text-success">
                                            {{ number_format($paidTotal, 0) }}
                                            <span class="text-muted small fw-normal fs-6 lh-sm">{{ $client->currency
                                                ??
                                                'INR'
                                                }}</span>
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Active Orders --}}
                        <div class="col-12 col-lg-12">
                            <div class="card border-0 bg-DarkLight rounded-3 h-100">
                                <div class="card-body bg-white rounded-3 p-3 d-flex align-items-center">

                                    <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3"
                                        style="width:50px;height:50px;">
                                        <i class="far fa-clock fs-4 lh-sm"></i>
                                    </div>

                                    <div>
                                        <div class="text-dark small lh-sm text-uppercase fw-normal  mb-1">
                                            Active Orders
                                        </div>
                                        <h5 class="mb-0 fw-bold text-info">
                                            {{ $activeOrdersCount }}
                                        </h5>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold">PDF Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2" style="height: 80vh;">
                <iframe id="pdfViewerFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
            </div>
        </div>
    </div>
</div>

@include('clients.partials.documents-modal')
@include('orders.partials.edit-order-modal')

@endsection

@push('scripts')
<script>
    window.__editModalConfig = {
        clientDocuments: @json($clientDocuments ?? []),
        todayStr: '{{ now()->format('Y-m-d') }}',
        renewRouteTemplate: '{{ route('invoices.orders.renew', ['order' => '__ORDER__']) }}',
        selectedClientId: '{{ $client->clientid ?? '' }}',
    };
</script>

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

                // Scroll active tab into view smoothly
                event.target.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            });
        });

        // Tabs scroll/slider logic (same as All Item List page)
        const tabsContainer = document.querySelector('.tabs-scroll-container');
        const prevBtn = document.querySelector('.tab-nav-prev');
        const nextBtn = document.querySelector('.tab-nav-next');

        if (tabsContainer && prevBtn && nextBtn) {
            const updateArrows = () => {
                const scrollLeft = Math.ceil(tabsContainer.scrollLeft);
                const scrollWidth = tabsContainer.scrollWidth;
                const clientWidth = tabsContainer.clientWidth;

                if (scrollWidth > clientWidth) {
                    prevBtn.classList.remove('d-none');
                    nextBtn.classList.remove('d-none');
                    prevBtn.classList.add('d-inline-flex');
                    nextBtn.classList.add('d-inline-flex');

                    if (scrollLeft <= 5) {
                        prevBtn.classList.add('opacity-50');
                        prevBtn.setAttribute('disabled', 'true');
                    } else {
                        prevBtn.classList.remove('opacity-50');
                        prevBtn.removeAttribute('disabled');
                    }

                    if (scrollLeft + clientWidth >= scrollWidth - 5) {
                        nextBtn.classList.add('opacity-50');
                        nextBtn.setAttribute('disabled', 'true');
                    } else {
                        nextBtn.classList.remove('opacity-50');
                        nextBtn.removeAttribute('disabled');
                    }
                } else {
                    prevBtn.classList.add('d-none');
                    nextBtn.classList.add('d-none');
                    prevBtn.classList.remove('d-inline-flex');
                    nextBtn.classList.remove('d-inline-flex');
                }
            };

            prevBtn.addEventListener('click', () => {
                tabsContainer.scrollBy({ left: -200, behavior: 'smooth' });
            });

            nextBtn.addEventListener('click', () => {
                tabsContainer.scrollBy({ left: 200, behavior: 'smooth' });
            });

            tabsContainer.addEventListener('scroll', updateArrows);
            window.addEventListener('resize', updateArrows);

            updateArrows();
            setTimeout(updateArrows, 150);

            // Initially scroll active tab into view
            const activeTab = document.querySelector('#dashboardTabs button.active');
            if (activeTab) {
                setTimeout(() => {
                    activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                }, 200);
            }
        }
        // Item description toggle logic
        document.addEventListener('click', function (e) {
            const toggleBtn = e.target.closest('.btn-desc-toggle');
            if (!toggleBtn) return;

            e.preventDefault();
            const parent = toggleBtn.closest('td') || toggleBtn.closest('.col');
            if (parent) {
                const descContainer = parent.querySelector('.desc-container');
                const toggleIcon = toggleBtn.querySelector('.desc-toggle-icon');
                if (descContainer) {
                    descContainer.classList.toggle('d-none');
                }
                if (toggleIcon) {
                    toggleIcon.classList.toggle('rotated');
                }
            }
        });

        const pdfModal = new bootstrap.Modal(document.getElementById('pdfViewerModal'));
        const pdfFrame = document.getElementById('pdfViewerFrame');

        document.querySelectorAll('.view-pdf-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pdfFrame.src = this.dataset.pdfUrl;
                pdfModal.show();
            });
        });

        document.getElementById('pdfViewerModal').addEventListener('hidden.bs.modal', function () {
            pdfFrame.src = '';
        });
    });
</script>
@endpush
