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
        <i class="fas fa-list btn-icon"></i> Order List
    </a>
    @else
    <a href="{{ route('orders.create', array_filter(['c' => $clientId])) }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Create Orders
    </a>
    @endif
</div>
@endsection

@section('content')
@if(!empty($showClientPicker))
<div class="position-relative d-flex align-items-center justify-content-center"
    style="min-height: calc(100vh - 160px);">
    <div class="row w-100">
        <div class="col-12 col-md-3 mx-auto">
            <div class="mb-5">
                <div class="bg-white p-4 rounded-3 mx-auto mb-5">
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-1">
                        <div class="d-flex align-items-center gap-2">
                            <div>
                                <h5 class="fw-semibold text-black mb-0">Manage Orders</h5>
                                <p class="text-dark mb-0">Choose a client first to load item-based orders.</p>
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
                                Create Order <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-light p-2 rounded-3 mb-3">
        <form action="{{ route('orders.index') }}" method="GET" class="mainForm">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-2">
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

                <div class="col-12 col-md-2">
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

                <div class="col-12 col-md-2 d-flex gap-2">
                    <a href="{{ route('orders.index', ['c' => empty($clientId) ? 'all' : $clientId]) }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium text-center justify-content-center">
                        <i class="fas fa-sync-alt btn-icon me-1"></i> Clear
                    </a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                        <i class="fas fa-filter btn-icon me-1"></i> Filter
                    </button>
                </div>

                <div class="col-12 col-md-6 d-flex justify-content-end align-items-center gap-2 mt-auto">
                    @if(empty($showClientPicker))
                    <a href="{{ route('clients.trials') }}"
                        class="btn btn-sm btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium h-auto">
                        <i class="fas fa-user-clock btn-icon"></i> Prospect Orders
                    </a>
                    @endif
                    <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            id="btn-grid-view">
                            <i class="fas fa-th-large toggle-icon"></i> Grid
                        </button>
                        <button type="button"
                            class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 h-auto"
                            id="btn-list-view">
                            <i class="fas fa-list toggle-icon"></i> List
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @forelse($groupedOrders as $clientName => $clientOrders)
    <div class="card border-0 mb-3">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pb-1 pt-0 px-1">
            <div class="d-flex align-items-center">
                <span class="fw-bold fs-5 lh-sm">{{ $clientName }} ({{ count($clientOrders) }}
                    orders)</span>
                @if(strtolower((string) ($clientOrders[0]['client_type'] ?? 'regular')) === 'trial')
                <span class="status-pill is-pending ms-2">Trial</span>
                @endif
            </div>
        </div>
        <div class="table-responsive orders-list-view">
            <table class="table table-striped border mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Order</th>
                        <th width="40%">Item</th>
                        <th class="text-center" width="15%">Create Date</th>
                        <th class="text-center" width="15%">Expiry</th>
                        <th class="text-end" width="20%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientOrders as $order)
                    <tr>
                        <td class="fw-semibold text-dark">#{{ $order['number'] }}</td>
                        <td>
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                <span class="fw-bold text-dark">{{ $order['items'][0]['item_name'] ?? 'Item' }}</span>
                                @if(!empty($order['items'][0]['item_description']))
                                <button type="button"
                                    class="btn p-0 border-0 bg-transparent btn-desc-toggle d-inline-flex align-items-center"
                                    style="outline: none; box-shadow: none;">
                                    <i class="fas fa-arrow-right text-primary ms-2 desc-toggle-icon"
                                        style="transition: transform 0.2s ease; font-size: 0.8rem;"></i>
                                </button>
                                @endif
                            </div>
                            @if(!empty($order['items'][0]['item_description']))
                            <div class="text-dark mt-1 d-none desc-container">{{
                                $order['items'][0]['item_description'] }}</div>
                            @endif
                            <div class="d-flex flex-wrap text-black mt-2">
                                <div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                    <small>Qty:</small>
                                    <span class="fw-semibold">{{
                                        rtrim(rtrim(number_format((float) ($order['items'][0]['quantity']
                                        ?? 1), 2, '.',
                                        ''), '0'), '.') }}</span>
                                </div>
                                @if(!empty($order['items'][0]['no_of_users']))
                                <div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                    <small>Users:</small>
                                    <span class="fw-semibold">{{ $order['items'][0]['no_of_users'] }}</span>
                                </div>
                                @endif
                                @if(!empty($order['items'][0]['delivery_date']))
                                <div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1">
                                    <small>Delivery Date:</small> <span class="fw-semibold">{{
                                        \Carbon\Carbon::parse($order['items'][0]['delivery_date'])->format('d M Y')
                                        }}</span>
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="text-center">{{ !empty($order['items'][0]['start_date']) ? \Carbon\Carbon::parse($order['items'][0]['start_date'])->format('d M Y') : '-' }}</td>
                        <td class=" text-center">
                            @php
                            $orderEndDate = $order['items'][0]['end_date'] ?? null;

                            $showDays = $orderEndDate
                            && !in_array($orderEndDate, ['9999-12-31', '2099-12-31']);

                            $daysLeft = null;

                            if ($showDays) {
                            $daysLeft = now()->startOfDay()->diffInDays(
                            \Carbon\Carbon::parse($orderEndDate)->startOfDay(),
                            false
                            );
                            }
                            @endphp

                            {{ $orderEndDate ? \Carbon\Carbon::parse($orderEndDate)->format('d M Y') : '-' }}

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
                                <button type="button" class="bg01 color01 border-0 js-view-timeline-btn"
                                    data-order-id="{{ $order['record_id'] }}"
                                    data-order-number="{{ $order['number'] }}">
                                    Timeline
                                </button>
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
                                <button type="button" class="bg03 color03 border-0 js-edit-order-btn"
                                    data-order-id="{{ $order['record_id'] }}" data-order-number="{{ $order['number'] }}"
                                    data-client-id="{{ $order['clientid'] }}" data-client-name="{{ $order['client'] }}"
                                    data-item-id="{{ $order['itemid'] ?? '' }}"
                                    data-item-name="{{ $order['items'][0]['item_name'] ?? '' }}"
                                    data-item-description="{{ $order['items'][0]['item_description'] ?? '' }}"
                                    data-quantity="{{ $order['items'][0]['quantity'] ?? 1 }}"
                                    data-no-of-users="{{ $order['items'][0]['no_of_users'] ?? '' }}"
                                    data-start-date="{{ $order['items'][0]['start_date'] ?? '' }}"
                                    data-end-date="{{ $order['items'][0]['end_date'] ?? '' }}"
                                    data-delivery-date="{{ $order['items'][0]['delivery_date'] ?? '' }}"
                                    data-client-docid="{{ $order['client_docid'] ?? '' }}">
                                    Edit
                                </button>
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

        <div
            class="orders-grid-view row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 bg-light rounded-bottom-3 d-none mt-1">
            @foreach($clientOrders as $order)
            <div class="col">
                <div class="card h-100 border-0 overflow-hidden">
                    <div class="card-body p-3 d-flex flex-column justify-content-between"
                        style="background-color: #fff; border-radius: 8px;">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small fw-semibold text-truncate" style="max-width: 120px;">#{{
                                    $order['number'] }}</span>
                                @if(($order['status'] ?? '') === 'cancelled')
                                <span
                                    class="status-pill rounded-pill border border-danger text-danger bg-light is-cancelled py-0 px-2 small"
                                    style="font-size: 11px;">Cancelled</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-bold text-dark mb-0 lh-sm"
                                    title="{{ $order['items'][0]['item_name'] ?? 'Item' }}" style="max-width: 85%;">
                                    {{ $order['items'][0]['item_name'] ?? 'Item' }}
                                </h6>
                                @if(!empty($order['items'][0]['item_description']))
                                <button type="button"
                                    class="btn p-0 border-0 bg-transparent btn-desc-toggle d-inline-flex align-items-center"
                                    style="outline: none; box-shadow: none;">
                                    <i class="fas fa-arrow-right text-primary desc-toggle-icon"
                                        style="transition: transform 0.2s ease; font-size: 0.8rem;"></i>
                                </button>
                                @endif
                            </div>
                            @if(!empty($order['items'][0]['item_description']))
                            <span class="d-block text-dark lh-sm text-break grid-text-medium d-none desc-container mt-1"
                                style="min-height: 32px;" title="{{ $order['items'][0]['item_description'] }}">
                                {{ $order['items'][0]['item_description'] }}
                            </span>
                            @else

                            @endif

                            <div class="bg-light rounded-3 px-2 py-2 grid-text-medium mb-2 mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Quantity</span>
                                    <strong class="text-dark fw-semibold">{{ rtrim(rtrim(number_format((float)
                                        ($order['items'][0]['quantity'] ?? 1), 2, '.', ''), '0'), '.') }}</strong>
                                </div>
                                @if(!empty($order['items'][0]['no_of_users']))
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Users</span>
                                    <strong class="text-dark fw-semibold">{{ $order['items'][0]['no_of_users']
                                        }}</strong>
                                </div>
                                @endif
                                @if(!empty($order['items'][0]['delivery_date']))
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Delivery Date</span>
                                    <strong class="text-dark fw-semibold">{{ \Carbon\Carbon::parse($order['items'][0]['delivery_date'])->format('d M Y') }}</strong>
                                </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted small">Create Date</span>
                                    <strong class="text-dark fw-semibold">{{ !empty($order['items'][0]['start_date']) ? \Carbon\Carbon::parse($order['items'][0]['start_date'])->format('d M Y') : '-' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Expiry</span>
                                    <div class="text-end">
                                        <strong class="text-dark fw-semibold d-block">{{ !empty($order['items'][0]['end_date']) ? \Carbon\Carbon::parse($order['items'][0]['end_date'])->format('d M Y') : '-' }}</strong>
                                        @php
                                        $orderEndDate = $order['items'][0]['end_date'] ?? null;
                                        $showDays = $orderEndDate && !in_array($orderEndDate, ['9999-12-31',
                                        '2099-12-31']);
                                        $daysLeft = null;
                                        if ($showDays) {
                                        $daysLeft =
                                        now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($orderEndDate)->startOfDay(),
                                        false);
                                        }
                                        @endphp
                                        @if($showDays)
                                        @if($daysLeft >= 0)
                                        <small class="text-success small lh-sm fw-semibold">{{ $daysLeft }}
                                            day(s)</small>
                                        @else
                                        <small class="text-danger small lh-sm fw-semibold">- {{ abs($daysLeft) }}
                                            day(s)</small>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                            <button type="button"
                                class="bg-timeline color-timeline border-0 text-center js-view-timeline-btn flex-grow-1"
                                data-order-id="{{ $order['record_id'] }}" data-order-number="{{ $order['number'] }}">
                                Timeline
                            </button>
                            @if(!empty($order['items'][0]['end_date']) &&
                            \Carbon\Carbon::parse($order['items'][0]['end_date'])->isPast())
                            <button type="button"
                                class="bg02 color02 border-0 text-center js-renew-order-btn flex-grow-1"
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
                            <button type="button"
                                class="bg03 color03 border-0 text-center js-edit-order-btn flex-grow-1"
                                data-order-id="{{ $order['record_id'] }}" data-order-number="{{ $order['number'] }}"
                                data-client-id="{{ $order['clientid'] }}" data-client-name="{{ $order['client'] }}"
                                data-item-id="{{ $order['itemid'] ?? '' }}"
                                data-item-name="{{ $order['items'][0]['item_name'] ?? '' }}"
                                data-item-description="{{ $order['items'][0]['item_description'] ?? '' }}"
                                data-quantity="{{ $order['items'][0]['quantity'] ?? 1 }}"
                                data-no-of-users="{{ $order['items'][0]['no_of_users'] ?? '' }}"
                                data-start-date="{{ $order['items'][0]['start_date'] ?? '' }}"
                                data-end-date="{{ $order['items'][0]['end_date'] ?? '' }}"
                                data-delivery-date="{{ $order['items'][0]['delivery_date'] ?? '' }}"
                                data-client-docid="{{ $order['client_docid'] ?? '' }}">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('orders.destroy', ['order' => $order['record_id']]) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Cancel this order?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg04 color04 text-center w-100">Cancel</button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('orders.restore', ['order' => $order['record_id']]) }}"
                                class="d-inline flex-grow-1" onsubmit="return confirm('Restore this order?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg02 color02 text-center w-100">Restore</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
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

@include('orders.partials.edit-order-modal')

@include('orders.partials.order-timeline-modal')

<script>
    window.__editModalConfig = {
        clientDocuments: @json($clientDocuments ?? []),
        todayStr: '{{ now()->format('Y- m - d') }}',
            renewRouteTemplate: '{{ route('invoices.orders.renew', ['order' => '__ORDER__']) }}',
                selectedClientId: '{{ $clientId ?? request('c') }}',
    };
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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

        // View Toggle Logic
        const btnList = document.getElementById('btn-list-view');
        const btnGrid = document.getElementById('btn-grid-view');
        const listViews = document.querySelectorAll('.orders-list-view');
        const gridViews = document.querySelectorAll('.orders-grid-view');

        function setView(viewType) {
            if (viewType === 'grid') {
                listViews.forEach(el => el.classList.add('d-none'));
                gridViews.forEach(el => el.classList.remove('d-none'));
                btnList.classList.remove('active', 'btn-primary');
                btnList.classList.add('btn-outline-primary');
                btnGrid.classList.add('active', 'btn-primary');
                btnGrid.classList.remove('btn-outline-primary');
                localStorage.setItem('orders_index_view_preference', 'grid');
            } else {
                listViews.forEach(el => el.classList.remove('d-none'));
                gridViews.forEach(el => el.classList.add('d-none'));
                btnList.classList.add('active', 'btn-primary');
                btnList.classList.remove('btn-outline-primary');
                btnGrid.classList.remove('active', 'btn-primary');
                btnGrid.classList.add('btn-outline-primary');
                localStorage.setItem('orders_index_view_preference', 'list');
            }
        }

        if (btnList && btnGrid) {
            btnList.addEventListener('click', () => setView('list'));
            btnGrid.addEventListener('click', () => setView('grid'));

            const savedPref = localStorage.getItem('orders_index_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }

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
        const renewRouteTemplate = window.__editModalConfig?.renewRouteTemplate || '';
        const selectedClientId = window.__editModalConfig?.selectedClientId || '';
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
                const orderNo = this.dataset.orderNumber || '';
                setText(orderNumber, orderNo ? '#' + orderNo : '-');
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
                    frequencyInput.value = frequency || 'One-Time';
                    frequencyInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (durationInput) {
                    durationInput.value = duration;
                    durationInput.disabled = !frequency || frequency === 'One-Time';
                }

                renewModal.show();
            });
        });

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
