@extends('layouts.app')

@section('header_actions')
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('clients.trials') }}" method="GET" class="mainForm">
            <div class="row g-2">

                <div class="col-12 col-md-2">
                    <select name="client" id="trials_client_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Clients</option>
                        @foreach ($clientOptions as $option)
                        <option value="{{ $option->clientid }}" {{ isset($selectedClient) && $selectedClient===$option->
                            clientid ? 'selected' : '' }}>
                            {{ $option->business_name ?? $option->contact_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <select name="item" id="trials_item_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Items</option>
                        @foreach ($itemOptions as $item)
                        <option value="{{ $item }}" {{ isset($selectedItem) && $selectedItem===$item ? 'selected' : ''
                            }}>
                            {{ $item }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute text-muted"
                            style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                        <input type="text" name="search" id="trials_search_filter" class="form-control"
                            value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person"
                            style="padding-left: 38px;" onchange="this.form.submit()">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- View Toggle Bar & Legend -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center align-self-end gap-3 small text-dark px-2">
            <div class="d-flex align-items-center">
                <span class="status-dot legend-dot active"></span> Active
            </div>
            <div class="d-flex align-items-center">
                <span class="status-dot legend-dot inactive"></span> Inactive
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('orders.index', ['c' => 'all']) }}"
                class="btn btn-sm btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-shopping-cart btn-icon"></i> Regular Orders
            </a>
            <a href="{{ route('clients.index') }}"
                class="btn btn-sm btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-users btn-icon"></i> Regular Clients
            </a>
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
    </div>

    <!-- Table View (List View) -->
    <div id="clients-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="20%">Client Name & Contact Details</th>
                        <th>Item Details</th>
                        <th class="text-end" width="35%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    <span class="d-block position-absolute">{{ strtoupper(substr($client['name'], 0, 2))
                                        }}</span>
                                    <div class="status-dot {{ strtolower($client['status']) }}"
                                        title="{{ ucfirst($client['status']) }}"></div>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold">{!! isset($searchTerm) && $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm
                                            . '</mark>', $client['name'])
                                        : $client['name'] !!}</span>
                                    <span class="d-block text-dark small">{{ $client['email'] }}</span>
                                    <span class="d-block text-dark small">{{ $client['phone'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @forelse ($client['orders_data'] as $orderData)
                            @php $item = $orderData['items'][0] ?? null; @endphp
                            @if($item)
                            <span
                                class="bg-DarkLight rounded-2 p-2 d-flex align-items-center gap-0 flex-wrap justify-content-between my-1">
                                <span class="fw-semibold d-block align-self-center">{{ $item['item_name'] }}</span>

                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="input-group input-group-sm"
                                            style="width: 130px; display: inline-flex; height: 30px; min-height: 30px;">
                                            <input type="date"
                                                class="form-control form-control-sm text-dark fw-semibold py-0 px-1.5 rounded-start-1 rounded-end-0"
                                                style="height: 30px; font-size: 0.8rem; min-height: 30px;"
                                                value="{{ $client['created_at'] ? \Carbon\Carbon::parse($client['created_at'])->format('Y-m-d') : '' }}"
                                                disabled title="Registration Date">
                                            <span
                                                class="input-group-text bg-white px-1.5 py-0 rounded-end-1 rounded-start-0"><i
                                                    class="far fa-calendar-alt text-secondary"
                                                    style="font-size: 0.7rem;"></i></span>
                                        </div>
                                    </div>

                                    <div class="align-self-end mb-1">
                                        <span class="text-muted small">-</span>
                                    </div>

                                    <div class="d-flex flex-column align-items-center">
                                        @if ($orderData['record_id'])
                                        <div class="input-group input-group-sm"
                                            style="width: 130px; display: inline-flex; height: 24px; min-height: 24px;">
                                            <input type="date"
                                                class="form-control form-control-sm trial-expiry-input text-danger fw-semibold py-0 px-1.5 rounded-start-1 rounded-end-0"
                                                style="height: 30px; font-size: 0.8rem; min-height: 30px;"
                                                value="{{ $item['end_date'] ? \Carbon\Carbon::parse($item['end_date'])->format('Y-m-d') : '' }}"
                                                min="{{ $client['created_at'] ? \Carbon\Carbon::parse($client['created_at'])->format('Y-m-d') : '' }}"
                                                data-order-id="{{ $orderData['record_id'] }}"
                                                title="Edit trial expiry date">
                                            <span
                                                class="input-group-text bg-white px-1.5 py-0 rounded-end-1 rounded-start-0"><i
                                                    class="far fa-calendar-alt text-secondary"
                                                    style="font-size: 0.7rem;"></i></span>
                                        </div>
                                        @else
                                        @if ($item['end_date'])
                                        <span class="text-danger fw-semibold small lh-sm mt-1">({{
                                            \Carbon\Carbon::parse($item['end_date'])->format('d M Y') }})</span>
                                        @else
                                        <span class="text-muted small mt-1">&#8212;</span>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </span>
                            @endif
                            @empty
                            <span class="text-muted">&#8212;</span>
                            @endforelse
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <button type="button" class="bg01 color01 border-0 js-view-orders-btn"
                                    data-client-record-id="{{ $client['record_id'] }}">View Orders</button>
                                <a href="{{ route('quotations.create', ['step' => 2, 'c' => $client['record_id']]) }}"
                                    class="bg03 color03">Create
                                    Quotation</a>
                                <form method="POST"
                                    action="{{ route('clients.convert-to-regular', $client['record_id']) }}"
                                    class="d-inline" data-name="{{ $client['name'] }}"
                                    onsubmit="return confirm('Convert ' + this.dataset.name + ' to regular client?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bg02 color02">Prospect to Regular</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="fas fa-user-clock mb-3 text-secondary fs-1 opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">No trial clients found</p>
                            <p class="small text-muted mb-0">There are currently no active trial clients.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid View -->
    <div id="clients-grid-view"
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none">
        @forelse ($clients as $client)
        <div class="col">
            <div class="card h-100 border-0 overflow-hidden">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Flex Avatar, Info -->
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div
                                class="tablePrifix position-relative align-self-start bg-primary-subtle text-primary rounded-circle fw-semibold shrink-0">
                                <span class="d-block position-absolute">{{ strtoupper(substr($client['name'], 0, 2))
                                    }}</span>
                                <div class="status-dot {{ strtolower($client['status']) }}"
                                    title="{{ ucfirst($client['status']) }}"></div>
                            </div>
                            <div class="grow min-w-0 ps-2">
                                <h6 class="fw-bold text-dark mb-0 text-truncate lh-sm" title="{{ $client['name'] }}">
                                    {!! isset($searchTerm) && $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                        '</mark>', $client['name'])
                                    : $client['name'] !!}
                                </h6>
                                <span class="d-block text-dark lh-sm text-break grid-text-medium mt-1"
                                    title="{{ $client['email'] }}">{{ $client['email'] }}</span>
                                <span class="d-block text-dark lh-sm text-break grid-text-medium mt-1"
                                    title="{{ $client['phone'] ?? '—' }}">{{ $client['phone'] ?? '—' }}</span>
                            </div>
                        </div>

                        <!-- Item & Date & Contact details -->
                        <div class="mb-2 border-top pt-2 grid-text-medium">
                            <!-- Item Details -->
                            <div class="mb-2">
                                @forelse ($client['orders_data'] as $orderData)
                                @php $item = $orderData['items'][0] ?? null; @endphp
                                @if($item)
                                <span
                                    class="bg-light rounded-2 p-2 d-flex align-items-center gap-0 flex-wrap justify-content-between my-1">
                                    <span class="fw-semibold">{{ $item['item_name'] }}</span>

                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="input-group input-group-sm"
                                                style="width: 130px; display: inline-flex; height: 24px; min-height: 24px;">
                                                <input type="date"
                                                    class="form-control form-control-sm text-dark fw-semibold py-0 px-1.5 rounded-start-1 rounded-end-0"
                                                    style="height: 30px; font-size: 0.8rem; min-height: 30px;"
                                                    value="{{ $client['created_at'] ? \Carbon\Carbon::parse($client['created_at'])->format('Y-m-d') : '' }}"
                                                    disabled title="Registration Date">
                                                <span
                                                    class="input-group-text bg-white px-1.5 py-0 rounded-end-1 rounded-start-0"><i
                                                        class="far fa-calendar-alt text-secondary"
                                                        style="font-size: 0.7rem;"></i></span>
                                            </div>
                                        </div>

                                        <div class="align-self-end mb-1">
                                            <span class="text-muted small">-</span>
                                        </div>

                                        <div class="d-flex flex-column align-items-center">
                                            @if ($orderData['record_id'])
                                            <div class="input-group input-group-sm"
                                                style="width: 130px; display: inline-flex; height: 24px; min-height: 24px;">
                                                <input type="date"
                                                    class="form-control form-control-sm trial-expiry-input text-danger fw-semibold py-0 px-1.5 rounded-start-1 rounded-end-0"
                                                    style="height: 30px; font-size: 0.8rem; min-height: 30px;"
                                                    value="{{ $item['end_date'] ? \Carbon\Carbon::parse($item['end_date'])->format('Y-m-d') : '' }}"
                                                    min="{{ $client['created_at'] ? \Carbon\Carbon::parse($client['created_at'])->format('Y-m-d') : '' }}"
                                                    data-order-id="{{ $orderData['record_id'] }}"
                                                    title="Edit trial expiry date">
                                                <span
                                                    class="input-group-text bg-white px-1.5 py-0 rounded-end-1 rounded-start-0"><i
                                                        class="far fa-calendar-alt text-secondary"
                                                        style="font-size: 0.7rem;"></i></span>
                                            </div>
                                            @else
                                            @if ($item['end_date'])
                                            <span class="text-danger fw-semibold small lh-sm mt-1">({{
                                                \Carbon\Carbon::parse($item['end_date'])->format('d M Y')
                                                }})</span>
                                            @else
                                            <span class="text-muted small mt-1">&#8212;</span>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </span>
                                @endif
                                @empty
                                <span class="text-muted small">&#8212;</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                        <button type="button" class="bg01 color01 text-center border-0 js-view-orders-btn"
                            data-client-record-id="{{ $client['record_id'] }}">View Orders</button>
                        <a href="{{ route('quotations.create', ['step' => 2, 'c' => $client['record_id']]) }}"
                            class="bg03 color03 text-center">Create Quotation</a>
                        <form method="POST" action="{{ route('clients.convert-to-regular', $client['record_id']) }}"
                            class="d-inline" data-name="{{ $client['name'] }}"
                            onsubmit="return confirm('Convert ' + this.dataset.name + ' to regular client?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg02 color02 text-center">Convert to Regular</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 w-100">
            <div class="card py-5 text-center text-muted">
                <div class="card-body">
                    <i class="fas fa-user-clock mb-3 text-secondary fs-1 opacity-50"></i>
                    <p class="fw-semibold text-dark mb-1">No trial clients found</p>
                    <p class="small text-muted mb-0">There are currently no active trial clients.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>

<script type="application/json" id="trials-clients-data">
{!! json_encode($clients->map(fn ($c) => [
    'record_id' => $c['record_id'],
    'name' => $c['name'],
    'orders_data' => $c['orders_data'],
])) !!}
</script>

<!-- View Orders Modal -->
<div class="modal fade" id="viewOrdersModal" tabindex="-1" aria-labelledby="viewOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-0 py-2">
                <h5 class="modal-title fw-semibold" id="viewOrdersModalLabel">
                    <span id="modalClientName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-3">
                <div class="card border-0">
                    <div class="table-responsive">
                        <table class="table table-striped border mainTable align-middle mb-0" id="modalOrdersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="10%">Order #</th>
                                    <th width="50%">Item</th>
                                    <th class="text-center" width="20%">Create Date</th>
                                    <th class="text-center" width="20%">Expiry</th>
                                </tr>
                            </thead>
                            <tbody id="modalOrdersBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const clientsData = JSON.parse(document.getElementById('trials-clients-data').textContent || '[]');

    function formatQty(qty) {
        qty = parseFloat(qty) || 1;
        return String(qty % 1 === 0 ? qty : qty.toFixed(2).replace(/\.?0+$/, ''));
    }

    function escapeHtml(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function formatDateToDisplay(dateStr) {
        if (!dateStr) return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const year = parts[0];
        const monthIndex = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        if (monthIndex < 0 || monthIndex > 11 || isNaN(day)) return dateStr;
        const dayStr = String(day).padStart(2, '0');
        return `${dayStr} ${months[monthIndex]} ${year}`;
    }

    function showTrialToast(message, type) {
        if (typeof window.showToast === 'function') {
            window.showToast(type || 'success', message);
        }
    }

    function renderOrderRows(orders) {
        return orders.map(function (order) {
            const item = order.items && order.items[0] ? order.items[0] : {};
            const orderEndDate = item.end_date || null;
            const showDays = orderEndDate && !['9999-12-31', '2099-12-31'].includes(orderEndDate);
            let daysLeft = null;
            if (orderEndDate && showDays) {
                const end = new Date(orderEndDate + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                daysLeft = Math.floor((end - today) / (1000 * 60 * 60 * 24));
            }

            return '<tr>' +
                '<td class="fw-semibold text-dark">#' + escapeHtml(order.number || '') + '</td>' +
                '<td>' +
                '<div class="fw-bold text-dark">' + escapeHtml(item.item_name || 'Item') + '</div>' +
                (item.item_description ? '<div class="text-dark mt-1">' + escapeHtml(item.item_description) + '</div>' : '') +
                '<div class="d-flex flex-wrap text-black mt-2">' +
                '<div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1"><small>Qty:</small> <span class="fw-semibold">' + formatQty(item.quantity) + '</span></div>' +
                (item.no_of_users ? '<div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1"><small>Users:</small> <span class="fw-semibold">' + escapeHtml(String(item.no_of_users)) + '</span></div>' : '') +
                (item.delivery_date ? '<div class="border border-dark-subtle rounded-pill small lh-sm px-2 py-1 me-2 my-1"><small>Delivery Date:</small> <span class="fw-semibold">' + escapeHtml(formatDateToDisplay(item.delivery_date)) + '</span></div>' : '') +
                '</div>' +
                '</td>' +
                '<td class="text-center">' + formatDateToDisplay(item.start_date) + '</td>' +
                '<td class="text-center">' +
                formatDateToDisplay(orderEndDate) +
                (showDays && daysLeft !== null
                    ? '<br><small class="' + (daysLeft >= 0 ? 'text-success' : 'text-danger') + ' fw-semibold">' +
                    (daysLeft >= 0 ? daysLeft : '- ' + Math.abs(daysLeft)) + ' day(s)</small>'
                    : '') +
                '</td>' +
                '</tr>';
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // View Toggle Logic
        const btnList = document.getElementById('btn-list-view');
        const btnGrid = document.getElementById('btn-grid-view');
        const listView = document.getElementById('clients-list-view');
        const gridView = document.getElementById('clients-grid-view');

        function setView(viewType) {
            if (viewType === 'grid') {
                listView.classList.add('d-none');
                gridView.classList.remove('d-none');
                btnList.classList.remove('active', 'btn-primary');
                btnList.classList.add('btn-outline-primary');
                btnGrid.classList.add('active', 'btn-primary');
                btnGrid.classList.remove('btn-outline-primary');
                localStorage.setItem('clients_view_preference', 'grid');
            } else {
                listView.classList.remove('d-none');
                gridView.classList.add('d-none');
                btnList.classList.add('active', 'btn-primary');
                btnList.classList.remove('btn-outline-primary');
                btnGrid.classList.remove('active', 'btn-primary');
                btnGrid.classList.add('btn-outline-primary');
                localStorage.setItem('clients_view_preference', 'list');
            }
        }

        if (btnList && btnGrid && listView && gridView) {
            btnList.addEventListener('click', () => setView('list'));
            btnGrid.addEventListener('click', () => setView('grid'));

            const savedPref = localStorage.getItem('clients_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }

        // View Orders Modal
        const modalEl = document.getElementById('viewOrdersModal');
        const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
        const modalBody = document.getElementById('modalOrdersBody');

        document.querySelectorAll('.js-view-orders-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const recordId = this.dataset.clientRecordId;
                const client = clientsData.find(function (c) { return String(c.record_id) === String(recordId); });
                if (!client) return;

                const orders = client.orders_data || [];
                document.getElementById('modalClientName').textContent = client.name;

                if (orders.length === 0) {
                    modalBody.innerHTML =
                        '<tr><td colspan="4" class="text-center py-5 text-muted">' +
                        '<i class="fas fa-receipt mb-3 text-secondary fs-1 opacity-50 d-block"></i>' +
                        '<p class="fw-semibold text-dark mb-1">No orders found</p>' +
                        '<p class="small text-muted mb-0">This client has no orders yet.</p></td></tr>';
                } else {
                    modalBody.innerHTML = renderOrderRows(orders);
                }

                if (modal) modal.show();
            });
        });

        // Edit Trial Order Expiry Date Listener
        document.querySelectorAll('.trial-expiry-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const orderId = this.dataset.orderId;
                const newDate = this.value;
                const originalValue = this.defaultValue;

                if (!newDate) {
                    alert('Please select a valid date.');
                    this.value = originalValue;
                    return;
                }

                const minDate = this.getAttribute('min');
                if (minDate && newDate < minDate) {
                    alert('Expiry date cannot be before registration date (' + formatDateToDisplay(minDate) + ').');
                    this.value = originalValue;
                    return;
                }

                const self = this;
                self.disabled = true;

                fetch("{{ url('/invoices/orders') }}/" + orderId + "/renew", {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        _method: 'PATCH',
                        end_date: newDate
                    })
                })
                    .then(function (res) {
                        if (!res.ok) {
                            return res.json().then(function (data) { throw data; });
                        }
                        return res.json();
                    })
                    .then(function (data) {
                        self.disabled = false;
                        if (data.success) {
                            self.defaultValue = newDate;
                            showTrialToast(data.message || 'Expiry date updated successfully.');

                            // Sync date inputs for the same order across views (Grid vs List)
                            document.querySelectorAll('.trial-expiry-input[data-order-id="' + orderId + '"]').forEach(function (el) {
                                if (el !== self) {
                                    el.value = newDate;
                                    el.defaultValue = newDate;
                                    if (el._flatpickr) el._flatpickr.setDate(newDate, false);
                                }
                            });

                            // Update local orders_data model for the view modal
                            const clientRecord = clientsData.find(function (c) {
                                return c.orders_data && c.orders_data.some(function (o) { return String(o.record_id) === String(orderId); });
                            });
                            if (clientRecord) {
                                const order = clientRecord.orders_data.find(function (o) { return String(o.record_id) === String(orderId); });
                                if (order && order.items && order.items[0]) {
                                    order.items[0].end_date = newDate;
                                }
                            }
                        } else {
                            showTrialToast(data.message || 'Failed to update expiry date.', 'danger');
                            self.value = originalValue;
                            if (self._flatpickr) self._flatpickr.setDate(originalValue, false);
                        }
                    })
                    .catch(function (err) {
                        self.disabled = false;
                        showTrialToast(err.message || 'Something went wrong. Please check inputs.', 'danger');
                        self.value = originalValue;
                        if (self._flatpickr) self._flatpickr.setDate(originalValue, false);
                    });
            });
        });

    });
</script>
@endsection
