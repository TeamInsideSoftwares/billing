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
        <div class="modal-dialog modal-lg modal-dialog-centered" class="modal-width-lg">
            <div class="modal-content" class="rounded-panel">
                <div class="modal-header" class="modal-header-custom">
                    <h5 class="modal-title" class="modal-title-strong"><i class="fas fa-layer-group" class="icon-spaced text-muted"></i>Manage Groups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" class="modal-body-custom">
                    <!-- Add/Edit Group Form -->
                    <div class="section-divider">
                        <h6 id="groupFormTitle" class="modal-subtitle"><i class="fas fa-plus-circle" class="icon-spaced-sm text-muted"></i>Add New Group</h6>
                        <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="panel-note">
                            @csrf
                            <input type="hidden" id="groupId" name="_group_id" value="">
                            <div class="grid-cols-2">
                                <div >
                                    <label class="label-compact">Group Name *</label>
                                    <input type="text" name="group_name" id="groupName" required maxlength="150" class="input-full">
                                </div>
                                <div >
                                    <label class="label-compact">Email</label>
                                    <input type="email" name="email" id="groupEmail" maxlength="150" class="input-full">
                                </div>
                                <div >
                                    <label class="label-compact">Address Line 1</label>
                                    <textarea name="address_line_1" id="groupAddress1" rows="2" maxlength="150" class="input-full textarea-auto">{{ old('address_line_1') }}</textarea>
                                </div>
                                <div >
                                    <label class="label-compact">Address Line 2</label>
                                    <textarea name="address_line_2" id="groupAddress2" rows="2" maxlength="150" class="input-full textarea-auto">{{ old('address_line_2') }}</textarea>
                                </div>
                                <div>
                                    <label class="label-compact">Country</label>
                                    <select id="groupCountry" name="country" class="country-select" data-selected="India" class="input-full">
                                        <option value="">Select Country</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label-compact">State</label>
                                    <select id="groupState" name="state" class="state-select" data-selected="" class="input-full">
                                        <option value="">Select State</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label-compact">City</label>
                                    <select id="groupCity" name="city" class="city-select" data-selected="" class="input-full">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label-compact">Postal Code</label>
                                    <input type="text" name="postal_code" id="groupPostalCode" maxlength="20" class="input-full">
                                </div>
                            </div>
                            <div class="flex-between">
                                <button type="submit" id="groupSubmitBtn" class="primary-button small">Save Group</button>
                                <button type="button" id="groupCancelBtn" class="text-link small" class="hidden" onclick="resetGroupForm()">Cancel Edit</button>
                            </div>
                        </form>
                    </div>

                    <!-- Groups List -->
                    <div class="scroll-box">
                        <h6 class="text-muted-uppercase">{{ $groups->count() }} Groups</h6>
                        @forelse($groups as $group)
                            <div class="panel-note">
                                <div class="flex-fill">
                                    <div class="flex-center-gap">
                                        <div class="section-icon"><i class="fas fa-users"></i></div>
                                        <div>
                                            <strong class="small-text">{{ $group->group_name }}</strong>
                                            @if($group->email)
                                                <div class="text-xs text-muted">{{ $group->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-center-gap-sm">
                                    <button type="button" class="icon-action-btn edit" onclick="editGroup('{{ $group->groupid }}', '{{ addslashes($group->group_name) }}', '{{ addslashes($group->email ?? '') }}', '{{ addslashes($group->address_line_1 ?? '') }}', '{{ addslashes($group->address_line_2 ?? '') }}', '{{ addslashes($group->city ?? '') }}', '{{ addslashes($group->state ?? '') }}', '{{ addslashes($group->postal_code ?? '') }}', '{{ addslashes($group->country ?? '') }}')" title="Edit" class="icon-action-compact">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('groups.destroy', $group->groupid) }}" class="inline" onsubmit="return confirm('Delete this group?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete" class="icon-action-compact">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="fas fa-folder-open" class="empty-state-icon"></i>
                                <p class="text-help">No groups yet. Create one above!</p>
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
        form.action = '{{ url('groups') }}/' + id;
        document.getElementById('groupId').value = id;

        // Handle _method input
        let methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput) {
            methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            form.appendChild(methodInput);
        }
        methodInput.value = 'PUT';

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
        
        document.getElementById('groupFormTitle').innerHTML = '<i class="fas fa-edit" class="icon-spaced-sm text-muted"></i>Edit Group';
        document.getElementById('groupSubmitBtn').textContent = 'Update Group';
        document.getElementById('groupCancelBtn').style.display = 'inline-block';
    }

    function resetGroupForm() {
        const form = document.getElementById('groupForm');
        form.action = "{{ route('groups.store') }}";
        form.reset();
        document.getElementById('groupId').value = '';

        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) methodInput.remove();
        
        document.getElementById('groupCountry').dataset.selected = 'India';
        document.getElementById('groupState').dataset.selected = '';
        document.getElementById('groupCity').dataset.selected = '';
        document.getElementById('groupCountry').dispatchEvent(new Event('change'));
        
        document.getElementById('groupFormTitle').innerHTML = '<i class="fas fa-plus-circle" class="icon-spaced-sm text-muted"></i>Add New Group';
        document.getElementById('groupSubmitBtn').textContent = 'Save Group';
        document.getElementById('groupCancelBtn').style.display = 'none';
    }

    document.getElementById('manageGroupsModal').addEventListener('hidden.bs.modal', resetGroupForm);
    </script>
@endsection

