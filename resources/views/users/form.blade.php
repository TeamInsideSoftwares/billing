@extends('layouts.app')

@section('header_actions')
<a href="{{ route('users.index') }}"
    class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Team List
</a>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <form method="POST" action="{{ isset($userModel) ? route('users.update', $userModel) : route('users.store') }}"
        class="mainForm" enctype="multipart/form-data">
        @isset($userModel)
        @method('PUT')
        @endisset
        @csrf

        <div class="row g-2 align-items-stretch">
            <!-- Column 1: User Information -->
            <div class="col-12 col-lg-4">
                <div class="bg-light p-2 rounded-3">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Team Information</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-12">
                            <label for="name" class="form-label small lh-sm fw-semibold text-dark mb-1">Full Name<span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $userModel->name ?? '') }}" required>
                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-12">
                            <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">Email<span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $userModel->email ?? '') }}" required>
                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12 col-md-12">
                            <label for="phone" class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $userModel->phone ?? '') }}" placeholder="e.g. +91 98xxxxxx">
                            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="designation" class="form-label small lh-sm fw-semibold text-dark mb-1">Designation</label>
                            <input type="text" id="designation" name="designation" class="form-control" value="{{ old('designation', $userModel->designation ?? '') }}" placeholder="e.g. Software Engineer">
                            @error('designation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="gender" class="form-label small lh-sm fw-semibold text-dark mb-1">Gender</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="" {{ old('gender', $userModel->gender ?? '') == '' ? 'selected' : '' }}>Select Gender</option>
                                <option value="Male" {{ old('gender', $userModel->gender ?? '') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $userModel->gender ?? '') == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $userModel->gender ?? '') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="depid" class="form-label small lh-sm fw-semibold text-dark mb-1">Department</label>
                            <select id="depid" name="depid" class="form-select">
                                <option value="" {{ old('depid', $userModel->depid ?? '') ? '' : 'selected' }}>Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->depid }}" {{ old('depid', $userModel->depid ?? '') == $dept->depid ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('depid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="roleid" class="form-label small lh-sm fw-semibold text-dark mb-1">Role<span class="text-danger">*</span></label>
                            <select id="roleid" name="roleid" class="form-select" data-max-level="{{ $maxLevel ?? 6 }}" required>
                                <option value="" disabled {{ old('roleid', $userModel->roleid ?? '') ? '' : 'selected' }}>Select Role</option>
                                @foreach($roles as $roleObj)
                                    <option value="{{ $roleObj->roleid }}" data-level-value="{{ $roleObj->roleLevel?->level_value ?? 0 }}" {{ old('roleid', $userModel->roleid ?? '') == $roleObj->roleid ? 'selected' : '' }}>{{ $roleObj->name }}</option>
                                @endforeach
                            </select>
                            @error('roleid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="shiftid" class="form-label small lh-sm fw-semibold text-dark mb-1">Shift</label>
                            <select id="shiftid" name="shiftid" class="form-select">
                                <option value="" {{ old('shiftid', $userModel->shiftid ?? '') ? '' : 'selected' }}>Select Shift</option>
                                @foreach($shifts as $shift)
                                    @php
                                        $timing = $shift->start_time && $shift->end_time 
                                            ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') . ' - ' . \Carbon\Carbon::parse($shift->end_time)->format('h:i A')
                                            : '';
                                    @endphp
                                    <option value="{{ $shift->shiftid }}" {{ old('shiftid', $userModel->shiftid ?? '') == $shift->shiftid ? 'selected' : '' }}>
                                        {{ $shift->shift_name }} {{ $timing ? "($timing)" : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shiftid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="date_of_birth" class="form-label small lh-sm fw-semibold text-dark mb-1">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $userModel->date_of_birth ?? '') }}">
                            @error('date_of_birth') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12 col-md-12">
                            <label for="salary_amount" class="form-label small lh-sm fw-semibold text-dark mb-1">Salary Amount (Monthly)</label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0 bg-light"><i class="fas fa-rupee-sign small"></i></span>
                                <input type="number" id="salary_amount" name="salary_amount" class="form-control border-start-0" step="0.01" min="0" value="{{ old('salary_amount', isset($userModel) && $userModel->salary ? (float)$userModel->salary->amount : '') }}">
                            </div>
                            @error('salary_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12 col-md-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-0">Documents</label>
                                <button type="button" class="btn btn-link d-inline-flex align-items-center gap-1 fw-medium" id="addDocumentBtn"><i class="fas fa-plus btn-icon"></i> Add</button>
                            </div>
                            
                            <div id="documentsContainer" class="d-flex flex-column gap-2">
                                @php
                                    $documents = isset($userModel) && $userModel->profile ? $userModel->profile->documents : collect([]);
                                @endphp
                                @if($documents->count() > 0)
                                    @foreach($documents as $doc)
                                        <div class="border rounded-2 p-2 bg-white d-flex justify-content-between align-items-center" id="existingDoc_{{ $doc->docid }}">
                                            <div class="text-truncate" style="max-width: 150px;">
                                                <small class="fw-semibold d-block text-dark lh-sm doc-type-label">{{ $doc->doc_type }}</small>
                                                <small class="text-muted" style="font-size: 0.7rem;"><a href="{{ $doc->full_url }}" target="_blank">View File</a></small>
                                            </div>
                                            <div class="tableActionButton">
                                                <button type="button" class="bg04 color04 border-0 remove-existing-doc" data-target="existingDoc_{{ $doc->docid }}" data-id="{{ $doc->docid }}">Delete</button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                                <!-- New documents will be appended here via JS -->
                            </div>
                        </div>
                    </div>
                </div>
                


                <div class="bg-light p-2 rounded-3 mt-2">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Account Policies</h5>
                        <small class="text-muted" style="font-size: 0.7rem;">Select the policies that apply to this user.</small>
                    </div>
                    
                    <div class="row g-2">
                        @php
                            $userPolicyIds = isset($userModel) ? $userModel->policies->pluck('policyid')->toArray() : [];
                            $groupedPolicies = $policies->groupBy('componentid');
                        @endphp
                        @foreach($groupedPolicies as $componentId => $componentPolicies)
                            @php
                                $componentName = $componentPolicies->first()->component?->name ?? 'Unknown Type';
                            @endphp
                            <div class="col-12 col-md-6">
                                <div class="mb-0 bg-white border rounded-1 px-2 py-1 w-100">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">{{ $componentName }}</label>
                                    <select class="form-select form-select-sm border-primary" name="account_policies[{{ $componentId }}]">
                                        <option value="">No Policy Selected</option>
                                        @foreach($componentPolicies as $policy)
                                            <option value="{{ $policy->policyid }}" {{ in_array($policy->policyid, old('account_policies', $userPolicyIds)) ? 'selected' : '' }}>
                                                {{ $policy->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                        @error('account_policies') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <!-- Column 2: Additional Details -->
            <div class="col-12 col-lg-8">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Permissions & Security</h5>
                    </div>

                    <div class="row g-2">
                        @php
                            $selectedPermissions = old('permissions', $userModel->permissions ?? []);
                        @endphp
                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Module Permissions</label>
                            <div class="bg-white p-2 border rounded-3">
                                <div class="row g-3">
                                    @foreach(($groupedPermissions ?? []) as $module => $permissions)
                                        @if($module === 'Team work' && !($hasTeamManagement ?? false))
                                            @continue
                                        @endif
                                        <div class="col-12 col-md-6 col-lg-4">
                                            @php
                                                $moduleSlug = \Illuminate\Support\Str::slug($module);
                                            @endphp
                                            <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ $module }} Module</h6>
                                            <select class="form-select form-select-sm module-permission-select" data-module="{{ $moduleSlug }}">
                                                <option value="none">No Permission</option>
                                                <option value="read">Read Only</option>
                                                <option value="full">Full Permission</option>
                                            </select>
                                            <div class="d-none">
                                                @foreach($permissions as $permission)
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="perm-checkbox perm-checkbox-{{ $moduleSlug }}" data-module="{{ $moduleSlug }}" {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('permissions') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('permissions.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Client Management</label>
                            <div class="mb-0 bg-white border rounded-1 px-2 py-1 ms-1">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input border-primary border-2" type="checkbox" name="can_add_maintenance_duration" id="can_add_maintenance_duration" value="1" {{ old('can_add_maintenance_duration', $userModel->can_add_maintenance_duration ?? 0) ? 'checked' : '' }}>
                                    <label class="form-check-label small lh-sm fw-normal text-dark" for="can_add_maintenance_duration">
                                        Can Add Maintenance Duration
                                        <div class="small text-muted" style="font-size: 0.75rem;">Allow user to assign maintenance duration to clients</div>
                                    </label>
                                </div>
                                @error('can_add_maintenance_duration') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>



                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label small lh-sm fw-semibold text-dark mb-1">{{ isset($userModel) ? 'New Password' : 'Password' }}<span class="text-danger">{{ isset($userModel) ? '' : '*' }}</span></label>
                            <input type="password" id="password" name="password" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6" placeholder="{{ isset($userModel) ? 'Leave blank to keep existing' : 'Minimum 6 characters' }}">
                            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="password_confirmation" class="form-label small lh-sm fw-semibold text-dark mb-1">Confirm Password<span class="text-danger">{{ isset($userModel) ? '' : '*' }}</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6">
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label small lh-sm fw-semibold text-dark mb-1">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this user">{{ old('notes', $userModel->notes ?? '') }}</textarea>
                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @php
            $profile = $userModel->profile ?? null;
        @endphp
        @if(isset($profile) && $profile->status === 'approved')
        <div class="row g-2 align-items-stretch mt-2">
            <div class="col-12 col-lg-4">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Profile Address (Optional)</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label for="address" class="form-label small lh-sm fw-semibold text-dark mb-1">Street Address</label>
                            <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $profile->address ?? '') }}">
                            @error('address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="city" class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                            <input type="text" id="city" name="city" class="form-control" value="{{ old('city', $profile->city ?? '') }}">
                            @error('city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="state" class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                            <input type="text" id="state" name="state" class="form-control" value="{{ old('state', $profile->state ?? '') }}">
                            @error('state') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="country" class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                            <input type="text" id="country" name="country" class="form-control" value="{{ old('country', $profile->country ?? '') }}">
                            @error('country') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="zip_code" class="form-label small lh-sm fw-semibold text-dark mb-1">Zip Code</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" value="{{ old('zip_code', $profile->zip_code ?? '') }}">
                            @error('zip_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Bank Details (Optional)</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label for="bank_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" class="form-control" value="{{ old('bank_name', $profile->bank_name ?? '') }}">
                            @error('bank_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="account_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Account Holder Name</label>
                            <input type="text" id="account_name" name="account_name" class="form-control" value="{{ old('account_name', $profile->account_name ?? '') }}">
                            @error('account_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="account_number" class="form-label small lh-sm fw-semibold text-dark mb-1">Account Number</label>
                            <input type="text" id="account_number" name="account_number" class="form-control" value="{{ old('account_number', $profile->account_number ?? '') }}">
                            @error('account_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="routing_code" class="form-label small lh-sm fw-semibold text-dark mb-1">IFSC / Routing Code</label>
                            <input type="text" id="routing_code" name="routing_code" class="form-control" value="{{ old('routing_code', $profile->routing_code ?? '') }}">
                            @error('routing_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-6">
                            <label for="bank_branch" class="form-label small lh-sm fw-semibold text-dark mb-1">Branch</label>
                            <input type="text" id="bank_branch" name="bank_branch" class="form-control" value="{{ old('bank_branch', $profile->bank_branch ?? '') }}">
                            @error('bank_branch') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="mt-2 d-flex justify-content-end gap-2">
            <a href="{{ route('users.index') }}"
                class="btn btn-outline-primary bg-white text-primary fw-medium px-4">Cancel</a>
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium px-4">Save Team</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addDocumentBtn = document.getElementById('addDocumentBtn');
    const documentsContainer = document.getElementById('documentsContainer');
    let docIndex = 0;

    addDocumentBtn?.addEventListener('click', function() {
        const docHtml = `
            <div class="border rounded-2 p-2 bg-white d-flex align-items-center gap-2" id="docBlock_${docIndex}">
                <select name="documents[${docIndex}][type]" class="form-select form-select-sm m-0 dynamic-doc-select" required style="width: auto;">
                    <option value="" disabled selected>Select Type</option>
                    <option value="Photo">Photo</option>
                    <option value="PAN">PAN</option>
                    <option value="Identity proof">Identity proof/Aadhaar</option>
                    <option value="Bank details">Bank details</option>
                </select>
                <input type="file" name="documents[${docIndex}][file]" class="form-control form-control-sm m-0" required accept=".jpg,.jpeg,.png,.webp,.pdf">
                <div class="tableActionButton">
                    <button type="button" class="bg04 color04 border-0 m-0 remove-doc-btn" data-target="docBlock_${docIndex}">Delete</button>
                </div>
            </div>
        `;
        documentsContainer.insertAdjacentHTML('beforeend', docHtml);
        docIndex++;
        updateAvailableDocumentTypes();
    });

    documentsContainer?.addEventListener('click', function(e) {
        if (e.target.closest('.remove-doc-btn')) {
            const btn = e.target.closest('.remove-doc-btn');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId)?.remove();
            updateAvailableDocumentTypes();
        }
    });

    documentsContainer?.addEventListener('change', function(e) {
        if (e.target.classList.contains('dynamic-doc-select')) {
            updateAvailableDocumentTypes();
        }
    });

    document.querySelectorAll('.remove-existing-doc').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const docId = this.getAttribute('data-id');
            // Add hidden input to form
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'delete_documents[]';
            hiddenInput.value = docId;
            document.querySelector('.mainForm').appendChild(hiddenInput);
            
            // Remove the block
            document.getElementById(targetId)?.remove();
            updateAvailableDocumentTypes();
        });
    });

    function updateAvailableDocumentTypes() {
        const existingTypes = Array.from(document.querySelectorAll('.doc-type-label')).map(el => el.textContent.trim());
        const selects = document.querySelectorAll('.dynamic-doc-select');
        const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== '');

        const allSelected = [...existingTypes, ...selectedValues];

        selects.forEach(select => {
            const currentValue = select.value;
            Array.from(select.options).forEach(option => {
                if (option.value === '') return;
                if (allSelected.includes(option.value) && option.value !== currentValue) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            });
        });
    }

    // Run on initial load
    updateAvailableDocumentTypes();

    // 3-Tier Module Permissions Logic
    document.querySelectorAll('.module-permission-select').forEach(select => {
        const module = select.dataset.module;
        const checkboxes = document.querySelectorAll(`.perm-checkbox-${module}`);
        const total = checkboxes.length;
        if (total === 0) return;

        let checkedCount = 0;
        let hasView = false;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                checkedCount++;
                if (cb.value.endsWith('.view')) {
                    hasView = true;
                }
            }
        });

        if (checkedCount === 0) {
            select.value = 'none';
        } else if (checkedCount === total || checkedCount > 1) {
            select.value = 'full';
        } else if (hasView && checkedCount === 1) {
            select.value = 'read';
        } else {
            select.value = 'none';
        }

        select.addEventListener('change', (e) => {
            const val = e.target.value;
            checkboxes.forEach(cb => {
                if (val === 'none') {
                    cb.checked = false;
                } else if (val === 'full') {
                    cb.checked = true;
                } else if (val === 'read') {
                    cb.checked = cb.value.endsWith('.view');
                }
            });
        });
    });
});
</script>


@endsection
