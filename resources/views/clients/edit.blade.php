@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" class="icon-spaced"></i>Back to Clients
    </a>
@endsection

@section('content')
<section class="panel-card panel-card-lg">
    <form method="POST" action="{{ route('clients.update', $client) }}" class="client-form" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        @if ($errors->any())
            <div class="alert error">
                <ul class="plain-list">
                    @foreach ($errors->all() as $error)
                        <li class="text-xs error">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" name="accountid" value="{{ $client->accountid ?? auth()->user()->accountid ?? 'ACC0000001' }}">

        <!-- Basic Info -->
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-building"></i></div>
            <h4 class="section-title">Business Information</h4>
        </div>

        <div class="form-grid grid-cols-4">
            <div class="col-span-2">
                <label for="business_name" class="text-sm">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $client->business_name) }}" required class="input-full">
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name" class="text-sm">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}" class="input-full">
            </div>
            <div>
                <label for="groupid" class="text-sm">Group</label>
                <div class="input-row">
                    <select id="groupid" name="groupid" class="select-form-input">
                        <option value="">-- No Group --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->groupid }}" {{ old('groupid', $client->groupid ?? '') == $group->groupid ? 'selected' : '' }}>
                                {{ $group->group_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="text-link text-link-button" data-bs-toggle="modal" data-bs-target="#groupsModal" title="Add Group"><i class="fas fa-plus"></i></button>
                </div>
                @error('groupid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email" class="text-sm">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required class="input-full">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone" class="text-sm">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone) }}" class="input-full">
            </div>
            <div>
                <label for="whatsapp_number" class="text-sm">WhatsApp</label>
                <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $client->whatsapp_number) }}" class="input-full">
            </div>
            <div>
                <label for="status" class="text-sm">Status</label>
                <select id="status" name="status" class="input-full">
                    <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status', $client->status) == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="currency" class="text-sm">Currency *</label>
                <select id="currency" name="currency" required class="input-full">
                    <option value="">-- Select --</option>
                    @foreach(($currencies ?? []) as $currencyItem)
                        <option value="{{ $currencyItem->iso }}" {{ old('currency', $client->currency ?? 'INR') === $currencyItem->iso ? 'selected' : '' }}>
                            {{ $currencyItem->iso }} - {{ $currencyItem->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="logo" class="text-sm">Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" class="file-input">
                @if($client->logo_path)
                    <div class="img-logo-container"><img src="{{ $client->logo_path }}" alt="Logo" class="img-logo-preview"></div>
                @endif
                @error('logo') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Address -->
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-map-marker-alt"></i></div>
            <h4 class="section-title">Address</h4>
        </div>

        <div class="form-grid grid-cols-4">
            <div>
                <label for="country" class="text-sm">Country</label>
                <select id="country" name="country" class="country-select" data-selected="{{ old('country', $client->country ?? 'India') }}" class="input-full">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="state" class="text-sm">State *</label>
                <select id="state" name="state" required class="state-select" data-selected="{{ old('state', $client->state) }}" class="input-full">
                    <option value="">Select</option>
                </select>
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="city" class="text-sm">City</label>
                <select id="city" name="city" class="city-select" data-selected="{{ old('city', $client->city) }}" class="input-full">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="postal_code" class="text-sm">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" maxlength="20" class="input-full">
            </div>
            <div class="col-span-2">
                <label for="address_line_1" class="text-sm">Address</label>
                <textarea id="address_line_1" name="address_line_1" rows="2" maxlength="300" class="textarea-auto">{{ old('address_line_1', $client->address_line_1) }}</textarea>
            </div>
        </div>

        <!-- Billing Details -->
        <div class="section-header">
            <div class="section-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4 class="section-title">Billing Details</h4>
        </div>

        <div class="mb-3">
            <label class="custom-checkbox">
                <input type="checkbox" id="billing_same_as_client" name="billing_same_as_client" value="1" {{ old('billing_same_as_client') ? 'checked' : '' }}>
                <span class="checkbox-label">Same as client details</span>
            </label>
        </div>

        <div class="panel-note">
            <div class="flex-between">
                <label for="existing_bd_id" class="label-small mb-0">Use existing billing profile</label>
                <select id="existing_bd_id" name="existing_bd_id" class="select-narrow">
                    <option value="">-- New billing profile --</option>
                    @foreach($billingProfiles ?? [] as $profile)
                        <option value="{{ $profile->bd_id }}" {{ old('existing_bd_id', $client->bd_id) === $profile->bd_id ? 'selected' : '' }}>
                            {{ $profile->business_name }} ({{ $profile->bd_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="new-billing-btn" class="text-link button-top-margin"><i class="fas fa-plus icon-spaced-sm"></i> Create new billing profile</button>
            @error('existing_bd_id') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div id="new-billing-fields">
            <div class="form-grid grid-cols-4">
                <div class="col-span-2">
                    <label for="billing_business_name" class="text-sm">Billing Business Name *</label>
                    <input type="text" id="billing_business_name" name="billing_business_name" value="{{ old('billing_business_name', $client->billingDetail->business_name ?? $client->business_name) }}" maxlength="150" class="input-full">
                    @error('billing_business_name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_gstin" class="text-sm">GSTIN</label>
                    <input type="text" id="billing_gstin" name="billing_gstin" value="{{ old('billing_gstin', $client->billingDetail->gstin ?? '') }}"
                        maxlength="15" minlength="15" pattern="[A-Z0-9]{15}"
                        title="GSTIN must be exactly 15 characters"
                        oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                        onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}"
                        class="input-full">
                    <span id="gstin_hint" class="form-hint">Exactly 15 characters required</span>
                    @error('billing_gstin') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_email" class="text-sm">Billing Email</label>
                    <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email', $client->billingDetail->billing_email ?? '') }}" class="input-full">
                    @error('billing_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_phone" class="text-sm">Billing Phone</label>
                    <input type="tel" id="billing_phone" name="billing_phone" value="{{ old('billing_phone', $client->billingDetail->billing_phone ?? '') }}" class="input-full">
                    @error('billing_phone') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_country" class="text-sm">Country</label>
                    <select id="billing_country" name="billing_country" class="country-select" data-selected="{{ old('billing_country', $client->billingDetail->country ?? 'India') }}" class="input-full">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_state" class="text-sm">State *</label>
                    <select id="billing_state" name="billing_state" class="state-select" data-selected="{{ old('billing_state', $client->billingDetail->state ?? '') }}" required class="input-full">
                        <option value="">Select</option>
                    </select>
                    @error('billing_state') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_city" class="text-sm">City</label>
                    <select id="billing_city" name="billing_city" class="city-select" data-selected="{{ old('billing_city', $client->billingDetail->city ?? '') }}" class="input-full">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_postal_code" class="text-sm">Postal Code</label>
                    <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code', $client->billingDetail->postal_code ?? '') }}" maxlength="20" class="input-full">
                </div>
                <div class="col-span-2">
                    <label for="billing_address_line_1" class="text-sm">Billing Address</label>
                    <textarea id="billing_address_line_1" name="billing_address_line_1" rows="2" maxlength="300" class="textarea-auto">{{ old('billing_address_line_1', $client->billingDetail->address_line_1 ?? '') }}</textarea>
                    @error('billing_address_line_1') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="primary-button">Update Client</button>
            <a href="{{ route('clients.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>

<!-- Groups Modal -->
<div class="modal fade" id="groupsModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" class="modal-header-custom">
                <h5 class="modal-title modal-title-strong"><i class="fas fa-layer-group icon-spaced-sm"></i></i>Manage Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="panel-note">
                    @csrf
                    <div id="groupMethodField"></div>
                    <h6 id="groupFormTitle" class="modal-subtitle">Add New Group</h6>
                    <div class="grid-cols-2">
                        <div class="col-span-2">
                            <label class="label-compact">Group Name *</label>
                            <input type="text" name="group_name" id="groupName" required maxlength="150" class="input-full">
                        </div>
                        <div class="col-span-2">
                            <label class="label-compact">Email</label>
                            <input type="email" name="email" id="groupEmail" maxlength="150" class="input-full">
                        </div>
                        <div class="col-span-2">
                            <label class="label-compact">Address Line 1</label>
                            <input type="text" name="address_line_1" id="groupAddress1" maxlength="150" class="input-full">
                        </div>
                        <div class="col-span-2">
                            <label class="label-compact">Address Line 2</label>
                            <input type="text" name="address_line_2" id="groupAddress2" maxlength="150" class="input-full">
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
                        <button type="submit" id="groupSubmitBtn" class="primary-button small">Save</button>
                        <button type="button" id="groupCancelBtn" class="text-link small" class="hidden" onclick="resetGroupForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const existingSelect = document.getElementById('existing_bd_id');
        const billingName = document.getElementById('billing_business_name');
        const billingGstin = document.getElementById('billing_gstin');
        const billingEmail = document.getElementById('billing_email');
        const billingPhone = document.getElementById('billing_phone');
        const billingAddress = document.getElementById('billing_address_line_1');
        const billingCity = document.getElementById('billing_city');
        const billingState = document.getElementById('billing_state');
        const billingPostal = document.getElementById('billing_postal_code');
        const billingCountry = document.getElementById('billing_country');
        const newBillingBtn = document.getElementById('new-billing-btn');
        const sameAsClientCheckbox = document.getElementById('billing_same_as_client');
        const clientBusinessName = document.getElementById('business_name');
        const clientEmail = document.getElementById('email');
        const clientPhone = document.getElementById('phone');
        const clientAddress = document.getElementById('address_line_1');
        const clientCity = document.getElementById('city');
        const clientState = document.getElementById('state');
        const clientPostal = document.getElementById('postal_code');
        const clientCountry = document.getElementById('country');
        const billingProfiles = @json(($billingProfiles ?? collect())->keyBy('bd_id'));

        function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) return;
            const profile = billingProfiles[bdId];
            billingName.value = profile.business_name || '';
            billingGstin.value = profile.gstin || '';
            billingEmail.value = profile.billing_email || '';
            billingPhone.value = profile.billing_phone || '';
            billingAddress.value = profile.address_line_1 || '';
            billingCity.value = profile.city || '';
            billingState.value = profile.state || '';
            billingPostal.value = profile.postal_code || '';
            billingCountry.value = profile.country || 'India';
        }

        function clearBillingFields() {
            billingName.value = ''; billingGstin.value = ''; billingEmail.value = '';
            billingPhone.value = '';
            billingAddress.value = ''; billingCity.value = ''; billingState.value = '';
            billingPostal.value = ''; billingCountry.value = 'India';
        }

        function copyClientDetailsToBilling() {
            billingName.value = clientBusinessName.value || '';
            billingEmail.value = clientEmail.value || '';
            billingPhone.value = clientPhone.value || '';
            billingAddress.value = clientAddress.value || '';
            billingCity.value = clientCity.value || '';
            billingState.value = clientState.value || '';
            billingPostal.value = clientPostal.value || '';
            billingCountry.value = clientCountry.value || 'India';
        }

        existingSelect.addEventListener('change', loadSelectedBillingProfile);
        newBillingBtn.addEventListener('click', function () { existingSelect.value = ''; clearBillingFields(); });
        sameAsClientCheckbox.addEventListener('change', function () { if (this.checked) copyClientDetailsToBilling(); });
        [clientBusinessName, clientEmail, clientPhone, clientAddress, clientCity, clientState, clientPostal, clientCountry].forEach(function(el) {
            el.addEventListener('input', function () { if (sameAsClientCheckbox.checked) copyClientDetailsToBilling(); });
        });
        billingName.required = true;
        loadSelectedBillingProfile();
        if (sameAsClientCheckbox.checked) copyClientDetailsToBilling();
    })();

    function selectGroup(id, name) {
        document.getElementById('groupid').value = id;
        const modal = bootstrap.Modal.getInstance(document.getElementById('groupsModal'));
        modal.hide();
    }

    function resetGroupForm() {
        const form = document.getElementById('groupForm');
        const title = document.getElementById('groupFormTitle');
        const submitBtn = document.getElementById('groupSubmitBtn');
        const cancelBtn = document.getElementById('groupCancelBtn');
        const methodField = document.getElementById('groupMethodField');
        form.action = "{{ route('groups.store') }}";
        methodField.innerHTML = '';
        form.reset();
        title.innerText = 'Add New Group';
        submitBtn.innerText = 'Save';
        cancelBtn.style.display = 'none';
    }
</script>
@endsection
