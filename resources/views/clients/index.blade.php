@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.create') }}" class="primary-button">Add Client</a>
    <button type="button" class="secondary-button" data-bs-toggle="modal" data-bs-target="#manageGroupsModal"><i class="fas fa-layer-group" class="icon-spaced-sm"></i>Manage Groups</button>
@endsection

@section('content')
    <section class="panel-card" class="no-padding">
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
                            <div class="text-xs text-muted"><i class="fas fa-phone" class="icon-small icon-spaced-sm"></i>{{ $client['phone'] }}</div>
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
                    <td class="table-actions">
                        <a href="{{ route('clients.show', $client['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('clients.edit', $client['record_id']) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $client['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="no-records-cell">
                        <i class="fas fa-users" class="empty-state-icon"></i>
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
    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 650px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold"><i class="fas fa-layer-group me-2 text-muted"></i>Manage Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="bg-light p-3 rounded border">
                    @csrf
                    <input type="hidden" id="groupId" name="_group_id" value="">
                    <div id="methodField"></div>
                    <h6 id="groupFormTitle" class="mb-3 fw-semibold">Add New Group</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Group Name *</label>
                        <input type="text" name="group_name" id="groupName" value="{{ old('group_name') }}" required maxlength="150" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email</label>
                        <input type="email" name="email" id="groupEmail" value="{{ old('email') }}" maxlength="150" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Address Line 1</label>
                        <textarea name="address_line_1" id="groupAddress1" rows="2" maxlength="150" class="form-control">{{ old('address_line_1') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Address Line 2</label>
                        <textarea name="address_line_2" id="groupAddress2" rows="2" maxlength="150" class="form-control">{{ old('address_line_2') }}</textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Country</label>
                            <select id="groupCountry" name="country" class="form-select" data-selected="India">
                                <option value="">Select Country</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">State</label>
                            <select id="groupState" name="state" class="form-select" data-selected="">
                                <option value="">Select State</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">City</label>
                            <select id="groupCity" name="city" class="form-select" data-selected="">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Postal Code</label>
                            <input type="text" name="postal_code" id="groupPostalCode" value="{{ old('postal_code') }}" maxlength="20" class="form-control">
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button type="submit" id="groupSubmitBtn" class="btn btn-primary btn-sm">Save Group</button>
                        <button type="button" id="groupCancelBtn" class="btn btn-link btn-sm d-none" onclick="resetGroupForm()">Cancel Edit</button>
                    </div>
                </form>

                <!-- Groups List -->
                <div style="margin-top: 1rem; max-height: 220px; overflow-y: auto;">
                    <h6 style="margin: 0 0 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">{{ $groups->count() }} Groups</h6>
                    @forelse($groups as $group)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 0.25rem; background: #f8fafc;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.4rem;">
                                    <div style="width: 24px; height: 24px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; flex-shrink: 0;"><i class="fas fa-users"></i></div>
                                    <div>
                                        <strong style="font-size: 0.82rem;">{{ $group->group_name }}</strong>
                                        @if($group->email)
                                            <div style="font-size: 0.72rem; color: #64748b;">{{ $group->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.2rem; align-items: center;">
                                <button type="button" style="padding: 0.25rem 0.4rem; font-size: 0.72rem; background: transparent; border: none; color: #6b7280; cursor: pointer;" onclick="editGroup('{{ $group->groupid }}', '{{ addslashes($group->group_name) }}', '{{ addslashes($group->email ?? '') }}', '{{ addslashes($group->address_line_1 ?? '') }}', '{{ addslashes($group->address_line_2 ?? '') }}', '{{ addslashes($group->city ?? '') }}', '{{ addslashes($group->state ?? '') }}', '{{ addslashes($group->postal_code ?? '') }}', '{{ addslashes($group->country ?? '') }}')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('groups.destroy', $group->groupid) }}" style="display: inline;" onsubmit="return confirm('Delete this group?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="padding: 0.25rem 0.4rem; font-size: 0.72rem; background: transparent; border: none; color: #ef4444; cursor: pointer;" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div style="text-align: center; padding: 1.25rem; color: #94a3b8;">
                            <i class="fas fa-folder-open" style="font-size: 1.25rem; margin-bottom: 0.4rem; opacity: 0.3;"></i>
                            <p style="margin: 0; font-size: 0.82rem;">No groups yet. Create one above!</p>
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

