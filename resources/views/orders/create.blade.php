@extends('layouts.app')

@section('content')
@php
    $isEditMode = (bool) ($isEditMode ?? false);
    $editingOrder = $order ?? null;
    $clientFallback = $isEditMode ? ($editingOrder->clientid ?? '') : ($preSelectedClientId ?? request('c') ?? '');
    $displayOrderNumber = old('order_number', $isEditMode ? ($editingOrder->order_number ?? $nextOrderNumber) : $nextOrderNumber);
    $defaultOrderDate = $isEditMode
        ? ($editingOrder?->order_date?->format('Y-m-d') ?? date('Y-m-d'))
        : date('Y-m-d');
    $defaultDeliveryDate = $isEditMode ? ($editingOrder?->delivery_date?->format('Y-m-d') ?? '') : '';
    $defaultSalesPersonId = $isEditMode ? ($editingOrder->sales_person_id ?? null) : null;
    $defaultNotes = $isEditMode ? ($editingOrder->notes ?? '') : '';
    $defaultPoNumber = $isEditMode ? ($editingOrder->po_number ?? '') : '';
    $defaultPoDate = $isEditMode ? ($editingOrder?->po_date?->format('Y-m-d') ?? '') : '';
    $defaultAgreementRef = $isEditMode ? ($editingOrder->agreement_ref ?? '') : '';
    $defaultAgreementDate = $isEditMode ? ($editingOrder?->agreement_date?->format('Y-m-d') ?? '') : '';
    $selectedClientId = old('clientid', $clientFallback);
    $selectedClient = collect($clients ?? [])->firstWhere('clientid', $selectedClientId);
    $selectedClientName = $selectedClient->business_name ?? $selectedClient->contact_name ?? 'Select client';
    $selectedClientEmail = $selectedClient->email ?? 'No client email';
    $accountStateForGst = strtoupper(trim((string) ($account->state ?? '')));
    $clientDirectoryPayload = collect($clients ?? [])->mapWithKeys(function ($client) {
        return [
            (string) ($client->clientid ?? '') => [
                'name' => (string) ($client->business_name ?? $client->contact_name ?? 'Select client'),
                'email' => (string) ($client->email ?? ''),
                'state' => strtoupper(trim((string) ($client->state ?? ''))),
            ],
        ];
    })->all();
@endphp

<section class="section-bar order-create-header" style="padding: 0.5rem 1rem;">
    <a href="{{ route('orders.index', ['c' => $preSelectedClientId ?? $clientFallback]) }}" class="text-link" style="font-size: 0.85rem;">&larr; Back to orders</a>
</section>


<section class="order-create-shell" style="padding: 0;">
    <form method="POST" action="{{ route('orders.store') }}" class="client-form" id="orderForm" enctype="multipart/form-data">
        @csrf

        <div class="order-top-summary order-lockable">
            <div class="order-top-summary__client">
                <div class="order-top-summary__icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div>
                    <p class="order-top-summary__eyebrow">{{ $isEditMode ? 'Editing Order' : '' }}</p>
                    <p class="order-top-summary__client-name" id="summaryClientName">{{ $selectedClientName }}</p>
                    <p class="order-top-summary__client-email" id="summaryClientEmail">{{ $selectedClientEmail ?: 'No client email' }}</p>
                    <div style="margin-top: 0.5rem; min-width: 240px;">
                        <!-- <label class="order-label">Client</label>
                        <div class="form-control form-control-sm" style="background: #f8fafc;" id="clientDisplayName">
                            {{ $selectedClientName }}
                        </div> -->
                        <input type="hidden" id="clientid" name="clientid" required value="{{ old('clientid', $clientFallback) }}">
                        @error('clientid') <span class="error">{{ $message }}</span> @enderror
                        <input type="hidden" id="order_number" name="order_number" value="{{ $displayOrderNumber }}">
                    </div>
                </div>
            </div>
            <div class="order-top-summary__fields">
                <div>
                    <label class="order-label" for="order_title">Order Title</label>
                    <input type="text" id="order_title" name="order_title" required value="{{ old('order_title', $isEditMode ? ($editingOrder->order_title ?? '') : '') }}" class="form-control form-control-sm" placeholder="Order Title/Details">
                </div>
                <div>
                    <label class="order-label" for="delivery_date">Delivery Date</label>
                    <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', $defaultDeliveryDate) }}" class="form-control form-control-sm" placeholder="Delivery Date">
                </div>
                <div>
                    <label class="order-label" for="sales_person_id">Sales Person</label>
                    <select id="sales_person_id" name="sales_person_id" class="form-control form-control-sm">
                        <option value="">-- Select Sales Person --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('sales_person_id', $defaultSalesPersonId) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="order-label" for="order_date">Order Date</label>
                    <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $defaultOrderDate) }}" required class="form-control form-control-sm" placeholder="Order Date">
                    @error('order_date') <span class="error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        

        <div class="row g-3 order-lockable">
            {{-- PO --}}
            <div class="col-12 col-lg-6">
                <details class="order-accordion" {{ (old('po_number', $defaultPoNumber) || old('po_date', $defaultPoDate)) ? 'open' : '' }}>
                    <summary>
                        <span><i class="fas fa-file-alt" style="margin-right: 0.4rem; color: #94a3b8;"></i>Purchase Order</span>
                    </summary>
                    <div class="order-accordion__content">
                        
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="order-label">PO Number</label>
                                <input type="text" id="po_number" name="po_number"
                                    value="{{ old('po_number', $defaultPoNumber) }}"
                                    class="form-control form-control-sm">
                            </div>

                            <div class="col-6 mb-2">
                                <label class="order-label">PO Date</label>
                                <input type="date" id="po_date" name="po_date"
                                    value="{{ old('po_date', $defaultPoDate) }}"
                                    class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="order-label">PO Upload</label>
                            @if($isEditMode && !empty($editingOrder?->po_file))
                                <div style="font-size: 0.75rem; margin-bottom: 0.25rem;">
                                    <a href="{{ asset('storage/' . $editingOrder->po_file) }}" target="_blank" class="text-link">
                                        <i class="fas fa-file"></i> View Current File
                                    </a>
                                </div>
                            @endif
                            <input type="file" id="po_file" name="po_file"
                                class="form-control form-control-sm"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>

                    </div>
                </details>
            </div>

            {{-- AGREEMENT --}}
            <div class="col-12 col-lg-6">
                <details class="order-accordion" {{ (old('agreement_ref', $defaultAgreementRef) || old('agreement_date', $defaultAgreementDate)) ? 'open' : '' }}>
                    <summary>
                        <span><i class="fas fa-file-signature" style="margin-right: 0.4rem; color: #94a3b8;"></i>Agreement</span>
                    </summary>
                    <div class="order-accordion__content">

                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="order-label">Agreement Ref</label>
                                <input type="text" id="agreement_ref" name="agreement_ref"
                                    value="{{ old('agreement_ref', $defaultAgreementRef) }}"
                                    class="form-control form-control-sm">
                            </div>

                            <div class="col-6 mb-2">
                                <label class="order-label">Agreement Date</label>
                                <input type="date" id="agreement_date" name="agreement_date"
                                    value="{{ old('agreement_date', $defaultAgreementDate) }}"
                                    class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="order-label">Agreement Upload</label>
                            @if($isEditMode && !empty($editingOrder?->agreement_file))
                                <div style="font-size: 0.75rem; margin-bottom: 0.25rem;">
                                    <a href="{{ asset('storage/' . $editingOrder->agreement_file) }}" target="_blank" class="text-link">
                                        <i class="fas fa-file"></i> View Current File
                                    </a>
                                </div>
                            @endif
                            <input type="file" id="agreement_file" name="agreement_file"
                                class="form-control form-control-sm"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>

                    </div>
                </details>
            </div>

        </div>

        <div class="order-save-row">
            <button type="button" id="saveOrderBtn" class="btn btn-sm btn-primary">
                <i class="fas fa-save" style="margin-right: 0.35rem;"></i>{{ $isEditMode ? 'Update Order & Continue' : 'Save Order & Continue' }}
            </button>
        </div>

        <div class="order-items-shell" style="position: relative;">
                {{-- Disabled overlay until order is saved --}}
                <div id="itemsDisabledOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255, 255, 255, 0.9); display: flex; align-items: center; justify-content: center; z-index: 10; border-radius: 8px;">
                    <div style="text-align: center; color: #64748b;">
                        <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 0.5rem; color: #94a3b8;"></i>
                        <p style="margin: 0; font-size: 0.9rem;">{{ $isEditMode ? 'Loading order details...' : 'Save order details first to add items' }}</p>
                    </div>
                </div>

                {{-- Items Section --}}
                <div class="items-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <p style="margin: 0; font-size: 0.85rem; color: #64748b;">
                            <!-- <i class="fas fa-info-circle" style="margin-right: 0.35rem;"></i> -->
                            <!-- Items will be saved when you click "Create Order" -->
                        </p>
                    </div>

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
                                            @endphp
                                            <option value="{{ $service->itemid }}"
                                                    data-selling-price="{{ $sellingPrice }}"
                                                    data-tax-rate="{{ $taxRate }}"
                                                    data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
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
                            <label style="font-size: 0.75rem;">Tax% <a href="#" id="open-tax-modal-order" style="font-size:10px;margin-left:2px;" class="text-link">+</a></label>
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
                        <div id="item_users_wrapper" style="flex: 0.6; min-width: 55px; display: none;">
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
                        <div id="item_duration_wrapper" style="flex: 0.6; min-width: 60px;">
                            <label style="font-size: 0.75rem;">Dur</label>
                            <input type="text" id="item_duration" placeholder="12" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div id="item_start_date_wrapper" style="flex: 0.8; min-width: 90px;">
                            <label style="font-size: 0.75rem;">Start</label>
                            <input type="date" id="item_start_date" style="font-size: 0.85rem; padding: 0.4rem 0.5rem; width: 100%;">
                        </div>
                        <div id="item_end_date_wrapper" style="flex: 0.8; min-width: 90px;">
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

                    <table id="itemsTable" style="width: 100%; border-collapse: collapse; margin-bottom: 1.25rem; display: none; font-size: 0.9rem;">
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
                        </tbody>
                    </table>

                    <div id="orderSummary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; max-width: 350px; margin-left: auto;">
                        <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 600;">Order Summary</h4>

                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span style="color: #64748b;">Subtotal:</span>
                            <strong id="subtotal">0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span style="color: #64748b;">Discount:</span>
                            <strong id="discountTotal" style="color: #dc2626;">0.00</strong>
                        </div>
                        <div id="taxIgstRow" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                            <span id="taxLabel" style="color: #64748b;">Tax (IGST 100%):</span>
                            <strong id="taxTotal">0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700; border-top: 2px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.5rem;">
                            <span>Total:</span>
                            <strong id="grandTotal" style="color: #3b82f6;">0.00</strong>
                        </div>
                    </div>
                </div>
            </div>

        <div class="form-actions text-end" 
            style="border-top: 1px solid #e2e8f0; display: none; justify-content: flex-end;" 
            id="finalActions">

            <a href="{{ route('orders.index', ['c' => $preSelectedClientId ?? $clientFallback]) }}" 
            class="btn btn-primary btn-sm">
                Confirm & Exit
            </a>

            <input type="hidden" name="orderid" id="savedOrderId">
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientDirectory = @json($clientDirectoryPayload);
    const accountStateForGst = @json($accountStateForGst ?? '');

    const isEditMode = @json($isEditMode);
    const serverExistingOrderId = @json($isEditMode ? (string) ($editingOrder->orderid ?? '') : '');
    const editUrlTemplate = @json(route('orders.edit', ['order' => '__ORDER__']));
    const initialOrderPayload = @json($initialOrderPayload ?? null);
    const initialItemsPayload = @json($initialItemsPayload ?? []);
    const saveButtonIdleLabel = isEditMode ? 'Update Order & Continue' : 'Save Order & Continue';
    const saveButtonDoneLabel = isEditMode ? 'Order Updated' : 'Order Saved';
    const saveSuccessMessage = isEditMode ? 'Order updated successfully! You can continue managing items.' : 'Order saved successfully! You can now add items.';

    let itemCounter = 0;
    const items = [];
    const tbody = document.getElementById('itemsTbody');
    let editingItemId = null;
    let savedOrderId = null;

    // For edit mode, prefill from server-rendered payload first.
    if (isEditMode && initialOrderPayload && initialOrderPayload.orderid) {
        savedOrderId = String(initialOrderPayload.orderid);
        document.getElementById('savedOrderId').value = savedOrderId;

        const fields = ['clientid', 'order_number', 'order_title', 'order_date', 'delivery_date', 'sales_person_id', 'notes', 'po_number', 'po_date', 'agreement_ref', 'agreement_date'];
        fields.forEach(field => {
            const el = document.getElementById(field);
            if (el && initialOrderPayload[field] !== undefined && initialOrderPayload[field] !== null) {
                el.value = initialOrderPayload[field];
            }
        });
        syncSelectedClientDisplay();

        document.getElementById('itemsDisabledOverlay').style.display = 'none';
        document.getElementById('finalActions').style.display = 'block';

        const btn = document.getElementById('saveOrderBtn');
        btn.innerHTML = `<i class="fas fa-check" style="margin-right: 0.35rem;"></i>${saveButtonDoneLabel}`;
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        btn.disabled = false;

        if (Array.isArray(initialItemsPayload) && initialItemsPayload.length > 0) {
            loadOrderItemsFromData(initialItemsPayload);
        }
    }

    // Check if there's an existing order ID in server-provided context or URL
    const urlParams = new URLSearchParams(window.location.search);
    const existingOrderId = serverExistingOrderId || urlParams.get('order');
    if (existingOrderId && !(isEditMode && initialOrderPayload && initialOrderPayload.orderid)) {
        savedOrderId = existingOrderId;
        document.getElementById('savedOrderId').value = savedOrderId;

        // Load order details into the form
        loadOrderDetails(existingOrderId);
    }

    // Save Order button handler
    document.getElementById('saveOrderBtn').addEventListener('click', function() {
        const form = document.getElementById('orderForm');

        // Trigger HTML5 validation manually since we use type="button" + fetch
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        // Disable button and show loading
        const btn = document.getElementById('saveOrderBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.35rem;"></i>Saving...';

        fetch('{{ route("orders.save-ajax") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                savedOrderId = data.orderid;
                document.getElementById('savedOrderId').value = savedOrderId;

                // Keep refresh-safe URL after first save.
                // In create mode, switch to edit URL for this order.
                // In edit mode, stay on the existing route.
                if (!isEditMode) {
                    const editUrl = editUrlTemplate.replace('__ORDER__', encodeURIComponent(savedOrderId));
                    window.history.replaceState({}, '', editUrl);
                } else {
                    const url = new URL(window.location);
                    url.searchParams.set('order', savedOrderId);
                    window.history.replaceState({}, '', url);
                }

                // Enable items section
                document.getElementById('itemsDisabledOverlay').style.display = 'none';
                document.getElementById('finalActions').style.display = 'block';

                // Update button
                btn.innerHTML = `<i class="fas fa-check" style="margin-right: 0.35rem;"></i>${saveButtonDoneLabel}`;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                btn.disabled = false;

                showToast('success', saveSuccessMessage);
            } else {
                throw new Error(data.message || 'Failed to save order');
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-save" style="margin-right: 0.35rem;"></i>${saveButtonIdleLabel}`;
            alert('Error: ' + error.message);
        });
    });

    // Helper function to set date value and notify flatpickr
    function setDateValue(fieldId, value) {
        const el = document.getElementById(fieldId);
        if (!el) return;
        el.value = value;
        if (el._flatpickr) {
            el._flatpickr.setDate(value, false);
        }
    }

    // Helper function to calculate end date based on frequency and duration
    function calculateEndDate(startDate, frequency, duration) {
        if (!startDate || !frequency || !duration) return '';

        const parts = startDate.split('-');
        const start = new Date(parts[0], parts[1] - 1, parts[2]); // Parse as local
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

        const y = endDate.getFullYear();
        const m = String(endDate.getMonth() + 1).padStart(2, '0');
        const d = String(endDate.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    // Helper function to calculate line total: qty x price x users
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

    // Normalize amounts so line_total always represents amount before tax.
    function round2(value) {
        return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
    }

    function calculateTaxBreakdown(lineTotalInput, discountPercent, taxRate) {
        const rate = Number(taxRate) || 0;
        const lineTotal = Number(lineTotalInput) || 0;

        const discountAmount = (lineTotal * (Number(discountPercent) || 0)) / 100;
        const taxableAmount = Math.max(0, lineTotal - discountAmount);
        const taxAmount = (taxableAmount * rate) / 100;

        return {
            lineTotal: round2(lineTotal),
            discountAmount: round2(discountAmount),
            taxAmount: round2(taxAmount)
        };
    }

    function renderLineTotalHtml(lineTotal) {
        const base = Number(lineTotal || 0).toFixed(2);
        return `<strong>${base}</strong>`;
    }

    // When order delivery date changes, update the input field for the next item
    document.getElementById('delivery_date').addEventListener('change', function() {
        const orderDeliveryDate = this.value || '';
        const itemDeliveryInput = document.getElementById('item_delivery_date');
        if (itemDeliveryInput) {
            itemDeliveryInput.value = orderDeliveryDate;
            if (itemDeliveryInput._flatpickr) {
                itemDeliveryInput._flatpickr.setDate(orderDeliveryDate, false);
            }
        }
    });
    
    // Initialize delivery date on page load
    const initialOrderDeliveryDate = document.getElementById('delivery_date').value || '';
    if (initialOrderDeliveryDate) {
        setDateValue('item_delivery_date', initialOrderDeliveryDate);
    }

    function syncSelectedClientDisplay() {
        const clientSelect = document.getElementById('clientid');
        if (!clientSelect) return;

        const fallbackName = 'Select client';
        const fallbackEmail = 'No client email';
        let name = fallbackName;
        let email = fallbackEmail;

        if (clientSelect.tagName === 'SELECT') {
            const selectedOption = clientSelect.options[clientSelect.selectedIndex];
            const hasSelectedClient = Boolean(selectedOption?.value);
            name = hasSelectedClient
                ? (selectedOption?.dataset?.name || selectedOption?.textContent?.trim() || fallbackName)
                : fallbackName;
            email = hasSelectedClient
                ? (selectedOption?.dataset?.email || fallbackEmail)
                : fallbackEmail;
        } else {
            const selectedClientId = (clientSelect.value || '').trim();
            const selectedClient = selectedClientId ? clientDirectory[selectedClientId] : null;
            name = selectedClient?.name || fallbackName;
            email = selectedClient?.email || fallbackEmail;
        }

        const summaryName = document.getElementById('summaryClientName');
        const summaryEmail = document.getElementById('summaryClientEmail');
        const cardName = document.getElementById('clientCardName');
        const cardEmail = document.getElementById('clientCardEmail');
        const displayName = document.getElementById('clientDisplayName');

        if (summaryName) summaryName.textContent = name;
        if (summaryEmail) summaryEmail.textContent = email;
        if (cardName) cardName.textContent = name;
        if (cardEmail) cardEmail.textContent = email;
        if (displayName) displayName.textContent = name;
        updateSummary();
    }

    function normalizeState(value) {
        return String(value || '')
            .trim()
            .toUpperCase()
            .replace(/[^A-Z0-9]/g, '');
    }

    function isSameStateGstForSelectedClient() {
        const clientSelect = document.getElementById('clientid');
        if (!clientSelect) return false;

        const selectedClientId = String(clientSelect.value || '').trim();
        const clientState = normalizeState(clientDirectory[selectedClientId]?.state || '');
        const accountState = normalizeState(accountStateForGst);

        return clientState !== '' && accountState !== '' && clientState === accountState;
    }

    function updateTaxBreakupDisplay(taxTotal) {
        const igstRow = document.getElementById('taxIgstRow');
        const taxTotalEl = document.getElementById('taxTotal');
        const taxLabelEl = document.getElementById('taxLabel');

        if (!igstRow || !taxTotalEl || !taxLabelEl) {
            return;
        }

        if (isSameStateGstForSelectedClient()) {
            taxLabelEl.textContent = 'Tax (CGST 50% + SGST 50%):';
            taxTotalEl.textContent = taxTotal.toFixed(2);
            igstRow.style.display = 'flex';
        } else {
            taxLabelEl.textContent = 'Tax (IGST 100%):';
            taxTotalEl.textContent = taxTotal.toFixed(2);
            igstRow.style.display = 'flex';
        }
    }

    const clientSelect = document.getElementById('clientid');
    if (clientSelect) {
        if (clientSelect.tagName === 'SELECT') {
            clientSelect.addEventListener('change', syncSelectedClientDisplay);
        } else {
            clientSelect.addEventListener('input', syncSelectedClientDisplay);
        }
        syncSelectedClientDisplay();
    }

    // Item select change
    document.getElementById('item_itemid').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (option.value) {
            document.getElementById('item_unit_price').value = option.dataset.sellingPrice || '0';
        } else {
            document.getElementById('item_unit_price').value = '';
        }
        @if($account->have_users)
        toggleUsersField();
        @endif
    });

    @if($account->have_users)
    function isSelectedItemUserWise() {
        const itemSelect = document.getElementById('item_itemid');
        const option = itemSelect?.options[itemSelect.selectedIndex];
        return option?.dataset?.userWise === '1';
    }

    function enforceUsersInputConstraint() {
        const usersInput = document.getElementById('item_users');
        if (!usersInput) return 1;

        let raw = String(usersInput.value ?? '').replace(/[^\d]/g, '');
        if (!raw || Number(raw) < 1) {
            raw = '1';
        }

        usersInput.value = String(parseInt(raw, 10));
        return parseInt(usersInput.value, 10) || 1;
    }

    function toggleUsersField() {
        const wrapper = document.getElementById('item_users_wrapper');
        const usersInput = document.getElementById('item_users');
        if (!wrapper || !usersInput) return;

        const show = isSelectedItemUserWise();
        wrapper.style.display = show ? 'block' : 'none';
        if (show) {
            enforceUsersInputConstraint();
        } else {
            usersInput.value = 1;
        }
    }

    const itemUsersInput = document.getElementById('item_users');
    if (itemUsersInput) {
        itemUsersInput.addEventListener('input', enforceUsersInputConstraint);
        itemUsersInput.addEventListener('change', enforceUsersInputConstraint);
        itemUsersInput.addEventListener('blur', enforceUsersInputConstraint);
    }
    @endif

    function toggleRecurringFields(frequency) {
        const isRecurring = frequency && frequency !== 'one-time';
        const durationField = document.getElementById('item_duration_wrapper');
        const startDateField = document.getElementById('item_start_date_wrapper');
        const endDateField = document.getElementById('item_end_date_wrapper');

        if (durationField) durationField.style.display = isRecurring ? 'block' : 'none';
        if (startDateField) startDateField.style.display = isRecurring ? 'block' : 'none';
        if (endDateField) endDateField.style.display = isRecurring ? 'block' : 'none';

        if (!isRecurring) {
            document.getElementById('item_duration').value = '';
            document.getElementById('item_start_date').value = '';
            document.getElementById('item_end_date').value = '';
        }
    }

    document.getElementById('item_frequency').addEventListener('change', function() {
        toggleRecurringFields(this.value);
    });

    toggleRecurringFields(document.getElementById('item_frequency').value || '');
    @if($account->have_users)
    toggleUsersField();
    @endif

    // Auto-calculate end date when start date, frequency, or duration changes
    ['item_start_date', 'item_frequency', 'item_duration'].forEach(fieldId => {
        document.getElementById(fieldId).addEventListener('change', function() {
            const startDate = document.getElementById('item_start_date').value;
            const frequency = document.getElementById('item_frequency').value;
            const duration = document.getElementById('item_duration').value;
            
            if (startDate && frequency && duration) {
                const endDate = calculateEndDate(startDate, frequency, duration);
                setDateValue('item_end_date', endDate);
            }
        });
    });

    // Helper function to add a new item
    function addNewItem(serviceId, serviceName, qty, unitPrice, frequency, duration, users, startDate, endDate, deliveryDate, lineTotal, discountPercent, discountAmount, taxRate, taxAmount) {
        // Disable button during save
        const btn = document.getElementById('addItemBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        // Save item via AJAX
        fetch(`{{ url('/orders') }}/${savedOrderId}/add-item`, {
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
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    throw new Error('Server returned HTML instead of JSON. Please refresh the page.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Add to local array for display
                itemCounter++;
                const item = {
                    id: itemCounter,
                    order_item_id: data.order_item_id || null,
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
            row.dataset.orderItemId = data.order_item_id || '';
            row.innerHTML = `
                <td style="padding: 0.4rem 0.6rem;">${item.item_name}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${item.quantity}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${item.unit_price}</td>
                @if($account->allow_multi_taxation)
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.tax_rate}%</td>
                @endif
                @if($account->have_users)
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.no_of_users ?? '—'}</td>
                @endif
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.discount_percent > 0 ? item.discount_percent + '%' : '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${durationDisplay}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.start_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.end_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.delivery_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${Math.round(item.line_total)}</strong></td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; white-space: nowrap;">
                    <button type="button" class="edit-item icon-action-btn edit" data-id="${itemCounter}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;"><i class="fas fa-edit"></i></button>
                    <button type="button" class="remove-item icon-action-btn delete" data-id="${itemCounter}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(row);

                document.getElementById('itemsTable').style.display = 'table';
                updateSummary();
                resetItemInputs();
                showToast('success', 'Item added successfully!');
            } else {
                throw new Error(data.message || 'Failed to add item');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Add';
        });
    }

    // Add/Update Item click handler
    document.getElementById('addItemBtn').addEventListener('click', function() {
        if (!savedOrderId) {
            alert('Please save the order details first!');
            return;
        }

        const serviceId = document.getElementById('item_itemid').value;
        if (!serviceId) return alert('Select an item');

        const serviceOption = document.getElementById('item_itemid').options[document.getElementById('item_itemid').selectedIndex];
        const serviceName = serviceOption.text.split(' (')[0];
        
        const qty = parseFloat(document.getElementById('item_quantity').value) || 1;
        const unitPrice = parseFloat(document.getElementById('item_unit_price').value) || 0;
        const frequency = document.getElementById('item_frequency').value || '';
        const duration = document.getElementById('item_duration').value || '';
        @if($account->have_users)
        const isUserWiseItem = isSelectedItemUserWise();
        const users = isUserWiseItem ? Math.max(1, enforceUsersInputConstraint()) : 1;
        const usersForStorage = isUserWiseItem ? users : null;
        @else
        const users = parseInt(document.getElementById('item_users').value) || 1;
        const usersForStorage = users;
        @endif
        const startDate = document.getElementById('item_start_date').value || '';
        const endDate = document.getElementById('item_end_date').value || '';
        const deliveryDate = document.getElementById('item_delivery_date').value || '';

        const discountPercent = parseFloat(document.getElementById('item_discount').value) || 0;
        const lineInputTotal = calculateLineTotal(qty, unitPrice, users, frequency, duration);

        @if($account->allow_multi_taxation)
        const taxRate = parseFloat(document.getElementById('item_tax_rate').value) || 0;
        @else
        const taxRate = {{ $account->fixed_tax_rate ?? 0 }};
        @endif
        
        const { lineTotal, discountAmount, taxAmount } = calculateTaxBreakdown(lineInputTotal, discountPercent, taxRate);

        if (editingItemId) {
            const item = items.find(i => i.id === editingItemId);
            const orderItemId = item?.order_item_id;

            if (orderItemId) {
                fetch(`{{ url('/orders') }}/${savedOrderId}/remove-item/${orderItemId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Failed to update item');
                    }

                    const index = items.findIndex(i => i.id === editingItemId);
                    if (index > -1) items.splice(index, 1);
                    const row = tbody.querySelector(`[data-item-id="${editingItemId}"]`);
                    if (row) row.remove();

                    editingItemId = null;
                    document.getElementById('addItemBtn').textContent = 'Add';
                    addNewItem(serviceId, serviceName, qty, unitPrice, frequency, duration, usersForStorage, startDate, endDate, deliveryDate, lineTotal, discountPercent, discountAmount, taxRate, taxAmount);
                })
                .catch(error => {
                    alert('Error updating item: ' + error.message);
                });
                return;
            }
        }

        addNewItem(serviceId, serviceName, qty, unitPrice, frequency, duration, usersForStorage, startDate, endDate, deliveryDate, lineTotal, discountPercent, discountAmount, taxRate, taxAmount);
    });

    // Edit and Remove items
    tbody.addEventListener('click', function(e) {
        // Edit item
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
                @if($account->have_users)
                toggleUsersField();
                @endif
                document.getElementById('item_discount').value = item.discount_percent || 0;
                document.getElementById('item_tax_rate').value = item.tax_rate || 0;
                
                setDateValue('item_start_date', item.start_date || '');
                setDateValue('item_end_date', item.end_date || '');
                // Use item's delivery date, or fall back to order's delivery date if item's is empty
                const orderDeliveryDate = document.getElementById('delivery_date')?.value || '';
                setDateValue('item_delivery_date', item.delivery_date || orderDeliveryDate);
                
                toggleRecurringFields(item.frequency || '');

                // Change button text to indicate update
                document.getElementById('addItemBtn').textContent = 'Update';
                editingItemId = itemId;

                // Scroll to form
                document.querySelector('.add-item-row').scrollIntoView({ behavior: 'smooth' });
            }
            return;
        }

        // Remove item
        const btn = e.target.closest('.remove-item');
        if (btn) {
            if (!confirm('Are you sure you want to remove this item?')) return;

            const itemId = parseInt(btn.dataset.id);
            const item = items.find(i => i.id === itemId);
            const orderItemId = item?.order_item_id;

            // Delete from DB via AJAX
            if (orderItemId) {
                fetch(`{{ url('/orders') }}/${savedOrderId}/remove-item/${orderItemId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const index = items.findIndex(i => i.id === itemId);
                        if (index > -1) items.splice(index, 1);
                        btn.closest('tr').remove();
                        updateSummary();
                        if (items.length === 0) {
                            document.getElementById('itemsTable').style.display = 'none';
                        }
                        showToast('success', 'Item removed successfully!');
                    } else {
                        throw new Error(data.message || 'Failed to remove item');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
    });

    function updateSummary() {
        const subtotal = round2(items.reduce((sum, item) => sum + Number(item.line_total || 0), 0));
        const discountTotal = round2(items.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0));
        const taxTotal = round2(items.reduce((sum, item) => sum + Number(item.tax_amount || 0), 0));
        const grandTotal = round2(subtotal - discountTotal + taxTotal);

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('discountTotal').textContent = discountTotal.toFixed(2);
        updateTaxBreakupDisplay(taxTotal);
        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);

        // Note: In create form, we don't need hidden fields since items are saved via AJAX
        // The hidden fields are only used in edit form for batch submission
    }

    function resetItemInputs() {
        const fields = {
            'item_itemid': '',
            'item_quantity': 1,
            'item_unit_price': '',
            'item_discount': 0,
            'item_frequency': '',
            'item_duration': '',
            'item_users': 1
        };
        
        for (const [id, value] of Object.entries(fields)) {
            const el = document.getElementById(id);
            if (el) el.value = value;
        }
        @if($account->have_users)
        toggleUsersField();
        @endif

        setDateValue('item_start_date', '');
        setDateValue('item_end_date', '');

        toggleRecurringFields('');

        const itemTaxRate = document.getElementById('item_tax_rate');
        if (itemTaxRate) {
            itemTaxRate.value = {{ $account->allow_multi_taxation ? '0' : ($account->fixed_tax_rate ?? 0) }};
        }

        // Reset delivery date to order's delivery date
        const orderDeliveryDate = document.getElementById('delivery_date')?.value || '';
        setDateValue('item_delivery_date', orderDeliveryDate);
    }

    // Load order details from database
    function loadOrderDetails(orderId) {
        fetch(`{{ url('/orders') }}/${orderId}/json`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            // Populate form with order details
            if (data.order) {
                const fields = ['clientid', 'order_number', 'order_title', 'order_date', 'delivery_date', 'sales_person_id', 'notes', 'po_number', 'po_date', 'agreement_ref', 'agreement_date'];
                fields.forEach(field => {
                    const el = document.getElementById(field);
                    if (el && data.order[field] !== undefined && data.order[field] !== null) {
                        el.value = data.order[field];
                    }
                });

                if (!isEditMode) {
                    // For create mode, lock details after initial save.
                    document.querySelectorAll('#orderForm .order-lockable input, #orderForm .order-lockable select, #orderForm .order-lockable textarea').forEach(el => {
                        el.disabled = true;
                    });
                }

                syncSelectedClientDisplay();

                // Enable items section
                document.getElementById('itemsDisabledOverlay').style.display = 'none';
                document.getElementById('finalActions').style.display = 'block';

                const btn = document.getElementById('saveOrderBtn');
                btn.innerHTML = `<i class="fas fa-check" style="margin-right: 0.35rem;"></i>${saveButtonDoneLabel}`;
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
                btn.disabled = false;

                // Load items
                if (data.items && data.items.length > 0) {
                    loadOrderItemsFromData(data.items);
                }
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
        });
    }

    // Load existing order items from database
    function loadOrderItems(orderId) {
        fetch(`{{ url('/orders') }}/${orderId}/json`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            if (data.items && data.items.length > 0) {
                loadOrderItemsFromData(data.items);
            }
        })
        .catch(error => {
            console.error('Error loading order items:', error);
        });
    }

    // Helper function to load items from data array
    function loadOrderItemsFromData(itemsArray) {
        itemsArray.forEach((itemData) => {
            itemCounter++;
            const item = {
                id: itemCounter,
                order_item_id: itemData.orderitemid,
                itemid: itemData.itemid,
                item_name: itemData.item_name,
                quantity: itemData.quantity,
                unit_price: itemData.unit_price,
                frequency: itemData.frequency || '',
                duration: itemData.duration || '',
                no_of_users: itemData.no_of_users ?? null,
                start_date: itemData.start_date || '',
                end_date: itemData.end_date || '',
                delivery_date: itemData.delivery_date || '',
                line_total: itemData.line_total,
                discount_percent: itemData.discount_percent || 0,
                discount_amount: itemData.discount_amount || 0,
                tax_rate: itemData.tax_rate || 0,
                tax_amount: (Math.max(0, (itemData.line_total || 0) - (itemData.discount_amount || 0)) * (itemData.tax_rate || 0)) / 100
            };
            items.push(item);

            const freqLabels = {'one-time':'One-Time','daily':'Daily','weekly':'Weekly','bi-weekly':'Bi-Weekly','monthly':'Monthly','quarterly':'Quarterly','semi-annually':'Semi-Annually','yearly':'Yearly'};
            const freqText = itemData.frequency ? (freqLabels[itemData.frequency] || itemData.frequency) : '—';
            const durationDisplay = itemData.duration || '—';

            const row = document.createElement('tr');
            row.dataset.itemId = itemCounter;
            row.dataset.orderItemId = itemData.orderitemid;
            row.innerHTML = `
                <td style="padding: 0.4rem 0.6rem;">${item.item_name}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${item.quantity}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.82rem;">${item.unit_price}</td>
                @if($account->allow_multi_taxation)
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.tax_rate}%</td>
                @endif
                @if($account->have_users)
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.no_of_users ?? '—'}</td>
                @endif
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.discount_percent > 0 ? item.discount_percent + '%' : '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${freqText}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${durationDisplay}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.start_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.end_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; font-size: 0.78rem;">${item.delivery_date || '—'}</td>
                <td style="padding: 0.4rem 0.5rem; text-align: right;" class="item-line-total"><strong>${Math.round(item.line_total)}</strong></td>
                <td style="padding: 0.4rem 0.5rem; text-align: right; white-space: nowrap;">
                    <button type="button" class="edit-item icon-action-btn edit" data-id="${itemCounter}" title="Edit" style="padding: 0.15rem 0.3rem; font-size: 0.7rem; margin-right: 0.2rem;"><i class="fas fa-edit"></i></button>
                    <button type="button" class="remove-item icon-action-btn delete" data-id="${itemCounter}" title="Remove" style="padding: 0.15rem 0.3rem; font-size: 0.7rem;"><i class="fas fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('itemsTable').style.display = 'table';
        updateSummary();
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
.order-create-shell {
    background: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
}
.order-create-header {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.order-top-summary {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    flex-wrap: wrap;
}
.order-top-summary__client {
    min-width: 220px;
    display: flex;
    gap: 0.75rem;
    align-items: center;
}
.order-top-summary__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #e2e8f0;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
}
.order-top-summary__eyebrow {
    margin: 0;
    font-size: 0.72rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 700;
}
.order-top-summary__client-name {
    margin: 0.12rem 0 0;
    color: #0f172a;
    font-size: 0.96rem;
    font-weight: 700;
}
.order-top-summary__client-email {
    margin: 0.1rem 0 0;
    color: #64748b;
    font-size: 0.8rem;
}
.order-top-summary__fields {
    flex: 1 1 540px;
    display: grid;
    grid-template-columns: repeat(4, minmax(150px, 1fr));
    gap: 0.65rem;
}
.order-save-row {
    margin-top: 0.9rem;
    display: flex;
    justify-content: flex-end;
}
.order-info-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    padding: 1rem;
    height: 100%;
}
.order-info-card__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.8rem;
}
.order-number-pill {
    background: #f1f5f9;
    color: #0f172a;
    border-radius: 999px;
    padding: 0.22rem 0.7rem;
    font-size: 0.78rem;
    font-weight: 600;
}
.order-label {
    font-size: 0.72rem;
    color: #64748b;
    display: block;
    margin-bottom: 0.2rem;
    font-weight: 600;
}
.order-client-meta,
.order-details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
}
.order-meta-label {
    margin: 0;
    color: #64748b;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 700;
}
.order-meta-value {
    margin: 0.15rem 0 0;
    color: #0f172a;
    font-size: 0.9rem;
    font-weight: 600;
}
.order-accordion-wrap {
    margin-top: 1rem;
    display: grid;
    gap: 0.75rem;
}
.order-accordion {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
}
.order-accordion summary {
    list-style: none;
    cursor: pointer;
    padding: 0.75rem 0.95rem;
    font-size: 0.88rem;
    color: #334155;
    font-weight: 700;
}
.order-accordion__content {
    border-top: 1px solid #e2e8f0;
    padding: 0.75rem 0.95rem 0.2rem;
}
.order-items-shell {
    margin-top: 1rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
}
.order-items-shell .items-section {
    background: #ffffff;
    border-radius: 10px;
}
@media (max-width: 1199px) {
    .order-top-summary__fields {
        grid-template-columns: repeat(2, minmax(150px, 1fr));
    }
}
@media (max-width: 767px) {
    .order-top-summary__fields,
    .order-client-meta,
    .order-details-grid {
        grid-template-columns: 1fr;
    }
    .order-save-row {
        width: 100%;
        justify-content: stretch;
    }
    .order-save-row #saveOrderBtn {
        width: 100%;
    }
}
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
</section>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalOrder" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-order">
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
    const taxModalEl = document.getElementById('addTaxModalOrder');
    const openTaxModalLink = document.getElementById('open-tax-modal-order');
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
