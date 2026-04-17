@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div></div>
</section>

<!-- Tabs Wrapper -->
<div style="padding: 6px 0;">
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

<style>
/* Tabs Container */
.tabs-nav {
    display: inline-flex; /* KEY FIX */
    gap: 4px;
    padding: 4px;
    background: #f1f5f9;
    border-radius: 8px;
    width: fit-content; /* prevents full width */
}

/* Tab Buttons */
.tab-button {
    flex: 0 1 auto;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    border: none;
    background: transparent;
    color: #475569;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.82rem;
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
    margin-top: 6px;
}

.tab-content.active {
    display: block;
}

/* Card */
.panel-card {
    padding: 0.85rem;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

/* Form */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-grid input {
    width: 100%;
    padding: 0.3rem 0.5rem;
    font-size: 0.82rem;
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
    padding: 0.35rem 0.9rem;
    font-size: 0.82rem;
    background: #3b82f6;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
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

/* Toggle Switch Styling */
.toggle-slider {
    transition: background-color 0.3s;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: transform 0.3s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

input:checked + .toggle-slider::before {
    transform: translateX(24px);
}

input:checked + .toggle-slider {
    background-color: #059669 !important;
}
</style>

<!-- PERSONAL TAB -->
<div id="personal" class="tab-content active">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-building"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Business Information</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Manage your public profile and billing details</p>
            </div>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong style="color: #991b1b; font-size: 0.9rem;">Please fix the following errors:</strong>
                </div>
                <ul style="margin: 0; padding-left: 1.5rem; color: #b91c1c; font-size: 0.85rem;">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom: 0.15rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
            @csrf
            @method('PUT')

            <!-- Logo Upload -->
            <div style="grid-column: span 1;">
                <label style="font-size: 0.8rem;">Company Logo</label>
                <div style="border: 2px dashed #e2e8f0; border-radius: 8px; padding: 0.75rem; text-align: center; background: #f8fafc;">
                    @if(!empty($account->logo_path))
                        <img src="{{ asset($account->logo_path) }}" alt="Logo" id="logo-preview" style="max-width: 120px; max-height: 80px; border-radius: 6px; margin-bottom: 0.5rem; object-fit: contain;">
                    @else
                        <div id="logo-preview" style="width: 120px; height: 80px; margin: 0 auto 0.5rem; display: flex; align-items: center; justify-content: center; border-radius: 6px; background: #e2e8f0; color: #94a3b8; font-size: 1.5rem;"><i class="fas fa-image"></i></div>
                    @endif
                    <input type="file" name="logo" id="logo-upload" accept="image/*" onchange="previewLogo(this)" style="font-size: 0.75rem; width: 100%;">
                    <small style="color: #94a3b8; font-size: 0.7rem;">Square recommended. 5MB max.</small>
                </div>
            </div>

            <div>
                <label style="font-size: 0.8rem;" class="required">Business Name *</label>
                <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}" required style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Legal Entity Name</label>
                <input type="text" name="legal_name" value="{{ old('legal_name', $account->legal_name ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Website</label>
                <input type="text" name="website" value="{{ old('website', $account->website ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;" class="required">Email *</label>
                <input type="email" name="email" value="{{ old('email', $account->email ?? '') }}" required style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $account->phone ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Currency</label>
                <select name="currency_code" style="font-size: 0.85rem; padding: 0.45rem 0.6rem; width: 100%;">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->iso }}" {{ old('currency_code', $account->currency_code ?? 'INR') == $currency->iso ? 'selected' : '' }}>
                            {{ $currency->iso }} - {{ $currency->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="font-size: 0.8rem;">Timezone</label>
                <input type="text" name="timezone" value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Address</label>
                <input type="text" name="address_line_1" value="{{ old('address_line_1', $account->address_line_1 ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>

            <div>
                <label style="font-size: 0.8rem;">Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $account->country ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem; width: 100%;">
                    <option value="">Select Country</option>
                </select>
            </div>

            <div>
                <label style="font-size: 0.8rem;">State *</label>
                <select name="state" required class="state-select" data-selected="{{ old('state', $account->state ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem; width: 100%;">
                    <option value="">Select State</option>
                </select>
                @error('state') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div>
                <label style="font-size: 0.8rem;">City</label>
                <select name="city" class="city-select" data-selected="{{ old('city', $account->city ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem; width: 100%;">
                    <option value="">Select City</option>
                </select>
            </div>

            <div>
                <label style="font-size: 0.8rem;">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $account->postal_code ?? '') }}" style="font-size: 0.85rem; padding: 0.45rem 0.6rem;">
            </div>
            <div>
                <label style="font-size: 0.8rem;">FY Start (Day & Month)</label>
                <div style="display: flex; gap: 0.5rem;">
                    @php
                        $currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
                        $parts = explode('-', $currentFy);
                        $curMonth = $parts[0] ?? '04';
                        $curDay = $parts[1] ?? '01';
                    @endphp
                    <select name="fy_day" style="width: 80px; padding: 0.45rem 0.6rem; font-size: 0.85rem;">
                        @for ($i = 1; $i <= 31; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $curDay == sprintf('%02d', $i) ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                    <select name="fy_month" style="flex: 1; padding: 0.45rem 0.6rem; font-size: 0.85rem;">
                        @foreach(['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'] as $mVal => $mName)
                            <option value="{{ $mVal }}" {{ $curMonth == $mVal ? 'selected' : '' }}>{{ $mName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Multi-Taxation Toggle -->
            <div style="grid-column: span 4; margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <label style="font-size: 0.9rem; font-weight: 600; color: #1e293b; margin: 0;">Allow Multi-Taxation</label>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0;">Enable multiple tax rates (GST, VAT, etc.) across invoices, orders, and quotations</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.85rem; color: {{ $account->allow_multi_taxation ? '#059669' : '#64748b' }};">{{ $account->allow_multi_taxation ? 'Yes' : 'No' }}</span>
                        <label style="position: relative; width: 50px; height: 26px; cursor: pointer;">
                            <input type="checkbox" name="allow_multi_taxation" value="1" {{ old('allow_multi_taxation', $account->allow_multi_taxation ?? false) ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                            <span class="toggle-slider" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $account->allow_multi_taxation ? '#059669' : '#cbd5e1' }}; border-radius: 26px; transition: 0.3s;"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Fixed Tax Rate (shown when multi-taxation is NO) -->
            <div id="fixed-tax-section" style="grid-column: span 4; margin-top: 0.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; display: {{ $account->allow_multi_taxation ? 'none' : 'block' }};">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <label style="font-size: 0.9rem; font-weight: 600; color: #1e293b; margin: 0;">Fixed Tax Rate</label>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0;">
                            {{ $account->allow_multi_taxation ? 'Enable multi-taxation to configure multiple tax rates' : 'Single tax rate applied to all orders and invoices' }}
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        @if(!$account->allow_multi_taxation)
                        <span style="font-size: 1.1rem; font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                            {{ $account->fixed_tax_type ?? 'GST' }} {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                        </span>
                        <button type="button" id="open-fixed-tax-modal" class="primary-button" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                            <i class="fas fa-edit" style="margin-right: 0.3rem;"></i> {{ ($account->fixed_tax_rate ?? 0) > 0 ? 'Edit Tax' : 'Add Tax' }}
                        </button>
                        @else
                        <span style="font-size: 0.85rem; color: #9ca3af;">Disabled</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Have Users Toggle -->
            <div style="grid-column: span 4; margin-top: 0.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <label style="font-size: 0.9rem; font-weight: 600; color: #1e293b; margin: 0;">Does your Products/Services are with the No. of Users?</label>
                        <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0;">Enable user-based features (accounts, services, and products can be assigned to specific users)</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.85rem; color: {{ $account->have_users ? '#059669' : '#64748b' }};">{{ $account->have_users ? 'Yes' : 'No' }}</span>
                        <label style="position: relative; width: 50px; height: 26px; cursor: pointer;">
                            <input type="checkbox" name="have_users" value="1" {{ old('have_users', $account->have_users ?? false) ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                            <span class="toggle-slider" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $account->have_users ? '#059669' : '#cbd5e1' }}; border-radius: 26px; transition: 0.3s;"></span>
                        </label>
                    </div>
                </div>
            </div>


            <div class="form-actions" style="grid-column: span 4; margin-top: 0.75rem;">
                <button type="submit" class="primary-button" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Update Profile</button>
            </div>
        </form>
    </section>
</div>

<!-- FINANCIAL YEAR -->
<div id="financial-year" class="tab-content">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-calendar-alt"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Financial Year</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Configure your financial year and serial numbers</p>
            </div>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong style="color: #991b1b; font-size: 0.9rem;">Please fix the following errors:</strong>
                </div>
                <ul style="margin: 0; padding-left: 1.5rem; color: #b91c1c; font-size: 0.85rem;">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom: 0.15rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="display: flex; gap: 1.25rem; align-items: flex-start;">
            <!-- FY Form -->
            <div style="flex: 0 0 50%;">
                <h6 style="margin-bottom: 0.75rem; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Add Financial Year</h6>
                <form method="POST" action="{{ route('financial-year.update') }}" style="background: #f8fafc; padding: 1rem; border-radius: 10px; border: 1px solid #e2e8f0;">
                    @csrf
                    <div style="display: flex; align-items: flex-end; gap: 8px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px; display: block; font-weight: 600;">Start Year</label>
                            <select name="year_start" id="fy_year_start" required style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem;">
                                @php $currentYear = date('Y'); @endphp
                                @for($y = $currentYear - 1; $y <= $currentYear + 1; $y++)
                                    <option value="{{ $y }}" {{ $y == $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <span style="font-size: 1rem; font-weight: bold; color: #94a3b8;">-</span>
                        <div style="flex: 1;">
                            <label style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px; display: block; font-weight: 600;">End Year</label>
                            <select name="year_end" id="fy_year_end" required style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem;">
                                @for($y = $currentYear; $y <= $currentYear + 2; $y++)
                                    <option value="{{ $y }}" {{ $y == $currentYear + 1 ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="primary-button" style="padding: 0.45rem 1rem; height: 36px; font-size: 0.82rem;">Add</button>
                    </div>
                </form>
            </div>

            <!-- FY List -->
            <div style="flex: 0 0 50%;">
                <h6 style="margin-bottom: 0.75rem; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Recorded Financial Years</h6>
                <table class="data-table" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>Financial Year</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($financialYears as $index => $fy)
                            <tr>
                                <td style="color: #64748b; font-size: 0.78rem;">{{ $index + 1 }}</td>
                                <td style="font-weight: 500; font-size: 0.83rem;">{{ $fy->financial_year }}</td>
                                <td>
                                    @if($fy->default)
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 0.68rem; font-weight: 600; text-transform: uppercase;">Default</span>
                                    @else
                                        <span style="color: #94a3b8; font-size: 0.78rem;">—</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if(!$fy->default)
                                        <form method="POST" action="{{ route('financial-year.default', $fy->fy_id) }}" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-link" style="background: none; border: 1px solid #3b82f6; color: #3b82f6; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">Set Default</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 1.25rem; font-size: 0.8rem;">No financial years yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Serial Configuration -->
        <div style="margin-top: 2rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-hashtag"></i></div>
                <h6 style="margin: 0; font-size: 0.9rem; font-weight: 600; color: #1e293b;">Serial Number Configuration</h6>
            </div>
            <p style="margin: 0.25rem 0 1rem 0; font-size: 0.75rem; color: #64748b;">Configure how invoice and quotation numbers are generated.</p>
            @include('settings.serial-config')
        </div>
    </section>
</div>

<!-- CONFIG -->
<div id="config" class="tab-content">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-cog"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Configuration Keys</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Manage system-wide configuration keys</p>
            </div>
        </div>

        <div style="margin-bottom: 2rem; padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <h6 style="margin-bottom: 0.75rem; font-weight: 600;">
                {{ $editingSetting ? 'Edit Configuration Key' : 'Add New Configuration Key' }}
            </h6>
            
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
            <h6 style="margin: 0; color: #1e293b; font-weight: 600;">System Settings</h6>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Key</th>
                    <th>Value</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($settings as $index => $setting)
                    <tr>
                        <td style="color: #64748b; font-size: 0.85rem;">{{ $index + 1 }}</td>
                        <td><code>{{ $setting['key'] }}</code></td>
                        <td>{{ $setting['value'] }}</td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <a href="{{ route('settings.index', ['e' => base64_encode($setting['record_id'])]) }}#config" class="text-link" style="border: 1px solid #3b82f6; color: #3b82f6; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; text-decoration: none;">Edit</a>
                                <form method="POST" action="{{ route('settings.destroy', $setting['record_id']) }}" onsubmit="return confirm('Delete this setting?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background: none; border: 1px solid #ef4444; color: #ef4444; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
@empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">No settings found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- BILLING DETAILS TAB -->
<div id="billing-details" class="tab-content">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-file-invoice-dollar"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Billing Details</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Configure billing information that appears on invoices</p>
            </div>
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
        {{-- DEBUG: Check if editingBillingDetail exists --}}
        @php
            echo '<!-- DEBUG: editingBillingDetail = ' . (isset($editingBillingDetail) ? 'SET' : 'NOT SET') . ' -->';
            if(isset($editingBillingDetail)) {
                echo '<!-- DEBUG: billing_name = ' . ($editingBillingDetail->billing_name ?? 'NULL') . ' -->';
                echo '<!-- DEBUG: country = ' . ($editingBillingDetail->country ?? 'NULL') . ' -->';
                echo '<!-- DEBUG: gstin = ' . ($editingBillingDetail->gstin ?? 'NULL') . ' -->';
            }
        @endphp
        <form method="POST" action="{{ route('account.billing.update') }}" enctype="multipart/form-data" class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
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
            <div style="grid-column: span 3;">
                <label style="font-size: 0.8rem;">Address</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">{{ old('address', $editingBillingDetail->address ?? '') }}</textarea>
            </div>
            <div>
                <label>Country</label>
                <select name="country" class="country-select" data-selected="{{ old('country', $editingBillingDetail->country ?? 'India') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select Country</option>
                </select>
            </div>
            <div>
                <label>State *</label>
                <select name="state" required class="state-select" data-selected="{{ old('state', $editingBillingDetail->state ?? '') }}" style="width: 100%; padding: 0.45rem 0.6rem;">
                    <option value="">Select State</option>
                </select>
                @error('state') <span class="error">{{ $message }}</span> @enderror
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
            <div>
                <label>Signature Upload</label>
                <input type="file" name="signature_upload" id="billing-signature-upload" accept="image/*" onchange="previewSignature(this, 'billing-signature-preview')">
                <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 0.25rem;">Max file size: 5MB. Supported formats: JPG, PNG, GIF, SVG</small>
                @if(!empty($editingBillingDetail->signature_upload))
                    <div style="margin-top: 0.5rem;">
                        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Current signature:</small>
                        <img id="billing-signature-preview" src="{{ asset('storage/' . $editingBillingDetail->signature_upload) }}" alt="Signature" style="max-width: 200px; max-height: 100px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px;">
                    </div>
                @else
                    <div id="billing-signature-preview" style="margin-top: 0.5rem; display: none;">
                        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Preview:</small>
                        <img src="" alt="Signature Preview" style="max-width: 200px; max-height: 100px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px;">
                    </div>
                @endif
            </div>

            <div class="form-actions" style="grid-column: span 3; margin-top: 0.75rem;">
                <button type="submit" class="primary-button" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Save Billing Detail</button>
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
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-file-contract"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Quotation Details</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Configure quotation details for quotations</p>
            </div>
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
        <form method="POST" action="{{ route('account.quotation.update') }}" enctype="multipart/form-data" class="form-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
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
            <div style="grid-column: span 3;">
                <label style="font-size: 0.8rem;">Address</label>
                <textarea name="address" rows="2" style="width: 100%; padding: 0.4rem 0.5rem; font-size: 0.85rem;">{{ old('address', $editingQuotationDetail->address ?? '') }}</textarea>
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
            <div>
                <label>Signature Upload</label>
                <input type="file" name="signature_upload" id="quotation-signature-upload" accept="image/*" onchange="previewSignature(this, 'quotation-signature-preview')">
                <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 0.25rem;">Max file size: 5MB. Supported formats: JPG, PNG, GIF, SVG</small>
                @if(!empty($editingQuotationDetail->signature_upload))
                    <div style="margin-top: 0.5rem;">
                        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Current signature:</small>
                        <img id="quotation-signature-preview" src="{{ asset('storage/' . $editingQuotationDetail->signature_upload) }}" alt="Signature" style="max-width: 200px; max-height: 100px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px;">
                    </div>
                @else
                    <div id="quotation-signature-preview" style="margin-top: 0.5rem; display: none;">
                        <small style="color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 0.25rem;">Preview:</small>
                        <img src="" alt="Signature Preview" style="max-width: 200px; max-height: 100px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px;">
                    </div>
                @endif
            </div>

            <div class="form-actions" style="grid-column: span 3; margin-top: 0.75rem;">
                <button type="submit" class="primary-button" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Save Quotation Detail</button>
                @if(isset($editingQuotationDetail) && request('edit_qd'))
                    <a href="{{ route('settings.index') }}#quotation-details" class="text-link" style="margin-left: 1rem;">Cancel</a>
                @endif
            </div>
        </form>

<!-- Single quotation detail form (no list) -->
    </section>
</div>

<!-- TERMS & CONDITIONS TAB -->
<div id="terms-conditions" class="tab-content">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-shield-alt"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Terms & Conditions</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Manage reusable terms for documents</p>
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
                <div style="flex: 1;">
                    <label style="font-size: 0.75rem; margin-bottom: 0.2rem; display: block; color: #64748b; font-weight: 600;">Terms and Condition *</label>
                    <input type="text" name="content" value="{{ old('content', $editingTerm->content ?? '') }}" placeholder="Enter terms and condition" required style="width: 100%; padding: 0.35rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                </div>
                <div style="display: flex; gap: 0.4rem;">
                    <button type="submit" class="primary-button" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">{{ $editingTerm ? 'Update' : 'Add' }}</button>
                    @if($editingTerm)
                        <a href="{{ route('settings.index') }}#terms-conditions" style="padding: 0.4rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #64748b; font-size: 0.85rem; display: inline-block;">Cancel</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Billing Terms List --}}
        <div style="margin-bottom: 1rem;">
            <h6 style="margin-bottom: 0.4rem; color: #1e293b; font-weight: 600; font-size: 0.9rem;">Billing T&C</h6>
            <table class="data-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 50px; padding: 0.4rem;">Seq</th>
                        <th style="padding: 0.4rem;">Terms and Condition</th>
                        <th style="width: 70px; padding: 0.4rem;">Status</th>
                        <th style="width: 80px; text-align: right; padding: 0.4rem;">Action</th>
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
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">No billing T&C added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Quotation Terms List --}}
        <div>
            <h6 style="margin-bottom: 0.4rem; color: #1e293b; font-weight: 600; font-size: 0.9rem;">Quotation T&C</h6>
            <table class="data-table" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th style="width: 50px; padding: 0.4rem;">Seq</th>
                        <th style="padding: 0.4rem;">Terms and Condition</th>
                        <th style="width: 70px; padding: 0.4rem;">Status</th>
                        <th style="width: 80px; text-align: right; padding: 0.4rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotationTerms as $index => $term)
                        <tr>
                            <td style="color: #64748b; text-align: center; padding: 0.4rem;">
                                <form method="POST" action="{{ route('terms-conditions.update-sequence', $term) }}" style="display: inline-block; margin: 0;">
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
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 1.5rem; font-size: 0.85rem;">No quotation T&C added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- TAXES TAB -->
<div id="taxes" class="tab-content">
    <section class="panel-card" style="padding: 0.85rem;">
        <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; padding-bottom: 0.35rem; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 22px; height: 22px; border-radius: 5px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;"><i class="fas fa-percent"></i></div>
            <div>
                <h5 style="margin: 0; font-size: 0.85rem; font-weight: 600; color: #1e293b;">Tax Management</h5>
                <p style="font-size: 0.7rem; color: #64748b; margin: 0;">Manage tax rates for invoices and quotations</p>
            </div>
        </div>

        {{-- Tax Form (add / edit inline) --}}
        <div id="tax-form-card" style="padding: 0.5rem 0.6rem; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 0.75rem; transition: background 0.2s;">
            <h6 id="tax-form-title" style="margin: 0 0 0.4rem 0; font-size: 0.8rem; color: #1e293b; font-weight: 600;">Add New Tax</h6>
            <form method="POST" id="tax-form" action="{{ route('taxes.store') }}" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 0.4rem; align-items: end;">
                @csrf
                <div>
                    <label style="font-size: 0.7rem; margin-bottom: 0.15rem; display: block; color: #64748b;">Rate (%) *</label>
                    <input type="number" name="rate" id="tax-rate-input" value="{{ old('rate') }}" placeholder="e.g., 18" step="0.01" min="0" max="100" required style="width: 100%; padding: 0.28rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                </div>
                <div>
                    <label style="font-size: 0.7rem; margin-bottom: 0.15rem; display: block; color: #64748b;">Type *</label>
                    <select name="type" id="tax-type-select" required style="width: 100%; padding: 0.28rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                        @foreach(['GST' => 'GST', 'VAT' => 'VAT'] as $val => $label)
                            <option value="{{ $val }}" {{ old('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; gap: 0.3rem; align-content: end;">
                    <button type="submit" id="tax-form-btn" class="primary-button" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Add Tax</button>
                    <button type="button" id="tax-form-cancel" style="display:none;padding:0.3rem 0.6rem;border:1px solid #cbd5e1;border-radius:4px;background:white;color:#64748b;font-size:0.8rem;cursor:pointer;" onclick="cancelEditTax()">Cancel</button>
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
            <div style="margin-bottom: 0.75rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                    <h6 style="margin: 0; font-weight: 600; color: #1e293b;">
                        <span style="background:#f1f5f9;color:#475569;padding:1px 8px;border-radius:8px;font-size:0.72rem;">{{ $taxType }}</span>
                        — <span style="font-size: 0.72rem; color: #64748b;">{{ $group->count() }} tax{{ $group->count() > 1 ? 'es' : '' }}</span>
                    </h6>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 35px; padding: 0.25rem; font-size: 0.75rem;">#</th>
                            <th style="font-size: 0.75rem;">Rate</th>
                            <th style="width: 60px; padding: 0.25rem; font-size: 0.75rem;">Status</th>
                            <th style="width: 75px; text-align: right; padding: 0.25rem; font-size: 0.75rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group as $index => $tax)
                            <tr>
                                <td style="color: #94a3b8; text-align: center; padding: 0.25rem; font-size: 0.78rem;">{{ $index + 1 }}</td>
                                <td style="padding: 0.25rem; font-weight: 500; font-size: 0.8rem;">{{ $tax->rate }}%</td>
                                <td style="padding: 0.25rem;">
                                    @if($tax->is_active)
                                        <span style="background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 8px; font-size: 0.65rem;">Active</span>
                                    @else
                                        <span style="background: #f1f5f9; color: #64748b; padding: 1px 6px; border-radius: 8px; font-size: 0.65rem;">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right; padding: 0.25rem;">
                                    <div class="table-actions">
                                        {{-- <form method="POST" action="{{ route('taxes.toggle', $tax) }}" style="display: inline;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="icon-action-btn" style="background: #f59e0b; border: none;" title="Toggle Status"><i class="fas fa-toggle-on" style="color: white;"></i></button>
                                        </form> --}}
                                        <a href="javascript:void(0)" class="icon-action-btn edit" title="Edit"
                                           data-id="{{ $tax->taxid }}"
                                           data-rate="{{ $tax->rate }}"
                                           data-type="{{ $tax->type }}"
                                           data-name="{{ $tax->tax_name }}"
                                           onclick="startEditTax(this)"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('taxes.destroy', $tax) }}" style="display: inline;" onsubmit="return confirm('Delete this tax?')">
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
            <p style="text-align: center; color: #94a3b8; padding: 0.75rem; font-size: 0.78rem;">No taxes configured yet.</p>
        @endif
    </section>
</div>

{{-- Fixed Tax Rate Modal --}}
<div class="modal fade" id="fixedTaxRateModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('account.fixed-tax.update') }}" id="fixed-tax-form">
                    @csrf
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Rate (%)</label>
                        <input type="number" name="fixed_tax_rate" placeholder="18" step="0.01" min="0" max="100" value="{{ old('fixed_tax_rate', $account->fixed_tax_rate ?? 0) }}" required
                               style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Type</label>
                        <select name="fixed_tax_type" required
                                style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
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
    document.getElementById('tax-form-cancel').style.display = 'inline-block';
    document.getElementById('tax-form-card').style.background = '#eff6ff';
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
    document.getElementById('tax-form-cancel').style.display = 'none';
    document.getElementById('tax-form-card').style.background = '#f8fafc';
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
                img.style.cssText = 'max-width: 120px; max-height: 80px; border-radius: 6px; margin-bottom: 0.5rem; object-fit: contain;';
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
