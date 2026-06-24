@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <button type="button"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium"
        data-bs-toggle="modal" data-bs-target="#manageGroupsModal">
        <i class="fas fa-layer-group btn-icon"></i> Client Groups
    </button>
    <button type="button"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium"
        data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
        <i class="fas fa-tags btn-icon"></i> Client Categories
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
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
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
                    <select name="state" id="clients_state_filter" class="form-select" onchange="this.form.submit()">
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
                    <select name="city" id="clients_city_filter" class="form-select" onchange="this.form.submit()">
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
    <div id="clients-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Contact Person</th>
                        <th>State</th>
                        <th class="text-end">Outstanding</th>
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
                                        title="{{ ucfirst($client['status']) }}"
                                        id="status-dot-{{ $client['record_id'] }}"></div>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold">{!! $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm
                                            . '</mark>', $client['name'])
                                        : $client['name'] !!}</span>
                                    <span class="d-block text-dark small">{{ $client['email'] }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($client['contact'])
                            <div class="fw-medium">{{ $client['contact'] }}</div>
                            @endif
                            @if ($client['phone'])
                            <small class="text-dark">{{ $client['phone'] }}</small>
                            @endif
                        </td>
                        <td>{{ $client['state'] ?? '—' }}</td>
                        <td class="text-end">
                            @php
                            $rawVal = (float) str_replace([$client['currency'], ' ', ','], '', $client['balance']);
                            $balanceClass = $rawVal < 0 ? 'text-danger' : ($rawVal> 0 ? 'text-success' : 'text-dark');
                                @endphp
                                <span class="fw-semibold {{ $balanceClass }}"> {{
                                    substr($client['balance'], strlen($client['currency']) + 1) }}
                                    <span class="currency-code-small text-muted d-block">{{ $client['currency']
                                        }}</span>
                                </span>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1 align-items-center">
                                <div class="form-check form-switch mb-0 d-inline-flex align-items-center me-1"
                                    style="padding-left: 2.5em; min-height: auto;">
                                    <input class="form-check-input client-status-toggle border-primary" type="checkbox" role="switch"
                                        data-id="{{ $client['record_id'] }}" {{ strtolower($client['status'])==='active'
                                        ? 'checked' : '' }} style="cursor: pointer; height: 1.15em; width: 2.1em;"
                                        title="Toggle Status">
                                </div>
                                <a href="{{ route('clients.dashboard', $client['record_id']) }}"
                                    data-client-id="{{ $client['record_id'] }}" class="bg01 color01">View</a>
                                <a href="#" class="bg02 color02 open-documents-modal" data-bs-toggle="modal"
                                    data-bs-target="#documentsModal" data-client-id="{{ $client['record_id'] }}"
                                    data-client-name="{{ $client['name'] }}">PO &
                                    Agreement</a>
                                <a href="{{ route('clients.edit', $client['record_id']) }}"
                                    class="bg03 color03">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
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
        class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-2 p-1 pb-3 mt-2 bg-DarkLight rounded-3 d-none">
        @forelse ($clients as $client)
        <div class="col">
            <div class="card h-100 border-0 overflow-hidden">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Flex Avatar, Info -->
                        <div class="d-flex align-items-center gap-2">
                            <div
                                class="tablePrifix position-relative align-self-center bg-primary-subtle text-primary rounded-circle fw-semibold flex-shrink-0">
                                <span class="d-block position-absolute">{{ strtoupper(substr($client['name'], 0, 2))
                                    }}</span>
                                <div class="status-dot {{ strtolower($client['status']) }}"
                                    title="{{ ucfirst($client['status']) }}"
                                    id="status-dot-grid-{{ $client['record_id'] }}"></div>
                            </div>
                            <div class="flex-grow-1 min-w-0 ps-2">
                                <h6 class="fw-bold text-dark mb-0 text-truncate lh-sm" title="{{ $client['name'] }}">
                                    {!! $searchTerm
                                    ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm .
                                        '</mark>', $client['name'])
                                    : $client['name'] !!}
                                </h6>
                                <span class="d-block text-dark lh-sm text-break grid-text-medium"
                                    title="{{ $client['email'] }}">{{ $client['email'] }}</span>
                            </div>
                        </div>



                        <!-- Contact info -->
                        <div class="mb-3 border-top pt-3 mt-3 grid-text-medium text-muted">
                            @if ($client['contact'])
                            <div class="text-dark fw-semibold lh-sm text-truncate mb-1"
                                title="{{ $client['contact'] }}">
                                <i class="fas fa-user contact-icon me-2 text-muted"></i>{{ $client['contact'] }}
                            </div>
                            @endif
                            @if ($client['phone'])
                            <div class="text-dark text-truncate lh-sm mb-1" title="{{ $client['phone'] }}">
                                <i class="fas fa-phone contact-icon me-1 text-muted"></i>{{ $client['phone'] }}
                            </div>
                            @endif
                            <div class="text-dark lh-sm text-truncate" title="{{ $client['state'] ?? '—' }}">
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
                                <strong class="{{ $balanceClassGrid }} fw-semibold grid-value-large text-end"> {{
                                    substr($client['balance'], strlen($client['currency']) + 1) }}
                                    <span class="currency-code-grid text-muted small lh-sm d-block">{{
                                        $client['currency']
                                        }}</span>
                                </strong>
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
                        <div class="form-check form-switch mb-0 d-inline-flex align-items-center justify-content-center flex-grow-1"
                            style="padding-left: 2.5em; min-height: 30px;">
                            <input class="form-check-input client-status-toggle border-primary" type="checkbox" role="switch"
                                data-id="{{ $client['record_id'] }}" {{ strtolower($client['status'])==='active'
                                ? 'checked' : '' }} style="cursor: pointer; height: 1.15em; width: 2.1em;"
                                title="Toggle Status">
                        </div>
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
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="manageGroupsModalLabel">Client Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Group Form -->
                <div id="add-group-pane" class="bg-DarkLight p-2 rounded-3 mb-3">
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
                                <div class="p-2 rounded-3 h-100 form-grid bg-light">
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
                                <div class="p-2 rounded-3 h-100 form-grid bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-semibold text-primary mb-0">Business Address</h6>
                                        <div class="mb-0 bg-white border rounded-1 px-1 py-0">
                                            <div class="form-check mb-0 form-check-small">
                                                <input class="form-check-input border-primary border-2" type="checkbox"
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

                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="submit" id="groupSubmitBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                Save Client Group <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Groups List -->
                <div id="group-list-pane" class="position-relative bg-DarkLight p-2 rounded-3">
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

<!-- Manage Categories Modal -->
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-labelledby="manageCategoriesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="manageCategoriesModalLabel">Client Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <!-- Category Form -->
                <div id="add-category-pane" class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="categoryForm" method="POST" action="{{ route('client-categories.store') }}" class="mainForm">
                        @csrf
                        <div id="categoryMethodField"></div>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-8">
                                <label for="categoryName" class="form-label small lh-sm fw-semibold text-dark mb-1">Category Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="categoryName" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-4 d-flex">
                                <button type="submit" id="categorySubmitBtn" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                                    Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Categories List -->
                <div id="category-list-pane" class="position-relative bg-DarkLight p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span>Category List ({{ $categories->count() }})</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="15%">Seq</th>
                                        <th width="55%">Category Name</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $category)
                                    <tr>
                                        <td>
                                            <form method="POST" action="{{ route('client-categories.update-sequence', $category->categoryid) }}" class="category-sequence-form">
                                                @csrf @method('PATCH')
                                                <select name="sequence" class="form-select form-select-sm category-sequence-select" style="width: 70px;">
                                                    @for ($i = 1; $i <= $categories->count(); $i++)
                                                        <option value="{{ $i }}" {{ ($category->sequence ?? $loop->parent->iteration) == $i ? 'selected' : '' }}>
                                                            {{ $i }}
                                                        </option>
                                                    @endfor
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                                    <span class="d-block position-absolute">{{ strtoupper(substr($category->name, 0, 2)) }}</span>
                                                </div>
                                                <div>
                                                    <span class="d-block fw-semibold">{{ $category->name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <button type="button" class="bg03 color03 border-0"
                                                    onclick="editCategory(this)" data-id="{{ $category->categoryid }}"
                                                    data-name="{{ $category->name }}">Edit</button>
                                                <form method="POST" action="{{ route('client-categories.destroy', $category->categoryid) }}" class="d-inline category-delete-form">
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
                                            <i class="fas fa-tags text-muted mb-2 fs-2 opacity-50"></i>
                                            <p class="text-muted small mb-0">No categories yet. Create one above!</p>
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

@include('clients.partials.documents-modal')

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

    function editCategory(btn) {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const sequence = btn.dataset.sequence;

        const form = document.getElementById('categoryForm');
        const submitBtn = document.getElementById('categorySubmitBtn');
        const cancelBtn = document.getElementById('categoryCancelBtn');
        const methodField = document.getElementById('categoryMethodField');

        form.action = 'client-categories/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('categoryName').value = name;

        submitBtn.innerHTML = 'Update Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';

        setTimeout(() => {
            document.getElementById('categoryName').focus();
        }, 150);
    }

    function resetCategoryForm() {
        const form = document.getElementById('categoryForm');
        const submitBtn = document.getElementById('categorySubmitBtn');
        const cancelBtn = document.getElementById('categoryCancelBtn');
        const methodField = document.getElementById('categoryMethodField');

        form.action = "{{ route('client-categories.store') }}";
        methodField.innerHTML = '';
        form.reset();

        submitBtn.innerHTML = 'Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';
    }

    function buildCategoryRow(category, totalCategories, currentIndex) {
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var prefix = category.name ? category.name.substring(0, 2).toUpperCase() : '';
        
        var seqOptions = '';
        for (var i = 1; i <= totalCategories; i++) {
            var selected = (category.sequence || (currentIndex + 1)) == i ? 'selected' : '';
            seqOptions += '<option value="' + i + '" ' + selected + '>' + i + '</option>';
        }

        return '<tr>' +
            '<td>' +
            '<form method="POST" action="client-categories/' + category.categoryid + '/sequence" class="category-sequence-form">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="PATCH">' +
            '<select name="sequence" class="form-select form-select-sm category-sequence-select" style="width: 70px;">' +
            seqOptions +
            '</select>' +
            '</form>' +
            '</td>' +
            '<td>' +
            '<div class="d-flex align-items-center gap-3">' +
            '<div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">' +
            '<span class="d-block position-absolute">' + prefix + '</span>' +
            '</div>' +
            '<div>' +
            '<span class="d-block fw-semibold">' + (category.name || '').replace(/"/g, '&quot;') + '</span>' +
            '</div>' +
            '</div>' +
            '</td>' +
            '<td class="text-end">' +
            '<div class="tableActionButton d-inline-flex gap-1">' +
            '<button type="button" class="bg03 color03 border-0" onclick="editCategory(this)" data-id="' + category.categoryid + '" data-name="' + (category.name || '').replace(/"/g, '&quot;') + '" data-sequence="' + category.sequence + '">Edit</button>' +
            '<form method="POST" action="client-categories/' + category.categoryid + '" class="d-inline category-delete-form">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="submit" class="bg04 color04 border-0">Delete</button>' +
            '</form>' +
            '</div>' +
            '</td>' +
            '</tr>';
    }

    function refreshCategoriesTable(categories) {
        var tbody = document.querySelector('#category-list-pane tbody');
        if (categories.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted bg-white">' +
                '<i class="fas fa-tags text-muted mb-2 fs-2 opacity-50"></i>' +
                '<p class="text-muted small mb-0">No categories yet. Create one above!</p>' +
                '</td></tr>';
        } else {
            var totalCategories = categories.length;
            var html = '';
            categories.forEach(function(cat, idx) {
                html += buildCategoryRow(cat, totalCategories, idx);
            });
            tbody.innerHTML = html;
        }
        document.querySelector('#category-list-pane h6 span').textContent = 'Category List (' + categories.length + ')';
    }

    function handleCategoryFormSubmit(e) {
        e.preventDefault();
        var form = this;
        var formData = new FormData(form);
        var url = form.action;
        var method = (form.querySelector('input[name="_method"]')?.value || 'POST').toUpperCase();
        if (method !== 'POST') {
            formData.set('_method', method);
        }

        document.getElementById('categorySubmitBtn').disabled = true;

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
                    refreshCategoriesTable(data.categories);
                    resetCategoryForm();
                    showGroupToast(data.message);
                }
            })
            .catch(function (err) {
                showGroupToast('Something went wrong. Please try again.', 'danger');
            })
            .finally(function () {
                document.getElementById('categorySubmitBtn').disabled = false;
            });
    }

    document.getElementById('manageCategoriesModal').addEventListener('hidden.bs.modal', resetCategoryForm);

    const clientsIndexUrl = "{{ route('clients.index') }}";
    const clientsBaseUrl = "{{ url('/') }}";
    const documentsListUrlTemplate = "{{ route('clients.documents.list', ['client' => '__CLIENT__']) }}";

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
        if (typeof window.showToast === 'function') {
            window.showToast(type || 'success', message);
        }
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

            const confirmed = await window.appConfirm('Delete group ' + name + '?');
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

        var categoryForm = document.getElementById('categoryForm');
        if (categoryForm) {
            categoryForm.removeEventListener('submit', handleCategoryFormSubmit);
            categoryForm.addEventListener('submit', handleCategoryFormSubmit);
        }

        document.querySelector('#category-list-pane').addEventListener('submit', async function (e) {
            var deleteForm = e.target.closest('.category-delete-form');
            if (!deleteForm) return;
            e.preventDefault();

            const confirmed = await window.appConfirm('Delete this category?');
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
                        refreshCategoriesTable(data.categories);
                        showGroupToast(data.message);
                    }
                })
                .catch(function () {
                    showGroupToast('Something went wrong. Please try again.', 'danger');
                });
        });

        document.querySelector('#category-list-pane').addEventListener('change', function (e) {
            if (e.target.classList.contains('category-sequence-select')) {
                var sequenceForm = e.target.closest('.category-sequence-form');
                if (!sequenceForm) return;

                var formData = new FormData(sequenceForm);
                var url = sequenceForm.action;

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
                            refreshCategoriesTable(data.categories);
                            showGroupToast(data.message);
                        }
                    })
                    .catch(function () {
                        showGroupToast('Something went wrong updating sequence.', 'danger');
                    });
            }
        });
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



        document.querySelectorAll('.client-status-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', async function () {
                const clientId = this.getAttribute('data-id');
                const isChecked = this.checked;
                const newStatus = isChecked ? 'active' : 'inactive';

                // Confirm action
                const msg = isChecked
                    ? "Are you sure? All login details will also be activated."
                    : "Are you sure? All login details will also be deactivated.";

                const isConfirmed = await window.appConfirm(msg, {
                    title: 'Please Confirm',
                    icon: 'warning',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel'
                });

                if (!isConfirmed) {
                    document.querySelectorAll(`.client-status-toggle[data-id="${clientId}"]`).forEach(cb => {
                        cb.checked = !isChecked;
                    });
                    return;
                }

                // Sync other toggle switches for the same client (grid and list views)
                document.querySelectorAll(`.client-status-toggle[data-id="${clientId}"]`).forEach(cb => {
                    if (cb !== this) cb.checked = isChecked;
                });

                // Disable switches during AJAX request
                const toggles = document.querySelectorAll(`.client-status-toggle[data-id="${clientId}"]`);
                toggles.forEach(cb => cb.disabled = true);

                try {
                    const response = await fetch(clientsBaseUrl + '/clients/' + clientId + '/toggle-status', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ status: newStatus })
                    });

                    if (response.ok) {
                        const result = await response.json();
                        // Update status dots
                        const dots = [
                            document.getElementById(`status-dot-${clientId}`),
                            document.getElementById(`status-dot-grid-${clientId}`)
                        ];
                        dots.forEach(dot => {
                            if (dot) {
                                dot.className = `status-dot ${newStatus}`;
                                dot.setAttribute('title', newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                            }
                        });

                        showGroupToast(result.message || 'Status updated successfully.');
                    } else {
                        toggles.forEach(cb => cb.checked = !isChecked);
                        alert('Failed to update status.');
                    }
                } catch (error) {
                    console.error('Failed to toggle status', error);
                    toggles.forEach(cb => cb.checked = !isChecked);
                    alert('An error occurred while updating status.');
                } finally {
                    toggles.forEach(cb => cb.disabled = false);
                }
            });
        });
    });
</script>
@endsection
