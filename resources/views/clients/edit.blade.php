@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div>
        <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Edit {{ $client->business_name ?? $client->contact_name }}</h3>
    </div>
    <a href="{{ route('clients.index') }}" class="text-link">&larr; Back to clients</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('clients.update', $client) }}" class="client-form" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger" style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; margin-bottom: 2rem; border-radius: 4px;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li style="color: #991b1b; font-size: 0.9rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="display:none;">
            <input type="hidden" name="accountid" value="{{ $client->accountid ?? auth()->user()->accountid ?? 'ACC0000001' }}">
        </div>

<h4 style="margin-bottom: 1rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Client Details</h4>
        <div class="form-grid">
            <div>
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name', $client->business_name) }}" required>
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="groupid">Group</label>
                <select id="groupid" name="groupid">
                    <option value="">-- No Group --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->groupid }}" {{ old('groupid', $client->groupid ?? '') == $group->groupid ? 'selected' : '' }}>
                            {{ $group->group_name }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="text-link small" style="margin-left: 0.5rem;" data-bs-toggle="modal" data-bs-target="#groupsModal">+ Add Group</button>
                @error('groupid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="logo">Company Logo (Square recommended)</label>
                <input type="file" id="logo" name="logo" accept="image/*">
                @if($client->logo_path)
                    <div style="margin-top: 0.5rem;">
                        <img src="{{ $client->logo_path }}" alt="Current Logo" style="height: 40px; border-radius: 4px;">
                    </div>
                @endif
                @error('logo') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}">
            </div>
            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" required>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
            </div>
            <div>
                <label for="whatsapp_number">WhatsApp Number</label>
                <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $client->whatsapp_number) }}">
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status', $client->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status', $client->status) == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status', $client->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="currency">Currency *</label>
                <select id="currency" name="currency" required>
                    <option value="">-- Select Currency --</option>
                    @foreach(($currencies ?? []) as $currencyItem)
                        <option value="{{ $currencyItem->iso }}" {{ old('currency', $client->currency ?? 'INR') === $currencyItem->iso ? 'selected' : '' }}>
                            {{ $currencyItem->iso }} - {{ $currencyItem->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="country">Country</label>
                <select id="country" name="country" class="country-select" data-selected="{{ old('country', $client->country ?? 'India') }}">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label for="state">State</label>
                <select id="state" name="state" class="state-select" data-selected="{{ old('state', $client->state) }}">
                    <option value="">Select State</option>
                </select>
            </div>
            <div>
                <label for="city">City</label>
                <select id="city" name="city" class="city-select" data-selected="{{ old('city', $client->city) }}">
                    <option value="">Select City</option>
                </select>
            </div>
            <div>
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $client->postal_code) }}" maxlength="20">
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_1">Address</label>
                <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $client->address_line_1) }}" maxlength="150">
            </div>
        </div>

        @php
            $billingProfiles = $billingProfiles ?? collect();
            $selectedExistingBdId = old('existing_bd_id', $client->bd_id);
        @endphp

<h4 style="margin-top: 1.5rem; margin-bottom: 1rem; border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">Billing Details</h4>
        <div style="margin-bottom: 0.75rem;">
            <label class="custom-checkbox">
                <input type="checkbox" id="billing_same_as_client" name="billing_same_as_client" value="1" {{ old('billing_same_as_client') ? 'checked' : '' }}>
                <span class="checkbox-label">Keep billing details same as client details</span>
            </label>
        </div>
        <div class="form-grid">
            <div style="grid-column: span 2;">
                <label for="existing_bd_id">Select Existing Billing Business</label>
                <select id="existing_bd_id" name="existing_bd_id">
                    <option value="">-- New Billing Business --</option>
                    @foreach($billingProfiles as $profile)
                        <option value="{{ $profile->bd_id }}" {{ $selectedExistingBdId === $profile->bd_id ? 'selected' : '' }}>
                            {{ $profile->business_name }} ({{ $profile->bd_id }})
                        </option>
                    @endforeach
                </select>
                <button type="button" id="new-billing-btn" class="text-link small" style="margin-top: 0.35rem;">New Billing</button>
                @error('existing_bd_id') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-grid" id="new-billing-fields" style="margin-top: 1rem;">
            <div>
                <label for="billing_business_name">Billing Business Name *</label>
                <input type="text" id="billing_business_name" name="billing_business_name" value="{{ old('billing_business_name', $client->billingDetail->business_name ?? $client->business_name) }}" maxlength="150">
                @error('billing_business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_gstin">GSTIN NO.</label>
                <input type="text" id="billing_gstin" name="billing_gstin" value="{{ old('billing_gstin', $client->billingDetail->gstin ?? '') }}">
                @error('billing_gstin') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_email">Billing Email</label>
                <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email', $client->billingDetail->billing_email ?? '') }}">
                @error('billing_email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="billing_address_line_1">Billing Address</label>
                <input type="text" id="billing_address_line_1" name="billing_address_line_1" value="{{ old('billing_address_line_1', $client->billingDetail->address_line_1 ?? '') }}">
                @error('billing_address_line_1') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_country">Billing Country</label>
                <select id="billing_country" name="billing_country" class="country-select" data-selected="{{ old('billing_country', $client->billingDetail->country ?? 'India') }}">
                    <option value="">Select Country</option>
                </select>
                @error('billing_country') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_state">Billing State</label>
                <select id="billing_state" name="billing_state" class="state-select" data-selected="{{ old('billing_state', $client->billingDetail->state ?? '') }}">
                    <option value="">Select State</option>
                </select>
                @error('billing_state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="billing_city">Billing City</label>
                <select id="billing_city" name="billing_city" class="city-select" data-selected="{{ old('billing_city', $client->billingDetail->city ?? '') }}">
                    <option value="">Select City</option>
                </select>
                @error('billing_city') <span class="error">{{ $message }}</span> @enderror
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
            <div class="modal-header" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--line);">
                <h5 class="modal-title" id="groupModalTitle" style="font-size: 1.1rem; font-weight: 700;">Manage Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1rem;">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="mb-4" style="background: var(--bg); padding: 0.75rem; border-radius: 0.75rem; border: 1px solid var(--line);">
                    @csrf
                    <div id="groupMethodField"></div>
                    <h6 id="groupFormTitle" class="eyebrow" style="margin-bottom: 0.75rem;">Add New Group</h6>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Group Name *</label>
                        <input type="text" name="group_name" id="groupName" value="{{ old('group_name') }}" required maxlength="150" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Email</label>
                        <input type="email" name="email" id="groupEmail" value="{{ old('email') }}" maxlength="150" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Address Line 1</label>
                        <input type="text" name="address_line_1" id="groupAddress1" value="{{ old('address_line_1') }}" maxlength="150" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Address Line 2</label>
                        <input type="text" name="address_line_2" id="groupAddress2" value="{{ old('address_line_2') }}" maxlength="150" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">City</label>
                            <input type="text" name="city" id="groupCity" value="{{ old('city') }}" maxlength="100" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">State</label>
                            <input type="text" name="state" id="groupState" value="{{ old('state') }}" maxlength="100" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Postal Code</label>
                            <input type="text" name="postal_code" id="groupPostalCode" value="{{ old('postal_code') }}" maxlength="20" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                        </div>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Country</label>
                            <input type="text" name="country" id="groupCountry" value="{{ old('country', 'India') }}" maxlength="100" style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button type="submit" id="groupSubmitBtn" class="primary-button small">Save Group</button>
                        <button type="button" id="groupCancelBtn" class="text-link small" style="display:none;" onclick="resetGroupForm()">Cancel</button>
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
        const billingAddress = document.getElementById('billing_address_line_1');
        const billingCity = document.getElementById('billing_city');
        const billingState = document.getElementById('billing_state');
        const billingPostal = document.getElementById('billing_postal_code');
        const billingCountry = document.getElementById('billing_country');
        const newBillingBtn = document.getElementById('new-billing-btn');
        const sameAsClientCheckbox = document.getElementById('billing_same_as_client');
        const clientBusinessName = document.getElementById('business_name');
        const clientEmail = document.getElementById('email');
        const clientAddress = document.getElementById('address_line_1');
        const clientCity = document.getElementById('city');
        const clientState = document.getElementById('state');
        const clientPostal = document.getElementById('postal_code');
        const clientCountry = document.getElementById('country');
        const billingProfiles = @json($billingProfiles->keyBy('bd_id'));

        function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) {
                return;
            }

            const profile = billingProfiles[bdId];
            billingName.value = profile.business_name || '';
            billingGstin.value = profile.gstin || '';
            billingEmail.value = profile.billing_email || '';
            billingAddress.value = profile.address_line_1 || '';
            billingCity.value = profile.city || '';
            billingState.value = profile.state || '';
            billingPostal.value = profile.postal_code || '';
            billingCountry.value = profile.country || 'India';
        }

        function clearBillingFields() {
            billingName.value = '';
            billingGstin.value = '';
            billingEmail.value = '';
            billingAddress.value = '';
            billingCity.value = '';
            billingState.value = '';
            billingPostal.value = '';
            billingCountry.value = 'India';
        }

        function copyClientDetailsToBilling() {
            billingName.value = clientBusinessName.value || '';
            billingEmail.value = clientEmail.value || '';
            billingAddress.value = clientAddress.value || '';
            billingCity.value = clientCity.value || '';
            billingState.value = clientState.value || '';
            billingPostal.value = clientPostal.value || '';
            billingCountry.value = clientCountry.value || 'India';
        }

        existingSelect.addEventListener('change', loadSelectedBillingProfile);
        newBillingBtn.addEventListener('click', function () {
            existingSelect.value = '';
            clearBillingFields();
        });
        sameAsClientCheckbox.addEventListener('change', function () {
            if (this.checked) {
                copyClientDetailsToBilling();
            }
        });
        [clientBusinessName, clientEmail, clientAddress, clientCity, clientState, clientPostal, clientCountry].forEach((el) => {
            el.addEventListener('input', function () {
                if (sameAsClientCheckbox.checked) {
                    copyClientDetailsToBilling();
                }
            });
        });
        billingName.required = true;
        loadSelectedBillingProfile();
        if (sameAsClientCheckbox.checked) {
            copyClientDetailsToBilling();
        }
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
        submitBtn.innerText = 'Save Group';
        cancelBtn.style.display = 'none';
    }
</script>
@endsection

