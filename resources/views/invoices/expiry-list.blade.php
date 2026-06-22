@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('invoices.index') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-list btn-icon"></i> Invoice List
    </a>
</div>
@endsection

@section('content')
@php
$allowedTabs = !empty($hasTrialClients)
? ['upcoming', 'expired', 'suspended', 'trial']
: ['upcoming', 'expired', 'suspended'];
$currentTab = in_array($selectedTab ?? 'expired', $allowedTabs, true)
? $selectedTab
: 'expired';
$tabRows = match ($currentTab) {
'upcoming' => $upcomingItems,
'expired' => $expiredItems,
'suspended' => $suspendedItems,
'trial' => $trialItems ?? collect(),
default => collect(),
};
@endphp

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('invoices.expiry-list') }}" method="GET" class="mainForm">
            <input type="hidden" name="tab" value="{{ $selectedTab ?? 'expired' }}">

            <div class="row g-2">
                <div class="col-12 col-md-2">
                    <select name="c" id="expiry_client_filter" class="form-select">
                        <option value="">All Clients</option>
                        @php
                        $regularClients = $clients->filter(fn($c) => strtolower((string) ($c->type ?? '')) !== 'trial');
                        $prospectClients = $clients->filter(fn($c) => strtolower((string) ($c->type ?? '')) ===
                        'trial');
                        @endphp

                        @if ($regularClients->isNotEmpty())
                        <optgroup label="Regular Clients">
                            @foreach ($regularClients as $clientOption)
                            <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId===(string)
                                $clientOption->clientid ? 'selected' : '' }}>
                                {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif

                        @if ($prospectClients->isNotEmpty())
                        <optgroup label="Prospect Clients">
                            @foreach ($prospectClients as $clientOption)
                            <option value="{{ $clientOption->clientid }}" {{ (string) $selectedClientId===(string)
                                $clientOption->clientid ? 'selected' : '' }}>
                                {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <div class="input-group">
                        <input type="date" name="from" id="expiry_from_filter" class="form-control"
                            placeholder="Start Date" value="{{ $fromDate ?? '' }}" readonly>
                        <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                    </div>
                </div>

                <div class="col-12 col-md-2">
                    <div class="input-group">
                        <input type="date" name="to" id="expiry_to_filter" class="form-control" placeholder="End Date"
                            value="{{ $toDate ?? '' }}" min="{{ $fromDate ?? '' }}" readonly>
                        <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                    </div>
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('invoices.expiry-list', array_filter([
                            'tab' => $selectedTab ?? 'expired',
                        ])) }}" class="btn btn-outline-primary bg-white text-primary fw-medium">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Clear
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        <i class="fas fa-filter btn-icon me-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2 px-1">
        <div class="btn-group" role="group" aria-label="Expiry Tabs">
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'upcoming' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'upcoming', 'from' => $fromDate ?? '', 'to' => $toDate ?? '', 'next_days' => $nextDays ?? 60])) }}">
                Upcoming <span
                    class="badge rounded-pill {{ $currentTab === 'upcoming' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $upcomingItems->count() }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'expired' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'expired', 'from' => $fromDate ?? '', 'to' => $toDate ?? ''])) }}">
                Expired <span
                    class="badge rounded-pill {{ $currentTab === 'expired' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $expiredItems->count() }}</span>
            </a>
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'suspended' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'suspended', 'from' => $fromDate ?? '', 'to' => $toDate ?? ''])) }}">
                Suspended <span
                    class="badge rounded-pill {{ $currentTab === 'suspended' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    $suspendedItems->count() }}</span>
            </a>
            @if(!empty($hasTrialClients))
            <a class="btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $currentTab === 'trial' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }} d-inline-flex align-items-center gap-2 fw-medium"
                href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'trial', 'from' => $fromDate ?? '', 'to' => $toDate ?? '', 'next_days' => $nextDays ?? 60])) }}">
                Prospect Clients <span
                    class="badge rounded-pill {{ $currentTab === 'trial' ? 'bg-primary text-white' : 'bg-primary-subtle text-primary' }}">{{
                    ($trialItems ?? collect())->count() }}</span>
            </a>
            @endif
        </div>
        <div class="btn-group align-self-end" role="group" aria-label="View Toggle">
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                style="font-size:0.875rem;" id="btn-grid-view">
                <i class="fas fa-th-large toggle-icon"></i> Grid
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                style="font-size:0.875rem;" id="btn-list-view">
                <i class="fas fa-list toggle-icon"></i> List
            </button>
        </div>
    </div>

    <section class="invoice-group">
        <div class="invoice-list-meta d-flex justify-content-between align-items-center flex-wrap gap-2 ps-2 mb-2">
            @if($currentTab === 'upcoming')
            <div class="meta-info align-self-end">
                <strong class="fw-bold fs-5 lh-sm">Upcoming</strong>
            </div>
            <form action="{{ route('invoices.expiry-list') }}" method="GET"
                class="d-flex align-items-center gap-2 flex-wrap">
                <input type="hidden" name="tab" value="upcoming">
                @if($selectedClientId)
                <input type="hidden" name="c" value="{{ $selectedClientId }}">
                @endif
                @if(($fromDate ?? '') !== '')
                <input type="hidden" name="from" value="{{ $fromDate }}">
                @endif
                @if(($toDate ?? '') !== '')
                <input type="hidden" name="to" value="{{ $toDate }}">
                @endif
                <label class="small fw-semibold text-dark mb-0" for="upcoming_next_days_inline">Next Days</label>
                <input type="number" name="next_days" id="upcoming_next_days_inline"
                    class="form-control form-control-sm" style="width: 200px;" value="{{ $nextDays ?? 60 }}" min="1"
                    step="1">
                <button type="submit" class="btn btn-sm btn-outline-primary bg-primary text-white fw-medium">View <i
                        class="fas fa-arrow-right btn-icon ms-1"></i></button>
            </form>
            @elseif($currentTab === 'expired')
            <div class="meta-info">
                <strong class="fw-bold fs-5 lh-sm">Expired</strong>
            </div>
            @elseif($currentTab === 'trial')
            <div class="meta-info">
                <strong class="fw-bold fs-5 lh-sm">Prospect Clients</strong>
            </div>
            @else
            <div class="meta-info">
                <strong class="fw-bold fs-5 lh-sm">Suspended</strong>
            </div>
            @endif
        </div>

        @if (collect($tabRows)->isEmpty())
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
            <div class="card-body bg-white rounded-3 py-5 text-center text-muted">
                <i class="fas fa-check-circle mb-3 text-secondary fs-1 opacity-50"></i>
                <p class="fw-semibold text-dark mb-1">No records found for this tab.</p>
            </div>
        </div>
        @else
        <div id="expiry-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3">
            <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="20%">Client</th>
                            <th>Items</th>
                            <th class="text-center" width="15%">Expiry</th>
                            <th class="text-center" width="15%">Days</th>
                            <th class="text-end" width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tabRows as $row)
                        <tr>
                            <td>{{ $row['client_name'] }}</td>
                            <td>
                                <strong class="text-dark">{{ $row['item_name'] }}</strong>
                            </td>
                            <td class="text-center">
                                <span
                                    class="{{ (($row['days_left'] ?? null) !== null && $row['days_left'] < 0) ? 'text-dark fw-semibold' : '' }}">
                                    {{ $row['end_date_display'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($row['days_left'] === null)
                                <span class="text-muted">-</span>
                                @elseif($row['days_left'] > 0)
                                <span class="text-success fw-semibold">{{ $row['days_left'] }} day(s)</span>
                                @elseif($row['days_left'] === 0)
                                <span class="text-warning fw-semibold">Today</span>
                                @else
                                <span class="text-danger fw-semibold">-{{ abs($row['days_left']) }} day(s)</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="tableActionButton d-inline-flex gap-1">
                                    @if(strtolower((string) ($row['status'] ?? '')) !== 'cancelled')
                                    <form method="POST"
                                        action="{{ route('invoices.orders.send-reminder', ['order' => $row['orderid']]) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('Send manual reminder for this order?')">
                                        @csrf
                                        <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                                        <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                        <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                        <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                        <button type="submit" class="bg03 color03">Renewal Reminder</button>
                                    </form>
                                    @endif

                                    <button type="button" class="bg02 color02 border-0 js-renew-order-btn"
                                        data-order-id="{{ $row['orderid'] }}"
                                        data-order-number="{{ $row['order_number'] }}"
                                        data-client-name="{{ $row['client_name'] }}"
                                        data-invoice-number="{{ $row['invoice_number'] }}"
                                        data-item-name="{{ $row['item_name'] }}"
                                        data-item-description="{{ $row['item_description'] }}"
                                        data-start-date="{{ $row['start_date_display'] }}"
                                        data-end-date-display="{{ $row['end_date_display'] }}"
                                        data-days-left="{{ $row['days_left'] }}"
                                        data-status="{{ ucfirst($row['status']) }}"
                                        data-end-date="{{ $row['end_date'] ? $row['end_date']->format('Y-m-d') : '' }}"
                                        data-client-id="{{ $row['clientid'] }}"
                                        data-frequency="{{ $row['frequency'] ?? '' }}"
                                        data-duration="{{ $row['duration'] ?? 1 }}">
                                        Renew
                                    </button>

                                    @if ($currentTab === 'expired' && $row['status'] !== 'suspended')
                                    <form method="POST"
                                        action="{{ route('invoices.orders.suspend', ['order' => $row['orderid']]) }}"
                                        class="d-inline" onsubmit="return confirm('Suspend this order?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                                        <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                        <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                        <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                        <button type="submit" class="bg04 color04">Suspend</button>
                                    </form>
                                    @elseif($row['status'] === 'suspended')
                                    <form method="POST"
                                        action="{{ route('invoices.orders.unsuspend', ['order' => $row['orderid']]) }}"
                                        class="d-inline" onsubmit="return confirm('Unsuspend this order?')">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                        <input type="hidden" name="tab" value="{{ $currentTab }}">
                                        <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                        <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                        <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                        <button type="submit" class="bg02 color02">Unsuspend</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expiry Grid View (5 blocks in one row on desktop) -->
        <div id="expiry-grid-view"
            class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none mb-3">
            @foreach ($tabRows as $row)
            <div class="col">
                <div class="card h-100 border-0 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Header / Dates -->
                            <div class="d-flex justify-content-end align-items-center mb-3">
                                <div class="text-dark small lh-sm" style="font-size: 13px;">
                                    Expiry: <span class="text-dark fw-semibold">{{ $row['end_date_display'] }}</span>
                                </div>
                                <div>
                                    @if(strtolower((string)($row['status'] ?? '')) === 'suspended')
                                    <span class="status-pill d-inline-block overdue bg-danger-subtle text-danger fw-semibold rounded-pill py-0.5 px-2" style="font-size: 11px; line-height: 18px;">Suspended</span>
                                    @elseif(strtolower((string)($row['status'] ?? '')) === 'cancelled')
                                    <span class="status-pill d-inline-block overdue bg-secondary-subtle text-secondary fw-semibold rounded-pill py-0.5 px-2" style="font-size: 11px; line-height: 18px;">Cancelled</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Client Info -->
                            <div class="d-flex align-items-center gap-2 mb-3 pb-3 border-bottom">
                                <div class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($row['client_name'], 0, 2)) }}</span>
                                </div>
                                <div class="flex-grow-1 min-w-0 ps-2">
                                    <h6 class="fw-semibold text-dark mb-1 text-truncate lh-sm" title="{{ $row['client_name'] }}">
                                        {{ $row['client_name'] }}
                                    </h6>
                                    <span class="d-block text-dark small lh-sm text-truncate" title="{{ $row['item_name'] }}">
                                        {{ $row['item_name'] }}
                                    </span>
                                </div> 
                            </div>

                            <!-- Additional Details -->
                            <div class="bg-light rounded-3 px-3 py-2 mt-auto grid-text-medium mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1 text-dark small lh-sm">
                                    <span class="text-muted">Days Left</span>
                                    <div>
                                        @if ($row['days_left'] === null)
                                        <span class="text-muted fw-semibold">-</span>
                                        @elseif($row['days_left'] > 0)
                                        <span class="text-success fw-semibold">{{ $row['days_left'] }} day(s)</span>
                                        @elseif($row['days_left'] === 0)
                                        <span class="text-warning fw-semibold">Today</span>
                                        @else
                                        <span class="text-danger fw-semibold">-{{ abs($row['days_left']) }} day(s)</span>
                                        @endif
                                    </div>
                                </div>
                                @if(!empty($row['invoice_number']))
                                <div class="d-flex justify-content-between align-items-center mb-1 text-dark small lh-sm">
                                    <span class="text-muted">Invoice Ref</span>
                                    <span class="text-truncate text-end fw-semibold" style="max-width: 120px;" title="{{ $row['invoice_number'] }}">{{ $row['invoice_number'] }}</span>
                                </div>
                                @endif
                                @if(!empty($row['order_number']))
                                <div class="d-flex justify-content-between align-items-center mb-1 text-dark small lh-sm">
                                    <span class="text-muted">Order Ref</span>
                                    <span class="text-truncate text-end fw-semibold" style="max-width: 120px;" title="#{{ $row['order_number'] }}">#{{ $row['order_number'] }}</span>
                                </div>
                                @endif
                                @if(!empty($row['frequency']))
                                <div class="d-flex justify-content-between align-items-center mb-1 text-dark small lh-sm">
                                    <span class="text-muted">Cycle</span>
                                    <span class="text-end fw-semibold">{{ $row['duration'] }} {{ $row['frequency'] }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="tableActionButton d-flex flex-wrap gap-1 mt-0">
                            @if(strtolower((string) ($row['status'] ?? '')) !== 'cancelled')
                            <form method="POST"
                                action="{{ route('invoices.orders.send-reminder', ['order' => $row['orderid']]) }}"
                                class="d-inline flex-grow-1"
                                onsubmit="return confirm('Send manual reminder for this order?')">
                                @csrf
                                <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                <input type="hidden" name="tab" value="{{ $currentTab }}">
                                <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                <button type="submit" class="bg03 color03 w-100 text-center border-0 py-1.5">Reminder</button>
                            </form>
                            @endif

                            <button type="button" class="bg02 color02 border-0 flex-grow-1 text-center py-1.5 js-renew-order-btn"
                                data-order-id="{{ $row['orderid'] }}"
                                data-order-number="{{ $row['order_number'] }}"
                                data-client-name="{{ $row['client_name'] }}"
                                data-invoice-number="{{ $row['invoice_number'] }}"
                                data-item-name="{{ $row['item_name'] }}"
                                data-item-description="{{ $row['item_description'] }}"
                                data-start-date="{{ $row['start_date_display'] }}"
                                data-end-date-display="{{ $row['end_date_display'] }}"
                                data-days-left="{{ $row['days_left'] }}"
                                data-status="{{ ucfirst($row['status']) }}"
                                data-end-date="{{ $row['end_date'] ? $row['end_date']->format('Y-m-d') : '' }}"
                                data-client-id="{{ $row['clientid'] }}"
                                data-frequency="{{ $row['frequency'] ?? '' }}"
                                data-duration="{{ $row['duration'] ?? 1 }}">
                                Renew
                            </button>

                            @if ($currentTab === 'expired' && $row['status'] !== 'suspended')
                            <form method="POST"
                                action="{{ route('invoices.orders.suspend', ['order' => $row['orderid']]) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Suspend this order?')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                <input type="hidden" name="tab" value="{{ $currentTab }}">
                                <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                <button type="submit" class="bg04 color04 w-100 text-center border-0 py-1.5" style="font-size: 11px;">Suspend</button>
                            </form>
                            @elseif($row['status'] === 'suspended')
                            <form method="POST"
                                action="{{ route('invoices.orders.unsuspend', ['order' => $row['orderid']]) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Unsuspend this order?')">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                <input type="hidden" name="tab" value="{{ $currentTab }}">
                                <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                <button type="submit" class="bg02 color02 w-100 text-center border-0 py-1.5" style="font-size: 11px;">Unsuspend</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>
</div>
@include('invoices.partials.renew-order-modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const renewModalEl = document.getElementById('renewOrderModal');
        if (!renewModalEl || typeof bootstrap === 'undefined') return;

        const renewModal = new bootstrap.Modal(renewModalEl);
        const renewForm = document.getElementById('renewOrderForm');
        const itemName = document.getElementById('renewOrderItemName');
        const clientName = document.getElementById('renewOrderClientName');
        const orderNumber = document.getElementById('renewOrderNumber');
        const invoiceNumber = document.getElementById('renewOrderInvoiceRef');
        const startDateDisplay = document.getElementById('renewOrderStartDate');
        const currentEndDateDisplay = document.getElementById('renewOrderCurrentEndDate');
        const statusDisplay = document.getElementById('renewOrderStatus');
        const endDateInput = document.getElementById('renew_order_end_date');
        const clientInput = document.getElementById('renew_order_client');
        const tabInput = document.getElementById('renew_order_tab');
        const fromInput = document.getElementById('renew_order_from');
        const toInput = document.getElementById('renew_order_to');
        const nextDaysInput = document.getElementById('renew_order_next_days');
        const frequencyInput = document.getElementById('renew_order_frequency');
        const durationInput = document.getElementById('renew_order_duration');
        const durationWrapper = document.getElementById('renew_order_duration_wrapper');

        const currentTab = @json($currentTab);
        const currentClient = @json($selectedClientId);
        const currentFrom = @json($fromDate ?? '');
        const currentTo = @json($toDate ?? '');
        const currentNextDays = @json($nextDays ?? 60);
        const renewRouteTemplate = @json(route('invoices.orders.renew', ['order' => '__ORDER__']));
    let pendingRenewEndDate = '';
    let renewalBaseStartDate = '';
    const setText = (element, value) => {
        if (!element) return;
        element.textContent = value;
    };

    function normalizeIsoDate(rawValue) {
        const value = String(rawValue || '').trim();
        if (!value) return '';

        const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (isoMatch) {
            return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
        }

        return '';
    }

    function applyRenewEndDate(rawValue) {
        const iso = normalizeIsoDate(rawValue);
        pendingRenewEndDate = iso;

        endDateInput.value = '';
        endDateInput.removeAttribute('value');
        endDateInput.dataset.prefillDate = '';

        if (endDateInput._flatpickr) {
            endDateInput._flatpickr.clear();
        }

        if (!iso) return;

        endDateInput.value = iso;
        endDateInput.setAttribute('value', iso);
        endDateInput.dataset.prefillDate = iso;

        if (endDateInput._flatpickr) {
            // Keep Flatpickr UI in sync so month/year are visible without extra clicks.
            endDateInput._flatpickr.setDate(iso, true, 'Y-m-d');
        }
    }

    function syncRenewEndDateStateFromInput() {
        pendingRenewEndDate = normalizeIsoDate(endDateInput.value);
    }

    document.querySelectorAll('.js-renew-order-btn').forEach((button) => {
        button.addEventListener('click', function () {
            const orderId = this.dataset.orderId || '';
            const orderNo = this.dataset.orderNumber || '-';
            const client = this.dataset.clientName || '-';
            const invoiceRef = this.dataset.invoiceNumber || '-';
            const item = this.dataset.itemName || '-';
            const startDate = this.dataset.startDate || '-';
            const endDateDisplay = this.dataset.endDateDisplay || '-';
            const status = this.dataset.status || '-';
            const endDate = normalizeIsoDate(this.dataset.endDate);
            const frequency = this.dataset.frequency || '';
            const duration = this.dataset.duration || 1;

            if (!orderId) return;

            renewForm.action = renewRouteTemplate.replace('__ORDER__', orderId);
            setText(itemName, item);
            setText(clientName, client);
            setText(orderNumber, orderNo && orderNo !== '-' ? '#' + orderNo : '-');
            setText(invoiceNumber, invoiceRef);
            setText(startDateDisplay, startDate);
            setText(currentEndDateDisplay, endDateDisplay);
            setText(statusDisplay, status);
            if (statusDisplay) {
                if (status.toLowerCase() === 'active') {
                    statusDisplay.classList.remove('text-danger');
                    statusDisplay.classList.add('text-success');
                } else {
                    statusDisplay.classList.remove('text-success');
                    statusDisplay.classList.add('text-danger');
                }
            }
            renewalBaseStartDate = plusOneDay(endDate);
            applyRenewEndDate(renewalBaseStartDate || endDate);
            clientInput.value = currentClient || '';
            tabInput.value = currentTab || 'expired';
            fromInput.value = currentFrom || '';
            toInput.value = currentTo || '';
            nextDaysInput.value = currentNextDays || '';

            // Set frequency and duration
            if (frequencyInput) {
                frequencyInput.value = frequency || 'One-Time';
                frequencyInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (durationInput) {
                durationInput.value = duration || 1;
                durationInput.disabled = !frequency || frequency === 'One-Time';
            }
            refreshEndDate();

            renewModal.show();
        });
    });

    renewModalEl.addEventListener('shown.bs.modal', function () {
        if (!pendingRenewEndDate) return;
        requestAnimationFrame(() => {
            applyRenewEndDate(pendingRenewEndDate);
            endDateInput.dispatchEvent(new Event('input', { bubbles: true }));
            endDateInput.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    renewModalEl.addEventListener('hidden.bs.modal', function () {
        pendingRenewEndDate = '';
        renewalBaseStartDate = '';
    });

    endDateInput.addEventListener('change', syncRenewEndDateStateFromInput);
    endDateInput.addEventListener('input', syncRenewEndDateStateFromInput);

    // Frequency/Duration auto-calculation
    function isOneTimeFrequency() {
        const selectedFrequency = frequencyInput?.value || '';
        return selectedFrequency === 'One-Time';
    }

    function toggleDurationField() {
        if (!durationInput) return;
        durationInput.disabled = isOneTimeFrequency();
    }

    function formatDateLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function plusOneDay(isoDate) {
        if (!isoDate) return '';
        const date = new Date(isoDate + 'T00:00:00');
        if (Number.isNaN(date.getTime())) return '';
        date.setDate(date.getDate() + 1);
        return formatDateLocal(date);
    }

    function calculateNewEndDate(baseStartDate, frequency, duration) {
        if (!baseStartDate) {
            return '';
        }

        const start = new Date(baseStartDate + 'T00:00:00');
        const end = new Date(start);
        const count = Math.max(1, parseInt(duration, 10) || 1);

        if (!frequency || frequency === 'One-Time') {
            return formatDateLocal(end);
        }

        switch (frequency) {
            case 'Day(s)':
                end.setDate(end.getDate() + count);
                break;
            case 'Week(s)':
                end.setDate(end.getDate() + (count * 7));
                break;
            case 'Month(s)':
                end.setMonth(end.getMonth() + count);
                break;
            case 'Quarter(s)':
                end.setMonth(end.getMonth() + (count * 3));
                break;
            case 'Year(s)':
                end.setFullYear(end.getFullYear() + count);
                break;
            default:
                break;
        }

        // Inclusive range: end date is one day before the next cycle boundary.
        end.setDate(end.getDate() - 1);
        return formatDateLocal(end);
    }

    function refreshEndDate() {
        if (!frequencyInput || !durationInput || !endDateInput) return;
        toggleDurationField();

        if (!renewalBaseStartDate) return;

        const newEndDate = calculateNewEndDate(
            renewalBaseStartDate,
            frequencyInput.value,
            durationInput.value
        );

        if (newEndDate) {
            applyRenewEndDate(newEndDate);
        }
    }

    if (frequencyInput) {
        frequencyInput.addEventListener('change', refreshEndDate);
    }
    if (durationInput) {
        durationInput.addEventListener('input', refreshEndDate);
    }

    // View Toggle Logic
    const btnList = document.getElementById('btn-list-view');
    const btnGrid = document.getElementById('btn-grid-view');
    const listView = document.getElementById('expiry-list-view');
    const gridView = document.getElementById('expiry-grid-view');

    function setView(viewType) {
        if (viewType === 'grid') {
            if (listView) listView.classList.add('d-none');
            if (gridView) gridView.classList.remove('d-none');
            if (btnList) {
                btnList.classList.remove('active', 'btn-primary');
                btnList.classList.add('btn-outline-primary');
            }
            if (btnGrid) {
                btnGrid.classList.add('active', 'btn-primary');
                btnGrid.classList.remove('btn-outline-primary');
            }
            localStorage.setItem('expiry_view_preference', 'grid');
        } else {
            if (listView) listView.classList.remove('d-none');
            if (gridView) gridView.classList.add('d-none');
            if (btnList) {
                btnList.classList.add('active', 'btn-primary');
                btnList.classList.remove('btn-outline-primary');
            }
            if (btnGrid) {
                btnGrid.classList.remove('active', 'btn-primary');
                btnGrid.classList.add('btn-outline-primary');
            }
            localStorage.setItem('expiry_view_preference', 'list');
        }
    }

    if (btnList && btnGrid) {
        btnList.addEventListener('click', () => setView('list'));
        btnGrid.addEventListener('click', () => setView('grid'));

        const savedPref = localStorage.getItem('expiry_view_preference');
        if (savedPref === 'grid') {
            setView('grid');
        } else {
            setView('list');
        }
    }
        });
</script>
@endsection
