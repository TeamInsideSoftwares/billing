@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <a href="{{ route('clients.index') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-list btn-icon"></i> Client List
    </a>
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-3 rounded-3 shadow-sm">
    <!-- Filters Card -->
    <div class="position-relative bg-light border p-3 rounded-3 mb-2">
        <form action="{{ route('clients.trials') }}" method="GET" class="mainForm">
            <div class="row g-2">

                <div class="col-12 col-md-2">
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trials_client_filter">Client</label>
                    <select name="client" id="trials_client_filter" class="form-select">
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
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trials_item_filter">Item</label>
                    <select name="item" id="trials_item_filter" class="form-select">
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
                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                        for="trials_search_filter">Search</label>
                    <input type="text" name="search" id="trials_search_filter" class="form-control"
                        value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person">
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('clients.trials') }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium text-center justify-content-center"><i
                            class="fas fa-sync-alt btn-icon me-1"></i> Reset</a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">Apply
                        <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
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
                <span class="status-dot legend-dot review"></span> Review
            </div>
            <div class="d-flex align-items-center">
                <span class="status-dot legend-dot inactive"></span> Inactive
            </div>
        </div>
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

    <!-- Table View (List View) -->
    <div id="clients-list-view" class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table mainTable border align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="20%">Client Name & Email</th>
                        <th width="15%">Phone Number</th>
                        <th width="25%">Item Details with Expiry Date</th>
                        <th class="text-center" width="15%">Registered Date</th>
                        <th class="text-end" width="25%">Actions</th>
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
                                    <span class="d-block text-muted small">{{ $client['email'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($client['phone'])
                            <div class="fw-medium text-dark">{{ $client['phone'] }}</div>
                            @else
                            <span class="text-muted small">&#8212;</span>
                            @endif
                        </td>
                        <td>
                            @if ($client['item_name'])
                            <span class="border border-dark-subtle rounded-2 small lh-sm px-2 py-1">
                                {{
                                $client['item_name']
                                }}
                                @if ($client['item_end_date'])
                                <span class="text-danger fw-semibold small lh-sm">({{
                                    \Carbon\Carbon::parse($client['item_end_date'])->format('d M Y') }})</span>
                                @endif
        </div>

        @else
        <span class="text-muted">&#8212;</span>
        @endif
        </td>
        <td class="text-center">{{ $client['created_at'] ?
            \Carbon\Carbon::parse($client['created_at'])->format('d M Y') : '&#8212;' }}
        </td>
        <td class="text-end">
            <div class="tableActionButton d-inline-flex gap-1">
                <a href="{{ route('clients.dashboard', $client['record_id']) }}" class="bg01 color01">View Orders</a>
                <a href="{{ route('quotations.create', ['c' => $client['record_id']]) }}" class="bg03 color03">Create
                    Quotation</a>
                <form method="POST" action="{{ route('clients.convert-to-regular', $client['record_id']) }}"
                    class="d-inline" data-name="{{ $client['name'] }}"
                    onsubmit="return confirm('Convert ' + this.dataset.name + ' to regular client?')">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="bg02 color02">Trial to Regular</button>
                </form>
            </div>
        </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="text-center py-5 text-muted">
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
    class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 rounded-3 p-1 mt-2 bg-light d-none">
    @forelse ($clients as $client)
    <div class="col">
        <div class="card h-100 border overflow-hidden">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
                <div>
                    <!-- Flex Avatar, Info -->
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div
                            class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                            <span class="d-block position-absolute">{{ strtoupper(substr($client['name'], 0, 2))
                                }}</span>
                            <div class="status-dot {{ strtolower($client['status']) }}"
                                title="{{ ucfirst($client['status']) }}"></div>
                        </div>
                        <div class="flex-grow-1 min-w-0 ps-2">
                            <h6 class="fw-bold text-dark mb-0 text-truncate lh-sm" title="{{ $client['name'] }}">
                                {!! isset($searchTerm) && $searchTerm
                                ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                    '</mark>', $client['name'])
                                : $client['name'] !!}
                            </h6>
                            <span class="d-block text-muted lh-sm text-break grid-text-medium"
                                title="{{ $client['email'] }}">{{ $client['email'] }}</span>
                        </div>
                    </div>

                    <!-- Item & Date & Contact details -->
                    <div class="mb-2 border-top pt-2 grid-text-medium">
                        <!-- Phone Number -->
                        <div class="text-dark lh-sm mb-2" title="{{ $client['phone'] ?? '—' }}">
                            <i class="fas fa-phone contact-icon me-2 text-muted"></i>{{ $client['phone'] ?? '—' }}
                        </div>

                        <!-- Item Details -->
                        <div class="mb-2">
                            @if ($client['item_name'])
                            <span class="border border-dark-subtle rounded-2 small lh-sm px-2 py-1 d-inline-block">
                                {{ $client['item_name'] }}
                                @if ($client['item_end_date'])
                                <span class="text-danger fw-semibold small lh-sm">({{
                                    \Carbon\Carbon::parse($client['item_end_date'])->format('d M Y') }})</span>
                                @endif
                            </span>
                            @else
                            <span class="text-muted small">&#8212;</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                    <a href="{{ route('clients.dashboard', $client['record_id']) }}"
                        class="bg01 color01 text-center">View Orders</a>
                    <a href="{{ route('quotations.create', ['c' => $client['record_id']]) }}"
                        class="bg03 color03 text-center">Create Quotation</a>
                    <form method="POST" action="{{ route('clients.convert-to-regular', $client['record_id']) }}"
                        class="d-inline" data-name="{{ $client['name'] }}"
                        onsubmit="return confirm('Convert ' + this.dataset.name + ' to regular client?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="bg02 color02 text-center">Convert to Regular</button>
                    </form>
                </div>

                <!-- Registered Date -->
                <div class="text-muted fw-normal text-end mt-3 pt-1 border-top" title="Registered Date">
                    <small class="small lh-sm">Reg. Date - {{ $client['created_at'] ?
                        \Carbon\Carbon::parse($client['created_at'])->format('d M Y') : '&#8212;' }}</small>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 w-100">
        <div class="card border-0 shadow-sm py-5 text-center text-muted">
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

<script>
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

            // Load saved preference
            const savedPref = localStorage.getItem('clients_view_preference');
            if (savedPref === 'grid') {
                setView('grid');
            } else {
                setView('list');
            }
        }
    });
</script>
@endsection
