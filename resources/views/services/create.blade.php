@extends('layouts.app')

@section('content')
{{-- Toast Container --}}
<div id="toast-container" class="toast-container"></div>

<section class="section-bar">
    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Create New Item</h3>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to items</a>
</section>

<section class="panel-card" style="padding: 1.25rem;">
    <form method="POST" action="{{ route('services.store') }}" class="service-form" id="item-form">
        @csrf

        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1rem;">
            <div>
                <label for="type" style="font-size: 0.82rem;">Type *</label>
                <select id="type" name="type" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                    <option value="product" {{ old('type') == 'product' ? 'selected' : '' }}>Product</option>
                    <option value="service" {{ old('type', 'service') == 'service' ? 'selected' : '' }}>Service</option>
                </select>
                @error('type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="sync" style="font-size: 0.82rem;">Sync</label>
                <label class="custom-checkbox" style="display: flex; align-items: center; margin-top: 0.25rem; cursor: pointer;">
                    <input type="hidden" name="sync" value="no">
                    <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync') == 'yes' ? 'checked' : '' }}>
                </label>
                @error('sync') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="ps_catid" style="font-size: 0.82rem;">Category</label>
                <select id="ps_catid" name="ps_catid" style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
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
                <label for="name" style="font-size: 0.82rem;">Item Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: span 2;">
                <label for="description" style="font-size: 0.82rem;">Description</label>
                <textarea id="description" name="description" rows="2" style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">{{ old('description') }}</textarea>
                @error('description') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        @php
            $existingCostings = collect(old('costings', [[
                'currency_code' => $defaultCurrency ?? 'INR',
                'cost_price' => '',
                'selling_price' => '',
                'sac_code' => '',
                'tax_rate' => '',
                'tax_included' => 'no',
            ]]))->values()->all();
            $selectedAddonIds = collect(old('addons', []))->values()->all();
        @endphp

        <div style="border-top: 1px solid var(--line); padding-top: 1rem; margin-top: 1rem;">
            <div style="position: relative; max-width: 480px;" id="addons-dropdown-wrap">
                <span class="checkbox-label" style="font-size: 0.85rem; font-weight: 500;">This item belongs under which parent item(s)?</span>
                <button type="button" class="secondary-button" id="addons-toggle" style="width: 100%; text-align: left; display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.6rem; font-size: 0.82rem;">
                    <span id="addons-selected-label">Select parent items</span>
                    <span aria-hidden="true">&#9662;</span>
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
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <button type="button" class="text-link" id="add-costing-row" style="font-size: 0.82rem;">+ Add currency</button>
                    @if($account->allow_multi_taxation)
                    <a href="#" id="open-tax-modal" style="font-size:13px;" class="text-link">+ Add Tax</a>
                    @endif
                </div>
            </div>

            <p id="costings-empty-state" style="margin: 0 0 0.45rem 0; color: #64748b; font-size: 0.8rem; display:none;">
                No pricing rows yet. Click + Add currency.
            </p>

            <div id="costings-table-wrap" style="overflow-x: auto;">
                <table class="data-table" style="min-width: 640px; font-size: 0.82rem;" id="costings-table">
                    <thead>
                        <tr>
                            <th>Currency *</th>
                            <th>Cost Price *</th>
                            <th>Selling Price *</th>
                            <th>SAC Code</th>
                            <th>Tax</th>
                            <th>Tax Incl. *</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="costing-rows">
                        @foreach($existingCostings as $index => $costing)
                            <tr>
                                <td>
                                    <select name="costings[{{ $index }}][currency_code]" style="min-width: 100px; padding: 0.3rem;" required>
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
                                    @if($account->allow_multi_taxation)
                                    <select name="costings[{{ $index }}][taxid]" class="tax-select" style="min-width: 140px; padding: 0.3rem; font-size: 0.82rem;">
                                        <option value="">— None</option>
                                        @foreach(['GST','VAT'] as $taxType)
                                            @php $typeTaxes = $taxes->where('type', $taxType); @endphp
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
                                    <span style="min-width: 140px; padding: 0.3rem 0.5rem; font-size: 0.82rem; background: #f1f5f9; border-radius: 4px; color: #64748b; display: inline-block;">
                                        {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    <select name="costings[{{ $index }}][tax_included]" style="min-width: 80px; padding: 0.3rem;" required>
                                        <option value="no" {{ ($costing['tax_included'] ?? 'no') === 'no' ? 'selected' : '' }}>Excl.</option>
                                        <option value="yes" {{ ($costing['tax_included'] ?? 'no') === 'yes' ? 'selected' : '' }}>Incl.</option>
                                    </select>
                                </td>
                                <td style="width: 60px; text-align: center;">
                                    <button type="button" class="icon-action-btn delete remove-costing" style="padding: 0.25rem;"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>

        <div id="saved-items-panel" style="border-top: 1px solid var(--line); padding-top: 1rem; margin-top: 1rem; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <strong style="font-size: 0.85rem;">Saved Items</strong>
                <span id="saved-items-count" style="color: #64748b; font-size: 0.78rem;">0 saved</span>
            </div>
            <div id="saved-items-list" style="margin-top: 0.45rem; display: flex; flex-direction: column; gap: 0.35rem;"></div>
        </div>

        {{-- Add Tax Modal --}}
        @if($account->allow_multi_taxation)
        <div class="modal fade" id="addTaxModal" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
                <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                        <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                            <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="padding: 1.25rem;">
                        <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form">
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
        @endif

        <div class="form-actions" style="margin-top: 1rem;">
            <button type="button" id="save-item-stay-btn" class="primary-button" style="padding: 0.4rem 1rem; font-size: 0.875rem;">Save Item</button>
        </div>
    </form>
</section>

<script>
// Toast notification function
function showToast(type, message) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    toast.innerHTML = `<i class="fas ${icon} toast-icon"></i><span>${message}</span>`;
    
    container.appendChild(toast);
    
    // Auto-dismiss after 3.5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('toast-leaving');
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
    const fixedTaxRate = @json($fixedTaxRate);
    const taxGroups = @json($taxGroupsData);

    function _esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    let taxOptionsHtml = '<option value="">— None</option>';
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
            return `<select name="costings[${i}][taxid]" class="tax-select" style="min-width:140px;padding:0.3rem;font-size:0.82rem;">${taxOptionsHtml}</select>`;
        } else {
            return `<input type="hidden" name="costings[${i}][taxid]" value=""><span style="min-width:140px;padding:0.3rem 0.5rem;font-size:0.82rem;background:#f1f5f9;border-radius:4px;color:#64748b;display:inline-block;">Fixed: ${fixedTaxRate.toFixed(2)}%</span>`;
        }
    }
    function taxIncludeHtml(i) {
        return `<select name="costings[${i}][tax_included]" style="min-width:80px;padding:0.3rem;" required><option value="no" selected>Excl.</option><option value="yes">Incl.</option></select>`;
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
                    <select name="costings[${i}][currency_code]" style="min-width: 100px; padding: 0.3rem;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${i}][cost_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="number" step="0.01" name="costings[${i}][selling_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="text" maxlength="20" name="costings[${i}][sac_code]" style="padding: 0.3rem; width: 80px;"></td>
                <td>${taxSelectHtml(i)}</td>
                <td>${taxIncludeHtml(i)}</td>
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

    function collectCostingsFromRows() {
        const rows = Array.from(costingTableBody.querySelectorAll('tr'));
        const costings = [];

        rows.forEach((row) => {
            const currency = row.querySelector('select[name*="[currency_code]"]')?.value || '';
            const costPrice = row.querySelector('input[name*="[cost_price]"]')?.value || '';
            const sellingPrice = row.querySelector('input[name*="[selling_price]"]')?.value || '';
            const sacCode = row.querySelector('input[name*="[sac_code]"]')?.value || '';
            const taxIncluded = row.querySelector('select[name*="[tax_included]"]')?.value || 'no';
            const taxId = row.querySelector('select[name*="[taxid]"]')?.value || '';

            if (currency || costPrice || sellingPrice || sacCode || taxId) {
                costings.push({
                    currency_code: currency,
                    cost_price: costPrice,
                    selling_price: sellingPrice,
                    sac_code: sacCode,
                    taxid: taxId || null,
                    tax_included: taxIncluded
                });
            }
        });

        return costings;
    }

    function renderSavedItemRow(item) {
        savedItemsPanel.style.display = 'block';
        const editUrl = editUrlTemplate.replace('__ITEMID__', item.itemid);
        const costings = item.costings || [];
        const validCostings = costings.filter((c) => (c.currency_code || '').trim() !== '');
        
        // Get parent items for this item
        const parentItemNames = Array.from(savedAddons.values());

        let costingsHtml;
        if (validCostings.length === 0) {
            costingsHtml = '<span style="color:#64748b;font-size:0.75rem;">No costings</span>';
        } else {
            costingsHtml = validCostings.map((c) => {
                const price = c.selling_price ? Number(c.selling_price).toLocaleString(undefined, {minimumFractionDigits: 0, maximumFractionDigits: 0}) : '—';
                const tax = c.tax_rate ? c.tax_rate + '%' : '';
                const type = c.tax_included === 'yes' ? '✓' : '';
                return `<span style="display:inline-block;padding:0.2rem 0.5rem;background:#f1f5f9;color:#475569;border-radius:0.25rem;font-size:0.75rem;">${c.currency_code} ${price}${tax ? ' | Tax: ' + tax : ''}${type ? ' ' + type : ''}</span>`;
            }).join(' ');
        }
        
        // Add parent items display
        let parentsHtml = '';
        if (parentItemNames.length > 0) {
            const parentPills = parentItemNames.map(name => 
                `<span style="display:inline-block;padding:0.15rem 0.4rem;background:#dbeafe;color:#1e40af;border-radius:0.2rem;font-size:0.7rem;">↖ ${name}</span>`
            ).join(' ');
            parentsHtml = `<div style="margin-top:0.25rem;display:flex;flex-wrap:wrap;gap:0.25rem;">${parentPills}</div>`;
        }

        const row = document.createElement('div');
        row.style.cssText = 'padding: 0.35rem 0.5rem; border: 1px solid var(--line); border-radius: 0.35rem; background: #fff; display: flex; justify-content: space-between; align-items: center; gap: 0.75rem; font-size: 0.82rem;';
        row.innerHTML = `<div style="min-width:0;"><strong>${item.name}</strong><div style="margin-top:0.2rem;display:flex;flex-wrap:wrap;gap:0.3rem;">${costingsHtml}</div>${parentsHtml}</div><a href="${editUrl}" class="icon-action-btn edit" title="Edit"><i class="fas fa-edit"></i></a>`;
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

        document.getElementById('name').focus();
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

        // console.log('Full payload:', payload);

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
            
            // Show success toast
            showToast('success', 'Item saved successfully!');
        } catch (error) {
            showToast('error', error.message || 'Unable to save item.');
        } finally {
            saveItemStayBtn.disabled = false;
            saveItemStayBtn.textContent = 'Save Item';
        }
    });

    syncCostingTableVisibility();
    refreshAddonLabel();
    renderSavedAddons();

    // Add Tax modal - just open it, form submits normally
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
