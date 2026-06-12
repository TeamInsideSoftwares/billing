@extends('layouts.app')

@section('content')
@php
$isMessageTemplateValidation =
$errors->any() &&
(old('template_type') !== null ||
old('channel') !== null ||
old('template_id') !== null ||
session()->has('mt_error_toast'));
$isFinancialYearValidation =
$errors->any() &&
(old('year_start') !== null ||
old('year_end') !== null ||
old('fy_prefix_type') !== null ||
old('fy_prefix_value') !== null ||
old('fy_number_start') !== null);
$isBillingDetailsValidation =
$errors->any() &&
(old('account_bdid') !== null ||
old('billing_name') !== null ||
old('billing_from_email') !== null ||
old('authorize_signatory') !== null ||
old('gstin') !== null ||
old('signature_upload') !== null);
$isBusinessInfoValidation =
$errors->any() &&
!($isMessageTemplateValidation || $isFinancialYearValidation || $isBillingDetailsValidation);
$activeSettingsTab = 'personal';

if ($isFinancialYearValidation) {
$activeSettingsTab = 'financial-year';
} elseif ($isMessageTemplateValidation) {
$activeSettingsTab = 'message-templates';
} elseif ($isBillingDetailsValidation) {
$activeSettingsTab = 'billing-details';
}
@endphp

<section class="section-bar">
    <div></div>
</section>

<style>
    .settings-tab-group {
        border-bottom: 1px solid #dee2e6;
    }

    .settings-tab-group .settings-tab-btn {
        color: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.6) !important;
        border: none;
        border-bottom: 2px solid transparent;
        background: transparent;
        padding: 0.5rem 1rem;
    }

    .settings-tab-group .settings-tab-btn:hover {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom-color: transparent;
    }

    .settings-tab-group .settings-tab-btn.active {
        color: var(--bs-primary, #0d6efd) !important;
        border-bottom: 2px solid var(--bs-primary, #0d6efd) !important;
        background-color: transparent !important;
    }
</style>

<div class="settings-page position-relative bg-white p-3 rounded-3 shadow-sm">
    <!-- Tabs Wrapper -->
    <ul class="nav nav-underline mb-3 settings-tab-group" role="tablist">
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'personal' ? 'active' : '' }}"
                data-bs-toggle="tab" data-bs-target="#personal" role="tab" aria-controls="personal"
                aria-selected="true">
                Business
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'financial-year' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#financial-year" role="tab" aria-controls="financial-year"
                aria-selected="false">
                Financial
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'config' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#config" role="tab" aria-controls="config" aria-selected="false">
                Configuration Keys
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'message-templates' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#message-templates" role="tab" aria-controls="message-templates"
                aria-selected="false">
                Manage Templates
            </button>
        </li>
        @if ($account->allow_multi_taxation)
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'billing-details' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#billing-details" role="tab" aria-controls="billing-details"
                aria-selected="false">
                Billing Details
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'terms-conditions' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#terms-conditions" role="tab" aria-controls="terms-conditions"
                aria-selected="false">
                Terms &amp; Conditions
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'taxes' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#taxes" role="tab" aria-controls="taxes" aria-selected="false">
                Taxes
            </button>
        </li>
        @else
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'billing-details' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#billing-details" role="tab" aria-controls="billing-details"
                aria-selected="false">
                Billing Details
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link rounded-0 settings-tab-btn {{ $activeSettingsTab === 'terms-conditions' ? 'active' : 'text-secondary' }}"
                data-bs-toggle="tab" data-bs-target="#terms-conditions" role="tab" aria-controls="terms-conditions"
                aria-selected="false">
                Terms &amp; Conditions
            </button>
        </li>
        @endif
    </ul>



    <div class="tab-content settings-tab-content">
        <!-- PERSONAL TAB -->
        <div id="personal" class="tab-pane fade {{ $activeSettingsTab === 'personal' ? 'show active' : '' }}"
            role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Business Information</h5>

                @if ($errors->any() && $isBusinessInfoValidation)
                <div class="alert alert-danger mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2 text-danger fw-bold">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <span>Please fix the following errors:</span>
                    </div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                        <li class="small text-danger">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="bg-light p-4 rounded-3 border">
                    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data"
                        class="mainForm row g-3">
                        @csrf
                        @method('PUT')

                        <!-- Logo Upload -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Company Logo</label>
                            @php
                            $hasLogo = !empty($account->logo_path);
                            @endphp
                            <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                style="cursor:pointer;" id="logo-drop-zone">
                                <input type="file" id="logo-upload" name="logo" accept="image/*"
                                    class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                    onchange="previewLogo(this)">

                                <div class="drop-zone-prompt {{ $hasLogo ? 'd-none' : 'd-flex' }} align-items-center justify-content-center"
                                    id="drop-zone-prompt">
                                    <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                    <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                            class="text-primary fw-semibold">browse files</span></span>
                                </div>

                                <div class="drop-zone-preview {{ $hasLogo ? '' : 'd-none' }} align-items-center justify-content-between w-100"
                                    id="drop-zone-preview">
                                    <img id="logo-preview"
                                        src="{{ $hasLogo ? (str_starts_with($account->logo_path, 'http') ? $account->logo_path : asset($account->logo_path)) : '#' }}"
                                        alt="Logo Preview" class="img-fluid rounded mb-0 shadow-sm" width="50px">
                                    <button type="button" id="remove-logo-btn"
                                        class="btn btn-sm btn-danger rounded-circle p-0 bg-transparent text-dark border-0"
                                        title="Remove Image">
                                        <i class="fas fa-upload fs-6 lh-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted small d-block mt-1">Square recommended. 5MB max.</small>
                        </div>

                        <!-- Business Info Fields -->
                        <div class="col-12 col-md-9">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Business Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $account->name ?? '') }}"
                                        required class="form-control">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Legal Entity
                                        Name</label>
                                    <input type="text" name="legal_name"
                                        value="{{ old('legal_name', $account->legal_name ?? '') }}"
                                        class="form-control">
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Website</label>
                                    <input type="text" name="website"
                                        value="{{ old('website', $account->website ?? '') }}" class="form-control">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="email" value="{{ old('email', $account->email ?? '') }}"
                                        required class="form-control"
                                        placeholder="name@company.com, accounts@company.com">
                                    <div class="form-text text-muted small mt-1">Use comma to add multiple emails</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                                    <input type="text" name="phone" value="{{ old('phone', $account->phone ?? '') }}"
                                        class="form-control" placeholder="+91..., +1...">
                                    <div class="form-text text-muted small mt-1">Use comma to add multiple phone numbers
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- More Fields -->
                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Currency</label>
                            <select name="currency_code" class="form-select">
                                @foreach ($currencies as $currency)
                                <option value="{{ $currency->iso }}" {{ old('currency_code', $account->currency_code ??
                                    'INR') == $currency->iso ? 'selected' : '' }}>
                                    {{ $currency->iso }} - {{ $currency->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Timezone</label>
                            <input type="text" name="timezone"
                                value="{{ old('timezone', $account->timezone ?? 'Asia/Kolkata') }}"
                                class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                            <input type="text" name="address_line_1"
                                value="{{ old('address_line_1', $account->address_line_1 ?? '') }}"
                                class="form-control">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                            <select name="country" class="country-select form-select"
                                data-selected="{{ old('country', $account->country ?? '') }}">
                                <option value="">Select Country</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">State <span
                                    class="text-danger">*</span></label>
                            <select name="state" required class="state-select form-select"
                                data-selected="{{ old('state', $account->state ?? '') }}">
                                <option value="">Select State</option>
                            </select>
                            @error('state')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                            <select name="city" class="city-select form-select"
                                data-selected="{{ old('city', $account->city ?? '') }}">
                                <option value="">Select City</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Postal Code</label>
                            <input type="text" name="postal_code"
                                value="{{ old('postal_code', $account->postal_code ?? '') }}" class="form-control">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">FY Start (Day &
                                Month)</label>
                            <div class="d-flex gap-2">
                                @php
                                $currentFy = old('fy_startdate', $account->fy_startdate ?? '04-01');
                                $parts = explode('-', $currentFy);
                                $curMonth = $parts[0] ?? '04';
                                $curDay = $parts[1] ?? '01';
                                @endphp
                                <select name="fy_day" class="fy-day-select form-select" style="width: auto;">
                                    @for ($i = 1; $i <= 31; $i++) <option value="{{ sprintf('%02d', $i) }}" {{
                                        $curDay==sprintf('%02d', $i) ? 'selected' : '' }}>{{ $i }}
                                        </option>
                                        @endfor
                                </select>
                                <select name="fy_month" class="fy-month-select form-select">
                                    @foreach (['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' =>
                                    'September', '10' => 'October', '11' => 'November', '12' => 'December'] as $mVal =>
                                    $mName)
                                    <option value="{{ $mVal }}" {{ $curMonth==$mVal ? 'selected' : '' }}>
                                        {{ $mName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Toggle Controls -->
                        <div class="col-12 col-md-9">
                            <div class="row g-3">
                                <!-- Tax Settings Toggle -->
                                <div class="col-12 col-md-6">
                                    <div class="bg-white p-3 rounded-3 border h-100">
                                        <p class="small text-muted fw-bold text-uppercase mb-2">Tax Settings</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">Allow Multi-Taxation</h6>
                                                <p class="small text-muted mb-0">Use different tax rates</p>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="badge {{ $account->allow_multi_taxation ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} px-2 py-1">
                                                    {{ $account->allow_multi_taxation ? 'Yes' : 'No' }}
                                                </span>
                                                <div class="form-check form-switch fs-4 mb-0">
                                                    <input type="checkbox" name="allow_multi_taxation" value="1" {{
                                                        old('allow_multi_taxation', $account->allow_multi_taxation ??
                                                    false) ? 'checked' : '' }}
                                                    class="form-check-input" role="switch">
                                                </div>
                                            </div>
                                        </div>

                                        <div id="fixed-tax-section"
                                            class="border-top mt-2 pt-2 {{ $account->allow_multi_taxation ? 'is-hidden' : '' }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-0 fw-semibold text-dark">Fixed Tax Rate</h6>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if (!$account->allow_multi_taxation)
                                                    <span
                                                        class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                        {{ $account->fixed_tax_type ?? 'GST' }}
                                                        {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                                    </span>
                                                    <button type="button" id="open-fixed-tax-modal"
                                                        class="btn btn-sm btn-outline-primary bg-white text-primary">
                                                        {{ ($account->fixed_tax_rate ?? 0) > 0 ? 'Edit Tax' : 'Add Tax'
                                                        }}
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Settings Toggle -->
                                <div class="col-12 col-md-6">
                                    <div class="bg-white p-3 rounded-3 border h-100">
                                        <p class="small text-muted fw-bold text-uppercase mb-2">User Settings</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-semibold text-dark">Does your Products/Services are
                                                    with the No. of Users?</h6>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="badge {{ $account->have_users ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} px-2 py-1">
                                                    {{ $account->have_users ? 'Yes' : 'No' }}
                                                </span>
                                                <div class="form-check form-switch fs-4 mb-0">
                                                    <input type="checkbox" name="have_users" value="1" {{
                                                        old('have_users', $account->have_users ?? false) ? 'checked' :
                                                    '' }}
                                                    class="form-check-input" role="switch">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Update Profile button wrapper -->
                        <div class="col-12 d-flex align-items-center justify-content-end gap-2 mt-3 pt-3 border-top">
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Update Profile <i class="fas fa-save btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <!-- FINANCIAL YEAR -->
        <div id="financial-year"
            class="tab-pane fade {{ $activeSettingsTab === 'financial-year' ? 'show active' : '' }}" role="tabpanel">
            <section class="bg-white border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="border-0 bg-white py-2 px-3">
                    <h5 class="fw-semibold text-dark mb-0">Financial Year</h5>
                </div>
                <div class="bg-white p-2 pt-0">

                    @if ($errors->any() && $isFinancialYearValidation)
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                            <li class="small text-danger">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row g-2">
                        <!-- FY Form -->
                        <div class="col-12 col-md-5">
                            <div class="bg-light p-2 rounded-3 h-100">
                                <div class="mb-2">
                                    <h6 class="fw-semibold text-primary small lh-sm mb-0">Add Financial Year</h6>
                                </div>
                                <form method="POST" action="{{ route('financial-year.update') }}" class="mainForm">
                                    @csrf
                                    <div class="row g-2 align-items-end">
                                        <div class="col">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Start
                                                Year</label>
                                            <select name="year_start" id="fy_year_start" required class="form-select">
                                                @php $currentYear = date('Y'); @endphp
                                                @for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++) <option
                                                    value="{{ $y }}" {{ $y==$currentYear ? 'selected' : '' }}>{{ $y }}
                                                    </option>
                                                    @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto pb-2 text-muted fw-bold">-</div>
                                        <div class="col">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">End
                                                Year</label>
                                            <select name="year_end" id="fy_year_end" required class="form-select">
                                                @for ($y = $currentYear; $y <= $currentYear + 2; $y++) <option
                                                    value="{{ $y }}" {{ $y==$currentYear + 1 ? 'selected' : '' }}>{{ $y
                                                    }}
                                                    </option>
                                                    @endfor
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                                <i class="fas fa-plus btn-icon me-1"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- FY List -->
                        <div class="col-12 col-md-7">
                            <div class="bg-light p-2 rounded-3 h-100">
                                <div class="mb-2">
                                    <h6 class="fw-semibold text-primary small lh-sm mb-0">Recorded Financial Years</h6>
                                </div>
                                <div class="card border-0 shadow-sm overflow-hidden">
                                    <div class="table-responsive">
                                        <table class="table mainTable border align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>Financial Year</th>
                                                    <th>Status</th>
                                                    <th class="text-end pe-3">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($financialYears as $index => $fy)
                                                <tr>
                                                    <td class="text-muted small ps-3">{{ $index + 1 }}</td>
                                                    <td><span class="fw-semibold text-dark">{{ $fy->financial_year
                                                            }}</span></td>
                                                    <td>
                                                        @if ($fy->default)
                                                        <span
                                                            class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Default</span>
                                                        @else
                                                        <span class="text-muted small">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end pe-3">
                                                        @if (!$fy->default)
                                                        <form method="POST"
                                                            action="{{ route('financial-year.default', $fy->fy_id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-primary bg-white text-primary">Set
                                                                Default</button>
                                                        </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">No financial
                                                        years yet.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Serial Configuration -->
                    <div class="border-top mt-3 pt-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="fs-5 text-secondary"><i class="fas fa-hashtag"></i></div>
                            <h6 class="fw-semibold text-dark mb-0">Serial Number Configuration</h6>
                        </div>
                        <p class="small text-muted mb-3">Configure how invoice and quotation numbers are generated.</p>
                        @include('settings.serial-config')
                    </div>
                </div>
            </section>
        </div>

        <!-- CONFIG -->
        <div id="config" class="tab-pane fade {{ $activeSettingsTab === 'config' ? 'show active' : '' }}"
            role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Configuration Keys</h5>

                <div class="position-relative bg-light border p-3 rounded-3 mb-4">
                    <h6 class="fw-semibold text-dark mb-3">
                        {{ $editingSetting ? 'Edit Configuration Key' : 'Add New Configuration Key' }}
                    </h6>

                    <form method="POST"
                        action="{{ $editingSetting ? route('settings.update', $editingSetting->settingid) : route('settings.store') }}"
                        class="mainForm">
                        @csrf
                        @if ($editingSetting)
                        @method('PUT')
                        @endif

                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Key Name <span
                                        class="text-danger">*</span></label>
                                <select id="config-key-select" name="key" required class="form-select">
                                    <option value="">-- Select Key --</option>
                                    @php
                                    $currentKey = old('key', $editingSetting->setting_key ?? '');
                                    @endphp
                                    @foreach ($suggestedKeys as $group => $keys)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($keys as $key => $label)
                                        <option value="{{ $key }}" {{ $currentKey==$key ? 'selected' : '' }}>{{ $key }}
                                            ({{ $label }})</option>
                                        @endforeach
                                    </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Value <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="value"
                                    value="{{ old('value', $editingSetting->setting_value ?? '') }}"
                                    placeholder="Enter value" required class="form-control">
                            </div>
                            <div class="col-12 col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    {{ $editingSetting ? 'Update Key' : 'Add Key' }}
                                </button>
                                @if ($editingSetting)
                                <a href="{{ route('settings.index') }}#config"
                                    class="btn btn-outline-secondary">Cancel</a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold text-dark mb-0">System Settings</h6>
                </div>

                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table mainTable border align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Key</th>
                                    <th>Value</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($settings as $index => $setting)
                                <tr>
                                    <td class="text-muted small ps-3">{{ $index + 1 }}</td>
                                    <td><code>{{ $setting['key'] }}</code></td>
                                    <td><span class="text-dark">{{ $setting['value'] }}</span></td>
                                    <td class="text-end pe-3">
                                        <div class="tableActionButton d-inline-flex gap-1">
                                            <a href="{{ route('settings.index', ['e' => base64_encode($setting['record_id'])]) }}#config"
                                                class="bg03 color03" title="Edit">
                                                Edit
                                            </a>
                                            <form method="POST"
                                                action="{{ route('settings.destroy', $setting['record_id']) }}"
                                                class="d-inline" onsubmit="return confirm('Delete this setting?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="bg04 color04" title="Delete">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No settings found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div> <!-- MESSAGE TEMPLATES -->
        <div id="message-templates"
            class="tab-pane fade {{ $activeSettingsTab === 'message-templates' ? 'show active' : '' }}" role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Manage Templates</h5>

                <!-- Document Type Tabs (Top Level) -->
                <ul class="nav nav-underline mb-3 settings-tab-group mt-3" role="tablist">
                    @foreach ($messageTemplateTypes as $typeKey => $typeLabel)
                    <li class="nav-item">
                        <button type="button"
                            class="nav-link rounded-0 settings-tab-btn mt-type-tab-btn {{ $loop->first ? 'is-active active' : 'text-secondary' }}"
                            data-type="{{ $typeKey }}">
                            {{ $typeLabel }}
                        </button>
                    </li>
                    @endforeach
                </ul>

                <div class="position-relative mt-3">
                    @php
                    // Flatten all templates into a single collection for the right-side list
                    $defaultTypeKey = array_key_first($messageTemplateTypes);
                    $templateContextMap = [];
                    $allTemplates = collect();
                    foreach ($messageTemplatesByType as $t) {
                    $allTemplates = $allTemplates->concat($t);
                    }
                    foreach ($allTemplates as $tpl) {
                    $ctxKey = ($tpl->template_type ?? '') . '|' . ($tpl->channel ?? '');
                    if ($ctxKey !== '|') {
                    $templateContextMap[$ctxKey] = [
                    'templateid' => (string) ($tpl->templateid ?? ''),
                    'template_type' => (string) ($tpl->template_type ?? ''),
                    'channel' => (string) ($tpl->channel ?? ''),
                    'name' => (string) ($tpl->name ?? ''),
                    'subject' => (string) ($tpl->subject ?? ''),
                    'body' => (string) ($tpl->body ?? ''),
                    'template_id' => (string) ($tpl->template_id ?? ''),
                    'sender_id' => (string) ($tpl->sender_id ?? ''),
                    ];
                    }
                    }
                    @endphp

                    <div class="row align-items-stretch g-2">
                        <!-- Email Column -->
                        <div class="col-12 col-lg-4">
                            <form method="POST" action="{{ route('message-templates.store') }}"
                                class="mainForm message-template-form d-flex flex-column h-100" data-channel="email"
                                data-store-action="{{ route('message-templates.store') }}"
                                data-update-base="{{ url('settings/message-templates') }}">
                                @csrf
                                <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                <input type="hidden" name="templateid" class="template-id-input" value="">
                                <input type="hidden" name="channel" class="template-channel-input" value="email">

                                <div class="bg-light p-3 rounded-3 h-100 d-flex flex-column">
                                    <div class="mb-3 border-bottom pb-2">
                                        <h5 class="fw-semibold text-primary small lh-sm mb-0"><i
                                                class="fas fa-envelope me-1"></i> Email Template</h5>
                                        <small class="text-xs text-muted template-editor-note-email">One template per
                                            type.</small>
                                    </div>
                                    <div class="d-flex flex-column grow">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6 form-group">
                                                <label
                                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                    Name <span class="template-name-required-mark">*</span></label>
                                                <input type="text" name="name" class="form-control template-name-input"
                                                    placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} Email Template"
                                                    required>
                                            </div>

                                            <div class="col-6 form-group template-subject-group">
                                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Subject
                                                    (optional)</label>
                                                <input type="text" name="subject"
                                                    class="form-control template-subject-input"
                                                    placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} update for @{{ client_name }}"
                                                    autocomplete="off">
                                            </div>
                                        </div>

                                        <div class="form-group mb-2 grow d-flex flex-column">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message
                                                Body <span class="template-body-required-mark">*</span></label>
                                            <textarea name="body" id="templateBodyInput-email" rows="5"
                                                class="form-control template-body-input grow"
                                                placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                        </div>

                                        <div
                                            class="d-flex align-items-center justify-content-end pt-2 border-top mt-auto">
                                            <button type="submit"
                                                class="btn btn-outline-primary btn-primary text-white btn-sm fw-medium px-3 py-1.5 template-submit-btn">
                                                Save Email Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- WhatsApp Column -->
                        <div class="col-12 col-lg-4">
                            <form method="POST" action="{{ route('message-templates.store') }}"
                                class="mainForm message-template-form d-flex flex-column h-100" data-channel="whatsapp"
                                data-store-action="{{ route('message-templates.store') }}"
                                data-update-base="{{ url('settings/message-templates') }}">
                                @csrf
                                <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                <input type="hidden" name="templateid" class="template-id-input" value="">
                                <input type="hidden" name="channel" class="template-channel-input" value="whatsapp">

                                <div class="bg-light p-3 rounded-3 h-100 d-flex flex-column">
                                    <div class="mb-3 border-bottom pb-2">
                                        <h5 class="fw-semibold text-success small lh-sm mb-0"><i
                                                class="fab fa-whatsapp me-1"></i> WhatsApp Template</h5>
                                        <small class="text-xs text-muted template-editor-note-whatsapp">One template per
                                            type.</small>
                                    </div>
                                    <div class="d-flex flex-column grow">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6 form-group">
                                                <label
                                                    class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                    Name (optional)</label>
                                                <input type="text" name="name" class="form-control template-name-input"
                                                    placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} WhatsApp Template">
                                            </div>

                                            <div class="col-6 form-group template-wa-template-id-group">
                                                <label
                                                    class="form-label small lh-sm fw-semibold text-dark mb-1">WhatsApp
                                                    Template ID <span class="text-danger">*</span></label>
                                                <input type="text" name="template_id"
                                                    class="form-control template-wa-template-id-input template-external-id-input"
                                                    placeholder="wa_template_42" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <div class="form-group mb-2 grow d-flex flex-column">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message
                                                Body</label>
                                            <textarea name="body" id="templateBodyInput-whatsapp" rows="5"
                                                class="form-control template-body-input grow"
                                                placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                            <p class="text-xs text-muted mt-2 mb-0">
                                                Message text is fixed by the provider template. Only keep/update dynamic
                                                variables here.
                                            </p>
                                        </div>

                                        <div
                                            class="d-flex align-items-center justify-content-end pt-2 border-top mt-auto">
                                            <button type="submit"
                                                class="btn btn-outline-success btn-success text-white btn-sm fw-medium px-3 py-1.5 template-submit-btn">
                                                Save WhatsApp Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- SMS Column -->
                        <div class="col-12 col-lg-4">
                            <form method="POST" action="{{ route('message-templates.store') }}"
                                class="mainForm message-template-form d-flex flex-column h-100" data-channel="sms"
                                data-store-action="{{ route('message-templates.store') }}"
                                data-update-base="{{ url('settings/message-templates') }}">
                                @csrf
                                <input type="hidden" name="template_type" value="{{ $defaultTypeKey }}">
                                <input type="hidden" name="templateid" class="template-id-input" value="">
                                <input type="hidden" name="channel" class="template-channel-input" value="sms">

                                <div class="bg-light p-3 rounded-3 h-100 d-flex flex-column">
                                    <div class="mb-3 border-bottom pb-2">
                                        <h5 class="fw-semibold text-info small lh-sm mb-0"><i
                                                class="fas fa-sms me-1"></i> SMS Template</h5>
                                        <small class="text-xs text-muted template-editor-note-sms">One template per
                                            type.</small>
                                    </div>
                                    <div class="d-flex flex-column grow">
                                        <div class="form-group mb-2">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Template
                                                Name (optional)</label>
                                            <input type="text" name="name" class="form-control template-name-input"
                                                placeholder="{{ $messageTemplateTypes[$defaultTypeKey] ?? '' }} SMS Template">
                                        </div>

                                        <div class="row g-2 mb-2">
                                            <div class="col-6 form-group">
                                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">SMS
                                                    Template ID <span class="text-danger">*</span></label>
                                                <input type="text" name="template_id"
                                                    class="form-control template-external-id-input"
                                                    placeholder="sms_template_15" autocomplete="off" required>
                                            </div>

                                            <div class="col-6 form-group">
                                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">SMS
                                                    Sender ID (optional)</label>
                                                <input type="text" name="sender_id"
                                                    class="form-control template-sender-id-input" placeholder=""
                                                    autocomplete="off">
                                            </div>
                                        </div>

                                        <div class="form-group mb-2 grow d-flex flex-column">
                                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message
                                                Body</label>
                                            <textarea name="body" id="templateBodyInput-sms" rows="5"
                                                class="form-control template-body-input grow"
                                                placeholder="Hi @{{ client_name }},\nPlease find the details below."></textarea>
                                            <p class="text-xs text-muted mt-2 mb-0">
                                                Message text is fixed by the provider template. Only keep/update dynamic
                                                variables here.
                                            </p>
                                        </div>

                                        <div
                                            class="d-flex align-items-center justify-content-end pt-2 border-top mt-auto">
                                            <button type="submit"
                                                class="btn btn-outline-info btn-info text-white btn-sm fw-medium px-3 py-1.5 template-submit-btn">
                                                Save SMS Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Template Variables Helper Box at the bottom of the row -->
                    <div class="bg-light p-3 border rounded-3 mt-3">
                        <div class="small fw-semibold text-muted mb-2">Available Template Variables:</div>
                        <div class="d-flex flex-wrap gap-2 template-variable-badges"></div>
                        <div class="text-xs text-muted mt-2 template-variable-help">
                            Showing common tags and tags relevant to the selected template type.
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- BILLING DETAILS TAB -->
        <div id="billing-details"
            class="tab-pane fade {{ $activeSettingsTab === 'billing-details' ? 'show active' : '' }}" role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Billing Details</h5>

                @if ($errors->any() && $isBillingDetailsValidation)
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                        <li class="small">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- DEBUG: Check if editingBillingDetail exists --}}
                @php
                echo '<!-- DEBUG: editingBillingDetail = ' .
                        (isset($editingBillingDetail) ? 'SET' : 'NOT SET') .
                        ' -->';
                @endphp

                <div class="bg-light p-4 rounded-3 border">
                    <form method="POST" action="{{ route('account.billing.update') }}" enctype="multipart/form-data"
                        class="mainForm row g-3">
                        @csrf
                        @if (isset($editingBillingDetail))
                        <input type="hidden" name="account_bdid" value="{{ $editingBillingDetail->account_bdid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Business Billing Name<span
                                    class="text-danger">*</span></label>
                            <input type="text" name="billing_name" class="form-control"
                                value="{{ old('billing_name', $editingBillingDetail->billing_name ?? ($account->name ?? '')) }}"
                                required>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Billing From Email</label>
                            <input type="text" name="billing_from_email" class="form-control"
                                value="{{ old('billing_from_email', $editingBillingDetail->billing_from_email ?? '') }}"
                                placeholder="billing@company.com, finance@company.com">
                            <div class="form-text text-muted small mt-1">Use comma to add multiple emails</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Authorize Signatory</label>
                            <input type="text" name="authorize_signatory" class="form-control"
                                value="{{ old('authorize_signatory', $editingBillingDetail->authorize_signatory ?? '') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                            <textarea name="address" rows="2"
                                class="form-control">{{ old('address', $editingBillingDetail->address ?? '') }}</textarea>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                            <select name="billing_country" class="country-select form-select"
                                data-selected="{{ old('billing_country', $editingBillingDetail->country ?? 'India') }}">
                                <option value="">Select Country</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">State<span
                                    class="text-danger">*</span></label>
                            <select name="billing_state" required class="state-select form-select"
                                data-selected="{{ old('billing_state', $editingBillingDetail->state ?? '') }}">
                                <option value="">Select State</option>
                            </select>
                            @error('billing_state')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                            <select name="billing_city" class="city-select form-select"
                                data-selected="{{ old('billing_city', $editingBillingDetail->city ?? '') }}">
                                <option value="">Select City</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Postal Code</label>
                            <input type="text" name="billing_postal_code" class="form-control"
                                value="{{ old('billing_postal_code', $editingBillingDetail->postal_code ?? '') }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">GSTIN</label>
                            <input type="text" name="gstin" class="form-control"
                                value="{{ old('gstin', $editingBillingDetail->gstin ?? '') }}" maxlength="15"
                                minlength="15" pattern="[A-Z0-9]{15}" title="GSTIN must be exactly 15 characters"
                                oninput="this.value=this.value.toUpperCase().replace(/[^A-Z0-9]/g,'')"
                                onblur="if(this.value && this.value.length!==15){this.setCustomValidity('GSTIN must be exactly 15 characters');this.reportValidity();}else{this.setCustomValidity('');}">
                            <div class="form-text text-muted small mt-1">Exactly 15 characters required</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">TIN</label>
                            <input type="text" name="tin" class="form-control"
                                value="{{ old('tin', $editingBillingDetail->tin ?? '') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Signature Upload</label>
                            @php
                            $hasSignature = !empty($editingBillingDetail->signature_upload);
                            @endphp
                            <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                style="cursor:pointer;" id="sig-drop-zone">
                                <input type="file" id="billing-signature-upload" name="signature_upload"
                                    accept="image/*" class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                    onchange="previewSignature(this)">

                                <div class="drop-zone-prompt {{ $hasSignature ? 'd-none' : 'd-flex' }} align-items-center justify-content-center"
                                    id="sig-drop-zone-prompt">
                                    <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                    <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                            class="text-primary fw-semibold">browse files</span></span>
                                </div>

                                <div class="drop-zone-preview {{ $hasSignature ? '' : 'd-none' }} align-items-center justify-content-between w-100"
                                    id="sig-drop-zone-preview">
                                    <img id="signature-preview-img"
                                        src="{{ $hasSignature ? $editingBillingDetail->signature_upload : '#' }}"
                                        alt="Signature Preview" class="img-fluid rounded mb-0 shadow-sm" width="50px">
                                    <button type="button" id="remove-signature-btn"
                                        class="btn btn-sm btn-danger rounded-circle p-0 bg-transparent text-dark border-0"
                                        title="Remove Image">
                                        <i class="fas fa-upload fs-6 lh-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text text-muted small mt-1">Max file size: 5MB. Supported formats: JPG,
                                PNG, GIF, SVG</div>
                        </div>

                        <div class="col-12 d-flex align-items-center justify-content-end gap-2 mt-3">
                            @if (isset($editingBillingDetail) && request('edit_bd'))
                            <a href="{{ route('settings.index') }}#billing-details"
                                class="btn btn-outline-primary bg-white text-primary fw-medium">
                                <i class="fas fa-times btn-icon me-1"></i> Cancel
                            </a>
                            @endif
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Billing Detail <i class="fas fa-save btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>



        <!-- TERMS & CONDITIONS TAB -->
        <div id="terms-conditions"
            class="tab-pane fade {{ $activeSettingsTab === 'terms-conditions' ? 'show active' : '' }}" role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Terms & Conditions</h5>

                {{-- Add / Edit Form --}}
                <div class="position-relative bg-light p-2 rounded-3 mb-3">
                    <form method="POST" action="{{ route('terms-conditions.store') }}" class="mainForm">
                        @csrf
                        @if ($editingTerm)
                        <input type="hidden" name="tc_id" value="{{ $editingTerm->tc_id }}">
                        @endif

                        <div class="row g-2">
                            {{-- Left: Type + checkbox + submit --}}
                            <div class="col-12 col-lg-4 d-flex flex-column gap-2">
                                <div>
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                        for="settings_term_type">Type<span class="text-danger">*</span></label>
                                    <select id="settings_term_type" name="type" required class="form-select">
                                        <option value="billing" {{ old('type', $editingTerm->type ?? '') == 'billing' ?
                                            'selected' : '' }}>Billing</option>
                                        <option value="quotation" {{ old('type', $editingTerm->type ?? '') ==
                                            'quotation' ? 'selected' : '' }}>Quotation</option>
                                        <option value="proforma" {{ old('type', $editingTerm->type ?? '') == 'proforma'
                                            ? 'selected' : '' }}>Proforma</option>
                                    </select>
                                </div>
                                <div class="mb-0 bg-white border rounded-1 px-2 py-1 ms-1">
                                    <div class="form-check mb-0 form-check-large">
                                        <input type="hidden" name="is_default" value="0">
                                        <input type="checkbox" name="is_default" value="1" class="form-check-input"
                                            id="settings_tc_default" {{ old('is_default', (int)
                                            ($editingTerm->is_default ?? 0)) ? 'checked' : '' }}>
                                        <label class="form-check-label small lh-sm fw-normal text-dark"
                                            for="settings_tc_default">
                                            Set as default
                                        </label>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-auto">
                                    @if ($editingTerm)
                                    <a href="{{ route('settings.index', ['t' => request('t', $editingTerm->type ?? 'billing')]) }}#terms-conditions"
                                        class="btn btn-outline-primary bg-white text-primary fw-medium btn-sm">
                                        <i class="fas fa-times btn-icon me-1"></i> Cancel
                                    </a>
                                    @endif
                                    <button type="submit"
                                        class="btn btn-outline-primary btn-primary text-white fw-medium btn-sm">
                                        {{ $editingTerm ? 'Update' : 'Add' }} <i
                                            class="fas {{ $editingTerm ? 'fa-save' : 'fa-plus' }} btn-icon ms-1"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Right: Textarea --}}
                            <div class="col-12 col-lg-8">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="settings_tc_content">Terms and Condition<span
                                        class="text-danger">*</span></label>
                                <textarea id="settings_tc_content" name="content" rows="6"
                                    placeholder="Enter terms and condition"
                                    class="form-control w-100">{{ old('content', $editingTerm->content ?? '') }}</textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <ul class="nav nav-underline mb-3" id="tcTypeTabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 tc-type-tab active" data-bs-toggle="tab"
                            data-bs-target="#billing-tc" role="tab" aria-controls="billing-tc" aria-selected="true">
                            Billing
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 tc-type-tab text-secondary" data-bs-toggle="tab"
                            data-bs-target="#quotation-tc" role="tab" aria-controls="quotation-tc"
                            aria-selected="false">
                            Quotation
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link rounded-0 tc-type-tab text-secondary" data-bs-toggle="tab"
                            data-bs-target="#proforma-tc" role="tab" aria-controls="proforma-tc" aria-selected="false">
                            Proforma
                        </button>
                    </li>
                </ul>

                <div class="tab-content tc-grid">
                    {{-- Billing Terms List --}}
                    <div class="tab-pane fade show active tc-type-pane" id="billing-tc" data-tc-type="billing"
                        role="tabpanel">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-dark mb-0">Billing T&C</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-4">
                            <div class="table-responsive">
                                <table class="table mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Seq</th>
                                            <th class="ps-3">Terms and Condition</th>
                                            <th style="width: 100px;">Default</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 140px;" class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($billingTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;">
                                                        @for ($i = 1; $i <= $billingTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ ($term->sequence ?? $index + 1)
                                                                ==
                                                                $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="ps-3 text-wrap" style="max-width: 400px;">{!! $term->content !!}
                                            </td>
                                            <td>
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="js-term-status-badge badge cursor-pointer px-2 py-1 {{ $term->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}"
                                                    data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                    data-is-active="{{ $term->is_active ? '1' : '0' }}" role="button"
                                                    tabindex="0"
                                                    title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}"
                                                    style="cursor: pointer;">{{ $term->is_active ? 'Active' : 'Inactive'
                                                    }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'billing']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No billing T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Quotation Terms List --}}
                    <div class="tab-pane fade tc-type-pane" id="quotation-tc" data-tc-type="quotation" role="tabpanel">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-dark mb-0">Quotation T&C</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-4">
                            <div class="table-responsive">
                                <table class="table mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Seq</th>
                                            <th class="ps-3">Terms and Condition</th>
                                            <th style="width: 100px;">Default</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 140px;" class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($quotationTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;">
                                                        @for ($i = 1; $i <= $quotationTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ ($term->sequence ?? $index + 1)
                                                                ==
                                                                $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="ps-3 text-wrap" style="max-width: 400px;">{!! $term->content !!}
                                            </td>
                                            <td>
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="js-term-status-badge badge cursor-pointer px-2 py-1 {{ $term->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}"
                                                    data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                    data-is-active="{{ $term->is_active ? '1' : '0' }}" role="button"
                                                    tabindex="0"
                                                    title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}"
                                                    style="cursor: pointer;">{{ $term->is_active ? 'Active' : 'Inactive'
                                                    }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'quotation']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No quotation T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Proforma Terms List --}}
                    <div class="tab-pane fade tc-type-pane" id="proforma-tc" data-tc-type="proforma" role="tabpanel">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-dark mb-0">Proforma T&C</h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-4">
                            <div class="table-responsive">
                                <table class="table mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Seq</th>
                                            <th class="ps-3">Terms and Condition</th>
                                            <th style="width: 100px;">Default</th>
                                            <th style="width: 120px;">Status</th>
                                            <th style="width: 140px;" class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($proformaTerms as $index => $term)
                                        <tr>
                                            <td>
                                                <form method="POST"
                                                    action="{{ route('terms-conditions.update-sequence', $term) }}"
                                                    class="settings-sequence-form">
                                                    @csrf @method('PATCH')
                                                    <select name="sequence" onchange="this.form.submit()"
                                                        class="form-select form-select-sm" style="width: 70px;">
                                                        @for ($i = 1; $i <= $proformaTerms->count(); $i++)
                                                            <option value="{{ $i }}" {{ ($term->sequence ?? $index + 1)
                                                                ==
                                                                $i ? 'selected' : '' }}>
                                                                {{ $i }}</option>
                                                            @endfor
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="ps-3 text-wrap" style="max-width: 400px;">{!! $term->content !!}
                                            </td>
                                            <td>
                                                @if ($term->is_default)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Default</span>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="js-term-status-badge badge cursor-pointer px-2 py-1 {{ $term->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}"
                                                    data-toggle-url="{{ route('terms-conditions.toggle', $term) }}"
                                                    data-is-active="{{ $term->is_active ? '1' : '0' }}" role="button"
                                                    tabindex="0"
                                                    title="Click to {{ $term->is_active ? 'Deactivate' : 'Activate' }}"
                                                    style="cursor: pointer;">{{ $term->is_active ? 'Active' : 'Inactive'
                                                    }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <a href="{{ route('settings.index', ['e' => base64_encode($term->tc_id), 't' => 'proforma']) }}#terms-conditions"
                                                        class="bg03 color03" title="Edit">Edit</a>
                                                    <form method="POST"
                                                        action="{{ route('terms-conditions.destroy', $term) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this term?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04"
                                                            title="Delete">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No proforma T&C added
                                                yet.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- TAXES TAB -->
        <div id="taxes" class="tab-pane fade {{ $activeSettingsTab === 'taxes' ? 'show active' : '' }}" role="tabpanel">
            <section class="py-3 px-1">
                <h5 class="fw-semibold text-dark mb-4">Tax Management</h5>

                {{-- Tax Form (add / edit inline) --}}
                <div id="tax-form-card" class="position-relative bg-light border p-3 rounded-3 mb-4">
                    <h6 id="tax-form-title" class="fw-semibold text-dark mb-3">Add New Tax</h6>
                    <form method="POST" id="tax-form" action="{{ route('taxes.store') }}" class="mainForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Rate (%)<span
                                        class="text-danger">*</span></label>
                                <input type="number" name="rate" id="tax-rate-input" value="{{ old('rate') }}"
                                    placeholder="e.g., 18" step="0.01" min="0" max="100" required class="form-control">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Type<span
                                        class="text-danger">*</span></label>
                                <select name="type" id="tax-type-select" required class="form-select">
                                    @foreach (['GST' => 'GST', 'VAT' => 'VAT'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('type')==$val ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex align-items-end gap-2 mt-3">
                                <button type="submit" id="tax-form-btn"
                                    class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    Add Tax <i class="fas fa-plus btn-icon ms-1"></i>
                                </button>
                                <button type="button" id="tax-form-cancel"
                                    class="btn btn-outline-primary bg-white text-primary fw-medium d-none"
                                    onclick="cancelEditTax()"><i class="fas fa-times btn-icon me-1"></i> Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Taxes Grouped by Type --}}
                @php
                $taxTypes = ['GST', 'VAT', 'Sales Tax', 'Service Tax', 'Other'];
                $groupedTaxes = $taxes->groupBy('type');
                @endphp
                <div class="tax-list-grid">
                    @foreach ($taxTypes as $taxType)
                    @php
                    $group = $groupedTaxes->get($taxType, collect());
                    @endphp
                    @if ($group->count() > 0)
                    <div class="field-gap mb-4">
                        <div class="mb-2">
                            <h6 class="fw-semibold text-dark mb-0">
                                <span
                                    class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">{{
                                    $taxType }}</span>
                                — <span class="text-muted small">{{ $group->count() }}
                                    tax{{ $group->count() > 1 ? 'es' : '' }}</span>
                            </h6>
                        </div>
                        <div class="card border-0 shadow-sm overflow-hidden mb-3">
                            <div class="table-responsive">
                                <table class="table mainTable border align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">#</th>
                                            <th>Rate</th>
                                            <th style="width: 150px;">Status</th>
                                            <th style="width: 150px;" class="text-end pe-3">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($group as $index => $tax)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $tax->rate }}%</td>
                                            <td>
                                                <span
                                                    class="badge px-2 py-1 {{ $tax->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">
                                                    {{ $tax->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="tableActionButton d-inline-flex gap-1">
                                                    <a href="javascript:void(0)" class="bg03 color03"
                                                        data-id="{{ $tax->taxid }}" data-rate="{{ $tax->rate }}"
                                                        data-type="{{ $tax->type }}" data-name="{{ $tax->tax_name }}"
                                                        onclick="startEditTax(this)">Edit</a>
                                                    <form method="POST" action="{{ route('taxes.destroy', $tax) }}"
                                                        class="d-inline" onsubmit="return confirm('Delete this tax?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="bg04 color04">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @if ($taxes->isEmpty())
                <p class="text-center py-5 text-muted">No taxes configured yet.</p>
                @endif
            </section>
        </div>

        {{-- Fixed Tax Rate Modal --}}
        <div class="modal fade" id="fixedTaxRateModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-semibold" id="fixedTaxRateModalLabel">Add Tax</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('account.fixed-tax.update') }}" id="fixed-tax-form"
                        class="mainForm">
                        @csrf
                        <div class="modal-body bg-light p-4">
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="fixed_tax_rate">Rate (%)<span class="text-danger">*</span></label>
                                <input type="number" id="fixed_tax_rate" name="fixed_tax_rate" placeholder="18"
                                    step="0.01" min="0" max="100"
                                    value="{{ old('fixed_tax_rate', $account->fixed_tax_rate ?? 0) }}" required
                                    class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="fixed_tax_type">Type<span class="text-danger">*</span></label>
                                <select id="fixed_tax_type" name="fixed_tax_type" required class="form-select">
                                    @foreach (['GST' => 'GST', 'VAT' => 'VAT'] as $v => $l)
                                    <option value="{{ $v }}" {{ old('fixed_tax_type', $account->fixed_tax_type ?? 'GST')
                                        ==
                                        $v ? 'selected' : '' }}>
                                        {{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                                    data-bs-dismiss="modal">
                                    <i class="fas fa-times btn-icon me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    Save <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>


    <script>
        function startEditTax(el) {
            var form = document.getElementById('tax-form');
            form.action = '{{ url('settings / taxes') }}/' + el.dataset.id;
            var existingMethod = form.querySelector('input[name="_method"]');
            if (existingMethod) existingMethod.remove();
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_method';
            input.value = 'PATCH';
            form.prepend(input);
            document.getElementById('tax-rate-input').value = el.dataset.rate;
            document.getElementById('tax-type-select').value = el.dataset.type;
            document.getElementById('tax-form-title').textContent = 'Edit Tax (' + el.dataset.id + ')';
            document.getElementById('tax-form-btn').textContent = 'Update';
            document.getElementById('tax-form-cancel').classList.remove('d-none');
            document.getElementById('tax-form-card').classList.add('is-editing');
            form.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function cancelEditTax() {
            var form = document.getElementById('tax-form');
            form.action = '{{ route('taxes.store') }}';
            var existingMethod = form.querySelector('input[name="_method"]');
            if (existingMethod) existingMethod.remove();
            document.getElementById('tax-rate-input').value = '';
            document.getElementById('tax-type-select').selectedIndex = 0;
            document.getElementById('tax-form-title').textContent = 'Add New Tax';
            document.getElementById('tax-form-btn').textContent = 'Add Tax';
            document.getElementById('tax-form-cancel').classList.add('d-none');
            document.getElementById('tax-form-card').classList.remove('is-editing');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const __toastSeenAt = {};

            function showToastDedup(type, message, dedupMs = 1200) {
                const text = String(message || '').trim();
                if (!text) return;
                const key = `${type}:${text}`;
                const now = Date.now();
                if (__toastSeenAt[key] && (now - __toastSeenAt[key]) < dedupMs) return;
                __toastSeenAt[key] = now;

                if (typeof showToast === 'function') {
                    showToast(type, text);
                    return;
                }
            }

            function activateTab(tabId) {
                if (!tabId) return;

                const targetSelector = tabId.startsWith('#') ? tabId : `#${tabId}`;
                const tabTrigger = document.querySelector(`[data-bs-target="${targetSelector}"]`);

                if (!tabTrigger || !window.bootstrap || !bootstrap.Tab) {
                    return;
                }

                bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
            }

            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach((button) => {
                button.addEventListener('shown.bs.tab', function (event) {
                    const targetId = (event.target.getAttribute('data-bs-target') || '').replace('#', '');
                    if (!targetId) return;
                    window.history.replaceState(null, null, `#${targetId}`);
                    try {
                        window.localStorage.setItem('settings_active_tab', targetId);
                    } catch (error) {
                        // Ignore storage failures in private mode or restricted browsers.
                    }
                    document.dispatchEvent(new CustomEvent('settings:tab-activated', {
                        detail: { tabId: targetId }
                    }));
                });
            });

            // Financial Year Sync
            const fyStart = document.getElementById('fy_year_start');
            const fyEnd = document.getElementById('fy_year_end');

            if (fyStart && fyEnd) {
                fyStart.addEventListener('change', function () {
                    const selectedStart = parseInt(this.value);
                    fyEnd.value = selectedStart + 1;

                    // Limit end year options visibility for clarity
                    Array.from(fyEnd.options).forEach(opt => {
                        const optVal = parseInt(opt.value);
                        opt.hidden = optVal !== selectedStart + 1;
                    });
                });

                // Initialize display on load
                fyStart.dispatchEvent(new Event('change'));
            }

            // Handle initial load from Hash
            const hash = window.location.hash.replace('#', '');
            const urlParams = new URLSearchParams(window.location.search);
            const encodedE = urlParams.get('e');
            let storedTab = null;
            let decodedE = null;
            try {
                decodedE = encodedE ? atob(encodedE) : null;
            } catch (e) {
                console.error('Failed to decode parameter e:', e);
            }

            try {
                storedTab = window.localStorage.getItem('settings_active_tab');
            } catch (error) {
                storedTab = null;
            }
            const tcTypeFromUrl = (urlParams.get('t') || '').toLowerCase();

            if (hash) {
                activateTab(hash);
            } else if (storedTab) {
                activateTab(storedTab);
            } else if (decodedE) {
                if (decodedE.startsWith('TC')) activateTab('terms-conditions');
                else if (decodedE.startsWith('SET')) activateTab('config');
                else if (decodedE.startsWith('ABD')) activateTab('billing-details');
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
                    if (autoGenDiv) autoGenDiv.classList.remove('is-hidden');
                    if (autoIncDiv) autoIncDiv.classList.add('is-hidden');
                } else if (radio.value === 'auto_increment') {
                    if (autoGenDiv) autoGenDiv.classList.add('is-hidden');
                    if (autoIncDiv) autoIncDiv.classList.remove('is-hidden');
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
                    const lengthInputGroup = form.querySelector(`[name="${part}_length"]`).closest(
                        '.input-group-len');

                    const valField = form.querySelector(`[name="${part}_value"]`);
                    const lengthField = form.querySelector(`[name="${part}_length"]`);

                    // Visibility & Label Logic
                    if (type === 'manual text') {
                        valInputGroup.classList.remove('is-hidden');
                        valLabel.innerText = 'Enter value';
                        lengthInputGroup.classList.add('is-hidden');
                    } else if (type === 'auto generate') {
                        valInputGroup.classList.add('is-hidden');
                        lengthInputGroup.classList.remove('is-hidden');
                    } else if (type === 'auto increment') {
                        valInputGroup.classList.remove('is-hidden');
                        valLabel.innerText = 'Start From';
                        lengthInputGroup.classList.add('is-hidden');
                    } else {
                        valInputGroup.classList.add('is-hidden');
                        lengthInputGroup.classList.add('is-hidden');
                    }

                    // Preview Logic
                    switch (type) {
                        case 'manual text':
                            return valField.value || (part === 'prefix' ? (target == 'billing' ? 'INV' : 'QUO') : (
                                part === 'suffix' ? '2026' : '1001'));
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
            document.querySelectorAll(
                '.serial-type-select, input[name$="_value"], input[name$="_length"], input[name$="_start"], select[name$="_separator"]'
            ).forEach(el => {
                el.addEventListener('input', function () {
                    const form = this.closest('form');
                    const target = form.id ? form.id.split('-')[0] : null;
                    if (target && (target === 'proforma' || target === 'billing' || target ===
                        'quotation')) {
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
                radio.addEventListener('change', function () {
                    handleSerialModeChange(this);
                });
            });

            // Initialize on page load - trigger for billing
            setTimeout(() => {
                const billingRadio = document.querySelector(
                    '#billing-details input[name="serial_mode"]:checked');
                if (billingRadio) {
                    handleSerialModeChange(billingRadio);
                }
            }, 100);

            // TinyMCE for Terms and Conditions Tab
            if (window.tinymce) {
                tinymce.init({
                    license_key: 'gpl',
                    selector: '#settings_tc_content',
                    menubar: false,
                    height: 200,
                    plugins: 'lists link table code autoresize',
                    toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat code',
                    setup: function (editor) {
                        editor.on('change', function () {
                            editor.save(); // keep textarea synchronized
                        });
                    }
                });

                // Trigger save before terms-conditions form submission
                const tcForm = document.querySelector('form[action*="terms-conditions"]');
                if (tcForm) {
                    tcForm.addEventListener('submit', function () {
                        tinymce.triggerSave();
                    });
                }
            }
        });

        // Signature preview function
        function previewSignature(input) {
            const file = input.files[0];
            const previewImg = document.getElementById('signature-preview-img');
            const dropZonePrompt = document.getElementById('sig-drop-zone-prompt');
            const dropZonePreview = document.getElementById('sig-drop-zone-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    dropZonePrompt.classList.add('d-none');
                    dropZonePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '#';
                dropZonePrompt.classList.remove('d-none');
                dropZonePreview.classList.add('d-none');
            }
        }

        function previewLogo(input) {
            const file = input.files[0];
            const previewImg = document.getElementById('logo-preview');
            const dropZonePrompt = document.getElementById('drop-zone-prompt');
            const dropZonePreview = document.getElementById('drop-zone-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    dropZonePrompt.classList.add('d-none');
                    dropZonePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '#';
                dropZonePrompt.classList.remove('d-none');
                dropZonePreview.classList.add('d-none');
            }
        }

        // Toggle fixed tax rate visibility based on multi-taxation toggle
        document.addEventListener('DOMContentLoaded', function () {
            // Company Logo Drag & Drop
            const logoInput = document.getElementById('logo-upload');
            const logoDropZone = document.getElementById('logo-drop-zone');
            const removeLogoBtn = document.getElementById('remove-logo-btn');
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

                logoDropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files && files[0]) {
                        logoInput.files = files;
                        previewLogo(logoInput);
                    }
                });

                if (removeLogoBtn) {
                    removeLogoBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        logoInput.value = '';
                        previewLogo(logoInput);
                    });
                }
            }

            // Signature Drag & Drop
            const sigInput = document.getElementById('billing-signature-upload');
            const sigDropZone = document.getElementById('sig-drop-zone');
            const removeSigBtn = document.getElementById('remove-signature-btn');
            if (sigInput && sigDropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    sigDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sigDropZone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    sigDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sigDropZone.classList.remove('dragover');
                    }, false);
                });

                sigDropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files && files[0]) {
                        sigInput.files = files;
                        previewSignature(sigInput);
                    }
                });

                if (removeSigBtn) {
                    removeSigBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        sigInput.value = '';
                        previewSignature(sigInput);
                    });
                }
            }

            const multiTaxationCheckbox = document.querySelector('input[name="allow_multi_taxation"]');
            const fixedTaxSection = document.getElementById('fixed-tax-section');
            const openFixedTaxBtn = document.getElementById('open-fixed-tax-modal');

            if (multiTaxationCheckbox && fixedTaxSection) {
                multiTaxationCheckbox.addEventListener('change', function () {
                    const isEnabled = this.checked;

                    if (isEnabled) {
                        // Multi-taxation enabled - hide fixed tax field
                        fixedTaxSection.classList.add('is-hidden');
                    } else {
                        // Multi-taxation disabled - show fixed tax field
                        fixedTaxSection.classList.remove('is-hidden');
                    }
                });
            }

            // Open fixed tax rate modal using Bootstrap
            if (openFixedTaxBtn) {
                const fixedTaxModalEl = document.getElementById('fixedTaxRateModal');
                if (fixedTaxModalEl) {
                    const fixedTaxModal = new bootstrap.Modal(fixedTaxModalEl);
                    openFixedTaxBtn.addEventListener('click', function () {
                        fixedTaxModal.show();
                    });
                }
            }

            const templateForms = Array.from(document.querySelectorAll('.message-template-form'));
            const form = document.querySelector('.message-template-form');
            const typeTabs = Array.from(document.querySelectorAll('.mt-type-tab-btn'));
            const templateTypeLabels = @json($messageTemplateTypes);
            const defaultTemplateType = @json(array_key_first($messageTemplateTypes));
            const oldTemplateType = @json(old('template_type', session('mt_active_type')));
            const oldTemplateChannel = @json(old('channel', session('mt_active_channel')));
            const mtErrorToast = @json(session('mt_error_toast'));
            const mtStateKey = 'settings_message_template_state_v1';
            const templateContextMap = @json($templateContextMap ?? []);
            const templateVariableMap = {
                common: [{
                    key: 'client_business_name',
                    label: "Client's Company"
                },
                {
                    key: 'client_contact_person',
                    label: "Client's Contact"
                },
                {
                    key: 'business_name',
                    label: 'Your Business Name'
                },
                ],
                pi: [{
                    key: 'invoice_title',
                    label: ''
                },
                {
                    key: 'pi_number',
                    label: ''
                },
                {
                    key: 'ti_number',
                    label: ''
                },
                {
                    key: 'pi_link',
                    label: ''
                },
                {
                    key: 'ti_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                {
                    key: 'due_date',
                    label: ''
                },
                {
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_start_date',
                    label: ''
                },
                {
                    key: 'item_end_date',
                    label: ''
                },
                ],
                ti: [{
                    key: 'invoice_title',
                    label: ''
                },
                {
                    key: 'pi_number',
                    label: ''
                },
                {
                    key: 'ti_number',
                    label: ''
                },
                {
                    key: 'pi_link',
                    label: ''
                },
                {
                    key: 'ti_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                {
                    key: 'due_date',
                    label: ''
                },
                {
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_start_date',
                    label: ''
                },
                {
                    key: 'item_end_date',
                    label: ''
                },
                ],
                quotation: [{
                    key: 'quotation_title',
                    label: ''
                },
                {
                    key: 'quotation_number',
                    label: ''
                },
                {
                    key: 'quotation_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                ],
                reminder: [{
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_description',
                    label: ''
                },
                {
                    key: 'days_left',
                    label: ''
                },
                {
                    key: 'order_number',
                    label: ''
                },
                {
                    key: 'order_start_date',
                    label: ''
                },
                {
                    key: 'order_end_date',
                    label: ''
                },
                ],
                expiry: [{
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_description',
                    label: ''
                },
                {
                    key: 'expiry_date',
                    label: ''
                },
                {
                    key: 'days_left',
                    label: ''
                },
                {
                    key: 'days_ago',
                    label: ''
                },
                {
                    key: 'order_number',
                    label: ''
                },
                {
                    key: 'order_start_date',
                    label: ''
                },
                {
                    key: 'order_end_date',
                    label: ''
                },
                ],
                payment_received: [{
                    key: 'payment_amount',
                    label: ''
                },
                {
                    key: 'currency',
                    label: 'Client currency'
                },
                {
                    key: 'payment_date',
                    label: ''
                },
                {
                    key: 'payment_mode',
                    label: 'How paid (Bank/Online/Cash)'
                },
                {
                    key: 'reference_number',
                    label: ''
                },
                {
                    key: 'invoice_number',
                    label: ''
                },
                {
                    key: 'invoice_title',
                    label: ''
                },
                ],
            };

            function renderTemplateVariableBadges(type) {
                const badgeContainers = document.querySelectorAll('.template-variable-badges');
                if (!badgeContainers.length) return;

                const common = templateVariableMap.common || [];
                const specific = templateVariableMap[type] || [];
                const tags = [...common, ...specific];
                const seen = new Set();

                const badgeHtmlArray = [];
                tags.forEach((tag) => {
                    if (!tag?.key || seen.has(tag.key)) return;
                    seen.add(tag.key);
                    badgeHtmlArray.push(`<span class="badge bg-light text-muted border px-2 py-1">@{{ ${tag.key} }}${tag.label ? ` (${tag.label})` : ''}</span>`);
                });

                badgeContainers.forEach(container => {
                    container.innerHTML = badgeHtmlArray.join(' ');
                });
            }

            function saveMtState(type, channel) {
                try {
                    sessionStorage.setItem(mtStateKey, JSON.stringify({
                        type: type || '',
                        channel: channel || '',
                    }));
                } catch (e) { }
            }

            function loadMtState() {
                try {
                    const raw = sessionStorage.getItem(mtStateKey);
                    if (!raw) return null;
                    const parsed = JSON.parse(raw);
                    return parsed && typeof parsed === 'object' ? parsed : null;
                } catch (e) {
                    return null;
                }
            }

            function setTinyContent(textareaId, value) {
                if (window.tinymce && tinymce.get(textareaId)) {
                    const editor = tinymce.get(textareaId);
                    const content = value || '';
                    if (content && !/<[a-z][\s\S]*>/i.test(content)) {
                        editor.setContent(content.replace(/\r\n|\r|\n/g, '<br>'));
                    } else {
                        editor.setContent(content);
                    }
                    return;
                }
                const input = document.getElementById(textareaId);
                if (input) input.value = value || '';
            }

            function htmlToPlainText(value) {
                if (!value) return '';
                const withBreaks = String(value)
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/<\/p>/gi, '\n')
                    .replace(/<p[^>]*>/gi, '');
                const temp = document.createElement('div');
                temp.innerHTML = withBreaks;
                return (temp.textContent || temp.innerText || '').replace(/\n{3,}/g, '\n\n').trim();
            }

            function toggleTemplateBodyEditor(form, channel) {
                if (!form) return;
                const bodyInput = form.querySelector('.template-body-input');
                const nameInput = form.querySelector('.template-name-input');
                const nameRequiredMark = form.querySelector('.template-name-required-mark');
                const bodyRequiredMark = form.querySelector('.template-body-required-mark');
                const variableOnlyNote = form.querySelector('.template-variable-only-note');
                if (!bodyInput) return;

                const isEmail = channel === 'email';
                form.noValidate = !isEmail;
                if (nameInput) {
                    nameInput.required = isEmail;
                    if (!isEmail) nameInput.removeAttribute('required');
                    nameInput.setAttribute('aria-required', isEmail ? 'true' : 'false');
                    nameInput.setCustomValidity('');
                }
                bodyInput.required = false;
                bodyInput.removeAttribute('required');
                bodyInput.setAttribute('aria-required', 'false');
                bodyInput.setCustomValidity('');
                if (nameRequiredMark) nameRequiredMark.classList.toggle('is-hidden', !isEmail);
                if (bodyRequiredMark) bodyRequiredMark.classList.toggle('is-hidden', !isEmail);
                bodyInput.readOnly = false;
                bodyInput.classList.remove('is-readonly-field');
                if (variableOnlyNote) {
                    variableOnlyNote.classList.toggle('is-hidden', isEmail);
                }

                if (!window.tinymce) return;

                const editor = tinymce.get(bodyInput.id);
                if (isEmail) {
                    const messageTemplatesTab = document.getElementById('message-templates');
                    const isTemplatesTabVisible = messageTemplatesTab?.classList.contains('active');
                    if (!isTemplatesTabVisible) return;
                    if (!editor) {
                        tinymce.init({
                            license_key: 'gpl',
                            selector: '#' + bodyInput.id,
                            menubar: false,
                            height: 280,
                            plugins: 'lists link table code autoresize',
                            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code',
                        });
                    }
                    return;
                }

                if (editor) {
                    editor.save();
                    editor.remove();
                }
                bodyInput.value = htmlToPlainText(bodyInput.value);
            }

            function ensureTemplateEditorReady(tries = 12) {
                const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
                if (!emailForm) return;

                if (window.tinymce) {
                    toggleTemplateBodyEditor(emailForm, 'email');
                    return;
                }

                if (tries <= 0) return;
                setTimeout(() => ensureTemplateEditorReady(tries - 1), 150);
            }

            function decodeTemplateBody(encodedBody) {
                if (!encodedBody) return '';
                try {
                    const binary = atob(encodedBody);
                    const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
                    return new TextDecoder('utf-8').decode(bytes);
                } catch (error) {
                    return encodedBody;
                }
            }

            function resetAllTemplateForms(type) {
                const currentType = type || defaultTemplateType;
                renderTemplateVariableBadges(currentType);

                templateForms.forEach((form) => {
                    const channel = form.dataset.channel;
                    const channelInput = form.querySelector('.template-channel-input');
                    const typeInput = form.querySelector('input[name="template_type"]');
                    const templateIdInput = form.querySelector('.template-id-input');
                    const nameInput = form.querySelector('.template-name-input');
                    const subjectInput = form.querySelector('.template-subject-input');
                    const waTemplateIdInput = form.querySelector('.template-wa-template-id-input');
                    const externalIdInput = form.querySelector('.template-external-id-input');
                    const senderIdInput = form.querySelector('.template-sender-id-input');
                    const bodyInput = form.querySelector('.template-body-input');
                    const submitBtn = form.querySelector('.template-submit-btn');
                    const editorNote = form.querySelector('.template-editor-note-' + channel);
                    const methodInput = form.querySelector('input[name="_method"]');

                    const typeLabel = templateTypeLabels[currentType] || currentType.replace(/_/g, ' ').toUpperCase();
                    const channelLabel = channel.charAt(0).toUpperCase() + channel.slice(1);

                    if (typeInput) typeInput.value = currentType;
                    if (channelInput) channelInput.value = channel;
                    if (templateIdInput) templateIdInput.value = '';
                    if (methodInput) methodInput.remove();
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Template';
                        if (channel === 'email') submitBtn.className = 'btn btn-primary btn-sm text-white fw-medium px-3 py-2 template-submit-btn';
                        else if (channel === 'whatsapp') submitBtn.className = 'btn btn-success btn-sm text-white fw-medium px-3 py-2 template-submit-btn';
                        else if (channel === 'sms') submitBtn.className = 'btn btn-info btn-sm text-white fw-medium px-3 py-2 template-submit-btn';
                    }
                    if (editorNote) editorNote.textContent = 'One template per type.';
                    if (nameInput) {
                        nameInput.value = '';
                        nameInput.placeholder = typeLabel + ' ' + channelLabel + ' Template';
                    }
                    if (subjectInput) {
                        subjectInput.value = '';
                        subjectInput.placeholder = typeLabel + ' update for @{{ client_name }}';
                    }
                    if (externalIdInput) externalIdInput.value = '';
                    if (waTemplateIdInput) waTemplateIdInput.value = '';
                    if (senderIdInput) senderIdInput.value = '';
                    if (bodyInput) {
                        bodyInput.placeholder = 'Hi @{{ client_name }},\nPlease find the details below.';
                        setTinyContent(bodyInput.id, '');
                    }

                    const contextKey = currentType + '|' + channel;
                    const contextTemplate = templateContextMap[contextKey] || null;
                    if (contextTemplate) {
                        form.action = form.dataset.updateBase + '/' + encodeURIComponent(contextTemplate.templateid);
                        const newMethodInput = document.createElement('input');
                        newMethodInput.type = 'hidden';
                        newMethodInput.name = '_method';
                        newMethodInput.value = 'PATCH';
                        form.appendChild(newMethodInput);

                        if (templateIdInput) templateIdInput.value = contextTemplate.templateid || '';
                        if (nameInput) nameInput.value = contextTemplate.name || '';
                        if (subjectInput) subjectInput.value = contextTemplate.subject || '';
                        if (externalIdInput) externalIdInput.value = contextTemplate.template_id || '';
                        if (waTemplateIdInput) waTemplateIdInput.value = contextTemplate.template_id || '';
                        if (senderIdInput) senderIdInput.value = contextTemplate.sender_id || '';
                        if (bodyInput) setTinyContent(bodyInput.id, contextTemplate.body || '');
                        if (editorNote) editorNote.textContent = 'Editing existing template.';
                        if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Update Template';
                    } else {
                        form.action = form.dataset.storeAction;
                    }

                    toggleTemplateBodyEditor(form, channel);
                });

                saveMtState(currentType);
            }

            function setActiveTab(tabs, matchAttr, value) {
                tabs.forEach((tab) => {
                    const active = tab.dataset[matchAttr] === value;
                    tab.classList.toggle('is-active', active);
                    tab.classList.toggle('active', active);
                    tab.classList.toggle('text-secondary', !active);
                });
            }

            function getCurrentTemplateType() {
                const activeTypeTab = document.querySelector('.mt-type-tab-btn.is-active');
                return activeTypeTab?.dataset.type || defaultTemplateType;
            }

            typeTabs.forEach((tab) => {
                tab.addEventListener('click', function () {
                    setActiveTab(typeTabs, 'type', this.dataset.type);
                    resetAllTemplateForms(this.dataset.type);
                });
            });

            const persistedMtState = loadMtState();
            const initialTemplateType = (oldTemplateType && templateTypeLabels[oldTemplateType]) ?
                oldTemplateType :
                ((persistedMtState?.type && templateTypeLabels[persistedMtState.type]) ? persistedMtState.type :
                    defaultTemplateType);
            setActiveTab(typeTabs, 'type', initialTemplateType);
            resetAllTemplateForms(initialTemplateType);

            document.addEventListener('settings:tab-activated', function (event) {
                if (event.detail?.tabId !== 'message-templates') return;
                const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
                if (!emailForm) return;
                requestAnimationFrame(() => {
                    toggleTemplateBodyEditor(emailForm, 'email');
                });
            });

            const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
            if (window.tinymce && document.querySelector('.template-body-input') && emailForm) {
                toggleTemplateBodyEditor(emailForm, 'email');
            }

            setActiveTab(typeTabs, 'type', initialTemplateType);
            resetAllTemplateForms(initialTemplateType);
            ensureTemplateEditorReady();

            templateForms.forEach((form) => {
                form.addEventListener('submit', function (event) {
                    const channel = form.dataset.channel;
                    const waTemplateIdInput = form.querySelector('.template-wa-template-id-input');
                    const templateIdInput = form.querySelector('.template-external-id-input');
                    const bodyInput = form.querySelector('.template-body-input');
                    const nameInput = form.querySelector('.template-name-input');

                    const isEmail = channel === 'email';
                    form.noValidate = !isEmail;
                    if (bodyInput) {
                        bodyInput.required = false;
                        bodyInput.removeAttribute('required');
                        bodyInput.setAttribute('aria-required', 'false');
                        bodyInput.setCustomValidity('');
                    }
                    if (nameInput) {
                        nameInput.required = isEmail;
                        if (!isEmail) nameInput.removeAttribute('required');
                        nameInput.setAttribute('aria-required', isEmail ? 'true' : 'false');
                        nameInput.setCustomValidity('');
                    }

                    if (channel === 'whatsapp' && waTemplateIdInput && templateIdInput) {
                        templateIdInput.value = waTemplateIdInput.value || '';
                    }
                    if (window.tinymce) tinymce.triggerSave();

                    if (isEmail && bodyInput) {
                        const plainTextBody = String(bodyInput.value || '')
                            .replace(/<br\s*\/?>/gi, '\n')
                            .replace(/<\/p>/gi, '\n')
                            .replace(/<[^>]+>/g, '')
                            .replace(/&nbsp;/gi, ' ')
                            .trim();

                        if (plainTextBody === '') {
                            event.preventDefault();
                            if (typeof showToastDedup === 'function') {
                                showToastDedup('error',
                                    'Message Body is required for Email templates.', 2200);
                            }
                            const editor = window.tinymce ? tinymce.get(bodyInput.id) : null;
                            if (editor) {
                                editor.focus();
                            } else {
                                bodyInput.focus();
                            }
                        }
                    }
                });
            });

            if (mtErrorToast) {
                try {
                    const messageText = String(mtErrorToast);
                    if (typeof showToast === 'function') {
                        showToastDedup('error', messageText, 2000);
                    } else {
                        const container = document.getElementById('app-toast-container') || (function () {
                            const el = document.createElement('div');
                            el.id = 'app-toast-container';
                            el.className = 'app-toast-container';
                            document.body.appendChild(el);
                            return el;
                        })();

                        const toast = document.createElement('div');
                        toast.className = 'app-toast app-toast-error';
                        toast.innerHTML =
                            `<i class="fas fa-exclamation-circle toast-icon"></i><span class="settings-toast-message">${messageText}</span>`;
                        container.appendChild(toast);
                        setTimeout(() => {
                            if (toast.parentNode) {
                                toast.classList.add('app-toast-leaving');
                                setTimeout(() => {
                                    if (toast.parentNode) toast.remove();
                                }, 300);
                            }
                        }, 5000);
                    }
                } catch (e) { }
            }

            async function toggleTermStatusBadge(badgeEl) {
                const url = badgeEl?.dataset?.toggleUrl;
                if (!url) return;

                badgeEl.classList.add('is-updating');
                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to update term status.');
                    }

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        const isActive = !!data.is_active;
                        badgeEl.dataset.isActive = isActive ? '1' : '0';
                        badgeEl.textContent = isActive ? 'Active' : 'Inactive';
                        badgeEl.title = isActive ? 'Click to Deactivate' : 'Click to Activate';
                        badgeEl.classList.toggle('is-active', isActive);
                        badgeEl.classList.toggle('is-inactive', !isActive);

                        badgeEl.classList.toggle('bg-success-subtle', isActive);
                        badgeEl.classList.toggle('text-success', isActive);
                        badgeEl.classList.toggle('border-success-subtle', isActive);
                        badgeEl.classList.toggle('bg-secondary-subtle', !isActive);
                        badgeEl.classList.toggle('text-secondary', !isActive);
                        badgeEl.classList.toggle('border-secondary-subtle', !isActive);

                        try {
                            const messageText = data.message || 'Term status updated.';
                            if (typeof showToast === 'function') {
                                showToastDedup('success', messageText);
                            }
                        } catch (e) { }
                    } else {
                        window.location.reload();
                    }
                } catch (e) {
                    try {
                        if (typeof showToast === 'function') {
                            showToastDedup('error', e.message || 'Failed to update term status.');
                        }
                    } catch (_e) { }
                } finally {
                    badgeEl.classList.remove('is-updating');
                }
            }

            document.querySelectorAll('.js-term-status-badge').forEach((badge) => {
                badge.addEventListener('click', async function () {
                    await toggleTermStatusBadge(this);
                });
                badge.addEventListener('keydown', async function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    await toggleTermStatusBadge(this);
                });
            });
        });
    </script>

    @endsection
