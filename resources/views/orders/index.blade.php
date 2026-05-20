@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        @if($clientId || !empty($selectedItemId) || !empty($hasClientFilter))
            <a href="{{ route('orders.create', array_filter(['c' => $clientId])) }}" class="primary-button">
                <i class="fas fa-plus icon-spaced"></i>Create Orders
            </a>
        @endif
    </div>
@endsection

@section('content')
<div class="order-index-shell">
    @if(!empty($showClientPicker))
        <div class="payment-client-picker-wrap">
            <div class="payment-client-picker">
                <div class="payment-client-picker-head">
                    <div class="payment-client-picker-title">
                        <div class="payment-client-picker-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <strong>Manage Orders</strong>
                            <p>Choose a client first to load item-based orders.</p>
                        </div>
                    </div>
                    <span class="payment-client-count">{{ $allClients->count() }} client(s)</span>
                </div>
                <form action="{{ route('orders.index') }}" method="GET" class="payment-client-picker-form">
                    <div class="payment-client-picker-field">
                        <label for="client-select">Client</label>
                        <select name="c" id="client-select" class="form-control" autofocus>
                            <option value="" selected disabled>Select a client</option>
                            @foreach($allClients as $client)
                                <option value="{{ $client->clientid }}">{{ $client->business_name ?? $client->contact_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="payment-client-picker-actions">
                        <button type="submit" class="secondary-button action-btn-lg">
                            <i class="fas fa-list icon-spaced"></i> View Orders
                        </button>
                        <button type="button" id="btnCreateOrderFromPicker" class="primary-button action-btn-lg">
                            <i class="fas fa-plus icon-spaced"></i> Create Orders
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <section class="panel-card module-filter-panel filter-panel-regular">
            <form action="{{ route('orders.index') }}" method="GET" class="module-filter-grid">
                <div class="module-filter-field">
                    <label class="module-filter-label" for="orders_client_filter">Client</label>
                    <select name="c" id="orders_client_filter" class="form-control">
                        <option value="all" {{ empty($clientId) ? 'selected' : '' }}>All Clients</option>
                        @foreach($allClients as $client)
                            <option value="{{ $client->clientid }}" {{ (string) $clientId === (string) $client->clientid ? 'selected' : '' }}>
                                {{ $client->business_name ?? $client->contact_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="module-filter-field">
                    <label class="module-filter-label" for="orders_item_filter">Product</label>
                    <select name="itemid" id="orders_item_filter" class="form-control">
                        <option value="">All Products</option>
                        @php
                            $servicesByCategory = collect($services ?? [])->groupBy(function ($service) {
                                return $service->category?->name ?? 'Uncategorized';
                            })->sortKeys();
                        @endphp
                        @foreach($servicesByCategory as $categoryName => $categoryServices)
                            <optgroup label="{{ $categoryName }}">
                                @foreach($categoryServices as $service)
                                    <option value="{{ $service->itemid }}" {{ (string) ($selectedItemId ?? '') === (string) $service->itemid ? 'selected' : '' }}>
                                        {{ $service->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="module-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('orders.index', ['c' => empty($clientId) ? 'all' : $clientId]) }}" class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        @forelse($groupedOrders as $clientName => $clientOrders)
            <section class="order-group">
                <div class="order-group-head">
                    <span class="order-client-meta">
                        <span class="category-title">{{ $clientName }}</span>
                    </span>
                    <span class="order-client-summary">
                        <span class="service-count">{{ count($clientOrders) }} order(s)</span>
                    </span>
                </div>
                <div class="order-table-wrap">
                    <table class="data-table table-no-margin">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Item</th>
                                <th>Create Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientOrders as $order)
                                <tr>
                                    <td>{{ $order['number'] }}</td>
                                    <td>
                                        <strong>{{ $order['items'][0]['item_name'] ?? 'Item' }}</strong>
                                        @if(!empty($order['items'][0]['item_description']))
                                            <div class="text-xs text-muted mt-1">{{ $order['items'][0]['item_description'] }}</div>
                                        @endif
                                        <div class="text-xs text-muted mt-1">
                                            Qty: {{ rtrim(rtrim(number_format((float) ($order['items'][0]['quantity'] ?? 1), 2, '.', ''), '0'), '.') }}
                                            @if(!empty($order['items'][0]['no_of_users']))
                                                | Users: {{ $order['items'][0]['no_of_users'] }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $order['items'][0]['start_date'] ?? '-' }}</td>
                                    <td>
                                        @php
                                            $orderEndDate = $order['items'][0]['end_date'] ?? null;
                                            $isOrderExpired = !empty($orderEndDate) && \Carbon\Carbon::parse($orderEndDate)->lt(now()->startOfDay());
                                        @endphp
                                        <span class="{{ $isOrderExpired ? 'text-danger' : '' }}" style="{{ $isOrderExpired ? 'font-weight: 600;' : '' }}">
                                            {{ $orderEndDate ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(($order['status'] ?? '') === 'cancelled')
                                            <span class="status-pill status-pill-cancelled">Cancelled</span>
                                        @elseif(($order['status'] ?? '') === 'suspended')
                                            <span class="status-pill status-pill-pending">Suspended</span>
                                        @elseif(($order['status'] ?? '') === 'completed')
                                            <span class="status-pill status-pill-completed">Completed</span>
                                        @else
                                            <span class="status-pill status-pill-running">{{ ($order['status'] ?? '') === 'running' ? 'Active' : ucfirst($order['status'] ?? 'active') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            @if(!empty($order['items'][0]['end_date']) && \Carbon\Carbon::parse($order['items'][0]['end_date'])->isPast())
                                                <button
                                                    type="button"
                                                    class="order-create-pi-link order-pill-created js-renew-order-btn"
                                                    data-order-id="{{ $order['record_id'] }}"
                                                    data-order-number="{{ $order['number'] }}"
                                                    data-client-name="{{ $order['client'] }}"
                                                    data-invoice-number="-"
                                                    data-item-name="{{ $order['items'][0]['item_name'] ?? 'Item' }}"
                                                    data-item-description="{{ $order['items'][0]['item_description'] ?? '' }}"
                                                    data-start-date="{{ $order['items'][0]['start_date'] ?? '-' }}"
                                                    data-end-date-display="{{ $order['items'][0]['end_date'] ?? '-' }}"
                                                    data-days-left="{{ !empty($order['items'][0]['end_date']) ? \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($order['items'][0]['end_date'])->startOfDay(), false) : '' }}"
                                                    data-status="{{ ($order['status'] ?? '') === 'running' ? 'Active' : ucfirst($order['status'] ?? 'active') }}"
                                                    data-end-date="{{ $order['items'][0]['end_date'] ?? '' }}"
                                                    data-client-id="{{ $order['clientid'] }}"
                                                    data-frequency="{{ $order['items'][0]['frequency'] ?? '' }}"
                                                    data-duration="{{ $order['items'][0]['duration'] ?? 1 }}"
                                                >
                                                    Renew
                                                </button>
                                            @endif
                                            @if(($order['status'] ?? '') !== 'cancelled')
                                                <a href="{{ route('orders.edit', ['order' => $order['record_id']]) }}" class="text-action-btn edit">Edit</a>
                                                <form method="POST" action="{{ route('orders.destroy', ['order' => $order['record_id']]) }}" class="inline-delete" onsubmit="return confirm('Cancel this order?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-action-btn delete">Cancel</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('orders.restore', ['order' => $order['record_id']]) }}" class="inline-delete" onsubmit="return confirm('Restore this order?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-action-btn secondary">Restore</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="order-empty">
                <i class="fas fa-receipt empty-state-icon"></i>
                <p class="no-empty-state-text">No orders found</p>
                <p class="small-text">Create your first order to get started.</p>
            </div>
        @endforelse
    @endif
</div>

@include('invoices.partials.renew-order-modal')

<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientPickerSelect = document.getElementById('client-select');
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
            endDateInput.value = this.dataset.endDate || '';
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
            endDateInput.value = newEndDate;
            endDateInput.setAttribute('value', newEndDate);
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
