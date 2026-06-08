@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('orders.index', ['c' => 'all']) }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-receipt btn-icon"></i> All Orders
    </a>
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-3 rounded-3 shadow-sm">

    <!-- Filters -->
    <div class="position-relative bg-light border p-3 rounded-3 mb-2">
        <form action="{{ route('orders.trials') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trial_orders_search">Search</label>
                    <input type="text" name="search" id="trial_orders_search" class="form-control"
                        value="{{ $searchTerm ?? '' }}" placeholder="Client name or item name">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trial_orders_client">Client</label>
                    <select name="client" id="trial_orders_client" class="form-select">
                        <option value="">All Clients</option>
                        @foreach ($clientOptions as $option)
                        <option value="{{ $option->clientid }}" {{ isset($selectedClient) && $selectedClient===$option->
                            clientid ? 'selected' : '' }}>
                            {{ $option->business_name ?? $option->contact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trial_orders_item">Item</label>
                    <select name="item" id="trial_orders_item" class="form-select">
                        <option value="">All Items</option>
                        @foreach ($itemOptions as $item)
                        <option value="{{ $item }}" {{ isset($selectedItem) && $selectedItem===$item ? 'selected' : ''
                            }}>
                            {{ $item }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('orders.trials') }}"
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

    <!-- View Toggle -->
    <div class="d-flex justify-content-end mb-2">
        <div class="btn-group shadow-sm" role="group" aria-label="View Toggle">
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                id="btn-grid-view">
                <i class="fas fa-th-large toggle-icon"></i> Grid
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                id="btn-list-view">
                <i class="fas fa-list toggle-icon"></i> List
            </button>
        </div>
    </div>

    <!-- List View -->
    <div id="trial-orders-list-view" class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table mainTable border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Item</th>
                        <th>Start Date</th>
                        <th>Expiry Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($order['client'], 0,
                                        2)) }}</span>
                                    <div class="status-dot {{ $order['client_status'] }}"
                                        title="{{ ucfirst($order['client_status']) }}"></div>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold">{!! isset($searchTerm) && $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm
                                            . '</mark>', $order['client'])
                                        : $order['client'] !!}</span>
                                    @if ($order['client_email'])
                                    <span class="d-block text-muted small">{{ $order['client_email'] }}</span>
                                    @endif
                                    @if ($order['client_phone'])
                                    <span class="d-block text-muted small">{{ $order['client_phone'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary fw-medium">{!! isset($searchTerm) &&
                                $searchTerm
                                ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                    '</mark>', $order['item_name'])
                                : $order['item_name'] !!}</span>
                            <div class="text-muted small mt-1">#{{ $order['number'] }}</div>
                        </td>
                        <td>
                            <span class="small">{{ $order['start_date'] ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="small {{ $order['is_expired'] ? 'text-danger fw-semibold' : '' }}">
                                {{ $order['end_date'] ?? '—' }}
                            </span>
                            @if ($order['is_expired'])
                            <div><span class="app-badge app-badge--red">Expired</span></div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <a href="{{ route('orders.edit', ['order' => $order['record_id'], 'return_to' => 'trials']) }}"
                                    class="bg03 color03">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-receipt mb-3 text-secondary fs-1 opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">No trial orders found</p>
                            <p class="small text-muted mb-0">There are currently no orders from trial clients.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid View -->
    <div id="trial-orders-grid-view" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3 mb-4 d-none">
        @forelse ($orders as $order)
        <div class="col">
            <div class="card h-100 border overflow-hidden">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Client avatar + info -->
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div
                                class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                <span class="d-block position-absolute">{{ strtoupper(substr($order['client'], 0, 2))
                                    }}</span>
                                <div class="status-dot {{ $order['client_status'] }}"
                                    title="{{ ucfirst($order['client_status']) }}"></div>
                            </div>
                            <div class="flex-grow-1 min-w-0 ps-2">
                                <h6 class="fw-bold text-dark mb-0 text-truncate lh-sm" title="{{ $order['client'] }}">
                                    {!! isset($searchTerm) && $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                        '</mark>', $order['client'])
                                    : $order['client'] !!}</h6>
                                @if ($order['client_email'])
                                <span class="d-block text-muted lh-sm text-break grid-text-medium">{{
                                    $order['client_email'] }}</span>
                                @endif
                                @if ($order['client_phone'])
                                <span class="d-block text-muted lh-sm grid-text-medium">{{ $order['client_phone']
                                    }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Item & dates -->
                        <div class="border-top pt-2 grid-text-medium">
                            <div class="mb-1">
                                <span class="badge bg-primary-subtle text-primary fw-medium">{!! isset($searchTerm) &&
                                    $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                        '</mark>', $order['item_name'])
                                    : $order['item_name'] !!}</span>
                            </div>
                            <div class="text-muted mb-1">
                                <i class="fas fa-play contact-icon me-1"></i>{{ $order['start_date'] ?? '—' }}
                            </div>
                            <div class="{{ $order['is_expired'] ? 'text-danger fw-semibold' : 'text-muted' }}">
                                <i class="fas fa-clock contact-icon me-1"></i>Exp: {{ $order['end_date'] ?? '—' }}
                            </div>
                            <div class="mt-1">
                                @if (strtolower($order['status']) === 'cancelled')
                                <span class="app-badge app-badge--red">Cancelled</span>
                                @elseif ($order['is_expired'])
                                <span class="app-badge app-badge--red">Expired</span>
                                @elseif (in_array(strtolower($order['status']), ['active', 'running', 'completed'],
                                true))
                                <span class="app-badge app-badge--green">{{ strtolower($order['status']) === 'running' ?
                                    'Active' : ucfirst($order['status']) }}</span>
                                @else
                                <span class="app-badge app-badge--gray">{{ ucfirst($order['status'] ?? 'Active')
                                    }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="tableActionButton d-flex flex-wrap gap-1 mt-3">
                        <a href="{{ route('clients.dashboard', $order['clientid']) }}"
                            class="bg01 color01 flex-grow-1 text-center">View Client</a>
                        <a href="{{ route('orders.edit', ['order' => $order['record_id'], 'return_to' => 'trials']) }}"
                            class="bg03 color03 flex-grow-1 text-center">Edit</a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 w-100">
            <div class="card border-0 shadow-sm py-5 text-center text-muted">
                <div class="card-body">
                    <i class="fas fa-receipt mb-3 text-secondary fs-1 opacity-50"></i>
                    <p class="fw-semibold text-dark mb-1">No trial orders found</p>
                    <p class="small text-muted mb-0">There are currently no orders from trial clients.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnList = document.getElementById('btn-list-view');
        const btnGrid = document.getElementById('btn-grid-view');
        const listView = document.getElementById('trial-orders-list-view');
        const gridView = document.getElementById('trial-orders-grid-view');

        function setView(viewType) {
            if (viewType === 'grid') {
                listView.classList.add('d-none');
                gridView.classList.remove('d-none');
                btnList.classList.remove('active', 'btn-primary');
                btnList.classList.add('btn-outline-primary');
                btnGrid.classList.add('active', 'btn-primary');
                btnGrid.classList.remove('btn-outline-primary');
                localStorage.setItem('trial_orders_view_preference', 'grid');
            } else {
                listView.classList.remove('d-none');
                gridView.classList.add('d-none');
                btnList.classList.add('active', 'btn-primary');
                btnList.classList.remove('btn-outline-primary');
                btnGrid.classList.remove('active', 'btn-primary');
                btnGrid.classList.add('btn-outline-primary');
                localStorage.setItem('trial_orders_view_preference', 'list');
            }
        }

        if (btnList && btnGrid && listView && gridView) {
            btnList.addEventListener('click', () => setView('list'));
            btnGrid.addEventListener('click', () => setView('grid'));

            const savedPref = localStorage.getItem('trial_orders_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }
    });
</script>
@endsection
