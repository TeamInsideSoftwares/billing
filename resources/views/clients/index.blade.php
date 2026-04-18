@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div></div>
        <div>
            <a href="{{ route('clients.create') }}" class="primary-button">Add Client</a>
            <button type="button" class="secondary-button" data-bs-toggle="modal" data-bs-target="#manageGroupsModal"><i class="fas fa-layer-group" style="margin-right: 5px;"></i>Manage Groups</button>
        </div>
    </section>

    <section class="panel-card" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Client</th>
                    <th style="width: 18%;">Contact</th>
                    <th style="width: 14%;">State</th>
                    <th style="width: 12%;">Outstanding</th>
                    <th style="width: 8%;">Invoices</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 8%;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;">
                                {{ strtoupper(substr($client['name'], 0, 2)) }}
                            </div>
                            <div>
                                <strong style="font-size: 0.9rem;">{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $client['name']) : $client['name'] !!}</strong>
                                <div style="font-size: 0.75rem; color: #64748b;">{{ $client['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($client['contact'])
                            <div style="font-size: 0.85rem;">{{ $client['contact'] }}</div>
                        @endif
                        @if($client['phone'])
                            <div style="font-size: 0.75rem; color: #64748b;"><i class="fas fa-phone" style="width: 12px; margin-right: 4px;"></i>{{ $client['phone'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $client['state'] ?? '—' }}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">{{ $client['currency'] }}</div>
                    </td>
                    <td>
                        <strong style="font-size: 0.9rem; color: {{ (float) str_replace(',', '', explode(' ', $client['balance'])[1] ?? '0') > 0 ? '#ef4444' : '#22c55e' }};">
                            {{ $client['balance'] }}
                        </strong>
                    </td>
                    <td>
                        <span style="font-size: 0.85rem; background: #f1f5f9; padding: 0.2rem 0.6rem; border-radius: 10px;">
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
                    <td colspan="7" style="padding: 3rem; text-align: center; color: #94a3b8;">
                        <i class="fas fa-users" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
                        <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">No clients found</p>
                        <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">Get started by adding your first client.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <!-- Manage Groups Modal -->
    <div class="modal fade" id="manageGroupsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 700px;">
            <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                    <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;"><i class="fas fa-layer-group" style="margin-right: 0.5rem; color: #64748b;"></i>Manage Groups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 0; max-height: 70vh; display: flex; flex-direction: column;">
                    <!-- Add/Edit Group Form -->
                    <div style="padding: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h6 id="groupFormTitle" style="margin: 0 0 0.75rem 0; font-size: 0.85rem; font-weight: 600;"><i class="fas fa-plus-circle" style="margin-right: 0.35rem; color: #64748b;"></i>Add New Group</h6>
                        <form id="groupForm" method="POST" action="{{ route('groups.store') }}" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem;">
                            @csrf
                            <input type="hidden" id="groupId" name="_group_id" value="">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div style="">
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Group Name *</label>
                                    <input type="text" name="group_name" id="groupName" required maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                </div>
                                <div style="">
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Email</label>
                                    <input type="email" name="email" id="groupEmail" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                </div>
                                <div style="">
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Address Line 1</label>
                                    <textarea name="address_line_1" id="groupAddress1" rows="2" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%; resize: vertical;">{{ old('address_line_1') }}</textarea>
                                </div>
                                <div style="">
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Address Line 2</label>
                                    <textarea name="address_line_2" id="groupAddress2" rows="2" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%; resize: vertical;">{{ old('address_line_2') }}</textarea>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Country</label>
                                    <select id="groupCountry" name="country" class="country-select" data-selected="India" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                        <option value="">Select Country</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">State</label>
                                    <select id="groupState" name="state" class="state-select" data-selected="" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                        <option value="">Select State</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">City</label>
                                    <select id="groupCity" name="city" class="city-select" data-selected="" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                        <option value="">Select City</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Postal Code</label>
                                    <input type="text" name="postal_code" id="groupPostalCode" maxlength="20" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                <button type="submit" id="groupSubmitBtn" class="primary-button small">Save Group</button>
                                <button type="button" id="groupCancelBtn" class="text-link small" style="display: none;" onclick="resetGroupForm()">Cancel Edit</button>
                            </div>
                        </form>
                    </div>

                    <!-- Groups List -->
                    <div style="padding: 1rem; max-height: 350px; overflow-y: auto;">
                        <h6 style="margin: 0 0 0.5rem 0; font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">{{ $groups->count() }} Groups</h6>
                        @forelse($groups as $group)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.35rem; background: #f8fafc;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;"><i class="fas fa-users"></i></div>
                                        <div>
                                            <strong style="font-size: 0.85rem;">{{ $group->group_name }}</strong>
                                            @if($group->email)
                                                <div style="font-size: 0.75rem; color: #64748b;">{{ $group->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.25rem;">
                                    <button type="button" class="icon-action-btn edit" onclick="editGroup('{{ $group->groupid }}', '{{ addslashes($group->group_name) }}', '{{ addslashes($group->email ?? '') }}', '{{ addslashes($group->address_line_1 ?? '') }}', '{{ addslashes($group->address_line_2 ?? '') }}', '{{ addslashes($group->city ?? '') }}', '{{ addslashes($group->state ?? '') }}', '{{ addslashes($group->postal_code ?? '') }}', '{{ addslashes($group->country ?? '') }}')" title="Edit" style="padding: 0.3rem 0.5rem; font-size: 0.75rem;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('groups.destroy', $group->groupid) }}" class="inline" onsubmit="return confirm('Delete this group?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="icon-action-btn delete" title="Delete" style="padding: 0.3rem 0.5rem; font-size: 0.75rem; border: none; cursor: pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 1.5rem; color: #94a3b8;">
                                <i class="fas fa-folder-open" style="font-size: 1.5rem; margin-bottom: 0.5rem; opacity: 0.3;"></i>
                                <p style="margin: 0; font-size: 0.85rem;">No groups yet. Create one above!</p>
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
        
        document.getElementById('groupFormTitle').innerHTML = '<i class="fas fa-edit" style="margin-right: 0.35rem; color: #64748b;"></i>Edit Group';
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
        
        document.getElementById('groupFormTitle').innerHTML = '<i class="fas fa-plus-circle" style="margin-right: 0.35rem; color: #64748b;"></i>Add New Group';
        document.getElementById('groupSubmitBtn').textContent = 'Save Group';
        document.getElementById('groupCancelBtn').style.display = 'none';
    }

    document.getElementById('manageGroupsModal').addEventListener('hidden.bs.modal', resetGroupForm);
    </script>
@endsection

