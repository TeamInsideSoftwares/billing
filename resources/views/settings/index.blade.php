@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div></div>
</section>

<div class="settings-page">
<!-- Tabs Wrapper -->
<div class="settings-tabs-wrap">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="personal">Business Info</button>
        <button class="tab-button" data-tab="financial-year">Financial Year</button>
<button class="tab-button" data-tab="config">Configuration Keys</button>
        @if($account->allow_multi_taxation)
        <button class="tab-button" data-tab="billing-details">Billing Details</button>
        <button class="tab-button" data-tab="quotation-details">Quotation Details</button>
        <button class="tab-button" data-tab="terms-conditions">Terms &amp; Conditions</button>
        <button class="tab-button" data-tab="taxes">Taxes</button>
        @else
        <button class="tab-button" data-tab="billing-details">Billing Details</button>
        <button class="tab-button" data-tab="quotation-details">Quotation Details</button>
        <button class="tab-button" data-tab="terms-conditions">Terms &amp; Conditions</button>
        @endif
    </div>
</div>



<!-- PERSONAL TAB -->
<div id="personal" class="tab-content active">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-building"></i></div>
            <div>
                <h5 class="settings-section-title">Business Information</h5>
                <p class="settings-section-subtitle">Manage your public profile and billing details</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="settings-error-box">
                <div class="settings-error-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong class="settings-error-title">Please fix the following errors:</strong>
                </div>
                <ul class="settings-error-list">
                    @foreach ($errors->all() as $error)
                        <li class="settings-error-item">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="form-grid form-grid grid-cols-4">
            @csrf
            @method('PUT')

            <!-- Logo Upload -->
            <div class="col-span-1">
                <label class="text-sm">Company Logo</label>
                <div class="logo-upload-box">
                    @if(!empty($account->logo_path))
                        <img src="{{ str_starts_with($account->logo_path, 'http') ? $account->logo_path : asset($account->logo_path) }}" alt="Logo" id="logo-preview" class="logo-preview-img">
                    @else
                        <div id="logo-preview" class="logo-preview-placeholder"><i class="fas fa-image"></i></div>
                    @endif
                    <input type="file" name="logo" id="logo-upload" accept="image/*" onchange="previewLogo(this)" class="text-xs input-full">
                    <small class="text-xs text-muted-light">Square recommended. 5MB max.</small>
                </div>
            </div>

            <div>
                <label class="text-sm required">Business Name *</label>
                <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}" required class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Legal Entity Name</label>
                <input type="text" name="legal_name" value="{{ old('legal_name', $account->legal_name ?? '') }}" class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Website</label>
                <input type="text" name="website" value="{{ old('website', $account->website ?? '') }}" class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm required">Email *</label>
                <input type="email" name="email" value="{{ old('email', $account->email ?? '') }}" required class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $account->phone ?? '') }}" class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Currency</label>
                <select name="currency_code" class="settings-input-sm full">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->iso }}" {{ old('currency_code', $account->currency_code ?? 'INR') == $currency->iso ? 'selected' : '' }}>
                            {{ $currency->iso }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm">Timezone</label>
                <input type="text" name="timezone" value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}" class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Address</label>
                <input type="text" name="address_line_1" value="{{ old('address_line_1', $account->address_line_1 ?? '') }}" class="settings-input-sm">
            </div>

            <div>
                <label class="text-sm">Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $account->country ?? '') }}" class="settings-input-sm full">
                    <option value="">Select Country</option>
                </select>
            </div>

            <div>
                <label class="text-sm">State *</label>
                <select name="state" required class="state-select" data-selected="{{ old('state', $account->state ?? '') }}" class="settings-input-sm full">
                    <option value="">Select State</option>
                </select>
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="text-sm">City</label>
                <select name="city" class="city-select" data-selected="{{ old('city', $account->city ?? '') }}" class="settings-input-sm full">
                    <option value="">Select City</option>
                </select>
            </div>

            <div>
                <label class="text-sm">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $account->postal_code ?? '') }}" class="settings-input-sm">
            </div>
            <div>
                <label class="text-sm">FY Start (Day & Month)</label>
                <div class="flex-gap">
                    @php
                        $currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
                        $parts = explode('-', $currentFy);
                        $curMonth = $parts[0] ?? '04';
                        $curDay = $parts[1] ?? '01';
                    @endphp
                    <select name="fy_day" class="fy-day-select">
                        @for ($i = 1; $i <= 31; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $curDay == sprintf('%02d', $i) ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <select name="fy_month" class="fy-month-select">
                        @foreach(['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $mVal => $mName)
                            <option value="{{ $mVal }}" {{ $curMonth == $mVal ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Multi-Taxation Toggle -->
            <div class="settings-wide-card">
                <div class="flex-between">
                    <div>
                        <label class="settings-toggle-title">Allow Multi-Taxation</label>
                        <p class="settings-toggle-note">Enable multiple tax rates (GST, VAT, etc.) across invoices, orders, and quotations</p>
                    </div>
                    <div class="flex-center-gap">
                        <span class="settings-toggle-state {{ $account->allow_multi_taxation ? 'is-on' : 'is-off' }}">{{ $account->allow_multi_taxation ? 'Yes' : 'No' }}</span>
                        <label class="toggle-wrap">
                            <input type="checkbox" name="allow_multi_taxation" value="1" {{ old('allow_multi_taxation', $account->allow_multi_taxation ?? false) ? 'checked' : '' }} class="toggle-input">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Fixed Tax Rate (shown when multi-taxation is NO) -->
            <div id="fixed-tax-section" class="settings-wide-card settings-wide-card-sm {{ $account->allow_multi_taxation ? 'hidden' : '' }}">
                <div class="flex-between">
                    <div>
                        <label class="settings-toggle-title">Fixed Tax Rate</label>
                        <p class="settings-toggle-note">
                            {{ $account->allow_multi_taxation ? 'Enable multi-taxation to configure multiple tax rates' : 'Single tax rate applied to all orders and invoices' }}
                        </p>
                    </div>
                    <div class="flex-center-gap">
                        @if(!$account->allow_multi_taxation)
                        <span class="fixed-tax-pill">
                            {{ $account->fixed_tax_type ?? 'GST' }} {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                        </span>
                        <button type="button" id="open-fixed-tax-modal" class="primary-button btn-md">
                            <i class="fas fa-edit icon-spaced-sm"></i> {{ ($account->fixed_tax_rate ?? 0) > 0 ? 'Edit Tax' : 'Add Tax' }}
                        </button>
                        @else
                        <span class="settings-toggle-state is-disabled">Disabled</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Have Users Toggle -->
            <div class="settings-wide-card settings-wide-card-sm">
                <div class="flex-between">
                    <div>
                        <label class="settings-toggle-title">Does your Products/Services are with the No. of Users?</label>
                        <p class="settings-toggle-note">Enable user-based features (accounts, services, and products can be assigned to specific users)</p>
                    </div>
                    <div class="flex-center-gap">
                        <span class="settings-toggle-state {{ $account->have_users ? 'is-on' : 'is-off' }}">{{ $account->have_users ? 'Yes' : 'No' }}</span>
                        <label class="toggle-wrap">
                            <input type="checkbox" name="have_users" value="1" {{ old('have_users', $account->have_users ?? false) ? 'checked' : '' }} class="toggle-input">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>


            <div class="form-actions form-actions settings-actions">
                <button type="submit" class="primary-button primary-button btn-md">Update Profile</button>
            </div>
        </form>
    </section>
</div>

<!-- FINANCIAL YEAR -->
<div id="financial-year" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h5 class="settings-section-title">Financial Year</h5>
                <p class="settings-section-subtitle">Configure your financial year and serial numbers</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="settings-error-box">
                <div class="settings-error-head">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong class="settings-error-title">Please fix the following errors:</strong>
                </div>
                <ul class="settings-error-list">
                    @foreach ($errors->all() as $error)
                        <li class="settings-error-item">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="settings-split">
            <!-- FY Form -->
            <div class="settings-half">
                <h6 class="settings-subhead">Add Financial Year</h6>
                <form method="POST" action="{{ route('financial-year.update') }}" class="settings-card-soft">
                    @csrf
                    <div class="flex-end-gap">
                        <div class="flex-fill">
                            <label class="label-compact text-muted">Start Year</label>
                            <select name="year_start" id="fy_year_start" required class="settings-select">
                                @php $currentYear = date('Y'); @endphp
                                @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
                                    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <span class="text-muted-light font-bold">-</span>
                        <div class="flex-fill">
                            <label class="label-compact text-muted">End Year</label>
                            <select name="year_end" id="fy_year_end" required class="settings-select">
                                @for($y = $currentYear; $y <= $currentYear + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == $currentYear + 1 ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="primary-button primary-button btn-xs h-36">Add</button>
                    </div>
                </form>
            </div>

            <!-- FY List -->
            <div class="settings-half">
                <h6 class="settings-subhead">Recorded Financial Years</h6>
                <table class="data-table text-xs">
                    <thead>
                        <tr>
                            <th class="w-30px">#</th>
                            <th>Financial Year</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($financialYears as $index => $fy)
                            <tr>
                                <td class="text-xs text-muted">{{ $index + 1 }}</td>
                                <td class="font-medium text-sm">{{ $fy->financial_year }}</td>
                                <td>
                                    @if($fy->default)
                                        <span class="status-pill status-pill-completed text-xs">Default</span>
                                    @else
                                        <span class="text-xs text-muted-light">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if(!$fy->default)
                                        <form method="POST" action="{{ route('financial-year.default', $fy->fy_id) }}" class="inline-delete">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-link text-link btn-outline-primary-xs">Set Default</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="no-records-cell">No financial years yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Serial Configuration -->
        <div class="settings-block-sep">
            <div class="settings-error-head">
                <div class="settings-section-icon"><i class="fas fa-hashtag"></i></div>
                <h6 class="settings-block-title">Serial Number Configuration</h6>
            </div>
            <p class="settings-block-note">Configure how invoice and quotation numbers are generated.</p>
            @include('settings.serial-config')
        </div>
    </section>
</div>

<!-- CONFIG -->
<div id="config" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-cog"></i></div>
            <div>
                <h5 class="settings-section-title">Configuration Keys</h5>
                <p class="settings-section-subtitle">Manage system-wide configuration keys</p>
            </div>
        </div>

        <div class="settings-card-soft mb-4">
            <h6 class="settings-subhead">
                {{ $editingSetting ? 'Edit Configuration Key' : 'Add New Configuration Key' }}
            </h6>

            <form method="POST" action="{{ $editingSetting ? route('settings.update', $editingSetting->settingid) : route('settings.store') }}" class="settings-grid-3">
                @csrf
                @if($editingSetting)
                    @method('PUT')
                @endif

                <div>
                    <label class="label-compact">Key Name *</label>
                    <select id="config-key-select" name="key" required class="settings-input">
                        <option value="">-- Select Key --</option>
                        @php
                            $currentKey = old('key', $editingSetting->setting_key ?? '');
                        @endphp
                        @foreach($suggestedKeys as $group => $keys)
                            <optgroup label="{{ $group }}">
                                @foreach($keys as $key => $label)
                                    <option value="{{ $key }}" {{ $currentKey == $key ? 'selected' : '' }}>{{ $key }} ({{ $label }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-compact">Value *</label>
                    <input type="text" name="value" value="{{ old('value', $editingSetting->setting_value ?? '') }}" placeholder="Enter value" required class="settings-input">
                </div>
                <div class="flex-gap">
                    <button type="submit" class="primary-button btn-md">
                        {{ $editingSetting ? 'Update Key' : 'Add Key' }}
                    </button>
                    @if($editingSetting)
                        <a href="{{ route('settings.index') }}#config" class="text-link text-link btn-outline-muted-xs">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="flex-between mb-3">
            <h6 class="settings-block-title">System Settings</h6>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-40px">#</th>
                    <th>Key</th>
                    <th>Value</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settings as $index => $setting)
                    <tr>
                        <td class="text-sm text-muted">{{ $index + 1 }}</td>
                        <td><code>{{ $setting['key'] }}</code></td>
                        <td>{{ $setting['value'] }}</td>
                        <td class="text-left">
                            <div class="table-actions justify-content-start">
                                <a href="{{ route('settings.index', ['e' => base64_encode($setting['record_id'])]) }}#config" class="icon-action-btn edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('settings.destroy', $setting['record_id']) }}" onsubmit="return confirm('Delete this setting?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="icon-action-btn delete" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
@empty
                    <tr>
                        <td colspan="4" class="no-records-cell">No settings found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- BILLING DETAILS TAB -->
<div id="billing-details" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <h5 class="settings-section-title">Billing Details</h5>
                <p class="settings-section-subtitle">Configure billing information that appears on invoices</p>
            </div>
        </div>
        @if ($errors->any())
            <div class="settings-error-box settings-error-box-soft">
                <ul class="settings-error-list settings-error-list-compact">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{-- DEBUG: Check if editingBillingDetail exists --}}
        @php
            echo '<!-- DEBUG: editingBillingDetail = ' . (isset($editingBillingDetail) ? 'SET' : 'NOT SET') . ' -->';
            if(isset($editingBillingDetail)) {
                echo '<!-- DEBUG: billing_name = ' . ($editingBillingDetail->billing_name ?? 'NULL') . ' -->';
                echo '<!-- DEBUG: country = ' . ($editingBillingDetail->country ?? 'NULL') . ' -->';
                echo '<!-- DEBUG: gstin = ' . ($editingBillingDetail->gstin ?? 'NULL') . ' -->';
            }
        @endphp
        <form method="POST" action="{{ route('account.billing.update') }}" enctype="multipart/form-data" class="form-grid settings-form-3col">
            @csrf
            @if(isset($editingBillingDetail))
                <input type="hidden" name="account_bdid" value="{{ $editingBillingDetail->account_bdid }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">

            <div>
                <label class="required">Billing Name</label>
                <input type="text" name="billing_name" value="{{ old('billing_name', $editingBillingDetail->billing_name ?? '') }}" required>
            </div>

            <div>
                <label>Billing From Email</label>
                <input type="email" name="billing_from_email" value="{{ old('billing_from_email', $editingBillingDetail->billing_from_email ?? '') }}">
            </div>
            <div>
                <label>Authorize Signatory</label>
                <input type="text" name="authorize_signatory" value="{{ old('authorize_signatory', $editingBillingDetail->authorize_signatory ?? '') }}">
            </div>
            <div class="col-span-3">
                <label class="text-sm">Address</label>
                <textarea name="address" rows="2" class="settings-textarea">{{ old('address', $editingBillingDetail->address ?? '') }}</textarea>
            </div>
            <div>
                <label>Country</label>
                <select name="country" class="country-select settings-input-sm full" data-selected="{{ old('country', $editingBillingDetail->country ?? 'India') }}">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label>State *</label>
                <select name="state" required class="state-select settings-input-sm full" data-selected="{{ old('state', $editingBillingDetail->state ?? '') }}">
                    <option value="">Select State</option>
                </select>
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label>City</label>
                <select name="city" class="city-select settings-input-sm full" data-selected="{{ old('city', $editingBillingDetail->city ?? '') }}">
                    <option value="">Select City</option>
                </select>
            </div>
            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $editingBillingDetail->postal_code ?? '') }}">
            </div>
            <div>
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingBillingDetail->gstin ?? '') }}"
                    maxlength="15" minlength="15" pattern="[A-Z0-9]{15}"
                    title="GSTIN must be exactly 15 characters"
                    oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                    onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}">
                <span class="help-text">Exactly 15 characters required</span>
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingBillingDetail->tin ?? '') }}">
            </div>
            <div>
                <label>Signature Upload</label>
                <input type="file" name="signature_upload" id="billing-signature-upload" accept="image/*" onchange="previewSignature(this, 'billing-signature-preview')">
                <small class="help-text help-text-muted">Max file size: 5MB. Supported formats: JPG, PNG, GIF, SVG</small>
                @if(!empty($editingBillingDetail->signature_upload))
                    <div class="signature-block">
                        <small class="help-text help-text-muted mb-1">Current signature:</small>
                        <img id="billing-signature-preview" src="{{ $editingBillingDetail->signature_upload }}" alt="Signature" class="signature-preview-img">
                    </div>
                @else
                    <div id="billing-signature-preview" class="signature-block hidden">
                        <small class="help-text help-text-muted mb-1">Preview:</small>
                        <img src="" alt="Signature Preview" class="signature-preview-img">
                    </div>
                @endif
            </div>

            <div class="form-actions settings-actions-3col">
                <button type="submit" class="primary-button primary-button btn-md">Save Billing Detail</button>
                @if(isset($editingBillingDetail) && request('edit_bd'))
                    <a href="{{ route('settings.index') }}#billing-details" class="text-link ml-4">Cancel</a>
                @endif
            </div>
        </form>

<!-- Single billing detail form (no list) -->
    </section>
</div>

<!-- QUOTATION DETAILS TAB -->
<div id="quotation-details" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-file-contract"></i></div>
            <div>
                <h5 class="settings-section-title">Quotation Details</h5>
                <p class="settings-section-subtitle">Configure quotation details for quotations</p>
            </div>
        </div>
        @if ($errors->any())
            <div class="settings-error-box settings-error-box-soft">
                <ul class="settings-error-list settings-error-list-compact">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('account.quotation.update') }}" enctype="multipart/form-data" class="form-grid settings-form-3col">
            @csrf
            @if(isset($editingQuotationDetail))
                <input type="hidden" name="account_qdid" value="{{ $editingQuotationDetail->account_qdid }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">

            <div>
                <label class="required">Quotation Name</label>
                <input type="text" name="quotation_name" value="{{ old('quotation_name', $editingQuotationDetail->quotation_name ?? '') }}" required>
            </div>
            <div>
                <label>Billing From Email</label>
                <input type="email" name="billing_from_email" value="{{ old('billing_from_email', $editingQuotationDetail->billing_from_email ?? '') }}">
            </div>
            <div>
                <label>Authorize Signatory</label>
                <input type="text" name="authorize_signatory" value="{{ old('authorize_signatory', $editingQuotationDetail->authorize_signatory ?? '') }}">
            </div>
            <div class="col-span-3">
                <label class="text-sm">Address</label>
                <textarea name="address" rows="2" class="settings-textarea">{{ old('address', $editingQuotationDetail->address ?? '') }}</textarea>
            </div>
            <div>
                <label>Country</label>
                <select name="country" class="country-select settings-input-sm full" data-selected="{{ old('country', $editingQuotationDetail->country ?? 'India') }}">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label>State</label>
                <select name="state" class="state-select settings-input-sm full" data-selected="{{ old('state', $editingQuotationDetail->state ?? '') }}">
                    <option value="">Select State</option>
                </select>
            </div>
            <div>
                <label>City</label>
                <select name="city" class="city-select settings-input-sm full" data-selected="{{ old('city', $editingQuotationDetail->city ?? '') }}">
                    <option value="">Select City</option>
                </select>
            </div>
            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $editingQuotationDetail->postal_code ?? '') }}">
            </div>
            <div>
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingQuotationDetail->gstin ?? '') }}"
                    maxlength="15" minlength="15" pattern="[A-Z0-9]{15}"
                    title="GSTIN must be exactly 15 characters"
                    oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                    onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}">
                <span class="help-text">Exactly 15 characters required</span>
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingQuotationDetail->tin ?? '') }}">
            </div>
            <div>
                <label>Signature Upload</label>
                <input type="file" name="signature_upload" id="quotation-signature-upload" accept="image/*" onchange="previewSignature(this, 'quotation-signature-preview')">
                <small class="help-text help-text-muted">Max file size: 5MB. Supported formats: JPG, PNG, GIF, SVG</small>
                @if(!empty($editingQuotationDetail->signature_upload))
                    <div class="signature-block">
                        <small class="help-text help-text-muted mb-1">Current signature:</small>
                        <img id="quotation-signature-preview" src="{{ $editingQuotationDetail->signature_upload }}" alt="Signature" class="signature-preview-img">
                    </div>
                @else
                    <div id="quotation-signature-preview" class="signature-block hidden">
                        <small class="help-text help-text-muted mb-1">Preview:</small>
                        <img src="" alt="Signature Preview" class="signature-preview-img">
                    </div>
                @endif
            </div>

            <div class="form-actions settings-actions-3col">
                <button type="submit" class="primary-button primary-button btn-md">Save Quotation Detail</button>
                @if(isset($editingQuotationDetail) && request('edit_qd'))
                    <a href="{{ route('settings.index') }}#quotation-details" class="text-link ml-4">Cancel</a>
                @endif
            </div>
        </form>

<!-- Single quotation detail form (no list) -->
    </section>
</div>

<!-- TERMS & CONDITIONS TAB -->
<div id="terms-conditions" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h5 class="settings-section-title">Terms & Conditions</h5>
                <p class="settings-section-subtitle">Manage reusable terms for documents</p>
            </div>
        </div>

        {{-- Add / Edit Form --}}
        <div style="padding: 0.75rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 1rem;">
            <h6 style="margin: 0 0 0.5rem 0; font-size: 0.85rem; color: #1e293b; font-weight: 600;">
                {{ $editingTerm ? 'Edit Term' : 'Add New Term' }}
            </h6>
            <form method="POST" action="{{ route('terms-conditions.store') }}" style="display: flex; gap: 0.5rem; align-items: end;">
                @csrf
                @if($editingTerm)
                    <input type="hidden" name="tc_id" value="{{ $editingTerm->tc_id }}">
                @endif

                <div style="flex: 0 0 120px;">
                    <label style="font-size: 0.75rem; margin-bottom: 0.2rem; display: block; color: #64748b;">Type *</label>
                    <select name="type" required style="width: 100%; padding: 0.35rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                        <option value="billing" {{ old('type', $editingTerm->type ?? '') == 'billing' ? 'selected' : '' }}>Billing</option>
                        <option value="quotation" {{ old('type', $editingTerm->type ?? '') == 'quotation' ? 'selected' : '' }}>Quotation</option>
                    </select>
                </div>
                <div class="flex-fill">
                    <label style="font-size: 0.75rem; margin-bottom: 0.2rem; display: block; color: #64748b; font-weight: 600;">Terms and Condition *</label>
                    <input type="text" name="content" value="{{ old('content', $editingTerm->content ?? '') }}" placeholder="Enter terms and condition" required style="width: 100%; padding: 0.35rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                </div>
                <div style="flex: 0 0 130px;">
                    <label style="font-size: 0.75rem; margin-bottom: 0.2rem; display: block; color: #64748b;">Default</label>
                    <label style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #334155;">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default', (int) ($editingTerm->is_default ?? 0)) ? 'checked' : '' }}>
                        Set as default
                    </label>
                </div>
                <div style="display: flex; gap: 0.4rem;">
                    <button type="submit" class="primary-button" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">{{ $editingTerm ? 'Update' : 'Add' }}</button>
                    @if($editingTerm)
                        <a href="{{ route('settings.index') }}#terms-conditions" style="padding: 0.4rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #64748b; font-size: 0.85rem; display: inline-block;">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="row">
            {{-- Billing Terms List --}}
            <div class="col-6" >
                <h6>Billing T&C</h6>
                <table class="data-table small-text">
                    <thead>
                        <tr>
                            <th>Seq</th>
                            <th>Terms and Condition</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($billingTerms as $index => $term)
                            <tr>
                                <td style="color: #64748b; text-align: center; padding: 0.4rem;">
                                    <form method="POST" action="{{ route('terms-conditions.update-sequence', $term) }}" style="display: inline-block; margin: 0;">
                                        @csrf @method('PATCH')
                                        <select name="sequence" onchange="this.form.submit()"
                                                style="width: 50px; padding: 0.2rem 0.3rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; text-align: center;">
                                            @for($i = 1; $i <= $billingTerms->count(); $i++)
                                                <option value="{{ $i }}" {{ ($term->sequence ?? ($index + 1)) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </form>
                                </td>
                                <td style="padding: 0.4rem;">{{ $term->content }}</td>
                                <td style="padding: 0.4rem;">
                                    @if($term->is_default)
                                        <span style="background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Default</span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.75rem;">-</span>
                                    @endif
                                </td>
                                <td style="padding: 0.4rem;">
                                    @if($term->is_active)
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Active</span>
                                    @else
                                        <span style="background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right; padding: 0.4rem;">
                                    <div class="table-actions">
                                        <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id)]) }}#terms-conditions" class="icon-action-btn edit" title="Edit"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('terms-conditions.destroy', $term) }}" onsubmit="return confirm('Delete this term?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">No billing T&C added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Quotation Terms List --}}
            <div class="col-6 border-left">
                <h6>Quotation T&C</h6>
                <table class="data-table small-text">
                    <thead>
                        <tr>
                            <th>Seq</th>
                            <th>Terms and Condition</th>
                            <th>Default</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quotationTerms as $index => $term)
                            <tr>
                                <td style="color: #64748b; text-align: center; padding: 0.4rem;">
                                    <form method="POST" action="{{ route('terms-conditions.update-sequence', $term) }}">
                                        @csrf @method('PATCH')
                                        <select name="sequence" onchange="this.form.submit()"
                                                style="width: 50px; padding: 0.2rem 0.3rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; text-align: center;">
                                            @for($i = 1; $i <= $quotationTerms->count(); $i++)
                                                <option value="{{ $i }}" {{ ($term->sequence ?? ($index + 1)) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </form>
                                </td>
                                <td style="padding: 0.4rem;">{{ $term->content }}</td>
                                <td style="padding: 0.4rem;">
                                    @if($term->is_default)
                                        <span style="background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Default</span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.75rem;">-</span>
                                    @endif
                                </td>
                                <td style="padding: 0.4rem;">
                                    @if($term->is_active)
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Active</span>
                                    @else
                                        <span style="background: #f1f5f9; color: #64748b; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem;">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right; padding: 0.4rem;">
                                    <div class="table-actions">
                                        <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id)]) }}#terms-conditions" class="icon-action-btn edit" title="Edit"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('terms-conditions.destroy', $term) }}" onsubmit="return confirm('Delete this term?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">No quotation T&C added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- TAXES TAB -->
<div id="taxes" class="tab-content">
    <section class="panel-card panel-card panel-card-compact">
        <div class="settings-section-head">
            <div class="settings-section-icon"><i class="fas fa-percent"></i></div>
            <div>
                <h5 class="settings-section-title">Tax Management</h5>
                <p class="settings-section-subtitle">Manage tax rates for invoices and quotations</p>
            </div>
        </div>

        {{-- Tax Form (add / edit inline) --}}
        <div id="tax-form-card" class="tax-form-card">
            <h6 id="tax-form-title" class="tax-form-title">Add New Tax</h6>
            <form method="POST" id="tax-form" action="{{ route('taxes.store') }}" class="tax-form-grid">
                @csrf
                <div>
                    <label class="tax-form-label">Rate (%) *</label>
                    <input type="number" name="rate" id="tax-rate-input" value="{{ old('rate') }}" placeholder="e.g., 18" step="0.01" min="0" max="100" required class="tax-form-input">
                </div>
                <div>
                    <label class="tax-form-label">Type *</label>
                    <select name="type" id="tax-type-select" required class="tax-form-input">
                        @foreach(['GST' => 'GST', 'VAT' => 'VAT'] as $val => $label)
                            <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="tax-form-actions">
                    <button type="submit" id="tax-form-btn" class="primary-button tax-form-btn">Add Tax</button>
                    <button type="button" id="tax-form-cancel" class="tax-form-cancel hidden" onclick="cancelEditTax()">Cancel</button>
                </div>
            </form>
        </div>

        {{-- Taxes Grouped by Type --}}
        @php
            $taxTypes = ['GST', 'VAT', 'Sales Tax', 'Service Tax', 'Other'];
            $groupedTaxes = $taxes->groupBy('type');
        @endphp
        @foreach($taxTypes as $taxType)
            @php
                $group = $groupedTaxes->get($taxType, collect());
            @endphp
            @if($group->count() > 0)
            <div class="field-gap">
                <div class="tax-group-head">
                    <h6 class="tax-group-title">
                        <span class="tax-group-pill">{{ $taxType }}</span>
                        — <span class="tax-group-count">{{ $group->count() }} tax{{ $group->count() > 1 ? 'es' : '' }}</span>
                    </h6>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="tax-col-idx">#</th>
                            <th class="tax-col-rate">Rate</th>
                            <th class="tax-col-status">Status</th>
                            <th class="tax-col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group as $index => $tax)
                            <tr>
                                <td class="tax-cell-idx">{{ $index + 1 }}</td>
                                <td class="tax-cell-rate">{{ $tax->rate }}%</td>
                                <td class="tax-cell-status">
                                    @if($tax->is_active)
                                        <span class="tax-status-pill is-active">Active</span>
                                    @else
                                        <span class="tax-status-pill is-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td class="tax-cell-action">
                                    <div class="table-actions">
                                        {{-- <form method="POST" action="{{ route('taxes.toggle', $tax) }}" class="inline-delete">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action-btn" title="Toggle Status"><i class="fas fa-toggle-on"></i></button>
                                        </form> --}}
                                        <a href="javascript:void(0)" class="icon-action-btn edit" title="Edit"
                                           data-id="{{ $tax->taxid }}"
                                           data-rate="{{ $tax->rate }}"
                                           data-type="{{ $tax->type }}"
                                           data-name="{{ $tax->tax_name }}"
                                           onclick="startEditTax(this)"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('taxes.destroy', $tax) }}" class="inline-delete" onsubmit="return confirm('Delete this tax?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="icon-action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endforeach
        @if($taxes->isEmpty())
            <p class="no-records-cell">No taxes configured yet.</p>
        @endif
    </section>
</div>

{{-- Fixed Tax Rate Modal --}}
<div class="modal fade" id="fixedTaxRateModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog modal-sm modal-dialog-centered modal-420">
        <div class="modal-content rounded-panel">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title modal-title service-modal-title">
                    <i class="fas fa-receipt icon-spaced text-muted"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body service-modal-body">
                <form method="POST" action="{{ route('account.fixed-tax.update') }}" id="fixed-tax-form">
                    @csrf
                    <div class="field-gap">
                        <label class="label-compact">Rate (%)</label>
                        <input type="number" name="fixed_tax_rate" placeholder="18" step="0.01" min="0" max="100" value="{{ old('fixed_tax_rate', $account->fixed_tax_rate ?? 0) }}" required
                               class="service-input-full">
                    </div>
                    <div class="field-gap">
                        <label class="label-compact">Type</label>
                        <select name="fixed_tax_type" required
                                class="service-input-full">
                            @foreach(['GST'=>'GST','VAT'=>'VAT'] as $v=>$l)
                                <option value="{{ $v }}" {{ old('fixed_tax_type', $account->fixed_tax_type ?? 'GST') == $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button type="submit" class="primary-button small">Add Tax</button>
                        <button type="button" class="text-link small" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function startEditTax(el){
    var form = document.getElementById('tax-form');
    form.action = '{{ url('settings/taxes') }}/' + el.dataset.id;
    var existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.remove();
    var input = document.createElement('input');
    input.type = 'hidden'; input.name = '_method'; input.value = 'PATCH';
    form.prepend(input);
    document.getElementById('tax-rate-input').value = el.dataset.rate;
    document.getElementById('tax-type-select').value = el.dataset.type;
    document.getElementById('tax-form-title').textContent = 'Edit Tax (' + el.dataset.id + ')';
    document.getElementById('tax-form-btn').textContent = 'Update';
    document.getElementById('tax-form-cancel').classList.remove('hidden');
    document.getElementById('tax-form-card').classList.add('is-editing');
    form.scrollIntoView({behavior:'smooth', block:'center'});
}
function cancelEditTax(){
    var form = document.getElementById('tax-form');
    form.action = '{{ route('taxes.store') }}';
    var existingMethod = form.querySelector('input[name="_method"]');
    if (existingMethod) existingMethod.remove();
    document.getElementById('tax-rate-input').value = '';
    document.getElementById('tax-type-select').selectedIndex = 0;
    document.getElementById('tax-form-title').textContent = 'Add New Tax';
    document.getElementById('tax-form-btn').textContent = 'Add Tax';
    document.getElementById('tax-form-cancel').classList.add('hidden');
    document.getElementById('tax-form-card').classList.remove('is-editing');
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.tab-button');
    const tabs = document.querySelectorAll('.tab-content');

    function activateTab(tabId) {
        if (!tabId) return;

        // Remove active class from all
        buttons.forEach(b => b.classList.remove('active'));
        tabs.forEach(t => t.classList.remove('active'));

        // Add to target
        const btn = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
        const tab = document.getElementById(tabId);

        if (btn && tab) {
            btn.classList.add('active');
            tab.classList.add('active');
            // Update URL hash without jumping
            window.history.replaceState(null, null, `#${tabId}`);
        }
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            activateTab(button.dataset.tab);
        });
    });

    // Financial Year Sync
    const fyStart = document.getElementById('fy_year_start');
    const fyEnd = document.getElementById('fy_year_end');

    if (fyStart && fyEnd) {
        fyStart.addEventListener('change', function() {
            const selectedStart = parseInt(this.value);
            fyEnd.value = selectedStart + 1;

            // Limit end year options visibility for clarity
            Array.from(fyEnd.options).forEach(opt => {
                const optVal = parseInt(opt.value);
                if (optVal === selectedStart + 1) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        });

        // Initialize display on load
        fyStart.dispatchEvent(new Event('change'));
    }

    // Handle initial load from Hash
    const hash = window.location.hash.replace('#', '');
    const urlParams = new URLSearchParams(window.location.search);
    const encodedE = urlParams.get('e');
    const decodedE = encodedE ? atob(encodedE) : null;

    if (hash) {
        activateTab(hash);
    } else if (decodedE) {
        if (decodedE.startsWith('TC')) activateTab('terms-conditions');
        else if (decodedE.startsWith('SET')) activateTab('config');
        else if (decodedE.startsWith('ABD')) activateTab('billing-details');
        else if (decodedE.startsWith('AQD')) activateTab('quotation-details');
        else activateTab('personal');
    } else {
        // Default to personal if no hash
        activateTab('personal');
    }

    // Serial mode toggle handler - OLD (kept for reference if still needed, but likely replaced)
    function handleSerialModeChange(radio) {
        const form = radio.closest('form');
        const isQuotation = form.action.includes('quotation');
        const prefix = isQuotation ? 'quotation' : 'billing';

        const autoGenDiv = document.getElementById(`${prefix}-auto-generate-options`);
        const autoIncDiv = document.getElementById(`${prefix}-auto-increment-options`);

        if (radio.value === 'auto_generate') {
            if (autoGenDiv) autoGenDiv.style.display = 'block';
            if (autoIncDiv) autoIncDiv.style.display = 'none';
        } else if (radio.value === 'auto_increment') {
            if (autoGenDiv) autoGenDiv.style.display = 'none';
            if (autoIncDiv) autoIncDiv.style.display = 'block';
        }
    }

    // NEW Serial Configuration Logic
    function updateSerialPreview(target) {
        const form = document.getElementById(`${target}-serial-form`);
        if (!form) return;

        const previewDiv = document.getElementById(`${target}-preview`);
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const date = String(now.getDate()).padStart(2, '0');

        function getSeparator(name) {
            const field = form.querySelector(`[name="${name}"]`);
            return field && field.value !== 'none' ? field.value : '';
        }

        function getPartValue(part) {
            const type = form.querySelector(`[name="${part}_type"]`).value;
            const valInputGroup = form.querySelector(`[name="${part}_value"]`).closest('.input-group-val');
            const valLabel = valInputGroup.querySelector('.val-label');
            const lengthInputGroup = form.querySelector(`[name="${part}_length"]`).closest('.input-group-len');

            const valField = form.querySelector(`[name="${part}_value"]`);
            const lengthField = form.querySelector(`[name="${part}_length"]`);

            // Visibility & Label Logic
            if (type === 'manual text') {
                valInputGroup.style.display = 'block';
                valLabel.innerText = 'Enter value';
                lengthInputGroup.style.display = 'none';
            } else if (type === 'auto generate') {
                valInputGroup.style.display = 'none';
                lengthInputGroup.style.display = 'block';
            } else if (type === 'auto increment') {
                valInputGroup.style.display = 'block';
                valLabel.innerText = 'Start From';
                lengthInputGroup.style.display = 'none';
            } else {
                valInputGroup.style.display = 'none';
                lengthInputGroup.style.display = 'none';
            }

            // Preview Logic
            switch (type) {
                case 'manual text':
                    return valField.value || (part === 'prefix' ? (target == 'billing' ? 'INV' : 'QUO') : (part === 'suffix' ? '2026' : '1001'));
                case 'date':
                    return `${year}-${month}-${date}`;
                case 'year':
                    return `${year}`;
                case 'month-year':
                    return `${month}-${year}`;
                case 'date-month':
                    return `${date}-${month}`;
                case 'auto increment':
                    return valField.value || '1';
                case 'auto generate':
                    const genLen = parseInt(lengthField.value) || 4;
                    let result = '';
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    for (let i = 0; i < genLen; i++) {
                        result += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return result;
                default:
                    return '';
            }
        }

        const prefix = getPartValue('prefix');
        const number = getPartValue('number');
        const suffix = getPartValue('suffix');

        const prefixSep = prefix ? getSeparator('prefix_separator') : '';
        const numberSep = suffix ? getSeparator('number_separator') : '';

        previewDiv.innerText = prefix + prefixSep + number + numberSep + suffix;
    }

// Attach listeners to new serial fields
    document.querySelectorAll('.serial-type-select, input[name$="_value"], input[name$="_length"], input[name$="_start"], select[name$="_separator"]').forEach(el => {
        el.addEventListener('input', function() {
            const form = this.closest('form');
            const target = form.id ? form.id.split('-')[0] : null;
            if (target && (target === 'proforma' || target === 'billing' || target === 'quotation')) {
                updateSerialPreview(target);
            }
        });
    });

    function updateFYPrefixPreview() {
        const form = document.getElementById('fy-prefix-form');
        if (!form) return;

        const previewDiv = document.getElementById('fy-prefix-preview');
        const type = form.querySelector('[name="fy_prefix_type"]').value;
        const prefixSep = form.querySelector('[name="fy_prefix_sep"]').value;
        const prefixValue = form.querySelector('[name="fy_prefix_value"]').value || 'FY';
        const numberSep = form.querySelector('[name="fy_number_sep"]').value;
        const numberValue = '001'; // placeholder
        const year = new Date().getFullYear();

        let previewText = prefixValue;
        if (prefixSep !== 'none') previewText += prefixSep;
        previewText += numberValue;
        if (numberSep !== 'none') previewText += numberSep;
        previewText += year;

        previewDiv.innerText = previewText;

        // Update label based on type
        const valLabel = document.getElementById('fy-val-label');
        if (type === 'value/number') {
            valLabel.innerText = 'Enter value';
        } else {
            valLabel.innerText = 'Fixed Value';
        }
    }



    // Initialize previews
    updateSerialPreview('proforma');
    updateSerialPreview('billing');
    updateSerialPreview('quotation');

    // Initialize field visibility on page load
    ['proforma', 'billing', 'quotation'].forEach(target => {
        ['prefix', 'number', 'suffix'].forEach(part => {
            const form = document.getElementById(`${target}-serial-form`);
            if (!form) return;

            const typeSelect = form.querySelector(`[name="${part}_type"]`);
            if (typeSelect) {
                // Trigger change event to set initial visibility
                typeSelect.dispatchEvent(new Event('input'));
            }
        });
    });

    // Attach event listeners to old serial mode radios (if they still exist)
    document.querySelectorAll('input[name="serial_mode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            handleSerialModeChange(this);
        });
    });

    // Initialize on page load - trigger for billing
    setTimeout(() => {
        const billingRadio = document.querySelector('#billing-details input[name="serial_mode"]:checked');
        if (billingRadio) {
            handleSerialModeChange(billingRadio);
        }

        // Initialize on page load - trigger for quotation
        const quotationRadio = document.querySelector('#quotation-details input[name="serial_mode"]:checked');
        if (quotationRadio) {
            handleSerialModeChange(quotationRadio);
        }
    }, 100);
});

// Signature preview function
function previewSignature(input, previewId) {
    const previewContainer = document.getElementById(previewId);
    const previewImg = previewContainer.querySelector('img');

    if (input.files && input.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewContainer.style.display = 'block';
        };

        reader.readAsDataURL(input.files[0]);
    }
}

function previewLogo(input) {
    const preview = document.getElementById('logo-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview.tagName === 'DIV') {
                const img = document.createElement('img');
                img.id = 'logo-preview';
                img.src = e.target.result;
                img.className = 'logo-preview-img';
                preview.parentNode.replaceChild(img, preview);
            } else {
                preview.src = e.target.result;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Toggle fixed tax rate visibility based on multi-taxation toggle
document.addEventListener('DOMContentLoaded', function() {
    const multiTaxationCheckbox = document.querySelector('input[name="allow_multi_taxation"]');
    const fixedTaxSection = document.getElementById('fixed-tax-section');
    const openFixedTaxBtn = document.getElementById('open-fixed-tax-modal');

    if (multiTaxationCheckbox && fixedTaxSection) {
        multiTaxationCheckbox.addEventListener('change', function() {
            const isEnabled = this.checked;

            if (isEnabled) {
                // Multi-taxation enabled - hide fixed tax field
                fixedTaxSection.style.display = 'none';
            } else {
                // Multi-taxation disabled - show fixed tax field
                fixedTaxSection.style.display = 'block';
            }
        });
    }

    // Open fixed tax rate modal using Bootstrap
    if (openFixedTaxBtn) {
        const fixedTaxModalEl = document.getElementById('fixedTaxRateModal');
        if (fixedTaxModalEl) {
            const fixedTaxModal = new bootstrap.Modal(fixedTaxModalEl);
            openFixedTaxBtn.addEventListener('click', function() {
                fixedTaxModal.show();
            });
        }
    }
});
</script>

@endsection
