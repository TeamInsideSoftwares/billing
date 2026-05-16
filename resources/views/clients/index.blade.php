@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('clients.create') }}" class="primary-button">Add Client</a>
        <button type="button" class="secondary-button" data-bs-toggle="modal" data-bs-target="#manageGroupsModal"><i class="fas fa-layer-group icon-spaced-sm"></i>Manage Groups</button>
    </div>
@endsection

@section('content')
    <section class="panel-card module-filter-panel filter-panel-regular">
        <form action="{{ route('clients.index') }}" method="GET" class="module-filter-grid">
            <div class="module-filter-field">
                <label class="module-filter-label" for="clients_state_filter">State</label>
                <select name="state" id="clients_state_filter" class="form-control">
                    <option value="">All States</option>
                    @foreach(($stateOptions ?? collect()) as $stateOption)
                        <option value="{{ $stateOption }}" {{ (string) ($selectedState ?? '') === (string) $stateOption ? 'selected' : '' }}>
                            {{ $stateOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="module-filter-field">
                <label class="module-filter-label" for="clients_city_filter">City</label>
                <select name="city" id="clients_city_filter" class="form-control">
                    <option value="">All Cities</option>
                    @foreach(($cityOptions ?? collect()) as $cityOption)
                        <option value="{{ $cityOption }}" {{ (string) ($selectedCity ?? '') === (string) $cityOption ? 'selected' : '' }}>
                            {{ $cityOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="module-filter-field">
                <label class="module-filter-label" for="clients_search_filter">Search</label>
                <input type="text" name="search" id="clients_search_filter" class="form-control" value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person">
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="primary-button">Apply</button>
                <a href="{{ route('clients.index') }}" class="secondary-button">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel-card no-padding">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>State</th>
                    <th>Outstanding</th>
                    <th>Invoices</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>
                        <div class="flex-center-gap">
                            <div class="avatar-box">
                                {{ strtoupper(substr($client['name'], 0, 2)) }}
                            </div>
                            <div>
                                <strong class="text-highlight">{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $client['name']) : $client['name'] !!}</strong>
                                <div class="text-xs text-muted">{{ $client['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($client['contact'])
                            <div class="small-text">{{ $client['contact'] }}</div>
                        @endif
                        @if($client['phone'])
                            <div class="text-xs text-muted"><i class="fas fa-phone icon-small icon-spaced-sm"></i>{{ $client['phone'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="small-text">{{ $client['state'] ?? '—' }}</div>
                        <div class="text-xs text-muted">{{ $client['currency'] }}</div>
                    </td>
                    <td>
                        <strong class="balance-text">
                            {{ $client['balance'] }}
                        </strong>
                    </td>
                    <td>
                        <span class="highlight-text-color">
                            {{ $client['invoice_count'] }}
                        </span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($client['status']) }}">{{ ucfirst(strtolower($client['status'])) }}</span>
                    </td>
                    <td class="">
                        <a href="{{ route('clients.show', $client['record_id']) }}" class="text-action-btn view">View</a>
                        <a href="{{ route('clients.documents.create', ['client' => $client['record_id']]) }}" class="text-action-btn edit">PO & Agreement</a>
                        <a href="{{ route('clients.edit', $client['record_id']) }}" class="text-action-btn edit">Edit</a>
                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $client['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-action-btn delete">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="no-records-cell">
                        <i class="fas fa-users empty-state-icon"></i>
                        <p class="no-empty-state-text">No clients found</p>
                        <p class="small-text">Get started by adding your first client.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <!-- Manage Groups Modal -->
    <div class="modal fade" id="manageGroupsModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered modal-650">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="fas fa-layer-group me-2 text-muted"></i>Manage Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="panel-note">
                    @csrf
                    <input type="hidden" id="groupId" name="_group_id" value="">
                    <div id="methodField"></div>
                    <h6 id="groupFormTitle" class="modal-subtitle">Add New Group</h6>
                    <div class="form-grid grid-cols-2">
                        <div>
                            <label class="label-compact">Group Name *</label>
                            <input type="text" name="group_name" id="groupName" value="{{ old('group_name') }}" required>
                        </div>
                        <div>
                            <label class="label-compact">Email</label>
                            <input type="email" name="email" id="groupEmail" value="{{ old('email') }}">
                        </div>
                        <div>
                            <label class="label-compact">Address Line 1</label>
                            <input type="text" name="address_line_1" id="groupAddress1" value="{{ old('address_line_1') }}">
                        </div>
                        <div>
                            <label class="label-compact">Address Line 2</label>
                            <input type="text" name="address_line_2" id="groupAddress2" value="{{ old('address_line_2') }}">
                        </div>
                        <div>
                            <label class="label-compact">Country</label>
                            <select id="groupCountry" name="country" class="country-select" data-selected="India">
                                <option value="">Select Country</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-compact">State</label>
                            <select id="groupState" name="state" class="state-select" data-selected="">
                                <option value="">Select State</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-compact">City</label>
                            <select id="groupCity" name="city" class="city-select" data-selected="">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div>
                            <label class="label-compact">Postal Code</label>
                            <input type="text" name="postal_code" id="groupPostalCode" value="{{ old('postal_code') }}">
                        </div>
                    </div>
                    <div class="flex-between">
                        <button type="submit" id="groupSubmitBtn" class="primary-button small">Save Group</button>
                        <button type="button" id="groupCancelBtn" class="text-link small hidden" onclick="resetGroupForm()">Cancel Edit</button>
                    </div>
                </form>

                <!-- Groups List -->
                <div class="group-list-wrap">
                    <h6 class="group-list-title">{{ $groups->count() }} Groups</h6>
                    @forelse($groups as $group)
                        <div class="group-list-item">
                            <div class="flex-fill">
                                <div class="group-list-item-head">
                                    <div class="group-list-item-icon"><i class="fas fa-users"></i></div>
                                    <div>
                                        <strong class="group-list-item-name">{{ $group->group_name }}</strong>
                                        @if($group->email)
                                            <div class="group-list-item-email">{{ $group->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                                <div class="">
                                    <button type="button" class="text-action-btn edit" onclick="editGroup('{{ $group->groupid }}', '{{ addslashes($group->group_name) }}', '{{ addslashes($group->email ?? '') }}', '{{ addslashes($group->address_line_1 ?? '') }}', '{{ addslashes($group->address_line_2 ?? '') }}', '{{ addslashes($group->city ?? '') }}', '{{ addslashes($group->state ?? '') }}', '{{ addslashes($group->postal_code ?? '') }}', '{{ addslashes($group->country ?? '') }}')">Edit</button>
                                    <form method="POST" action="{{ route('groups.destroy', $group->groupid) }}" class="inline-delete" onsubmit="return confirm('Delete group {{ $group->group_name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-action-btn delete">Delete</button>
                                    </form>
                                </div>
                        </div>
                    @empty
                        <div class="group-list-empty">
                            <i class="fas fa-folder-open empty-state-icon-sm"></i>
                            <p class="group-list-empty-text">No groups yet. Create one above!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
    function editGroup(id, name, email, addr1, addr2, city, state, postal, country) {
        const form = document.getElementById('groupForm');
        const title = document.getElementById('groupFormTitle');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('methodField');

        form.action = 'groups/' + id;
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

        document.getElementById('groupName').value = name;
        document.getElementById('groupEmail').value = email;
        document.getElementById('groupAddress1').value = addr1;
        document.getElementById('groupAddress2').value = addr2;
        document.getElementById('groupPostalCode').value = postal;

        // Set country, then state, then city (cascade order)
        const countryEl = document.getElementById('groupCountry');
        const stateEl = document.getElementById('groupState');
        const cityEl = document.getElementById('groupCity');

        countryEl.dataset.selected = country || 'India';
        stateEl.dataset.selected = state || '';
        cityEl.dataset.selected = city || '';

        // Trigger change events to populate cascading dropdowns
        countryEl.dispatchEvent(new Event('change'));
        setTimeout(() => { stateEl.dispatchEvent(new Event('change')); }, 300);

        title.innerText = 'Editing Group';
        submitBtn.innerText = 'Update Now';
        cancelBtn.style.display = 'inline-block';
        document.getElementById('groupName').focus();
    }

    function resetGroupForm() {
        const form = document.getElementById('groupForm');
        const title = document.getElementById('groupFormTitle');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('methodField');

        form.action = "{{ route('groups.store') }}";
        methodField.innerHTML = '';
        form.reset();

        title.innerText = 'Add New Group';
        submitBtn.innerText = 'Save Group';
        cancelBtn.style.display = 'none';
    }

    document.getElementById('manageGroupsModal').addEventListener('hidden.bs.modal', resetGroupForm);
    </script>
@endsection
