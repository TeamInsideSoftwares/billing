@extends('layouts.app')

@section('header_actions')
<a href="{{ route('clients.index') }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Client List
</a>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <form method="POST" action="{{ isset($client) ? route('clients.update', $client) : route('clients.store') }}"
        class="mainForm" enctype="multipart/form-data">
        @isset($client)
        @method('PUT')
        @endisset
        @csrf

        @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <input type="hidden" name="accountid"
            value="{{ isset($client) ? ($client->accountid ?? auth()->user()->accountid ?? 'ACC0000001') : (auth()->user()->accountid ?? 'ACC0000001') }}">

        <div class="row g-2 align-items-stretch">
            <!-- Column 1: Client Information -->
            <div class="col-12 col-lg-3">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Client Information</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label for="business_name"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Business Name<span
                                    class="text-danger">*</span></label>
                            <input type="text" id="business_name" name="business_name" class="form-control"
                                value="{{ old('business_name', $client->business_name ?? '') }}" required>
                            @error('business_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label for="contact_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Contact
                                Person Name</label>
                            <input type="text" id="contact_name" name="contact_name" class="form-control"
                                value="{{ old('contact_name', $client->contact_name ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label for="groupid" class="form-label small lh-sm fw-semibold text-dark mb-1">Does this
                                Client fall in a group?</label>
                            <div class="input-group">
                                <select id="groupid" name="groupid" class="form-select">
                                    <option value="">-- Without Group --</option>
                                    @foreach($groups as $group)
                                    <option value="{{ $group->groupid }}" {{ old('groupid', $client->groupid ?? '') ==
                                        $group->groupid ? 'selected' : '' }}>
                                        {{ $group->group_name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-primary text-white"
                                    data-bs-toggle="modal" data-bs-target="#groupsModal" title="Add Group">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            @error('groupid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="primary_email" class="form-label small lh-sm fw-semibold text-dark mb-1">Primary
                                Email<span class="text-danger">*</span></label>
                            <input type="email" id="primary_email" name="primary_email" class="form-control"
                                value="{{ old('primary_email', $client->primary_email ?? '') }}" required
                                placeholder="name@company.com">
                            @error('primary_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">Secondary
                                Emails <span class="fw-normal">(Optional)</span></label>
                            <input type="text" id="email" name="email" class="form-control"
                                value="{{ old('email', $client->email ?? '') }}"
                                placeholder="accounts@company.com, finance@company.com">
                            <small class="text-dark small">Use comma to add multiple emails</small>
                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="{{ old('phone', $client->phone ?? '') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="whatsapp_number"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">WhatsApp</label>
                            <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control"
                                value="{{ old('whatsapp_number', $client->whatsapp_number ?? '') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="type" class="form-label small lh-sm fw-semibold text-dark mb-1">Client
                                Type</label>
                            <select id="type" name="type" class="form-select">
                                <option value="regular" {{ old('type', $client->type ?? 'regular') == 'regular' ?
                                    'selected' : '' }}>Regular</option>
                                <option value="trial" {{ old('type', $client->type ?? 'regular') == 'trial' ? 'selected'
                                    : '' }}>Trial</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="currency"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Currency<span
                                    class="text-danger">*</span></label>
                            <select id="currency" name="currency" required class="form-select">
                                <option value="">-- Select --</option>
                                @foreach(($currencies ?? []) as $currencyItem)
                                <option value="{{ $currencyItem->iso }}" {{ old('currency', $client->currency ?? 'INR')
                                    ===
                                    $currencyItem->iso ? 'selected' : '' }}>
                                    {{ $currencyItem->iso }} - {{ $currencyItem->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('currency') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Address -->
            <div class="col-12 col-lg-3">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Address</h5>
                    </div>

                    <div class="row g-2 form-grid">
                        <div class="col-12 col-md-12">
                            <label for="country"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                            <select id="country" name="country" class="form-select country-select"
                                data-selected="{{ old('country', $client->country ?? 'India') }}">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="state" class="form-label small lh-sm fw-semibold text-dark mb-1">State<span
                                    class="text-danger">*</span></label>
                            <select id="state" name="state" required class="form-select state-select"
                                data-selected="{{ old('state', $client->state ?? '') }}">
                                <option value="">Select</option>
                            </select>
                            @error('state') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="city" class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                            <select id="city" name="city" class="form-select city-select"
                                data-selected="{{ old('city', $client->city ?? '') }}">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="postal_code" class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control"
                                value="{{ old('postal_code', $client->postal_code ?? '') }}">
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="address_line_1"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                            <textarea id="address_line_1" name="address_line_1" rows="3"
                                class="form-control">{{ old('address_line_1', $client->address_line_1 ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="mb-2 mt-3">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Business Logo</h5>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-12">

                            <!-- Custom Drag and Drop Area -->
                            @php
                            $hasLogo = isset($client) && $client->logo_path;
                            @endphp
                            <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                style="cursor:pointer;" id="logo-drop-zone">
                                <input type="file" id="logo" name="logo" accept="image/*"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0">

                                <div class="drop-zone-prompt {{ $hasLogo ? 'd-none' : 'd-flex' }} align-items-center justify-content-start"
                                    id="drop-zone-prompt">
                                    <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                    <span class="text-muted fw-medium ms-2">Drag and drop or <span
                                            class="text-black fw-semibold">browse files</span></span>
                                </div>

                                <div class="drop-zone-preview {{ $hasLogo ? '' : 'd-none' }} align-items-center justify-content-between w-100"
                                    id="drop-zone-preview">
                                    <img id="logo-preview-img" src="{{ $hasLogo ? $client->logo_path : '#' }}"
                                        alt="Logo Preview" class="img-fluid rounded mb-0 shadow-sm" width="50px">
                                    <button type="button" id="remove-logo-btn"
                                        class="btn btn-sm btn-danger rounded-circle p-0 bg-transparent text-dark border-0"
                                        title="Remove Image">
                                        <i class="fas fa-upload fs-6 lh-sm"></i>
                                    </button>
                                </div>
                            </div>

                            @error('logo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Billing Details -->
            <div class="col-12 col-lg-6">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Billing Details</h5>
                        <div class="d-flex align-items-center gap-1">
                            <div class="mb-0 bg-white border rounded-1 px-2 py-1">
                                <div class="form-check mb-0 form-check-large">
                                    <input class="form-check-input" type="checkbox" id="billing_same_as_client"
                                        name="billing_same_as_client" value="1" {{ old('billing_same_as_client')
                                        ? 'checked' : '' }}>
                                    <label class="form-check-label small lh-sm fw-normal text-dark"
                                        for="billing_same_as_client">
                                        Same as Client Details
                                    </label>
                                </div>
                            </div>
                            <button type="button" id="new-billing-btn"
                                class="btn btn-outline-primary btn-primary text-white ms-1 btn-icon-square h-auto py-2"
                                title="Reload Billing Profile">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>


                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="useExistingBilling" style="cursor:pointer;"
                            {{ old('existing_bd_id', $client->bd_id ?? '') !== '' ? 'checked' : '' }}>
                        <label class="form-check-label fw-normal text-dark" style="cursor:pointer;"
                            for="useExistingBilling">
                            Do you want to use the existing billing details for this client?
                        </label>
                    </div>

                    <div id="existingBillingWrap"
                        class="position-relative mb-2 {{ old('existing_bd_id', $client->bd_id ?? '') !== '' ? '' : 'd-none' }}">
                        <label for="existing_bd_id" class="form-label small lh-sm fw-semibold text-dark mb-1">Select
                            Existing Billing Profile</label>
                        <select id="existing_bd_id" name="existing_bd_id" class="form-select form-select-sm">
                            <option value="">-- New billing profile --</option>
                            @foreach($billingProfiles ?? [] as $profile)
                            <option value="{{ $profile->bd_id }}" {{ old('existing_bd_id', $client->bd_id ??
                                '')
                                ===
                                $profile->bd_id ? 'selected' : '' }}>
                                {{ $profile->business_name }} ({{ $profile->bd_id }})
                            </option>
                            @endforeach
                        </select>
                        @error('existing_bd_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div id="new-billing-fields">
                        <div class="row g-2 form-grid">
                            <div class="col-12 col-md-12">
                                <label for="billing_business_name"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Billing Business Name<span
                                        class="text-danger">*</span></label>
                                <input type="text" id="billing_business_name" name="billing_business_name"
                                    class="form-control"
                                    value="{{ old('billing_business_name', isset($client) ? ($client->billingDetail->business_name ?? $client->business_name) : '') }}">
                                @error('billing_business_name') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="billing_gstin"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">GSTIN <span
                                        id="gstin_hint" class="fw-normal">(Exactly 15 characters
                                        required)</span></label>
                                <input type="text" id="billing_gstin" name="billing_gstin" class="form-control"
                                    value="{{ old('billing_gstin', $client->billingDetail->gstin ?? '') }}"
                                    maxlength="15" minlength="15" pattern="[A-Z0-9]{15}"
                                    title="GSTIN must be exactly 15 characters"
                                    oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                                    onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}">
                                @error('billing_gstin') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="billing_email"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Email</label>
                                <input type="email" id="billing_email" multiple name="billing_email"
                                    class="form-control"
                                    value="{{ old('billing_email', $client->billingDetail->billing_email ?? '') }}">
                                @error('billing_email') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="billing_phone"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                                <input type="tel" id="billing_phone" name="billing_phone" class="form-control"
                                    value="{{ old('billing_phone', $client->billingDetail->billing_phone ?? '') }}">
                                @error('billing_phone') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="billing_country"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                <select id="billing_country" name="billing_country" class="form-select country-select"
                                    data-selected="{{ old('billing_country', $client->billingDetail->country ?? 'India') }}">
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="billing_state"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">State<span
                                        class="text-danger">*</span></label>
                                <select id="billing_state" name="billing_state" class="form-select state-select"
                                    data-selected="{{ old('billing_state', $client->billingDetail->state ?? '') }}"
                                    required>
                                    <option value="">Select</option>
                                </select>
                                @error('billing_state') <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="billing_city"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                <select id="billing_city" name="billing_city" class="form-select city-select"
                                    data-selected="{{ old('billing_city', $client->billingDetail->city ?? '') }}">
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label for="billing_postal_code"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                    Code</label>
                                <input type="text" id="billing_postal_code" name="billing_postal_code"
                                    class="form-control"
                                    value="{{ old('billing_postal_code', $client->billingDetail->postal_code ?? '') }}">
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="billing_address_line_1"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Billing Address</label>
                                <textarea id="billing_address_line_1" name="billing_address_line_1" rows="3"
                                    class="form-control">{{ old('billing_address_line_1', $client->billingDetail->address_line_1 ?? '') }}</textarea>
                                @error('billing_address_line_1') <div class="text-danger small mt-1">{{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions outside the columns -->
        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                {{ isset($client) ? 'Update Client' : 'Add Client' }}
                <i class="fas fa-arrow-right btn-icon ms-1"></i>
            </button>
        </div>
    </form>

    <!-- Groups Modal -->
    <div class="modal fade" id="groupsModal" tabindex="-1" aria-labelledby="groupsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom py-2">
                    <h5 class="modal-title fw-semibold" id="groupsModalLabel">Add Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light p-4">
                    <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="mainForm">
                        @csrf
                        <div id="groupMethodField"></div>
                        <div class="row g-2 mb-3 form-grid">
                            <div class="col-md-6">
                                <label for="groupName" class="form-label small lh-sm fw-semibold text-dark mb-1">Group
                                    Name<span class="text-danger">*</span></label>
                                <input type="text" name="group_name" id="groupName" required class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="groupEmail"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Email</label>
                                <input type="email" name="email" id="groupEmail" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="groupAddress1"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Address
                                    Line 1</label>
                                <textarea name="address_line_1" id="groupAddress1" class="form-control" rows="2">{{ old('address_line_1') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="groupAddress2"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Address
                                    Line 2</label>
                                <textarea name="address_line_2" id="groupAddress2" class="form-control" rows="2">{{ old('address_line_2') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="groupCountry"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                <select id="groupCountry" name="country" class="form-select country-select"
                                    data-selected="India">
                                    <option value="">Select Country</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="groupState"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                                <select id="groupState" name="state" class="form-select state-select" data-selected="">
                                    <option value="">Select State</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="groupCity"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                <select id="groupCity" name="city" class="form-select city-select" data-selected="">
                                    <option value="">Select City</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="groupPostalCode"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                    Code</label>
                                <input type="text" name="postal_code" id="groupPostalCode" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                                data-bs-dismiss="modal">
                                <i class="fas fa-times btn-icon me-1"></i> Cancel
                            </button>
                            <button type="submit" id="groupSubmitBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/json"
    id="billing-profiles-data">{!! json_encode(($billingProfiles ?? collect())->keyBy('bd_id')) !!}</script>

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
        const clientPrimaryEmail = document.getElementById('primary_email');
        const clientSecondaryEmails = document.getElementById('email');
        const clientPhone = document.getElementById('phone');
        const clientAddress = document.getElementById('address_line_1');
        const clientCity = document.getElementById('city');
        const clientState = document.getElementById('state');
        const clientPostal = document.getElementById('postal_code');
        const clientCountry = document.getElementById('country');
        const billingProfiles = JSON.parse(document.getElementById('billing-profiles-data').textContent || '{}');

        function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) return;
            const profile = billingProfiles[bdId];
            billingName.value = profile.business_name || '';
            billingGstin.value = profile.gstin || '';
            billingEmail.value = profile.billing_email || '';
            billingPhone.value = profile.billing_phone || '';
            billingAddress.value = profile.address_line_1 || '';
            billingPostal.value = profile.postal_code || '';

            setSelectValueAndNotify(billingCountry, profile.country || 'India');
            setSelectValueAndNotify(billingState, profile.state || '');
            billingCity.value = profile.city || '';
        }

        function clearBillingFields() {
            billingName.value = '';
            billingGstin.value = '';
            billingEmail.value = '';
            billingPhone.value = '';
            billingAddress.value = '';
            billingCountry.value = 'India';
            billingState.value = '';
            billingCity.value = '';
            billingPostal.value = '';
        }

        function setSelectValueAndNotify(selectEl, value) {
            if (!selectEl) return;
            selectEl.value = value || '';
            selectEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        async function waitForOption(selectEl, value, attempts = 20, delayMs = 100) {
            if (!selectEl || !value) return false;
            for (let i = 0; i < attempts; i++) {
                const hasOption = Array.from(selectEl.options || []).some(function (opt) {
                    return opt.value === value;
                });
                if (hasOption) return true;
                await new Promise(function (resolve) { setTimeout(resolve, delayMs); });
            }
            return false;
        }

        async function syncBillingLocationFromClient() {
            const country = clientCountry.value || 'India';
            const state = clientState.value || '';
            const city = clientCity.value || '';

            setSelectValueAndNotify(billingCountry, country);

            if (state) {
                await waitForOption(billingState, state);
                setSelectValueAndNotify(billingState, state);
            } else {
                setSelectValueAndNotify(billingState, '');
            }

            if (city) {
                await waitForOption(billingCity, city);
                billingCity.value = city;
            } else {
                billingCity.value = '';
            }
        }

        async function copyClientDetailsToBilling() {
            billingName.value = clientBusinessName.value || '';
            const emailParts = [
                (clientPrimaryEmail.value || '').trim(),
                (clientSecondaryEmails.value || '').trim(),
            ].filter(Boolean);
            billingEmail.value = Array.from(new Set(emailParts)).join(', ');
            billingPhone.value = clientPhone.value || '';
            billingAddress.value = clientAddress.value || '';
            billingPostal.value = clientPostal.value || '';
            await syncBillingLocationFromClient();
        }

        const useExistingCheckbox = document.getElementById('useExistingBilling');
        const existingBillingWrap = document.getElementById('existingBillingWrap');

        useExistingCheckbox.addEventListener('change', function () {
            if (this.checked) {
                existingBillingWrap.classList.remove('d-none');
            } else {
                existingBillingWrap.classList.add('d-none');
                existingSelect.value = '';
                clearBillingFields();
            }
        });

        existingSelect.addEventListener('change', loadSelectedBillingProfile);
        newBillingBtn.addEventListener('click', function () { existingSelect.value = ''; clearBillingFields(); });
        sameAsClientCheckbox.addEventListener('change', function () { if (this.checked) copyClientDetailsToBilling(); });
        [clientBusinessName, clientPrimaryEmail, clientSecondaryEmails, clientPhone, clientAddress, clientPostal].forEach(function (el) {
            el.addEventListener('input', function () { if (sameAsClientCheckbox.checked) copyClientDetailsToBilling(); });
        });
        [clientCity, clientState, clientCountry].forEach(function (el) {
            el.addEventListener('change', function () { if (sameAsClientCheckbox.checked) copyClientDetailsToBilling(); });
        });
        billingName.required = true;
        // Logo Drag and Drop logic
        const logoInput = document.getElementById('logo');
        const logoDropZone = document.getElementById('logo-drop-zone');
        const dropZonePrompt = document.getElementById('drop-zone-prompt');
        const dropZonePreview = document.getElementById('drop-zone-preview');
        const previewImg = document.getElementById('logo-preview-img');
        const removeLogoBtn = document.getElementById('remove-logo-btn');
        const existingLogoWrap = document.getElementById('existing-logo-wrap');

        if (logoInput && logoDropZone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                logoDropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    logoDropZone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                logoDropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    logoDropZone.classList.remove('dragover');
                }, false);
            });

            logoInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        dropZonePrompt.classList.add('d-none');
                        dropZonePreview.classList.remove('d-none');
                        if (existingLogoWrap) {
                            existingLogoWrap.classList.add('d-none');
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.src = '#';
                    dropZonePrompt.classList.remove('d-none');
                    dropZonePreview.classList.add('d-none');
                    if (existingLogoWrap) {
                        existingLogoWrap.classList.remove('d-none');
                    }
                }
            });

            if (removeLogoBtn) {
                removeLogoBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    logoInput.value = '';
                    logoInput.dispatchEvent(new Event('change'));
                });
            }
        }

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
        const submitBtn = document.getElementById('groupSubmitBtn');
        const methodField = document.getElementById('groupMethodField');
        form.action = "{{ route('groups.store') }}";
        methodField.innerHTML = '';
        form.reset();
        if (submitBtn) {
            submitBtn.innerHTML = 'Save <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        }
    }
</script>
@endsection
