@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <button type="button"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium"
        data-bs-toggle="modal" data-bs-target="#manageGroupsModal">
        <i class="fas fa-layer-group btn-icon"></i> Client Groups
    </button>
    <a href="{{ route('clients.create') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Add Client
    </a>
</div>
@endsection

@section('content')

<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-light p-2 rounded-3 mb-2">
        <form action="{{ route('clients.index') }}" method="GET" class="mainForm">
            @if ($selectedGroup)
            <input type="hidden" name="groupid" value="{{ $selectedGroup }}">
            @endif
            <div class="row g-2">
                @if ($selectedGroup)
                @php
                $activeGroup = $groups->firstWhere('groupid', $selectedGroup);
                @endphp
                <div class="col-12 col-md-12">
                    <div class="d-flex align-items-center gap-2 mb-0 px-1 active-filter-container">
                        <span class="small text-muted fw-medium d-inline-flex align-items-center gap-1">
                            <i class="fas fa-filter text-secondary" style="font-size: 0.75rem;"></i> Filtered by:
                        </span>
                        <span
                            class="badge bg-primary-subtle text-primary border border-primary-subtle fw-medium d-inline-flex align-items-center gap-2 rounded-pill px-2 py-1.5"
                            style="font-size: 0.8rem;">
                            <i class="fas fa-layer-group" style="font-size: 0.75rem;"></i>
                            <span class="text-dark align-self-center">{{ $activeGroup?->group_name ?? 'Group
                                #'.$selectedGroup }}</span>
                            <a href="{{ route('clients.index') }}" class="group-filter-clear-btn" title="Clear filter">
                                <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                            </a>
                        </span>
                    </div>
                </div>
                @endif
                <div class="col-12 col-md-2">
                    <select name="state" id="clients_state_filter" class="form-select">
                        <option value="">All States</option>
                        @foreach ($stateOptions ?? collect() as $stateOption)
                        <option value="{{ $stateOption }}" {{ (string) ($selectedState ?? '' )===(string) $stateOption
                            ? 'selected' : '' }}>
                            {{ $stateOption }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <select name="city" id="clients_city_filter" class="form-select">
                        <option value="">All Cities</option>
                        @foreach ($cityOptions ?? collect() as $cityOption)
                        <option value="{{ $cityOption }}" {{ (string) ($selectedCity ?? '' )===(string) $cityOption
                            ? 'selected' : '' }}>
                            {{ $cityOption }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute text-muted"
                            style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                        <input type="text" name="search" id="clients_search_filter" class="form-control"
                            value="{{ $searchTerm ?? '' }}" placeholder="Search Client Name or Contact Person"
                            style="padding-left: 38px;">
                    </div>
                </div>

                <div class="col-12 col-md-2 mt-auto d-flex gap-2">
                    <a href="{{ route('clients.index') }}"
                        class="btn btn-outline-primary bg-white text-primary fw-medium"><i
                            class="fas fa-sync-alt btn-icon me-1"></i> Clear</a>
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium"><i
                            class="fas fa-filter btn-icon me-1"></i>Filter</button>
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
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('clients.trials') }}"
                class="btn btn-sm btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-user-clock btn-icon"></i> Prospect Clients
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

    <!-- Clients List View (Table View) -->
    <div id="clients-list-view" class="card overflow-hidden">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>State</th>
                        <th class="text-center">Outstanding</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Actions</th>
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
                                    <span class="d-block fw-semibold">{!! $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm
                                            . '</mark>', $client['name'])
                                        : $client['name'] !!}</span>
                                    <span class="d-block text-muted small">{{ $client['email'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($client['contact'])
                            <div class="fw-medium">{{ $client['contact'] }}</div>
                            @endif
                            @if ($client['phone'])
                            <small class="text-muted">{{ $client['phone'] }}</small>
                            @endif
                        </td>
                        <td>{{ $client['state'] ?? '—' }}</td>
                        <td class="text-center">
                            @php
                            $rawVal = (float) str_replace([$client['currency'], ' ', ','], '', $client['balance']);
                            $balanceClass = $rawVal < 0 ? 'text-danger' : ($rawVal> 0 ? 'text-success' : 'text-dark');
                                @endphp
                                <span class="fw-semibold {{ $balanceClass }}">
                                    <span class="currency-code-small text-muted">{{ $client['currency'] }}</span> {{
                                    substr($client['balance'], strlen($client['currency']) + 1) }}
                                </span>
                        </td>
                        <td class="text-center">
                            <span class="text-primary fw-semibold">
                                {{ $client['invoice_count'] }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <a href="{{ route('clients.dashboard', $client['record_id']) }}"
                                    data-client-id="{{ $client['record_id'] }}" class="bg01 color01">View</a>
                                <a href="#" class="bg02 color02 open-documents-modal" data-bs-toggle="modal"
                                    data-bs-target="#documentsModal" data-client-id="{{ $client['record_id'] }}"
                                    data-client-name="{{ $client['name'] }}">PO &
                                    Agreement</a>
                                <a href="{{ route('clients.edit', $client['record_id']) }}"
                                    class="bg03 color03">Edit</a>
                                <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}"
                                    class="d-inline" data-name="{{ $client['name'] }}"
                                    onsubmit="return confirm('Delete ' + this.dataset.name + '?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-users mb-3 text-secondary fs-1 opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">No clients found</p>
                            <p class="small text-muted mb-0">Get started by adding your first client.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clients Grid View (5 blocks in one row on desktop) -->
    <div id="clients-grid-view"
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 mt-2 bg-light rounded-end-3 d-none">
        @forelse ($clients as $client)
        <div class="col">
            <div class="card h-100 border-0 overflow-hidden">
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
                                <h6 class="fw-bold text-dark mb-1 text-truncate lh-sm" title="{{ $client['name'] }}">
                                    {!! $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                        '</mark>', $client['name'])
                                    : $client['name'] !!}
                                </h6>
                                <span class="d-block text-muted lh-sm text-break grid-text-medium"
                                    title="{{ $client['email'] }}">{{ $client['email'] }}</span>
                            </div>
                        </div>



                        <!-- Contact info -->
                        <div class="mb-3 border-top pt-3 grid-text-medium text-muted">
                            @if ($client['contact'])
                            <div class="text-dark fw-semibold lh-sm text-truncate mb-1"
                                title="{{ $client['contact'] }}">
                                <i class="fas fa-user contact-icon me-2 text-muted"></i>{{ $client['contact'] }}
                            </div>
                            @endif
                            @if ($client['phone'])
                            <div class="text-muted text-truncate lh-sm mb-1" title="{{ $client['phone'] }}">
                                <i class="fas fa-phone contact-icon me-1 text-muted"></i>{{ $client['phone'] }}
                            </div>
                            @endif
                            <div class="text-muted lh-sm text-truncate" title="{{ $client['state'] ?? '—' }}">
                                <i class="fas fa-map-marker-alt contact-icon me-2 text-muted"></i>{{ $client['state']
                                ??
                                '—' }}
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding & Invoices -->
                    <div class="bg-light rounded-3 px-3 py-2 mt-auto grid-text-medium mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted">Outstanding</span>
                            @php
                            $rawValGrid = (float) str_replace([$client['currency'], ' ', ','], '', $client['balance']);
                            $balanceClassGrid = $rawValGrid < 0 ? 'text-danger' : ($rawValGrid> 0 ? 'text-success' :
                                'text-dark');
                                @endphp
                                <strong class="{{ $balanceClassGrid }} fw-semibold grid-value-large">
                                    <span class="currency-code-grid text-muted">{{ $client['currency'] }}</span> {{
                                    substr($client['balance'], strlen($client['currency']) + 1) }}
                                </strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Invoices</span>
                            <strong class="text-primary fw-semibold grid-value-large">{{ $client['invoice_count']
                                }}</strong>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="tableActionButton d-flex flex-wrap gap-1 mt-2">
                        <a href="{{ route('clients.dashboard', $client['record_id']) }}"
                            class="bg01 color01 flex-grow-1 text-center">View</a>
                        <a href="#" class="bg02 color02 flex-grow-1 text-center open-documents-modal"
                            data-bs-toggle="modal" data-bs-target="#documentsModal"
                            data-client-id="{{ $client['record_id'] }}" data-client-name="{{ $client['name'] }}">PO &
                            Agreement</a>
                        <a href="{{ route('clients.edit', $client['record_id']) }}"
                            class="bg03 color03 flex-grow-1 text-center">Edit</a>
                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}"
                            class="d-inline flex-grow-1" data-name="{{ $client['name'] }}"
                            onsubmit="return confirm('Delete ' + this.dataset.name + '?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg04 color04 text-center">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 w-100">
            <div class="card border-0 shadow-sm py-5 text-center text-muted">
                <div class="card-body">
                    <i class="fas fa-users mb-3 text-secondary fs-1 opacity-50"></i>
                    <p class="fw-semibold text-dark mb-1">No clients found</p>
                    <p class="small text-muted mb-0">Get started by adding your first client.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    @if ($clients->hasPages())
    <div class="d-flex justify-content-end mt-2">
        <nav aria-label="Clients pagination">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $clients->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link border-0 shadow-none bg-transparent"
                        href="{{ $clients->previousPageUrl() ?? '#' }}"
                        tabindex="{{ $clients->onFirstPage() ? '-1' : '0' }}">&lsaquo;</a>
                </li>

                @foreach ($clients->getUrlRange(1, $clients->lastPage()) as $page => $url)
                <li class="page-item {{ $page == $clients->currentPage() ? 'active' : '' }}">
                    <a class="page-link border-0 {{ $page == $clients->currentPage() ? 'rounded' : 'bg-transparent text-secondary' }}"
                        href="{{ $url }}">{{ $page }}</a>
                </li>
                @endforeach

                <li class="page-item {{ $clients->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link border-0 shadow-none bg-transparent" href="{{ $clients->nextPageUrl() ?? '#' }}"
                        tabindex="{{ $clients->hasMorePages() ? '0' : '-1' }}">&rsaquo;</a>
                </li>
            </ul>
        </nav>
    </div>
    @endif

</div>
<!-- Manage Groups Modal -->
<div class="modal fade" id="manageGroupsModal" tabindex="-1" aria-labelledby="manageGroupsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header bg-light py-2 border-0">
                <h5 class="modal-title fw-semibold" id="manageGroupsModalLabel">Client Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Group Form -->
                <div id="add-group-pane" class="bg-light p-2 rounded-3 mb-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="groupTabTitle">Add Client Group</span>
                    </h6>
                    <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="mainForm">
                        @csrf
                        <input type="hidden" id="groupId" name="_group_id" value="">
                        <div id="methodField"></div>
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label for="groupName" class="form-label small lh-sm fw-semibold text-dark mb-1">Group
                                    Name<span class="text-danger">*</span></label>
                                <input type="text" name="group_name" id="groupName" class="form-control"
                                    value="{{ old('group_name') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="groupEmail"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Email</label>
                                <input type="email" name="email" id="groupEmail" class="form-control"
                                    value="{{ old('email') }}">
                            </div>
                            <!-- Registered Address (col-6) -->
                            <div class="col-12 col-md-6">
                                <div class="p-2 rounded-3 border h-100 form-grid">
                                    <h6 class="fw-semibold text-primary mb-2">Registered Address</h6>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <textarea name="registered_address" id="groupRegisteredAddress"
                                                class="form-control" placeholder="Address"
                                                style="min-height: 60px;">{{ old('registered_address') }}</textarea>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupCountry"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                            <select id="groupCountry" name="country" class="form-select country-select"
                                                data-selected="India">
                                                <option value="">Select Country</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupState"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                                            <select id="groupState" name="state" class="form-select state-select"
                                                data-selected="">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupCity"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                            <select id="groupCity" name="city" class="form-select city-select"
                                                data-selected="">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupPostalCode"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                                Code</label>
                                            <input type="text" name="postal_code" id="groupPostalCode"
                                                class="form-control" value="{{ old('postal_code') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Address (col-6) -->
                            <div class="col-12 col-md-6">
                                <div class="p-2 rounded-3 h-100 form-grid" style="background: #f3f3f3;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-semibold text-primary mb-0">Business Address</h6>
                                        <div class="mb-0 bg-white border rounded-1 px-1 py-0">
                                            <div class="form-check mb-0 form-check-small">
                                                <input class="form-check-input" type="checkbox"
                                                    id="groupSameAsRegistered" value="1">
                                                <label class="form-check-label small lh-sm fw-normal text-dark"
                                                    for="groupSameAsRegistered">
                                                    Same as Registered Address
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <textarea name="business_address" id="groupBusinessAddress"
                                                class="form-control" placeholder="Address"
                                                style="min-height: 60px;">{{ old('business_address') }}</textarea>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessCountry"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                            <select id="groupBusinessCountry" name="business_country"
                                                class="form-select country-select" data-selected="India">
                                                <option value="">Select Country</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessState"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                                            <select id="groupBusinessState" name="business_state"
                                                class="form-select state-select" data-selected="">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessCity"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                            <select id="groupBusinessCity" name="business_city"
                                                class="form-select city-select" data-selected="">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessPostalCode"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                                Code</label>
                                            <input type="text" name="business_postal_code" id="groupBusinessPostalCode"
                                                class="form-control" value="{{ old('business_postal_code') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end mt-3">
                            <button type="submit" id="groupSubmitBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                Save Client Group <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Groups List -->
                <div id="group-list-pane" class="position-relative bg-light p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="group-list-tab">Client Group List ({{ $groups->count()}})</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Group</th>
                                        <th width="20%">Registered Address</th>
                                        <th width="20%">Business Address</th>
                                        <th class="text-end" width="35%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($groups as $group)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div
                                                    class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                                    <span class="d-block position-absolute">{{
                                                        strtoupper(substr($group->group_name, 0, 2)) }}</span>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-semibold">{{ $group->group_name
                                                        }}</span>
                                                    <span class="d-block text-muted small">{{ $group->email ??
                                                        '—' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ implode(', ', array_filter([$group->city, $group->state,
                                            $group->country, $group->postal_code])) ?: '—' }}</td>
                                        <td>{{ implode(', ', array_filter([$group->business_city,
                                            $group->business_state,
                                            $group->business_country, $group->business_postal_code])) ?: '—' }}</td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <a href="{{ route('clients.index') }}?groupid={{ $group->groupid }}"
                                                    class="bg01 color01 border-0"
                                                    title="View clients in this group">View Clients</a>
                                                <button type="button" class="bg03 color03 border-0"
                                                    onclick="editGroup(this)" data-id="{{ $group->groupid }}"
                                                    data-name="{{ $group->group_name }}"
                                                    data-email="{{ $group->email }}"
                                                    data-registered-address="{{ $group->registered_address }}"
                                                    data-city="{{ $group->city }}" data-state="{{ $group->state }}"
                                                    data-postal="{{ $group->postal_code }}"
                                                    data-country="{{ $group->country }}"
                                                    data-business-address="{{ $group->business_address }}"
                                                    data-business-city="{{ $group->business_city }}"
                                                    data-business-state="{{ $group->business_state }}"
                                                    data-business-postal="{{ $group->business_postal_code }}"
                                                    data-business-country="{{ $group->business_country }}">Edit</button>
                                                <form method="POST"
                                                    action="{{ route('groups.destroy', $group->groupid) }}"
                                                    class="d-inline group-delete-form"
                                                    data-group-name="{{ $group->group_name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted bg-white">
                                            <i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>
                                            <p class="text-muted small mb-0">No groups yet. Create one above!
                                            </p>
                                        </td>
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
</div>

<!-- Documents Modal -->
<div class="modal fade" id="documentsModal" tabindex="-1" aria-labelledby="documentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header bg-light py-2 border-0">
                <h5 class="modal-title fw-semibold" id="documentsModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Document Form -->
                <div id="add-document-pane" class="bg-light p-2 rounded-3 mb-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="documentTabTitle">Add Document</span>
                    </h6>
                    <form id="documentForm" method="POST" enctype="multipart/form-data" class="mainForm">
                        @csrf
                        <input type="hidden" id="docClientId" name="clientid" value="">
                        <input type="hidden" id="docId" name="_doc_id" value="">
                        <div id="docMethodField"></div>
                        <div class="row g-2">
                            <div class="col-12 col-md-2">
                                <select id="docType" name="type" class="form-select" required>
                                    <option value="">Select type</option>
                                    <option value="po">Purchase Order</option>
                                    <option value="agreement">Agreement</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <input type="text" id="docTitle" name="title" class="form-control" placeholder="Title"
                                    maxlength="150">
                            </div>
                            <div class="col-12 col-md-2">
                                <input type="text" id="docNumber" name="document_number" class="form-control"
                                    placeholder="Doc Number" maxlength="100">
                            </div>
                            <div class="col-12 col-md-2">
                                <div class="input-group">
                                    <input type="date" id="docDate" name="document_date" class="form-control"
                                        placeholder="Doc Date" value="{{ date('Y-m-d') }}">
                                    <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                                </div>
                            </div>
                            <div class="col-12 col-md-10">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Upload File</label>
                                <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                    style="cursor:pointer;" id="docUploadDropZone">
                                    <input type="file" id="docFile" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                        class="position-absolute top-0 start-0 w-100 h-100 opacity-0">
                                    <div class="drop-zone-prompt d-flex align-items-center justify-content-start"
                                        id="docDropPrompt">
                                        <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                        <span class="text-muted fw-medium ms-2">Drag and drop or <span
                                                class="text-primary fw-semibold">browse files</span></span>
                                    </div>
                                    <div class="drop-zone-preview d-none align-items-center justify-content-between w-100"
                                        id="docDropPreview">
                                        <div class="d-flex align-items-center gap-2">
                                            <img id="docPreviewImg" src="#" alt="Preview"
                                                class="img-fluid rounded shadow-sm d-none" width="50px">
                                            <i id="docFileIcon" class="far fa-file-alt fs-3 text-secondary d-none"></i>
                                            <span id="docFileName" class="text-muted small fw-medium"></span>
                                        </div>
                                        <button type="button" id="docRemoveBtn"
                                            class="btn btn-sm p-0 bg-transparent text-dark border-0"
                                            title="Remove File">
                                            <i class="fas fa-times fs-5 lh-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-2 mt-auto">
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <button type="submit" id="documentSubmitBtn"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                        Save Document <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Document List -->
                <div id="document-list-pane" class="position-relative bg-light p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span id="documentListTabLabel">Document List (0)</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Type</th>
                                        <th width="35%">Title</th>
                                        <th width="15%">Document Number</th>
                                        <th width="15%">Document Date</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="documentTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted bg-white">
                                            <i class="fas fa-file-alt text-muted mb-2 fs-2 opacity-50"></i>
                                            <p class="text-muted small mb-0">Select a client to view documents.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clientShowModal" tabindex="-1" aria-labelledby="clientShowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom py-2">
                <h5 class="modal-title fw-semibold" id="clientShowModalLabel">Client Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-4" id="clientShowModalBody">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border" role="status"></div>
                    <p class="small mt-3 mb-0">Loading client details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('groupSameAsRegistered').addEventListener('change', function () {
        if (!this.checked) return;

        const regAddr = document.getElementById('groupRegisteredAddress');
        const busAddr = document.getElementById('groupBusinessAddress');
        if (regAddr && busAddr) { busAddr.value = regAddr.value; }

        const regPostal = document.getElementById('groupPostalCode');
        const busPostal = document.getElementById('groupBusinessPostalCode');
        if (regPostal && busPostal) { busPostal.value = regPostal.value; }

        const busCountryEl = document.getElementById('groupBusinessCountry');
        const busStateEl = document.getElementById('groupBusinessState');
        const busCityEl = document.getElementById('groupBusinessCity');
        const regCountryEl = document.getElementById('groupCountry');
        const regStateEl = document.getElementById('groupState');
        const regCityEl = document.getElementById('groupCity');

        busCountryEl.dataset.selected = regCountryEl.value;
        busStateEl.dataset.selected = regStateEl.value;
        busCityEl.dataset.selected = regCityEl.value;

        LocationPicker.loadSelection(busCountryEl);
    });

    function editGroup(btn) {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const email = btn.dataset.email || '';
        const regAddr = btn.dataset.registeredAddress || btn.dataset.address1 || '';
        const city = btn.dataset.city || '';
        const state = btn.dataset.state || '';
        const postal = btn.dataset.postal || '';
        const country = btn.dataset.country || '';

        const busAddr = btn.dataset.businessAddress || '';
        const busCity = btn.dataset.businessCity || '';
        const busState = btn.dataset.businessState || '';
        const busPostal = btn.dataset.businessPostal || '';
        const busCountry = btn.dataset.businessCountry || 'India';

        const form = document.getElementById('groupForm');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('methodField');

        // Always uncheck "same as registered" when editing
        const sameAsCheckbox = document.getElementById('groupSameAsRegistered');
        if (sameAsCheckbox) { sameAsCheckbox.checked = false; }


        form.action = 'groups/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('groupName').value = name;
        document.getElementById('groupEmail').value = email;
        document.getElementById('groupRegisteredAddress').value = regAddr;
        document.getElementById('groupPostalCode').value = postal;

        document.getElementById('groupBusinessAddress').value = busAddr;
        document.getElementById('groupBusinessPostalCode').value = busPostal;

        const countryEl = document.getElementById('groupCountry');
        const stateEl = document.getElementById('groupState');
        const cityEl = document.getElementById('groupCity');

        countryEl.dataset.selected = country || 'India';
        stateEl.dataset.selected = state || '';
        cityEl.dataset.selected = city || '';

        LocationPicker.loadSelection(countryEl);

        const busCountryEl = document.getElementById('groupBusinessCountry');
        const busStateEl = document.getElementById('groupBusinessState');
        const busCityEl = document.getElementById('groupBusinessCity');

        busCountryEl.dataset.selected = busCountry || 'India';
        busStateEl.dataset.selected = busState || '';
        busCityEl.dataset.selected = busCity || '';

        LocationPicker.loadSelection(busCountryEl);

        submitBtn.innerHTML = 'Update Client Group <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.remove('d-none');
        }

        const addTabEl = document.getElementById('add-group-tab');
        if (addTabEl) {
            document.getElementById('groupTabTitle').innerText = 'Edit Group';
            addTabEl.click();
        }

        setTimeout(() => {
            document.getElementById('groupName').focus();
        }, 150);
    }

    function resetGroupForm() {
        const form = document.getElementById('groupForm');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('methodField');

        form.action = "{{ route('groups.store') }}";
        methodField.innerHTML = '';
        form.reset();

        submitBtn.innerHTML = 'Save Client Group <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.add('d-none');
        }

        const addTabEl = document.getElementById('add-group-tab');
        if (addTabEl) {
            document.getElementById('groupTabTitle').innerText = 'Add Group';
        }

        const countryEl = document.getElementById('groupCountry');
        const busCountryEl = document.getElementById('groupBusinessCountry');
        const sameAsCheckbox = document.getElementById('groupSameAsRegistered');

        if (sameAsCheckbox) {
            sameAsCheckbox.checked = false;
        }

        if (countryEl) {
            countryEl.dataset.selected = 'India';
            countryEl.dispatchEvent(new Event('change'));
        }
        if (busCountryEl) {
            busCountryEl.dataset.selected = 'India';
            busCountryEl.dispatchEvent(new Event('change'));
        }

        document.querySelectorAll('#add-group-pane .text-danger.small.mt-1').forEach(function (el) {
            el.remove();
        });
        document.querySelectorAll('#add-group-pane .is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    const clientsIndexUrl = "{{ route('clients.index') }}";
    const clientsBaseUrl = "{{ url('/') }}";
    const documentsListUrlTemplate = "{{ route('clients.documents.list', ['client' => '__CLIENT__']) }}";
    const clientShowUrlTemplate = "{{ route('clients.show', ['client' => '__CLIENT__']) }}";

    function buildGroupRow(group) {
        var initials = (group.group_name || '').substring(0, 2).toUpperCase();
        var email = group.email || '—';
        var address = [group.city, group.state, group.country, group.postal_code].filter(Boolean).join(', ') || '—';
        var business_address = [group.business_city, group.business_state, group.business_country, group.business_postal_code].filter(Boolean).join(', ') || '—';
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        return '<tr>' +
            '<td>' +
            '<div class="d-flex align-items-center gap-3">' +
            '<div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">' +
            '<span class="d-block position-absolute">' + initials + '</span>' +
            '</div>' +
            '<div>' +
            '<span class="d-block fw-semibold">' + (group.group_name || '') + '</span>' +
            '<span class="d-block text-muted small">' + email + '</span>' +
            '</div>' +
            '</div>' +
            '</td>' +
            '<td>' + address + '</td>' +
            '<td>' + business_address + '</td>' +
            '<td class="text-end">' +
            '<div class="tableActionButton d-inline-flex gap-1">' +
            '<a href="' + clientsIndexUrl + '?groupid=' + group.groupid + '" class="bg01 color01 border-0" title="View clients in this group">View Clients</a>' +
            '<button type="button" class="bg03 color03 border-0" onclick="editGroup(this)"' +
            ' data-id="' + group.groupid + '"' +
            ' data-name="' + (group.group_name || '').replace(/"/g, '&quot;') + '"' +
            ' data-email="' + (group.email || '').replace(/"/g, '&quot;') + '"' +
            ' data-registered-address="' + (group.registered_address || '').replace(/"/g, '&quot;') + '"' +
            ' data-city="' + (group.city || '').replace(/"/g, '&quot;') + '"' +
            ' data-state="' + (group.state || '').replace(/"/g, '&quot;') + '"' +
            ' data-postal="' + (group.postal_code || '').replace(/"/g, '&quot;') + '"' +
            ' data-country="' + (group.country || '').replace(/"/g, '&quot;') + '"' +
            ' data-business-address="' + (group.business_address || '').replace(/"/g, '&quot;') + '"' +
            ' data-business-city="' + (group.business_city || '').replace(/"/g, '&quot;') + '"' +
            ' data-business-state="' + (group.business_state || '').replace(/"/g, '&quot;') + '"' +
            ' data-business-postal="' + (group.business_postal_code || '').replace(/"/g, '&quot;') + '"' +
            ' data-business-country="' + (group.business_country || '').replace(/"/g, '&quot;') + '">Edit</button>' +
            '<form method="POST" action="groups/' + group.groupid + '" class="d-inline group-delete-form" data-group-name="' + (group.group_name || '').replace(/"/g, '&quot;') + '">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="submit" class="bg04 color04 border-0">Delete</button>' +
            '</form>' +
            '</div>' +
            '</td>' +
            '</tr>';
    }

    function refreshGroupsTable(groups, activeTab) {
        var tbody = document.querySelector('#group-list-pane tbody');
        if (groups.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-folder-open text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No groups yet. Create one above!</p>' +
                '</td></tr>';
        } else {
            tbody.innerHTML = groups.map(buildGroupRow).join('');
        }
        document.querySelector('#group-list-tab').innerHTML = '<i class="fas fa-list me-1"></i>Client Group List (' + groups.length + ')';

        if (activeTab === 'list') {
            document.getElementById('group-list-tab').click();
        }
    }

    function handleGroupFormSubmit(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var url = form.action;
        var method = (form.querySelector('input[name="_method"]')?.value || 'POST').toUpperCase();
        if (method !== 'POST') {
            formData.set('_method', method);
        }

        document.getElementById('groupSubmitBtn').disabled = true;

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) {
                if (res.status === 422) {
                    return res.json().then(function (data) { throw data; });
                }
                if (!res.ok) {
                    throw new Error('Server error');
                }
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    refreshGroupsTable(data.groups, 'list');
                    resetGroupForm();
                    showGroupToast(data.message);
                }
            })
            .catch(function (err) {
                if (err && err.errors) {
                    showGroupFormErrors(err.errors);
                } else {
                    showGroupToast('Something went wrong. Please try again.', 'danger');
                }
            })
            .finally(function () {
                document.getElementById('groupSubmitBtn').disabled = false;
            });
    }

    function showGroupFormErrors(errors) {
        document.querySelectorAll('#add-group-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-group-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

        Object.keys(errors).forEach(function (field) {
            var input = document.querySelector('#add-group-pane [name="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
                var errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger small mt-1';
                errorDiv.textContent = errors[field].join(', ');
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    function showGroupToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('groupToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'groupToastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'app-toast app-toast-' + type;
        toast.innerHTML = '<span>' + message + '</span>';
        toast.onclick = function () { this.remove(); };
        container.appendChild(toast);
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 4000);
    }

    document.getElementById('manageGroupsModal').addEventListener('hidden.bs.modal', resetGroupForm);

    document.addEventListener('DOMContentLoaded', function () {
        var groupForm = document.getElementById('groupForm');
        if (groupForm) {
            groupForm.removeEventListener('submit', handleGroupFormSubmit);
            groupForm.addEventListener('submit', handleGroupFormSubmit);
        }

        document.querySelector('#group-list-pane').addEventListener('submit', async function (e) {
            var deleteForm = e.target.closest('.group-delete-form');
            if (!deleteForm) return;
            e.preventDefault();
            var name = deleteForm.dataset.groupName || 'this group';

            const confirmed = await appConfirm('Delete group ' + name + '?');
            if (!confirmed) return;

            var formData = new FormData(deleteForm);
            var url = deleteForm.action;

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (res) {
                    if (!res.ok) throw new Error('Server error');
                    return res.json();
                })
                .then(function (data) {
                    if (data.success) {
                        refreshGroupsTable(data.groups, 'list');
                        showGroupToast(data.message);
                    }
                })
                .catch(function () {
                    showGroupToast('Something went wrong. Please try again.', 'danger');
                });
        });
    });

    // === Documents Modal Logic ===
    let currentDocClientId = '';
    let currentDocClientName = '';

    function openDocumentsModal(btn, e) {
        if (e) {
            e.preventDefault();
        }

        currentDocClientId = btn.dataset.clientId || '';
        currentDocClientName = btn.dataset.clientName || '';
        document.getElementById('docClientId').value = currentDocClientId;
        document.getElementById('documentsModalLabel').textContent = currentDocClientName;
        resetDocumentForm();

        var modalEl = document.getElementById('documentsModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        loadDocuments(currentDocClientId);
    }

    document.querySelectorAll('.open-documents-modal').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            openDocumentsModal(btn, e);
        });
    });

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.open-documents-modal');
        if (!trigger) return;
        openDocumentsModal(trigger, e);
    });

    function openClientShowModal(clientId, clientName) {
        var modalEl = document.getElementById('clientShowModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var body = document.getElementById('clientShowModalBody');
        var title = document.getElementById('clientShowModalLabel');
        if (title) {
            title.textContent = clientName ? clientName + ' - Details' : 'Client Details';
        }
        if (body) {
            body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border" role="status"></div><p class="small mt-3 mb-0">Loading client details...</p></div>';
        }
        modal.show();

        fetch(clientShowUrlTemplate.replace('__CLIENT__', clientId), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Modal-View': 'client-show',
                'Accept': 'application/json'
            }
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('HTTP ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                if (data.success && body) {
                    body.innerHTML = data.html || '<p class="text-muted small mb-0">No details available.</p>';
                } else if (body) {
                    body.innerHTML = '<p class="text-muted small mb-0">Failed to load client details.</p>';
                }
            })
            .catch(function (err) {
                console.error('Client modal load error:', err);
                if (body) {
                    body.innerHTML = '<p class="text-muted small mb-0">Failed to load client details.</p>';
                }
            });
    }

    document.querySelectorAll('a[data-bs-target="#clientShowModal"]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openClientShowModal(btn.dataset.clientId, btn.dataset.clientName || '');
        });
    });

    function loadDocuments(clientId) {
        var tbody = document.getElementById('documentTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Loading...</td></tr>';

        var documentsUrl = documentsListUrlTemplate.replace('__CLIENT__', clientId);

        fetch(documentsUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    refreshDocumentsTable(data.documents);
                    var listTabEl = document.getElementById('document-list-tab');
                    if (listTabEl && data.documents && data.documents.length > 0) {
                        bootstrap.Tab.getOrCreateInstance(listTabEl).show();
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Failed to load documents.</td></tr>';
                }
            })
            .catch(function (err) {
                console.error('Documents load error:', err);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Failed to load documents.</td></tr>';
            });
    }

    function refreshDocumentsTable(documents) {
        var tbody = document.getElementById('documentTableBody');
        var listTabLabel = document.getElementById('documentListTabLabel');
        if (!documents || documents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-file-alt text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No documents yet. Add one above!</p>' +
                '</td></tr>';
        } else {
            tbody.innerHTML = documents.map(buildDocumentRow).join('');
        }
        if (listTabLabel) {
            listTabLabel.textContent = 'Document List (' + (documents ? documents.length : 0) + ')';
        }
    }

    function buildDocumentRow(doc) {
        var typeLabel = doc.type === 'po' ? 'Purchase Order' : 'Agreement';
        var typeBadge = doc.type === 'po'
            ? '<span class="border border-primary rounded-pill small lh-sm px-2 py-1 bg-primary text-white">PO</span>'
            : '<span class="border rounded-pill small lh-sm px-2 py-1 text-white" style="background-color: #346739; border-color: #346739;">Agreement</span>';
        var fileLink = doc.file_url
            ? '<a href="' + doc.file_url + '" target="_blank" class="bg01 color01 text-decoration-none">View</a>'
            : '';
        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var numberHtml = doc.document_number ? doc.document_number : '';
        var titleHtml = '<span class="d-block fw-semibold text-dark">' + (doc.title || '—') + '</span>';
        return '<tr>' +
            '<td>' + typeBadge + '</td>' +
            '<td>' + titleHtml + '</td>' +
            '<td>' + numberHtml + '</td>' +
            '<td>' + doc.document_date_display + '</td>' +
            '<td class="text-end">' +
            '<div class="tableActionButton d-inline-flex gap-1">' +
            fileLink +
            '<button type="button" class="bg03 color03 border-0" onclick="editDocument(this)"' +
            ' data-id="' + doc.client_docid + '"' +
            ' data-type="' + doc.type + '"' +
            ' data-title="' + (doc.title || '').replace(/"/g, '&quot;') + '"' +
            ' data-number="' + (doc.document_number || '').replace(/"/g, '&quot;') + '"' +
            ' data-date="' + (doc.document_date || '') + '">Edit</button>' +
            '<form class="d-inline document-delete-form" onsubmit="return false;">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="button" class="bg04 color04 border-0" onclick="deleteDocument(\'' + doc.client_docid + '\', this)">Delete</button>' +
            '</form>' +
            '</div>' +
            '</td>' +
            '</tr>';
    }

    function editDocument(btn) {
        var id = btn.dataset.id;
        var type = btn.dataset.type;
        var title = btn.dataset.title;
        var number = btn.dataset.number;
        var date = btn.dataset.date;

        var form = document.getElementById('documentForm');
        var submitBtn = document.getElementById('documentSubmitBtn');
        var cancelBtn = document.getElementById('documentCancelBtn');
        var methodField = document.getElementById('docMethodField');

        form.action = '{{ url("clients") }}/' + currentDocClientId + '/documents/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('docId').value = id;
        document.getElementById('docType').value = type;
        document.getElementById('docTitle').value = title;
        document.getElementById('docNumber').value = number;
        document.getElementById('docDate').value = date;

        submitBtn.innerHTML = 'Update Document <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.remove('d-none');
        }

        document.getElementById('documentTabTitle').innerText = 'Edit Document';
        var addTabEl = document.getElementById('add-document-tab');
        if (addTabEl) addTabEl.click();

        setTimeout(function () { document.getElementById('docTitle').focus(); }, 150);
    }

    function deleteDocument(docId, btn) {
        if (!confirm('Delete this document?')) return;
        var form = btn.closest('form');
        var formData = new FormData(form);
        formData.append('clientid', currentDocClientId);

        fetch(clientsBaseUrl + '/clients/' + currentDocClientId + '/documents/' + docId, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    refreshDocumentsTable(data.documents);
                    showDocToast(data.message);
                }
            })
            .catch(function () {
                showDocToast('Something went wrong. Please try again.', 'danger');
            });
    }

    function resetDocumentForm() {
        var form = document.getElementById('documentForm');
        var submitBtn = document.getElementById('documentSubmitBtn');
        var cancelBtn = document.getElementById('documentCancelBtn');
        var methodField = document.getElementById('docMethodField');

        form.action = clientsBaseUrl + '/clients/' + currentDocClientId + '/documents';
        methodField.innerHTML = '';
        document.getElementById('docId').value = '';
        document.getElementById('docType').value = '';
        document.getElementById('docTitle').value = '';
        document.getElementById('docNumber').value = '';
        document.getElementById('docDate').value = '';
        document.getElementById('docFile').value = '';
        if (window._resetDocUpload) window._resetDocUpload();

        submitBtn.innerHTML = 'Save Document <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        if (cancelBtn) {
            cancelBtn.classList.add('d-none');
        }
        document.getElementById('documentTabTitle').innerText = 'Add Document';

        document.querySelectorAll('#add-document-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-document-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function handleDocumentFormSubmit(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        formData.set('clientid', currentDocClientId);
        var url = form.action;
        var method = (form.querySelector('input[name="_method"]')?.value || 'POST').toUpperCase();
        if (method !== 'POST') {
            formData.set('_method', method);
        }

        document.getElementById('documentSubmitBtn').disabled = true;

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
            .then(function (res) {
                if (res.status === 422) {
                    return res.json().then(function (data) { throw data; });
                }
                if (!res.ok) throw new Error('Server error');
                return res.json();
            })
            .then(function (data) {
                if (data.success) {
                    refreshDocumentsTable(data.documents);
                    resetDocumentForm();
                    showDocToast(data.message);
                    var listTabEl = document.getElementById('document-list-tab');
                    if (listTabEl) listTabEl.click();
                }
            })
            .catch(function (err) {
                if (err && err.errors) {
                    showDocFormErrors(err.errors);
                } else {
                    showDocToast('Something went wrong. Please try again.', 'danger');
                }
            })
            .finally(function () {
                document.getElementById('documentSubmitBtn').disabled = false;
            });
    }

    function showDocFormErrors(errors) {
        document.querySelectorAll('#add-document-pane .text-danger.small.mt-1').forEach(function (el) { el.remove(); });
        document.querySelectorAll('#add-document-pane .is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

        Object.keys(errors).forEach(function (field) {
            var input = document.querySelector('#add-document-pane [name="' + field + '"]');
            if (input) {
                input.classList.add('is-invalid');
                var errorDiv = document.createElement('div');
                errorDiv.className = 'text-danger small mt-1';
                errorDiv.textContent = errors[field].join(', ');
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    function showDocToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('docToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'docToastContainer';
            container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999';
            document.body.appendChild(container);
        }
        var toast = document.createElement('div');
        toast.className = 'app-toast app-toast-' + type;
        toast.innerHTML = '<span>' + message + '</span>';
        toast.onclick = function () { this.remove(); };
        container.appendChild(toast);
        setTimeout(function () { if (toast.parentNode) toast.remove(); }, 4000);
    }

    // Document Upload Drag & Drop
    (function () {
        var dropZone = document.getElementById('docUploadDropZone');
        var fileInput = document.getElementById('docFile');
        var prompt = document.getElementById('docDropPrompt');
        var preview = document.getElementById('docDropPreview');
        var previewImg = document.getElementById('docPreviewImg');
        var fileIcon = document.getElementById('docFileIcon');
        var fileName = document.getElementById('docFileName');
        var removeBtn = document.getElementById('docRemoveBtn');

        if (!dropZone || !fileInput) return;

        ['dragenter', 'dragover'].forEach(function (ev) {
            dropZone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function (ev) {
            dropZone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            }, false);
        });

        fileInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) {
                resetDocUpload();
                return;
            }
            fileName.textContent = file.name;
            previewImg.classList.add('d-none');
            fileIcon.classList.add('d-none');
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                fileIcon.classList.remove('d-none');
            }
            prompt.classList.add('d-none');
            preview.classList.remove('d-none');
            preview.classList.add('d-flex');
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.value = '';
                fileInput.dispatchEvent(new Event('change'));
            });
        }

        function resetDocUpload() {
            prompt.classList.remove('d-none');
            prompt.classList.add('d-flex');
            preview.classList.add('d-none');
            preview.classList.remove('d-flex');
            previewImg.classList.add('d-none');
            fileIcon.classList.add('d-none');
            fileName.textContent = '';
        }

        // Expose so resetDocumentForm can call it
        window._resetDocUpload = resetDocUpload;
    })();

    document.getElementById('documentsModal').addEventListener('hidden.bs.modal', function () {
        resetDocumentForm();
        currentDocClientId = '';
        currentDocClientName = '';
    });

    document.addEventListener('DOMContentLoaded', function () {
        var docForm = document.getElementById('documentForm');
        if (docForm) {
            docForm.removeEventListener('submit', handleDocumentFormSubmit);
            docForm.addEventListener('submit', handleDocumentFormSubmit);
        }
    });

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
    });
</script>
@endsection
