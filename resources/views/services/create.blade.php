@extends('layouts.app')

@section('content')
<h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Create New Service</h3>

<section class="section-bar">
    <div></div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.store') }}" class="client-form" id="service-form">
        @csrf
        <input type="hidden" name="serviceid" id="serviceid" value="">
        <div class="form-grid">
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
                                    <button type="button" class="text-link danger remove-costing">Remove</button>
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

        <div class="panel-card" style="margin-top: 1rem; border: 1px dashed var(--line);">
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
                    <strong>Add item and then add one or more costing rows under it</strong>
                </div>
                <button type="button" class="text-link" id="add-addon-card">+ Add item</button>
            </div>

            <div id="addon-cards" style="display:flex; flex-direction:column; gap:0.9rem;">
                @foreach($existingAddons as $addonIndex => $addon)
                    @php
                        $addonCostings = collect($addon['costings'] ?? [])->values()->all();
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
                    @endphp

                    <div class="addon-card panel-card" style="border:1px solid var(--line);" data-addon-index="{{ $addonIndex }}" data-next-costing-index="{{ count($addonCostings) }}">
                        <div class="form-grid" style="margin-bottom:0.7rem;">
                            <div>
                                <label>Item Name *</label>
                                <input type="text" name="addons[{{ $addonIndex }}][name]" value="{{ $addon['name'] ?? '' }}" maxlength="150" required>
                            </div>

                            <div style="grid-column: span 2;">
                                <label>Description</label>
                                <input type="text" name="addons[{{ $addonIndex }}][description]" value="{{ $addon['description'] ?? '' }}">
                            </div>
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                            <strong>Item Costings</strong>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <button type="button" class="text-link add-addon-costing">+ Add currency</button>
                                <!-- Divider -->
                                <span style="opacity:0.4;">|</span>
                                <button type="button" class="text-link danger remove-addon-card"> ✕ Remove item</button>
                            </div>
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
                                <tbody class="addon-costing-rows">
                                    @foreach($addonCostings as $costingIndex => $addonCosting)
                                        <tr>
                                            <td>
                                                <select name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][currency_code]" style="min-width: 180px;" required>
                                                    <option value="">Select</option>
                                                    @foreach($currencies as $currency)
                                                        <option value="{{ $currency->iso }}" {{ ($addonCosting['currency_code'] ?? '') === $currency->iso ? 'selected' : '' }}>
                                                            {{ $currency->iso }} - {{ $currency->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][cost_price]" value="{{ $addonCosting['cost_price'] ?? '' }}" required></td>
                                            <td><input type="number" step="0.01" name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][selling_price]" value="{{ $addonCosting['selling_price'] ?? '' }}" required></td>
                                            <td><input type="text" maxlength="20" name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][sac_code]" value="{{ $addonCosting['sac_code'] ?? '' }}"></td>
                                            <td>
                                                <select name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][tax_included]" style="min-width: 120px;" required>
                                                    <option value="no" {{ ($addonCosting['tax_included'] ?? 'no') === 'no' ? 'selected' : '' }}>Excl. Tax</option>
                                                    <option value="yes" {{ ($addonCosting['tax_included'] ?? 'no') === 'yes' ? 'selected' : '' }}>Incl. Tax</option>
                                                </select>
                                            </td>
                                            <td><input type="number" step="0.01" min="0" max="100" name="addons[{{ $addonIndex }}][costings][{{ $costingIndex }}][tax_rate]" value="{{ $addonCosting['tax_rate'] ?? '' }}"></td>
                                            <td style="width: 70px; text-align: center;"><button type="button" class="text-link danger remove-addon-costing">Remove</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
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
            <button type="submit" class="primary-button">Create Service</button>
            <a href="{{ route('services.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>

<script>
(function() {
    const costingTableBody = document.getElementById('costing-rows');
    let costingRowIndex = costingTableBody.rows.length;

    const addonCards = document.getElementById('addon-cards');
    const existingAddonIndexes = Array.from(addonCards.querySelectorAll('.addon-card'))
        .map((card) => parseInt(card.dataset.addonIndex || '0', 10));
    let addonIndex = existingAddonIndexes.length ? Math.max(...existingAddonIndexes) + 1 : 0;

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
                <td style="width: 70px; text-align: center;"><button type="button" class="text-link danger remove-costing">Remove</button></td>
            </tr>
        `;
    }

    function addonCostingRowHtml(aIndex, cIndex, data = {}) {
        return `
            <tr>
                <td>
                    <select name="addons[${aIndex}][costings][${cIndex}][currency_code]" style="min-width: 180px;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="addons[${aIndex}][costings][${cIndex}][cost_price]" value="${data.cost_price || ''}" required></td>
                <td><input type="number" step="0.01" name="addons[${aIndex}][costings][${cIndex}][selling_price]" value="${data.selling_price || ''}" required></td>
                <td><input type="text" maxlength="20" name="addons[${aIndex}][costings][${cIndex}][sac_code]" value="${data.sac_code || ''}"></td>
                <td>
                    <select name="addons[${aIndex}][costings][${cIndex}][tax_included]" style="min-width: 120px;" required>
                        <option value="no">Excl. Tax</option>
                        <option value="yes">Incl. Tax</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" max="100" name="addons[${aIndex}][costings][${cIndex}][tax_rate]" value="${data.tax_rate || ''}"></td>
                <td style="width: 70px; text-align: center;"><button type="button" class="text-link danger remove-addon-costing">Remove</button></td>
            </tr>
        `;
    }

    function addonCardHtml(aIndex, firstCostingIndex = 0) {
        return `
            <div class="addon-card panel-card" style="border:1px solid var(--line);" data-addon-index="${aIndex}" data-next-costing-index="${firstCostingIndex + 1}" data-addon-id="">
                <div class="form-grid" style="margin-bottom:0.7rem;">
                    <div>
                        <label>Item Name *</label>
                        <input type="text" name="addons[${aIndex}][name]" maxlength="150" required>
                    </div>

                    <div style="grid-column: span 2;">
                        <label>Description</label>
                        <input type="text" name="addons[${aIndex}][description]">
                    </div>
                </div>

                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                    <strong>Item Costings</strong>
                   <div style="display:flex; align-items:center; gap:1rem;">
                        <!-- Add actions -->
                        <button type="button" class="text-link add-addon-costing">
                            + Add currency
                        </button>
                        <!-- Divider -->
                        <span style="opacity:0.4;">|</span>
                        <!-- Remove action -->
                        <button type="button" class="text-link danger remove-addon-card">
                            ✕ Remove item
                        </button>
                    </div>
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
                        <tbody class="addon-costing-rows">
                            ${addonCostingRowHtml(aIndex, firstCostingIndex)}
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" class="primary-button save-addon-btn" style="font-size: 0.875rem; padding: 0.4rem 0.8rem;">Save Item</button>
                </div>
            </div>
        `;
    }

    document.getElementById('add-costing-row').addEventListener('click', function() {
        const row = document.createElement('tbody');
        row.innerHTML = mainCostingRowHtml(costingRowIndex);
        costingTableBody.appendChild(row.firstElementChild);
        costingRowIndex++;
    });

    costingTableBody.addEventListener('click', function(e) {
        if (!e.target.classList.contains('remove-costing')) return;
        if (costingTableBody.rows.length === 1) {
            alert('At least one costing is required.');
            return;
        }
        e.target.closest('tr').remove();
    });

    document.getElementById('add-addon-card').addEventListener('click', function() {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = addonCardHtml(addonIndex);
        addonCards.appendChild(wrapper.firstElementChild);
        addonIndex++;
    });

    addonCards.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-addon-card')) {
            e.target.closest('.addon-card').remove();
            return;
        }

        if (e.target.classList.contains('add-addon-costing')) {
            const card = e.target.closest('.addon-card');
            const aIndex = card.dataset.addonIndex;
            let cIndex = parseInt(card.dataset.nextCostingIndex || '0', 10);
            const tbody = card.querySelector('.addon-costing-rows');

            const rowWrap = document.createElement('tbody');
            rowWrap.innerHTML = addonCostingRowHtml(aIndex, cIndex);
            tbody.appendChild(rowWrap.firstElementChild);

            card.dataset.nextCostingIndex = String(cIndex + 1);
            return;
        }

        if (e.target.classList.contains('remove-addon-costing')) {
            const card = e.target.closest('.addon-card');
            const tbody = card.querySelector('.addon-costing-rows');
            if (tbody.querySelectorAll('tr').length === 1) {
                alert('Each add-on item needs at least one costing row.');
                return;
            }
            e.target.closest('tr').remove();
        }
    });

    const addonToggleYes = document.getElementById('has-addons-yes');
    const addonToggleNo = document.getElementById('has-addons-no');
    const addonSection = document.getElementById('addon-section');
    const addonSectionTitle = document.getElementById('addon-section-title');

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

        const serviceid = document.getElementById('serviceid').value;
        const name = document.getElementById('name').value;
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
                serviceid, name, ps_catid, description, costings
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('serviceid').value = data.serviceid;
                addonSectionTitle.innerText = `Service Add-on Items for ${name}`;
                alert(data.message);
            } else {
                alert('Error: ' + (data.message || 'Something went wrong'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save service.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = originalText;
        });
    });

    addonCards.addEventListener('click', function(e) {
        if (e.target.classList.contains('save-addon-btn')) {
            const btn = e.target;
            const card = btn.closest('.addon-card');
            const serviceid = document.getElementById('serviceid').value;

            if (!serviceid) {
                alert('Please save the main service details first.');
                return;
            }

            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Saving...';

            const addonid = card.dataset.addonId;
            const name = card.querySelector(`input[name*="[name]"]`).value;
            const description = card.querySelector(`input[name*="[description]"]`).value;

            const costings = [];
            card.querySelectorAll('.addon-costing-rows tr').forEach(row => {
                costings.push({
                    currency_code: row.querySelector(`[name*="[currency_code]"]`).value,
                    cost_price: row.querySelector(`[name*="[cost_price]"]`).value,
                    selling_price: row.querySelector(`[name*="[selling_price]"]`).value,
                    sac_code: row.querySelector(`[name*="[sac_code]"]`).value,
                    tax_included: row.querySelector(`[name*="[tax_included]"]`).value,
                    tax_rate: row.querySelector(`[name*="[tax_rate]"]`).value,
                });
            });

            fetch("{{ route('services.addons.ajax-save') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    serviceid, addonid, name, description, costings
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    card.dataset.addonId = data.addonid;
                    alert(data.message);
                } else {
                    alert('Error: ' + (data.message || 'Something went wrong'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to save item.');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            });
        }
    });
})();
</script>
@endsection
