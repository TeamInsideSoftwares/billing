@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Clients</p>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Create New Client</h3>
    </div>
    <a href="{{ route('clients.index') }}" class="text-link">&larr; Back to clients</a>
</section>

<section class="panel-card" style="padding: 1.25rem;">
    <form method="POST" action="{{ route('clients.store') }}" class="client-form" enctype="multipart/form-data">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger" style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 0.75rem 1rem; margin-bottom: 1.25rem; border-radius: 4px;">
                <ul style="margin: 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li style="color: #991b1b; font-size: 0.85rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" name="accountid" value="{{ auth()->user()->accountid ?? 'ACC0000001' }}">

        <!-- Basic Info -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-building"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Business Information</h4>
        </div>

        <div class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
            <div style="grid-column: span 2;">
                <label for="business_name" style="font-size: 0.8rem;">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}" required style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                @error('business_name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="contact_name" style="font-size: 0.8rem;">Contact Person</label>
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>
            <div>
                <label for="groupid" style="font-size: 0.8rem;">Group</label>
                <div style="display: flex; gap: 0.35rem;">
                    <select id="groupid" name="groupid" style="font-size: 0.85rem; padding: 0.45rem 0.6rem; flex: 1;">
                        <option value="">-- No Group --</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->groupid }}" {{ old('groupid') == $group->groupid ? 'selected' : '' }}>
                                {{ $group->group_name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="text-link small" data-bs-toggle="modal" data-bs-target="#groupsModal" style="padding: 0.45rem 0.5rem; font-size: 0.85rem; border: 1px solid #e2e8f0; border-radius: 6px; white-space: nowrap;" title="Add Group"><i class="fas fa-plus"></i></button>
                </div>
                @error('groupid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email" style="font-size: 0.8rem;">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone" style="font-size: 0.8rem;">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>
            <div>
                <label for="whatsapp_number" style="font-size: 0.8rem;">WhatsApp</label>
                <input type="tel" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>
            <div>
                <label for="status" style="font-size: 0.8rem;">Status</label>
                <select id="status" name="status" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="review" {{ old('status') == 'review' ? 'selected' : '' }}>Review</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label for="currency" style="font-size: 0.8rem;">Currency *</label>
                <select id="currency" name="currency" required style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    <option value="">-- Select --</option>
                    @foreach(($currencies ?? []) as $currencyItem)
                        <option value="{{ $currencyItem->iso }}" {{ old('currency', 'INR') === $currencyItem->iso ? 'selected' : '' }}>
                            {{ $currencyItem->iso }} - {{ $currencyItem->name }}
                        </option>
                    @endforeach
                </select>
                @error('currency') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="logo" style="font-size: 0.8rem;">Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*" style="font-size: 0.8rem; padding: 0.35rem 0.5rem;">
                @error('logo') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Address -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-map-marker-alt"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Address</h4>
        </div>

        <div class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
            <div>
                <label for="country" style="font-size: 0.8rem;">Country</label>
                <select id="country" name="country" class="country-select" data-selected="{{ old('country', 'India') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="state" style="font-size: 0.8rem;">State</label>
                <select id="state" name="state" class="state-select" data-selected="{{ old('state') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="city" style="font-size: 0.8rem;">City</label>
                <select id="city" name="city" class="city-select" data-selected="{{ old('city') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    <option value="">Select</option>
                </select>
            </div>
            <div>
                <label for="postal_code" style="font-size: 0.8rem;">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>
            <div style="grid-column: span 2;">
                <label for="address_line_1" style="font-size: 0.8rem;">Address</label>
                <textarea id="address_line_1" name="address_line_1" rows="2" maxlength="300" style="resize: vertical; font-size: 0.85rem; padding: 0.45rem 0.6rem;">{{ old('address_line_1') }}</textarea>
            </div>
        </div>

        <!-- Billing Details -->
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 28px; height: 28px; border-radius: 6px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Billing Details</h4>
        </div>

        <div style="margin-bottom: 0.75rem;">
            <label class="custom-checkbox">
                <input type="checkbox" id="billing_same_as_client" name="billing_same_as_client" value="1" {{ old('billing_same_as_client') ? 'checked' : '' }}>
                <span class="checkbox-label">Same as client details</span>
            </label>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <label for="existing_bd_id" style="font-size: 0.8rem; margin: 0;">Use existing billing profile</label>
                <select id="existing_bd_id" name="existing_bd_id" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 280px;">
                    <option value="">-- New billing profile --</option>
                    @foreach($billingProfiles ?? [] as $profile)
                        <option value="{{ $profile->bd_id }}" {{ old('existing_bd_id') === $profile->bd_id ? 'selected' : '' }}>
                            {{ $profile->business_name }} ({{ $profile->bd_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="new-billing-btn" class="text-link" style="font-size: 0.8rem; margin-top: 0.35rem;"><i class="fas fa-plus" style="margin-right: 3px;"></i> Create new billing profile</button>
            @error('existing_bd_id') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div id="new-billing-fields">
            <div class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
                <div style="grid-column: span 2;">
                    <label for="billing_business_name" style="font-size: 0.8rem;">Billing Business Name *</label>
                    <input type="text" id="billing_business_name" name="billing_business_name" value="{{ old('billing_business_name') }}" maxlength="150" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    @error('billing_business_name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_gstin" style="font-size: 0.8rem;">GSTIN</label>
                    <input type="text" id="billing_gstin" name="billing_gstin" value="{{ old('billing_gstin') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    @error('billing_gstin') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_email" style="font-size: 0.8rem;">Billing Email</label>
                    <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    @error('billing_email') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_phone" style="font-size: 0.8rem;">Billing Phone</label>
                    <input type="tel" id="billing_phone" name="billing_phone" value="{{ old('billing_phone') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                    @error('billing_phone') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="billing_country" style="font-size: 0.8rem;">Country</label>
                    <select id="billing_country" name="billing_country" class="country-select" data-selected="{{ old('billing_country', 'India') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_state" style="font-size: 0.8rem;">State</label>
                    <select id="billing_state" name="billing_state" class="state-select" data-selected="{{ old('billing_state') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_city" style="font-size: 0.8rem;">City</label>
                    <select id="billing_city" name="billing_city" class="city-select" data-selected="{{ old('billing_city') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                        <option value="">Select</option>
                    </select>
                </div>
                <div>
                    <label for="billing_postal_code" style="font-size: 0.8rem;">Postal Code</label>
                    <input type="text" id="billing_postal_code" name="billing_postal_code" value="{{ old('billing_postal_code') }}" maxlength="20" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
                </div>
                <div style="grid-column: span 2;">
                    <label for="billing_address_line_1" style="font-size: 0.8rem;">Billing Address</label>
                    <textarea id="billing_address_line_1" name="billing_address_line_1" rows="2" maxlength="300" style="resize: vertical; font-size: 0.85rem; padding: 0.45rem 0.6rem;">{{ old('billing_address_line_1') }}</textarea>
                    @error('billing_address_line_1') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            <button type="submit" class="primary-button" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Create Client</button>
            <a href="{{ route('clients.index') }}" class="text-link" style="font-size: 0.85rem;">Cancel</a>
        </div>
    </form>
</section>

<!-- Groups Modal -->
<div class="modal fade" id="groupsModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;"><i class="fas fa-layer-group" style="margin-right: 0.5rem; color: #64748b;"></i>Manage Groups</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1rem;">
                <form id="groupForm" method="POST" action="{{ route('groups.store') }}" style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    @csrf
                    <div id="groupMethodField"></div>
                    <h6 id="groupFormTitle" style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.75rem;">Add New Group</h6>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div style="grid-column: span 2;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Group Name *</label>
                            <input type="text" name="group_name" id="groupName" required maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Email</label>
                            <input type="email" name="email" id="groupEmail" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Address Line 1</label>
                            <input type="text" name="address_line_1" id="groupAddress1" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div style="grid-column: span 2;">
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Address Line 2</label>
                            <input type="text" name="address_line_2" id="groupAddress2" maxlength="150" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">City</label>
                            <input type="text" name="city" id="groupCity" maxlength="100" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">State</label>
                            <input type="text" name="state" id="groupState" maxlength="100" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Postal Code</label>
                            <input type="text" name="postal_code" id="groupPostalCode" maxlength="20" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                        <div>
                            <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Country</label>
                            <input type="text" name="country" id="groupCountry" value="{{ old('country', 'India') }}" maxlength="100" style="padding: 0.4rem 0.6rem; font-size: 0.85rem; width: 100%;">
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.75rem;">
                        <button type="submit" id="groupSubmitBtn" class="primary-button small">Save</button>
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
        const billingProfiles = @json(($billingProfiles ?? collect())->keyBy('bd_id'));

        function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) return;
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
        newBillingBtn.addEventListener('click', function () { existingSelect.value = ''; clearBillingFields(); });
        sameAsClientCheckbox.addEventListener('change', function () { if (this.checked) copyClientDetailsToBilling(); });
        [clientBusinessName, clientEmail, clientAddress, clientCity, clientState, clientPostal, clientCountry].forEach((el) => {
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
