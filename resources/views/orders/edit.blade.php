@extends('layouts.app')

@section('content')

<section class="section-bar" style="padding: 0.5rem 1rem;">
    <div>
        <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #64748b;">Edit {{ $order->order_number }}</h3>
    </div>
    <a href="{{ route('orders.index', ['c' => $order->clientid]) }}" class="text-link" style="font-size: 0.85rem;">&larr; Back to orders</a>
</section>

<section class="panel-card" style="padding: 1rem;">
    <form method="POST" action="{{ route('orders.update', ['order' => $order, 'c' => $clientId]) }}" class="client-form" id="orderForm" enctype="multipart/form-data">
        @method('PUT')
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger alert-sm" style="margin-bottom: 1rem;">
                <strong>Please fix the following errors:</strong>
                <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row">
            {{-- LEFT COLUMN: Order Details (narrower) --}}
            <div class="col-3">
                <div style="position: sticky; top: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem; margin-bottom: 1rem;">

                        <h5 style="margin: 0; font-size: 1rem; font-weight: 600; color: #0f172a;">
                            <i class="fas fa-clipboard-list" style="color: #3b82f6; font-size: 1.1rem; margin-right: 0.5rem;"></i>
                            Order Details
                        </h5>

                        <div style="background: #f1f5f9; padding: 0.4rem 0.7rem; border-radius: 6px; font-size: 0.85rem;">
                            <span style="color: #0f172a; font-weight: 500;">
                                {{ old('order_number', $order->order_number) }}
                            </span>
                        </div>

                    </div>

                    <div class="mb-3">
                        <select id="clientid" name="clientid_disabled" disabled class="form-control form-control-sm">
                            <option value="">-- Choose Client --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->clientid }}" {{ old('clientid', $order->clientid) == $client->clientid ? 'selected' : '' }}>
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('clientid') <span class="error">{{ $message }}</span> @enderror
                        <input type="hidden" name="clientid" value="{{ old('clientid', $order->clientid) }}">
                        <input type="hidden" name="order_number" value="{{ old('order_number', $order->order_number) }}">
                    </div>

                    <div class="mb-3">
                        <input type="text" id="order_title" name="order_title" value="{{ old('order_title', $order->order_title) }}" class="form-control form-control-sm" placeholder="Order Title/Details">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $order->order_date ? $order->order_date->format('Y-m-d') : '') }}" required class="form-control form-control-sm">
                            @error('order_date') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 mb-3">
                            <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', $order->delivery_date ? $order->delivery_date->format('Y-m-d') : '') }}" class="form-control form-control-sm">
                        </div>
                    </div>


                    <div class="mb-3">
                        <select id="sales_person_id" name="sales_person_id" class="form-control form-control-sm">
                            <option value="">-- Select Sales Person --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('sales_person_id', $order->sales_person_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Purchase Order Section --}}
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.75rem; margin-bottom: 0.75rem;">
                        <h6 style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 0.75rem;">Purchase Order</h6>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <input type="text" id="po_number" name="po_number" value="{{ old('po_number', $order->po_number) }}" class="form-control form-control-sm" placeholder="PO Number">
                            </div>
                            <div class="col-6 mb-3">
                                <input type="date" id="po_date" name="po_date" value="{{ old('po_date', $order->po_date ? $order->po_date->format('Y-m-d') : '') }}" class="form-control form-control-sm" placeholder="PO Date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label style="font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 0.25rem;">PO Upload</label>
                            @if($order->po_file)
                                <div style="font-size: 0.75rem; margin-bottom: 0.25rem;">
                                    <a href="{{ asset('storage/' . $order->po_file) }}" target="_blank" class="text-link">
                                        <i class="fas fa-file"></i> View Current File
                                    </a>
                                </div>
                            @endif
                            <input type="file" id="po_file" name="po_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small style="font-size: 0.7rem; color: #94a3b8;">Leave empty to keep existing file</small>
                        </div>
                    </div>

                    {{-- Agreement Section --}}
                    <div style="border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-bottom: 0.75rem;">
                        <h6 style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 0.75rem;">Agreement</h6>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <input type="text" id="agreement_ref" name="agreement_ref" value="{{ old('agreement_ref', $order->agreement_ref) }}" class="form-control form-control-sm" placeholder="Agreement Ref">
                            </div>
                            <div class="col-6 mb-3">
                                <input type="date" id="agreement_date" name="agreement_date" value="{{ old('agreement_date', $order->agreement_date ? $order->agreement_date->format('Y-m-d') : '') }}" class="form-control form-control-sm" placeholder="Agreement Date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label style="font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 0.25rem;">Agreement Upload</label>
                            @if($order->agreement_file)
                                <div style="font-size: 0.75rem; margin-bottom: 0.25rem;">
                                    <a href="{{ asset('storage/' . $order->agreement_file) }}" target="_blank" class="text-link">
                                        <i class="fas fa-file"></i> View Current File
                                    </a>
                                </div>
                            @endif
                            <input type="file" id="agreement_file" name="agreement_file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <small style="font-size: 0.7rem; color: #94a3b8;">Leave empty to keep existing file</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <textarea id="notes" name="notes" rows="3" class="form-control form-control-sm" placeholder="Notes">{{ old('notes', $order->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: Order Items (wider) --}}
            <div class="col-9">

                {{-- Items Section --}}
                <div class="items-section">
                    <!-- <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">
                            <i class="fas fa-info-circle" style="margin-right: 0.35rem;"></i>
                            Changes will be saved when you click "Update Order"
                        </p>
                    </div> -->

                    <div class="add-item-row form-grid" style="background: #f9fafb; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; flex-wrap: nowrap; gap: 0.5rem; align-items: end;">
                        <div style="flex: 2; min-width: 150px;">
                            <label style="font-size: 0.75rem;">Item</label>
                            <select id="item_itemid" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
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
                                                $taxIncluded = $defaultCosting?->tax_included ?? 'no';
                                            @endphp
                                            <option value="{{ $service->itemid }}"
                                                    data-selling-price="{{ $sellingPrice }}"
                                                    data-tax-rate="{{ $taxRate }}"
                                                    data-tax-included="{{ $taxIncluded }}">
                                                {{ $service->name }} ({{ number_format($sellingPrice, 0) }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 0.6; min-width: 60px;">
                            <label style="font-size: 0.75rem;">Qty</label>
                            <input type="number" id="item_quantity" value="1" min="0.01" step="0.01" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex: 0.8; min-width: 80px;">
                            <label style="font-size: 0.75rem;">Price</label>
                            <input type="number" id="item_unit_price" min="0" step="0.01" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        @if($account->allow_multi_taxation)
                        <div style="flex: 0.8; min-width: 80px;">
                            <label style="font-size: 0.75rem;">Tax% <a href="#" id="open-tax-modal-order-edit" style="font-size:10px;margin-left:2px;" class="text-link">+</a></label>
                            <select id="item_tax_rate" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                                <option value="0">No Tax</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->rate }}">{{ $tax->tax_name }} ({{ number_format($tax->rate, 2) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" id="item_tax_rate" value="{{ $account->fixed_tax_rate ?? 0 }}">
                        @endif
                        @if($account->have_users)
                        <div style="flex: 0.6; min-width: 55px;">
                            <label style="font-size: 0.75rem;">Users</label>
                            <input type="number" id="item_users" value="1" min="1" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        @else
                        <input type="hidden" id="item_users" value="1">
                        @endif
                        <div style="flex: 0.6; min-width: 70px;">
                            <label style="font-size: 0.75rem;">Disc%</label>
                            <input type="number" id="item_discount" value="0" min="0" max="100" step="0.01" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex: 0.8; min-width: 80px;">
                            <label style="font-size: 0.75rem;">Freq</label>
                            <select id="item_frequency" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
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
                        <div style="flex: 0.6; min-width: 60px;">
                            <label style="font-size: 0.75rem;">Dur</label>
                            <input type="text" id="item_duration" placeholder="12" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex: 0.8; min-width: 90px;">
                            <label style="font-size: 0.75rem;">Start</label>
                            <input type="date" id="item_start_date" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex: 0.8; min-width: 90px;">
                            <label style="font-size: 0.75rem;">End</label>
                            <input type="date" id="item_end_date" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex: 0.8; min-width: 90px;">
                            <label style="font-size: 0.75rem;">Delivery</label>
                            <input type="date" id="item_delivery_date" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div style="flex-shrink: 0;">
                            <button type="button" id="addItemBtn" class="btn btn-sm btn-primary" style="padding: 0.35rem 0.85rem; font-size: 0.8rem; white-space: nowrap;">Add</button>
                        </div>
                    </div>

                    <table id="itemsTable" style="width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f3f4f6;">
                                <th style="padding: 0.75rem 0.85rem; text-align: left; font-size: 0.82rem; font-weight: 600;">Item</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 85px; font-size: 0.82rem; font-weight: 600;">Qty</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 120px; font-size: 0.82rem; font-weight: 600;">Price</th>
                                @if($account->allow_multi_taxation)
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 85px; font-size: 0.82rem; font-weight: 600;">Tax %</th>
                                @endif
                                @if($account->have_users)
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 85px; font-size: 0.82rem; font-weight: 600;">Users</th>
                                @endif
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 75px; font-size: 0.82rem; font-weight: 600;">Disc%</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 115px; font-size: 0.82rem; font-weight: 600;">Freq</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 100px; font-size: 0.82rem; font-weight: 600;">Dur</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 105px; font-size: 0.82rem; font-weight: 600;">Start</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 105px; font-size: 0.82rem; font-weight: 600;">End</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 90px; font-size: 0.82rem; font-weight: 600;">Delivery</th>
                                <th style="padding: 0.75rem 0.6rem; text-align: right; width: 115px; font-size: 0.82rem; font-weight: 600;">Total</th>
                                <th style="padding: 0.7rem 0.55rem; width: 85px;"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTbody">
                            @foreach($items as $item)
                                @php
                                    $lineTotal = (float) ($item->line_total ?? 0);
                                @endphp
                                <tr data-item-id="{{ $loop->index + 1 }}">
                                    <td style="padding: 0.5rem 0.65rem;">{{ $item->item->name ?? $item->item_name }}</td>
                                    <td style="padding: 0.5rem 0.6rem; text-align: right; font-size: 0.82rem;">{{ $item->quantity }}</td>
                                    <td style="padding: 0.5rem 0.6rem; text-align: right; font-size: 0.82rem;">{{ $item->unit_price }}</td>
                                    @if($account->allow_multi_taxation)
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ number_format($item->tax_rate ?? 0, 2) }}%</td>
                                    @endif
                                    @if($account->have_users)
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ $item->no_of_users ?? '—' }}</td>
                                    @endif
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ ($item->discount_percent ?? 0) > 0 ? number_format($item->discount_percent, 0) . '%' : '—' }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ ucfirst($item->frequency ?? '—') }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ $item->duration ?? '—' }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ $item->start_date ? $item->start_date->format('d M Y') : '—' }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ $item->end_date ? $item->end_date->format('d M Y') : '—' }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; font-size: 0.78rem;">{{ $item->delivery_date ? $item->delivery_date->format('d M Y') : '—' }}</td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right;" class="item-line-total"><strong>{{ number_format($lineTotal, 0) }}</strong></td>
                                    <td style="padding: 0.5rem 0.55rem; text-align: right; white-space: nowrap;">
                                        <button type="button" class="edit-item icon-action-btn edit" data-id="{{ $loop->index + 1 }}" title="Edit" style="padding: 0.2rem 0.35rem; font-size: 0.75rem; margin-right: 0.25rem;"><i class="fas fa-edit"></i></button>
                                        <button type="button" class="remove-item icon-action-btn delete" data-id="{{ $loop->index + 1 }}" title="Remove" style="padding: 0.2rem 0.35rem; font-size: 0.75rem;"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="orderSummary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; max-width: 350px; margin-left: auto;">
                        <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 600;">Order Summary</h4>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span style="color: #64748b;">Subtotal:</span>
                            <strong id="subtotal">{{ number_format($order->subtotal, 0) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span style="color: #64748b;">Discount:</span>
                            <strong id="discountTotal" style="color: #dc2626;">{{ number_format($order->discount_total ?? 0, 0) }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span style="color: #64748b;">Tax:</span>
                            <strong id="taxTotal">0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700; border-top: 2px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.5rem;">
                            <span>Total:</span>
                            <strong id="grandTotal" style="color: #3b82f6;">{{ number_format($order->grand_total, 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <button type="submit" class="btn btn-primary btn-sm" id="submitBtn">
                <i class="fas fa-save" style="margin-right: 0.35rem;"></i>Update Order
            </button>
            <a href="{{ route('orders.index', ['c' => $clientId]) }}" class="btn btn-secondary btn-sm" style="margin-left: 0.5rem;">
                <i class="fas fa-times" style="margin-right: 0.35rem;"></i>Cancel
            </a>
            <input type="hidden" name="subtotal" id="formSubtotal">
            <input type="hidden" name="discount_total" id="formDiscountTotal">
            <input type="hidden" name="tax_total" id="formTaxTotal">
            <input type="hidden" name="grand_total" id="formGrandTotal">
            <input type="hidden" name="items_data" id="formItemsData">
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemCounter = {{ $items->count() }};
    const items = [
        @foreach($items as $item)
            @php
                $lineTotal = (float) ($item->line_total ?? 0);
                $taxRate = (float) ($item->tax_rate ?? 0);
                $discountPercent = (float) ($item->discount_percent ?? 0);
                $discountAmount = (float) ($item->discount_amount ?? 0);
                $taxAmount = ($lineTotal * $taxRate) / 100;
            @endphp
            {
                id: {{ $loop->index + 1 }},
                order_item_id: '{{ $item->orderitemid }}',
                itemid: '{{ $item->itemid }}',
                item_name: '{{ $item->item->name ?? $item->item_name }}',
                quantity: {{ $item->quantity }},
                unit_price: {{ $item->unit_price }},
                frequency: '{{ $item->frequency ?? '' }}',
                duration: '{{ $item->duration ?? '' }}',
                no_of_users: {{ $item->no_of_users ?? 1 }},
                start_date: '{{ $item->start_date ? $item->start_date->format("Y-m-d") : "" }}',
                end_date: '{{ $item->end_date ? $item->end_date->format("Y-m-d") : "" }}',
                delivery_date: '{{ $item->delivery_date ? $item->delivery_date->format("Y-m-d") : "" }}',
                line_total: {{ $lineTotal }},
                discount_percent: {{ $discountPercent }},
                discount_amount: {{ $discountAmount }},
                tax_rate: {{ $taxRate }},
                tax_amount: {{ $taxAmount }}
            }@if(!$loop->last), @endif
        @endforeach
    ];

    const tbody = document.getElementById('itemsTbody');
    let editingItemId = null;
    updateSummary();

    // Initialize: Set item delivery date to match order delivery date on load
    const orderDeliveryDate = document.getElementById('delivery_date').value || '';
    if (orderDeliveryDate) {
        document.getElementById('item_delivery_date').value = orderDeliveryDate;
        
        // Also update any existing items that don't have a delivery date
        items.forEach(item => {
            if (!item.delivery_date) {
                item.delivery_date = orderDeliveryDate;
            }
        });
    }
    
    // When order delivery date changes, update all items
    document.getElementById('delivery_date').addEventListener('change', function() {
        const newDeliveryDate = this.value || '';
        document.getElementById('item_delivery_date').value = newDeliveryDate;

        // Update all existing items in the JavaScript array
        items.forEach(item => {
            item.delivery_date = newDeliveryDate;
        });

        // Update the delivery date display in the table (column index 8)
        tbody.querySelectorAll('tr').forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 8) {
                cells[8].textContent = newDeliveryDate ? newDeliveryDate.split('-').reverse().join(' ') : '—';
            }
        });
        
        // Update the hidden form field to ensure it's submitted
        updateSummary();
    });

    // Helper function to calculate end date based on frequency and duration
    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration) return '';

        const start = new Date(startDate);
        const durationNum = parseFloat(duration);
        if (isNaN(durationNum) || durationNum <= 0) return '';

        let endDate = new Date(start);

        switch(frequency) {
            case 'daily':
                endDate.setDate(endDate.getDate() + durationNum - 1);
                break;
            case 'weekly':
                endDate.setDate(endDate.getDate() + (durationNum * 7) - 1);
                break;
            case 'bi-weekly':
                endDate.setDate(endDate.getDate() + (durationNum * 14) - 1);
                break;
            case 'monthly':
                endDate.setMonth(endDate.getMonth() + durationNum);
                endDate.setDate(endDate.getDate() - 1);
                break;
            case 'quarterly':
                endDate.setMonth(endDate.getMonth() + (durationNum * 3));
                endDate.setDate(endDate.getDate() - 1);
                break;
            case 'semi-annually':
                endDate.setMonth(endDate.getMonth() + (durationNum * 6));
                endDate.setDate(endDate.getDate() - 1);
                break;
            case 'yearly':
                endDate.setFullYear(endDate.getFullYear() + durationNum);
                endDate.setDate(endDate.getDate() - 1);
                break;
            case 'one-time':
            default:
                return '';
        }

        return endDate.toISOString().split('T')[0];
    }

    // Helper function to calculate line total: qty × price × users
    // If duration exists, also multiply by duration
    function calculateLineTotal(qty, unitPrice, users, frequency, duration) {
        let total = qty * unitPrice * users;
        
        // If duration is provided, multiply by duration
        if (duration && frequency && frequency !== 'one-time') {
            const durationNum = parseFloat(duration);
            if (!isNaN(durationNum) && durationNum > 0) {
                total = total * durationNum;
            }
        }
        
        return total;
    }

    // Auto-populate item delivery date when order delivery date changes
    document.getElementById('delivery_date').addEventListener('change', function() {
        const orderDeliveryDate = this.value || '';
        document.getElementById('item_delivery_date').value = orderDeliveryDate;
    });

    // Item select change - auto-fill price
    document.getElementById('item_itemid').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            document.getElementById('item_unit_price').value = option.dataset.sellingPrice || '0';
        } else {
            document.getElementById('item_unit_price').value = '';
        }
    });

    // Handle frequency change - hide/show start and end date for one-time
    document.getElementById('item_frequency').addEventListener('change', function() {
        const frequency = this.value;
        const startDateField = document.getElementById('item_start_date').closest('div');
        const endDateField = document.getElementById('item_end_date').closest('div');
        
        if (frequency === 'one-time' || frequency === '') {
            // Hide start and end date for one-time or no frequency
            if (startDateField) startDateField.style.display = 'none';
            if (endDateField) endDateField.style.display = 'none';
            // Clear the values
            document.getElementById('item_start_date').value = '';
            document.getElementById('item_end_date').value = '';
        } else {
            // Show start and end date for recurring frequencies
            if (startDateField) startDateField.style.display = 'block';
            if (endDateField) endDateField.style.display = 'block';
        }
    });

    // Auto-calculate end date when start date, frequency, or duration changes
    ['item_start_date', 'item_frequency', 'item_duration'].forEach(fieldId => {
        document.getElementById(fieldId).addEventListener('change', function() {
            const startDate = document.getElementById('item_start_date').value;
            const frequency = document.getElementById('item_frequency').value;
            const duration = document.getElementById('item_duration').value;
            
            if (startDate && frequency && duration) {
                const endDate = calculateEndDate(startDate, frequency, duration);
                document.getElementById('item_end_date').value = endDate;
            }
        });
    });

    // Add Item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const serviceId = document.getElementById('item_itemid').value;
        if (!serviceId) return alert('Select an item');

        const serviceOption = document.getElementById('item_itemid').options[document.getElementById('item_itemid').selectedIndex];
        const serviceName = serviceOption.text.split(' (')[0];
        const taxIncluded = serviceOption.dataset.taxIncluded || 'no';
        
        const qty = parseFloat(document.getElementById('item_quantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const frequency = document.getElementById('item_frequency').value || '';
        const duration = document.getElementById('item_duration').value || '';
        const users = parseInt(document.getElementById('item_users').value) || 1;
        const startDate = document.getElementById('item_start_date').value || '';
        const endDate = document.getElementById('item_end_date').value || '';
        const deliveryDate = document.getElementById('item_delivery_date').value || '';

        // Calculate line total with users and duration multiplier
        const lineTotal = calculateLineTotal(qty, unitPrice, users, frequency, duration);
        const discountPercent = parseFloat(document.getElementById('item_discount').value) || 0;
        const discountAmount = (lineTotal * discountPercent) / 100;

        // Get tax rate from dropdown if multi-taxation is enabled, otherwise use fixed tax rate
        @if($account->allow_multi_taxation)
        const taxRate = parseFloat(document.getElementById('item_tax_rate').value) || 0;
        @else
        const taxRate = {{ $account->fixed_tax_rate ?? 0 }};
        @endif
        
        // Calculate tax based on whether it's included or excluded
        let taxAmount = 0;
        let lineTotalWithoutTax = lineTotal - discountAmount;
        
        if (taxIncluded === 'yes') {
            // Tax is included in the price - extract the tax amount
            taxAmount = (lineTotalWithoutTax * taxRate) / (100 + taxRate);
        } else {
            // Tax is excluded - add tax on top
            taxAmount = (lineTotalWithoutTax * taxRate) / 100;
        }

        if (editingItemId) {
            // Update existing item via AJAX
            const item = items.find(i => i.id === editingItemId);
            if (item && item.order_item_id) {
                // Disable button during save
                const btn = document.getElementById('addItemBtn');
                btn.disabled = true;
                btn.textContent = 'Updating...';

                // Update item via AJAX
                fetch(`{{ url('/orders') }}/{{ $order->orderid }}/update-item/${item.order_item_id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        itemid: serviceId,
                        quantity: qty,
                        unit_price: unitPrice,
                        frequency: frequency,
                        duration: duration,
                        no_of_users: users,
                        start_date: startDate,
                        end_date: endDate,
                        delivery_date: deliveryDate,
                        line_total: lineTotal,
                        discount_percent: discountPercent,
                        discount_amount: discountAmount,
                        tax_rate: taxRate
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update local item data
                        item.itemid = serviceId;
                        item.item_name = serviceName;
                        item.quantity = qty;
                        item.unit_price = unitPrice;
                        item.frequency = frequency;
                        item.duration = duration;
                        item.no_of_users = users;
                        item.start_date = startDate;
                        item.end_date = endDate;
                        item.delivery_date = deliveryDate;
                        item.line_total = lineTotal;
                        item.discount_percent = discountPercent;
                        item.discount_amount = discountAmount;
                        item.tax_rate = taxRate;
                        item.tax_amount = taxAmount;

                        const freqLabels = {'one-time':'One-Time','daily':'Daily','weekly':'Weekly','bi-weekly':'Bi-Weekly','monthly':'Monthly','quarterly':'Quarterly','semi-annually':'Semi-Annually','yearly':'Yearly'};
                        const freqText = frequency ? (freqLabels[frequency] || frequency) : '—';
                        const durationDisplay = duration || '—';

                        const row = tbody.querySelector(`[data-item-id="${editingItemId}"]`);
                        if (row) {
                            row.innerHTML = `
                                <td style="padding: 0.4rem 0.6rem;">${serviceName}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${qty}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${unitPrice}</td>
                                @if($account->allow_multi_taxation)
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${taxRate}%</td>
                                @endif
                                @if($account->have_users)
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${users}</td>
                                @endif
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${discountPercent > 0 ? discountPercent + '%' : '—'}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${durationDisplay}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${startDate || '—'}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${endDate || '—'}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${deliveryDate || '—'}</td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${Math.round(lineTotal)}</strong></td>
                                <td style="padding: 0.4rem 0.5rem; text-align: right; white-space: nowrap;">
                                    <button type="button" class="edit-item icon-action-btn edit" data-id="${editingItemId}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;"><i class="fas fa-edit"></i></button>
                                    <button type="button" class="remove-item icon-action-btn delete" data-id="${editingItemId}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
                                </td>
                            `;
                        }

                        updateSummary();
                        resetItemInputs();
                        editingItemId = null;
                        btn.textContent = 'Add';
                        btn.disabled = false;
                        
                        showToast('success', 'Item updated successfully');
                    } else {
                        throw new Error(data.message || 'Failed to update item');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.textContent = 'Update';
                    alert('Error: ' + error.message);
                });
            } else {
                // Item not yet saved to database, just update locally
                item.itemid = serviceId;
                item.item_name = serviceName;
                item.quantity = qty;
                item.unit_price = unitPrice;
                item.frequency = frequency;
                item.duration = duration;
                item.no_of_users = users;
                item.start_date = startDate;
                item.end_date = endDate;
                item.delivery_date = deliveryDate;
                item.line_total = lineTotal;
                item.tax_rate = taxRate;
                item.tax_amount = taxAmount;

                const freqLabels = {'one-time':'One-Time','daily':'Daily','weekly':'Weekly','bi-weekly':'Bi-Weekly','monthly':'Monthly','quarterly':'Quarterly','semi-annually':'Semi-Annually','yearly':'Yearly'};
                const freqText = frequency ? (freqLabels[frequency] || frequency) : '—';
                const durationDisplay = duration || '—';

                const row = tbody.querySelector(`[data-item-id="${editingItemId}"]`);
                if (row) {
                    row.innerHTML = `
                        <td style="padding: 0.4rem 0.6rem;">${serviceName}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${qty}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${unitPrice}</td>
                        @if($account->allow_multi_taxation)
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${taxRate}%</td>
                        @endif
                        @if($account->have_users)
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${users}</td>
                        @endif
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${durationDisplay}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${startDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${endDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${deliveryDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${Math.round(lineTotal)}</strong></td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; white-space: nowrap;">
                            <button type="button" class="edit-item icon-action-btn edit" data-id="${editingItemId}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;"><i class="fas fa-edit"></i></button>
                            <button type="button" class="remove-item icon-action-btn delete" data-id="${editingItemId}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                }
                editingItemId = null;
                document.getElementById('addItemBtn').textContent = 'Add';
                updateSummary();
                resetItemInputs();
            }
        } else {
            // Add new item - save to database via AJAX
            const btn = document.getElementById('addItemBtn');
            btn.disabled = true;
            btn.textContent = 'Saving...';

            fetch(`{{ url('/orders') }}/{{ $order->orderid }}/add-item`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    itemid: serviceId,
                    quantity: qty,
                    unit_price: unitPrice,
                    frequency: frequency,
                    duration: duration,
                    no_of_users: users,
                    start_date: startDate,
                    end_date: endDate,
                    delivery_date: deliveryDate,
                    line_total: lineTotal,
                    discount_percent: discountPercent,
                    discount_amount: discountAmount,
                    tax_rate: taxRate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    itemCounter++;
                    const item = {
                        id: itemCounter,
                        order_item_id: data.order_item_id,
                        itemid: serviceId,
                        item_name: serviceName,
                        quantity: qty,
                        unit_price: unitPrice,
                        frequency: frequency,
                        duration: duration,
                        no_of_users: users,
                        start_date: startDate,
                        end_date: endDate,
                        delivery_date: deliveryDate,
                        line_total: lineTotal,
                        discount_percent: discountPercent,
                        discount_amount: discountAmount,
                        tax_rate: taxRate,
                        tax_amount: taxAmount
                    };
                    items.push(item);

                    const freqLabels = {'one-time':'One-Time','daily':'Daily','weekly':'Weekly','bi-weekly':'Bi-Weekly','monthly':'Monthly','quarterly':'Quarterly','semi-annually':'Semi-Annually','yearly':'Yearly'};
                    const freqText = frequency ? (freqLabels[frequency] || frequency) : '—';
                    const durationDisplay = duration || '—';

                    const row = document.createElement('tr');
                    row.dataset.itemId = itemCounter;
                    row.innerHTML = `
                        <td style="padding: 0.4rem 0.6rem;">${serviceName}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${qty}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${unitPrice}</td>
                        @if($account->allow_multi_taxation)
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${taxRate}%</td>
                        @endif
                        @if($account->have_users)
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${users}</td>
                        @endif
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${discountPercent > 0 ? discountPercent + '%' : '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${durationDisplay}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${startDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${endDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${deliveryDate || '—'}</td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${lineTotal.toFixed(2)}</strong></td>
                        <td style="padding: 0.4rem 0.5rem; text-align: right; white-space: nowrap;">
                            <button type="button" class="edit-item icon-action-btn edit" data-id="${itemCounter}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;"><i class="fas fa-edit"></i></button>
                            <button type="button" class="remove-item icon-action-btn delete" data-id="${itemCounter}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
                        </td>
                    `;
                    tbody.appendChild(row);
                    updateSummary();
                    resetItemInputs();
                    
                    btn.disabled = false;
                    btn.textContent = 'Add';
                    document.getElementById('itemsTable').style.display = 'table';
                } else {
                    throw new Error(data.message || 'Failed to add item');
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = 'Add';
                alert('Error: ' + error.message);
            });
        }
    });

    // Edit item
    tbody.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-item');
        if (editBtn) {
            const itemId = parseInt(editBtn.dataset.id);
            const item = items.find(i => i.id === itemId);
            if (item) {
                // Load item data into form
                document.getElementById('item_itemid').value = item.itemid;
                document.getElementById('item_quantity').value = item.quantity;
                document.getElementById('item_unit_price').value = item.unit_price;
                document.getElementById('item_frequency').value = item.frequency || '';
                document.getElementById('item_duration').value = item.duration || '';
                document.getElementById('item_users').value = item.no_of_users || 1;
                document.getElementById('item_discount').value = item.discount_percent || 0;
                document.getElementById('item_start_date').value = item.start_date || '';
                document.getElementById('item_end_date').value = item.end_date || '';
                document.getElementById('item_delivery_date').value = item.delivery_date || '';
                
                // Hide/show start and end date based on frequency
                const frequency = item.frequency || '';
                const startDateField = document.getElementById('item_start_date').closest('div');
                const endDateField = document.getElementById('item_end_date').closest('div');
                
                if (frequency === 'one-time' || frequency === '') {
                    if (startDateField) startDateField.style.display = 'none';
                    if (endDateField) endDateField.style.display = 'none';
                } else {
                    if (startDateField) startDateField.style.display = 'block';
                    if (endDateField) endDateField.style.display = 'block';
                }
                
                // Change button text to indicate update
                document.getElementById('addItemBtn').textContent = 'Update';
                editingItemId = itemId;
                
                // Scroll to form
                document.querySelector('.add-item-row').scrollIntoView({ behavior: 'smooth' });
            }
            return;
        }

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
        const discountTotal = items.reduce((sum, item) => sum + (item.discount_amount || 0), 0);
        const taxTotal = items.reduce((sum, item) => sum + (item.tax_amount || 0), 0);
        const grandTotal = subtotal - discountTotal + taxTotal;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('discountTotal').textContent = discountTotal.toFixed(2);
        document.getElementById('taxTotal').textContent = taxTotal.toFixed(2);
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);

        document.getElementById('formSubtotal').value = subtotal;
        document.getElementById('formDiscountTotal').value = discountTotal;
        document.getElementById('formTaxTotal').value = taxTotal;
        document.getElementById('formGrandTotal').value = grandTotal;
        document.getElementById('formItemsData').value = JSON.stringify(items.map(item => ({
            itemid: item.itemid,
            quantity: item.quantity,
            unit_price: item.unit_price,
            frequency: item.frequency,
            duration: item.duration,
            no_of_users: item.no_of_users,
            start_date: item.start_date || null,
            end_date: item.end_date || null,
            delivery_date: item.delivery_date || null,
            line_total: item.line_total,
            discount_percent: item.discount_percent || 0,
            discount_amount: item.discount_amount || 0,
            tax_rate: item.tax_rate || 0
        })));
    }

    function resetItemInputs() {
        document.getElementById('item_itemid').value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_unit_price').value = '';
        document.getElementById('item_frequency').value = '';
        document.getElementById('item_duration').value = '';
        document.getElementById('item_users').value = 1;
        document.getElementById('item_discount').value = 0;
        document.getElementById('item_start_date').value = '';
        document.getElementById('item_end_date').value = '';
        
        // Reset delivery date to order's delivery date
        const orderDeliveryDate = document.getElementById('delivery_date').value || '';
        document.getElementById('item_delivery_date').value = orderDeliveryDate;
    }

    // Toast notification function
    function showToast(type, message) {
        const container = document.getElementById('toast-container') || document.body;
        const toast = document.createElement('div');
        toast.style.cssText = `position: fixed; top: 20px; right: 20px; background: ${type === 'success' ? '#10b981' : '#ef4444'}; color: white; padding: 0.75rem 1.25rem; border-radius: 8px; font-size: 0.9rem; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideIn 0.3s ease;`;
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'times-circle'}" style="margin-right: 0.5rem;"></i>${message}`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
</script>

<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>

<div id="toast-container"></div>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalOrderEdit" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-order-edit">
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

<script>
(function() {
    const taxModalEl = document.getElementById('addTaxModalOrderEdit');
    const openTaxModalLink = document.getElementById('open-tax-modal-order-edit');
    if (taxModalEl && openTaxModalLink) {
        const taxModal = new bootstrap.Modal(taxModalEl);
        openTaxModalLink.addEventListener('click', function(e) {
            e.preventDefault();
            taxModal.show();
        });
    }
})();
</script>
@endif

@endsection
