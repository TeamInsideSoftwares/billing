@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #475569;">
            <i class="fas fa-file-invoice" style="color: #f59e0b; margin-right: 0.5rem;"></i>
            Create Proforma Invoice
        </h3>
        <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: #64748b;">
            <i class="fas fa-info-circle" style="margin-right: 0.3rem;"></i>
            This will create a Proforma Invoice. You can convert it to a Tax Invoice later.
        </p>
    </div>
    <a href="{{ $clientId ? route('orders.index', ['c' => $clientId]) : route('invoices.index') }}" class="text-link">&larr; Back</a>
</section>

{{-- 
    MULTI-STEP INVOICE FORM ARCHITECTURE
    ====================================
    This form uses the InvoiceStepManager class for modular, extensible step navigation.
    
    Current Steps:
    - Step 1: Client & Source Selection (steps/step1-client-source.blade.php)
    - Step 2: Items & Details (steps/step2-items.blade.php)
    - Step 3: Terms & Preview (steps/step3-terms.blade.php)
    
    To add Step 4, 5, etc.:
    1. Create partial: steps/step4-{name}.blade.php
    2. Include it below
    3. Update InvoiceStepManager totalSteps parameter
    4. Add validation in validateStep() method
    5. See ADDING_STEPS.md for detailed instructions
--}}

<section class="panel-card" style="padding: 1.5rem;">
    {{-- Optional: Step Indicator (uncomment to enable) --}}
    {{-- @include('invoices.components.step-indicator', ['totalSteps' => 3]) --}}

    <form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm">
        @csrf

        @if ($errors->any())
            <div style="margin-bottom: 1.25rem; padding: 0.9rem 1rem; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; border-radius: 10px;">
                <strong style="display: block; margin-bottom: 0.4rem;">
                    @if($errors->has('general'))
                        Error: {{ $errors->first('general') }}
                    @else
                        Fix these issues before creating the invoice:
                    @endif
                </strong>
                @unless($errors->has('general'))
                <ul style="margin: 0; padding-left: 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @endunless
            </div>
        @endif

        {{-- STEP 1: Client & Source Selection --}}
        @include('invoices.steps.step1-client-source')

        {{-- STEP 2: Items & Details --}}
        @include('invoices.steps.step2-items')

        {{-- STEP 3: Terms & Preview --}}
        @include('invoices.steps.step3-terms')

        {{-- 
            TO ADD STEP 4:
            1. Create: resources/views/invoices/steps/step4-{name}.blade.php
            2. Uncomment the line below
            3. Update InvoiceStepManager totalSteps to 4
            4. Add navigation buttons in your step4 partial
        --}}
        {{-- @include('invoices.steps.step4-{name}') --}}

        {{-- 
            TO ADD STEP 5:
            Follow the same pattern as Step 4
        --}}
        {{-- @include('invoices.steps.step5-{name}') --}}

    </form>
</section>

    <style>
/* Step-specific styles */
.invoice-step {
    transition: opacity 0.3s ease;
}

.invoice-step[style*="display: none"] {
    opacity: 0;
}

.invoice-step:not([style*="display: none"]) {
    opacity: 1;
}

/* Existing styles */
.invoice-meta-card { padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.invoice-meta-label, .field-label.small { display: block; margin-bottom: 0.35rem; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: #64748b; }
.invoice-meta-value { color: #1e293b; font-size: 0.95rem; }
.field-label { display: block; margin-bottom: 0.45rem; font-size: 0.85rem; font-weight: 600; color: #475569; }
.source-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
.invoice-source-card { position: relative; display: flex; flex-direction: column; gap: 0.45rem; padding: 1rem 1.1rem; border: 1px solid #dbe4ee; border-radius: 14px; background: #ffffff; cursor: pointer; transition: 0.2s ease; }
.invoice-source-card:hover { border-color: #93c5fd; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); }
.invoice-source-card input { position: absolute; opacity: 0; pointer-events: none; }
.invoice-source-card:has(input:checked) { border-color: #2563eb; background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%); box-shadow: 0 12px 32px rgba(37, 99, 235, 0.12); }
.source-icon { width: 42px; height: 42px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; }
.invoice-source-card strong { color: #1e293b; }
.workflow-panel { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0; }
.panel-heading-row { margin-bottom: 0.8rem; }
.table-shell { border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; background: #ffffff; }
.empty-state { padding: 1.4rem; text-align: center; color: #64748b; font-size: 0.88rem; }
.builder-card { padding: 1rem; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.manual-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0.75rem; align-items: end; }
.totals-card { padding: 1rem; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
.total-row { display: flex; justify-content: space-between; gap: 1rem; margin-bottom: 0.55rem; font-size: 0.9rem; color: #475569; }
.total-row:last-child { margin-bottom: 0; }
.total-row-grand { padding-top: 0.7rem; border-top: 1px solid #cbd5e1; font-size: 1rem; font-weight: 700; color: #1e293b; }

.status-pill.paid { background: #dcfce7; color: #166534; }
.status-pill.unpaid { background: #fee2e2; color: #991b1b; }
.status-pill.partially-paid { background: #fef3c7; color: #92400e; }

@media (max-width: 1100px) { .manual-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 720px) { .manual-grid { grid-template-columns: 1fr; } }
</style>

<script>
(function () {
    // DOM Elements
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const clientSelect = document.getElementById('clientid');
    const invoiceForm = document.getElementById('invoiceForm');
    const existingInvoicesSection = document.getElementById('existingInvoicesSection');
    const clientInvoicesAccordion = document.getElementById('clientInvoicesAccordion');
    const noInvoicesMessage = document.getElementById('noInvoicesMessage');
    const sourceSelectionSection = document.getElementById('sourceSelectionSection');
    const btnNextToStep2 = document.getElementById('btnNextToStep2');
    const btnBackToStep1 = document.getElementById('btnBackToStep1');
    const btnNextToStep3 = document.getElementById('btnNextToStep3');
    const btnBackToStep2 = document.getElementById('btnBackToStep2');
    const ordersSection = document.getElementById('ordersSection');
    const ordersBody = document.getElementById('ordersBody');
    const noOrdersMessage = document.getElementById('noOrdersMessage');
    const renewalSection = document.getElementById('renewalSection');
    const renewalBody = document.getElementById('renewalBody');
    const noRenewalMessage = document.getElementById('noRenewalMessage');
    const renewalPicker = document.getElementById('renewalPicker');
    const manualItemsSection = document.getElementById('manualItemsSection');
    const itemsSection = document.getElementById('itemsSection');
    const itemsBody = document.getElementById('itemsBody');
    const manualItemsBody = document.getElementById('manualItemsBody');
    const manualItemsTable = document.getElementById('manualItemsTable');
    const manualItemsEmpty = document.getElementById('manualItemsEmpty');
    const manualSummary = document.getElementById('manualOrderSummary');

    const orderIdInput = document.getElementById('orderid');
    const subtotalInput = document.getElementById('subtotal');
    const taxTotalInput = document.getElementById('tax_total');
    const grandTotalInput = document.getElementById('grand_total');
    const itemsDataInput = document.getElementById('items_data');
    const currencyCodeInput = document.getElementById('currency_code');
    const addManualItemBtn = document.getElementById('addManualItemBtn');
    const termCheckboxes = document.querySelectorAll('.term-checkbox');
    const previewContent = document.getElementById('previewContent');

    @php
        $accountDataArr = [
            'name' => optional($account)->name,
            'logo' => ($account && $account->logo_path) ? asset($account->logo_path) : null,
            'billing' => [
                'name' => optional($accountBillingDetail)->billing_name ?? optional($account)->name,
                'address' => optional($accountBillingDetail)->address ?? '',
                'city' => optional($accountBillingDetail)->city ?? '',
                'state' => optional($accountBillingDetail)->state ?? '',
                'postal_code' => optional($accountBillingDetail)->postal_code ?? '',
                'country' => optional($accountBillingDetail)->country ?? '',
                'gstin' => optional($accountBillingDetail)->gstin ?? '',
                'signatory' => optional($accountBillingDetail)->authorize_signatory ?? '',
                'signature' => (optional($accountBillingDetail)->signature_upload) ? asset($accountBillingDetail->signature_upload) : null,
            ]
        ];
    @endphp
    const accountData = {!! json_encode($accountDataArr) !!};

    // Constants
    const frequencyOptions = ['one-time', 'daily', 'weekly', 'bi-weekly', 'monthly', 'quarterly', 'semi-annually', 'yearly'];
    const frequencyLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };

    @php
        $taxOptionsArr = ($taxes ?? collect())->map(fn ($tax) => ['name' => $tax->tax_name, 'rate' => (float) $tax->rate])->values();
    @endphp
    const taxOptions = {!! json_encode($taxOptionsArr) !!};

    // State
    let selectedClientId = clientSelect.value || null;
    let clientCurrency = currencyCodeInput.value || 'INR';
    let invoiceItems = [];
    let manualItems = [];
    let manualItemCounter = 0;
    let editingManualItemId = null;
    const STORAGE_KEY = 'invoice_create_state';

    // Auto-select client and order from URL parameters
    @if($clientId)
        selectedClientId = '{{ $clientId }}';
        clientSelect.value = '{{ $clientId }}';
        clientSelect.dispatchEvent(new Event('change'));
    @endif

    @if($orderId)
        // Wait a bit for client change to process, then auto-select order
        setTimeout(() => {
            // Set invoice source to "From Orders"
            const ordersRadio = document.querySelector('input[name="invoice_for"][value="orders"]');
            if (ordersRadio) {
                ordersRadio.checked = true;
                ordersRadio.dispatchEvent(new Event('change'));
            }

            // Auto-select the order after orders load
            setTimeout(() => {
                const orderCheckbox = document.querySelector(`input[name="selected_orders[]"][value="{{ $orderId }}"]`);
                if (orderCheckbox) {
                    orderCheckbox.checked = true;
                    orderCheckbox.dispatchEvent(new Event('change'));
                }
            }, 500);
        }, 300);
    @endif

    // ============================================
    // STEP NAVIGATION WITH URL HASH (PERSISTENT)
    // ============================================
    
    /**
     * Show a specific step and hide others
     * Updates URL hash for persistence across refresh
     */
    function showStep(stepNumber) {
        // Hide all steps
        step1.style.display = 'none';
        step2.style.display = 'none';
        step3.style.display = 'none';

        // Show the requested step
        let targetStep = null;
        switch(stepNumber) {
            case 1: targetStep = step1; break;
            case 2: targetStep = step2; break;
            case 3: targetStep = step3; break;
            default: targetStep = step1; stepNumber = 1;
        }

        if (targetStep) {
            targetStep.style.display = 'block';
            
            // Update URL hash for persistence
            const newHash = `#step-${stepNumber}`;
            if (window.location.hash !== newHash) {
                history.replaceState(null, '', newHash);
            }

            // Step-specific initialization
            if (stepNumber === 2) {
                // Activate the selected source when showing Step 2
                const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
                console.log('Step 2: activating source:', source);
                if (source) {
                    activateSource(source);
                } else {
                    console.warn('Step 2: No source selected!');
                }
            } else if (stepNumber === 3) {
                updateInvoicePreview();
            }

            // Scroll to top
            window.scrollTo(0, 0);
        }
    }

    /**
     * Get current step from URL hash
     */
    function getCurrentStepFromHash() {
        const hash = window.location.hash;
        const match = hash.match(/^#step-(\d+)$/);
        if (match) {
            const step = parseInt(match[1]);
            if (step >= 1 && step <= 3) {
                return step;
            }
        }
        return 1; // Default to step 1
    }

    /**
     * Navigate to next step with validation
     */
    function navigateToStep(stepNumber) {
        // Validate before moving forward
        if (stepNumber > getCurrentStepFromHash()) {
            if (!validateCurrentStep()) {
                return;
            }
        }

        showStep(stepNumber);
    }

    /**
     * Validate current step before proceeding
     */
    function validateCurrentStep() {
        const currentStep = getCurrentStepFromHash();
        
        if (currentStep === 1) {
            if (!selectedClientId) {
                alert('Please select a client first.');
                return false;
            }
            const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
            if (!source) {
                alert('Please choose an invoice source.');
                return false;
            }
        } else if (currentStep === 2) {
            const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
            let hasItems = false;
            if (source === 'without_orders') {
                hasItems = manualItems.length > 0;
            } else if (source === 'orders' || source === 'renewal') {
                hasItems = invoiceItems.length > 0;
            }
            
            if (!hasItems) {
                alert('Please add at least one item before proceeding.');
                return false;
            }
        }
        
        return true;
    }

    // ============================================
    // END STEP NAVIGATION FUNCTIONS
    // ============================================

    // Load state from localStorage - ONLY restore if user explicitly saved
    function loadState() {
        try {
            const savedState = localStorage.getItem(STORAGE_KEY);
            if (!savedState) return null;

            const state = JSON.parse(savedState);
            if (!state.clientId || !state.explicitlySaved) return null;

            console.log('Restoring saved state:', state);

            // Restore client selection
            selectedClientId = state.clientId;
            clientSelect.value = state.clientId;
            clientCurrency = state.clientCurrency || 'INR';
            currencyCodeInput.value = clientCurrency;
            existingInvoicesSection.style.display = 'block';
            sourceSelectionSection.style.display = 'block';
            loadInvoicesForClient(state.clientId);

            // Restore source selection
            if (state.invoiceFor) {
                const radio = document.querySelector(`input[name="invoice_for"][value="${state.invoiceFor}"]`);
                if (radio) radio.checked = true;
            }

            // Restore items
            if (state.manualItems && state.manualItems.length > 0) {
                manualItems = state.manualItems;
                manualItemCounter = state.manualItemCounter || manualItems.length;
                renderManualItems();
            }
            if (state.invoiceItems && state.invoiceItems.length > 0) {
                invoiceItems = state.invoiceItems;
                renderItems();
            }
            if (state.orderId) {
                orderIdInput.value = state.orderId;
            }

            return state;
        } catch (e) {
            console.warn('Could not restore invoice state:', e);
            return null;
        }
    }

    // Save state - only called when user explicitly clicks Save Progress
    function saveState() {
        try {
            const currentStep = step2.style.display === 'block' ? 2 : 1;
            const invoiceFor = document.querySelector('input[name="invoice_for"]:checked')?.value || '';
            
            const state = {
                explicitlySaved: true,
                currentStep,
                clientId: selectedClientId,
                clientCurrency,
                invoiceFor,
                orderId: orderIdInput.value,
                manualItems: currentStep === 2 && invoiceFor === 'without_orders' ? manualItems : [],
                invoiceItems: currentStep === 2 && invoiceFor !== 'without_orders' ? invoiceItems : [],
                manualItemCounter
            };
            
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            showSaveSuccess();
        } catch (e) {
            console.warn('Could not save invoice state:', e);
        }
    }
    
    // Show save success on button
    function showSaveSuccess() {
        const saveBtn = document.getElementById('saveStateBtn');
        if (!saveBtn) return;
        
        const originalHTML = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-check" style="margin-right: 0.4rem;"></i>Saved!';
        saveBtn.style.background = '#10b981';
        
        setTimeout(() => {
            saveBtn.innerHTML = originalHTML;
            saveBtn.style.background = '';
        }, 2000);
    }
    
    // Manual save button handler
    document.getElementById('saveStateBtn').addEventListener('click', function() {
        saveState();
    });

    // Clear state on successful form submission
    function clearState() {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {
            // Ignore
        }
    }

    // Helper Functions
    function formatMoney(amount) {
        return `${clientCurrency} ${Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function renderTaxSelect(selectedRate, inputClass, attributes = '') {
        const normalizedRate = Number(selectedRate || 0);
        const options = [`<option value="0" ${normalizedRate === 0 ? 'selected' : ''}>No Tax</option>`];
        taxOptions.forEach((tax) => {
            const rate = Number(tax.rate || 0);
            options.push(`<option value="${rate}" ${rate === normalizedRate ? 'selected' : ''}>${tax.name} (${rate.toFixed(2)}%)</option>`);
        });
        return `<select class="form-input ${inputClass}" ${attributes}>${options.join('')}</select>`;
    }

    function calculateLineTotal(quantity, unitPrice, users, frequency, duration) {
        let total = (Number(quantity) || 0) * (Number(unitPrice) || 0) * Math.max(1, Number(users) || 1);
        if (frequency && frequency !== 'one-time' && duration) {
            const durationNumber = Number(duration) || 0;
            if (durationNumber > 0) total *= durationNumber;
        }
        return total;
    }

    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration || frequency === 'one-time') return '';
        const start = new Date(startDate);
        const durationNumber = Number(duration);
        if (Number.isNaN(start.getTime()) || durationNumber <= 0) return '';
        const end = new Date(start);
        switch (frequency) {
            case 'daily': end.setDate(end.getDate() + durationNumber); break;
            case 'weekly': end.setDate(end.getDate() + (durationNumber * 7)); break;
            case 'bi-weekly': end.setDate(end.getDate() + (durationNumber * 14)); break;
            case 'monthly': end.setMonth(end.getMonth() + durationNumber); break;
            case 'quarterly': end.setMonth(end.getMonth() + (durationNumber * 3)); break;
            case 'semi-annually': end.setMonth(end.getMonth() + (durationNumber * 6)); break;
            case 'yearly': end.setFullYear(end.getFullYear() + durationNumber); break;
            default: return '';
        }
        return end.toISOString().split('T')[0];
    }

    function updateFinalSubmitButton() {
        const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
        let hasItems = false;
        if (source === 'without_orders') {
            hasItems = manualItems.length > 0;
        } else if (source === 'orders' || source === 'renewal') {
            hasItems = invoiceItems.length > 0;
        } else {
            // Fallback: enable if either list has items
            hasItems = manualItems.length > 0 || invoiceItems.length > 0;
        }
        btnNextToStep3.disabled = !hasItems;
    }

    function updateInvoicePreview() {
        const clientOption = clientSelect.options[clientSelect.selectedIndex];
        const clientName = clientOption ? clientOption.text : 'Client Name';
        const invoiceTitle = document.getElementById('invoice_title').value;
        const invoiceNumber = '{{ $nextInvoiceNumber }}';
        const issueDate = document.getElementById('issue_date').value;
        const dueDate = document.getElementById('due_date').value;
        const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
        const items = source === 'without_orders' ? manualItems : invoiceItems;
        const subtotal = Number(subtotalInput.value);
        const taxTotal = Number(taxTotalInput.value);
        const grandTotal = Number(grandTotalInput.value);
        const notes = document.getElementById('notes').value;
        
        // Get terms from checked checkboxes
        const terms = Array.from(document.querySelectorAll('.term-checkbox'))
            .filter(cb => cb.checked)
            .map(cb => cb.value.replace(/\n/g, '<br>'))
            .join('<br><br>');

        let itemsHtml = '';
        items.forEach((item, index) => {
            const qty = Number(item.quantity) || 0;
            const price = Number(item.unit_price) || 0;
            const users = Number(item.no_of_users) || 1;
            const lineTotal = calculateLineTotal(qty, price, users, item.frequency, item.duration);

            itemsHtml += `
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;">${index + 1}</td>
                    <td style="padding: 0.75rem 0.5rem; border-right: 1px solid #e5e7eb;">
                        <strong style="display: block;">${item.item_name}</strong>
                        ${item.frequency && item.frequency !== 'one-time' ? `<div style="font-size: 0.72rem; color: #64748b; margin-top: 0.2rem;">${frequencyLabels[item.frequency] || item.frequency} × ${item.duration || 1}</div>` : ''}
                    </td>
                    <td style="padding: 0.75rem 0.5rem; text-align: center; border-right: 1px solid #e5e7eb;">${qty.toLocaleString()}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; border-right: 1px solid #e5e7eb;">${clientCurrency} ${price.toLocaleString()}</td>
                    <td style="padding: 0.75rem 0.5rem; text-align: right; font-weight: 600;">${clientCurrency} ${lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                </tr>
            `;
        });

        const previewHtml = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 3px solid #2563eb;">
                <div style="flex: 1;">
                    ${accountData.logo ? `<div style="margin-bottom: 1rem;"><img src="${accountData.logo}" style="max-width: 150px; max-height: 60px; object-fit: contain;"></div>` : ''}
                    <h2 style="margin: 0; font-size: 1.25rem;">${accountData.billing.name}</h2>
                    <p style="margin: 0.2rem 0; font-size: 0.8rem; color: #64748b;">${accountData.billing.address}</p>
                    <p style="margin: 0.2rem 0; font-size: 0.8rem; color: #64748b;">${accountData.billing.city}, ${accountData.billing.state} ${accountData.billing.postal_code}</p>
                    ${accountData.billing.gstin ? `<p style="margin: 0.5rem 0 0.2rem 0; font-size: 0.8rem;"><strong>GSTIN:</strong> ${accountData.billing.gstin}</p>` : ''}
                </div>
                <div style="text-align: right; min-width: 200px;">
                    <h1 style="margin: 0; font-size: 1.75rem; color: #2563eb; font-weight: 700;">PROFORMA</h1>
                    <div style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; margin-top: 1rem; text-align: right; display: inline-block;">
                        <p style="margin: 0.2rem 0; font-size: 0.85rem;"><strong>Invoice #:</strong> ${invoiceNumber}</p>
                        <p style="margin: 0.2rem 0; font-size: 0.85rem;"><strong>Date:</strong> ${issueDate}</p>
                        <p style="margin: 0.2rem 0; font-size: 0.85rem;"><strong>Due:</strong> ${dueDate}</p>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: #64748b; text-transform: uppercase;">Bill To:</h3>
                <h4 style="margin: 0; font-size: 1.1rem;">${clientName}</h4>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #2563eb; color: white;">
                        <th style="padding: 0.5rem; text-align: left;">#</th>
                        <th style="padding: 0.5rem; text-align: left;">Description</th>
                        <th style="padding: 0.5rem; text-align: center;">Qty</th>
                        <th style="padding: 0.5rem; text-align: right;">Price</th>
                        <th style="padding: 0.5rem; text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>

            <div style="display: flex; justify-content: flex-end;">
                <div style="min-width: 250px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Subtotal</span><strong>${clientCurrency} ${subtotal.toLocaleString()}</strong></div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Tax</span><strong>${clientCurrency} ${taxTotal.toLocaleString()}</strong></div>
                    <div style="display: flex; justify-content: space-between; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 2px solid #2563eb; font-size: 1.1rem; font-weight: 700; color: #2563eb;">
                        <span>Total</span><span>${clientCurrency} ${grandTotal.toLocaleString()}</span>
                    </div>
                </div>
            </div>

            ${notes ? `<div style="margin-top: 2rem;"><h4 style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">Notes:</h4><p style="margin: 0; font-size: 0.85rem; color: #64748b;">${notes}</p></div>` : ''}
            
            ${terms ? `<div style="margin-top: 2rem; padding: 1.25rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                <h4 style="margin: 0 0 0.75rem 0; font-size: 0.95rem; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 0.4rem;">Terms & Conditions</h4>
                <div style="margin: 0; font-size: 0.82rem; color: #475569; line-height: 1.5;">${terms}</div>
            </div>` : ''}

            ${accountData.billing.signatory ? `
                <div style="margin-top: 3rem; text-align: right;">
                    ${accountData.billing.signature ? `<img src="${accountData.billing.signature}" style="max-width: 120px; max-height: 50px; margin-bottom: 0.5rem;">` : ''}
                    <div style="border-top: 1px solid #1e293b; display: inline-block; padding-top: 0.5rem; min-width: 180px;">
                        <p style="margin: 0; font-weight: 600;">${accountData.billing.signatory}</p>
                        <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Authorized Signatory</p>
                    </div>
                </div>
            ` : ''}
        `;
        previewContent.innerHTML = previewHtml;
    }

    function setTotals(subtotal, taxTotal) {
        const grandTotal = subtotal + taxTotal;
        subtotalInput.value = subtotal.toFixed(2);
        taxTotalInput.value = taxTotal.toFixed(2);
        grandTotalInput.value = grandTotal.toFixed(2);
        const sd = document.getElementById('subtotalDisplay');
        const td = document.getElementById('taxDisplay');
        const gd = document.getElementById('grandTotalDisplay');
        if (sd) sd.textContent = formatMoney(subtotal);
        if (td) td.textContent = formatMoney(taxTotal);
        if (gd) gd.textContent = formatMoney(grandTotal);
        const ms = document.getElementById('manualSubtotal');
        const mt = document.getElementById('manualTaxTotal');
        const mg = document.getElementById('manualGrandTotal');
        if (ms) ms.textContent = formatMoney(subtotal);
        if (mt) mt.textContent = formatMoney(taxTotal);
        if (mg) mg.textContent = formatMoney(grandTotal);
    }

    // Step 1 Functions
    function loadInvoicesForClient(clientId) {
        clientInvoicesAccordion.innerHTML = '<div style="padding: 1rem; text-align: center; color: #94a3b8;">Loading invoices...</div>';
        noInvoicesMessage.style.display = 'none';
        
        fetch(`{{ route('invoices.index') }}?clientid=${clientId}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => response.json())
            .then((data) => {
                const invoices = data.invoices || [];
                if (invoices.length === 0) {
                    clientInvoicesAccordion.innerHTML = '';
                    noInvoicesMessage.style.display = 'block';
                    return;
                }

                let accordionHtml = '';
                invoices.forEach((invoice) => {
                    const paymentStatus = invoice.payment_status || 'unpaid';
                    const paymentStatusClass = paymentStatus.toLowerCase().replace(/\s+/g, '-');
                    const headerTitle = invoice.title ? `${invoice.title} (${invoice.number || ''})` : (invoice.number || 'Untitled Invoice');
                    const issueDate = invoice.issue_date || '-';
                    const dueDate = invoice.due_date || '-';
                    const itemsHtml = (invoice.items || []).map((item) => {
                        // Build details only for fields that have values
                        const details = [];
                        if (item.price && item.price !== '0.00') details.push(`Unit: ${item.price}`);
                        if (item.tax_rate) details.push(`Tax: ${item.tax_rate}%`);
                        if (item.users && item.users > 1) details.push(`Users: ${item.users}`);
                        if (item.frequency) details.push(`Freq: ${item.frequency}`);
                        if (item.duration) details.push(`Dur: ${item.duration}`);

                        const dates = [];
                        if (item.start_date) dates.push(`Start: ${item.start_date}`);
                        if (item.end_date) dates.push(`End: ${item.end_date}`);

                        return `
                        <div style="padding: 0.6rem 0; border-bottom: 1px dashed #e2e8f0; font-size: 0.8rem;">
                            <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                <span style="color: #334155; font-weight: 600;">${item.name || 'Item'} (x${item.qty || item.quantity || 1})</span>
                                <strong style="color: #1e293b;">${item.total || '-'}</strong>
                            </div>
                            ${details.length ? `<div style="margin-top: 0.25rem; color: #64748b; font-size: 0.74rem;">${details.join(' | ')}</div>` : ''}
                            ${dates.length ? `<div style="margin-top: 0.18rem; color: #94a3b8; font-size: 0.72rem;">${dates.join(' | ')}</div>` : ''}
                        </div>`;
                    }).join('') || '<div style="padding: 0.75rem 0; color: #94a3b8; font-style: italic; font-size: 0.82rem;">No items found</div>';

                    accordionHtml += `
                        <details class="category-accordion">
                            <summary class="accordion-header" style="padding: 0.65rem 0.9rem;">
                                <span style="display: inline-flex; flex-direction: column; gap: 0.1rem;">
                                    <span class="category-title" style="font-size: 0.84rem;">${headerTitle}</span>
                                    <span style="font-size: 0.72rem; color: #64748b; font-weight: 500;">Issue: ${issueDate}</span>
                                </span>
                                <span style="display: inline-flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                                    <span class="service-count">${invoice.amount || '-'}</span>
                                    <span class="status-pill ${paymentStatusClass}" style="font-size: 0.72rem;">${paymentStatus}</span>
                                    <span class="accordion-icon"></span>
                                </span>
                            </summary>
                            <div class="accordion-content">
                                <div style="padding: 0.65rem 0.9rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; flex-wrap: wrap; background: #f8fafc;">
                                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                        <span style="font-size: 0.74rem; color: #64748b;">Issue: ${issueDate} | Due: ${dueDate}</span>
                                        <span style="font-size: 0.7rem; padding: 0.2rem 0.5rem; background: #dbeafe; color: #1e40af; border-radius: 4px; font-weight: 600;">Proforma</span>
                                        <span style="font-size: 0.74rem; color: #64748b;">For: ${invoice.invoice_for || '-'}</span>
                                    </div>
                                    <div style="display: flex; gap: 0.4rem; align-items: center;">
                                        <button type="button" class="edit-invoice-btn" data-id="${invoice.record_id}" data-invoice='${JSON.stringify(invoice).replace(/'/g, "&#39;")}' style="font-size: 0.8rem; padding: 0.35rem 0.7rem; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </div>
                                </div>
                                <div style="padding: 0.3rem 0.9rem 0.8rem;" class="items-display">
                                    ${itemsHtml}
                                </div>
                                <div class="inline-edit-section" id="inline-edit-section-${invoice.record_id}" style="display: none; padding: 1rem 0.9rem;">
                                    <div style="margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                        <h5 style="margin: 0; font-size: 0.95rem; color: #334155; font-weight: 600;">
                                            <i class="fas fa-edit" style="margin-right: 0.4rem; color: #3b82f6;"></i>Edit Items
                                        </h5>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button type="button" class="update-inline-btn" data-id="${invoice.record_id}" style="font-size: 0.78rem; padding: 0.35rem 0.8rem; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                            <button type="button" class="close-inline-edit" data-id="${invoice.record_id}" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 1.1rem;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="inline-items-container"></div>
                                </div>
                            </div>
                        </details>
                    `;
                });

                clientInvoicesAccordion.innerHTML = accordionHtml;
                
            })
            .catch(() => {
                clientInvoicesAccordion.innerHTML = '<div style="padding: 1rem; text-align: center; color: #ef4444;">Failed to load invoices.</div>';
            });
    }

    // Inline Edit - Show editable items table directly in the accordion
    function showInlineEdit(invoiceData) {
        const invoiceId = invoiceData.record_id;
        const editSection = document.getElementById(`inline-edit-section-${invoiceId}`);
        const itemsContainer = editSection.querySelector('.inline-items-container');
        const editBtn = document.querySelector(`.edit-invoice-btn[data-id="${invoiceId}"]`);

        // Toggle visibility
        if (editSection.style.display === 'block') {
            // Hide
            editSection.style.display = 'none';
            editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
            return;
        }

        // Show the edit section
        editSection.style.display = 'block';
        editBtn.innerHTML = '<i class="fas fa-check"></i> Editing...';

        // Map invoice items to editable format - handle various field name variations
        const items = (invoiceData.items || []).map((item, index) => {

            // Safely parse numeric values
            const parseNumber = (value, defaultValue = 0) => {
                if (value === null || value === undefined || value === '') return defaultValue;
                const num = Number(value);
                return isNaN(num) ? defaultValue : num;
            };

            const mappedItem = {
                index: index,
                itemid: item.itemid || item.service_id || item.item_id || '',
                item_name: item.item_name || item.service_name || item.name || item.description || 'Item',
                quantity: parseNumber(item.quantity || item.qty || item.q, 1),
                unit_price: parseNumber(item.unit_price || item.price || item.rate || item.selling_price, 0),
                tax_rate: parseNumber(item.tax_rate || item.tax || item.tax_percentage, 0),
                frequency: item.frequency || item.freq || item.billing_frequency || '',
                duration: (item.duration !== null && item.duration !== undefined) ? item.duration : '',
                no_of_users: parseNumber(item.no_of_users || item.users || item.user_count, 1),
                start_date: item.start_date || item.start || '',
                end_date: item.end_date || item.end || '',
            };

            return mappedItem;
        });

        // Build editable items table
        const allowMultiTax = @json($account->allow_multi_taxation);
        const haveUsers = @json($account->have_users);
        
        let html = `
            <div class="table-shell" style="margin-bottom: 1rem;">
                <table class="data-table" style="margin: 0; font-size: 0.8rem;">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Item</th>
                            <th style="width: 70px;">Qty</th>
                            <th style="width: 90px;">Price</th>
                            ${allowMultiTax ? '<th style="width: 70px;">Tax%</th>' : ''}
                            ${haveUsers ? '<th style="width: 60px;">Users</th>' : ''}
                            <th style="width: 100px;">Freq</th>
                            <th style="width: 70px;">Dur</th>
                            <th style="width: 90px;">Start</th>
                            <th style="width: 90px;">End</th>
                            <th style="width: 100px;">Total</th>
                            <th style="width: 40px;"></th>
                        </tr>
                    </thead>
                    <tbody class="inline-items-body" data-invoice-id="${invoiceId}">
        `;

        items.forEach((item, idx) => {
            const lineTotal = (item.quantity || 0) * (item.unit_price || 0) * Math.max(1, item.no_of_users || 1);
            const showDates = item.frequency && item.frequency !== 'one-time';
            
            // Validate and format dates properly for HTML date inputs
            const formatDateForInput = (dateValue) => {
                if (!dateValue || dateValue === 'null' || dateValue === 'undefined') return '';
                // Check if it's already in YYYY-MM-DD format
                if (/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return dateValue;
                // Try to parse other formats
                try {
                    const d = new Date(dateValue);
                    if (isNaN(d.getTime())) return '';
                    return d.toISOString().split('T')[0];
                } catch (e) {
                    return '';
                }
            };
            
            const startDateValue = formatDateForInput(item.start_date);
            const endDateValue = formatDateForInput(item.end_date);
            const durationValue = (item.duration !== null && item.duration !== undefined && item.duration !== '') ? item.duration : '';

            html += `
                <tr data-idx="${idx}">
                    <td><strong>${item.item_name}</strong></td>
                    <td><input type="number" class="form-input inline-edit-field" data-idx="${idx}" data-field="quantity" value="${item.quantity}" min="0.01" step="0.01" style="padding: 0.3rem; font-size: 0.78rem;"></td>
                    <td><input type="number" class="form-input inline-edit-field" data-idx="${idx}" data-field="unit_price" value="${item.unit_price}" min="0" step="0.01" style="padding: 0.3rem; font-size: 0.78rem;"></td>
                    ${allowMultiTax ? `<td><input type="number" class="form-input inline-edit-field" data-idx="${idx}" data-field="tax_rate" value="${item.tax_rate}" min="0" step="0.01" style="padding: 0.3rem; font-size: 0.78rem;"></td>` : ''}
                    ${haveUsers ? `<td><input type="number" class="form-input inline-edit-field" data-idx="${idx}" data-field="no_of_users" value="${item.no_of_users}" min="1" step="1" style="padding: 0.3rem; font-size: 0.78rem;"></td>` : ''}
                    <td>
                        <select class="form-input inline-edit-field" data-idx="${idx}" data-field="frequency" style="padding: 0.3rem; font-size: 0.78rem;">
                            <option value="">None</option>
                            <option value="one-time" ${item.frequency === 'one-time' ? 'selected' : ''}>One-Time</option>
                            <option value="daily" ${item.frequency === 'daily' ? 'selected' : ''}>Daily</option>
                            <option value="weekly" ${item.frequency === 'weekly' ? 'selected' : ''}>Weekly</option>
                            <option value="bi-weekly" ${item.frequency === 'bi-weekly' ? 'selected' : ''}>Bi-Weekly</option>
                            <option value="monthly" ${item.frequency === 'monthly' ? 'selected' : ''}>Monthly</option>
                            <option value="quarterly" ${item.frequency === 'quarterly' ? 'selected' : ''}>Quarterly</option>
                            <option value="semi-annually" ${item.frequency === 'semi-annually' ? 'selected' : ''}>Semi-Annually</option>
                            <option value="yearly" ${item.frequency === 'yearly' ? 'selected' : ''}>Yearly</option>
                        </select>
                    </td>
                    <td><input type="number" class="form-input inline-edit-field" data-idx="${idx}" data-field="duration" value="${durationValue}" min="0" step="1" style="padding: 0.3rem; font-size: 0.78rem;"></td>
                    <td><input type="date" class="form-input inline-edit-field" data-idx="${idx}" data-field="start_date" value="${startDateValue}" ${!showDates || !startDateValue ? 'disabled' : ''} style="padding: 0.3rem; font-size: 0.78rem;"></td>
                    <td><input type="date" class="form-input inline-edit-field" data-idx="${idx}" data-field="end_date" value="${endDateValue}" ${!showDates || !endDateValue ? 'disabled' : ''} style="padding: 0.3rem; font-size: 0.78rem;"></td>
                    <td><strong class="line-total" data-idx="${idx}">${clientCurrency} ${lineTotal.toFixed(2)}</strong></td>
                    <td><button type="button" class="icon-action-btn delete remove-inline-item" data-idx="${idx}" style="padding: 0.25rem; font-size: 0.75rem;"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 0.5rem;">
                <div style="padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; gap: 2rem; font-size: 0.85rem; margin-bottom: 0.3rem;">
                        <span>Subtotal:</span><strong class="inline-subtotal">${clientCurrency} 0.00</strong>
                    </div>
                    ${allowMultiTax ? `<div style="display: flex; justify-content: space-between; gap: 2rem; font-size: 0.85rem; margin-bottom: 0.3rem;"><span>Tax:</span><strong class="inline-tax">${clientCurrency} 0.00</strong></div>` : ''}
                    <div style="display: flex; justify-content: space-between; gap: 2rem; font-size: 0.95rem; font-weight: 700; border-top: 1px solid #cbd5e1; padding-top: 0.5rem;">
                        <span>Grand Total:</span><strong class="inline-grand-total">${clientCurrency} 0.00</strong>
                    </div>
                </div>
            </div>
        `;

        itemsContainer.innerHTML = html;

        // Store items data and invoice ID for later update
        editSection.dataset.items = JSON.stringify(items);
        editSection.dataset.invoiceId = invoiceId;

        // Calculate initial totals
        recalcInlineEditTotals(editSection);

        // Scroll to editor
        editSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Recalculate totals for an inline edit section
    function recalcInlineEditTotals(editSection) {
        const items = JSON.parse(editSection.dataset.items || '[]');
        let subtotal = 0, taxTotal = 0;

        items.forEach((item, idx) => {
            const lineTotal = (item.quantity || 0) * (item.unit_price || 0) * Math.max(1, item.no_of_users || 1);
            const lineTax = lineTotal * ((item.tax_rate || 0) / 100);
            subtotal += lineTotal;
            taxTotal += lineTax;
            const el = editSection.querySelector(`.line-total[data-idx="${idx}"]`);
            if (el) el.textContent = `${clientCurrency} ${(lineTotal + lineTax).toFixed(2)}`;
        });

        const s = editSection.querySelector('.inline-subtotal');
        const t = editSection.querySelector('.inline-tax');
        const g = editSection.querySelector('.inline-grand-total');
        if (s) s.textContent = `${clientCurrency} ${subtotal.toFixed(2)}`;
        if (t) t.textContent = `${clientCurrency} ${taxTotal.toFixed(2)}`;
        if (g) g.textContent = `${clientCurrency} ${(subtotal + taxTotal).toFixed(2)}`;
    }

    // Handle inline edit field changes via event delegation
    document.addEventListener('input', function(e) {
        const field = e.target.closest('.inline-edit-field');
        if (!field) return;

        const editSection = field.closest('.inline-edit-section');
        if (!editSection) return;

        const idx = parseInt(field.dataset.idx);
        const fieldName = field.dataset.field;
        const value = field.type === 'number' ? Number(field.value) : field.value;

        const items = JSON.parse(editSection.dataset.items || '[]');
        if (!items[idx]) return;
        items[idx][fieldName] = value;

        // Handle frequency change - disable dates for one-time
        if (fieldName === 'frequency') {
            const itemsBody = editSection.querySelector('.inline-items-body');
            const showDates = value && value !== 'one-time';
            const startField = itemsBody.querySelector(`input[data-idx="${idx}"][data-field="start_date"]`);
            const endField = itemsBody.querySelector(`input[data-idx="${idx}"][data-field="end_date"]`);
            if (startField) { startField.disabled = !showDates; if (!showDates) { startField.value = ''; items[idx].start_date = ''; } }
            if (endField) { endField.disabled = !showDates; if (!showDates) { endField.value = ''; items[idx].end_date = ''; } }
        }

        editSection.dataset.items = JSON.stringify(items);
        recalcInlineEditTotals(editSection);
    });

    // Event delegation for Edit Invoice buttons
    document.addEventListener('click', function(e) {
        // Edit button click
        if (e.target.closest('.edit-invoice-btn')) {
            const btn = e.target.closest('.edit-invoice-btn');
            const invoiceData = JSON.parse(btn.dataset.invoice);
            showInlineEdit(invoiceData);
        }
        
        // Close button click
        if (e.target.closest('.close-inline-edit')) {
            const btn = e.target.closest('.close-inline-edit');
            const invoiceId = btn.dataset.id;
            const editSection = document.getElementById(`inline-edit-section-${invoiceId}`);
            const editBtn = document.querySelector(`.edit-invoice-btn[data-id="${invoiceId}"]`);
            
            editSection.style.display = 'none';
            editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
        }
        
        // Update button click
        if (e.target.closest('.update-inline-btn')) {
            const btn = e.target.closest('.update-inline-btn');
            const invoiceId = btn.dataset.id;
            const editSection = document.getElementById(`inline-edit-section-${invoiceId}`);
            const items = JSON.parse(editSection.dataset.items || '[]');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            // Calculate totals
            let subtotal = 0, taxTotal = 0;
            const preparedItems = items.map(item => {
                const qty = Number(item.quantity) || 0;
                const price = Number(item.unit_price) || 0;
                const users = Math.max(1, Number(item.no_of_users) || 1);
                const freq = item.frequency || '';
                const dur = Number(item.duration) || 0;
                let lineTotal = qty * price * users;
                if (freq && freq !== 'one-time' && dur > 0) lineTotal *= dur;
                const lineTax = lineTotal * ((Number(item.tax_rate) || 0) / 100);
                subtotal += lineTotal;
                taxTotal += lineTax;
                return {
                    itemid: item.itemid || '',
                    item_name: item.item_name || 'Item',
                    quantity: qty,
                    unit_price: price,
                    tax_rate: Number(item.tax_rate) || 0,
                    duration: item.duration || null,
                    frequency: item.frequency || null,
                    no_of_users: users,
                    start_date: item.start_date || null,
                    end_date: item.end_date || null,
                    line_total: lineTotal
                };
            });

            const grandTotal = subtotal + taxTotal;

            // Get invoice meta from stored data attribute on the edit button
            const invoiceDataEl = document.querySelector(`.edit-invoice-btn[data-id="${invoiceId}"]`);
            const invoiceData = invoiceDataEl ? JSON.parse(invoiceDataEl.dataset.invoice || '{}') : {};

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            formData.append('clientid', invoiceData.clientid || '');
            formData.append('invoice_number', invoiceData.number || '');
            formData.append('invoice_title', invoiceData.title || '');
            formData.append('issue_date', invoiceData.issue_date_raw || '');
            formData.append('due_date', invoiceData.due_date_raw || '');
            formData.append('status', invoiceData.status || 'draft');
            formData.append('notes', '');
            formData.append('items_data', JSON.stringify(preparedItems));
            formData.append('subtotal', subtotal.toFixed(2));
            formData.append('tax_total', taxTotal.toFixed(2));
            formData.append('grand_total', grandTotal.toFixed(2));

            fetch(`{{ url('/invoices') }}/${invoiceId}`, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(async response => {
                const text = await response.text();
                
                if (response.ok) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Saved!';
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-save"></i> Update';
                        editSection.style.display = 'none';
                        const editBtn = document.querySelector(`.edit-invoice-btn[data-id="${invoiceId}"]`);
                        if (editBtn) editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
                        if (selectedClientId) loadInvoicesForClient(selectedClientId);
                    }, 800);
                } else {
                    try {
                        const json = JSON.parse(text);
                        const errors = json.errors ? Object.values(json.errors).flat().join('\n') : json.message || 'Unknown error';
                        alert('Error:\n' + errors);
                    } catch(e) {
                        // Show first 300 chars of HTML error
                        const match = text.match(/\<title\>(.*?)\<\/title\>/);
                        const match2 = text.match(/message.*?:\s*"(.*?)"/);
                        alert('Error ' + response.status + ': ' + (match2?.[1] || match?.[1] || 'Check console'));
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Update';
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update';
                alert('Network error: ' + error.message);
            });
        }
    });

    clientSelect.addEventListener('change', function () {
        selectedClientId = this.value || null;
        if (!selectedClientId) {
            existingInvoicesSection.style.display = 'none';
            sourceSelectionSection.style.display = 'none';
            return;
        }
        clientCurrency = this.options[this.selectedIndex]?.dataset?.currency || 'INR';
        currencyCodeInput.value = clientCurrency;
        existingInvoicesSection.style.display = 'block';
        sourceSelectionSection.style.display = 'block';
        loadInvoicesForClient(selectedClientId);
    });

    btnNextToStep2.addEventListener('click', async () => {
        if (!selectedClientId) {
            alert('Please select a client first.');
            return;
        }
        const source = document.querySelector('input[name="invoice_for"]:checked')?.value;
        if (!source) {
            alert('Please choose an invoice source.');
            return;
        }

        // Save draft to DB
        const invoiceTitle = document.getElementById('invoice_title')?.value || '';
        try {
            const resp = await fetch("{{ route('invoices.save-draft') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ clientid: selectedClientId, invoice_title: invoiceTitle, invoice_for: source }),
            });
            const data = await resp.json();
            if (data.ok) {
                document.getElementById('proformaid').value = data.proformaid;
                console.log('Draft saved:', data.proformaid);
            }
        } catch (e) {
            console.warn('Could not save draft:', e);
        }

        navigateToStep(2);
    });

    btnBackToStep1.addEventListener('click', () => {
        showStep(1);
    });

    btnNextToStep3.addEventListener('click', () => {
        navigateToStep(3);
    });

    btnBackToStep2.addEventListener('click', () => {
        showStep(2);
    });

    // Use event delegation for terms checkboxes
    document.getElementById('termsList').addEventListener('change', (e) => {
        if (e.target.classList.contains('term-checkbox')) {
            updateInvoicePreview();
            const allCheckboxes = document.querySelectorAll('.term-checkbox');
            const anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);
            document.getElementById('finalSubmitBtnStep3').disabled = !anyChecked;
        }
    });

    // Step 2 Functions
    function activateSource(source) {
        console.log('=== activateSource called ===', source);
        
        ordersSection.style.display = 'none';
        renewalSection.style.display = 'none';
        manualItemsSection.style.display = 'none';
        itemsSection.style.display = 'none';

        if (source === 'orders') {
            console.log('Showing Orders section');
            ordersSection.style.display = 'block';
            loadOrders();
        } else if (source === 'renewal') {
            console.log('Showing Renewal section');
            renewalSection.style.display = 'block';
            loadRenewals();
        } else if (source === 'without_orders') {
            console.log('Showing Manual Items section');
            manualItemsSection.style.display = 'block';
            renderManualItems();
        } else {
            console.warn('Unknown source:', source);
        }
    }

    function loadOrders() {
        ordersBody.innerHTML = '<tr><td colspan="5" class="empty-state">Loading orders...</td></tr>';
        fetch(`{{ route('invoices.client-orders') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId }),
        })
            .then(res => res.json())
            .then(orders => {
                ordersBody.innerHTML = '';
                if (orders.length === 0) {
                    noOrdersMessage.style.display = 'block';
                    return;
                }
                noOrdersMessage.style.display = 'none';
                orders.forEach(order => {
                    const row = document.createElement('tr');
                    const orderDisplay = order.order_title ? `${order.order_title}` : order.order_number;
                    const numberDisplay = order.order_title ? `<div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">${order.order_number}</div>` : '';
                    
                    row.innerHTML = `
                        <td>
                            <strong>${orderDisplay}</strong>
                            ${numberDisplay}
                        </td>
                        <td>${order.order_date}</td>
                        <td>${order.currency} ${Number(order.grand_total).toLocaleString()}</td>
                        <td>${order.status}</td>
                        <td><button type="button" class="primary-button select-order-btn" data-id="${order.orderid}" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Select</button></td>
                    `;
                    ordersBody.appendChild(row);
                });
            });
    }

    ordersBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.select-order-btn');
        if (!btn) return;
        const orderId = btn.dataset.id;
        orderIdInput.value = orderId;
        fetch(`{{ url('/invoices/order-items') }}/${orderId}`)
            .then(res => res.json())
            .then(data => {
                invoiceItems = data.items || [];
                renderItems();
                itemsSection.scrollIntoView({ behavior: 'smooth' });
            });
    });

    function loadRenewals() {
        renewalBody.innerHTML = '<tr><td colspan="5" class="empty-state">Loading renewal candidates...</td></tr>';
        noRenewalMessage.style.display = 'none';
        
        const daysFilter = document.getElementById('renewalDaysFilter')?.value || 1;
        
        console.log('Loading renewals for client:', selectedClientId, 'days filter:', daysFilter);
        
        fetch(`{{ route('invoices.renewal-invoices') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ clientid: selectedClientId, days: daysFilter }),
        })
            .then(res => res.json())
            .then(invoices => {
                console.log('=== RENEWAL RESPONSE ===');
                console.log('Invoices count:', invoices.length);
                console.log('Invoices:', invoices);
                
                renewalBody.innerHTML = '';
                if (invoices.length === 0) {
                    noRenewalMessage.style.display = 'block';
                    noRenewalMessage.innerHTML = `
                        <strong>No items to renew</strong><br>
                        <small style="color: #94a3b8;">All items are either active or already renewed.</small>
                    `;
                    return;
                }
                noRenewalMessage.style.display = 'none';
                invoices.forEach(inv => {
                    const row = document.createElement('tr');
                    const expiredText = inv.expired_items > 0 
                        ? `<span style="color: #ef4444; font-weight: 600;">${inv.expired_items} Expired</span>`
                        : '';
                    const upcomingText = inv.upcoming_items > 0 
                        ? `<span style="color: #f59e0b; font-weight: 600; margin-left: 0.5rem;">${inv.upcoming_items} Expiring Soon</span>`
                        : '';
                    row.innerHTML = `
                        <td><strong>${inv.invoice_number}</strong></td>
                        <td>${expiredText}${upcomingText}</td>
                        <td>${inv.currency} ${Number(inv.grand_total).toLocaleString()}</td>
                        <td>${inv.total_items} items</td>
                        <td><button type="button" class="primary-button select-renewal-btn" data-id="${inv.proformaid}" data-num="${inv.invoice_number}" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Review</button></td>
                    `;
                    renewalBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error('Error loading renewals:', err);
                renewalBody.innerHTML = '<tr><td colspan="5" class="empty-state" style="color: #ef4444;">Error loading renewals. Check console.</td></tr>';
            });
    }

    renewalBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.select-renewal-btn');
        if (!btn) return;
        const invId = btn.dataset.id;
        const daysFilter = document.getElementById('renewalDaysFilter')?.value || 1;
        fetch(`{{ url('/invoices/renewal-items') }}/${invId}?days=${daysFilter}`)
            .then(res => res.json())
            .then(data => {
                showRenewalPicker(btn.dataset.num, data.items || []);
                renewalPicker.scrollIntoView({ behavior: 'smooth' });
            });
    });

    // Reload renewals when days filter changes
    document.getElementById('renewalDaysFilter')?.addEventListener('change', () => {
        if (selectedClientId) {
            loadRenewals();
        }
    });

    function showRenewalPicker(invNum, items) {
        // Only show items that are expired or upcoming AND not renewed
        const renewableItems = items.filter(i => (i.is_expired || i.is_upcoming) && !i.renewed_to_proformaid);
        const expired = renewableItems.filter(i => i.is_expired);
        const upcoming = renewableItems.filter(i => i.is_upcoming);
        const renewed = items.filter(i => i.renewed_to_proformaid);
        
        console.log('Renewal picker - Total:', items.length, 'Expired:', expired.length, 'Upcoming:', upcoming.length, 'Renewed:', renewed.length);
        
        // Group all renewable items together, expired first
        const allRenewable = [...expired, ...upcoming];
        
        renewalPicker.style.display = 'block';
        renewalPicker.innerHTML = `
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; overflow: hidden;">
                <div style="padding: 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <h5 style="margin: 0;">Renew from ${invNum}</h5>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                        ${expired.length > 0 ? `${expired.length} expired` : ''}${expired.length > 0 && upcoming.length > 0 ? ', ' : ''}${upcoming.length > 0 ? `${upcoming.length} expiring soon` : ''}
                    </p>
                </div>
                <table class="data-table" style="margin: 0; font-size: 0.8rem;">
                    <thead><tr><th></th><th>Item</th><th>End Date</th><th>Status</th><th>Price</th></tr></thead>
                    <tbody>
                        ${allRenewable.length > 0 ? allRenewable.map((i, idx) => {
                            const statusBadge = i.is_expired 
                                ? '<span style="padding: 0.15rem 0.5rem; background: #fee2e2; color: #991b1b; border-radius: 8px; font-size: 0.68rem; font-weight: 600;">Expired</span>'
                                : '<span style="padding: 0.15rem 0.5rem; background: #fef3c7; color: #92400e; border-radius: 8px; font-size: 0.68rem; font-weight: 600;">Expiring Soon</span>';
                            const dateColor = i.is_expired ? '#ef4444' : '#f59e0b';
                            return `
                            <tr>
                                <td>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" 
                                            class="renewal-check" 
                                            data-idx="${idx}" 
                                            checked>
                                        <span class="checkbox-label"></span>
                                    </label>
                                </td>
                                <td>${i.item_name}</td>
                                <td style="color: ${dateColor};">${i.end_date}</td>
                                <td>${statusBadge}</td>
                                <td>${formatMoney(i.line_total)}</td>
                            </tr>`;
                        }).join('') : '<tr><td colspan="5" style="padding: 1rem; text-align: center; color: #64748b;">No items to renew</td></tr>'}
                    </tbody>
                </table>
                <div style="padding: 1rem; border-top: 1px solid #e2e8f0; text-align: right;">
                    <button type="button" id="btnConfirmRenewal" class="primary-button" ${allRenewable.length === 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''}>Add Selected Items</button>
                </div>
            </div>
        `;
        document.getElementById('btnConfirmRenewal').addEventListener('click', () => {
            const checks = document.querySelectorAll('.renewal-check:checked');
            const originalItemIds = Array.from(checks).map(c => allRenewable[c.dataset.idx].proformaitemid);
            
            console.log('Renewing proforma items:', originalItemIds);
            
            // Store original item IDs for marking as renewed
            window.renewalOriginalItemIds = originalItemIds;
            
            invoiceItems = Array.from(checks).map(c => {
                const item = { ...allRenewable[c.dataset.idx] };
                item.start_date = document.getElementById('issue_date').value;
                // Store the original proformaitemid for tracing
                item.renewed_from_proformaitemid = item.proformaitemid;
                if (item.frequency && item.duration) item.end_date = calculateEndDate(item.start_date, item.frequency, item.duration);
                return item;
            });
            
            console.log('Renewing items:', invoiceItems.map(i => ({ name: i.item_name, original_id: i.renewed_from_proformaitemid })));
            
            renderItems();
        });
    }

    function renderItems() {
        itemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;
        let anyItemHasRecurringFrequency = false;
        
        // Check if any item has a recurring frequency
        invoiceItems.forEach((item) => {
            if (item.frequency && item.frequency !== 'one-time') {
                anyItemHasRecurringFrequency = true;
            }
        });
        
        // Show/hide Start/End column headers based on frequencies
        const headerStart = document.getElementById('headerStart');
        const headerEnd = document.getElementById('headerEnd');
        if (headerStart) headerStart.style.display = anyItemHasRecurringFrequency ? '' : 'none';
        if (headerEnd) headerEnd.style.display = anyItemHasRecurringFrequency ? '' : 'none';
        
        invoiceItems.forEach((item, idx) => {
            const lineTotal = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = lineTotal * (Number(item.tax_rate || 0) / 100);
            subtotal += lineTotal;
            taxTotal += lineTax;

            // Check if we should show date fields for this item
            const showDates = item.frequency && item.frequency !== 'one-time';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>${item.item_name}</strong></td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="quantity" value="${item.quantity}" min="0.01" step="0.01"></td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="unit_price" value="${item.unit_price}"></td>
                @if($account->allow_multi_taxation)
                <td>${renderTaxSelect(item.tax_rate, 'item-edit', `data-idx="${idx}" data-field="tax_rate"`)}</td>
                @endif
                @if($account->have_users)
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="no_of_users" value="${item.no_of_users || 1}"></td>
                @endif
                <td>
                    <select class="form-input item-edit" data-idx="${idx}" data-field="frequency">
                        <option value="">None</option>
                        ${frequencyOptions.map(f => `<option value="${f}" ${item.frequency === f ? 'selected' : ''}>${frequencyLabels[f] || f}</option>`).join('')}
                    </select>
                </td>
                <td><input type="number" class="form-input item-edit" data-idx="${idx}" data-field="duration" value="${item.duration || ''}"></td>
                <td class="date-cell" style="display: ${showDates ? '' : 'none'}"><input type="date" class="form-input item-edit date-field" data-idx="${idx}" data-field="start_date" value="${item.start_date || ''}" ${showDates ? '' : 'disabled'}></td>
                <td class="date-cell" style="display: ${showDates ? '' : 'none'}"><input type="date" class="form-input item-edit date-field" data-idx="${idx}" data-field="end_date" value="${item.end_date || ''}" ${showDates ? '' : 'disabled'}></td>
                <td><strong>${formatMoney(lineTotal + lineTax)}</strong></td>
                <td><button type="button" class="icon-action-btn delete remove-item" data-idx="${idx}"><i class="fas fa-trash"></i></button></td>
            `;
            itemsBody.appendChild(row);
        });
        setTotals(subtotal, taxTotal);
        itemsSection.style.display = invoiceItems.length ? 'block' : 'none';
        updateFinalSubmitButton();
    }

    itemsBody.addEventListener('input', (e) => {
        const input = e.target.closest('.item-edit');
        if (!input) return;
        const idx = input.dataset.idx;
        const field = input.dataset.field;
        invoiceItems[idx][field] = input.type === 'number' ? Number(input.value) : input.value;
        
        // Clear start_date and end_date when frequency is set to one-time
        if (field === 'frequency' && (input.value === 'one-time' || input.value === '')) {
            invoiceItems[idx].start_date = '';
            invoiceItems[idx].end_date = '';
        }
        
        if (['start_date', 'frequency', 'duration'].includes(field)) {
            invoiceItems[idx].end_date = calculateEndDate(invoiceItems[idx].start_date, invoiceItems[idx].frequency, invoiceItems[idx].duration);
        }
        renderItems();
    });

    itemsBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-item');
        if (!btn) return;
        invoiceItems.splice(btn.dataset.idx, 1);
        renderItems();
    });

    // Manual Items
    document.getElementById('manual_item_itemid').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        document.getElementById('manual_item_unit_price').value = opt.dataset.sellingPrice || '';

        const taxRateEl = document.getElementById('manual_item_tax_rate');
        if (!taxRateEl || taxRateEl.tagName !== 'SELECT') return;

        const taxRate = opt.dataset.taxRate || '0';
        const serviceTaxid = opt.dataset.taxid || '';

        let found = false;
        for (let i = 0; i < taxRateEl.options.length; i++) {
            if (taxRateEl.options[i].dataset.taxid && taxRateEl.options[i].dataset.taxid === serviceTaxid) {
                taxRateEl.selectedIndex = i;
                found = true;
                break;
            }
        }
        if (!found) {
            for (let i = 0; i < taxRateEl.options.length; i++) {
                if (taxRateEl.options[i].value === taxRate) {
                    taxRateEl.selectedIndex = i;
                    break;
                }
            }
        }
    });

    // Show/hide start+end date fields in the add form based on frequency
    document.getElementById('manual_item_frequency').addEventListener('change', function () {
        const showDates = this.value && this.value !== 'one-time';
        document.getElementById('manual_item_start_date_wrap').style.display = showDates ? '' : 'none';
        document.getElementById('manual_item_end_date_wrap').style.display = showDates ? '' : 'none';
        if (!showDates) {
            document.getElementById('manual_item_start_date').value = '';
            document.getElementById('manual_item_end_date').value = '';
        }
    });

    // Auto-calculate end date in the add form when start/duration/frequency change
    function recalcAddFormEndDate() {
        const freq = document.getElementById('manual_item_frequency').value;
        const dur = document.getElementById('manual_item_duration').value;
        const start = document.getElementById('manual_item_start_date').value;
        if (freq && freq !== 'one-time' && start && dur) {
            document.getElementById('manual_item_end_date').value = calculateEndDate(start, freq, dur);
        }
    }
    document.getElementById('manual_item_start_date').addEventListener('change', recalcAddFormEndDate);
    document.getElementById('manual_item_duration').addEventListener('input', recalcAddFormEndDate);

    addManualItemBtn.addEventListener('click', () => {
        const select = document.getElementById('manual_item_itemid');
        if (!select.value) return alert('Select an item');
        const opt = select.options[select.selectedIndex];
        const taxRateEl = document.getElementById('manual_item_tax_rate');
        const taxRate = Number(taxRateEl.value) || 0;
        const taxid = (taxRateEl.tagName === 'SELECT' && taxRateEl.selectedIndex >= 0)
            ? (taxRateEl.options[taxRateEl.selectedIndex].dataset.taxid || null)
            : null;
        const freq = document.getElementById('manual_item_frequency').value;

        const itemData = {
            itemid: select.value,
            item_name: opt.text.split(' (')[0],
            quantity: Number(document.getElementById('manual_item_quantity').value) || 1,
            unit_price: Number(document.getElementById('manual_item_unit_price').value) || 0,
            tax_rate: taxRate,
            taxid: taxid,
            frequency: freq,
            duration: document.getElementById('manual_item_duration').value,
            no_of_users: Number(document.getElementById('manual_item_users').value) || 1,
            start_date: document.getElementById('manual_item_start_date').value || '',
            end_date: document.getElementById('manual_item_end_date').value || '',
        };

        if (editingManualItemId !== null) {
            const idx = manualItems.findIndex(i => i.id === editingManualItemId);
            if (idx > -1) manualItems[idx] = { ...manualItems[idx], ...itemData };
        } else {
            manualItems.push({ id: ++manualItemCounter, ...itemData });
        }

        resetManualForm();
        renderManualItems();
        updateFinalSubmitButton();
    });

    function renderManualItems() {
        manualItemsBody.innerHTML = '';
        let subtotal = 0, taxTotal = 0;
        let anyItemHasRecurringFrequency = false;
        
        // Check if any item has a recurring frequency
        manualItems.forEach((item) => {
            if (item.frequency && item.frequency !== 'one-time') {
                anyItemHasRecurringFrequency = true;
            }
        });
        
        const manualHeaderStart = document.getElementById('manualHeaderStart');
        const manualHeaderEnd = document.getElementById('manualHeaderEnd');
        if (manualHeaderStart) manualHeaderStart.style.display = anyItemHasRecurringFrequency ? '' : 'none';
        if (manualHeaderEnd) manualHeaderEnd.style.display = anyItemHasRecurringFrequency ? '' : 'none';

        const freqLabels = { 'one-time': 'One-Time', 'daily': 'Daily', 'weekly': 'Weekly', 'bi-weekly': 'Bi-Weekly', 'monthly': 'Monthly', 'quarterly': 'Quarterly', 'semi-annually': 'Semi-Annually', 'yearly': 'Yearly' };

        manualItems.forEach((item, idx) => {
            const lineTotal = calculateLineTotal(item.quantity, item.unit_price, item.no_of_users, item.frequency, item.duration);
            const lineTax = lineTotal * (item.tax_rate / 100);
            subtotal += lineTotal;
            taxTotal += lineTax;

            const showDates = item.frequency && item.frequency !== 'one-time';
            const isEditing = editingManualItemId === item.id;

            const row = document.createElement('tr');
            if (isEditing) row.style.background = '#eff6ff';
            row.dataset.itemId = item.id;
            row.innerHTML = `
                <td><strong>${item.item_name}</strong></td>
                <td style="text-align:right;">${item.quantity}</td>
                <td style="text-align:right;">${formatMoney(item.unit_price)}</td>
                @if($account->allow_multi_taxation)
                <td style="text-align:right;">${item.tax_rate ? item.tax_rate + '%' : '0%'}</td>
                @endif
                @if($account->have_users)
                <td style="text-align:right;">${item.no_of_users || 1}</td>
                @endif
                <td>${freqLabels[item.frequency] || item.frequency || '—'}</td>
                <td style="text-align:right;">${item.duration || '—'}</td>
                <td class="date-cell" style="display: ${showDates ? '' : 'none'}">${item.start_date || '—'}</td>
                <td class="date-cell" style="display: ${showDates ? '' : 'none'}">${item.end_date || '—'}</td>
                <td style="text-align:right;"><strong>${formatMoney(lineTotal + lineTax)}</strong></td>
                <td style="white-space:nowrap;">
                    <button type="button" class="icon-action-btn edit edit-manual" data-idx="${idx}" title="Edit" style="margin-right:0.2rem;"><i class="fas fa-edit"></i></button>
                    <button type="button" class="icon-action-btn delete remove-manual" data-idx="${idx}" title="Remove"><i class="fas fa-trash"></i></button>
                </td>
            `;
            manualItemsBody.appendChild(row);
        });
        manualItemsTable.style.display = manualItems.length ? 'table' : 'none';
        manualItemsEmpty.style.display = manualItems.length ? 'none' : 'block';
        if (manualSummary) manualSummary.style.display = manualItems.length ? 'block' : 'none';
        setTotals(subtotal, taxTotal);
        updateFinalSubmitButton();
    }

    function resetManualForm() {
        document.getElementById('manual_item_itemid').value = '';
        document.getElementById('manual_item_quantity').value = 1;
        document.getElementById('manual_item_unit_price').value = '';
        document.getElementById('manual_item_frequency').value = '';
        document.getElementById('manual_item_duration').value = '';
        document.getElementById('manual_item_start_date').value = '';
        document.getElementById('manual_item_end_date').value = '';
        document.getElementById('manual_item_start_date_wrap').style.display = 'none';
        document.getElementById('manual_item_end_date_wrap').style.display = 'none';
        const taxRateEl = document.getElementById('manual_item_tax_rate');
        if (taxRateEl && taxRateEl.tagName === 'SELECT') taxRateEl.selectedIndex = 0;
        const usersEl = document.getElementById('manual_item_users');
        if (usersEl && usersEl.type !== 'hidden') usersEl.value = 1;
        editingManualItemId = null;
        addManualItemBtn.textContent = 'Add';
        addManualItemBtn.classList.add('primary-button');
        addManualItemBtn.classList.remove('secondary-button');
    }

    manualItemsBody.addEventListener('click', (e) => {
        // Edit
        const editBtn = e.target.closest('.edit-manual');
        if (editBtn) {
            const idx = parseInt(editBtn.dataset.idx);
            const item = manualItems[idx];
            if (!item) return;

            editingManualItemId = item.id;

            document.getElementById('manual_item_itemid').value = item.itemid;
            document.getElementById('manual_item_quantity').value = item.quantity;
            document.getElementById('manual_item_unit_price').value = item.unit_price;
            document.getElementById('manual_item_frequency').value = item.frequency || '';
            document.getElementById('manual_item_duration').value = item.duration || '';
            const usersEl = document.getElementById('manual_item_users');
            if (usersEl) usersEl.value = item.no_of_users || 1;

            const showDates = item.frequency && item.frequency !== 'one-time';
            document.getElementById('manual_item_start_date_wrap').style.display = showDates ? '' : 'none';
            document.getElementById('manual_item_end_date_wrap').style.display = showDates ? '' : 'none';
            document.getElementById('manual_item_start_date').value = item.start_date || '';
            document.getElementById('manual_item_end_date').value = item.end_date || '';

            const taxRateEl = document.getElementById('manual_item_tax_rate');
            if (taxRateEl && taxRateEl.tagName === 'SELECT') {
                for (let i = 0; i < taxRateEl.options.length; i++) {
                    if (Number(taxRateEl.options[i].value) === Number(item.tax_rate)) {
                        taxRateEl.selectedIndex = i;
                        break;
                    }
                }
            }

            addManualItemBtn.textContent = 'Update Item';
            addManualItemBtn.classList.remove('primary-button');
            addManualItemBtn.classList.add('secondary-button');

            renderManualItems();
            document.querySelector('.builder-card').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        // Delete
        const delBtn = e.target.closest('.remove-manual');
        if (delBtn) {
            const idx = parseInt(delBtn.dataset.idx);
            if (editingManualItemId === manualItems[idx]?.id) resetManualForm();
            manualItems.splice(idx, 1);
            renderManualItems();
        }
    });

    invoiceForm.addEventListener('submit', async (e) => {
        const source = document.querySelector('input[name="invoice_for"]:checked').value;
        const items = source === 'without_orders' ? manualItems : invoiceItems;
        itemsDataInput.value = JSON.stringify(items.map(i => ({ ...i, line_total: calculateLineTotal(i.quantity, i.unit_price, i.no_of_users, i.frequency, i.duration) })));
        
        // If this is a renewal, add the original item IDs to a hidden field
        if (source === 'renewal' && window.renewalOriginalItemIds && window.renewalOriginalItemIds.length > 0) {
            let renewalIdsInput = document.querySelector('input[name="renewed_item_ids"]');
            if (!renewalIdsInput) {
                renewalIdsInput = document.createElement('input');
                renewalIdsInput.type = 'hidden';
                renewalIdsInput.name = 'renewed_item_ids';
                invoiceForm.appendChild(renewalIdsInput);
            }
            renewalIdsInput.value = JSON.stringify(window.renewalOriginalItemIds);
            console.log('Submitting renewal for items:', window.renewalOriginalItemIds);
        }
        
        // If we have a draft proformaid, the store endpoint will update it
        const draftId = document.getElementById('proformaid').value;
        if (draftId) {
            console.log('Submitting with draft ID:', draftId);
        }
        
        clearState(); // Clear state on successful submission
    });

    // ============================================
    // INITIALIZATION: Restore step from URL hash
    // ============================================
    
    console.log('=== INITIALIZING ===');
    console.log('URL hash:', window.location.hash);
    
    // Check for saved draft in DB first
    if (selectedClientId) {
        fetch("{{ url('/invoices/draft') }}/" + selectedClientId)
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.draft) {
                    console.log('Restoring draft:', data.draft);
                    document.getElementById('proformaid').value = data.draft.proformaid;
                    
                    // Restore Step 1 data
                    if (data.draft.invoice_title) {
                        document.getElementById('invoice_title').value = data.draft.invoice_title;
                    }
                    if (data.draft.invoice_for) {
                        const radio = document.querySelector(`input[name="invoice_for"][value="${data.draft.invoice_for}"]`);
                        if (radio) radio.checked = true;
                    }
                    
                    // Restore Step 2 data
                    if (data.draft.items && data.draft.items.length > 0) {
                        invoiceItems = data.draft.items;
                        renderItems();
                    }
                    
                    // Navigate to Step 2 if draft has progress
                    showStep(2);
                } else {
                    loadState();
                    const initialStep = getCurrentStepFromHash();
                    showStep(initialStep);
                }
            })
            .catch(() => {
                loadState();
                const initialStep = getCurrentStepFromHash();
                showStep(initialStep);
            });
    } else {
        loadState();
        showStep(getCurrentStepFromHash());
    }

    // Listen for browser back/forward buttons
    window.addEventListener('hashchange', () => {
        const stepFromHash = getCurrentStepFromHash();
        if (stepFromHash) {
            showStep(stepFromHash);
        }
    });

    // ============================================
    // END INITIALIZATION
    // ============================================
})();
</script>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalInvoice" tabindex="-1">
    {{-- ... existing tax modal ... --}}
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-invoice">
                    @csrf
                    <input type="hidden" name="redirect_back" value="1">
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Rate (%)</label>
                        <input type="number" name="rate" placeholder="18" step="0.01" min="0" max="100" required
                               style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="font-size: 0.75rem; font-weight: 600; display: block; margin-bottom: 0.25rem;">Type</label>
                        <select name="type" required
                                style="padding: 0.4rem 0.75rem; font-size: 0.9rem; width: 100%;">
                            @foreach(['GST'=>'GST','VAT'=>'VAT'] as $v=>$l)
                                <option value="{{ $v }}">{{ $l }}</option>
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
@endif

{{-- Add Term Modal --}}
<div class="modal fade" id="addTCModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            <div class="modal-header" style="padding: 1.25rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <h5 class="modal-title" style="font-size: 1.1rem; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-file-contract" style="margin-right: 0.6rem; color: #3b82f6;"></i>Add Billing Term
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <form id="quick-tc-form">
                    @csrf
                    <input type="hidden" name="type" value="billing">
                    <div style="margin-bottom: 1.25rem;">
                        <label class="field-label" style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.5rem;">Terms and Condition *</label>
                        <textarea name="content" id="tc_content" rows="5" placeholder="Enter terms and condition..." required class="form-input" style="width: 100%; min-height: 140px; font-size: 0.9rem;"></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                        <button type="button" class="secondary-button" data-bs-dismiss="modal" style="padding: 0.65rem 1.25rem;">Cancel</button>
                        <button type="submit" class="primary-button" id="btnSaveTC" style="padding: 0.65rem 2rem;">Save Term</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Modal instances
    const taxModalEl = document.getElementById('addTaxModalInvoice');
    const tcModalEl = document.getElementById('addTCModal');
    let taxModal = null;
    let tcModal = null;
    
    if (taxModalEl) taxModal = new bootstrap.Modal(taxModalEl);
    if (tcModalEl) tcModal = new bootstrap.Modal(tcModalEl);

    // Open Tax Modal
    const openTaxLink = document.getElementById('open-tax-modal-invoice');
    if (openTaxLink) {
        openTaxLink.addEventListener('click', (e) => {
            e.preventDefault();
            taxModal.show();
        });
    }

    // Open T&C Modal
    const btnAddTC = document.getElementById('btnAddTC');
    if (btnAddTC) {
        btnAddTC.addEventListener('click', () => {
            tcModal.show();
        });
    }

    // Handle T&C Save via AJAX
    const quickTCForm = document.getElementById('quick-tc-form');
    if (quickTCForm) {
        quickTCForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btnSave = document.getElementById('btnSaveTC');
            const originalText = btnSave.textContent;
            
            btnSave.disabled = true;
            btnSave.textContent = 'Saving...';

            const formData = new FormData(quickTCForm);
            
            fetch("{{ route('invoices.terms.billing.store') }}", {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok) {
                    // Add new term to the list
                    const termsList = document.getElementById('termsList');
                    const newTermHtml = `
                        <div style="margin-bottom: 0.5rem; padding: 0.75rem; border-radius: 8px; border: 1px solid #dcfce7; background: #f0fdf4; transition: all 0.2s;" class="term-item-row">
                            <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer;">
                                <input type="checkbox" class="term-checkbox" data-tc-id="${data.term.id}" data-content="${data.term.content}" value="${data.term.content}" style="margin-top: 0.2rem; width: 16px; height: 16px; cursor: pointer;">
                                <div style="flex: 1;">
                                    <p style="margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.5;">${data.term.content}</p>
                                </div>
                            </label>
                        </div>
                    `;
                    termsList.insertAdjacentHTML('afterbegin', newTermHtml);
                    
                    // Attach change listener to the new checkbox
                    const newCheckbox = termsList.querySelector('.term-checkbox');
                    newCheckbox.addEventListener('change', () => {
                        if (typeof updateInvoicePreview === 'function') updateInvoicePreview();
                        const allCheckboxes = document.querySelectorAll('.term-checkbox');
                        const anyChecked = Array.from(allCheckboxes).some(cb => cb.checked);
                        document.getElementById('finalSubmitBtnStep3').disabled = !anyChecked;
                    });

                    // Success state
                    btnSave.textContent = 'Saved!';
                    setTimeout(() => {
                        tcModal.hide();
                        quickTCForm.reset();
                        btnSave.disabled = false;
                        btnSave.textContent = originalText;
                    }, 800);
                } else {
                    alert('Failed to save term. Please try again.');
                    btnSave.disabled = false;
                    btnSave.textContent = originalText;
                }
            })
            .catch(err => {
                console.error('Error saving term:', err);
                alert('An error occurred. Check the console for details.');
                btnSave.disabled = false;
                btnSave.textContent = originalText;
            });
        });
    }
})();
</script>

@endsection
