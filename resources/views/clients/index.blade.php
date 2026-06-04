@extends('layouts.app')

@section('header_actions')
    <div class="flex items-center gap-4">
        <a href="{{ route('clients.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white font-semibold text-sm bg-blue-600 hover:bg-blue-700 shadow-md hover:shadow-lg transition-all no-underline cursor-pointer">Add Client</a>
        <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline" data-bs-toggle="modal" data-bs-target="#manageGroupsModal"><i
                class="fas fa-layer-group"></i>Manage Groups</button>
    </div>
    <div class="flex items-center gap-4">
        <a href="{{ route('clients.trials') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-slate-700 font-semibold text-sm bg-white border border-slate-200 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition-all cursor-pointer no-underline"><i class="fas fa-user-clock"></i>View Trial
            Clients</a>
    </div>
@endsection

@section('content')
    <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-1.5 mb-3">
        <form action="{{ route('clients.index') }}" method="GET" class="flex flex-wrap gap-2 p-0 mb-2">
            <div class="flex flex-col min-w-0 w-1/4">
                <select name="state" id="clients_state_filter"
                    class="w-full bg-white border border-slate-300 rounded-sm px-3 h-[46px] text-sm focus:bg-white focus:ring-1 focus:ring-[#6576ff] transition-all outline-none placeholder-[#999]">
                    <option value="">All States</option>
                    @foreach ($stateOptions ?? collect() as $stateOption)
                        <option value="{{ $stateOption }}"
                            {{ (string) ($selectedState ?? '') === (string) $stateOption ? 'selected' : '' }}>
                            {{ $stateOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col min-w-0 w-1/4">
                <select name="city" id="clients_city_filter"
                    class="w-full bg-white border  border-slate-300  rounded-sm px-3 h-[46px] text-sm focus:bg-white focus:ring-1 focus:ring-[#6576ff] transition-all outline-none placeholder-[#999]">
                    <option value="">All Cities</option>
                    @foreach ($cityOptions ?? collect() as $cityOption)
                        <option value="{{ $cityOption }}"
                            {{ (string) ($selectedCity ?? '') === (string) $cityOption ? 'selected' : '' }}>
                            {{ $cityOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col min-w-0 w-1/4">
                <input type="text" name="search" id="clients_search_filter"
                    class="w-full bg-white border  border-slate-300  rounded-sm px-3 h-[46px] text-sm focus:bg-white focus:ring-1 focus:ring-[#6576ff] transition-all outline-none placeholder-[#999]"
                    value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person">
            </div>

            <div class="flex items-center gap-1.5 flex-wrap w-1/4">
                <button type="submit"
                    class="px-4 py-2 bg-[#2563eb] text-white rounded-lg text-xs font-normal shadow-sm hover:bg-[#2563eb] transition-all">Apply</button>
                <a href="{{ route('clients.index') }}"
                    class="px-4 py-2 bg-white text-[#2563eb] rounded-2 text-xs font-normal shadow-sm hover:bg-[#2563eb] transition-all">Reset</a>
            </div>
        </form>

        <table class="w-full text-left border-collapse datatable dataTable no-footer">
            <thead>
                <tr class="bg-surface-container-low border-b bg-slate-50">
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">Client
                    </th>
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">Contact</th>
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">State</th>
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">Outstanding
                    </th>
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">Invoices</th>
                    <th class="py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 sorting">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($clients as $client)
                    <tr class="hover:bg-surface-container-low transition-colors group odd">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($client['name'], 0, 2)) }}
                                </div>
                                <div>
                                    <div class="text-md font-bold text-slate-900 leading-tight">
                                        {!! $searchTerm
                                            ? str_ireplace($searchTerm, '<mark>' . $searchTerm . '</mark>', $client['name'])
                                            : $client['name'] !!}</div>
                                    <div class="text-[13px] text-slate-700 font-light mb-2">{{ $client['email'] }}</div>
                                    <div
                                        class="inline-flex flex-wrap justify-start gap-1.5 mt-3 p-1.5 bg-white rounded-full shadow-sm border border-slate-100 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <a href="{{ route('clients.show', $client['record_id']) }}"
                                            class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">View</a>
                                        <a href="{{ route('clients.documents.create', ['client' => $client['record_id']]) }}"
                                            class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">PO
                                            & Agreement</a>
                                        <a href="{{ route('clients.edit', $client['record_id']) }}"
                                            class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-amber-600 hover:bg-amber-600 hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Edit</a>
                                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}"
                                            class="inline-flex m-0"
                                            onsubmit="return confirm('Delete {{ $client['name'] }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-100 text-[#6576ff] hover:bg-[#6576ff] hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-5">
                            @if ($client['contact'])
                                <div class="text-[13px] text-slate-700 font-light">{{ $client['contact'] }}</div>
                            @endif
                            @if ($client['phone'])
                                <div class="text-[13px] text-slate-700 font-light"><i
                                        class="fas fa-phone"></i>{{ $client['phone'] }}</div>
                            @endif
                        </td>
                        <td class="px-8 py-5">
                            <div class="text-[13px] text-slate-700 font-light">{{ $client['state'] ?? '—' }}</div>
                        </td>
                        <td class="px-8 py-5">
                            <strong class="text-sm font-semibold">
                                {{ $client['balance'] }}
                            </strong>
                        </td>
                        <td class="px-8 py-5">
                            <span class="bg-slate-100 px-1.5 py-0.5 rounded-xl inline-block">
                                {{ $client['invoice_count'] }}
                            </span>
                        </td>
                        <td class="px-8 py-5">
                            <span
                                class="inline-flex items-center justify-center px-3 py-1.5 rounded-full text-[0.7rem] font-bold uppercase tracking-wider leading-none whitespace-nowrap transition-all
                                {{ strtolower($client['status']) === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ strtolower($client['status']) === 'inactive' ? 'bg-red-100 text-red-800' : '' }}
                                {{ strtolower($client['status']) === 'review' ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ strtolower($client['status']) === 'trial' ? 'bg-amber-100 text-amber-800' : '' }}
                ">{{ ucfirst(strtolower($client['status'])) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center text-slate-400">
                            <i class="fas fa-users text-4xl mb-3 block mx-auto opacity-30"></i>
                            <p class="text-sm font-medium m-0 text-slate-400">No clients found</p>
                            <p class="text-sm text-slate-400">Get started by adding your first client.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if ($clients->hasPages())
        <div class="flex justify-end mt-4">
            <nav aria-label="Clients pagination">
                <ul class="flex items-center gap-1.5 list-none m-0 p-0">
                    <li class="{{ $clients->onFirstPage() ? 'opacity-50 pointer-events-none' : '' }}">
                        <a class="px-3 py-1.5 border border-slate-200 rounded-md text-xs font-semibold hover:bg-slate-50 text-slate-600 transition-colors" href="{{ $clients->previousPageUrl() ?? '#' }}"
                            tabindex="{{ $clients->onFirstPage() ? '-1' : '0' }}">&lsaquo;</a>
                    </li>

                    @foreach ($clients->getUrlRange(1, $clients->lastPage()) as $page => $url)
                        <li>
                            <a class="px-3 py-1.5 border text-xs font-semibold rounded-md transition-colors {{ $page == $clients->currentPage() ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 hover:bg-slate-50 text-slate-600' }}" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach

                    <li class="{{ $clients->hasMorePages() ? '' : 'opacity-50 pointer-events-none' }}">
                        <a class="px-3 py-1.5 border border-slate-200 rounded-md text-xs font-semibold hover:bg-slate-50 text-slate-600 transition-colors" href="{{ $clients->nextPageUrl() ?? '#' }}"
                            tabindex="{{ $clients->hasMorePages() ? '0' : '-1' }}">&rsaquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif

    <!-- Manage Groups Modal -->
    <div class="fixed inset-0 z-50 hidden items-center justify-center p-4" id="manageGroupsModal" tabindex="-1">
        <!-- Backdrop overlay -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        
        <!-- Dialog container -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden z-10 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
                <h5 class="text-sm font-bold text-slate-800"><i class="fas fa-layer-group mr-2 text-slate-400"></i>Manage Groups</h5>
                <button type="button" class="text-slate-400 hover:text-slate-600 text-lg font-light leading-none" onclick="closeModal('manageGroupsModal')">&times;</button>
            </div>
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-3">
                    @csrf
                    <input type="hidden" id="groupId" name="_group_id" value="">
                    <div id="methodField"></div>
                    <h6 id="groupFormTitle" class="text-sm font-semibold mb-3">Add New Group</h6>
                    <div class="grid grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-4 mb-6">
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Group Name *</label>
                            <input type="text" name="group_name" id="groupName" value="{{ old('group_name') }}"
                                required>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Email</label>
                            <input type="email" name="email" id="groupEmail" value="{{ old('email') }}">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Address Line 1</label>
                            <input type="text" name="address_line_1" id="groupAddress1"
                                value="{{ old('address_line_1') }}">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Address Line 2</label>
                            <input type="text" name="address_line_2" id="groupAddress2"
                                value="{{ old('address_line_2') }}">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Country</label>
                            <select id="groupCountry" name="country" class="country-select" data-selected="India">
                                <option value="">Select Country</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">State</label>
                            <select id="groupState" name="state" class="state-select" data-selected="">
                                <option value="">Select State</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">City</label>
                            <select id="groupCity" name="city" class="city-select" data-selected="">
                                <option value="">Select City</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-0.5">Postal Code</label>
                            <input type="text" name="postal_code" id="groupPostalCode"
                                value="{{ old('postal_code') }}">
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <button type="submit" id="groupSubmitBtn" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-white font-semibold text-[0.7rem] bg-blue-600 hover:bg-blue-700 shadow-md transition-all no-underline cursor-pointer">Save Group</button>
                        <button type="button" id="groupCancelBtn" class="text-blue-600 font-semibold text-xs hover:underline hidden"
                            onclick="resetGroupForm()">Cancel Edit</button>
                    </div>
                </form>

                <!-- Groups List -->
                <div id="group-list-wrap">
                    <h6 class="text-sm font-semibold text-slate-600 mb-3">{{ $groups->count() }} Groups</h6>
                    @forelse($groups as $group)
                        <div class="flex items-center justify-between py-3 px-4 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 rounded-lg transition-colors">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm"><i class="fas fa-users"></i></div>
                                    <div>
                                        <strong class="text-sm text-slate-800">{{ $group->group_name }}</strong>
                                        @if ($group->email)
                                            <div class="text-xs text-slate-500">{{ $group->email }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button type="button" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-blue-50 text-blue-700 hover:bg-blue-100"
                                    onclick="editGroup('{{ $group->groupid }}', '{{ addslashes($group->group_name) }}', '{{ addslashes($group->email ?? '') }}', '{{ addslashes($group->address_line_1 ?? '') }}', '{{ addslashes($group->address_line_2 ?? '') }}', '{{ addslashes($group->city ?? '') }}', '{{ addslashes($group->state ?? '') }}', '{{ addslashes($group->postal_code ?? '') }}', '{{ addslashes($group->country ?? '') }}')">Edit</button>
                                <form method="POST" action="{{ route('groups.destroy', $group->groupid) }}"
                                    class="inline-flex m-0"
                                    onsubmit="return confirm('Delete group {{ $group->group_name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[28px] px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-wider rounded-md transition-all border border-transparent no-underline whitespace-nowrap cursor-pointer bg-red-50 text-red-600 hover:bg-red-100">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <i class="fas fa-folder-open text-xl mb-2 opacity-30 text-slate-400"></i>
                            <p class="text-sm text-slate-500">No groups yet. Create one above!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function displayValidationErrors(form, errors) {
            clearValidationErrors(form);
            for (const field in errors) {
                if (errors.hasOwnProperty(field)) {
                    const errorMsg = errors[field].join('<br>');
                    const inputEl = form.querySelector(`[name="${field}"]`);
                    if (inputEl) {
                        inputEl.classList.add('is-invalid');
                        inputEl.style.borderColor = 'var(--danger)';

                        const errorSpan = document.createElement('span');
                        errorSpan.className = 'error validation-error-msg';
                        errorSpan.innerHTML = errorMsg;

                        if (inputEl.parentNode.classList.contains('input-row') || inputEl.parentNode.classList.contains(
                                'custom-checkbox')) {
                            inputEl.parentNode.parentNode.appendChild(errorSpan);
                        } else {
                            inputEl.parentNode.appendChild(errorSpan);
                        }
                    }
                }
            }
        }

        function clearValidationErrors(form) {
            form.querySelectorAll('.validation-error-msg').forEach(el => el.remove());
            form.querySelectorAll('.is-invalid, [style*="border-color"]').forEach(el => {
                el.classList.remove('is-invalid');
                el.style.borderColor = '';
            });
        }

        function editGroup(id, name, email, addr1, addr2, city, state, postal, country) {
            const form = document.getElementById('groupForm');
            const title = document.getElementById('groupFormTitle');
            const submitBtn = document.getElementById('groupSubmitBtn');
            const cancelBtn = document.getElementById('groupCancelBtn');
            const methodField = document.getElementById('methodField');

            form.action = "{{ route('groups.update', ':id') }}".replace(':id', id);
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

            document.getElementById('groupName').value = name;
            document.getElementById('groupEmail').value = email;
            document.getElementById('groupAddress1').value = addr1;
            document.getElementById('groupAddress2').value = addr2;
            document.getElementById('groupPostalCode').value = postal;

            const countryEl = document.getElementById('groupCountry');
            const stateEl = document.getElementById('groupState');
            const cityEl = document.getElementById('groupCity');

            countryEl.dataset.selected = country || 'India';
            stateEl.dataset.selected = state || '';
            cityEl.dataset.selected = city || '';

            // Clone elements to clear old event listeners before re-initializing picker
            const newCountry = countryEl.cloneNode(true);
            const newState = stateEl.cloneNode(true);
            const newCity = cityEl.cloneNode(true);

            countryEl.parentNode.replaceChild(newCountry, countryEl);
            stateEl.parentNode.replaceChild(newState, stateEl);
            cityEl.parentNode.replaceChild(newCity, cityEl);

            // Temporarily disable submit button to prevent saving during dynamic location load
            submitBtn.disabled = true;
            submitBtn.innerText = 'Loading locations...';
            const locationLoadTimeout = setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Update Now';
            }, 1200);
            form.dataset.loadTimeout = locationLoadTimeout;

            // Re-run location-picker initialization on form container
            LocationPicker.init(form.parentNode);

            title.innerText = 'Editing Group';
            cancelBtn.style.display = 'inline-block';
            document.getElementById('groupName').focus();
        }

        function resetGroupForm() {
            const form = document.getElementById('groupForm');
            const title = document.getElementById('groupFormTitle');
            const submitBtn = document.getElementById('groupSubmitBtn');
            const cancelBtn = document.getElementById('groupCancelBtn');
            const methodField = document.getElementById('methodField');

            // Clear any active location load timeout
            if (form.dataset.loadTimeout) {
                clearTimeout(parseInt(form.dataset.loadTimeout));
                delete form.dataset.loadTimeout;
            }

            form.action = "{{ route('groups.store') }}";
            methodField.innerHTML = '';
            form.reset();
            clearValidationErrors(form);

            title.innerText = 'Add New Group';
            submitBtn.disabled = false;
            submitBtn.innerText = 'Save Group';
            cancelBtn.style.display = 'none';
        }

        document.getElementById('manageGroupsModal').addEventListener('hidden.bs.modal', resetGroupForm);

        // Intercept forms inside the groups modal for AJAX submissions
        document.getElementById('manageGroupsModal').addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            e.preventDefault();
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            clearValidationErrors(form);

            fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        if (response.status === 422) {
                            return response.json().then(data => {
                                displayValidationErrors(form, data.errors || {
                                    group_name: [data.message]
                                });
                                return null;
                            });
                        }
                        throw new Error('Server returned error status ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    if (html === null) return;

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // 1. Update the groups list in the modal
                    const newGroupList = doc.querySelector('#manageGroupsModal #group-list-wrap');
                    const currentGroupList = document.querySelector('#manageGroupsModal #group-list-wrap');
                    if (newGroupList && currentGroupList) {
                        currentGroupList.innerHTML = newGroupList.innerHTML;
                    }

                    resetGroupForm();

                    // 2. Update the global toast notification
                    const newToast = doc.querySelector('#app-toast-container');
                    if (newToast) {
                        const currentToastContainer = document.getElementById('app-toast-container');
                        if (currentToastContainer) {
                            currentToastContainer.innerHTML = newToast.innerHTML;
                        } else {
                            const div = document.createElement('div');
                            div.id = 'app-toast-container';
                            div.className = 'app-toast-container';
                            div.innerHTML = newToast.innerHTML;
                            document.body.appendChild(div);
                        }
                        setTimeout(() => {
                            const toast = document.querySelector('.app-toast');
                            if (toast) toast.remove();
                        }, 4000);
                    }
                })
                .catch(err => {
                    console.error('AJAX Error:', err);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: err.message || 'An error occurred. Please try again.',
                            confirmButtonColor: '#3b82f6'
                        });
                    } else {
                        alert(err.message || 'An error occurred. Please try again.');
                    }
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
        });

        @if (session('open_group_modal'))
            document.addEventListener("DOMContentLoaded", function() {
                const modalEl = document.getElementById('manageGroupsModal');
                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            });
        @endif
    </script>
@endsection
