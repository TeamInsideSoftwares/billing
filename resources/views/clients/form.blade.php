@extends('layouts.app')

@section('header_actions')
<a href="{{ route('clients.index') }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Client List
</a>
@endsection

@section('content')
@php
$showDetails = isset($client) || old('business_name') !== null || $errors->any();
@endphp
<div class="position-relative bg-white p-2 rounded-3">
    <form method="POST" action="{{ isset($client) ? route('clients.update', $client) : route('clients.store') }}"
        class="mainForm" enctype="multipart/form-data">
        @isset($client)
        @method('PUT')
        @endisset
        @csrf



        <input type="hidden" name="accountid"
            value="{{ isset($client) ? ($client->accountid ?? auth()->user()->accountid ?? 'ACC0000001') : (auth()->user()->accountid ?? 'ACC0000001') }}">
        <input type="hidden" name="clientid_hidden" id="clientid_hidden"
            value="{{ isset($client) ? $client->clientid : '' }}" data-initial-bd-id="{{ $client->bd_id ?? '' }}">

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
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-business_name"></div>
                        </div>
                        <div class="col-12">
                            <label for="groupid" class="form-label small lh-sm fw-semibold text-dark mb-1">Does this
                                Client fall in a group?</label>
                            <div class="input-group">
                                <select id="groupid" name="groupid" class="form-select">
                                    <option value="">Not part of any Group</option>
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
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-groupid"></div>
                        </div>
                        <div class="col-12">
                            <label for="categoryid" class="form-label small lh-sm fw-semibold text-dark mb-1">Client Category</label>
                            <div class="input-group">
                                <select id="categoryid" name="categoryid" class="form-select">
                                    <option value="">No Category</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->categoryid }}" {{ old('categoryid', $client->categoryid ?? '') ==
                                        $category->categoryid ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-primary text-white"
                                    data-bs-toggle="modal" data-bs-target="#categoriesModal" title="Add Category">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            @error('categoryid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-categoryid"></div>
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="primary_email" class="form-label small lh-sm fw-semibold text-dark mb-1">Primary
                                Email<span class="text-danger">*</span></label>
                            <input type="email" id="primary_email" name="primary_email" class="form-control"
                                value="{{ old('primary_email', $client->primary_email ?? '') }}" required
                                placeholder="name@company.com">
                            @error('primary_email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-primary_email"></div>
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">Secondary
                                Emails <span class="fw-normal">(Optional)</span></label>
                            <input type="text" id="email" name="email" class="form-control"
                                value="{{ old('email', $client->email ?? '') }}"
                                placeholder="accounts@company.com, finance@company.com">
                            <small class="text-dark small">Use comma to add multiple emails</small>
                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-email"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                value="{{ old('phone', $client->phone ?? '') }}">
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-phone"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="whatsapp_number"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">WhatsApp</label>
                            <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control"
                                value="{{ old('whatsapp_number', $client->whatsapp_number ?? '') }}">
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-whatsapp_number"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="type" class="form-label small lh-sm fw-semibold text-dark mb-1">Client
                                Type</label>
                            <select id="type" name="type" class="form-select">
                                <option value="regular" {{ old('type', $client->type ?? 'regular') == 'regular' ?
                                    'selected' : '' }}>Regular</option>
                                <option value="trial" {{ old('type', $client->type ?? 'regular') == 'trial' ? 'selected'
                                    : '' }}>Prospect (No invoices can be generated)</option>
                            </select>
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-type"></div>
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
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-currency"></div>
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Business Logo</label>
                            @php
                            $hasLogo = isset($client) && $client->logo_path;
                            @endphp
                            <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                style="cursor:pointer;" id="logo-drop-zone">
                                <input type="file" id="logo" name="logo" accept="image/*"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0">

                                <div class="drop-zone-prompt {{ $hasLogo ? 'd-none' : 'd-flex' }} align-items-center justify-content-start px-2"
                                    id="drop-zone-prompt">
                                    <i class="far fa-file text-secondary mb-0 fs-5"></i>
                                    <span class="text-muted fw-medium ms-2">Drag & drop or
                                        <span class="text-primary fw-semibold">browse</span></span>
                                </div>

                                <div class="drop-zone-preview {{ $hasLogo ? '' : 'd-none' }} align-items-center justify-content-between w-100 px-2"
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
                            <div class="text-danger small mt-1 ajax-error" id="ajax-error-logo"></div>
                        </div>
                        <div class="col-12 mt-2 text-end">
                            @if(!isset($client))
                            <button type="button" id="btn-save-client-info"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Client Info <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Address & Contacts -->
            <div class="col-12 col-lg-3 @if(!$showDetails) d-none @endif" id="address-contacts-column">
                <div class="bg-light p-2 rounded-3 h-100 d-flex flex-column">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Business Address</h5>
                    </div>

                    <div class="row g-2 form-grid mb-3">
                        <div class="col-12 col-md-6">
                            <label for="country"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                            <select id="country" name="country" class="form-select country-select"
                                data-selected="{{ old('country', $client->country ?? 'India') }}">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="state" class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                            <select id="state" name="state" class="form-select state-select"
                                data-selected="{{ old('state', $client->state ?? '') }}">
                                <option value="">Select</option>
                            </select>
                            @error('state') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="city" class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                            <select id="city" name="city" class="form-select city-select"
                                data-selected="{{ old('city', $client->city ?? '') }}">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="postal_code" class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control"
                                value="{{ old('postal_code', $client->postal_code ?? '') }}">
                        </div>
                        <div class="col-12 col-md-12">
                            <label for="address_line_1"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                            <textarea id="address_line_1" name="address_line_1" rows="2"
                                class="form-control">{{ old('address_line_1', $client->address_line_1 ?? '') }}</textarea>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="mt-3 bg-white p-2 rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="fw-semibold text-primary small lh-sm mb-0 align-self-end">Client Contacts
                                    </h5>
                                    <button type="button"
                                        class="btn btn-xs lh-base btn-outline-primary btn-primary text-white py-1 px-2 h-auto"
                                        id="add-contact-btn">
                                        <i class="fas fa-plus me-1"></i> Add Contact
                                    </button>
                                </div>

                                <div class="table-responsive border rounded bg-white">
                                    <table class="table table-striped mainTable mb-0" id="contacts-table">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="text-center px-2" width="10%">SN</th>
                                                <th class="px-2" width="60%">Name & Designation</th>
                                                <th class="text-end px-2" width="30%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="contacts-table-body">
                                            <!-- Dynamic rows -->
                                        </tbody>
                                    </table>
                                </div>

                                <input type="hidden" name="contacts_json" id="contacts_json">
                                @error('contacts_json')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Billing Details -->
            <div class="col-12 col-lg-6 @if(!$showDetails) d-none @endif" id="billing-details-column">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Billing Profile</h5>
                        <div class="d-flex align-items-center gap-1">
                            <div class="mb-0 bg-white border rounded-1 px-2 py-1 ms-1">
                                <div class="form-check mb-0 form-check-large">
                                    <input class="form-check-input border-primary border-2" type="checkbox" id="useExistingBilling"
                                        style="cursor:pointer;" {{ old('existing_bd_id', $client->bd_id ?? '') !== '' ?
                                    'checked' : '' }}>
                                    <label class="form-check-label small lh-sm fw-normal text-dark"
                                        style="cursor:pointer;" for="useExistingBilling">
                                        Copy Billing Profile
                                    </label>
                                </div>
                            </div>
                            <div class="mb-0 bg-white border rounded-1 px-2 py-1 ms-1">
                                <div class="form-check mb-0 form-check-large">
                                    <input class="form-check-input border-primary border-2" type="checkbox" id="billing_same_as_client"
                                        name="billing_same_as_client" value="1" {{ old('billing_same_as_client')
                                        ? 'checked' : '' }}>
                                    <label class="form-check-label small lh-sm fw-normal text-dark"
                                        for="billing_same_as_client">
                                        Same as Client Details
                                    </label>
                                </div>
                            </div>
                            <!--<button type="button" id="new-billing-btn"
                                class="btn btn-outline-primary btn-primary text-white btn-icon-square h-auto py-2 ms-1"
                                title="Reload Billing Profile">
                                <i class="fas fa-sync-alt"></i>
                            </button>-->
                        </div>
                    </div>




                    <div id="existingBillingWrap"
                        class="position-relative  bg-secondary p-2 rounded-3 mb-2 {{ old('existing_bd_id', $client->bd_id ?? '') !== '' ? '' : 'd-none' }}">
                        <select id="existing_bd_id" name="existing_bd_id" class="form-select form-select-sm">
                            <option value="">Copy Profile From</option>
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
                    <div id="billing-profile-usage-info" class="mb-4 text-muted small fw-medium d-none"
                        style="font-size: 0.85rem; padding: 0 4px;"></div>

                    <div id="new-billing-fields">
                        <div class="row g-2 form-grid">
                            <div class="col-12 col-md-12">
                                <label for="billing_business_name"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Bill to
                                    Name<span class="text-danger">*</span></label>
                                <input type="text" id="billing_business_name" name="billing_business_name"
                                    class="form-control"
                                    value="{{ old('billing_business_name', isset($client) ? ($client->billingDetail->business_name ?? $client->business_name) : '') }}">
                                @error('billing_business_name') <div class="text-danger small mt-1">{{ $message }}
                                </div>
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
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Billing
                                    Address</label>
                                <textarea id="billing_address_line_1" name="billing_address_line_1" rows="3"
                                    class="form-control">{{ old('billing_address_line_1', $client->billingDetail->address_line_1 ?? '') }}</textarea>
                                @error('billing_address_line_1') <div class="text-danger small mt-1">{{ $message }}
                                </div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-12">
                                <!-- Form Actions outside the columns -->
                                <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                                    <button type="submit"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium">
                                        Finalize Client
                                        <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Groups Modal -->
    <div class="modal fade" id="groupsModal" tabindex="-1" aria-labelledby="groupsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-white py-2">
                    <h5 class="modal-title fw-semibold" id="groupsModalLabel">Add Client Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-DarkLight p-3">
                    <form id="groupForm" method="POST" action="{{ route('groups.store') }}" class="mainForm">
                        @csrf
                        <div id="groupMethodField"></div>
                        <div class="row g-2 mb-3">
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

                            <!-- Registered Address (col-md-6) -->
                            <div class="col-12 col-md-6">
                                <div class="p-2 rounded bg-light h-100 form-grid">
                                    <h6 class="fw-semibold text-primary mb-2">Registered Address</h6>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label for="groupRegisteredAddress"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                                            <input type="text" name="registered_address" id="groupRegisteredAddress"
                                                class="form-control" value="{{ old('registered_address') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupCountry"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                            <select id="groupCountry" name="country" class="form-select country-select"
                                                data-selected="India">
                                                <option value="">Select Country</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupState"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                                            <select id="groupState" name="state" class="form-select state-select"
                                                data-selected="">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupCity"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                            <select id="groupCity" name="city" class="form-select city-select"
                                                data-selected="">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupPostalCode"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                                Code</label>
                                            <input type="text" name="postal_code" id="groupPostalCode"
                                                class="form-control" value="{{ old('postal_code') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Address (col-md-6) -->
                            <div class="col-12 col-md-6">
                                <div class="p-2 rounded bg-light h-100 form-grid">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-semibold text-primary mb-0 align-self-end">Business Address</h6>
                                        <div class="mb-0 bg-white border rounded-1 px-1 py-0">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input border-primary border-2" type="checkbox"
                                                    id="formGroupSameAsRegistered" value="1">
                                                <label class="form-check-label small lh-sm fw-normal text-dark"
                                                    for="formGroupSameAsRegistered">
                                                    Same as Registered Address
                                                </label>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label for="groupBusinessAddress"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                                            <input type="text" name="business_address" id="groupBusinessAddress"
                                                class="form-control" value="{{ old('business_address') }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessCountry"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                            <select id="groupBusinessCountry" name="business_country"
                                                class="form-select country-select" data-selected="India">
                                                <option value="">Select Country</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessState"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">State</label>
                                            <select id="groupBusinessState" name="business_state"
                                                class="form-select state-select" data-selected="">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessCity"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                            <select id="groupBusinessCity" name="business_city"
                                                class="form-select city-select" data-selected="">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="groupBusinessPostalCode"
                                                class="form-label small lh-sm fw-semibold text-dark mb-1">Postal
                                                Code</label>
                                            <input type="text" name="business_postal_code" id="groupBusinessPostalCode"
                                                class="form-control" value="{{ old('business_postal_code') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="submit" id="groupSubmitBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Client Group <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Modal -->
    <div class="modal fade" id="categoriesModal" tabindex="-1" aria-labelledby="categoriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 bg-white py-2">
                    <h5 class="modal-title fw-semibold" id="categoriesModalLabel">Add Client Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-DarkLight p-3">
                    <form id="categoryForm" method="POST" action="{{ route('client-categories.store') }}" class="mainForm">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label for="categoryName" class="form-label small lh-sm fw-semibold text-dark mb-1">Category Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="categoryName" required class="form-control">
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="submit" id="categorySubmitBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content border-0">
                <div class="modal-header bg-white border-0 py-2">
                    <h5 class="modal-title fw-semibold" id="contactModalLabel">Add Client Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-DarkLight p-3">
                    <div id="contact-form-errors" class="alert alert-danger d-none py-2 mb-3 small"></div>
                    <form id="contactForm" onsubmit="event.preventDefault();" class="mainForm">
                        <input type="hidden" id="contact_index" value="">
                        <input type="hidden" id="contact_id_field" value="">
                        <div class="row g-2">
                            <div class="col-12 col-md-12">
                                <label for="contact_name_field"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Name<span
                                        class="text-danger">*</span></label>
                                <input type="text" id="contact_name_field" required class="form-control">
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="contact_designation_field"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Designation</label>
                                <input type="text" id="contact_designation_field" class="form-control">
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="contact_email_field"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Email</label>
                                <input type="email" id="contact_email_field" class="form-control">
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="contact_phone_field"
                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                                <input type="tel" id="contact_phone_field" class="form-control">
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input border-primary border-2" type="checkbox" id="contact_is_primary_field"
                                        style="cursor:pointer;">
                                    <label class="form-check-label fw-normal text-dark mb-0" style="cursor:pointer;"
                                        for="contact_is_primary_field">
                                        Set as Primary
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="button" id="save-contact-btn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Contact <i class="fas fa-arrow-right btn-icon ms-1"></i>
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

<script type="application/json"
    id="existing-contacts-data">{!! json_encode(old('contacts_json') ? json_decode(old('contacts_json'), true) : (isset($client) ? $client->contacts : [])) !!}</script>

<script>
    (function () {
        let clientId = document.getElementById('clientid_hidden').value;
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

        function updateBillingProfileUsage() {
            const usageInfo = document.getElementById('billing-profile-usage-info');
            if (!usageInfo) return;
            const bdId = existingSelect.value;
            if (!bdId || !billingProfiles[bdId]) {
                usageInfo.classList.add('d-none');
                usageInfo.innerHTML = '';
                return;
            }

            const profile = billingProfiles[bdId];
            const clients = profile.clients || [];

            const initialBdId = document.getElementById('clientid_hidden')?.dataset.initialBdId || '';

            const otherClients = clients.filter(c => {
                if (clientId && initialBdId === bdId) {
                    return c.clientid !== clientId;
                }
                return true;
            });

            if (otherClients.length > 0) {
                const names = otherClients.map(c => c.business_name || 'Unnamed Client').join(', ');
                usageInfo.innerHTML = `<i class="fas fa-info-circle me-1"></i> Shared with: <strong class="text-dark">${names}</strong>`;
                usageInfo.classList.remove('d-none');
            } else {
                usageInfo.classList.add('d-none');
                usageInfo.innerHTML = '';
            }
        }

        async function loadSelectedBillingProfile() {
            const bdId = existingSelect.value;
            updateBillingProfileUsage();
            if (!bdId || !billingProfiles[bdId]) return;
            const profile = billingProfiles[bdId];
            billingName.value = profile.business_name || '';
            billingGstin.value = profile.gstin || '';
            billingEmail.value = profile.billing_email || '';
            billingPhone.value = profile.billing_phone || '';
            billingAddress.value = profile.address_line_1 || '';
            billingPostal.value = profile.postal_code || '';

            setSelectValueAndNotify(billingCountry, profile.country || 'India');

            if (profile.state) {
                await waitForOption(billingState, profile.state);
                setSelectValueAndNotify(billingState, profile.state);
            } else {
                setSelectValueAndNotify(billingState, '');
            }

            if (profile.city) {
                await waitForOption(billingCity, profile.city);
                setSelectValueAndNotify(billingCity, profile.city);
            } else {
                setSelectValueAndNotify(billingCity, '');
            }
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
                updateBillingProfileUsage();
            } else {
                existingBillingWrap.classList.add('d-none');
                existingSelect.value = '';
                clearBillingFields();
                updateBillingProfileUsage();
            }
        });

        existingSelect.addEventListener('change', loadSelectedBillingProfile);
        if (newBillingBtn) {
            newBillingBtn.addEventListener('click', function () {
                existingSelect.value = '';
                clearBillingFields();
                updateBillingProfileUsage();
            });
        }
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

        const btnSaveClientInfo = document.getElementById('btn-save-client-info');

        function showSuccessToast(message) {
            if (typeof showToast === 'function') {
                showToast('success', message);
            } else if (typeof window.showToast === 'function') {
                window.showToast('success', message);
            }
        }

        if (btnSaveClientInfo) {
            btnSaveClientInfo.addEventListener('click', async function (e) {
                const clientInfoContainer = btnSaveClientInfo.closest('.bg-light');
                if (clientInfoContainer) {
                    const requiredFields = clientInfoContainer.querySelectorAll('[required]');
                    let allValid = true;
                    for (const field of requiredFields) {
                        if (!field.checkValidity()) {
                            field.reportValidity();
                            allValid = false;
                            break;
                        }
                    }
                    if (!allValid) {
                        return;
                    }
                }
                e.preventDefault();

                document.querySelectorAll('.ajax-error').forEach(el => el.textContent = '');

                btnSaveClientInfo.disabled = true;
                btnSaveClientInfo.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                if (clientId) {
                    formData.append('clientid', clientId);
                }
                formData.append('business_name', document.getElementById('business_name').value);
                formData.append('groupid', document.getElementById('groupid').value);
                formData.append('primary_email', document.getElementById('primary_email').value);
                formData.append('email', document.getElementById('email').value);
                formData.append('phone', document.getElementById('phone').value);
                formData.append('whatsapp_number', document.getElementById('whatsapp_number').value);
                formData.append('type', document.getElementById('type').value);
                formData.append('currency', document.getElementById('currency').value);

                const logoInput = document.getElementById('logo');
                if (logoInput && logoInput.files[0]) {
                    formData.append('logo', logoInput.files[0]);
                }

                let savedSuccessfully = false;
                try {
                    const response = await fetch("{{ route('clients.ajax-save-info') }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    if (response.ok) {
                        const result = await response.json();
                        clientId = result.clientid;
                        document.getElementById('clientid_hidden').value = clientId;

                        const form = document.querySelector('.mainForm');
                        if (form) {
                            form.action = "{{ route('clients.update', ':id') }}".replace(':id', clientId);
                            let methodInput = form.querySelector('input[name="_method"]');
                            if (!methodInput) {
                                methodInput = document.createElement('input');
                                methodInput.type = 'hidden';
                                methodInput.name = '_method';
                                methodInput.value = 'PUT';
                                form.appendChild(methodInput);
                            }
                        }

                        // Update browser URL to edit page without reloading to avoid losing state on refresh
                        const editUrl = "{{ route('clients.edit', ':id') }}".replace(':id', clientId);
                        window.history.replaceState(null, '', editUrl);

                        showSuccessToast(result.message || 'Client saved successfully.');

                        // Show Column 2 and Column 3
                        const col2 = document.getElementById('address-contacts-column');
                        const col3 = document.getElementById('billing-details-column');
                        if (col2) {
                            col2.classList.remove('d-none');
                        }
                        if (col3) {
                            col3.classList.remove('d-none');
                        }

                        renderContacts();

                        if (result.logo_path) {
                            const previewImg = document.getElementById('logo-preview-img');
                            if (previewImg) {
                                previewImg.src = result.logo_path;
                            }
                        }
                        savedSuccessfully = true;
                    } else if (response.status === 422) {
                        const errData = await response.json();
                        for (const [field, messages] of Object.entries(errData.errors || {})) {
                            const errEl = document.getElementById(`ajax-error-${field}`);
                            if (errEl) {
                                errEl.textContent = messages[0];
                            }
                        }
                    } else {
                        alert('Something went wrong. Please check fields.');
                    }
                } catch (error) {
                    console.error('AJAX Client save error', error);
                    alert('An error occurred while saving.');
                } finally {
                    if (savedSuccessfully) {
                        btnSaveClientInfo.classList.add('d-none');
                    } else {
                        btnSaveClientInfo.disabled = false;
                        btnSaveClientInfo.innerHTML = 'Save Client Info <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                    }
                }
            });
        }

        // Client Contacts management
        let contactsList = [];
        const existingContactsEl = document.getElementById('existing-contacts-data');
        if (existingContactsEl) {
            try {
                contactsList = JSON.parse(existingContactsEl.textContent || '[]');
            } catch (e) {
                console.error('Failed to parse contacts', e);
            }
        }

        const contactsTableBody = document.getElementById('contacts-table-body');
        const contactsJsonInput = document.getElementById('contacts_json');

        function renderContacts() {
            if (!contactsTableBody) return;

            // Sort contacts so primary contact(s) come first
            contactsList.sort((a, b) => {
                const aPrimary = a.is_primary ? 1 : 0;
                const bPrimary = b.is_primary ? 1 : 0;
                return bPrimary - aPrimary;
            });

            contactsTableBody.innerHTML = '';

            if (contactsList.length === 0) {
                contactsTableBody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3 small">No contacts added yet.</td>
                    </tr>
                `;
                contactsJsonInput.value = '';
                return;
            }

            contactsList.forEach((contact, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center px-2 align-middle" width="10%">${index + 1}</td>
                    <td class="px-2 align-middle fw-medium">
                         ${contact.is_primary
                        ? '<span class="badge bg-light text-success border border-success-subtle px-2 py-0.5 rounded-pill d-inline-flex" style="font-size: 0.65rem;">Primary</span> <br/>'
                        : ''
                    }
                        ${escapeHtml(contact.name)} <div class="small lh-sm text-muted">${escapeHtml(contact.designation || '')}</div></td>
                    <td class="text-end px-2 align-middle">
                        <div class="tableActionButton d-inline-flex gap-1">
                            <button type="button" class="bg03 color03 edit-contact-btn" data-index="${index}">
                                Edit
                            </button>
                            <button type="button" class="bg04 color04 delete-contact-btn" data-index="${index}">
                                Delete
                            </button>
                        </div>
                    </td>
                `;
                contactsTableBody.appendChild(tr);
            });

            contactsJsonInput.value = JSON.stringify(contactsList);

            // Attach event listeners
            contactsTableBody.querySelectorAll('.edit-contact-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    openContactModal(idx);
                });
            });

            contactsTableBody.querySelectorAll('.delete-contact-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const idx = parseInt(this.getAttribute('data-index'), 10);
                    deleteContact(idx);
                });
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
        const contactForm = document.getElementById('contactForm');
        const contactFormErrors = document.getElementById('contact-form-errors');
        const contactIndexInput = document.getElementById('contact_index');
        const contactNameInput = document.getElementById('contact_name_field');
        const contactDesignationInput = document.getElementById('contact_designation_field');
        const contactEmailInput = document.getElementById('contact_email_field');
        const contactPhoneInput = document.getElementById('contact_phone_field');
        const contactIsPrimaryInput = document.getElementById('contact_is_primary_field');
        const saveContactBtn = document.getElementById('save-contact-btn');
        const addContactBtn = document.getElementById('add-contact-btn');

        if (addContactBtn) {
            addContactBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!clientId) {
                    window.appAlert('Please save Client Information first before adding contacts.', {
                        title: 'Client Info Required',
                        icon: 'warning'
                    });
                    return;
                }
                openContactModal(-1);
            });
        }

        function openContactModal(index) {
            contactFormErrors.classList.add('d-none');
            contactFormErrors.textContent = '';

            if (index >= 0) {
                document.getElementById('contactModalLabel').textContent = 'Edit Client Contact';
                const contact = contactsList[index];
                contactIndexInput.value = index;
                document.getElementById('contact_id_field').value = contact.contactid || '';
                contactNameInput.value = contact.name || '';
                contactDesignationInput.value = contact.designation || '';
                contactEmailInput.value = contact.email || '';
                contactPhoneInput.value = contact.phone || '';
                contactIsPrimaryInput.checked = !!contact.is_primary;
            } else {
                document.getElementById('contactModalLabel').textContent = 'Add Client Contact';
                contactIndexInput.value = '';
                document.getElementById('contact_id_field').value = '';
                contactForm.reset();
                contactIsPrimaryInput.checked = (contactsList.length === 0);
            }
            contactModal.show();
        }

        if (saveContactBtn) {
            saveContactBtn.addEventListener('click', function (e) {
                const form = saveContactBtn.closest('form');
                if (form && !form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                e.preventDefault();
                const name = contactNameInput.value.trim();
                const designation = contactDesignationInput.value.trim();
                const email = contactEmailInput.value.trim();
                const phone = contactPhoneInput.value.trim();
                const isPrimary = contactIsPrimaryInput.checked;

                if (!name) {
                    contactFormErrors.textContent = 'Name is required.';
                    contactFormErrors.classList.remove('d-none');
                    return;
                }

                const indexVal = contactIndexInput.value;
                const contactData = {
                    name: name,
                    designation: designation,
                    email: email,
                    phone: phone,
                    is_primary: isPrimary
                };

                if (clientId) {
                    saveContactBtn.disabled = true;
                    saveContactBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

                    const contactId = document.getElementById('contact_id_field').value;
                    const url = "{{ route('clients.contacts.ajax-save', ':client') }}".replace(':client', clientId);

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            contactid: contactId || null,
                            name: name,
                            designation: designation,
                            email: email || null,
                            phone: phone || null,
                            is_primary: isPrimary
                        })
                    })
                        .then(async res => {
                            if (res.ok) {
                                const data = await res.json();
                                contactsList = data.contacts;
                                contactModal.hide();
                                renderContacts();
                            } else {
                                const errData = await res.json();
                                let errMsg = 'Failed to save contact.';
                                if (errData.errors && errData.errors.name) {
                                    errMsg = errData.errors.name[0];
                                }
                                contactFormErrors.textContent = errMsg;
                                contactFormErrors.classList.remove('d-none');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            contactFormErrors.textContent = 'An error occurred while saving contact.';
                            contactFormErrors.classList.remove('d-none');
                        })
                        .finally(() => {
                            saveContactBtn.disabled = false;
                            saveContactBtn.innerHTML = 'Save Contact <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                        });
                } else {
                    if (isPrimary) {
                        contactsList.forEach(c => c.is_primary = false);
                    }

                    if (indexVal !== '') {
                        const idx = parseInt(indexVal, 10);
                        contactsList[idx] = contactData;
                    } else {
                        contactsList.push(contactData);
                    }

                    const hasPrimary = contactsList.some(c => c.is_primary);
                    if (!hasPrimary && contactsList.length > 0) {
                        contactsList[0].is_primary = true;
                    }

                    contactModal.hide();
                    renderContacts();
                }
            });
        }

        async function deleteContact(index) {
            const isConfirmed = await window.appConfirm('Are you sure you want to delete this contact?', {
                title: 'Confirm Delete',
                icon: 'warning',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            });

            if (isConfirmed) {
                const contact = contactsList[index];
                if (clientId && contact.contactid) {
                    const url = "{{ route('clients.contacts.ajax-delete', [':client', ':contact']) }}"
                        .replace(':client', clientId)
                        .replace(':contact', contact.contactid);

                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(async res => {
                            if (res.ok) {
                                const data = await res.json();
                                contactsList = data.contacts;
                                renderContacts();
                            } else {
                                alert('Failed to delete contact.');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert('An error occurred while deleting.');
                        });
                } else {
                    const wasPrimary = contactsList[index].is_primary;
                    contactsList.splice(index, 1);

                    if (wasPrimary && contactsList.length > 0) {
                        contactsList[0].is_primary = true;
                    }

                    renderContacts();
                }
            }
        }

        renderContacts();

        loadSelectedBillingProfile();
        if (sameAsClientCheckbox.checked) copyClientDetailsToBilling();

        var groupForm = document.getElementById('groupForm');
        if (groupForm) {
            groupForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('groupSubmitBtn');
                if (!btn) return;
                var originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        var select = document.getElementById('groupid');
                        select.innerHTML = '<option value="">Not part of any Group</option>';
                        data.groups.forEach(g => {
                            var opt = document.createElement('option');
                            opt.value = g.groupid;
                            opt.textContent = g.group_name;
                            select.appendChild(opt);
                        });
                        var modal = bootstrap.Modal.getInstance(document.getElementById('groupsModal'));
                        if (modal) modal.hide();
                        resetGroupForm();
                        if (window.showToast) window.showToast('success', data.message);
                    }
                }).catch(err => {
                    console.error(err);
                    alert('An error occurred while saving the group.');
                }).finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });
        }

        var categoryForm = document.getElementById('categoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var btn = document.getElementById('categorySubmitBtn');
                if (!btn) return;
                var originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';

                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        var select = document.getElementById('categoryid');
                        select.innerHTML = '<option value="">No Category</option>';
                        data.categories.forEach(c => {
                            var opt = document.createElement('option');
                            opt.value = c.categoryid;
                            opt.textContent = c.name;
                            select.appendChild(opt);
                        });
                        var modal = bootstrap.Modal.getInstance(document.getElementById('categoriesModal'));
                        if (modal) modal.hide();
                        categoryForm.reset();
                        if (window.showToast) window.showToast('success', data.message);
                    }
                }).catch(err => {
                    console.error(err);
                    alert('An error occurred while saving the category.');
                }).finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });
        }
    })();

    function selectGroup(id, name) {
        document.getElementById('groupid').value = id;
        const modal = bootstrap.Modal.getInstance(document.getElementById('groupsModal'));
        modal.hide();
    }

    document.getElementById('formGroupSameAsRegistered').addEventListener('change', function () {
        if (!this.checked) return;

        const regAddr = document.getElementById('groupRegisteredAddress');
        const busAddr = document.getElementById('groupBusinessAddress');
        if (regAddr && busAddr) { busAddr.value = regAddr.value; }

        const regPostal = document.getElementById('groupPostalCode');
        const busPostal = document.getElementById('groupBusinessPostalCode');
        if (regPostal && busPostal) { busPostal.value = regPostal.value; }

        const busCountryEl = document.getElementById('groupBusinessCountry');
        const busStateEl = document.getElementById('groupBusinessState');
        const busCityEl = document.getElementById('groupBusinessCity');
        const regCountryEl = document.getElementById('groupCountry');
        const regStateEl = document.getElementById('groupState');
        const regCityEl = document.getElementById('groupCity');

        busCountryEl.dataset.selected = regCountryEl.value;
        busStateEl.dataset.selected = regStateEl.value;
        busCityEl.dataset.selected = regCityEl.value;

        LocationPicker.loadSelection(busCountryEl);
    });

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
        const countryEl = document.getElementById('groupCountry');
        const busCountryEl = document.getElementById('groupBusinessCountry');
        if (countryEl) {
            countryEl.dataset.selected = 'India';
            countryEl.dispatchEvent(new Event('change'));
        }
        if (busCountryEl) {
            busCountryEl.dataset.selected = 'India';
            busCountryEl.dispatchEvent(new Event('change'));
        }
    }
</script>
@endsection
