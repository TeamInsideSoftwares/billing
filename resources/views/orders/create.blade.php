@extends('layouts.app')

@section('content')

<section class="section-bar" style="padding: 0.5rem 1rem;">
    <div>
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #64748b;">Create New Order</h3>
    </div>
    <a href="{{ route('orders.index') }}" class="text-link" style="font-size: 0.85rem;">&larr; Back to orders</a>
</section>

<section class="panel-card" style="padding: 1rem;">
    <form method="POST" action="{{ route('orders.store') }}" class="client-form" id="orderForm">
        @csrf
        <div class="form-grid" style="grid-template-columns: repeat(4, 1fr); gap: 0.75rem;">
            <div>
                <label for="clientid" style="font-size: 0.8rem;">Client *</label>
                <select id="clientid" name="clientid" required style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
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
                <label for="order_number" style="font-size: 0.8rem;">Order Number *</label>
                <input type="text" id="order_number" name="order_number" value="{{ old('order_number', 'ORD-' . date('Ymd') . '-' . rand(100, 999)) }}" required style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
                @error('order_number') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="order_title" style="font-size: 0.8rem;">Order Title</label>
                <input type="text" id="order_title" name="order_title" value="{{ old('order_title') }}" style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
            </div>
            <div>
                <label for="status" style="font-size: 0.8rem;">Status *</label>
                <select id="status" name="status" style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
                    <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ old('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="processing" {{ old('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="shipped" {{ old('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                    <option value="delivered" {{ old('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                </select>
            </div>
            <div>
                <label for="order_date" style="font-size: 0.8rem;">Order Date *</label>
                <input type="date" id="order_date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
                @error('order_date') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="delivery_date" style="font-size: 0.8rem;">Delivery Date</label>
                <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date') }}" style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
            </div>
            <div>
                <label for="sales_person_id" style="font-size: 0.8rem;">Sales Person</label>
                <select id="sales_person_id" name="sales_person_id" style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">
                    <option value="">-- Select --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('sales_person_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label for="notes" style="font-size: 0.8rem;">Notes</label>
                <textarea id="notes" name="notes" rows="2" style="font-size: 0.85rem; padding: 0.4rem 0.5rem;">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Items Section --}}
        <div class="items-section" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            <h4 style="font-size: 0.95rem; margin-top: 0; margin-bottom: 0.75rem;">Order Items</h4>

            <div class="add-item-row form-grid" style="background: #f9fafb; padding: 0.9rem; border-radius: 6px; margin-bottom: 0.75rem; grid-template-columns: 2fr 0.7fr 1fr 1fr 1fr 0.7fr auto; gap: 0.6rem; align-items: end;">
                <div>
                    <label for="item_itemid" style="font-size: 0.8rem;">Item *</label>
                    <select id="item_itemid" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                        <option value="">-- Select Item --</option>
                        @php
                            $groupedServices = $services->groupBy(fn($s) => $s->category->name ?? 'No Category');
                        @endphp
                        @foreach($groupedServices as $catName => $catServices)
                            <optgroup label="{{ $catName }}">
                                @foreach($catServices as $service)
                                    @php
                                        $costings = $service->costings->sortBy('currency_code');
                                        $defaultCosting = $costings->first();
                                        $sellingPrice = $defaultCosting?->selling_price ?? 0;
                                        $taxRate = $defaultCosting?->tax_rate ?? 0;
                                    @endphp
                                    <option value="{{ $service->itemid }}"
                                            data-selling-price="{{ $sellingPrice }}"
                                            data-tax-rate="{{ $taxRate }}">
                                        {{ $service->name }} ({{ number_format($sellingPrice, 0) }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="item_quantity" style="font-size: 0.8rem;">Qty</label>
                    <input type="number" id="item_quantity" value="1" min="0.01" step="0.01" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                </div>
                <div>
                    <label for="item_unit_price" style="font-size: 0.8rem;">Price</label>
                    <input type="number" id="item_unit_price" min="0" step="0.01" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                </div>
                <div>
                    <label for="item_frequency" style="font-size: 0.8rem;">Frequency</label>
                    <select id="item_frequency" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                        <option value="">--</option>
                        <option value="one-time">One-Time</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="bi-weekly">Bi-Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="semi-annually">Semi-Annually</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label for="item_duration" style="font-size: 0.8rem;">Duration</label>
                    <input type="text" id="item_duration" placeholder="e.g. 12" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                </div>
                <div>
                    <label for="item_users" style="font-size: 0.8rem;">Users</label>
                    <input type="number" id="item_users" value="1" min="1" style="font-size: 0.9rem; padding: 0.45rem 0.55rem;">
                </div>
                <div style="align-self: end;">
                    <button type="button" id="addItemBtn" class="primary-button" style="height: auto; padding: 0.55rem 1.05rem; font-size: 0.9rem;">Add</button>
                </div>
            </div>

            <table id="itemsTable" style="width: 100%; border-collapse: collapse; margin-bottom: 1rem; display: none; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #f3f4f6;">
                        <th style="padding: 0.65rem 0.8rem; text-align: left; font-size: 0.82rem;">Item</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 80px; font-size: 0.82rem;">Qty</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 120px; font-size: 0.82rem;">Price</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 110px; font-size: 0.82rem;">Frequency</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 95px; font-size: 0.82rem;">Duration</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 80px; font-size: 0.82rem;">Users</th>
                        <th style="padding: 0.65rem 0.55rem; text-align: right; width: 110px; font-size: 0.82rem;">Total</th>
                        <th style="padding: 0.6rem 0.5rem; width: 40px;"></th>
                    </tr>
                </thead>
                <tbody id="itemsTbody">
                </tbody>
            </table>

            <div id="orderSummary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem; float: right; width: 250px; margin-left: 1rem;">
                <h4 style="margin-top: 0; font-size: 0.9rem;">Summary</h4>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.85rem;">
                    <span>Subtotal:</span>
                    <strong id="subtotal">0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.85rem;">
                    <span>Tax:</span>
                    <strong id="taxTotal">0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1rem; font-weight: bold; border-top: 2px solid #e2e8f0; padding-top: 0.35rem; margin-top: 0.35rem;">
                    <span>Total:</span>
                    <strong id="grandTotal">0.00</strong>
                </div>
            </div>
        </div>

        <div class="form-actions" style="clear: both; margin-top: 0.75rem;">
            <button type="submit" class="primary-button" id="submitBtn" disabled style="padding: 0.5rem 1.25rem; font-size: 0.9rem;"> Create Order </button>
            <a href="{{ route('orders.index') }}" class="text-link" style="font-size: 0.85rem;">Cancel</a>
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
            document.getElementById('item_unit_price').value = option.dataset.sellingPrice || '0';
        } else {
            document.getElementById('item_unit_price').value = '';
        }
    });

    // Add Item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const serviceId = document.getElementById('item_itemid').value;
        if (!serviceId) return alert('Select an item');

        const serviceName = document.getElementById('item_itemid').options[document.getElementById('item_itemid').selectedIndex].text.split(' (')[0];
        const qty = parseFloat(document.getElementById('item_quantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const frequency = document.getElementById('item_frequency').value || '';
        const duration = document.getElementById('item_duration').value || '';
        const users = parseInt(document.getElementById('item_users').value) || 1;
        const lineTotal = qty * unitPrice;
        const selectedOption = document.getElementById('item_itemid').options[document.getElementById('item_itemid').selectedIndex];
        const taxRate = parseFloat(selectedOption?.dataset?.taxRate || '0') || 0;
        const taxAmount = (lineTotal * taxRate) / 100;

        itemCounter++;
        const item = {
            id: itemCounter,
            itemid: serviceId,
            item_name: serviceName,
            quantity: qty,
            unit_price: unitPrice,
            frequency: frequency,
            duration: duration,
            no_of_users: users,
            line_total: lineTotal,
            tax_rate: taxRate,
            tax_amount: taxAmount
        };
        items.push(item);

        const freqLabels = {'one-time':'One-Time','daily':'Daily','weekly':'Weekly','bi-weekly':'Bi-Weekly','monthly':'Monthly','quarterly':'Quarterly','semi-annually':'Semi-Annually','yearly':'Yearly'};
        const freqText = frequency ? (freqLabels[frequency] || frequency) : '—';

        const row = document.createElement('tr');
        row.dataset.itemId = itemCounter;
        row.innerHTML = `
            <td style="padding: 0.4rem 0.6rem;">${serviceName}</td>
            <td style="padding: 0.45rem 0.55rem; text-align: right;"><input type="number" class="item-qty" value="${qty}" min="0.01" step="0.01" style="width: 72px; text-align: right; font-size: 0.88rem; padding: 0.25rem 0.35rem;"></td>
            <td style="padding: 0.45rem 0.55rem; text-align: right;"><input type="number" class="item-price" value="${unitPrice}" min="0" step="0.01" style="width: 110px; text-align: right; font-size: 0.88rem; padding: 0.25rem 0.35rem;"></td>
            <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
            <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${duration || '—'}</td>
            <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${users}</td>
            <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${lineTotal.toFixed(2)}</strong></td>
            <td style="padding: 0.4rem 0.5rem; text-align: right;"><button type="button" class="remove-item icon-action-btn delete" data-id="${itemCounter}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(row);

        document.getElementById('itemsTable').style.display = 'table';
        updateSummary();
        resetItemInputs();
    });

    // Inline edit
    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-qty') || e.target.classList.contains('item-price')) {
            const row = e.target.closest('tr');
            const itemId = parseInt(row.dataset.itemId);
            const item = items.find(i => i.id === itemId);
            if (item) {
                item.quantity = parseFloat(row.querySelector('.item-qty').value) || 0;
                item.unit_price = parseFloat(row.querySelector('.item-price').value) || 0;
                item.line_total = item.quantity * item.unit_price;
                item.tax_amount = (item.line_total * (item.tax_rate || 0)) / 100;
                row.querySelector('.item-line-total strong').textContent = item.line_total.toFixed(2);
                updateSummary();
            }
        }
    });

    // Remove item
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-item');
        if (btn) {
            const itemId = parseInt(btn.dataset.id);
            const index = items.findIndex(item => item.id === itemId);
            if (index > -1) {
                items.splice(index, 1);
                btn.closest('tr').remove();
                updateSummary();
                if (items.length === 0) {
                    document.getElementById('itemsTable').style.display = 'none';
                }
            }
        }
    });

    function updateSummary() {
        const subtotal = items.reduce((sum, item) => sum + item.line_total, 0);
        const taxTotal = items.reduce((sum, item) => sum + (item.tax_amount || 0), 0);
        const grandTotal = subtotal + taxTotal;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('taxTotal').textContent = taxTotal.toFixed(2);
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
        document.getElementById('formSubtotal').value = subtotal;
        document.getElementById('formTaxTotal').value = taxTotal;
        document.getElementById('formGrandTotal').value = grandTotal;
        document.getElementById('formItemsData').value = JSON.stringify(items.map(item => ({
            itemid: item.itemid,
            quantity: item.quantity,
            unit_price: item.unit_price,
            frequency: item.frequency,
            duration: item.duration,
            no_of_users: item.no_of_users,
            line_total: item.line_total,
            tax_rate: item.tax_rate || 0
        })));

        document.getElementById('submitBtn').disabled = items.length === 0;
    }

    function resetItemInputs() {
        document.getElementById('item_itemid').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_frequency').value = '';
        document.getElementById('item_duration').value = '';
        document.getElementById('item_users').value = 1;
    }
});
</script>
@endsection
