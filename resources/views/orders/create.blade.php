@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('orders.index', ['c' => $preSelectedClientId ?? ($order->clientid ?? request('c'))]) }}" class="secondary-button">
        Back to Orders
    </a>
@endsection

@section('content')
@php
    $selectedClientId = old('clientid', $preSelectedClientId ?? ($order->clientid ?? request('c')));
    $todayDate = now()->format('Y-m-d');
    $maxEndDate = '2099-12-31';
    $isEditingOrder = (bool) $isEditMode;
    $isIframeMode = (string) request()->query('iframe') === '1';
    $isClientLocked = $isEditingOrder || $isIframeMode;
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
@endphp

<section class="panel-card panel-card-lg">
    <form method="POST" action="{{ $isEditMode ? route('orders.update', $order->orderid) : route('orders.store') }}" id="orderForm" class="client-form">
        @csrf
        @if($isEditMode)
            @method('PUT')
        @endif

        <div class="invoice-client-header mb-3">
            <div class="invoice-client-header__row">
                <div class="invoice-client-header__icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="invoice-client-header__body" style="min-width: 0; flex: 1;">
                    @if(!$isClientLocked)
                        <label for="clientid" class="field-label" style="margin-bottom: 0.25rem;">Select Client *</label>
                        <select id="clientid" name="clientid" class="form-control" required>
                            <option value="">Select Client</option>
                            @php
                                $clientsByType = collect($clients ?? [])->groupBy(function ($client) {
                                    return strtolower((string) ($client->type ?? 'regular')) === 'trial' ? 'trial' : 'regular';
                                });
                            @endphp
                            @foreach(['regular' => 'Regular Clients', 'trial' => 'Trial Clients'] as $typeKey => $typeLabel)
                                @if(($clientsByType[$typeKey] ?? collect())->isNotEmpty())
                                    <optgroup label="{{ $typeLabel }}">
                                        @foreach($clientsByType[$typeKey] as $client)
                                            <option value="{{ $client->clientid }}" {{ (string) $selectedClientId === (string) $client->clientid ? 'selected' : '' }}>
                                                {{ $client->business_name ?? $client->contact_name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="clientid" value="{{ $selectedClientId }}">
                        <div class="invoice-client-header__name">{{ $selectedClientName }}</div>
                        <div class="invoice-client-header__email" style="{{ $selectedClientEmail ? '' : 'display:none;' }}">{{ $selectedClientEmail }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="form-grid order-create-meta-grid">
        </div>

        <hr class="my-4">

        <div class="section-header mb-3">
            <div class="section-icon"><i class="fas fa-box"></i></div>
            <h4 class="section-title">{{ $isEditMode ? 'Edit Item' : 'Add Items' }}</h4>
        </div>

        <div class="add-item-row form-grid form-input-row order-create-item-box">
            <div class="order-field order-field-item">
                <label>Item</label>
                <select id="item_itemid" class="form-control" {{ ($isEditMode && !empty($isItemLockedByInvoice)) ? 'disabled' : '' }}>
                    <option value="">Select Item</option>
                    @php
                        $groupedServices = $services->groupBy(fn($service) => $service->category->name ?? 'No Category');
                    @endphp
                    @foreach($groupedServices as $categoryName => $categoryServices)
                        <optgroup label="{{ $categoryName }}">
                            @foreach($categoryServices as $service)
                                <option
                                    value="{{ $service->itemid }}"
                                    data-description="{{ $service->description ?? '' }}"
                                    data-user-wise="{{ (int) ($service->user_wise ?? 0) }}"
                                >
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="order-field">
                <label>Qty</label>
                <input type="number" id="item_quantity" class="form-control" min="1" step="1" value="1">
            </div>

            @if($account?->have_users)
                <div id="item_users_wrapper" class="order-field" style="display:none;">
                    <label>Users</label>
                    <input type="number" id="item_users" class="form-control" min="1" step="1" value="1">
                </div>
            @else
                <input type="hidden" id="item_users" value="1">
            @endif

            <div class="order-field">
                <label>Frequency</label>
                <select id="item_frequency" class="form-control">
                    <option value="">None</option>
                    <option value="One-Time">One-Time</option>
                    <option value="Day(s)">Day(s)</option>
                    <option value="Week(s)">Week(s)</option>
                    <option value="Month(s)">Month(s)</option>
                    <option value="Quarter(s)">Quarter(s)</option>
                    <option value="Year(s)">Year(s)</option>
                </select>
            </div>

            <div id="item_duration_wrapper" class="order-field" style="display:none;">
                <label>Duration</label>
                <input type="number" id="item_duration" class="form-control" min="1" step="1" value="1">
            </div>

            <div class="order-field">
                <label>Start Date</label>
                <input type="date" id="item_start_date" class="form-control" value="{{ $todayDate }}" readonly>
            </div>

            <div class="order-field">
                <label>End Date</label>
                <input type="date" id="item_end_date" class="form-control" value="{{ $maxEndDate }}" readonly>
            </div>

            <div class="order-field">
                <label>Delivery Date</label>
                <input type="date" id="item_delivery_date" class="form-control">
            </div>
            <div class="order-field">
                <label for="client_docid">PO</label>
                <select id="client_docid" name="client_docid" class="form-control">
                    <option value="">Select Document</option>
                    @php
                        $poDocuments = collect($clientDocuments ?? [])
                            ->filter(fn ($document) => (string) ($document->clientid ?? '') === (string) ($selectedClientId ?? ''))
                            ->filter(fn ($document) => trim((string) ($document->title ?? '')) !== '')
                            ->values();
                    @endphp
                    @forelse($poDocuments as $document)
                        <option value="{{ $document->client_docid }}" {{ old('client_docid', $order->client_docid ?? '') == $document->client_docid ? 'selected' : '' }}>
                            {{ $document->title }}
                        </option>
                    @empty
                        <option value="" disabled>No PO documents found</option>
                    @endforelse
                </select>
            </div>
            <div class="order-field order-field-description" style="width: 50%;">
                <label>Description</label>
                <textarea id="item_description" class="form-control"></textarea>
            </div>

            @if(!$isEditMode)
                <div class="order-field order-field-action text-end" style="grid-column: 1 / -1;">
                    <button type="button" id="addItemBtn" class="primary-button">
                        Add Order
                    </button>
                </div>
            @endif
        </div>

        <input type="hidden" name="items_data" id="items_data">

        <div class="mt-4 flex items-center gap-2 order-create-submit-bar">
            @if($isEditMode)
                <button type="submit" class="primary-button">
                    Update Order
                </button>
            @endif
            <span class="text-sm text-muted">
                {{ $isEditMode ? '' : 'Add Order saves directly to the orders table.' }}
            </span>
        </div>
    </form>

    @if(!$isEditMode)
        @if(!empty($selectedClientId) && ($recentOrders ?? collect())->isNotEmpty())
            <div class="order-create-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th></th>
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
                                <td>{{ ($recentOrder->status ?? '') === 'running' ? 'Active' : ucfirst((string) ($recentOrder->status ?? 'active')) }}</td>
                                <td class="text-end">
                                    <a
                                        href="{{ route('orders.edit', ['order' => $recentOrder->orderid, 'return_to' => 'create', 'c' => $selectedClientId, 'iframe' => request()->query('iframe')]) }}"
                                        class="text-action-btn edit"
                                    >
                                        Edit
                                    </a>
                                    <form
                                        method="POST"
                                        action="{{ route('orders.destroy', ['order' => $recentOrder->orderid, 'return_to' => 'create', 'c' => $selectedClientId, 'iframe' => request()->query('iframe')]) }}"
                                        style="display:inline;"
                                        onsubmit="return confirm('Cancel this order?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-action-btn delete">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const items = [];
    const isEditMode = @json((bool) $isEditMode);
    const todayDate = @json($todayDate);
    const maxEndDate = @json($maxEndDate);
    @php
        $existingOrderData = $order ? [
            'itemid' => $order->itemid,
            'item_name' => $order->item_name,
            'item_description' => $order->item_description,
            'quantity' => (float) ($order->quantity ?? 1),
            'no_of_users' => $order->no_of_users,
            'frequency' => '',
            'duration' => 1,
            'start_date' => $order->start_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'end_date' => $order->end_date?->format('Y-m-d') ?? '2099-12-31',
            'delivery_date' => $order->delivery_date?->format('Y-m-d'),
        ] : null;
    @endphp
    const existingOrder = @json($existingOrderData);
    const existingClientItemIds = new Set((@json($existingClientItemIds ?? []))
        .map(value => String(value || ''))
        .filter(Boolean));
    const itemSelect = document.getElementById('item_itemid');
    const clientSelect = document.getElementById('clientid');
    const addItemBtn = document.getElementById('addItemBtn');
    const itemsInput = document.getElementById('items_data');
    const usersWrapper = document.getElementById('item_users_wrapper');
    const frequencyInput = document.getElementById('item_frequency');
    const durationInput = document.getElementById('item_duration');
    const durationWrapper = document.getElementById('item_duration_wrapper');
    const startDateInput = document.getElementById('item_start_date');
    const endDateInput = document.getElementById('item_end_date');
    let editingItemIndex = null;

    function isSelectedItemUserWise() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        return option && option.dataset.userWise === '1';
    }

    function toggleUsersField() {
        if (!usersWrapper) return;
        const show = isSelectedItemUserWise();
        usersWrapper.style.display = show ? 'block' : 'none';
        if (!show) {
            document.getElementById('item_users').value = 1;
        }
    }

    function syncItemsInput() {
        itemsInput.value = JSON.stringify(items);
    }

    function isOneTimeFrequency() {
        const selectedFrequency = frequencyInput.value || '';
        return selectedFrequency === '' || selectedFrequency === 'One-Time';
    }

    function toggleDurationField() {
        if (!durationWrapper) return;

        const show = !isOneTimeFrequency();
        durationWrapper.style.display = show ? 'block' : 'none';

        if (!show) {
            durationInput.value = 1;
        } else if (!durationInput.value || Number(durationInput.value) < 1) {
            durationInput.value = 1;
        }
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
        endDateInput.value = calculateEndDate(
            startDateInput.value || todayDate,
            frequencyInput.value || '',
            durationInput.value || 1
        );
    }


    function populateFromSelection() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        if (!option || !option.value) return;
        document.getElementById('item_description').value = option.dataset.description || '';
        startDateInput.value = todayDate;
        refreshEndDate();
        toggleUsersField();
    }

    function currentItemPayload() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        return {
            itemid: itemSelect.value,
            item_name: option ? option.text.trim() : '',
            item_description: document.getElementById('item_description').value || '',
            quantity: document.getElementById('item_quantity').value || 1,
            no_of_users: usersWrapper && usersWrapper.style.display !== 'none'
                ? (document.getElementById('item_users').value || 1)
                : null,
            frequency: frequencyInput.value || 'One-Time',

            duration: isOneTimeFrequency() ? null : (durationInput.value || 1),
            start_date: startDateInput.value || todayDate,
            end_date: endDateInput.value || maxEndDate,
            delivery_date: document.getElementById('item_delivery_date').value || '',
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
        if (!addItemBtn) return;
        addItemBtn.textContent = editingItemIndex === null ? 'Add Order' : 'Update Order';
    }

    function resetItemForm() {
        itemSelect.value = '';
        document.getElementById('item_quantity').value = 1;
        document.getElementById('item_users').value = 1;
        frequencyInput.value = '';
        durationInput.value = 1;
        startDateInput.value = todayDate;
        document.getElementById('item_delivery_date').value = '';
        document.getElementById('item_description').value = '';
        editingItemIndex = null;
        toggleUsersField();
        refreshEndDate();
        setAddButtonState();
    }

    function loadItemIntoForm(item) {
        if (!item) return;
        itemSelect.value = item.itemid || '';
        document.getElementById('item_quantity').value = item.quantity || 1;
        document.getElementById('item_users').value = item.no_of_users || 1;
        frequencyInput.value = item.frequency || '';
        durationInput.value = item.duration || 1;
        startDateInput.value = item.start_date || todayDate;
        endDateInput.value = item.end_date || maxEndDate;
        document.getElementById('item_delivery_date').value = item.delivery_date || '';
        document.getElementById('item_description').value = item.item_description || '';
        toggleUsersField();
        toggleDurationField();
    }

    if (clientSelect && !isEditMode) {
        clientSelect.addEventListener('change', function () {
            if (!this.value) return;
            const url = new URL(window.location.href);
            url.searchParams.set('c', this.value);
            window.location.href = url.toString();
        });
    }

    frequencyInput.addEventListener('change', refreshEndDate);
    durationInput.addEventListener('input', refreshEndDate);

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
        const selectedItemId = String(itemSelect.value || '');
        if (!selectedItemId || isEditMode) {
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

    itemSelect.addEventListener('change', async function () {
        populateFromSelection();
        await handleItemSelectionDuplicateCheck();
    });

    if (addItemBtn) {
        addItemBtn.addEventListener('click', async function () {
            if (!itemSelect.value) {
                alert('Select an item first.');
                return;
            }

            if (!isEditMode) {
                refreshEndDate();
            }
            const payload = currentItemPayload();
            if (!payload.end_date) {
                alert('End date is required.');
                return;
            }

            const orderForm = document.getElementById('orderForm');
            if (isEditMode) {
                items.splice(0, items.length, payload);
                syncItemsInput();
                orderForm.requestSubmit();
                return;
            } else {
                items.splice(0, items.length, payload);
                syncItemsInput();
                orderForm.requestSubmit();
                return;
            }
        });
    }

    const orderForm = document.getElementById('orderForm');
    const isIframe = window.self !== window.top;

    orderForm.addEventListener('submit', async function (event) {
        if (isEditMode) {
            if (!itemSelect.value) {
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
            items.splice(0, items.length, payload);
            syncItemsInput();
        } else {
            if (!items.length) {
                event.preventDefault();
                alert('Add at least one item before saving.');
                return;
            }
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

    if (existingOrder) {
        loadItemIntoForm(existingOrder);
    }
});
</script>
@endsection
