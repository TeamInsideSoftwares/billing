@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div></div>
</section>

<!-- Tabs Wrapper -->
<div style="padding: 10px 0;">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="personal">Personal Info</button>
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
                <label>City</label>
                <input type="text" name="city" value="{{ old('city', $account->city ?? '') }}">
            </div>

            <div>
                <label>Country</label>
                <input type="text" name="country" value="{{ old('country', $account->country ?? '') }}">
            </div>
            <div>
                <label>Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $account->postal_code ?? '') }}">
            </div>
            <div>
                <label>FY Start Month</label>
                <input type="month" name="fy_startdate" value="{{ old('fy_startdate', $account->fy_startdate ? date('Y-').substr($account->fy_startdate, 0, 2) : date('Y-04')) }}">
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
                    <input type="text" name="key" value="{{ old('key', $editingSetting->setting_key ?? '') }}" placeholder="e.g. STRIPE_API_KEY" required style="padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1;">
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
        <form method="POST" action="#" class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            @csrf
            @if(isset($editingBillingDetail))
                @method('PUT')
                <input type="hidden" name="account_qdid" value="{{ $editingBillingDetail->account_qdid }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">
            <div>
                <label class="required">Serial Number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $editingBillingDetail->serial_number ?? '') }}" required>
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
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingBillingDetail->gstin ?? '') }}">
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingBillingDetail->tin ?? '') }}">
            </div>
            <div style="grid-column: span 2;">
                <label>Terms &amp; Conditions</label>
                <textarea name="terms_conditions" rows="4" style="width: 100%; padding: 0.45rem 0.6rem;">{{ old('terms_conditions', $editingBillingDetail->terms_conditions ?? '') }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-button">{{ isset($editingBillingDetail) ? 'Update' : 'Add' }} Billing Detail</button>
                @if(isset($editingBillingDetail))
                    <a href="{{ route('settings.index') }}#billing-details" class="text-link" style="margin-left: 1rem;">Cancel</a>
                @endif
            </div>
        </form>

        <div style="margin-top: 2rem;">
            <h5>Billing Details List</h5>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th>Name</th>
                        <th>GSTIN</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($billingDetails ?? [] as $bd)
                        <tr>
                            <td>{{ $bd->serial_number }}</td>
                            <td>{{ $bd->billing_name }}</td>
                            <td>{{ $bd->gstin ?? '-' }}</td>
                            <td>
                                <a href="{{ route('settings.index', ['edit_bd' => $bd->account_qdid]) }}#billing-details" class="text-link">Edit</a>
                                <form method="POST" action="{{ route('billing-details.destroy', $bd->account_qdid) }}" style="display: inline; margin-left: 1rem;" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-link" style="background: none; border: none; color: #ef4444;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No billing details added</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
        <form method="POST" action="#" class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            @csrf
            @if(isset($editingQuotationDetail))
                @method('PUT')
                <input type="hidden" name="account_qd" value="{{ $editingQuotationDetail->account_qd }}">
            @endif
            <input type="hidden" name="accountid" value="{{ $account->accountid }}">
            <div>
                <label class="required">Serial Number</label>
                <input type="text" name="serial_number" value="{{ old('serial_number', $editingQuotationDetail->serial_number ?? '') }}" required>
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
                <label>GSTIN</label>
                <input type="text" name="gstin" value="{{ old('gstin', $editingQuotationDetail->gstin ?? '') }}">
            </div>
            <div>
                <label>TIN</label>
                <input type="text" name="tin" value="{{ old('tin', $editingQuotationDetail->tin ?? '') }}">
            </div>
            <div style="grid-column: span 2;">
                <label>Terms &amp; Conditions</label>
                <textarea name="terms_conditions" rows="4" style="width: 100%; padding: 0.45rem 0.6rem;">{{ old('terms_conditions', $editingQuotationDetail->terms_conditions ?? '') }}</textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-button">{{ isset($editingQuotationDetail) ? 'Update' : 'Add' }} Quotation Detail</button>
                @if(isset($editingQuotationDetail))
                    <a href="{{ route('settings.index') }}#quotation-details" class="text-link" style="margin-left: 1rem;">Cancel</a>
                @endif
            </div>
        </form>

        <div style="margin-top: 2rem;">
            <h5>Quotation Details List</h5>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th>Name</th>
                        <th>GSTIN</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quotationDetails ?? [] as $qd)
                        <tr>
                            <td>{{ $qd->serial_number }}</td>
                            <td>{{ $qd->quotation_name }}</td>
                            <td>{{ $qd->gstin ?? '-' }}</td>
                            <td>
                                <a href="{{ route('settings.index', ['edit_qd' => $qd->account_qd]) }}#quotation-details" class="text-link">Edit</a>
                                <form method="POST" action="{{ route('quotation-details.destroy', $qd->account_qd) }}" style="display: inline; margin-left: 1rem;" onsubmit="return confirm('Delete?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-link" style="background: none; border: none; color: #ef4444;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No quotation details added</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
});
</script>

@endsection