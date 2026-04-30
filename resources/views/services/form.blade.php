@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('services.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Items
    </a>
@endsection

@section('content')
{{-- Toast Container --}}
<div id="app-toast-container" class="app-toast-container"></div>

<section class="panel-card service-panel-card">
    <form method="POST" action="{{ isset($service) ? route('services.update', $service) : route('services.store') }}" class="service-form" id="item-form">
        @isset($service)
            @method('PUT')
        @endisset
        @csrf

        <div class="service-grid">
            <div class="service-field">
                <label for="type">Type *</label>
                <select id="type" name="type" required class="service-input-compact">
                    <option value="product" {{ old('type', isset($service) ? $service->type : 'service') == 'product' ? 'selected' : '' }}>Product</option>
                    <option value="service" {{ old('type', isset($service) ? $service->type : 'service') == 'service' ? 'selected' : '' }}>Service</option>
                </select>
                @error('type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field">
                <label for="ps_catid">Category</label>
                <select id="ps_catid" name="ps_catid" class="service-input-compact">
                    <option value="">-- No Category --</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->ps_catid }}" {{ old('ps_catid', isset($service) ? $service->ps_catid : '') == $category->ps_catid ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
                @error('ps_catid') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-toggle">
                <label for="sync">Sync</label>
                <label class="custom-checkbox service-check">
                    <input type="hidden" name="sync" value="no">
                    <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync', isset($service) ? $service->sync : 'no') == 'yes' ? 'checked' : '' }}>
                </label>
                @error('sync') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-toggle">
                <label for="user_wise">User-wise</label>
                <label class="custom-checkbox service-check">
                    <input type="hidden" name="user_wise" value="0">
                    <input type="checkbox" name="user_wise" value="1" id="user_wise" {{ old('user_wise', isset($service) ? $service->user_wise : 0) ? 'checked' : '' }}>
                </label>
                @error('user_wise') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field service-span-2">
                <label for="name">Item Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', isset($service) ? $service->name : '') }}" required class="service-input-compact">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field service-span-2">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="service-input-compact">{{ old('description', isset($service) ? $service->description : '') }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        @php
            if (isset($service)) {
                $existingCostings = old('costings', $service->costings->map(function($c) {
                    return [
                        'currency_code' => $c->currency_code,
                        'cost_price' => $c->cost_price,
                        'selling_price' => $c->selling_price,
                        'sac_code' => $c->sac_code,
                        'taxid' => $c->taxid,
                        'tax_rate' => $c->tax_rate,
                    ];
                })->toArray());

                if (empty($existingCostings)) {
                    $existingCostings = [[
                        'currency_code' => $defaultCurrency ?? 'INR',
                        'cost_price' => '',
                        'selling_price' => '',
                        'sac_code' => '',
                        'taxid' => '',
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

        <div class="section-divider">
            <div class="addons-wrap" id="addons-dropdown-wrap">
                <span class="checkbox-label addons-title">This item belongs under which parent item(s)?</span>
                <button type="button" class="secondary-button addons-toggle" id="addons-toggle">
                    <span id="addons-selected-label">Select parent items</span>
                    <span aria-hidden="true">&#9662;</span>
                </button>
                <div id="addons-dropdown" class="addons-dropdown">
                    @forelse($availableAddonItems as $item)
                        <label class="custom-checkbox addon-option">
                            <input type="checkbox" value="{{ $item->itemid }}" data-item-name="{{ $item->name }}" class="addon-checkbox" {{ in_array($item->itemid, $selectedAddonIds, true) ? 'checked' : '' }}>
                            <span class="checkbox-label">{{ $item->name }}</span>
                            <span class="addon-meta">({{ ucfirst($item->type ?? 'service') }})</span>
                        </label>
                    @empty
                        <p class="addons-empty">No existing items available.</p>
                    @endforelse
                </div>
            </div>
            <div id="addons-hidden-inputs"></div>
            <div id="saved-addons-list" class="saved-addons"></div>
            @error('addons') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="section-divider">
            <div class="costing-head">
                <div>
                    <p class="eyebrow eyebrow costing-eyebrow">Item Costings</p>
                    <strong class="costing-title">Add pricing per currency</strong>
                </div>
                <div class="costing-tools">
                    <button type="button" class="text-link" id="add-costing-row" class="text-link text-link-xs">+ Add currency</button>
                    @if($account->allow_multi_taxation)
                    <a href="#" id="open-tax-modal" class="text-link text-link-sm text-link">+ Add Tax</a>
                    @endif
                </div>
            </div>

            <p id="costings-empty-state" class="costing-empty">
                No pricing rows yet. Click + Add currency.
            </p>

            <div id="costings-table-wrap" class="table-x">
                <table class="data-table data-table table-compact-xs" id="costings-table">
                    <thead>
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
                                    <select name="costings[{{ $index }}][currency_code]" class="cell-input cell-input-currency" required>
                                        <option value="">Select</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->iso }}" {{ ($costing['currency_code'] ?? '') === $currency->iso ? 'selected' : '' }}>
                                                {{ $currency->iso }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][cost_price]" value="{{ $costing['cost_price'] ?? '' }}" required class="cell-input cell-input-100"></td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][selling_price]" value="{{ $costing['selling_price'] ?? '' }}" required class="cell-input cell-input-100"></td>
                                <td><input type="text" maxlength="20" name="costings[{{ $index }}][sac_code]" value="{{ $costing['sac_code'] ?? '' }}" class="cell-input cell-input-80"></td>
                                <td>
                                    @if($account->allow_multi_taxation)
                                    <select name="costings[{{ $index }}][taxid]" class="tax-select tax-select cell-input-tax">
                                        <option value="">-- None --</option>
                                        @foreach($taxes->groupBy(fn($tax) => $tax->type ?: 'Other') as $taxType => $typeTaxes)
                                            @if($typeTaxes->isNotEmpty())
                                                <optgroup label="{{ $taxType }}">
                                                    @foreach($typeTaxes as $tax)
                                                        <option value="{{ $tax->taxid }}" data-rate="{{ $tax->rate }}" data-name="{{ $tax->tax_name ?? $tax->type }}" {{ ($costing['taxid'] ?? '') === $tax->taxid ? 'selected' : '' }}>{{ $tax->tax_name ?? $tax->type }} ({{ $tax->rate }}%)</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                    @else
                                    <input type="hidden" name="costings[{{ $index }}][taxid]" value="">
                                    <span class="fixed-tax-chip">
                                        {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                    </span>
                                    @endif
                                </td>
                                <td class="cell-actions">
                                    <button type="button" class="icon-action-btn delete remove-costing remove-costing-compact"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div id="saved-items-panel" class="section-divider hidden">
            <div class="flex-between">
                <strong class="small-text">Saved Items</strong>
                <span id="saved-items-count" class="saved-items-count">0 saved</span>
            </div>
            <div id="saved-items-list" class="saved-items-list"></div>
        </div>

        {{-- Add Tax Modal --}}
        @if($account->allow_multi_taxation)
        <div class="modal fade" id="addTaxModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered modal-dialog modal-sm modal-dialog-centered modal-420">
                <div class="modal-content rounded-panel">
                    <div class="modal-header modal-header-custom">
                        <h5 class="modal-title modal-title service-modal-title">
                            <i class="fas fa-receipt icon-spaced text-muted"></i>Add Tax
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body modal-body service-modal-body">
                        <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form">
                            @csrf
                            <input type="hidden" name="redirect_back" value="1">
                            <div class="field-gap">
                                <label class="label-compact">Rate (%)</label>
                                <input type="number" name="rate" placeholder="18" step="0.01" min="0" max="100" required
                                       class="service-input-full">
                            </div>
                            <div class="field-gap">
                                <label class="label-compact">Type</label>
                                <select name="type" required
                                        class="service-input-full">
                                    @foreach(['GST'=>'GST','VAT'=>'VAT'] as $v=>$l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-center-gap">
                                <button type="submit" class="primary-button small">Add Tax</button>
                                <button type="button" class="text-link small" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <div class="form-actions form-actions service-actions">
            @isset($service)
                <button type="submit" class="primary-button primary-button btn-compact">Update Item</button>
            @else
                <button type="button" id="save-item-stay-btn" class="primary-button primary-button btn-compact">Save Item</button>
                <a href="{{ route('services.index') }}" id="finish-btn" class="secondary-button btn-compact hidden">Finish</a>
            @endisset
        </div>
    </form>
</section>

<script>
// Toast notification function
function showToast(type, message) {
    const container = document.getElementById('app-toast-container');
    const toast = document.createElement('div');
    toast.className = `app-toast app-toast-${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    toast.innerHTML = `<i class="fas ${icon} toast-icon"></i><span>${message}</span>`;
    
    container.appendChild(toast);
    
    // Auto-dismiss after 3.5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('app-toast-leaving');
            setTimeout(() => {
                if (toast.parentNode) toast.remove();
            }, 300);
        }
    }, 3500);
}

(function() {
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
        $currencyOptions = collect($currencies)->map(function ($currency) {
            return '<option value="' . e($currency->iso) . '">' . e($currency->iso . ' - ' . $currency->name) . '</option>';
        })->implode('');

        $isMultiTax = $account->allow_multi_taxation ?? false;
        $fixedTaxRate = $account->fixed_tax_rate ?? 0;

        $taxGroupsData = $isMultiTax ? $taxes->groupBy('type')->map(function($group, $type) {
            return $group->map(fn($t) => [
                'id' => $t->taxid,
                'name' => $t->tax_name ?? $t->type,
                'rate' => $t->rate,
            ])->values()->all();
        })->filter(fn($group) => count($group) > 0)->all() : [];
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

    let taxOptionsHtml = '<option value="">-- None --</option>';
    for (const type in taxGroups) {
        if (taxGroups[type].length > 1) {
            taxOptionsHtml += '<optgroup label="' + _esc(type) + '">';
            taxGroups[type].forEach(t => {
                taxOptionsHtml += '<option value="' + t.id + '" data-rate="' + t.rate + '">' + _esc(t.name) + ' (' + t.rate + '%)</option>';
            });
            taxOptionsHtml += '</optgroup>';
        } else {
            taxGroups[type].forEach(t => {
                taxOptionsHtml += '<option value="' + t.id + '" data-rate="' + t.rate + '">' + _esc(t.name) + ' (' + t.rate + '%)</option>';
            });
        }
    }

    function taxSelectHtml(i) {
        if (isMultiTax) {
            return `<select name="costings[${i}][taxid]" class="tax-select cell-input-tax">${taxOptionsHtml}</select>`;
        } else {
            return `<input type="hidden" name="costings[${i}][taxid]" value=""><span class="fixed-tax-chip">${fixedTaxRate.toFixed(2)}%</span>`;
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
                    <select name="costings[${i}][currency_code]" class="cell-input cell-input-currency" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${i}][cost_price]" required class="cell-input cell-input-100"></td>
                <td><input type="number" step="0.01" name="costings[${i}][selling_price]" required class="cell-input cell-input-100"></td>
                <td><input type="text" maxlength="20" name="costings[${i}][sac_code]" class="cell-input cell-input-80"></td>
                <td>${taxSelectHtml(i)}</td>
                <td class="cell-actions">
                    <button type="button" class="icon-action-btn delete remove-costing remove-costing-compact"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    }

    document.getElementById('add-costing-row').addEventListener('click', function() {
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

    costingTableBody.addEventListener('click', function(e) {
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
            empty.className = 'saved-addons-empty';
            empty.textContent = 'No parent items selected yet.';
            savedAddonsList.appendChild(empty);
            return;
        }

        savedAddons.forEach((name, id) => {
            const pill = document.createElement('span');
            pill.className = 'saved-addon-pill';
            pill.innerHTML = `${name} <button type="button" data-remove-addon-id="${id}" class="saved-addon-remove">x</button>`;
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
            const taxId = row.querySelector('select[name*="[taxid]"]')?.value || '';

            if (currency || costPrice || sellingPrice || sacCode || taxId) {
                costings.push({
                    currency_code: currency,
                    cost_price: costPrice,
                    selling_price: sellingPrice,
                    sac_code: sacCode,
                    taxid: taxId || null
                });
            }
        });

        return costings;
    }

    function renderSavedItemRow(item) {
        savedItemsPanel.style.display = 'block';
        const editUrl = editUrlTemplate.replace('SERVICEID', item.itemid);
        const costings = item.costings || [];
        const validCostings = costings.filter((c) => (c.currency_code || '').trim() !== '');
        
        const parentItemNames = Array.from(savedAddons.values());

        let costingsHtml;
        if (validCostings.length === 0) {
            costingsHtml = '<span class="saved-item-empty">No costings</span>';
        } else {
            costingsHtml = validCostings.map((c) => {
                const price = c.selling_price ? Number(c.selling_price).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '—';
                const tax = c.tax_rate ? c.tax_rate + '%' : (c.taxid ? 'Taxed' : '');
                return `<span class="saved-costing-pill">${c.currency_code} ${price} <small class="saved-costing-note">(${tax || 'No Tax'})</small></span>`;
            }).join(' ');
        }
        
        let parentsHtml = '';
        if (parentItemNames.length > 0) {
            const parentPills = parentItemNames.map(name => 
                `<span class="saved-parent-pill">↖ ${name}</span>`
            ).join(' ');
            parentsHtml = `<div class="saved-parent-wrap">${parentPills}</div>`;
        }

        const catSelect = document.getElementById('ps_catid');
        const catName = catSelect.options[catSelect.selectedIndex]?.text || '';
        const catHtml = (catName && !catName.includes('--')) ? `<span class="saved-item-cat">in ${catName}</span>` : '';

        const row = document.createElement('div');
        row.className = 'saved-item-row';
        row.innerHTML = `
            <div class="saved-item-main">
                <div class="saved-item-head">
                    <span class="saved-item-type">${item.type}</span>
                    <strong class="saved-item-name">${item.name}</strong>
                    ${catHtml}
                </div>
                <div class="saved-costings-wrap">${costingsHtml}</div>
                ${parentsHtml}
            </div>
            <div class="saved-item-actions">
                <a href="${editUrl}" class="icon-action-btn edit" title="Edit Item"><i class="fas fa-edit"></i></a>
            </div>
        `;
        savedItemsList.prepend(row);

        const count = savedItemsList.children.length;
        savedItemsCount.textContent = `${count} saved`;
    }

    function resetAfterQuickSave() {
        document.getElementById('type').value = 'service';
        document.getElementById('sync').checked = false;
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
            console.log('Selected addons:', addonsArray);
            console.log('savedAddons Map:', savedAddons);
            
            const payload = {
                type: document.getElementById('type').value,
                sync: document.getElementById('sync').checked ? 'yes' : 'no',
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

    toggle.addEventListener('click', function () {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
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
        openTaxModalLink.addEventListener('click', function(e) {
            e.preventDefault();
            taxModal.show();
        });
    }
})();
</script>
@endsection
