@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <p class="eyebrow">Billing</p>
        <h3>Create new invoice</h3>
    </div>
    <a href="{{ route('invoices.index') }}" class="text-link">&larr; Back to invoices</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('invoices.store') }}" class="client-form" id="invoiceForm">
        @csrf
        <div class="form-grid">
            <div>
                <label for="client_id">Select Client *</label>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('client_id') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="invoice_number">Invoice Number *</label>
                <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', 'INV-' . date('Ymd') . '-' . rand(100, 999)) }}" required>
                @error('invoice_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                @error('issue_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="due_date">Due Date *</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+7 days'))) }}" required>
                @error('due_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Items Section --}}
        <div class="items-section" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h4>Invoice Items</h4>
            
            {{-- Add Item Row --}}
            <div class="add-item-row form-grid" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <label for="item_service_id">Service *</label>
                    <select id="item_service_id">
                        <option value="">-- Select Service --</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" 
                                    data-unit-price="{{ $service->unit_price }}" 
                                    data-tax-rate="{{ $service->tax_rate ?? 18 }}">
                                {{ $service->name }} (Rs {{ number_format($service->unit_price, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="item_quantity">Quantity</label>
                    <input type="number" id="item_quantity" value="1" min="0.01" step="0.01">
                </div>
                <div>
                    <label for="item_unit_price">Unit Price (Rs)</label>
                    <input type="number" id="item_unit_price" min="0" step="0.01" readonly>
                </div>
                <div>
                    <label for="item_tax_rate">Tax (%)</label>
                    <input type="number" id="item_tax_rate" min="0" max="100" step="0.01" readonly>
                </div>
                <div style="align-self: end;">
                    <button type="button" id="addItemBtn" class="primary-button" style="height: 100%;">Add Item</button>
                </div>
            </div>

            {{-- Items Table --}}
            <table id="itemsTable" style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; display: none;">
                <thead>
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 1rem; text-align: left;">Service</th>
                        <th style="padding: 1rem; text-align: right; width: 100px;">Qty</th>
                        <th style="padding: 1rem; text-align: right; width: 120px;">Unit Price</th>
                        <th style="padding: 1rem; text-align: right; width: 100px;">Tax %</th>
                        <th style="padding: 1rem; text-align: right; width: 120px;">Line Total</th>
                        <th style="padding: 1rem; width: 80px;"></th>
                    </tr>
                </thead>
                <tbody id="itemsTbody">
                </tbody>
            </table>

            {{-- Summary Panel - Bottom Right --}}
            <div id="invoiceSummary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; float: right; width: 300px; margin-left: 2rem;">
                <h4 style="margin-top: 0;">Invoice Summary</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Subtotal:</span>
                    <strong id="subtotal">Rs 0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>Tax:</span>
                    <strong id="taxTotal">Rs 0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.2em; font-weight: bold; border-top: 2px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.5rem;">
                    <span>Total:</span>
                    <strong id="grandTotal">Rs 0.00</strong>
                </div>
            </div>
        </div>

        <div class="form-actions" style="clear: both;">
            <button type="submit" class="primary-button" id="submitBtn" disabled> Create Invoice </button>
            <a href="{{ route('invoices.index') }}" class="text-link">Cancel</a>
            <input type="hidden" name="subtotal" id="formSubtotal">
            <input type="hidden" name="tax_total" id="formTaxTotal">
            <input type="hidden" name="grand_total" id="formGrandTotal">
            <input type="hidden" name="items_data" id="formItemsData">
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemCounter = 0;
    const items = [];

    // Service select change
    document.getElementById('item_service_id').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        document.getElementById('item_unit_price').value = option.dataset.unitPrice || '';
        document.getElementById('item_tax_rate').value = option.dataset.taxRate || '';
        calculateLineTotal();
    });

    // Qty/Price/Tax change
    ['item_quantity', 'item_unit_price', 'item_tax_rate'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateLineTotal);
    });

    function calculateLineTotal() {
        const qty = parseFloat(document.getElementById('item_quantity').value) || 0;
        const price = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const lineTotal = qty * price;
        // Display line total if needed
    }

    // Add Item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const serviceId = document.getElementById('item_service_id').value;
        if (!serviceId) return alert('Select a service');

        const serviceName = document.getElementById('item_service_id').options[document.getElementById('item_service_id').selectedIndex].text;
        const qty = parseFloat(document.getElementById('item_quantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const taxRate = parseFloat(document.getElementById('item_tax_rate').value) || 0;
        const lineTotal = qty * unitPrice;
        const taxAmount = lineTotal * (taxRate / 100);

        itemCounter++;
        const item = {
            id: itemCounter,
            service_id: serviceId,
            service_name: serviceName,
            quantity: qty,
            unit_price: unitPrice,
            tax_rate: taxRate,
            line_total: lineTotal,
            tax_amount: taxAmount
        };
        items.push(item);

        // Add row to table
        const tbody = document.getElementById('itemsTbody');
        const row = tbody.insertRow();
        row.innerHTML = `
            <td style="padding: 1rem;">${serviceName}</td>
            <td style="padding: 1rem; text-align: right;">${qty.toFixed(2)}</td>
            <td style="padding: 1rem; text-align: right;">Rs ${unitPrice.toFixed(2)}</td>
            <td style="padding: 1rem; text-align: right;">${taxRate}%</td>
            <td style="padding: 1rem; text-align: right;"><strong>Rs ${lineTotal.toFixed(2)}</strong></td>
            <td style="padding: 1rem; text-align: right;">
                <button type="button" class="remove-item" data-id="${itemCounter}" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Remove</button>
            </td>
        `;

        document.getElementById('itemsTable').style.display = 'table';
        updateSummary();
        resetItemInputs();
    });

    // Remove item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            const itemId = parseInt(e.target.dataset.id);
            const index = items.findIndex(item => item.id === itemId);
            if (index > -1) {
                items.splice(index, 1);
                e.target.closest('tr').remove();
                updateSummary();
                if (items.length === 0) {
                    document.getElementById('itemsTable').style.display = 'none';
                }
            }
        }
    });

    function updateSummary() {
        const subtotal = items.reduce((sum, item) => sum + item.line_total, 0);
        const taxTotal = items.reduce((sum, item) => sum + item.tax_amount, 0);
        const grandTotal = subtotal + taxTotal;

        document.getElementById('subtotal').textContent = `Rs ${subtotal.toFixed(2)}`;
        document.getElementById('taxTotal').textContent = `Rs ${taxTotal.toFixed(2)}`;
        document.getElementById('grandTotal').textContent = `Rs ${grandTotal.toFixed(2)}`;

        document.getElementById('formSubtotal').value = subtotal;
        document.getElementById('formTaxTotal').value = taxTotal;
        document.getElementById('formGrandTotal').value = grandTotal;
        document.getElementById('formItemsData').value = JSON.stringify(items.map(item => ({
            service_id: item.service_id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
            line_total: item.line_total
        })));

        document.getElementById('submitBtn').disabled = items.length === 0;
    }

    function resetItemInputs() {
        document.getElementById('item_service_id').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_tax_rate').value = '';
    }
});
</script>
@endsection
