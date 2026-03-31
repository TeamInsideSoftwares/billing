@extends('layouts.app')

@section('content')
<section class="section-bar">
    <div>
    </div>
    <a href="{{ route('services.index') }}" class="text-link">&larr; Back to services</a>
</section>

<section class="panel-card">
    <form method="POST" action="{{ route('services.store') }}" class="client-form">
        @csrf
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
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="grid-column: span 2;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
        </div>

        @php
            $existingCostings = old('costings', [
                [
                    'currency_code' => $defaultCurrency ?? 'INR',
                    'cost_price' => '',
                    'selling_price' => '',
                    'sac_code' => '',
                    'tax_rate' => '',
                ],
            ]);
            $currencies = $currencies ?? collect();
        @endphp

        <div class="panel-card" style="margin-top: 1rem; border: 1px dashed var(--line);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <div>
                    <p class="eyebrow" style="margin: 0;">Costings</p>
                    <strong>Add pricing per currency</strong>
                </div>
                <button type="button" class="text-link" id="add-costing-row">+ Add currency</button>
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
                                            <option value="{{ $currency->iso }}" {{ $costing['currency_code'] === $currency->iso ? 'selected' : '' }}>
                                                {{ $currency->iso }} - {{ $currency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="costings[{{ $index }}][cost_price]" value="{{ $costing['cost_price'] }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="costings[{{ $index }}][selling_price]" value="{{ $costing['selling_price'] }}" required>
                                </td>
                                <td>
                                    <input type="text" maxlength="20" name="costings[{{ $index }}][sac_code]" value="{{ $costing['sac_code'] ?? '' }}">
                                </td>
                                <td>
                                    <select name="costings[{{ $index }}][tax_included]" style="min-width: 120px;" required>
<option value="no" {{ ($costing['tax_included'] ?? 'no') == 'no' ? 'selected' : '' }}>Excl. Tax</option>
<option value="yes" {{ ($costing['tax_included'] ?? 'no') == 'yes' ? 'selected' : '' }}>Incl. Tax</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="costings[{{ $index }}][tax_rate]" value="{{ $costing['tax_rate'] }}">
                                </td>
                                <td style="width: 70px; text-align: center;">
                                    <button type="button" class="text-link danger remove-costing">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('costings') <span class="error">{{ $message }}</span> @enderror
            @error('costings.*.currency_code') <span class="error">{{ $message }}</span> @enderror
            @error('costings.*.cost_price') <span class="error">{{ $message }}</span> @enderror
            @error('costings.*.selling_price') <span class="error">{{ $message }}</span> @enderror
            @error('costings.*.sac_code') <span class="error">{{ $message }}</span> @enderror
            @error('costings.*.tax_rate') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="primary-button">Create Service</button>
            <a href="{{ route('services.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>

<script>
    (function() {
        const tableBody = document.getElementById('costing-rows');
        let rowIndex = tableBody.rows.length;
        const currencyOptionsHtml = @json(
            collect($currencies)->map(function ($currency) {
                return '<option value="' . e($currency->iso) . '">' . e($currency->iso . ' - ' . $currency->name) . '</option>';
            })->implode('')
        );

        document.getElementById('add-costing-row').addEventListener('click', function() {
            addRow();
        });

        tableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-costing')) {
                if (tableBody.rows.length === 1) {
                    alert('At least one costing is required.');
                    return;
                }
                e.target.closest('tr').remove();
            }
        });

        function addRow(data = {}) {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="costings[${rowIndex}][currency_code]" style="min-width: 180px;" required>
                        <option value="">Select</option>
                        ${currencyOptionsHtml}
                    </select>
                </td>
                <td><input type="number" step="0.01" name="costings[${rowIndex}][cost_price]" value="${data.cost_price || ''}" required></td>
                <td><input type="number" step="0.01" name="costings[${rowIndex}][selling_price]" value="${data.selling_price || ''}" required></td>
                <td><input type="text" maxlength="20" name="costings[${rowIndex}][sac_code]" value="${data.sac_code || ''}"></td>
                <td>
                    <select name="costings[${rowIndex}][tax_included]" style="min-width: 120px;" required>
                        <option value="no">Excl. Tax</option>
                        <option value="yes">Incl. Tax</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0" max="100" name="costings[${rowIndex}][tax_rate]" value="${data.tax_rate || ''}"></td>
                <td style="width: 70px; text-align: center;"><button type="button" class="text-link danger remove-costing">Remove</button></td>
            `;
            tableBody.appendChild(row);
            if (data.currency_code) {
                row.querySelector(`select[name="costings[${rowIndex}][currency_code]"]`).value = data.currency_code;
            }
// Fixed: tax_included now uses 'no'/'yes' consistently
            rowIndex++;
        }
    })();
</script>
@endsection

