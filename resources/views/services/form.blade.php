@extends('layouts.app')

@section('header_actions')
<a href="{{ route('services.index') }}"
    class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium">
    Back to Items
</a>
@endsection

@section('content')
<div id="app-toast-container" class="app-toast-container"></div>

<section class="position-relative bg-white p-3 rounded-3">
    <form method="POST" action="{{ isset($service) ? route('services.update', $service) : route('services.store') }}"
        class="mainForm" id="item-form">
        @isset($service)
        @method('PUT')
        @endisset
        @csrf

        @php
        if (isset($service)) {
        $existingCostings = old('costings', $service->costings->map(function($c) {
        return [
        'currency_code' => $c->currency_code,
        'cost_price' => $c->cost_price,
        'selling_price' => $c->selling_price,
        'sac_code' => $c->sac_code,
        'tax_rate' => $c->tax_rate,
        ];
        })->toArray());

        if (empty($existingCostings)) {
        $existingCostings = [[
        'currency_code' => $defaultCurrency ?? 'INR',
        'cost_price' => '',
        'selling_price' => '',
        'sac_code' => '',
        'tax_rate' => '',
        ]];
        }

        $selectedAddonIds = collect(old('addons', $service->addons ?? []))->values()->all();
        } else {
        $existingCostings = collect(old('costings', [[
        'currency_code' => $defaultCurrency ?? 'INR',
        'cost_price' => '',
        'selling_price' => '',
        'sac_code' => '',
        'tax_rate' => '',
        ]]))->values()->all();
        $selectedAddonIds = collect(old('addons', []))->values()->all();
        }
        @endphp

        <div class="row g-3 align-items-stretch">
            <div class="col-12 col-lg-4">
                <div class="bg-light p-4 rounded-3 border h-100">
                    <div class="mb-3">
                        <h5 class="fw-semibold text-black mb-0">Item Details</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label for="type" class="form-label small lh-sm fw-semibold text-dark mb-1">Type *</label>
                            <select id="type" name="type" required class="form-select">
                                <option value="product" {{ old('type', isset($service) ? $service->type : 'service') ==
                                    'product' ? 'selected' : '' }}>Product</option>
                                <option value="service" {{ old('type', isset($service) ? $service->type : 'service') ==
                                    'service' ? 'selected' : '' }}>Service</option>
                            </select>
                            @error('type') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-12">
                            <label for="ps_catid"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Category</label>
                            <div class="input-group">
                                <select id="ps_catid" name="ps_catid" class="form-select">
                                    <option value="">-- No Category --</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->ps_catid }}" {{ old('ps_catid', isset($service) ?
                                        $service->ps_catid : '') == $category->ps_catid ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" id="btn-add-category-inline"
                                    class="btn btn-outline-primary btn-primary text-white" title="Add Category">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            @error('ps_catid') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-12">
                            <label for="name" class="form-label small lh-sm fw-semibold text-dark mb-1">Item Name
                                *</label>
                            <input type="text" id="name" name="name"
                                value="{{ old('name', isset($service) ? $service->name : '') }}" required
                                class="form-control">
                            @error('name') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-12">
                            <label for="grace_period" class="form-label small lh-sm fw-semibold text-dark mb-1">Grace
                                Period (days)</label>
                            <input type="number" id="grace_period" name="grace_period" min="0" step="1"
                                value="{{ old('grace_period', isset($service) ? (int) ($service->grace_period ?? 0) : 0) }}"
                                class="form-control">
                            @error('grace_period') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="bg-light p-4 rounded-3 border h-100">
                    <div class="mb-3">
                        <h5 class="fw-semibold text-black mb-0">Description & Settings</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label for="description"
                                class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                            <textarea id="description" name="description" rows="3"
                                class="form-control">{{ old('description', isset($service) ? $service->description : '') }}</textarea>
                            @error('description') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        @if($account && $account->allow_sync)
                        <div class="col-12">
                            <div
                                class="d-flex justify-content-between align-items-center bg-white rounded-3 border p-3">
                                <label for="sync" class="form-label small lh-sm fw-semibold text-dark mb-0">Sync with
                                    Superadmin</label>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input type="hidden" name="sync" value="no">
                                    <input type="checkbox" name="sync" value="yes" id="sync" class="form-check-input"
                                        role="switch" {{ old('sync', isset($service) ? $service->sync : 'no') == 'yes' ?
                                    'checked' : '' }}>
                                </div>
                            </div>
                            @error('sync') <span class="error">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div class="col-12">
                            <div
                                class="d-flex justify-content-between align-items-center bg-white rounded-3 border p-3">
                                <label for="user_wise" class="form-label small lh-sm fw-semibold text-dark mb-0">Sold
                                    per User?</label>
                                <div class="form-check form-switch fs-4 mb-0">
                                    <input type="hidden" name="user_wise" value="0">
                                    <input type="checkbox" name="user_wise" value="1" id="user_wise"
                                        class="form-check-input" role="switch" {{ old('user_wise', isset($service) ?
                                        $service->user_wise : 0) ? 'checked' : '' }}>
                                </div>
                            </div>
                            @error('user_wise') <span class="error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="bg-light p-4 rounded-3 border h-100">
                    <div class="mb-3">
                        <h5 class="fw-semibold text-black mb-0">Parent Items</h5>
                        <p class="small text-muted mb-0">This item belongs under which parent item(s)?</p>
                    </div>

                    <div class="position-relative" id="addons-dropdown-wrap">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                            id="addons-toggle">
                            <span id="addons-selected-label">Select parent items</span>
                        </button>
                        <div id="addons-dropdown"
                            class="bg-white border rounded-3 shadow-sm p-2 position-absolute w-100 z-3"
                            style="display: none; max-height: 220px; overflow-y: auto;">
                            @forelse($availableAddonItems as $item)
                            <label class="d-flex align-items-center gap-2 px-2 py-1 rounded-1" style="cursor: pointer;">
                                <input type="checkbox" value="{{ $item->itemid }}" data-item-name="{{ $item->name }}"
                                    class="form-check-input m-0 addon-checkbox" {{ in_array($item->itemid,
                                $selectedAddonIds, true) ? 'checked' : '' }}>
                                <span class="small">{{ $item->name }} <span class="text-muted">({{ ucfirst($item->type
                                        ?? 'service') }})</span></span>
                            </label>
                            @empty
                            <span class="text-muted small px-2">No existing items available.</span>
                            @endforelse
                        </div>
                    </div>
                    <div id="addons-hidden-inputs"></div>
                    <div id="saved-addons-list" class="d-flex flex-wrap gap-1 mt-2"></div>
                    @error('addons') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('addons.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="bg-light p-4 rounded-3 border mt-3">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                <div>
                    <h5 class="fw-semibold text-black mb-0">Item Costings</h5>
                    <p class="small text-muted mb-0">Add pricing per currency</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" id="add-costing-row"
                        class="btn btn-outline-primary btn-sm btn-primary text-white">+ Add currency</button>
                    @if($account->allow_multi_taxation)
                    <a href="#" id="open-tax-modal" class="btn btn-outline-primary btn-sm bg-white text-primary">+ Add
                        Tax</a>
                    @endif
                </div>
            </div>

            <div id="costings-empty-state" class="alert alert-light border mb-0">
                No pricing rows yet. Click + Add currency.
            </div>

            <div id="costings-table-wrap" class="card border-0 shadow-sm overflow-hidden mt-3">
                <div class="table-responsive">
                    <table class="table mainTable align-middle mb-0" id="costings-table">
                        <thead class="table-light">
                            <tr>
                                <th>Currency *</th>
                                <th>Cost Price *</th>
                                <th>Selling Price *</th>
                                <th>SAC Code</th>
                                <th>Tax</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="costing-rows">
                            @foreach($existingCostings as $index => $costing)
                            <tr>
                                <td>
                                    <select name="costings[{{ $index }}][currency_code]"
                                        class="form-select form-select-sm" required>
                                        <option value="">Select</option>
                                        @foreach($currencies as $currency)
                                        <option value="{{ $currency->iso }}" {{ ($costing['currency_code'] ?? ''
                                            )===$currency->iso ? 'selected' : '' }}>
                                            {{ $currency->iso }} - {{ $currency->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][cost_price]"
                                        value="{{ $costing['cost_price'] ?? '' }}" required
                                        class="form-control form-control-sm"></td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][selling_price]"
                                        value="{{ $costing['selling_price'] ?? '' }}" required
                                        class="form-control form-control-sm"></td>
                                <td><input type="text" maxlength="20" name="costings[{{ $index }}][sac_code]"
                                        value="{{ $costing['sac_code'] ?? '' }}" class="form-control form-control-sm">
                                </td>
                                <td>
                                    @if($account->allow_multi_taxation)
                                    <select name="costings[{{ $index }}][tax_rate]" class="form-select form-select-sm">
                                        <option value="0">-- None --</option>
                                        @php
                                            $groupedTaxes = $taxes->groupBy(fn($tax) => $tax->type ?: 'Other');
                                        @endphp
                                        @foreach($groupedTaxes as $taxType => $typeTaxes)
                                        @if($typeTaxes->isNotEmpty())
                                        <optgroup label="{{ $taxType }}">
                                            @foreach($typeTaxes as $tax)
                                            <option value="{{ $tax->rate }}" {{ (string) ($costing['tax_rate'] ?? ''
                                                )===(string) $tax->rate ? 'selected' : '' }}>{{ $tax->tax_name ??
                                                $tax->type }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </optgroup>
                                        @endif
                                        @endforeach
                                    </select>
                                    @else
                                    <input type="hidden" name="costings[{{ $index }}][tax_rate]"
                                        value="{{ number_format($account->fixed_tax_rate ?? 0, 2, '.', '') }}">
                                    <span class="badge text-bg-secondary">
                                        {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                    </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <button type="button" class="bg04 color04 remove-costing">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addCategoryInlineModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-semibold">
                            <i class="fas fa-folder-plus icon-spaced text-muted"></i>Add Category
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-light p-4">
                        <div class="mb-3">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                for="inline-category-name">Category Name *</label>
                            <input type="text" id="inline-category-name" class="form-control" maxlength="150"
                                placeholder="Enter category name">
                        </div>
                        <div id="inline-category-error"
                            style="display:none; margin-bottom:0.75rem; padding:0.55rem 0.7rem; border-radius:8px; background:#fef2f2; color:#b91c1c; font-size:0.82rem;">
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                                data-bs-dismiss="modal">
                                <i class="fas fa-times btn-icon me-1"></i> Cancel
                            </button>
                            <button type="button" id="save-inline-category-btn"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">
                                Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="saved-items-panel" class="card border-0 shadow-sm overflow-hidden mt-3" style="display: none;">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <strong class="small fw-semibold">Saved Items</strong>
                <span id="saved-items-count" class="small text-muted">0 saved</span>
            </div>
            <div class="table-responsive">
                <table class="table mainTable border align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Costings</th>
                            <th>Parent Items</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="saved-items-list"></tbody>
                </table>
            </div>
        </div>

        @if($account->allow_multi_taxation)
        <div class="modal fade" id="addTaxModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-semibold">
                            <i class="fas fa-receipt icon-spaced text-muted"></i>Add Tax
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light p-4">
                        <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form" class="mainForm">
                            @csrf
                            <input type="hidden" name="redirect_back" value="1">
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="quick_tax_rate">Rate (%)</label>
                                <input type="number" id="quick_tax_rate" name="rate" placeholder="18" step="0.01"
                                    min="0" max="100" required class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                    for="quick_tax_type">Type</label>
                                <select id="quick_tax_type" name="type" required class="form-select">
                                    @foreach(['GST'=>'GST','VAT'=>'VAT'] as $v=>$l)
                                    <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium"
                                    data-bs-dismiss="modal">
                                    <i class="fas fa-times btn-icon me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    Add Tax <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="d-flex align-items-center justify-content-end gap-2 mt-3">
            @isset($service)
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">Update Item</button>
            @else
            <button type="button" id="save-item-stay-btn"
                class="btn btn-outline-primary btn-primary text-white fw-medium">Save Item</button>
            <a href="{{ route('services.index') }}" id="finish-btn"
                class="btn btn-outline-primary bg-white text-primary fw-medium hidden">Finish</a>
            @endisset
        </div>
    </form>
</section>

<script>
    function showToast(type, message) {
        const container = document.getElementById('app-toast-container');
        const toast = document.createElement('div');
        toast.className = `app-toast app-toast-${type}`;

        const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
        toast.innerHTML = `<i class="fas ${icon} toast-icon"></i><span>${message}</span>`;

        container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.classList.add('app-toast-leaving');
                setTimeout(() => {
                    if (toast.parentNode) toast.remove();
                }, 300);
            }
        }, 3500);
    }

    (function () {
        const costingTableBody = document.getElementById('costing-rows');
        const costingTableWrap = document.getElementById('costings-table-wrap');
        const costingEmptyState = document.getElementById('costings-empty-state');
        const saveItemStayBtn = document.getElementById('save-item-stay-btn');
        const savedItemsPanel = document.getElementById('saved-items-panel');
        const savedItemsList = document.getElementById('saved-items-list');
        const savedItemsCount = document.getElementById('saved-items-count');
        let costingRowIndex = costingTableBody.rows.length;
        const editUrlTemplate = @json(route('services.edit', 'SERVICEID'));

        @php
        $currencyOptions = collect($currencies) -> map(function ($currency) {
            return '<option value="'.e($currency -> iso). '">'.e($currency -> iso. ' - '.$currency -> name). '</option>';
        }) -> implode('');

        $isMultiTax = $account -> allow_multi_taxation ?? false;
        $fixedTaxRate = $account -> fixed_tax_rate ?? 0;

        $taxGroupsData = $isMultiTax ? $taxes -> groupBy('type') -> map(function ($group, $type) {
            return $group -> map(fn($t) => [
                'name' => $t -> tax_name ?? $t -> type,
                'rate' => $t -> rate,
            ]) -> values() -> all();
        }) -> filter(fn($group) => count($group) > 0) -> all() : [];
        @endphp

        const currencyOptionsHtml = @json($currencyOptions);
        const isMultiTax = @json($isMultiTax);
        const fixedTaxRate = parseFloat(@json($fixedTaxRate)) || 0;
        const taxGroups = @json($taxGroupsData);

        function _esc(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        let taxOptionsHtml = '<option value="0">-- None --</option>';
        for (const type in taxGroups) {
            if (taxGroups[type].length > 1) {
                taxOptionsHtml += '<optgroup label="' + _esc(type) + '">';
                taxGroups[type].forEach(t => {
                    taxOptionsHtml += '<option value="' + t.rate + '">' + _esc(t.name) + ' (' + t.rate + '%)</option>';
                });
                taxOptionsHtml += '</optgroup>';
            } else {
                taxGroups[type].forEach(t => {
                    taxOptionsHtml += '<option value="' + t.rate + '">' + _esc(t.name) + ' (' + t.rate + '%)</option>';
                });
            }
        }

        function taxSelectHtml(i) {
            if (isMultiTax) {
                return `<select name="costings[${i}][tax_rate]" class="form-select form-select-sm">${taxOptionsHtml}</select>`;
            } else {
                return `<input type="hidden" name="costings[${i}][tax_rate]" value="${fixedTaxRate.toFixed(2)}"><span class="badge text-bg-secondary">${fixedTaxRate.toFixed(2)}%</span>`;
            }
        }

        function syncCostingTableVisibility() {
            const hasRows = costingTableBody.querySelectorAll('tr').length > 0;
            costingTableWrap.style.display = hasRows ? 'block' : 'none';
            costingEmptyState.style.display = hasRows ? 'none' : 'block';
        }

        function costingRowHtml(i) {
            return `
            <tr>
                <td>
                    <select name="costings[${i}][currency_code]" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${i}][cost_price]" required class="form-control form-control-sm"></td>
                <td><input type="number" step="0.01" name="costings[${i}][selling_price]" required class="form-control form-control-sm"></td>
                <td><input type="text" maxlength="20" name="costings[${i}][sac_code]" class="form-control form-control-sm"></td>
                <td>${taxSelectHtml(i)}</td>
                <td class="text-end">
                    <div class="tableActionButton d-inline-flex gap-1">
                        <button type="button" class="bg04 color04 remove-costing">Delete</button>
                    </div>
                </td>
            </tr>
        `;
        }

        document.getElementById('add-costing-row').addEventListener('click', function () {
            const row = document.createElement('tr');
            row.innerHTML = costingRowHtml(costingRowIndex).trim();
            const select = row.querySelector('select[name^="costings"][name$="[currency_code]"]');
            if (select) {
                select.value = '{{ $defaultCurrency ?? "INR" }}';
            }
            costingTableBody.appendChild(row);
            costingRowIndex++;
            syncCostingTableVisibility();
        });

        costingTableBody.addEventListener('click', function (e) {
            const removeButton = e.target.closest('.remove-costing');
            if (!removeButton) return;
            if (costingTableBody.querySelectorAll('tr').length === 1) {
                alert('At least one costing row is required.');
                return;
            }
            removeButton.closest('tr').remove();
            syncCostingTableVisibility();
        });

        const dropdownWrap = document.getElementById('addons-dropdown-wrap');
        const dropdown = document.getElementById('addons-dropdown');
        const toggle = document.getElementById('addons-toggle');
        const selectedLabel = document.getElementById('addons-selected-label');
        const savedAddonsList = document.getElementById('saved-addons-list');
        const hiddenInputsWrap = document.getElementById('addons-hidden-inputs');
        const initialSavedAddonIds = @json($selectedAddonIds);
        const savedAddons = new Map();

        function refreshAddonLabel() {
            const checked = dropdown.querySelectorAll('.addon-checkbox:checked').length;
            selectedLabel.textContent = checked > 0 ? `${checked} parent item(s) selected` : 'Select parent items';
        }

        function renderSavedAddons() {
            savedAddonsList.innerHTML = '';
            hiddenInputsWrap.innerHTML = '';

            if (savedAddons.size === 0) {
                const empty = document.createElement('span');
                empty.className = 'text-muted small';
                empty.textContent = 'No parent items selected yet.';
                savedAddonsList.appendChild(empty);
                return;
            }

            savedAddons.forEach((name, id) => {
                const pill = document.createElement('span');
                pill.className = 'badge bg-light text-dark border fw-normal';
                pill.innerHTML = `${name} <button type="button" data-remove-addon-id="${id}" class="btn-close btn-close-dark" style="font-size: 0.5rem; vertical-align: middle;"></button>`;
                savedAddonsList.appendChild(pill);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'addons[]';
                hidden.value = id;
                hiddenInputsWrap.appendChild(hidden);
            });
        }

        function collectCostingsFromRows() {
            const rows = Array.from(costingTableBody.querySelectorAll('tr'));
            const costings = [];

            rows.forEach((row) => {
                const currency = row.querySelector('select[name*="[currency_code]"]')?.value || '';
                const costPrice = row.querySelector('input[name*="[cost_price]"]')?.value || '';
                const sellingPrice = row.querySelector('input[name*="[selling_price]"]')?.value || '';
                const sacCode = row.querySelector('input[name*="[sac_code]"]')?.value || '';
                const taxInput = row.querySelector('[name*="[tax_rate]"]');
                const taxRate = taxInput ? parseFloat(taxInput.value || '0') : (isMultiTax ? 0 : fixedTaxRate);

                if (currency || costPrice || sellingPrice || sacCode || taxRate) {
                    costings.push({
                        currency_code: currency,
                        cost_price: costPrice,
                        selling_price: sellingPrice,
                        sac_code: sacCode,
                        tax_rate: Number.isFinite(taxRate) ? taxRate : 0
                    });
                }
            });

            return costings;
        }

        function renderSavedItemRow(item) {
            savedItemsPanel.style.display = '';
            const editUrl = editUrlTemplate.replace('SERVICEID', item.itemid);
            const costings = item.costings || [];
            const validCostings = costings.filter((c) => (c.currency_code || '').trim() !== '');

            const parentItemNames = Array.from(savedAddons.values());

            let costingsHtml;
            if (validCostings.length === 0) {
                costingsHtml = '<span class="text-muted small">No costings</span>';
            } else {
                costingsHtml = validCostings.map((c) => {
                    const price = c.selling_price ? Number(c.selling_price).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
                    const tax = c.tax_rate ? c.tax_rate + '%' : '';
                    return `<span class="badge bg-light text-dark border fw-normal">${c.currency_code} ${price} <small class="text-muted">(${tax || 'No Tax'})</small></span>`;
                }).join(' ');
            }

            let parentsHtml = '';
            if (parentItemNames.length > 0) {
                const parentPills = parentItemNames.map(name =>
                    `<span class="badge bg-primary-subtle text-primary border-0 fw-normal">↖ ${name}</span>`
                ).join(' ');
                parentsHtml = `<div class="d-flex flex-wrap gap-1">${parentPills}</div>`;
            }

            const catSelect = document.getElementById('ps_catid');
            const catName = catSelect.options[catSelect.selectedIndex]?.text || '';
            const catHtml = (catName && !catName.includes('--')) ? `<span class="text-muted small">in ${catName}</span>` : '';

            const row = document.createElement('tr');
            row.innerHTML = `
            <td>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-muted fw-normal text-uppercase" style="font-size: 0.65rem;">${item.type}</span>
                    <strong class="fw-semibold text-dark">${item.name}</strong>
                    ${catHtml}
                </div>
            </td>
            <td><span class="badge bg-light text-muted fw-normal text-uppercase" style="font-size: 0.65rem;">${item.type}</span></td>
            <td><div class="d-flex flex-wrap gap-1">${costingsHtml}</div></td>
            <td>${parentsHtml || '<span class="text-muted small">—</span>'}</td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
                    <a href="${editUrl}" class="bg03 color03">Edit</a>
                </div>
            </td>
        `;
            savedItemsList.prepend(row);

            const count = savedItemsList.querySelectorAll('tr').length;
            savedItemsCount.textContent = `${count} saved`;
        }

        function resetAfterQuickSave() {
            document.getElementById('type').value = 'service';
            const syncInput = document.getElementById('sync');
            if (syncInput) {
                syncInput.checked = false;
            }
            document.getElementById('user_wise').checked = false;
            document.getElementById('name').value = '';
            document.getElementById('ps_catid').value = '';
            document.getElementById('description').value = '';

            costingTableBody.innerHTML = '';
            const firstRow = document.createElement('tr');
            firstRow.innerHTML = costingRowHtml(0).trim();
            const firstCurrency = firstRow.querySelector('select[name^="costings"][name$="[currency_code]"]');
            if (firstCurrency) {
                firstCurrency.value = '{{ $defaultCurrency ?? "INR" }}';
            }
            costingTableBody.appendChild(firstRow);
            costingRowIndex = 1;
            syncCostingTableVisibility();

            savedAddons.clear();
            dropdown.querySelectorAll('.addon-checkbox').forEach((cb) => {
                cb.checked = false;
            });
            renderSavedAddons();
            refreshAddonLabel();

            document.getElementById('finish-btn').style.display = 'inline-block';

            document.getElementById('name').focus();
        }

        if (saveItemStayBtn) {
            saveItemStayBtn.addEventListener('click', async function () {
                const addonsArray = Array.from(savedAddons.keys());

                const payload = {
                    type: document.getElementById('type').value,
                    sync: document.getElementById('sync')?.checked ? 'yes' : 'no',
                    user_wise: document.getElementById('user_wise').checked ? 1 : 0,
                    name: document.getElementById('name').value.trim(),
                    ps_catid: document.getElementById('ps_catid').value || null,
                    description: document.getElementById('description').value.trim(),
                    addons: addonsArray,
                    costings: collectCostingsFromRows()
                };

                if (!payload.name) {
                    alert('Item name is required.');
                    document.getElementById('name').focus();
                    return;
                }

                if (!payload.costings.length) {
                    alert('At least one costing row is required.');
                    return;
                }

                try {
                    saveItemStayBtn.disabled = true;
                    saveItemStayBtn.textContent = 'Saving...';

                    const res = await fetch("{{ route('services.ajax-save') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Failed to save item.');
                    }

                    renderSavedItemRow({
                        name: payload.name,
                        type: payload.type,
                        itemid: data.itemid,
                        costings: payload.costings
                    });
                    resetAfterQuickSave();

                    showToast('success', 'Item saved successfully!');
                } catch (error) {
                    showToast('error', error.message || 'Unable to save item.');
                } finally {
                    saveItemStayBtn.disabled = false;
                    saveItemStayBtn.textContent = 'Save Item';
                }
            });
        }

        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
        });

        document.addEventListener('click', function (e) {
            if (!dropdownWrap.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        dropdown.querySelectorAll('.addon-checkbox').forEach((cb) => {
            cb.addEventListener('change', function () {
                if (cb.checked) {
                    savedAddons.set(cb.value, cb.dataset.itemName || cb.value);
                } else {
                    savedAddons.delete(cb.value);
                }
                refreshAddonLabel();
                renderSavedAddons();
            });
        });

        savedAddonsList.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-remove-addon-id]');
            if (!btn) return;
            const id = btn.dataset.removeAddonId;
            savedAddons.delete(id);
            const checkbox = dropdown.querySelector(`.addon-checkbox[value="${id}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            renderSavedAddons();
            refreshAddonLabel();
        });

        initialSavedAddonIds.forEach((id) => {
            const checkbox = dropdown.querySelector(`.addon-checkbox[value="${id}"]`);
            if (checkbox) {
                savedAddons.set(id, checkbox.dataset.itemName || id);
            }
        });

        syncCostingTableVisibility();
        refreshAddonLabel();
        renderSavedAddons();

        const taxModalEl = document.getElementById('addTaxModal');
        const openTaxModalLink = document.getElementById('open-tax-modal');
        if (taxModalEl && openTaxModalLink) {
            const taxModal = new bootstrap.Modal(taxModalEl);
            openTaxModalLink.addEventListener('click', function (e) {
                e.preventDefault();
                taxModal.show();
            });
        }

        const categorySelect = document.getElementById('ps_catid');
        const addCategoryBtn = document.getElementById('btn-add-category-inline');
        const addCategoryModalEl = document.getElementById('addCategoryInlineModal');
        const addCategoryNameInput = document.getElementById('inline-category-name');
        const addCategoryError = document.getElementById('inline-category-error');
        const saveCategoryBtn = document.getElementById('save-inline-category-btn');
        const addCategoryModal = addCategoryModalEl ? new bootstrap.Modal(addCategoryModalEl) : null;

        function showCategoryError(message) {
            if (!addCategoryError) return;
            addCategoryError.textContent = message || 'Unable to save category.';
            addCategoryError.style.display = 'block';
        }

        if (addCategoryBtn && addCategoryModal) {
            addCategoryBtn.addEventListener('click', function () {
                if (addCategoryError) {
                    addCategoryError.style.display = 'none';
                    addCategoryError.textContent = '';
                }
                if (addCategoryNameInput) addCategoryNameInput.value = '';
                addCategoryModal.show();
                setTimeout(() => addCategoryNameInput?.focus(), 100);
            });
        }

        if (saveCategoryBtn) {
            saveCategoryBtn.addEventListener('click', async function () {
                const categoryName = (addCategoryNameInput?.value || '').trim();
                if (!categoryName) {
                    showCategoryError('Category name is required.');
                    addCategoryNameInput?.focus();
                    return;
                }

                if (addCategoryError) {
                    addCategoryError.style.display = 'none';
                    addCategoryError.textContent = '';
                }

                try {
                    saveCategoryBtn.disabled = true;
                    saveCategoryBtn.innerHTML = 'Saving... <i class="fas fa-spinner fa-spin ms-1"></i>';

                    const response = await fetch("{{ route('product-categories.store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            name: categoryName,
                            status: 'active'
                        })
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success || !data.category) {
                        throw new Error(data.message || 'Failed to create category.');
                    }

                    const existingOption = Array.from(categorySelect.options)
                        .find(opt => opt.value === data.category.ps_catid);

                    if (!existingOption) {
                        const option = document.createElement('option');
                        option.value = data.category.ps_catid;
                        option.textContent = data.category.name;
                        categorySelect.appendChild(option);
                    }

                    categorySelect.value = data.category.ps_catid;
                    addCategoryModal.hide();
                    showToast('success', 'Category added.');
                } catch (error) {
                    showCategoryError(error.message || 'Unable to add category right now.');
                } finally {
                    saveCategoryBtn.disabled = false;
                    saveCategoryBtn.innerHTML = 'Save Category <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                }
            });
        }
    })();
</script>
@endsection
