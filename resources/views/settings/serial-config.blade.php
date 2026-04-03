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
        '-' => 'Hyphen (-)',
        '/' => 'Slash (/)',
        'none' => 'No Separator',
    ];
@endphp

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">


                <!-- Billing Serial Config -->
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem;">Invoice Serial</h4>
                    <form method="POST" action="{{ route('account.billing.update') }}" id="billing-serial-form">
                        @csrf
                        @if(isset($editingBillingDetail))
                            <input type="hidden" name="account_bdid" value="{{ $editingBillingDetail->account_bdid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">
                        
                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Preview</label>
                            <div id="billing-preview" style="font-family: monospace; font-size: 1rem; font-weight: bold; color: #1e293b; padding: 0.5rem; background: white; border-radius: 6px; border: 2px dashed #cbd5e1; text-align: center; margin-top: 0.25rem;">
                                INV-1-8596
                            </div>
                        </div>

                        @foreach(['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                            <div style="margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr {{ in_array($part, ['prefix', 'number']) ? '0.8fr' : '' }}; gap: 0.4rem; align-items: flex-start;">
                                    <!-- Type Selection -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $label }}</label>
                                        <!-- <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Type</label> -->
                                        <select name="{{ $part }}_type" class="serial-type-select" data-part="{{ $part }}" data-target="billing" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($serialOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($editingBillingDetail->{$part.'_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <!-- Value/Length Input -->
                                    <div>
                                        <!-- Value input (used for text or start from) -->
                                        <div class="input-group-val">
                                            @php
                                                $valLabel = ($editingBillingDetail->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value';
                                            @endphp
                                            <label class="val-label" style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $valLabel }}</label>
                                            <input type="text" name="{{ $part }}_value" value="{{ $editingBillingDetail->{$part.'_value'} ?? ($part == 'number' ? ($editingBillingDetail->auto_increment_start ?? 1) : '') }}" placeholder="Value" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                        
                                        <!-- Length input (used for auto generate) -->
                                        <div class="input-group-len">
                                            <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Length</label>
                                            <input type="text" name="{{ $part }}_length" placeholder="Length" value="{{ $editingBillingDetail->{$part.'_length'} ?? 4 }}" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                    </div>

                                    @if(in_array($part, ['prefix', 'number']))
                                    <!-- Separator Dropdown -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Separator</label>
                                        <select name="{{ $part }}_separator" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($sepOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($editingBillingDetail->{$part.'_separator'} ?? '-') == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; background: #fffbeb; padding: 0.5rem; border-radius: 6px; border: 1px solid #fef3c7;">
                            <input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1" {{ ($editingBillingDetail->reset_on_fy ?? false) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="billing-reset-on-fy" style="font-size: 0.75rem; color: #92400e; cursor: pointer; font-weight: 500;">
                                Reset Invoice Serial Number when new FY starts
                            </label>
                        </div>

                        <button type="submit" class="primary-button" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">Save Invoice Serial</button>
                    </form>
                </div>

    <!-- Quotation Serial Config -->
                <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem;">Quotation Serial</h4>
                    <form method="POST" action="{{ route('account.quotation.update') }}" id="quotation-serial-form">
                        @csrf
                        @if(isset($editingQuotationDetail))
                            <input type="hidden" name="account_qdid" value="{{ $editingQuotationDetail->account_qdid }}">
                        @endif
                        <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Preview</label>
                            <div id="quotation-preview" style="font-family: monospace; font-size: 1rem; font-weight: bold; color: #1e293b; padding: 0.5rem; background: white; border-radius: 6px; border: 2px dashed #cbd5e1; text-align: center; margin-top: 0.25rem;">
                                QUO-1-8512
                            </div>
                        </div>

                        @foreach(['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                            <div style="margin-bottom: 1rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr {{ in_array($part, ['prefix', 'number']) ? '0.8fr' : '' }}; gap: 0.4rem; align-items: flex-start;">
                                    <!-- Type Selection -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $label }}</label>
                                        <!-- <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Type</label> -->
                                        <select name="{{ $part }}_type" class="serial-type-select" data-part="{{ $part }}" data-target="quotation" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($serialOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($editingQuotationDetail->{$part.'_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <!-- Value/Length Input -->
                                    <div>
                                        <!-- Value input (used for text or start from) -->
                                        <div class="input-group-val">
                                            @php
                                                $valLabel = ($editingQuotationDetail->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value';
                                            @endphp
                                            <label class="val-label" style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">{{ $valLabel }}</label>
                                            <input type="text" name="{{ $part }}_value" value="{{ $editingQuotationDetail->{$part.'_value'} ?? ($part == 'number' ? ($editingQuotationDetail->auto_increment_start ?? 1) : '') }}" placeholder="Value" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                        
                                        <!-- Length input (used for auto generate) -->
                                        <div class="input-group-len">
                                            <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Length</label>
                                            <input type="text" name="{{ $part }}_length" placeholder="Length" value="{{ $editingQuotationDetail->{$part.'_length'} ?? 4 }}" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                        </div>
                                    </div>

                                    @if(in_array($part, ['prefix', 'number']))
                                    <!-- Separator Dropdown -->
                                    <div>
                                        <label style="font-size: 0.65rem; color: #64748b; display: block; margin-bottom: 2px;">Separator</label>
                                        <select name="{{ $part }}_separator" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem;">
                                            @foreach($sepOptions as $val => $text)
                                                <option value="{{ $val }}" {{ ($editingQuotationDetail->{$part.'_separator'} ?? '-') == $val ? 'selected' : '' }}>{{ $text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem; background: #fffbeb; padding: 0.5rem; border-radius: 6px; border: 1px solid #fef3c7;">
                            <input type="checkbox" name="reset_on_fy" id="quotation-reset-on-fy" value="1" {{ ($editingQuotationDetail->reset_on_fy ?? false) ? 'checked' : '' }} style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="quotation-reset-on-fy" style="font-size: 0.75rem; color: #92400e; cursor: pointer; font-weight: 500;">
                               Reset Quotation Serial Number when new FY starts
                            </label>
                        </div>

                        <button type="submit" class="primary-button" style="width: 100%; padding: 0.5rem; font-size: 0.85rem;">Save Quotation Serial</button>
                    </form>
                </div>
