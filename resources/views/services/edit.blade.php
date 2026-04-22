@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('services.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left" style="margin-right: 0.4rem;"></i>Back to Items
    </a>
@endsection

@section('content')
{{-- Toast Container --}}
<div id="toast-container" class="toast-container"></div>

<style>
    #item-form .service-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem 1rem;
        margin-bottom: 0.9rem;
        align-items: start;
    }
    #item-form .service-span-2 {
        grid-column: span 2;
    }
    #item-form .service-span-4 {
        grid-column: span 4;
    }
    #item-form .service-field label,
    #item-form .service-toggle > label:first-child {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
    }
    #item-form input[type="text"],
    #item-form input[type="number"],
    #item-form select,
    #item-form textarea {
        width: 100%;
        min-width: 0;
        padding: 0.58rem 0.72rem !important;
        font-size: 0.9rem !important;
    }
    #item-form textarea {
        min-height: 84px;
        resize: vertical;
    }
    #item-form .service-toggle .custom-checkbox {
        min-height: 44px;
        margin-top: 0;
        padding: 0.58rem 0.72rem;
        border: 1px solid var(--line);
        border-radius: 0.7rem;
        background: #fff;
    }
    #item-form .section-divider {
        border-top: 1px solid var(--line);
        padding-top: 0.95rem;
        margin-top: 0.95rem;
    }
    #item-form #addons-dropdown-wrap {
        max-width: 640px !important;
    }
    #item-form #saved-addons-list {
        margin-top: 0.45rem !important;
        gap: 0.35rem !important;
    }
    #item-form #costings-table th,
    #item-form #costings-table td {
        padding: 0.55rem 0.6rem;
        vertical-align: middle;
    }
    #item-form .form-actions {
        margin-top: 1rem !important;
        gap: 0.65rem !important;
    }
    @media (max-width: 1100px) {
        #item-form .service-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        #item-form .service-span-4 {
            grid-column: span 2;
        }
    }
    @media (max-width: 720px) {
        #item-form .service-grid {
            grid-template-columns: 1fr;
        }
        #item-form .service-span-2,
        #item-form .service-span-4 {
            grid-column: span 1;
        }
    }
</style>

<section class="panel-card" style="padding: 1.1rem;">
    <form method="POST" action="{{ route('services.update', $service) }}" class="service-form" id="item-form">
        @method('PUT')
        @csrf

        <div class="service-grid">
            <div class="service-field">
                <label for="type">Type *</label>
                <select id="type" name="type" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                    <option value="product" {{ old('type', $service->type ?? 'service') == 'product' ? 'selected' : '' }}>Product</option>
                    <option value="service" {{ old('type', $service->type ?? 'service') == 'service' ? 'selected' : '' }}>Service</option>
                </select>
                @error('type') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field">
                <label for="ps_catid">Category</label>
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
            <div class="service-toggle">
                <label for="sync">Sync</label>
                <label class="custom-checkbox" style="display: flex; align-items: center; margin-top: 0.25rem; cursor: pointer;">
                    <input type="hidden" name="sync" value="no">
                    <input type="checkbox" name="sync" value="yes" id="sync" {{ old('sync', $service->sync ?? 'no') == 'yes' ? 'checked' : '' }}>
                </label>
                @error('sync') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-toggle">
                <label for="user_wise">User-wise</label>
                <label class="custom-checkbox" style="display: flex; align-items: center; margin-top: 0.25rem; cursor: pointer;">
                    <input type="hidden" name="user_wise" value="0">
                    <input type="checkbox" name="user_wise" value="1" id="user_wise" {{ old('user_wise', $service->user_wise ?? false) ? 'checked' : '' }}>
                </label>
                @error('user_wise') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field service-span-2">
                <label for="name">Item Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required style="padding: 0.4rem 0.5rem; font-size: 0.875rem;">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="service-field service-span-2">
                <label for="description">Description</label>
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
        @endphp

        <div class="section-divider">
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

        <div class="section-divider">
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
            <div style="overflow-x: auto;">
                <table class="data-table" style="min-width: 600px; font-size: 0.82rem;" id="costings-table">
                    <thead>
                        <tr>
                            <th>Currency *</th>
                            <th>Cost Price *</th>
                            <th>Selling Price *</th>
                            <th>SAC Code *</th>
                            <th>Tax</th>
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
                                    @if($account->allow_multi_taxation)
                                    <select name="costings[{{ $index }}][taxid]" class="tax-select" style="min-width: 140px; padding: 0.3rem; font-size: 0.82rem;">
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
                                    <span style="min-width: 140px; padding: 0.3rem 0.5rem; font-size: 0.82rem; background: #f1f5f9; border-radius: 4px; color: #64748b; display: inline-block;">
                                        {{ number_format($account->fixed_tax_rate ?? 0, 2) }}%
                                    </span>
                                    @endif
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

        <div class="form-actions" style="margin-top: 1rem;">
            <button type="submit" class="primary-button" style="padding: 0.4rem 1rem; font-size: 0.875rem;">Update Item</button>
        </div>
    </form>
</section>

{{-- Add Tax Modal --}}
@if($account->allow_multi_taxation)
<div class="modal fade" id="addTaxModalEdit" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="padding: 0.75rem 1.25rem; border-bottom: 1px solid #e5e7eb;">
                <h5 class="modal-title" style="font-size: 1rem; font-weight: 600;">
                    <i class="fas fa-receipt" style="margin-right: 0.5rem; color: #64748b;"></i>Add Tax
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 1.25rem;">
                <form method="POST" action="{{ route('taxes.store') }}" id="quick-tax-form-edit">
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

<script>
(function() {
    // Add Tax modal - just open it, form submits normally
    const taxModalEl = document.getElementById('addTaxModalEdit');
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

<script>
(function() {
    const costingTableBody = document.getElementById('costing-rows');
    let costingRowIndex = costingTableBody.rows.length;

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

    function _esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function taxSelectHtml(i) {
        if (isMultiTax) {
            return `<select name="costings[${i}][taxid]" class="tax-select" style="min-width:140px;padding:0.3rem;font-size:0.82rem;">${taxOptionsHtml}</select>`;
        } else {
            return `<input type="hidden" name="costings[${i}][taxid]" value=""><span style="min-width:140px;padding:0.3rem 0.5rem;font-size:0.82rem;background:#f1f5f9;border-radius:4px;color:#64748b;display:inline-block;">${fixedTaxRate.toFixed(2)}%</span>`;
        }
    }
    function costingRowHtml(i) {
        return `
            <tr>
                <td>
                    <select name="costings[${i}][currency_code]" style="min-width: 150px; padding: 0.3rem;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${i}][cost_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="number" step="0.01" name="costings[${i}][selling_price]" required style="padding: 0.3rem; width: 100px;"></td>
                <td><input type="text" maxlength="20" name="costings[${i}][sac_code]" style="padding: 0.3rem; width: 80px;"></td>
                <td>${taxSelectHtml(i)}</td>
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
