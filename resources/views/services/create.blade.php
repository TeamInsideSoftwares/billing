@extends('layouts.app')

@section('content')
<h3 id="service-page-title" style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Create New Service</h3>

<section class="section-bar">
    <div></div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.store') }}" class="client-form" id="service-form">
        @csrf
        <input type="hidden" name="serviceid" id="serviceid" value="">
        
        <div id="service-form-section">
            <div class="form-grid">
                <div>
                    <label for="type">Type *</label>
                    <select id="type" name="type" required>
                        <option value="product" {{ old('type') == 'product' ? 'selected' : '' }}>Product</option>
                        <option value="service" {{ old('type', 'service') == 'service' ? 'selected' : '' }}>Service</option>
                    </select>
                    @error('type') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div style="display:flex; align-items: center; gap: 0.5rem;">
                    <label class="custom-checkbox" style="display: flex; align-items: center; cursor: pointer;">
                        <input type="hidden" name="sync" value="no">
                        <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync') == 'yes' ? 'checked' : '' }}>
                        <span style="margin-left: 0.5rem;">Sync</span>
                    </label>
                    @error('sync') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="name">Service Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="ps_catid">Category</label>
                    <select id="ps_catid" name="ps_catid">
                        <option value="">-- No Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->ps_catid }}" {{ old('ps_catid') == $category->ps_catid ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('ps_catid') <span class="error">{{ $message }}</span> @enderror
                </div>

                <div style="grid-column: span 2;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
            </div>

            @php
                $existingCostings = old('costings', [[
                    'currency_code' => $defaultCurrency ?? 'INR',
                    'cost_price' => '',
                    'selling_price' => '',
                    'sac_code' => '',
                    'tax_rate' => '',
                    'tax_included' => 'no',
                ]]);

                $existingAddons = old('addons', []);
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
                    <button type="button" class="primary-button" id="save-service-btn" style="font-size: 0.875rem; padding: 0.4rem 0.8rem;">Save Service</button>
                </div>
            </div>
        </div>

        <h4 id="saved-service-heading" style="margin: 1rem 0 0 0; display: none; color: #1f2937;"></h4>

        <div class="panel-card" id="addon-question-panel" style="margin-top: 1rem; border: 1px dashed var(--line); display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong>Do you want to add add-on items for this service?</strong>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <label class="custom-radio">
                        <input type="radio" name="has_addons_toggle" value="yes" id="has-addons-yes">
                        <span class="radio-label">Yes</span>
                    </label>
                    <label class="custom-radio">
                        <input type="radio" name="has_addons_toggle" value="no" id="has-addons-no" checked>
                        <span class="radio-label">No</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="panel-card" id="addon-section" style="margin-top: 1rem; border: 1px dashed var(--line); display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div>
                    <p class="eyebrow" style="margin: 0;" id="addon-section-title">Service Add-on Items</p>
                    <strong id="service-name-heading">Add item details and save to list below</strong>
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
                    <button type="button" class="text-link" id="reset-addon-form">Clear Form</button>
                    <button type="button" class="primary-button" id="save-addon-item-btn" style="font-size: 0.875rem; padding: 0.4rem 0.8rem;">Save Item</button>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <strong>Saved Add-on Items</strong>
                <div style="overflow-x:auto; margin-top: 0.5rem;">
                    <table class="data-table" style="min-width: 700px;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">#</th>
                                <th>Item Name</th>
                                <th>Selling Price</th>
                            </tr>
                        </thead>
                        <tbody id="saved-addon-list-body">
                            <tr id="saved-addon-empty-row">
                                <td colspan="3" style="text-align:center; color:#64748b;">No items saved yet.</td>
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
    const servicePageTitle = document.getElementById('service-page-title');

    const costingTableBody = document.getElementById('costing-rows');
    let costingRowIndex = costingTableBody.rows.length;

    const addonQuestionPanel = document.getElementById('addon-question-panel');
    const addonToggleYes = document.getElementById('has-addons-yes');
    const addonToggleNo = document.getElementById('has-addons-no');
    const addonSection = document.getElementById('addon-section');
    const addonSectionTitle = document.getElementById('addon-section-title');
    const serviceNameHeading = document.getElementById('service-name-heading');
    const serviceFormSection = document.getElementById('service-form-section');
    const savedServiceHeading = document.getElementById('saved-service-heading');

    const addonNameInput = document.getElementById('addon-name');
    const addonDescriptionInput = document.getElementById('addon-description');
    const addonCostingRows = document.getElementById('addon-costing-rows');
    const savedAddonListBody = document.getElementById('saved-addon-list-body');

    const existingAddons = @json(collect($existingAddons)->values());

    const currencyOptionsHtml = @json(
        collect($currencies)->map(function ($currency) {
            return '<option value="' . e($currency->iso) . '">' . e($currency->iso . ' - ' . $currency->name) . '</option>';
        })->implode('')
    );

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

    function addMainCostingRow() {
        const row = document.createElement('tbody');
        row.innerHTML = mainCostingRowHtml(costingRowIndex);
        costingTableBody.appendChild(row.firstElementChild);
        costingRowIndex++;
    }

    function addAddonCostingRow(data = {}) {
        const rowWrap = document.createElement('tbody');
        rowWrap.innerHTML = addonCostingRowHtml(data);
        const row = rowWrap.firstElementChild;

        addonCostingRows.appendChild(row);
        row.querySelector('.addon-currency').value = data.currency_code || '';
        row.querySelector('.addon-tax-included').value = data.tax_included || 'no';
    }

    function setSavedServiceHeading(serviceName) {
        if (!serviceName) {
            servicePageTitle.innerText = 'Create New Service';
            addonSectionTitle.innerText = 'Service Add-on Items';
            serviceNameHeading.innerText = 'Add item details and save to list below';
            return;
        }

        servicePageTitle.innerText = `Service Saved: ${serviceName}`;
        addonSectionTitle.innerText = `Service Add-on Items for ${serviceName}`;
        serviceNameHeading.innerText = `Add-on items for ${serviceName}`;
        savedServiceHeading.innerText = serviceName;
    }

    function toggleServiceFormSection() {
        const serviceSaved = Boolean(serviceIdInput.value);
        serviceFormSection.style.display = serviceSaved ? 'none' : 'block';
        savedServiceHeading.style.display = serviceSaved ? 'block' : 'none';

        if (serviceSaved) {
            savedServiceHeading.innerText = serviceNameInput.value.trim();
        }
    }

    function toggleAddonSection() {
        const serviceSaved = Boolean(serviceIdInput.value);
        addonQuestionPanel.style.display = serviceSaved ? 'block' : 'none';

        if (!serviceSaved) {
            addonSection.style.display = 'none';
            return;
        }

        addonSection.style.display = addonToggleYes.checked ? 'block' : 'none';
    }

    function resetAddonForm() {
        addonNameInput.value = '';
        addonDescriptionInput.value = '';
        addonCostingRows.innerHTML = '';
        addAddonCostingRow();
    }

    function appendSavedAddon(addonName, costings) {
        const emptyRow = document.getElementById('saved-addon-empty-row');
        if (emptyRow) {
            emptyRow.remove();
        }

        const tr = document.createElement('tr');
        const sequence = savedAddonListBody.querySelectorAll('tr').length + 1;

        const tdSequence = document.createElement('td');
        tdSequence.textContent = String(sequence);

        const tdName = document.createElement('td');
        tdName.textContent = addonName || '-';

        const tdSellingPrice = document.createElement('td');
        
        // Create currency badges for all costings
        if (Array.isArray(costings) && costings.length > 0) {
            costings.forEach((costing, index) => {
                if (index > 0) {
                    tdSellingPrice.appendChild(document.createTextNode(' '));
                }
                
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.style.cssText = 'display: inline-block; padding: 0.25rem 0.5rem; background: #e0e7ff; color: #4338ca; border-radius: 0.25rem; font-size: 0.75rem; margin-right: 0.25rem;';
                badge.textContent = `${costing.currency_code} ${costing.selling_price}`;
                tdSellingPrice.appendChild(badge);
            });
        } else {
            tdSellingPrice.textContent = '-';
        }

        tr.appendChild(tdSequence);
        tr.appendChild(tdName);
        tr.appendChild(tdSellingPrice);
        savedAddonListBody.appendChild(tr);
    }

    document.getElementById('add-costing-row').addEventListener('click', addMainCostingRow);

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
                serviceIdInput.value = data.serviceid;
                setSavedServiceHeading(name.trim());
                toggleServiceFormSection();
                toggleAddonSection();
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

    document.getElementById('save-addon-item-btn').addEventListener('click', function() {
        const btn = this;
        const serviceid = serviceIdInput.value;

        if (!serviceid) {
            alert('Please save the main service details first.');
            return;
        }

        const addonName = addonNameInput.value.trim();
        const addonDescription = addonDescriptionInput.value.trim();

        if (!addonName) {
            alert('Please enter item name.');
            addonNameInput.focus();
            return;
        }

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
                name: addonName,
                description: addonDescription,
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
                appendSavedAddon(addonName, costings);
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

    if (existingAddons.length > 0) {
        existingAddons.forEach((addon) => {
            appendSavedAddon(addon.name, addon.costings || []);
        });
    }

    resetAddonForm();
    setSavedServiceHeading(serviceIdInput.value ? serviceNameInput.value.trim() : '');
    toggleServiceFormSection();
    toggleAddonSection();
})();
</script>
@endsection
