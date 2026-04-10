@php
    $serialOptions = [
        'manual text' => 'Fixed Value',
        'date' => 'Date',
        'year' => 'Year',
        'month-year' => 'Month-Year',
        'date-month' => 'Date-Month',
        'auto increment' => 'Auto Increment',
        'auto generate' => 'Auto Generate',
    ];

    $sepOptions = [
    'none' => 'No Separator',
        '-' => 'Hyphen (-)',
        '/' => 'Slash (/)',
    ];
@endphp

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">

                <!-- Proforma Invoice Serial Config -->
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem;">Proforma Invoice Serial</h4>
                    <form method="POST" action="{{ route('serial.config.update') }}" id="proforma-serial-form">
                        @csrf
                        <input type="hidden" name="from_tab" value="financial-year">
                        <input type="hidden" name="document_type" value="proforma_invoice">
                        @if(isset($proformaSerialConfig))
                            <input type="hidden" name="serial_configid" value="{{ $proformaSerialConfig->serial_configid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                        @php
                            $proformaPreview = isset($proformaSerialConfig) && method_exists($proformaSerialConfig, 'generateNextSerialNumber') ? $proformaSerialConfig->generateNextSerialNumber() : 'Configure serial first';
                        @endphp
                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Preview</label>
                            <div id="proforma-preview" style="font-family: monospace; font-size: 1rem; font-weight: bold; color: {{ empty($proformaSerialConfig) ? '#94a3b8' : '#1e293b' }}; padding: 0.5rem; background: white; border-radius: 6px; border: 2px dashed {{ empty($proformaSerialConfig) ? '#94a3b8' : '#cbd5e1' }}; text-align: center; margin-top: 0.25rem;">
                                {{ $proformaPreview }}
                            </div>
                        </div>

                        @foreach(['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                            <div style="margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr {{ in_array($part, ['prefix', 'number']) ? '0.8fr' : '' }}; gap: 0.4rem; align-items: flex-start;">
                                    <!-- Type Selection -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $label }}</label>
                                        <select name="{{ $part }}_type" class="serial-type-select" data-part="{{ $part }}" data-target="proforma" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($serialOptions as $val => $text)
                                                <option value="{{ $val }}" {{ (isset($proformaSerialConfig) ? ($proformaSerialConfig->{$part.'_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Value/Length Input -->
                                    <div>
                                        <!-- Value input (used for text or start from) -->
                                        <div class="input-group-val">
                                            @php
                                                $valLabel = ($proformaSerialConfig->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value';
                                            @endphp
                                            <label class="val-label" style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $valLabel }}</label>
                                            <input type="text" name="{{ $part }}_value" value="{{ $part == 'number' && (($proformaSerialConfig->{$part.'_type'} ?? 'auto increment') == 'auto increment') ? ($proformaSerialConfig->number_value ?? 1) : ($proformaSerialConfig->{$part.'_value'} ?? '') }}" placeholder="Value" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>

                                        <!-- Length input (used for auto generate) -->
                                        <div class="input-group-len" style="display: none;">
                                            <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Length</label>
                                            <input type="text" name="{{ $part }}_length" placeholder="Length" value="{{ $proformaSerialConfig->{$part.'_length'} ?? 4 }}" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                    </div>

                                    @if(in_array($part, ['prefix', 'number']))
                                    <!-- Separator Dropdown -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Separator</label>
                                        <select name="{{ $part }}_separator" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($sepOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($proformaSerialConfig->{$part.'_separator'} ?? 'none') == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; background: #fffbeb; padding: 0.5rem; border-radius: 6px; border: 1px solid #fef3c7;">
                            <input type="checkbox" name="reset_on_fy" id="proforma-reset-on-fy" value="1" {{ ($proformaSerialConfig->reset_on_fy ?? false) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="proforma-reset-on-fy" style="font-size: 0.75rem; color: #92400e; cursor: pointer; font-weight: 500;">
                                Reset Proforma Serial Number when new FY starts
                            </label>
                        </div>

                        <button type="submit" class="primary-button" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">Save Proforma Serial</button>
                    </form>
                </div>

                <!-- Tax Invoice Serial Config -->
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem;">Tax Invoice Serial</h4>
                    <form method="POST" action="{{ route('serial.config.update') }}" id="billing-serial-form">
                        @csrf
                        <input type="hidden" name="from_tab" value="financial-year">
                        <input type="hidden" name="document_type" value="tax_invoice">
                        @if(isset($taxInvoiceSerialConfig))
                            <input type="hidden" name="serial_configid" value="{{ $taxInvoiceSerialConfig->serial_configid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                        @php
                            $taxInvoicePreview = isset($taxInvoiceSerialConfig) && method_exists($taxInvoiceSerialConfig, 'generateNextSerialNumber') ? $taxInvoiceSerialConfig->generateNextSerialNumber() : 'Configure serial first';
                        @endphp
                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Preview</label>
                            <div id="billing-preview" style="font-family: monospace; font-size: 1rem; font-weight: bold; color: {{ empty($taxInvoiceSerialConfig) ? '#94a3b8' : '#1e293b' }}; padding: 0.5rem; background: white; border-radius: 6px; border: 2px dashed {{ empty($taxInvoiceSerialConfig) ? '#94a3b8' : '#cbd5e1' }}; text-align: center; margin-top: 0.25rem;">
                                {{ $taxInvoicePreview }}
                            </div>
                        </div>

                        @foreach(['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                            <div style="margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr {{ in_array($part, ['prefix', 'number']) ? '0.8fr' : '' }}; gap: 0.4rem; align-items: flex-start;">
                                    <!-- Type Selection -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $label }}</label>
                                        <select name="{{ $part }}_type" class="serial-type-select" data-part="{{ $part }}" data-target="billing" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($serialOptions as $val => $text)
                                                <option value="{{ $val }}" {{ (isset($taxInvoiceSerialConfig) ? ($taxInvoiceSerialConfig->{$part.'_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Value/Length Input -->
                                    <div>
                                        <!-- Value input (used for text or start from) -->
                                        <div class="input-group-val">
                                            @php
                                                $valLabel = (isset($taxInvoiceSerialConfig) && $taxInvoiceSerialConfig->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value';
                                            @endphp
                                            <label class="val-label" style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $valLabel }}</label>
                                            <input type="text" name="{{ $part }}_value" value="{{ $part == 'number' && ((isset($taxInvoiceSerialConfig) ? ($taxInvoiceSerialConfig->{$part.'_type'} ?? 'auto increment') : 'auto increment') == 'auto increment') ? ($taxInvoiceSerialConfig->number_value ?? 1) : ($taxInvoiceSerialConfig->{$part.'_value'} ?? '') }}" placeholder="Value" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>

                                        <!-- Length input (used for auto generate) -->
                                        <div class="input-group-len" style="display: none;">
                                            <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Length</label>
                                            <input type="text" name="{{ $part }}_length" placeholder="Length" value="{{ $taxInvoiceSerialConfig->{$part.'_length'} ?? 4 }}" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                    </div>

                                    @if(in_array($part, ['prefix', 'number']))
                                    <!-- Separator Dropdown -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Separator</label>
                                        <select name="{{ $part }}_separator" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($sepOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($taxInvoiceSerialConfig->{$part.'_separator'} ?? 'none') == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; background: #fffbeb; padding: 0.5rem; border-radius: 6px; border: 1px solid #fef3c7;">
                            <input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1" {{ ($taxInvoiceSerialConfig->reset_on_fy ?? false) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="billing-reset-on-fy" style="font-size: 0.75rem; color: #92400e; cursor: pointer; font-weight: 500;">
                                Reset Tax Invoice Serial Number when new FY starts
                            </label>
                        </div>

                        <button type="submit" class="primary-button" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">Save Tax Invoice Serial</button>
                    </form>
                </div>

    <!-- Quotation Serial Config -->
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem;">Quotation Serial</h4>
                    <form method="POST" action="{{ route('serial.config.update') }}" id="quotation-serial-form">
                        @csrf
                        <input type="hidden" name="from_tab" value="financial-year">
                        <input type="hidden" name="document_type" value="quotation">
                        @if(isset($quotationSerialConfig))
                            <input type="hidden" name="serial_configid" value="{{ $quotationSerialConfig->serial_configid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                        @php
                            $quotationPreview = isset($quotationSerialConfig) && method_exists($quotationSerialConfig, 'generateNextSerialNumber') ? $quotationSerialConfig->generateNextSerialNumber() : 'Configure serial first';
                        @endphp
                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Preview</label>
                            <div id="quotation-preview" style="font-family: monospace; font-size: 1rem; font-weight: bold; color: {{ empty($quotationSerialConfig) ? '#94a3b8' : '#1e293b' }}; padding: 0.5rem; background: white; border-radius: 6px; border: 2px dashed {{ empty($quotationSerialConfig) ? '#94a3b8' : '#cbd5e1' }}; text-align: center; margin-top: 0.25rem;">
                                {{ $quotationPreview }}
                            </div>
                        </div>

                        @foreach(['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                            <div style="margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr {{ in_array($part, ['prefix', 'number']) ? '0.8fr' : '' }}; gap: 0.4rem; align-items: flex-start;">
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $label }}</label>
                                        <select name="{{ $part }}_type" class="serial-type-select" data-part="{{ $part }}" data-target="quotation" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($serialOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($quotationSerialConfig->{$part.'_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <div class="input-group-val">
                                            @php $valLabel = ($quotationSerialConfig->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value'; @endphp
                                            <label class="val-label" style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $valLabel }}</label>
                                            <input type="text" name="{{ $part }}_value" value="{{ $part == 'number' && (($quotationSerialConfig->{$part.'_type'} ?? 'auto increment') == 'auto increment') ? ($quotationSerialConfig->number_value ?? 1) : ($quotationSerialConfig->{$part.'_value'} ?? '') }}" placeholder="Value" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                        <div class="input-group-len" style="display: none;">
                                            <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Length</label>
                                            <input type="text" name="{{ $part }}_length" placeholder="Length" value="{{ $quotationSerialConfig->{$part.'_length'} ?? 4 }}" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                    </div>
                                    @if(in_array($part, ['prefix', 'number']))
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Separator</label>
                                        <select name="{{ $part }}_separator" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($sepOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($quotationSerialConfig->{$part.'_separator'} ?? 'none') == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; background: #fffbeb; padding: 0.5rem; border-radius: 6px; border: 1px solid #fef3c7;">
                            <input type="checkbox" name="reset_on_fy" id="quotation-reset-on-fy" value="1" {{ ($quotationSerialConfig->reset_on_fy ?? false) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="quotation-reset-on-fy" style="font-size: 0.75rem; color: #92400e; cursor: pointer; font-weight: 500;">
                               Reset Quotation Serial Number when new FY starts
                            </label>
                        </div>

                        <button type="submit" class="primary-button" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">Save Quotation Serial</button>
                    </form>
                </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure all elements are rendered
    setTimeout(function() {
        const allTypeSelects = document.querySelectorAll('.serial-type-select');
        
        allTypeSelects.forEach(function(select) {
            // Initialize on page load
            toggleInputs(select);
            
            // Update on change
            select.addEventListener('change', function() {
                toggleInputs(this);
                updatePreview(this.closest('form'));
            });
        });
        
        // Listen to all input changes for preview update
        const allForms = document.querySelectorAll('#proforma-serial-form, #billing-serial-form, #quotation-serial-form');
        allForms.forEach(function(form) {
            form.addEventListener('input', function() {
                updatePreview(this);
            });
            form.addEventListener('change', function() {
                updatePreview(this);
            });
        });
    }, 100);
    
    function toggleInputs(select) {
        // Find the parent grid row (the inner div with grid-template-columns)
        const innerGrid = select.closest('[style*="grid-template-columns"]');
        if (!innerGrid) return;
        
        // The second child is always the Value/Length column
        const children = Array.from(innerGrid.children);
        const secondColumn = children[1];
        if (!secondColumn) return;
        
        const valueGroup = secondColumn.querySelector('.input-group-val');
        const lengthGroup = secondColumn.querySelector('.input-group-len');
        const valLabel = valueGroup?.querySelector('.val-label');
        
        if (select.value === 'auto generate') {
            // Show Length, hide Value
            if (valueGroup) valueGroup.style.display = 'none';
            if (lengthGroup) lengthGroup.style.display = 'block';
        } else if (select.value === 'manual text' || select.value === 'auto increment') {
            // Show Value, hide Length
            if (valueGroup) valueGroup.style.display = 'block';
            if (lengthGroup) lengthGroup.style.display = 'none';
            if (valLabel) {
                valLabel.textContent = select.value === 'auto increment' ? 'Start From' : 'Enter its value';
            }
        } else {
            // For year, date, month-year, date-month - hide both (they use dynamic values)
            if (valueGroup) valueGroup.style.display = 'none';
            if (lengthGroup) lengthGroup.style.display = 'none';
        }
    }
    
    function updatePreview(form) {
        if (!form) return;
        
        const now = new Date();
        const currentYear = now.getFullYear().toString();
        const currentMonth = (now.getMonth() + 1).toString().padStart(2, '0');
        const currentDate = now.getDate().toString().padStart(2, '0');
        
        function getPartLength(part) {
            return parseInt(form.querySelector(`[name="${part}_length"]`)?.value || '4', 10);
        }

        function getTypeValue(part) {
            const type = form.querySelector(`[name="${part}_type"]`)?.value || 'manual text';
            const value = form.querySelector(`[name="${part}_value"]`)?.value || '';

            switch(type) {
                case 'manual text':
                    return value || '';
                case 'date':
                    return `${currentYear}-${currentMonth}-${currentDate}`;
                case 'year':
                    return currentYear;
                case 'month-year':
                    return currentMonth + '-' + currentYear;
                case 'date-month':
                    return currentDate + '-' + currentMonth;
                case 'auto increment':
                    return value || '1';
                case 'auto generate': {
                    const len = Math.max(1, getPartLength(part));
                    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                    let result = '';
                    for (let i = 0; i < len; i++) {
                        result += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    return result;
                }
                default:
                    return value || 'XXX';
            }
        }

        function pushPart(parts, part, separatorField = null) {
            const partValue = getTypeValue(part);
            const nextPart = part === 'prefix' ? 'number' : (part === 'number' ? 'suffix' : null);
            const nextValue = nextPart ? getTypeValue(nextPart) : '';
            const separator = separatorField ? (form.querySelector(`[name="${separatorField}"]`)?.value || 'none') : 'none';

            if (partValue) {
                parts.push(partValue);
            }

            if (partValue && nextValue && separator !== 'none') {
                parts.push(separator);
            }
        }

        let parts = [];
        pushPart(parts, 'prefix', 'prefix_separator');
        pushPart(parts, 'number', 'number_separator');
        pushPart(parts, 'suffix');
        
        const previewId = form.id === 'proforma-serial-form' ? 'proforma-preview' : 
                         form.id === 'billing-serial-form' ? 'billing-preview' : 'quotation-preview';
        const previewEl = document.getElementById(previewId);
        if (previewEl) {
            previewEl.textContent = parts.join('') || 'Configure serial first';
        }
    }
});
</script>
