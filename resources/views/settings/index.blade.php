@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div></div>
</section>

<!-- Tabs Wrapper -->
<div style="padding: 10px 0;">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="personal">Business Info</button>
        <button class="tab-button" data-tab="financial-year">Financial Year</button>
<button class="tab-button" data-tab="config">Configuration Keys</button>
        <button class="tab-button" data-tab="billing-details">Billing Details</button>
        <button class="tab-button" data-tab="quotation-details">Quotation Details</button>
    </div>
</div>

<style>
/* Tabs Container */
.tabs-nav {
    display: inline-flex; /* KEY FIX */
    gap: 6px;
    padding: 6px;
    background: #f1f5f9;
    border-radius: 10px;
    width: fit-content; /* prevents full width */
}

/* Tab Buttons */
.tab-button {
    flex: 0 1 auto;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    white-space: nowrap;
}

/* Hover */
.tab-button:hover:not(.active) {
    background: #e2e8f0;
}

/* Active Tab */
.tab-button.active {
    background: #3b82f6;
    color: white;
}

/* Tab Content */
.tab-content {
    display: none;
    margin-top: 10px;
}

.tab-content.active {
    display: block;
}

/* Card */
.panel-card {
    padding: 1.25rem;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

/* Form */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-grid input {
    width: 100%;
    padding: 0.45rem 0.6rem;
}

/* Labels */
label {
    display: block;
    margin-bottom: 4px;
    font-size: 0.85rem;
}

.required {
    font-weight: 600;
}

/* Actions */
.form-actions {
    grid-column: span 2;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.primary-button {
    padding: 0.6rem 1.2rem;
    background: #3b82f6;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 6px;
}

/* Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 0.6rem;
    border-bottom: 1px solid #e2e8f0;
}

.text-link {
    color: #3b82f6;
    cursor: pointer;
}
</style>

<!-- PERSONAL TAB -->
<div id="personal" class="tab-content active">
    <section class="panel-card">
        <p style="margin-bottom: 1rem;">Your public and billing information.</p>

        <form method="POST" action="{{ route('account.update') }}" class="form-grid">
            @csrf
            @method('PUT')

            <div>
                <label class="required">Business Name *</label>
                <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}" required>
            </div>

            <div>
                <label>Legal Entity Name</label>
                <input type="text" name="legal_name" value="{{ old('legal_name', $account->legal_name ?? '') }}">
            </div>

            <div>
                <label class="required">Email *</label>
                <input type="email" name="email" value="{{ old('email', $account->email ?? '') }}" required>
            </div>

            <div>
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $account->phone ?? '') }}">
            </div>

            <div>
                <label>Currency</label>
                <select name="currency_code" style="width: 100%; padding: 0.45rem 0.6rem;">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->iso }}" {{ old('currency_code', $account->currency_code ?? 'INR') == $currency->iso ? 'selected' : '' }}>
                            {{ $currency->iso }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Timezone</label>
                <input type="text" name="timezone" value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}">
            </div>

            <div>
                <label>Address</label>
                <input type="text" name="address_line_1" value="{{ old('address_line_1', $account->address_line_1 ?? '') }}">
            </div>

            <div>
                <label>Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $account->country ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select Country</option>
                </select>
            </div>

            <div>
                <label>State</label>
                <select name="state" class="state-select" data-selected="{{ old('state', $account->state ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select State</option>
                </select>
            </div>

            <div>
                <label>City</label>
                <select name="city" class="city-select" data-selected="{{ old('city', $account->city ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select City</option>
                </select>
            </div>

            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $account->postal_code ?? '') }}">
            </div>
            <div>
                <label>FY Start (Day & Month)</label>
                <div style="display: flex; gap: 0.5rem;">
                    @php
                        $currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
                        $parts = explode('-', $currentFy);
                        $curMonth = $parts[0] ?? '04';
                        $curDay = $parts[1] ?? '01';
                    @endphp
                    <select name="fy_day" style="width: 80px; padding: 0.45rem 0.6rem;">
                        @for ($i = 1; $i <= 31; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $curDay == sprintf('%02d', $i) ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <select name="fy_month" style="flex: 1; padding: 0.45rem 0.6rem;">
                        @foreach(['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $mVal => $mName)
                            <option value="{{ $mVal }}" {{ $curMonth == $mVal ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            

            <div class="form-actions">
                <button type="submit" class="primary-button">Update Profile</button>
            </div>
        </form>
    </section>
</div>

<!-- FINANCIAL YEAR -->
<div id="financial-year" class="tab-content">
    <section class="panel-card">
        <p style="margin-bottom: 1rem;">Enter the start and end years for your financial year (e.g. 2024 - 2025).</p>

        @if ($errors->any())
            <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 1rem;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('financial-year.update') }}" style="display: flex; align-items: center; gap: 10px; max-width: 400px;">
            @csrf
            <input type="number" name="year_start" value="{{ date('Y') }}" required style="width: 100px; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <span style="font-size: 1.5rem; font-weight: bold;">-</span>
            <input type="number" name="year_end" value="{{ date('Y') + 1 }}" required style="width: 100px; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="submit" class="primary-button">Add</button>
        </form>

        <div style="margin-top: 2rem;">
            <h4>Financial Years</h4>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Financial Year</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($financialYears as $fy)
                        <tr>
                            <td>{{ $fy->financial_year }}</td>
                            <td>
                                @if($fy->default)
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem;">Default</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(!$fy->default)
                                    <form method="POST" action="{{ route('financial-year.default', $fy->fy_id) }}" style="display: inline;">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-link" style="background: none; border: none; padding: 0; font-size: 0.85rem;">Set as Default</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No financial years recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- CONFIG -->
<div id="config" class="tab-content">
    <section class="panel-card">
        <div style="margin-bottom: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h5 style="margin-bottom: 0.75rem;">
                {{ $editingSetting ? 'Edit Configuration Key' : 'Add New Configuration Key' }}
            </h5>
            
            <form method="POST" action="{{ $editingSetting ? route('settings.update', $editingSetting->settingid) : route('settings.store') }}" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.75rem; align-items: end;">
                @csrf
                @if($editingSetting)
                    @method('PUT')
                @endif
                
                <div>
                    <label style="font-size: 0.75rem; margin-bottom: 2px;">Key Name *</label>
                    <select id="config-key-select" name="key" required style="padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; width: 100%;">
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
                    <label style="font-size: 0.75rem; margin-bottom: 2px;">Value *</label>
                    <input type="text" name="value" value="{{ old('value', $editingSetting->setting_value ?? '') }}" placeholder="Enter value" required style="padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1;">
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="primary-button" style="padding: 0.45rem 1rem;">
                        {{ $editingSetting ? 'Update Key' : 'Add Key' }}
                    </button>
                    @if($editingSetting)
                        <a href="{{ route('settings.index') }}#config" class="text-link" style="padding: 0.45rem 0.5rem; text-decoration: none; border: 1px solid #cbd5e1; border-radius: 4px; background: white; font-size: 0.85rem; color: #64748b;">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
            <h4>System Settings</h4>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settings as $setting)
                    <tr>
                        <td><code>{{ $setting['key'] }}</code></td>
                        <td>{{ $setting['value'] }}</td>
                        <td>
                            <a href="{{ route('settings.index', ['edit' => $setting['record_id']]) }}#config" class="text-link">Edit</a>
                        </td>
                    </tr>
@empty
                    <tr>
                        <td colspan="3">No settings found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- BILLING DETAILS TAB -->
<div id="billing-details" class="tab-content">
    <section class="panel-card">
        <div style="margin-bottom: 1rem;">
            <h4>Billing Details</h4>
            <p>Add billing details for invoices.</p>
        </div>
        @if ($errors->any())
            <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 1rem;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('account.billing.update') }}" class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            @csrf
            @if(isset($editingBillingDetail))
                <input type="hidden" name="account_bdid" value="{{ $editingBillingDetail->account_bdid }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">
            <div style="grid-column: span 2; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h5 style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #1e293b;">Serial Number Configuration</h5>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Prefix</label>
                        <input type="text" name="prefix" value="{{ old('prefix', $editingBillingDetail->prefix ?? '') }}" placeholder="INV-" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Suffix</label>
                        <input type="text" name="suffix" value="{{ old('suffix', $editingBillingDetail->suffix ?? '') }}" placeholder="-2026" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Preview</label>
                        <div id="billing-preview" style="font-family: monospace; font-size: 0.9rem; color: #1e293b; padding: 0.4rem 0.5rem; background: white; border-radius: 4px; border: 1px solid #cbd5e1;">
                            {{ $editingBillingDetail->prefix ?? '' }}[NUMBER]{{ $editingBillingDetail->suffix ?? '' }}
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: auto 1fr; gap: 1rem; align-items: start;">
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block; font-weight: 600;">Mode</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label class="custom-radio">
                                <input type="radio" name="serial_mode" value="auto_generate" 
                                    {{ old('serial_mode', $editingBillingDetail->serial_mode ?? 'auto_generate') == 'auto_generate' ? 'checked' : '' }}
                                    class="billing-serial-mode-radio">
                                <span class="radio-label">Auto Generate</span>
                            </label>
                            <label class="custom-radio">
                                <input type="radio" name="serial_mode" value="auto_increment" 
                                    {{ old('serial_mode', $editingBillingDetail->serial_mode ?? 'auto_generate') == 'auto_increment' ? 'checked' : '' }}
                                    class="billing-serial-mode-radio">
                                <span class="radio-label">Auto Increment</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <div id="billing-auto-generate-options" style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block;">Alphanumeric Length</label>
                            <select name="alphanumeric_length" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                                <option value="4" {{ old('alphanumeric_length', $editingBillingDetail->alphanumeric_length ?? 4) == 4 ? 'selected' : '' }}>4 characters (A3F9)</option>
                                <option value="6" {{ old('alphanumeric_length', $editingBillingDetail->alphanumeric_length ?? 4) == 6 ? 'selected' : '' }}>6 characters (A3F9B2)</option>
                            </select>
                        </div>

                        <div id="billing-auto-increment-options" style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem; align-items: end;">
                                <div>
                                    <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block;">Start From</label>
                                    <input type="number" name="auto_increment_start" value="{{ old('auto_increment_start', $editingBillingDetail->auto_increment_start ?? 1) }}" min="1" max="99999" placeholder="1001" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                                </div>
                                <div>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="reset_on_fy" value="1" {{ old('reset_on_fy', $editingBillingDetail->reset_on_fy ?? false) ? 'checked' : '' }}>
                                        <span class="checkbox-label">Reset on FY</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="required">Billing Name</label>
                <input type="text" name="billing_name" value="{{ old('billing_name', $editingBillingDetail->billing_name ?? '') }}" required>
            </div>
            <div style="grid-column: span 2;">
                <label>Address</label>
                <textarea name="address" rows="3" style="width: 100%; padding: 0.45rem 0.6rem;">{{ old('address', $editingBillingDetail->address ?? '') }}</textarea>
            </div>
            <div>
                <label>Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $editingBillingDetail->country ?? 'India') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label>State</label>
                <select name="state" class="state-select" data-selected="{{ old('state', $editingBillingDetail->state ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select State</option>
                </select>
            </div>
            <div>
                <label>City</label>
                <select name="city" class="city-select" data-selected="{{ old('city', $editingBillingDetail->city ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select City</option>
                </select>
            </div>
            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $editingBillingDetail->postal_code ?? '') }}">
            </div>
            <div>
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingBillingDetail->gstin ?? '') }}">
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingBillingDetail->tin ?? '') }}">
            </div>
            <div style="grid-column: span 2;">
<label>Terms &amp; Conditions</label>
                <div id="billing-terms-editor" style="border: 1px solid #cbd5e1; border-radius: 6px; min-height: 100px; padding: 0.5rem;">{!! old('terms_conditions', $editingBillingDetail->terms_conditions ?? '') !!}</div>
                <textarea name="terms_conditions" id="billing-terms-hidden" style="display: none;">{{ old('terms_conditions', $editingBillingDetail->terms_conditions ?? '') }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-button">Save Billing Detail</button>
                @if(isset($editingBillingDetail) && request('edit_bd'))
                    <a href="{{ route('settings.index') }}#billing-details" class="text-link" style="margin-left: 1rem;">Cancel</a>
                @endif
            </div>
        </form>

<!-- Single billing detail form (no list) -->
    </section>
</div>

<!-- QUOTATION DETAILS TAB -->
<div id="quotation-details" class="tab-content">
    <section class="panel-card">
        <div style="margin-bottom: 1rem;">
            <h4>Quotation Details</h4>
            <p>Add quotation details for estimates.</p>
        </div>
        @if ($errors->any())
            <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 1rem;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('account.quotation.update') }}" class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            @csrf
            @if(isset($editingQuotationDetail))
                <input type="hidden" name="account_qdid" value="{{ $editingQuotationDetail->account_qdid }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">
            <div style="grid-column: span 2; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h5 style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #1e293b;">Serial Number Configuration</h5>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Prefix</label>
                        <input type="text" name="prefix" value="{{ old('prefix', $editingQuotationDetail->prefix ?? '') }}" placeholder="QUO-" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Suffix</label>
                        <input type="text" name="suffix" value="{{ old('suffix', $editingQuotationDetail->suffix ?? '') }}" placeholder="-2026" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.25rem;">Preview</label>
                        <div id="quotation-preview" style="font-family: monospace; font-size: 0.9rem; color: #1e293b; padding: 0.4rem 0.5rem; background: white; border-radius: 4px; border: 1px solid #cbd5e1;">
                            {{ $editingQuotationDetail->prefix ?? '' }}[NUMBER]{{ $editingQuotationDetail->suffix ?? '' }}
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: auto 1fr; gap: 1rem; align-items: start;">
                    <div>
                        <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block; font-weight: 600;">Mode</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <label class="custom-radio">
                                <input type="radio" name="serial_mode" value="auto_generate" 
                                    {{ old('serial_mode', $editingQuotationDetail->serial_mode ?? 'auto_generate') == 'auto_generate' ? 'checked' : '' }}
                                    class="quotation-serial-mode-radio">
                                <span class="radio-label">Auto Generate</span>
                            </label>
                            <label class="custom-radio">
                                <input type="radio" name="serial_mode" value="auto_increment" 
                                    {{ old('serial_mode', $editingQuotationDetail->serial_mode ?? 'auto_generate') == 'auto_increment' ? 'checked' : '' }}
                                    class="quotation-serial-mode-radio">
                                <span class="radio-label">Auto Increment</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <div id="quotation-auto-generate-options" style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block;">Alphanumeric Length</label>
                            <select name="alphanumeric_length" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                                <option value="4" {{ old('alphanumeric_length', $editingQuotationDetail->alphanumeric_length ?? 4) == 4 ? 'selected' : '' }}>4 characters (A3F9)</option>
                                <option value="6" {{ old('alphanumeric_length', $editingQuotationDetail->alphanumeric_length ?? 4) == 6 ? 'selected' : '' }}>6 characters (A3F9B2)</option>
                            </select>
                        </div>

                        <div id="quotation-auto-increment-options" style="padding: 0.75rem; background: white; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <div style="display: grid; grid-template-columns: 1fr auto; gap: 0.75rem; align-items: end;">
                                <div>
                                    <label style="font-size: 0.8rem; margin-bottom: 0.5rem; display: block;">Start From</label>
                                    <input type="number" name="auto_increment_start" value="{{ old('auto_increment_start', $editingQuotationDetail->auto_increment_start ?? 1) }}" min="1" max="99999" placeholder="1001" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">
                                </div>
                                <div>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="reset_on_fy" value="1" {{ old('reset_on_fy', $editingQuotationDetail->reset_on_fy ?? false) ? 'checked' : '' }}>
                                        <span class="checkbox-label">Reset on FY</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <label class="required">Quotation Name</label>
                <input type="text" name="quotation_name" value="{{ old('quotation_name', $editingQuotationDetail->quotation_name ?? '') }}" required>
            </div>
            <div style="grid-column: span 2;">
                <label>Address</label>
                <textarea name="address" rows="3" style="width: 100%; padding: 0.45rem 0.6rem;">{{ old('address', $editingQuotationDetail->address ?? '') }}</textarea>
            </div>
            <div>
                <label>Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $editingQuotationDetail->country ?? 'India') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label>State</label>
                <select name="state" class="state-select" data-selected="{{ old('state', $editingQuotationDetail->state ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select State</option>
                </select>
            </div>
            <div>
                <label>City</label>
                <select name="city" class="city-select" data-selected="{{ old('city', $editingQuotationDetail->city ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select City</option>
                </select>
            </div>
            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $editingQuotationDetail->postal_code ?? '') }}">
            </div>
            <div>
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingQuotationDetail->gstin ?? '') }}">
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingQuotationDetail->tin ?? '') }}">
            </div>
            <div style="grid-column: span 2;">
<label>Terms &amp; Conditions</label>
                <div id="quotation-terms-editor" style="border: 1px solid #cbd5e1; border-radius: 6px; min-height: 100px; padding: 0.5rem;">{!! old('terms_conditions', $editingQuotationDetail->terms_conditions ?? '') !!}</div>
                <textarea name="terms_conditions" id="quotation-terms-hidden" style="display: none;">{{ old('terms_conditions', $editingQuotationDetail->terms_conditions ?? '') }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-button">Save Quotation Detail</button>
                @if(isset($editingQuotationDetail) && request('edit_qd'))
                    <a href="{{ route('settings.index') }}#quotation-details" class="text-link" style="margin-left: 1rem;">Cancel</a>
                @endif
            </div>
        </form>

<!-- Single quotation detail form (no list) -->
    </section>
</div>

<!-- JS -->
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

    // Handle initial load from Hash
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        activateTab(hash);
    } else {
        // Default to personal if no hash
        activateTab('personal');
    }

    // Serial mode toggle handler
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

    // Attach event listeners to serial mode radios
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

function initTinyMCE() {
  tinymce.init({
    selector: '#billing-terms-editor, #quotation-terms-editor',
    license_key: 'gpl',
height: 300,
    menubar: false,
    plugins: 'lists link image table',
    toolbar: 'undo redo | link | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist',
    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4',
    extended_valid_elements: 'a[href|target|style]',
    allow_script_urls: true,
    setup: function(editor){
      editor.on('change', function(){
        const hiddenId = editor.id === 'billing-terms-editor' ? 'billing-terms-hidden' : 'quotation-terms-hidden';
        document.getElementById(hiddenId).value = editor.getContent();
      });
    },
    content_style: `
      .mce-content-body[data-mce-placeholder]:not(.mce-visualblocks)::before {
        color: #000;
      }`
  });

  // Sync before form submission
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
      if (typeof tinymce !== 'undefined') {
        const billingEditor = tinymce.get('billing-terms-editor');
        if (billingEditor) document.getElementById('billing-terms-hidden').value = billingEditor.getContent();
        
        const quotationEditor = tinymce.get('quotation-terms-editor');
        if (quotationEditor) document.getElementById('quotation-terms-hidden').value = quotationEditor.getContent();
      }
    });
  });
}
initTinyMCE();
</script>

@endsection