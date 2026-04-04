@extends('layouts.app')

@section('content')
<section class="section-bar">
    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Edit {{ $service->name }}</h3>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to items</a>
</section>

<section class="panel-card" style="padding: 1.25rem;">
    <form method="POST" action="{{ route('services.update', $service) }}" class="service-form" id="item-form">
        @method('PUT')
        @csrf

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem;">
            <div>
                <label for="type" style="font-size: 0.82rem;">Type *</label>
                <select id="type" name="type" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                    <option value="product" {{ old('type', $service->type ?? 'service') == 'product' ? 'selected' : '' }}>Product</option>
                    <option value="service" {{ old('type', $service->type ?? 'service') == 'service' ? 'selected' : '' }}>Service</option>
                </select>
                @error('type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="sync" style="font-size: 0.82rem;">Sync</label>
                <label class="custom-checkbox" style="display: flex; align-items: center; margin-top: 0.25rem; cursor: pointer;">
                    <input type="hidden" name="sync" value="no">
                    <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync', $service->sync ?? 'no') == 'yes' ? 'checked' : '' }}>
                </label>
                @error('sync') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="ps_catid" style="font-size: 0.82rem;">Category</label>
                <select id="ps_catid" name="ps_catid" style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
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
                <label for="name" style="font-size: 0.82rem;">Item Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="description" style="font-size: 0.82rem;">Description</label>
                <textarea id="description" name="description" rows="2" style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">{{ old('description', $service->description) }}</textarea>
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

            $selectedAddonIds = collect(old('addons', $service->addons ?? []))->values()->all();
        @endphp

        <div style="border-top: 1px solid var(--line); padding-top: 1rem; margin-top: 1rem;">
            <div style="position: relative; max-width: 480px;" id="addons-dropdown-wrap">
                <span class="checkbox-label" style="font-size: 0.85rem; font-weight: 500;">This item belongs under which parent item(s)?</span>
                <button type="button" class="secondary-button" id="addons-toggle" style="width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; font-size: 0.82rem;">
                    <span id="addons-selected-label">Select parent items</span>
                    <span aria-hidden="true">▾</span>
                </button>
                <div id="addons-dropdown" style="display: none; position: absolute; left: 0; right: 0; top: calc(100% + 0.35rem); background: #fff; border: 1px solid var(--line); border-radius: 0.5rem; padding: 0.65rem; max-height: 260px; overflow: auto; z-index: 20;">
                    @forelse($availableAddonItems as $item)
                        <label class="custom-checkbox" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; font-size: 0.85rem;">
                            <input type="checkbox" value="{{ $item->itemid }}" data-item-name="{{ $item->name }}" class="addon-checkbox" {{ in_array($item->itemid, $selectedAddonIds, true) ? 'checked' : '' }}>
                            <span class="checkbox-label">{{ $item->name }}</span>
                            <span style="color: #64748b; font-size: 0.75rem;">({{ ucfirst($item->type ?? 'service') }})</span>
                        </label>
                    @empty
                        <p style="margin: 0; color: #64748b; font-size: 0.82rem;">No existing items available.</p>
                    @endforelse
                </div>
            </div>
            <div id="addons-hidden-inputs"></div>
            <div id="saved-addons-list" style="margin-top: 0.35rem; display: flex; flex-wrap: wrap; gap: 0.35rem;"></div>
            @error('addons') <span class="error">{{ $message }}</span> @enderror
            @error('addons.*') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div style="border-top: 1px solid var(--line); padding-top: 1rem; margin-top: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <div>
                    <p class="eyebrow" style="margin: 0; font-size: 0.75rem;">Item Costings</p>
                    <strong style="font-size: 0.875rem;">Add pricing per currency</strong>
                </div>
                <button type="button" class="text-link" id="add-costing-row" style="font-size: 0.82rem;">+ Add currency</button>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table" style="min-width: 600px; font-size: 0.82rem;" id="costings-table">
                    <thead>
                        <tr>
                            <th>Currency *</th>
                            <th>Cost Price *</th>
                            <th>Selling Price *</th>
                            <th>SAC Code</th>
                            <th>Tax Type *</th>
                            <th>Tax %</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="costing-rows">
                        @foreach($existingCostings as $index => $costing)
                            <tr>
                                <td>
                                    <select name="costings[{{ $index }}][currency_code]" style="min-width: 150px; padding: 0.3rem;" required>
                                        <option value="">Select</option>
                                        @foreach($currencies as $currency)
                                            <option value="{{ $currency->iso }}" {{ ($costing['currency_code'] ?? '') === $currency->iso ? 'selected' : '' }}>
                                                {{ $currency->iso }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][cost_price]" value="{{ $costing['cost_price'] ?? '' }}" required style="padding: 0.3rem; width: 100px;"></td>
                                <td><input type="number" step="0.01" name="costings[{{ $index }}][selling_price]" value="{{ $costing['selling_price'] ?? '' }}" required style="padding: 0.3rem; width: 100px;"></td>
                                <td><input type="text" maxlength="20" name="costings[{{ $index }}][sac_code]" value="{{ $costing['sac_code'] ?? '' }}" style="padding: 0.3rem; width: 80px;"></td>
                                <td>
                                    <select name="costings[{{ $index }}][tax_included]" style="min-width: 100px; padding: 0.3rem;" required>
                                        <option value="no" {{ ($costing['tax_included'] ?? 'no') === 'no' ? 'selected' : '' }}>Excl.</option>
                                        <option value="yes" {{ ($costing['tax_included'] ?? 'no') === 'yes' ? 'selected' : '' }}>Incl.</option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" max="100" name="costings[{{ $index }}][tax_rate]" value="{{ $costing['tax_rate'] ?? '' }}" style="padding: 0.3rem; width: 70px;"></td>
                                <td style="width: 60px; text-align: center;">
                                    <button type="button" class="icon-action-btn delete remove-costing" style="padding: 0.25rem;"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1rem;">
            <button type="submit" class="primary-button" style="padding: 0.4rem 1rem; font-size: 0.875rem;">Update Item</button>
            <!-- <a href="{{ route('services.index') }}" class="text-link">Back to items</a> -->
        </div>
    </form>
</section>

<script>
(function() {
    const costingTableBody = document.getElementById('costing-rows');
    let costingRowIndex = costingTableBody.rows.length;

    const currencyOptionsHtml = @json(
        collect($currencies)->map(function ($currency) {
            return '<option value="' . e($currency->iso) . '">' . e($currency->iso . ' - ' . $currency->name) . '</option>';
        })->implode('')
    );

    function costingRowHtml(index) {
        return `
            <tr>
                <td>
                    <select name="costings[${index}][currency_code]" style="min-width: 150px; padding: 0.3rem;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${index}][cost_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="number" step="0.01" name="costings[${index}][selling_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="text" maxlength="20" name="costings[${index}][sac_code]" style="padding: 0.3rem; width: 80px;"></td>
                <td>
                    <select name="costings[${index}][tax_included]" style="min-width: 100px; padding: 0.3rem;" required>
                        <option value="no" selected>Excl.</option>
                        <option value="yes">Incl.</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" max="100" name="costings[${index}][tax_rate]" style="padding: 0.3rem; width: 70px;"></td>
                <td style="width: 60px; text-align: center;">
                    <button type="button" class="icon-action-btn delete remove-costing" style="padding: 0.25rem;"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    }

    document.getElementById('add-costing-row').addEventListener('click', function() {
        const row = document.createElement('tr');
        row.innerHTML = costingRowHtml(costingRowIndex).trim();
        const select = row.querySelector('select[name^="costings"][name$="[currency_code]"]');
        if (select) select.value = '{{ $defaultCurrency ?? "INR" }}';
        costingTableBody.appendChild(row);
        costingRowIndex++;
    });

    costingTableBody.addEventListener('click', function(e) {
        const removeButton = e.target.closest('.remove-costing');
        if (!removeButton) return;
        if (costingTableBody.querySelectorAll('tr').length === 1) {
            alert('At least one costing row is required.');
            return;
        }
        removeButton.closest('tr').remove();
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
            empty.style.color = '#64748b';
            empty.style.fontSize = '0.78rem';
            empty.textContent = 'No parent items selected yet.';
            savedAddonsList.appendChild(empty);
            return;
        }

        savedAddons.forEach((name, id) => {
            const pill = document.createElement('span');
            pill.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.45rem; background: #f3f4f6; color: #374151; border-radius: 0.25rem; font-size: 0.75rem;';
            pill.innerHTML = `${name} <button type="button" data-remove-addon-id="${id}" style="border:none;background:transparent;color:#6b7280;cursor:pointer;font-size:0.82rem;line-height:1;">x</button>`;
            savedAddonsList.appendChild(pill);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'addons[]';
            hidden.value = id;
            hiddenInputsWrap.appendChild(hidden);
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
        if (checkbox) checkbox.checked = false;
        renderSavedAddons();
        refreshAddonLabel();
    });

    initialSavedAddonIds.forEach((id) => {
        const checkbox = dropdown.querySelector(`.addon-checkbox[value="${id}"]`);
        if (checkbox) {
            savedAddons.set(id, checkbox.dataset.itemName || id);
        }
    });

    refreshAddonLabel();
    renderSavedAddons();
})();
</script>
@endsection
