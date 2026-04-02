@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
        <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Edit {{ $service->name }}</h3>
    </div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.update', $service) }}" class="service-form" id="service-form">
        @method('PUT')
        @csrf
        <input type="hidden" name="serviceid" id="serviceid" value="{{ $service->serviceid }}">
        
        <div class="form-grid">
            <div>
                <label for="type">Type *</label>
                <select id="type" name="type" required>
                    <option value="product" {{ old('type', $service->type ?? 'service') == 'product' ? 'selected' : '' }}>Product</option>
                    <option value="service" {{ old('type', $service->type ?? 'service') == 'service' ? 'selected' : '' }}>Service</option>
                </select>
                @error('type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="display:flex; align-items: end;">
                <label for="sync">Sync</label>
                <label class="custom-checkbox" style="margin-left: 0.5rem;">
                    <input type="hidden" name="sync" value="no">
                    <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync', $service->sync ?? 'no') == 'yes' ? 'checked' : '' }}>
                    <span class="checkbox-label">Yes</span>
                </label>
                @error('sync') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="name">Service Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="ps_catid">Category</label>
                <select id="ps_catid" name="ps_catid">
                    <option value="">-- No Category --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->ps_catid }}" {{ old('ps_catid', $service->ps_catid) == $category->ps_catid ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('ps_catid') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div style="grid-column: span 2;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        @php
            $existingCostings = old('costings', $service->costings->map(function($c) {
                return [
                    'currency_code' => $c->currency_code,
                    'cost_price' => $c->cost_price,
                    'selling_price' => $c->selling_price,
                    'sac_code' => $c->sac_code,
                    'tax_rate' => $c->tax_rate,
                    'tax_included' => $c->tax_included,
                ];
            })->toArray());

            if (empty($existingCostings)) {
                $existingCostings = [[
                    'currency_code' => $defaultCurrency ?? 'INR',
                    'cost_price' => '',
                    'selling_price' => '',
                    'sac_code' => '',
                    'tax_rate' => '',
                    'tax_included' => 'no',
                ]];
            }

            $existingAddons = old('addons', $service->addons->map(function($addon) use ($defaultCurrency) {
                $addonCostings = $addon->costings->map(function($costing) {
                    return [
                        'currency_code' => $costing->currency_code,
                        'cost_price' => $costing->cost_price,
                        'selling_price' => $costing->selling_price,
                        'sac_code' => $costing->sac_code,
                        'tax_rate' => $costing->tax_rate,
                        'tax_included' => $costing->tax_included,
                    ];
                })->values()->all();

                if (empty($addonCostings)) {
                    $addonCostings = [[
                        'currency_code' => $defaultCurrency ?? 'INR',
                        'cost_price' => '',
                        'selling_price' => '',
                        'sac_code' => '',
                        'tax_rate' => '',
                        'tax_included' => 'no',
                    ]];
                }

                return [
                    'name' => $addon->name,
                    'description' => $addon->description,
                    'status' => $addon->is_active ? 'active' : 'inactive',
                    'costings' => $addonCostings,
                ];
            })->toArray());

            $existingAddonsForJs = $service->addons->map(function ($addon) {
                return [
                    'addonid' => $addon->addonid,
                    'name' => $addon->name,
                    'description' => $addon->description,
                    'costings' => $addon->costings->map(function ($costing) {
                        return [
                            'currency_code' => $costing->currency_code,
                            'cost_price' => $costing->cost_price,
                            'selling_price' => $costing->selling_price,
                            'sac_code' => $costing->sac_code,
                            'tax_rate' => $costing->tax_rate,
                            'tax_included' => $costing->tax_included,
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            $currencies = $currencies ?? collect();
        @endphp

        <div class="panel-card" style="margin-top: 1rem; border: 1px dashed var(--line);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div>
                    <p class="eyebrow" style="margin: 0;">Costings</p>
                    <strong>Add pricing per currency</strong>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" class="text-link" id="add-costing-row">+ Add currency</button>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table" style="min-width: 600px;" id="costings-table">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>SAC Code</th>
                            <th>Tax Type</th>
                            <th>Tax %</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="costing-rows">
                        @foreach($existingCostings as $index => $costing)
                            <tr>
                                <td>
                                    <select name="costings[{{ $index }}][currency_code]" style="min-width: 180px;" required>
                                        <option value="">Select</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->iso }}" {{ ($costing['currency_code'] ?? '') === $currency->iso ? 'selected' : '' }}>
                                                {{ $currency->iso }} - {{ $currency->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                </td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][cost_price]" value="{{ $costing['cost_price'] ?? '' }}" required></td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][selling_price]" value="{{ $costing['selling_price'] ?? '' }}" required></td>
                                <td><input type="text" maxlength="20" name="costings[{{ $index }}][sac_code]" value="{{ $costing['sac_code'] ?? '' }}"></td>
                                <td>
                                    <select name="costings[{{ $index }}][tax_included]" style="min-width: 120px;" required>
                                        <option value="no" {{ ($costing['tax_included'] ?? 'no') === 'no' ? 'selected' : '' }}>Excl. Tax</option>
                                        <option value="yes" {{ ($costing['tax_included'] ?? 'no') === 'yes' ? 'selected' : '' }}>Incl. Tax</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" max="100" name="costings[{{ $index }}][tax_rate]" value="{{ $costing['tax_rate'] ?? '' }}"></td>
                                <td style="width: 70px; text-align: center;">
                                    <button type="button" class="icon-action-btn delete remove-costing"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" class="primary-button" id="save-service-btn" style="font-size: 0.875rem; padding: 0.4rem 0.8rem;">
                    Save Service
                </button>
            </div>
        </div>

        <div class="panel-card" style="margin-top: 1rem; border: 1px dashed var(--line);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Do you want to add add-on items for this service?</strong>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <label class="custom-radio">
                        <input type="radio" name="has_addons_toggle" value="yes" id="has-addons-yes" {{ $service->addons->count() > 0 ? 'checked' : '' }}>
                        <span class="radio-label">Yes</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="has_addons_toggle" value="no" id="has-addons-no" {{ $service->addons->count() === 0 ? 'checked' : '' }}>
                        <span class="radio-label">No</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="panel-card" id="addon-section" style="margin-top: 1rem; border: 1px dashed var(--line); {{ $service->addons->count() > 0 ? '' : 'display: none;' }}">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div>
                    <p class="eyebrow" style="margin: 0;" id="addon-section-title">Service Add-on Items for {{ $service->name }}</p>
                    <strong>Edit one item at a time using the list below</strong>
                </div>
            </div>

            <div class="panel-card" style="border:1px solid var(--line);">
                <div class="form-grid" style="margin-bottom:0.7rem;">
                    <div>
                        <label for="addon-name">Item Name *</label>
                        <input type="text" id="addon-name" maxlength="150" required>
                    </div>

                    <div style="grid-column: span 2;">
                        <label for="addon-description">Description</label>
                        <input type="text" id="addon-description">
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <strong>Item Costings</strong>
                    <button type="button" class="text-link" id="add-addon-costing-row">+ Add currency</button>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th>Currency</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th>SAC Code</th>
                                <th>Tax Type</th>
                                <th>Tax %</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="addon-costing-rows"></tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                    <button type="button" class="text-link" id="reset-addon-form">Clear</button>
                    <button type="button" class="primary-button" id="save-addon-item-btn" style="font-size: 0.875rem; padding: 0.4rem 0.8rem;">Save Item</button>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <strong>Items</strong>
                <div style="overflow-x:auto; margin-top: 0.5rem;">
                    <table class="data-table" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th style="width:70px;">#</th>
                                <th>Item Name</th>
                                <th>Selling Price</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="saved-addon-list-body">
                            <tr id="saved-addon-empty-row">
                                <td colspan="4" style="text-align:center; color:#64748b;">No items saved yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @error('addons') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.name') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings.*.currency_code') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings.*.cost_price') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings.*.selling_price') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings.*.tax_rate') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*.costings.*.tax_included') <span class="error">{{ $message }}</span> @enderror
        </div>
        <div class="form-actions">
            <a href="{{ route('services.index') }}" class="text-link">Back to services</a>
        </div>
    </form>
</section>

<script>
(function() {
    const serviceIdInput = document.getElementById('serviceid');
    const serviceNameInput = document.getElementById('name');
    const costingTableBody = document.getElementById('costing-rows');
    let costingRowIndex = costingTableBody.rows.length;

    const addonToggleYes = document.getElementById('has-addons-yes');
    const addonToggleNo = document.getElementById('has-addons-no');
    const addonSection = document.getElementById('addon-section');
    const addonSectionTitle = document.getElementById('addon-section-title');

    const addonNameInput = document.getElementById('addon-name');
    const addonDescriptionInput = document.getElementById('addon-description');
    const addonCostingRows = document.getElementById('addon-costing-rows');
    const saveAddonItemButton = document.getElementById('save-addon-item-btn');
    const savedAddonListBody = document.getElementById('saved-addon-list-body');

    let currentEditingAddonId = '';

    const currencyOptionsHtml = @json(
        collect($currencies)->map(function ($currency) {
            return '<option value="' . e($currency->iso) . '">' . e($currency->iso . ' - ' . $currency->name) . '</option>';
        })->implode('')
    );

    const existingAddonsData = @json($existingAddonsForJs);

    const addonsById = {};

    function mainCostingRowHtml(index, data = {}) {
        return `
            <tr>
                <td>
                    <select name="costings[${index}][currency_code]" style="min-width: 180px;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${index}][cost_price]" value="${data.cost_price || ''}" required></td>
                <td><input type="number" step="0.01" name="costings[${index}][selling_price]" value="${data.selling_price || ''}" required></td>
                <td><input type="text" maxlength="20" name="costings[${index}][sac_code]" value="${data.sac_code || ''}"></td>
                <td>
                    <select name="costings[${index}][tax_included]" style="min-width: 120px;" required>
                        <option value="no">Excl. Tax</option>
                        <option value="yes">Incl. Tax</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" max="100" name="costings[${index}][tax_rate]" value="${data.tax_rate || ''}"></td>
                <td style="width: 70px; text-align: center;"><button type="button" class="icon-action-btn delete remove-costing"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    }

    function addonCostingRowHtml(data = {}) {
        return `
            <tr>
                <td>
                    <select class="addon-currency" style="min-width: 180px;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" class="addon-cost-price" value="${data.cost_price || ''}" required></td>
                <td><input type="number" step="0.01" class="addon-selling-price" value="${data.selling_price || ''}" required></td>
                <td><input type="text" maxlength="20" class="addon-sac-code" value="${data.sac_code || ''}"></td>
                <td>
                    <select class="addon-tax-included" style="min-width: 120px;" required>
                        <option value="no">Excl. Tax</option>
                        <option value="yes">Incl. Tax</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" max="100" class="addon-tax-rate" value="${data.tax_rate || ''}"></td>
                <td style="width: 70px; text-align: center;"><button type="button" class="icon-action-btn delete remove-addon-costing"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    }

    function getFirstSellingPrice(costings) {
        if (!Array.isArray(costings) || costings.length === 0) return '-';
        const price = costings[0].selling_price;
        return price === null || price === undefined || price === '' ? '-' : String(price);
    }

    function appendOrUpdateAddonRow(addon) {
        const emptyRow = document.getElementById('saved-addon-empty-row');
        if (emptyRow) emptyRow.remove();

        const existingRow = savedAddonListBody.querySelector(`tr[data-addon-id="${addon.addonid}"]`);
        const sellingPriceText = getFirstSellingPrice(addon.costings);

        if (existingRow) {
            existingRow.querySelector('.item-name-cell').textContent = addon.name;
            existingRow.querySelector('.item-price-cell').textContent = sellingPriceText;
        } else {
            const tr = document.createElement('tr');
            tr.dataset.addonId = addon.addonid;
            tr.innerHTML = `
                <td class="item-seq-cell"></td>
                <td class="item-name-cell"></td>
                <td class="item-price-cell"></td>
                <td><button type="button" class="text-link edit-saved-addon">Edit</button></td>
            `;
            tr.querySelector('.item-name-cell').textContent = addon.name;
            tr.querySelector('.item-price-cell').textContent = sellingPriceText;
            savedAddonListBody.appendChild(tr);
        }

        updateAddonSequenceNumbers();
    }

    function updateAddonSequenceNumbers() {
        const rows = savedAddonListBody.querySelectorAll('tr[data-addon-id]');
        rows.forEach((row, index) => {
            const seqCell = row.querySelector('.item-seq-cell');
            if (seqCell) seqCell.textContent = String(index + 1);
        });
    }

    function resetAddonForm() {
        currentEditingAddonId = '';
        addonNameInput.value = '';
        addonDescriptionInput.value = '';
        addonCostingRows.innerHTML = '';
        addAddonCostingRow();
        saveAddonItemButton.textContent = 'Save Item';
    }

    function addAddonCostingRow(data = {}) {
        const rowWrap = document.createElement('tbody');
        rowWrap.innerHTML = addonCostingRowHtml(data);
        const row = rowWrap.firstElementChild;
        addonCostingRows.appendChild(row);
        row.querySelector('.addon-currency').value = data.currency_code || '';
        row.querySelector('.addon-tax-included').value = data.tax_included || 'no';
    }

    function fillAddonForm(addon) {
        currentEditingAddonId = addon.addonid || '';
        addonNameInput.value = addon.name || '';
        addonDescriptionInput.value = addon.description || '';
        addonCostingRows.innerHTML = '';

        const costings = Array.isArray(addon.costings) && addon.costings.length > 0
            ? addon.costings
            : [{ currency_code: '', cost_price: '', selling_price: '', sac_code: '', tax_rate: '', tax_included: 'no' }];

        costings.forEach((costing) => addAddonCostingRow(costing));
        saveAddonItemButton.textContent = currentEditingAddonId ? 'Update Item' : 'Save Item';
    }

    function collectAddonCostings() {
        const costings = [];
        let hasInvalidCosting = false;

        addonCostingRows.querySelectorAll('tr').forEach((row) => {
            const costing = {
                currency_code: row.querySelector('.addon-currency').value,
                cost_price: row.querySelector('.addon-cost-price').value,
                selling_price: row.querySelector('.addon-selling-price').value,
                sac_code: row.querySelector('.addon-sac-code').value,
                tax_included: row.querySelector('.addon-tax-included').value,
                tax_rate: row.querySelector('.addon-tax-rate').value,
            };

            if (!costing.currency_code || costing.cost_price === '' || costing.selling_price === '') {
                hasInvalidCosting = true;
            }

            costings.push(costing);
        });

        return { costings, hasInvalidCosting };
    }

    document.getElementById('add-costing-row').addEventListener('click', function() {
        const row = document.createElement('tbody');
        row.innerHTML = mainCostingRowHtml(costingRowIndex);
        costingTableBody.appendChild(row.firstElementChild);
        costingRowIndex++;
    });

    costingTableBody.addEventListener('click', function(e) {
        const removeButton = e.target.closest('.remove-costing');
        if (!removeButton) return;
        if (costingTableBody.rows.length === 1) {
            alert('At least one costing is required.');
            return;
        }
        removeButton.closest('tr').remove();
    });

    document.getElementById('add-addon-costing-row').addEventListener('click', function() {
        addAddonCostingRow();
    });

    addonCostingRows.addEventListener('click', function(e) {
        const removeButton = e.target.closest('.remove-addon-costing');
        if (!removeButton) return;
        if (addonCostingRows.querySelectorAll('tr').length === 1) {
            alert('Each add-on item needs at least one costing row.');
            return;
        }
        removeButton.closest('tr').remove();
    });

    document.getElementById('reset-addon-form').addEventListener('click', resetAddonForm);

    savedAddonListBody.addEventListener('click', function(e) {
        const editButton = e.target.closest('.edit-saved-addon');
        if (!editButton) return;

        const row = editButton.closest('tr[data-addon-id]');
        if (!row) return;

        const addonId = row.dataset.addonId;
        const addon = addonsById[addonId];
        if (addon) {
            fillAddonForm(addon);
        }
    });

    function toggleAddonSection() {
        if (addonToggleYes.checked) {
            addonSection.style.display = 'block';
        } else {
            addonSection.style.display = 'none';
        }
    }

    addonToggleYes.addEventListener('change', toggleAddonSection);
    addonToggleNo.addEventListener('change', toggleAddonSection);

    document.getElementById('save-service-btn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Saving...';

        const serviceid = serviceIdInput.value;
        const type = document.getElementById('type').value;
        const sync = document.getElementById('sync').checked ? 'yes' : 'no';
        const name = serviceNameInput.value;
        const ps_catid = document.getElementById('ps_catid').value;
        const description = document.getElementById('description').value;

        const costings = [];
        costingTableBody.querySelectorAll('tr').forEach((row) => {
            costings.push({
                currency_code: row.querySelector(`[name*="[currency_code]"]`).value,
                cost_price: row.querySelector(`[name*="[cost_price]"]`).value,
                selling_price: row.querySelector(`[name*="[selling_price]"]`).value,
                sac_code: row.querySelector(`[name*="[sac_code]"]`).value,
                tax_included: row.querySelector(`[name*="[tax_included]"]`).value,
                tax_rate: row.querySelector(`[name*="[tax_rate]"]`).value,
            });
        });

        fetch("{{ route('services.ajax-save') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                serviceid, type, sync, name, ps_catid, description, costings
            }),
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                addonSectionTitle.innerText = `Service Add-on Items for ${name}`;
                alert(data.message);
            } else {
                alert('Error: ' + (data.message || 'Something went wrong'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = originalText;
        });
    });

    saveAddonItemButton.addEventListener('click', function() {
        const btn = this;
        const serviceid = serviceIdInput.value;

        if (!serviceid) {
            alert('Service ID not found.');
            return;
        }

        const name = addonNameInput.value.trim();
        const description = addonDescriptionInput.value.trim();

        if (!name) {
            alert('Please enter item name.');
            addonNameInput.focus();
            return;
        }

        const { costings, hasInvalidCosting } = collectAddonCostings();
        if (costings.length === 0 || hasInvalidCosting) {
            alert('Please complete all required costing fields.');
            return;
        }

        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = 'Saving...';

        fetch("{{ route('services.addons.ajax-save') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                serviceid,
                addonid: currentEditingAddonId || null,
                name,
                description,
                costings
            }),
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Server error');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const addonData = {
                    addonid: data.addonid,
                    name,
                    description,
                    costings
                };

                addonsById[data.addonid] = addonData;
                appendOrUpdateAddonRow(addonData);
                resetAddonForm();
                alert(data.message);
            } else {
                alert('Error: ' + (data.message || 'Something went wrong'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = originalText;
        });
    });

    existingAddonsData.forEach((addon) => {
        addonsById[addon.addonid] = addon;
        appendOrUpdateAddonRow(addon);
    });

    resetAddonForm();
    toggleAddonSection();
})();
</script>
@endsection
