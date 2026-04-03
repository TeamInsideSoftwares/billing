@extends('layouts.app')

@section('content')

<section class="section-bar">
    <div>
        <p class="eyebrow">Billing</p>
        <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Create New Invoice</h3>
    </div>
    <a href="{{ route('invoices.index') }}" class="text-link">&larr; Back to invoices</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('invoices.store') }}" class="client-form" id="invoiceForm">
        @csrf
        <div class="form-grid">
            <div>
                <label for="clientid">Select Client *</label>
                <select id="clientid" name="clientid" required>
                    <option value="">-- Choose Client --</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" {{ old('clientid') == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
                @error('clientid') <span class="error">{{ $message }}</span> @enderror
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
        <div class="items-section" style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
            <h4>Invoice Items</h4>
            
            {{-- Add Item Row --}}
            <div class="add-item-row form-grid" style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <div>
                    <label for="item_itemid">Item *</label>
                    <select id="item_itemid">
                        <option value="">-- Select Item --</option>
                        @foreach($services as $service)
                            <option value="{{ $service->itemid }}" 
                                    data-unit-price="{{ $service->unit_price }}" 
                                    data-tax-rate="{{ $service->tax_rate ?? 18 }}">
                                {{ $service->name }} (Rs {{ number_format($service->unit_price, 0) }})
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
                    <input type="number" id="item_unit_price" min="0" step="0.01">
                </div>
                <div>
                    <label for="item_tax_rate">Tax (%)</label>
                    <input type="number" id="item_tax_rate" min="0" max="100" step="0.01">
                </div>
                <div style="align-self: end;">
                    <button type="button" id="addItemBtn" class="primary-button" style="height: 100%;">Add Item</button>
                </div>
            </div>

            {{-- Items Table --}}
            <table id="itemsTable" style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; display: none;">
                <thead>
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 1rem; text-align: left;">Item</th>
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
            <div id="invoiceSummary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; float: right; width: 300px; margin-left: 1.25rem;">
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

    const tbody = document.getElementById('itemsTbody');

    // Item select change
    document.getElementById('item_itemid').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            document.getElementById('item_unit_price').value = option.dataset.unitPrice || '0';
            document.getElementById('item_tax_rate').value = option.dataset.taxRate || '0';
        } else {
            document.getElementById('item_unit_price').value = '';
            document.getElementById('item_tax_rate').value = '';
        }
    });

    // Add Item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const serviceId = document.getElementById('item_itemid').value;
        if (!serviceId) return alert('Select an item');

        const serviceName = document.getElementById('item_itemid').options[document.getElementById('item_itemid').selectedIndex].text;
        const qty = parseFloat(document.getElementById('item_quantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const taxRate = parseFloat(document.getElementById('item_tax_rate').value) || 0;
        const lineTotal = qty * unitPrice;
        const taxAmount = lineTotal * (taxRate / 100);

        itemCounter++;
        const item = {
            id: itemCounter,
            itemid: serviceId,
            item_name: serviceName,
            quantity: qty,
            unit_price: unitPrice,
            tax_rate: taxRate,
            line_total: lineTotal,
            tax_amount: taxAmount
        };
        items.push(item);

        const row = document.createElement('tr');
        row.dataset.itemId = itemCounter;
        row.innerHTML = `
            <td style="padding: 1rem;">
                ${serviceName}
            </td>
            <td style="padding: 1rem; text-align: right;">
                <input type="number" class="item-qty" value="${qty}" min="0.01" step="0.01" style="width: 100px; text-align: right;">
            </td>
            <td style="padding: 1rem; text-align: right;">
                <input type="number" class="item-price" value="${unitPrice}" min="0" step="0.01" style="width: 100px; text-align: right;">
            </td>
            <td style="padding: 1rem; text-align: right;">
                <input type="number" class="item-tax" value="${taxRate}" min="0" max="100" step="0.01" style="width: 100px; text-align: right;">
            </td>
            <td style="padding: 1rem; text-align: right;" class="item-line-total"><strong>Rs ${lineTotal.toFixed(2)}</strong></td>
            <td style="padding: 1rem; text-align: right;">
                <button type="button" class="remove-item icon-action-btn delete" data-id="${itemCounter}" title="Remove item">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        document.getElementById('itemsTable').style.display = 'table';
        updateSummary();
        resetItemInputs();
    });

    // Inline edit
    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price') || e.target.classList.contains('item-tax')) {
            const row = e.target.closest('tr');
            const itemId = parseInt(row.dataset.itemId);
            const item = items.find(i => i.id === itemId);
            if (item) {
                item.quantity = parseFloat(row.querySelector('.item-qty').value) || 0;
                item.unit_price = parseFloat(row.querySelector('.item-price').value) || 0;
                item.tax_rate = parseFloat(row.querySelector('.item-tax').value) || 0;
                item.line_total = item.quantity * item.unit_price;
                item.tax_amount = item.line_total * (item.tax_rate / 100);
                row.querySelector('.item-line-total strong').textContent = `Rs ${item.line_total.toFixed(2)}`;
                updateSummary();
            }
        }
    });

    // Remove item
    tbody.addEventListener('click', function(e) {
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
            itemid: item.itemid,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
            line_total: item.line_total
        })));

        document.getElementById('submitBtn').disabled = items.length === 0;
    }

    function resetItemInputs() {
        document.getElementById('item_itemid').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_tax_rate').value = '';
    }
});
</script>
@endsection



