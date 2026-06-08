@extends('layouts.app')

@section('header_actions')
<a href="{{ request()->query('return_to') === 'trials' ? route('orders.trials') : route('orders.index', ['c' => $preSelectedClientId ?? ($order->clientid ?? request('c'))]) }}"
    class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left btn-icon"></i> Back to Orders
</a>
@endsection

@section('content')
@php
$selectedClientId = old('clientid', $preSelectedClientId ?? ($order->clientid ?? request('c')));
$selectedQuotationId = old('quotationid', request('quotationid', ''));
$todayDate = now()->format('Y-m-d');
$maxEndDate = '2099-12-31';
$isEditingOrder = (bool) $isEditMode;
$isIframeMode = (string) request()->query('iframe') === '1';
$isClientLocked = $isEditingOrder || $isIframeMode;
$clientQuotations = collect($clientQuotations ?? [])->values();
$selectedClient = $isEditingOrder
? ($order->client ?? collect($clients ?? [])->firstWhere('clientid', $selectedClientId))
: collect($clients ?? [])->firstWhere('clientid', $selectedClientId);
$selectedClientName = (string) (
$selectedClient?->business_name
?? $selectedClient?->contact_name
?? 'Select Client'
);
$selectedClientEmail = (string) (
$selectedClient?->primary_email
?? $selectedClient?->billingDetail?->billing_email
?? $selectedClient?->email
?? ''
);
$initialOrderItems = [];
if (is_string(old('items_data')) && trim((string) old('items_data')) !== '') {
$decodedOldItems = json_decode((string) old('items_data'), true);
if (is_array($decodedOldItems)) {
$initialOrderItems = $decodedOldItems;
}
} elseif ($isEditingOrder && !empty($order)) {
$initialOrderItems = [[
'itemid' => $order->itemid,
'item_name' => $order->item_name,
'item_description' => $order->item_description,
'quantity' => (float) ($order->quantity ?? 1),
'no_of_users' => $order->no_of_users,
'frequency' => '',
'duration' => 1,
'start_date' => $order->start_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
'end_date' => $order->end_date?->format('Y-m-d') ?? $maxEndDate,
'delivery_date' => $order->delivery_date?->format('Y-m-d'),
]];
}
@endphp

<div class="position-relative bg-white p-3 rounded-3">
    <form method="POST"
        action="{{ $isEditMode ? route('orders.update', ['order' => $order->orderid, 'return_to' => request()->query('return_to')]) : route('orders.store') }}"
        id="orderForm" class="mainForm">
        @csrf
        @if($isEditMode)
        @method('PUT')
        @endif

        @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                <li class="small">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="row g-3 align-items-stretch">
            <div class="col-12 col-lg-4">
                <div class="bg-light p-4 rounded-3 border h-100">
                    <div class="mb-3">
                        <h5 class="fw-semibold text-black mb-0">Client Details</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-12">
                            <label for="clientid" class="form-label small lh-sm fw-semibold text-dark mb-1">Select
                                Client *</label>
                            <select id="clientid" name="{{ $isClientLocked ? '' : 'clientid' }}" class="form-select" {{
                                $isClientLocked ? 'disabled' : '' }} required>
                                <option value="">Select Client</option>
                                @foreach($clients ?? [] as $client)
                                <option value="{{ $client->clientid }}" {{ (string)$selectedClientId===(string)$client->
                                    clientid ? 'selected' : '' }}>
                                    {{ $client->business_name ?? $client->contact_name }}
                                </option>
                                @endforeach
                            </select>

                            @if($isClientLocked)
                            <input type="hidden" name="clientid" value="{{ $selectedClientId }}">
                            @endif
                            <!-- <label class="form-label small lh-sm fw-semibold text-dark mb-1">Client</label>
                            <input type="hidden" name="clientid" value="{{ $selectedClientId }}">
                            <div class="fw-semibold text-dark">{{ $selectedClientName }}</div>
                            <div class="text-muted small {{ $selectedClientEmail ? '' : 'is-hidden' }}">{{
                                $selectedClientEmail }}</div> -->
                        </div>

                        @if(!$isEditMode)
                        <div class="col-12 col-md-12">
                            <label for="quotationid"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Quotation</label>
                            <select id="quotationid" class="form-select" {{ empty($selectedClientId) ? 'disabled' : ''
                                }}>
                                <option value="">Select Quotation</option>
                                @forelse($clientQuotations as $quotation)
                                <option value="{{ $quotation['quotationid'] }}" {{ (string)
                                    $selectedQuotationId===(string) $quotation['quotationid'] ? 'selected' : '' }}>
                                    {{ $quotation['display_title'] ?? $quotation['quo_title'] ??
                                    $quotation['quotation_number'] ?? $quotation['quotationid'] }}
                                </option>
                                @empty
                                <option value="" disabled>
                                    {{ empty($selectedClientId) ? 'Select a client first' : 'No quotations found for
                                    this client' }}
                                </option>
                                @endforelse
                            </select>
                            <div class="form-text text-muted small mt-1">
                                Select a quotation to load its items into the order.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <div class="bg-light p-4 rounded-3 border h-100">
                    <div class="mb-3">
                        <h5 class="fw-semibold text-black mb-0">{{ $isEditMode ? 'Edit Item' : 'Add Items' }}</h5>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Item</label>
                            <select id="item_itemid" class="form-select" {{ ($isEditMode &&
                                !empty($isItemLockedByInvoice)) ? 'disabled' : '' }}>
                                <option value="">Select Item</option>
                                @php
                                $groupedServices = $services->groupBy(fn($service) => $service->category->name ?? 'No
                                Category');
                                @endphp
                                @foreach($groupedServices as $categoryName => $categoryServices)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach($categoryServices as $service)
                                    <option value="{{ $service->itemid }}"
                                        data-description="{{ $service->description ?? '' }}"
                                        data-user-wise="{{ (int) ($service->user_wise ?? 0) }}">
                                        {{ $service->name }}
                                    </option>
                                    @endforeach
                                </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Qty</label>
                            <input type="number" id="item_quantity" class="form-control" min="1" step="1" value="1">
                        </div>

                        @if($account?->have_users)
                        <div class="col-12 col-md-2 is-hidden" id="item_users_wrapper">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Users</label>
                            <input type="number" id="item_users" class="form-control" min="1" step="1" value="1">
                        </div>
                        @else
                        <input type="hidden" id="item_users" value="1">
                        @endif

                        <div class="col-12 col-md-2">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Frequency</label>
                            <select id="item_frequency" class="form-select">
                                <option value="">None</option>
                                <option value="One-Time">One-Time</option>
                                <option value="Day(s)">Day(s)</option>
                                <option value="Week(s)">Week(s)</option>
                                <option value="Month(s)">Month(s)</option>
                                <option value="Quarter(s)">Quarter(s)</option>
                                <option value="Year(s)">Year(s)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-2 is-hidden" id="item_duration_wrapper">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Duration</label>
                            <input type="number" id="item_duration" class="form-control" min="1" step="1" value="1">
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Start Date</label>
                            <input type="date" id="item_start_date" class="form-control" value="{{ $todayDate }}"
                                readonly>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">End Date</label>
                            <input type="date" id="item_end_date" class="form-control" value="{{ $maxEndDate }}"
                                readonly>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Delivery Date</label>
                            <input type="date" id="item_delivery_date" class="form-control">
                        </div>

                        <div class="col-12 col-md-3">
                            <label for="client_docid"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">PO</label>
                            <select id="client_docid" name="client_docid" class="form-select">
                                <option value="">Select Document</option>
                                @php
                                $poDocuments = collect($clientDocuments ?? [])
                                ->filter(fn ($document) => (string) ($document->clientid ?? '') === (string)
                                ($selectedClientId ?? ''))
                                ->filter(fn ($document) => trim((string) ($document->title ?? '')) !== '')
                                ->values();
                                @endphp
                                @forelse($poDocuments as $document)
                                <option value="{{ $document->client_docid }}" {{ old('client_docid', $order->
                                    client_docid ?? '') == $document->client_docid ? 'selected' : '' }}>
                                    {{ $document->title }}
                                </option>
                                @empty
                                <option value="" disabled>No PO documents found</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                            <textarea id="item_description" class="form-control" rows="3"></textarea>
                        </div>

                        @if(!$isEditMode)
                        <div class="col-12 d-flex justify-content-end">
                            <button type="button" id="addItemBtn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Add Item
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="items_data" id="items_data">

        @if(!$isEditMode)
        <div id="orderItemsEmptyState" class="empty-state mt-4">
            {{-- No items added yet. Choose a quotation or add items manually. --}}
        </div>

        <div id="orderItemsTableWrap" class="order-create-table-wrap mt-4 is-hidden" style="display: none;">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="table-responsive">
                    <table class="table mainTable align-middle mb-0" style="table-layout: fixed;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 38%;">Item</th>
                                <th style="width: 8%;">Qty</th>
                                <th style="width: 9%;">Users</th>
                                <th class="text-nowrap">Start</th>
                                <th class="text-nowrap">End</th>
                                <th class="text-nowrap">Delivery</th>
                                <th class="text-end" style="width: 12%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="orderSubmitBar" class="d-flex align-items-center justify-content-end gap-2 mt-3"
            style="display: none;">
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                Save Orders
            </button>
        </div>
        @else
        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                Update Order
            </button>
        </div>
        @endif
    </form>

    @if(!$isEditMode)
    @if(!empty($selectedClientId) && ($recentOrders ?? collect())->isNotEmpty())
    <div class="card border-0 shadow-sm overflow-hidden mt-4">
        <div class="table-responsive">
            <table class="table mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $recentOrder)
                    <tr>
                        <td>{{ $recentOrder->order_number ?? 'N/A' }}</td>
                        <td>{{ $recentOrder->item_name ?? 'Item' }}</td>
                        <td>{{ (int) ($recentOrder->quantity ?? 1) }}</td>
                        <td>{{ $recentOrder->start_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ $recentOrder->end_date?->format('d M Y') ?? '-' }}</td>
                        <td>{{ ($recentOrder->status ?? '') === 'running' ? 'Active' : ucfirst((string)
                            ($recentOrder->status ?? 'active')) }}</td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <a href="{{ route('orders.edit', ['order' => $recentOrder->orderid, 'return_to' => 'create', 'c' => $selectedClientId, 'iframe' => request()->query('iframe')]) }}"
                                    class="bg03 color03">
                                    Edit
                                </a>
                                <form method="POST"
                                    action="{{ route('orders.destroy', ['order' => $recentOrder->orderid, 'return_to' => 'create', 'c' => $selectedClientId, 'iframe' => request()->query('iframe')]) }}"
                                    class="inline-delete" onsubmit="return confirm('Cancel this order?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg04 color04 border-0">Cancel</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isEditMode = @json((bool) $isEditMode);
        const isIframe = window.self !== window.top;
        const todayDate = @json($todayDate);
        const maxEndDate = @json($maxEndDate);
        const initialItems = @json($initialOrderItems ?? []);
        const clientQuotations = @json($clientQuotations ?? []);
        const existingClientItemIds = new Set((@json($existingClientItemIds ?? []))
            .map(value => String(value || ''))
            .filter(Boolean));

        const itemSelect = document.getElementById('item_itemid');
        const clientSelect = document.getElementById('clientid');
        const quotationSelect = document.getElementById('quotationid');
        const addItemBtn = document.getElementById('addItemBtn');
        const itemsInput = document.getElementById('items_data');
        const orderItemsBody = document.getElementById('orderItemsBody');
        const orderItemsEmptyState = document.getElementById('orderItemsEmptyState');
        const orderItemsTableWrap = document.getElementById('orderItemsTableWrap');
        const orderSubmitBar = document.getElementById('orderSubmitBar');
        const usersWrapper = document.getElementById('item_users_wrapper');
        const usersInput = document.getElementById('item_users');
        const frequencyInput = document.getElementById('item_frequency');
        const durationInput = document.getElementById('item_duration');
        const durationWrapper = document.getElementById('item_duration_wrapper');
        const startDateInput = document.getElementById('item_start_date');
        const endDateInput = document.getElementById('item_end_date');
        const deliveryDateInput = document.getElementById('item_delivery_date');
        const descriptionInput = document.getElementById('item_description');
        const quantityInput = document.getElementById('item_quantity');
        const orderForm = document.getElementById('orderForm');
        const quotationMap = new Map(clientQuotations.map((quotation) => [String(quotation.quotationid || ''), quotation]));

        let items = Array.isArray(initialItems) ? initialItems.map(normalizeItem) : [];
        let editingItemIndex = null;
        let activeQuotationId = '';

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeItem(item) {
            return {
                itemid: String(item?.itemid || ''),
                item_name: String(item?.item_name || item?.name || 'Item').trim() || 'Item',
                item_description: String(item?.item_description || ''),
                quantity: Math.max(1, Math.round(Number(item?.quantity || 1))),
                no_of_users: item?.no_of_users === null || item?.no_of_users === undefined || String(item?.no_of_users || '').trim() === ''
                    ? null
                    : Math.max(1, Math.round(Number(item?.no_of_users || 1))),
                frequency: String(item?.frequency || ''),
                duration: item?.duration === null || item?.duration === undefined || String(item?.duration || '').trim() === ''
                    ? null
                    : Math.max(1, Math.round(Number(item?.duration || 1))),
                start_date: String(item?.start_date || todayDate),
                end_date: String(item?.end_date || maxEndDate),
                delivery_date: String(item?.delivery_date || ''),
            };
        }

        function syncItemsInput() {
            if (itemsInput) {
                itemsInput.value = JSON.stringify(items);
            }
        }

        function isSelectedItemUserWise() {
            const option = itemSelect?.options?.[itemSelect.selectedIndex];
            return String(option?.dataset?.userWise || '0') === '1';
        }

        function isOneTimeFrequency() {
            const selectedFrequency = frequencyInput?.value || '';
            return selectedFrequency === '' || selectedFrequency === 'One-Time';
        }

        function formatDateLocal(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function calculateEndDate(startDate, frequency, duration) {
            if (!frequency || frequency === 'One-Time') {
                return maxEndDate;
            }

            if (!startDate) {
                return maxEndDate;
            }

            const start = new Date(startDate + 'T00:00:00');
            const end = new Date(start);
            const count = Math.max(1, parseInt(duration, 10) || 1);

            switch (frequency) {
                case 'Day(s)':
                    end.setDate(end.getDate() + count - 1);
                    break;
                case 'Week(s)':
                    end.setDate(end.getDate() + (count * 7) - 1);
                    break;
                case 'Month(s)':
                    end.setMonth(end.getMonth() + count);
                    end.setDate(end.getDate() - 1);
                    break;
                case 'Quarter(s)':
                    end.setMonth(end.getMonth() + (count * 3));
                    end.setDate(end.getDate() - 1);
                    break;
                case 'Year(s)':
                    end.setFullYear(end.getFullYear() + count);
                    end.setDate(end.getDate() - 1);
                    break;
                default:
                    break;
            }

            const max = new Date(maxEndDate + 'T00:00:00');
            if (end > max) {
                return maxEndDate;
            }

            return formatDateLocal(end);
        }

        function refreshEndDate() {
            toggleDurationField();
            if (endDateInput) {
                endDateInput.value = calculateEndDate(
                    startDateInput?.value || todayDate,
                    frequencyInput?.value || '',
                    durationInput?.value || 1
                );
            }
        }

        function toggleUsersField() {
            if (!usersWrapper || !usersInput) return;
            const show = isSelectedItemUserWise();
            usersWrapper.classList.toggle('is-hidden', !show);
            if (!show) {
                usersInput.value = 1;
            }
        }

        function toggleDurationField() {
            if (!durationWrapper || !durationInput) return;

            const show = !isOneTimeFrequency();
            durationWrapper.classList.toggle('is-hidden', !show);

            if (!show) {
                durationInput.value = 1;
            } else if (!durationInput.value || Number(durationInput.value) < 1) {
                durationInput.value = 1;
            }
        }

        function currentItemPayload() {
            const option = itemSelect?.options?.[itemSelect.selectedIndex];
            return {
                itemid: String(itemSelect?.value || ''),
                item_name: option ? String(option.textContent || '').trim() : '',
                item_description: String(descriptionInput?.value || ''),
                quantity: Math.max(1, Math.round(Number(quantityInput?.value || 1))),
                no_of_users: usersWrapper && !usersWrapper.classList.contains('is-hidden')
                    ? Math.max(1, Math.round(Number(usersInput?.value || 1)))
                    : null,
                frequency: String(frequencyInput?.value || ''),
                duration: isOneTimeFrequency()
                    ? null
                    : Math.max(1, Math.round(Number(durationInput?.value || 1))),
                start_date: String(startDateInput?.value || todayDate),
                end_date: String(endDateInput?.value || maxEndDate),
                delivery_date: String(deliveryDateInput?.value || ''),
            };
        }

        function hasDuplicateItemId(itemid, ignoreIndex = null) {
            return items.some((item, index) => {
                if (ignoreIndex !== null && index === ignoreIndex) {
                    return false;
                }
                return String(item.itemid || '') === String(itemid || '');
            });
        }

        function hasDuplicateInSavedOrders(itemid) {
            return existingClientItemIds.has(String(itemid || ''));
        }

        function setAddButtonState() {
            if (!addItemBtn || isEditMode) return;
            addItemBtn.textContent = editingItemIndex === null ? 'Add Item' : 'Update Item';
        }

        function resetItemForm() {
            if (itemSelect) itemSelect.value = '';
            if (quantityInput) quantityInput.value = 1;
            if (usersInput) usersInput.value = 1;
            if (frequencyInput) frequencyInput.value = '';
            if (durationInput) durationInput.value = 1;
            if (startDateInput) startDateInput.value = todayDate;
            if (endDateInput) endDateInput.value = maxEndDate;
            if (deliveryDateInput) deliveryDateInput.value = '';
            if (descriptionInput) descriptionInput.value = '';
            editingItemIndex = null;
            toggleUsersField();
            refreshEndDate();
            setAddButtonState();
        }

        function loadItemIntoForm(item) {
            if (!item) return;
            if (itemSelect) itemSelect.value = item.itemid || '';
            if (quantityInput) quantityInput.value = item.quantity || 1;
            if (usersInput) usersInput.value = item.no_of_users || 1;
            if (frequencyInput) frequencyInput.value = item.frequency || '';
            if (durationInput) durationInput.value = item.duration || 1;
            if (startDateInput) startDateInput.value = item.start_date || todayDate;
            if (endDateInput) endDateInput.value = item.end_date || maxEndDate;
            if (deliveryDateInput) deliveryDateInput.value = item.delivery_date || '';
            if (descriptionInput) descriptionInput.value = item.item_description || '';
            toggleUsersField();
            toggleDurationField();
            setAddButtonState();
        }

        function renderItems() {
            syncItemsInput();
            if (isEditMode || !orderItemsBody) {
                return;
            }

            orderItemsBody.innerHTML = '';

            if (items.length === 0) {
                orderItemsTableWrap?.classList.add('is-hidden');
                orderItemsTableWrap?.style.setProperty('display', 'none');
                orderItemsEmptyState?.classList.remove('is-hidden');
                if (orderSubmitBar) orderSubmitBar.style.display = 'none';
                return;
            }

            orderItemsEmptyState?.classList.add('is-hidden');
            orderItemsTableWrap?.classList.remove('is-hidden');
            orderItemsTableWrap?.style.removeProperty('display');
            if (orderSubmitBar) orderSubmitBar.style.removeProperty('display');

            items.forEach((item, index) => {
                const row = document.createElement('tr');
                if (editingItemIndex === index) {
                    row.classList.add('is-active');
                }
                const itemDescription = String(item.item_description || '').trim();
                row.innerHTML = `
                <td>
                    <div class="fw-semibold">${escapeHtml(item.item_name)}</div>
                    ${itemDescription ? `<div class="small-text">${escapeHtml(itemDescription)}</div>` : ''}
                </td>
                <td>${escapeHtml(item.quantity)}</td>
                <td>${item.no_of_users ? escapeHtml(item.no_of_users) : '-'}</td>
                <td class="text-nowrap">${escapeHtml(item.start_date || '-')}</td>
                <td class="text-nowrap">${escapeHtml(item.end_date || '-')}</td>
                <td class="text-nowrap">${item.delivery_date ? escapeHtml(item.delivery_date) : '-'}</td>
                <td class="text-end">
                    <div class="tableActionButton d-inline-flex gap-1">
                        <button type="button" class="bg03 color03 border-0" data-edit-index="${index}">Edit</button>
                        <button type="button" class="bg04 color04 border-0" data-remove-index="${index}">Remove</button>
                    </div>
                </td>
            `;
                orderItemsBody.appendChild(row);
            });

            orderItemsBody.querySelectorAll('[data-remove-index]').forEach((button) => {
                button.addEventListener('click', function () {
                    const index = Number(this.dataset.removeIndex);
                    items.splice(index, 1);
                    if (editingItemIndex !== null) {
                        editingItemIndex = null;
                        setAddButtonState();
                    }
                    renderItems();
                });
            });

            orderItemsBody.querySelectorAll('[data-edit-index]').forEach((button) => {
                button.addEventListener('click', function () {
                    const index = Number(this.dataset.editIndex);
                    const item = items[index];
                    if (!item) return;

                    editingItemIndex = index;
                    loadItemIntoForm(item);
                    renderItems();
                });
            });
        }

        async function confirmDuplicateItem() {
            if (typeof window.appConfirm === 'function') {
                return await window.appConfirm(
                    'This Product/Service already exists in the list. Do you want to add it again?',
                    {
                        title: 'Duplicate Product/Service',
                        icon: 'warning',
                        confirmButtonText: 'Add Again',
                        cancelButtonText: 'Cancel',
                        customClass: {
                            popup: 'app-swal-popup',
                            title: 'app-swal-title',
                            htmlContainer: 'app-swal-text',
                            confirmButton: 'app-swal-btn app-swal-btn-cancel',
                            cancelButton: 'app-swal-btn app-swal-btn-confirm',
                            icon: 'app-swal-icon',
                        },
                        width: 430,
                    }
                );
            }

            return confirm('This Product/Service already exists in the list. Do you want to add it again?');
        }

        async function confirmDuplicateSavedItem() {
            if (typeof window.appConfirm === 'function') {
                return await window.appConfirm(
                    'This Product/Service already exists in the list. Do you want to add it again?',
                    {
                        title: 'Duplicate Product/Service',
                        icon: 'warning',
                        cancelButtonText: 'Cancel',
                        confirmButtonText: 'Add Again',
                        customClass: {
                            popup: 'app-swal-popup',
                            title: 'app-swal-title',
                            htmlContainer: 'app-swal-text',
                            confirmButton: 'app-swal-btn app-swal-btn-cancel',
                            cancelButton: 'app-swal-btn app-swal-btn-confirm',
                            icon: 'app-swal-icon',
                        },
                        width: 430,
                    }
                );
            }

            return confirm('This Product/Service already exists in the list. Do you want to add it again?');
        }

        async function handleItemSelectionDuplicateCheck() {
            const selectedItemId = String(itemSelect?.value || '');
            if (!selectedItemId) {
                return true;
            }

            if (hasDuplicateItemId(selectedItemId, editingItemIndex)) {
                const shouldKeepDuplicate = await confirmDuplicateItem();
                if (!shouldKeepDuplicate) {
                    resetItemForm();
                    return false;
                }
            }

            if (editingItemIndex === null && hasDuplicateInSavedOrders(selectedItemId)) {
                const shouldKeepSavedDuplicate = await confirmDuplicateSavedItem();
                if (!shouldKeepSavedDuplicate) {
                    resetItemForm();
                    return false;
                }
            }

            return true;
        }

        async function applyQuotationItems(quotationId) {
            const quotation = quotationMap.get(String(quotationId || ''));
            if (!quotation) {
                return;
            }

            const importedItems = Array.isArray(quotation.items) ? quotation.items.map(normalizeItem) : [];
            items = importedItems;
            editingItemIndex = null;
            activeQuotationId = String(quotationId || '');
            renderItems();
            resetItemForm();
        }

        async function handleQuotationChange() {
            if (!quotationSelect) return;
            const nextQuotationId = String(quotationSelect.value || '');

            if (!nextQuotationId) {
                if (items.length > 0) {
                    const confirmed = confirm('Clear the current items?');
                    if (!confirmed) {
                        quotationSelect.value = activeQuotationId;
                        return;
                    }
                }
                activeQuotationId = '';
                items = [];
                editingItemIndex = null;
                renderItems();
                resetItemForm();
                return;
            }

            if (activeQuotationId !== nextQuotationId && items.length > 0) {
                const confirmed = confirm('Loading a quotation will replace the current item list. Continue?');
                if (!confirmed) {
                    quotationSelect.value = activeQuotationId;
                    return;
                }
            }

            await applyQuotationItems(nextQuotationId);
        }

        if (clientSelect && !isEditMode) {
            clientSelect.addEventListener('change', function () {
                if (!this.value) return;
                const url = new URL(window.location.href);
                url.searchParams.set('c', this.value);
                window.location.href = url.toString();
            });
        }

        if (itemSelect) {
            itemSelect.addEventListener('change', async function () {
                const option = itemSelect.options[itemSelect.selectedIndex];
                if (descriptionInput) {
                    descriptionInput.value = option?.dataset.description || '';
                }
                if (startDateInput) {
                    startDateInput.value = todayDate;
                }
                refreshEndDate();
                toggleUsersField();
                await handleItemSelectionDuplicateCheck();
            });
        }

        if (frequencyInput) {
            frequencyInput.addEventListener('change', refreshEndDate);
        }

        if (durationInput) {
            durationInput.addEventListener('input', refreshEndDate);
        }

        if (quotationSelect) {
            quotationSelect.addEventListener('change', handleQuotationChange);
        }

        if (addItemBtn && !isEditMode) {
            addItemBtn.addEventListener('click', async function () {
                if (!itemSelect || !itemSelect.value) {
                    alert('Select an item first.');
                    return;
                }

                if (!await handleItemSelectionDuplicateCheck()) {
                    return;
                }

                refreshEndDate();
                const payload = currentItemPayload();
                if (!payload.end_date) {
                    alert('End date is required.');
                    return;
                }

                if (editingItemIndex !== null) {
                    items.splice(editingItemIndex, 1, payload);
                    editingItemIndex = null;
                } else {
                    items.push(payload);
                }

                renderItems();
                resetItemForm();
            });
        }

        orderForm.addEventListener('submit', async function (event) {
            if (isEditMode) {
                if (!itemSelect || !itemSelect.value) {
                    event.preventDefault();
                    alert('Select an item first.');
                    return;
                }
                const payload = currentItemPayload();
                if (!payload.end_date) {
                    event.preventDefault();
                    alert('End date is required.');
                    return;
                }
                items = [payload];
                syncItemsInput();
            } else {
                if (!items.length) {
                    event.preventDefault();
                    alert('Add at least one item before saving.');
                    return;
                }
                syncItemsInput();
            }

            if (isIframe) {
                event.preventDefault();
                try {
                    const formData = new FormData(orderForm);
                    const response = await fetch(orderForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (window.parent && typeof window.parent.onOrderCreated === 'function') {
                            window.parent.onOrderCreated(data);
                        }
                    } else {
                        const errData = await response.json().catch(() => ({}));
                        let errMsg = errData.message || 'Failed to create order.';
                        if (errData.errors) {
                            errMsg += '\n' + Object.values(errData.errors).flat().join('\n');
                        }
                        alert(errMsg);
                    }
                } catch (err) {
                    console.error(err);
                    alert('An error occurred while creating the order.');
                }
            }
        });

        toggleUsersField();
        refreshEndDate();
        setAddButtonState();
        syncItemsInput();
        renderItems();

        if (isEditMode && items.length > 0) {
            loadItemIntoForm(items[0]);
        } else if (quotationSelect && quotationSelect.value) {
            activeQuotationId = String(quotationSelect.value || '');
            const selectedQuotation = quotationMap.get(activeQuotationId);
            if (selectedQuotation && Array.isArray(selectedQuotation.items) && items.length === 0) {
                items = selectedQuotation.items.map(normalizeItem);
                renderItems();
            }
        }
    });
</script>
@endsection
