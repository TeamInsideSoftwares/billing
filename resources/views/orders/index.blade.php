@extends('layouts.app')

@php
    if (isset($subtitle) && $subtitle === 'Showing orders across all clients.') {
        $subtitle = null;
    }
@endphp

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    @if(!empty($showClientPicker))
    <a href="{{ route('orders.index', ['c' => 'all']) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-list btn-icon"></i> View Orders
    </a>
    @else
    <a href="{{ route('orders.trials') }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-user-clock btn-icon"></i> Trial Orders
    </a>
    <a href="{{ route('orders.create', array_filter(['c' => $clientId])) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Create Orders
    </a>
    @endif
</div>
@endsection

@section('content')
@if(!empty($showClientPicker))
<div class="position-relative">
    <div class="row">
        <div class="col-12 col-md-4 mx-auto">
            <div class="bg-white p-3 rounded-3 shadow-sm">
                <div class="bg-light p-4 rounded-3 border mx-auto">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                <h5 class="fw-semibold text-black mb-0">Manage Orders</h5>
                                <p class="text-muted mb-0">Choose a client first to load item-based orders.</p>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('orders.index') }}" method="GET" class="mainForm">
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label for="client-select"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Clients ({{
                                    $allClients->count() }})<span class="text-danger">*</span></label>
                                <select name="c" id="client-select" class="form-select" autofocus>
                                    <option value="" selected disabled>Select a client</option>
                                    <option value="all">All Clients</option>
                                    @foreach($allClients ?? [] as $client)
                                    <option value="{{ $client->clientid }}">{{ $client->business_name ??
                                        $client->contact_name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
                            <button type="button" id="btnCreateOrderFromPicker"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Create Orders <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
    <!-- Filters Card -->
    <div class="position-relative bg-light border p-3 rounded-3 mb-3">
        <form action="{{ route('orders.index') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-5">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="orders_client_filter">Client</label>
                    <select name="c" id="orders_client_filter" class="form-select">
                        <option value="all" {{ empty($clientId) ? 'selected' : '' }}>All Clients</option>
                        @foreach($allClients ?? [] as $client)
                        <option value="{{ $client->clientid }}" {{ (string) $clientId===(string) $client->clientid ?
                            'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-5">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="orders_item_filter">Product</label>
                    <select name="itemid" id="orders_item_filter" class="form-select">
                        <option value="">All Products</option>
                        @php
                        $servicesByCategory = collect($services ?? [])->groupBy(function ($service) {
                        return $service->category?->name ?? 'Uncategorized';
                        })->sortKeys();
                        @endphp
                        @foreach($servicesByCategory as $categoryName => $categoryServices)
                        <optgroup label="{{ $categoryName }}">
                            @foreach($categoryServices as $service)
                            <option value="{{ $service->itemid }}" {{ (string) ($selectedItemId ?? '' )===(string)
                                $service->itemid ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('orders.index', ['c' => empty($clientId) ? 'all' : $clientId]) }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium w-100 text-center justify-content-center">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Reset
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                        Apply <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    @forelse($groupedOrders as $clientName => $clientOrders)
    <div class="card border shadow-sm mb-3">
        <div class="card-header bg-light border-bottom d-flex justify-content-between align-items-center py-2 px-3">
            <div class="d-flex align-items-center">
                <span class="fw-bold text-dark fs-6">{{ $clientName }}</span>
                @if(strtolower((string) ($clientOrders[0]['client_type'] ?? 'regular')) === 'trial')
                <span class="status-pill is-pending ms-2">Trial</span>
                @endif
            </div>
            <span class="badge bg-secondary-subtle text-secondary fw-semibold">{{ count($clientOrders) }}
                order(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th class="w-50">Item</th>
                        <th>Create Date</th>
                        <th>Expiry Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientOrders as $order)
                    <tr>
                        <td class="fw-semibold text-dark">#{{ $order['number'] }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $order['items'][0]['item_name'] ?? 'Item' }}</div>
                            @if(!empty($order['items'][0]['item_description']))
                            <div class="text-muted small mt-1">{{ $order['items'][0]['item_description'] }}</div>
                            @endif
                            <div class="text-muted small mt-1">
                                Qty: {{ rtrim(rtrim(number_format((float) ($order['items'][0]['quantity'] ?? 1), 2, '.',
                                ''), '0'), '.') }}
                                @if(!empty($order['items'][0]['no_of_users']))
                                | Users: {{ $order['items'][0]['no_of_users'] }}
                                @endif
                            </div>
                        </td>
                        <td>{{ $order['items'][0]['start_date'] ?? '-' }}</td>
                        <td>
                            @php
                            $orderEndDate = $order['items'][0]['end_date'] ?? null;
                            $isOrderExpired = !empty($orderEndDate) &&
                            \Carbon\Carbon::parse($orderEndDate)->lt(now()->startOfDay());
                            @endphp
                            <span class="{{ $isOrderExpired ? 'text-danger fw-bold' : '' }}">
                                {{ $orderEndDate ?? '-' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                @if(!empty($order['items'][0]['end_date']) &&
                                \Carbon\Carbon::parse($order['items'][0]['end_date'])->isPast())
                                <button type="button" class="bg02 color02 border-0 js-renew-order-btn"
                                    data-order-id="{{ $order['record_id'] }}" data-order-number="{{ $order['number'] }}"
                                    data-client-name="{{ $order['client'] }}" data-invoice-number="-"
                                    data-item-name="{{ $order['items'][0]['item_name'] ?? 'Item' }}"
                                    data-item-description="{{ $order['items'][0]['item_description'] ?? '' }}"
                                    data-start-date="{{ $order['items'][0]['start_date'] ?? '-' }}"
                                    data-end-date-display="{{ $order['items'][0]['end_date'] ?? '-' }}"
                                    data-days-left="{{ !empty($order['items'][0]['end_date']) ? \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order['items'][0]['end_date'])->startOfDay(), false) : '' }}"
                                    data-status="{{ ($order['status'] ?? '') === 'running' ? 'Active' : ucfirst($order['status'] ?? 'active') }}"
                                    data-end-date="{{ $order['items'][0]['end_date'] ?? '' }}"
                                    data-client-id="{{ $order['clientid'] }}"
                                    data-frequency="{{ $order['items'][0]['frequency'] ?? '' }}"
                                    data-duration="{{ $order['items'][0]['duration'] ?? 1 }}">
                                    Renew
                                </button>
                                @endif
                                @if(($order['status'] ?? '') !== 'cancelled')
                                <a href="{{ route('orders.edit', ['order' => $order['record_id']]) }}"
                                    class="bg03 color03">Edit</a>
                                <form method="POST"
                                    action="{{ route('orders.destroy', ['order' => $order['record_id']]) }}"
                                    class="d-inline" onsubmit="return confirm('Cancel this order?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04">Cancel</button>
                                </form>
                                @else
                                <form method="POST"
                                    action="{{ route('orders.restore', ['order' => $order['record_id']]) }}"
                                    class="d-inline" onsubmit="return confirm('Restore this order?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bg01 color01">Restore</button>
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
    @empty
    <div class="card border-0 shadow-sm py-5 text-center text-muted mb-3">
        <div class="card-body">
            <i class="fas fa-receipt mb-3 text-secondary fs-1 opacity-50"></i>
            <p class="fw-semibold text-dark mb-1">No orders found</p>
            <p class="small text-muted mb-0">Create your first order to get started.</p>
        </div>
    </div>
    @endforelse
</div>
@endif

@include('invoices.partials.renew-order-modal')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const clientPickerSelect = document.getElementById('client-select');

        const clientForm = document.querySelector('.payment-client-picker-form');
        clientForm?.addEventListener('submit', function () {
            if (clientPickerSelect && !clientPickerSelect.value) {
                clientPickerSelect.value = 'all';
            }
        });

        document.getElementById('btnCreateOrderFromPicker')?.addEventListener('click', function () {
            const clientId = clientPickerSelect?.value || '';
            if (clientId) {
                window.location.href = "{{ route('orders.create') }}?c=" + encodeURIComponent(clientId);
            } else {
                alert('Please select a client first.');
            }
        });

        const renewModalEl = document.getElementById('renewOrderModal');
        if (!renewModalEl || typeof bootstrap === 'undefined') return;

        const renewModal = new bootstrap.Modal(renewModalEl);
        const renewForm = document.getElementById('renewOrderForm');
        const itemName = document.getElementById('renewOrderItemName');
        const clientName = document.getElementById('renewOrderClientName');
        const orderNumber = document.getElementById('renewOrderNumber');
        const invoiceNumber = document.getElementById('renewOrderInvoiceRef');
        const itemDescription = document.getElementById('renewOrderItemDescription');
        const startDateDisplay = document.getElementById('renewOrderStartDate');
        const currentEndDateDisplay = document.getElementById('renewOrderCurrentEndDate');
        const statusDisplay = document.getElementById('renewOrderStatus');
        const daysLeftDisplay = document.getElementById('renewOrderDaysLeft');
        const todayDisplay = document.getElementById('renewOrderToday');
        const endDateInput = document.getElementById('renew_order_end_date');
        const clientInput = document.getElementById('renew_order_client');
        const tabInput = document.getElementById('renew_order_tab');
        const fromInput = document.getElementById('renew_order_from');
        const toInput = document.getElementById('renew_order_to');
        const nextDaysInput = document.getElementById('renew_order_next_days');
        const returnToInput = document.getElementById('renew_order_return_to');
        const frequencyInput = document.getElementById('renew_order_frequency');
        const durationInput = document.getElementById('renew_order_duration');
        const durationWrapper = document.getElementById('renew_order_duration_wrapper');
        const renewRouteTemplate = @json(route('invoices.orders.renew', ['order' => '__ORDER__']));
    const selectedClientId = @json($clientId ?? request('c'));
    const normalizeIsoDate = (rawValue) => {
        const value = String(rawValue || '').trim();
        const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[1]}-${match[2]}-${match[3]}` : '';
    };
    const applyRenewEndDate = (rawValue) => {
        const iso = normalizeIsoDate(rawValue);
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
            endDateInput._flatpickr.setDate(iso, true, 'Y-m-d');
        }
    };
    const setText = (el, value) => {
        if (!el) return;
        el.textContent = value;
    };

    document.querySelectorAll('.js-renew-order-btn').forEach((button) => {
        button.addEventListener('click', function () {
            const orderId = this.dataset.orderId || '';
            if (!orderId) return;

            const daysLeftRaw = this.dataset.daysLeft;
            const daysLeft = daysLeftRaw === '' || daysLeftRaw === undefined || daysLeftRaw === null
                ? '-'
                : (Number(daysLeftRaw) < 0
                    ? `${Math.abs(Number(daysLeftRaw))} day(s) ago`
                    : (Number(daysLeftRaw) === 0 ? 'Today' : `${Number(daysLeftRaw)} day(s)`));

            renewForm.action = renewRouteTemplate.replace('__ORDER__', orderId);
            setText(itemName, this.dataset.itemName || '-');
            setText(clientName, this.dataset.clientName || '-');
            setText(orderNumber, this.dataset.orderNumber || '-');
            setText(invoiceNumber, this.dataset.invoiceNumber || '-');
            setText(itemDescription, this.dataset.itemDescription || '-');
            setText(startDateDisplay, this.dataset.startDate || '-');
            setText(currentEndDateDisplay, this.dataset.endDateDisplay || '-');
            setText(statusDisplay, this.dataset.status || '-');
            setText(daysLeftDisplay, daysLeft);
            setText(todayDisplay, new Date().toLocaleDateString());
            applyRenewEndDate(this.dataset.endDate || '');
            clientInput.value = selectedClientId || this.dataset.clientId || '';
            tabInput.value = '';
            fromInput.value = '';
            toInput.value = '';
            nextDaysInput.value = '';
            returnToInput.value = 'orders';

            // Set frequency and duration
            const frequency = this.dataset.frequency || '';
            const duration = this.dataset.duration || 1;
            if (frequencyInput) {
                frequencyInput.value = frequency;
                frequencyInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (durationInput) {
                durationInput.value = duration;
            }
            if (durationWrapper) {
                durationWrapper.style.display = (frequency && frequency !== 'One-Time') ? 'block' : 'none';
            }

            renewModal.show();
        });
    });

    // Frequency/Duration auto-calculation
    function isOneTimeFrequency() {
        const selectedFrequency = frequencyInput?.value || '';
        return selectedFrequency === '' || selectedFrequency === 'One-Time';
    }

    function toggleDurationField() {
        if (!durationWrapper) return;
        durationWrapper.style.display = isOneTimeFrequency() ? 'none' : 'block';
    }

    function formatDateLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function calculateNewEndDate(startDate, frequency, duration) {
        if (!frequency || frequency === 'One-Time') {
            return '';
        }

        if (!startDate) {
            return '';
        }

        const start = new Date(startDate + 'T00:00:00');
        const end = new Date(start);
        const count = Math.max(1, parseInt(duration, 10) || 1);

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

        return formatDateLocal(end);
    }

    function refreshEndDate() {
        if (!frequencyInput || !durationInput || !endDateInput) return;
        toggleDurationField();

        // Use current end date as starting point
        const currentEndDate = endDateInput.value || currentEndDateDisplay?.textContent;
        if (!currentEndDate || currentEndDate === '-') return;

        const newEndDate = calculateNewEndDate(
            currentEndDate,
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
});
</script>
@endsection
