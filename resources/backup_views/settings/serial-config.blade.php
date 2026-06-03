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

<div class="row serial-config-grid">

    <!-- Proforma Invoice Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="serial-config-card">
            <h4 class="serial-config-title">Proforma Invoice Serial</h4>
            <form method="POST" action="{{ route('serial.config.update') }}" id="proforma-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="financial-year">
                <input type="hidden" name="document_type" value="proforma_invoice">
                @if (isset($proformaSerialConfig))
                    <input type="hidden" name="serial_configid" value="{{ $proformaSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $proformaPreview =
                        isset($proformaSerialConfig) && method_exists($proformaSerialConfig, 'generateNextSerialNumber')
                            ? $proformaSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp
                <!-- Parts Toggle -->

                <div class="serial-section">
                    <label class="serial-preview-label">Preview</label>
                    <div id="proforma-preview"
                        class="serial-preview {{ empty($proformaSerialConfig) ? 'serial-preview--empty' : 'serial-preview--ready' }}">
                        {{ $proformaPreview }}
                    </div>
                </div>

                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $proformaHasSeparator = in_array($part, ['prefix', 'number']);
                        $proformaGridClass = $proformaHasSeparator
                            ? 'serial-part-grid serial-part-grid--with-separator'
                            : 'serial-part-grid';
                    @endphp
                    <div class="serial-part-row {{ !(isset($proformaSerialConfig) && ($proformaSerialConfig->{$part . '_show'} ?? 1)) ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="serial-part-toggle-label">
                            <input type="checkbox" class="part-toggle serial-part-toggle"
                                data-part="{{ $part }}" name="{{ $part }}_show" value="1"
                                {{ isset($proformaSerialConfig) && ($proformaSerialConfig->{$part . '_show'} ?? 1) ? 'checked' : '' }}>
                        </label>
                        <div class="{{ $proformaGridClass }}">
                            <!-- Type Selection -->
                            <div>
                                <label class="serial-field-label">{{ $label }}</label>
                                <select name="{{ $part }}_type" class="serial-select serial-type-select"
                                    data-part="{{ $part }}" data-target="proforma">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ (isset($proformaSerialConfig) ? $proformaSerialConfig->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text') : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>
                                            {{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Value/Length Input -->
                            <div>
                                <!-- Value input (used for text or start from) -->
                                <div class="input-group-val">
                                    @php
                                        $valLabel =
                                            ($proformaSerialConfig->{$part . '_type'} ?? '') == 'auto increment'
                                                ? 'Start From'
                                                : 'Enter its value';
                                    @endphp
                                    <label class="serial-field-label val-label">{{ $valLabel }}</label>
                                    <input type="text" name="{{ $part }}_value"
                                        value="{{ $part == 'number' && ($proformaSerialConfig->{$part . '_type'} ?? 'auto increment') == 'auto increment' ? $proformaSerialConfig->number_value ?? 1 : $proformaSerialConfig->{$part . '_value'} ?? '' }}"
                                        placeholder="Value" class="serial-input">
                                </div>

                                <!-- Length input (used for auto generate) -->
                                <div class="input-group-len d-none">
                                    <label class="serial-field-label">Length</label>
                                    <input type="text" name="{{ $part }}_length" placeholder="Length"
                                        value="{{ $proformaSerialConfig->{$part . '_length'} ?? 4 }}"
                                        class="serial-input">
                                </div>
                            </div>

                            @if ($proformaHasSeparator)
                                <!-- Separator Dropdown -->
                                <div>
                                    <label class="serial-field-label">Separator</label>
                                    <select name="{{ $part }}_separator" class="serial-select">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($proformaSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="serial-warning">
                    <input type="checkbox" name="reset_on_fy" id="proforma-reset-on-fy" value="1"
                        {{ $proformaSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="serial-warning-checkbox">
                    <label for="proforma-reset-on-fy" class="serial-warning-label">
                        Reset Proforma Serial Number when new FY starts
                    </label>
                </div>

                <button type="submit" class="primary-button serial-save-button">Save Proforma Serial</button>
            </form>
        </div>
    </div>

    <!-- Tax Invoice Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="serial-config-card">
            <h4 class="serial-config-title">Tax Invoice Serial</h4>
            <form method="POST" action="{{ route('serial.config.update') }}" id="billing-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="financial-year">
                <input type="hidden" name="document_type" value="tax_invoice">
                @if (isset($taxInvoiceSerialConfig))
                    <input type="hidden" name="serial_configid"
                        value="{{ $taxInvoiceSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $taxInvoicePreview =
                        isset($taxInvoiceSerialConfig) &&
                        method_exists($taxInvoiceSerialConfig, 'generateNextSerialNumber')
                            ? $taxInvoiceSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp
                <!-- Parts Toggle -->

                <div class="serial-section">
                    <label class="serial-preview-label">Preview</label>
                    <div id="billing-preview"
                        class="serial-preview {{ empty($taxInvoiceSerialConfig) ? 'serial-preview--empty' : 'serial-preview--ready' }}">
                        {{ $taxInvoicePreview }}
                    </div>
                </div>

                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $gridClass = $hasSeparator
                            ? 'serial-part-grid serial-part-grid--with-separator'
                            : 'serial-part-grid';
                    @endphp
                    <div class="serial-part-row {{ !(isset($taxInvoiceSerialConfig) && ($taxInvoiceSerialConfig->{$part . '_show'} ?? 1)) ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="serial-part-toggle-label">
                            <input type="checkbox" class="part-toggle serial-part-toggle"
                                data-part="{{ $part }}" name="{{ $part }}_show" value="1"
                                {{ isset($taxInvoiceSerialConfig) && ($taxInvoiceSerialConfig->{$part . '_show'} ?? 1) ? 'checked' : '' }}>
                        </label>
                        <div class="{{ $gridClass }}">
                            <!-- Type Selection -->
                            <div>
                                <label class="serial-field-label">{{ $label }}</label>
                                <select name="{{ $part }}_type" class="serial-select serial-type-select"
                                    data-part="{{ $part }}" data-target="billing">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ (isset($taxInvoiceSerialConfig) ? $taxInvoiceSerialConfig->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text') : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>
                                            {{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Value/Length Input -->
                            <div>
                                <!-- Value input (used for text or start from) -->
                                <div class="input-group-val">
                                    @php
                                        $valLabel =
                                            (isset($taxInvoiceSerialConfig) &&
                                                $taxInvoiceSerialConfig->{$part . '_type'} ??
                                                '') ==
                                            'auto increment'
                                                ? 'Start From'
                                                : 'Enter its value';
                                    @endphp
                                    <label class="serial-field-label val-label">{{ $valLabel }}</label>
                                    <input type="text" name="{{ $part }}_value"
                                        value="{{ $part == 'number' && (isset($taxInvoiceSerialConfig) ? $taxInvoiceSerialConfig->{$part . '_type'} ?? 'auto increment' : 'auto increment') == 'auto increment' ? $taxInvoiceSerialConfig->number_value ?? 1 : $taxInvoiceSerialConfig->{$part . '_value'} ?? '' }}"
                                        placeholder="Value" class="serial-input">
                                </div>

                                <!-- Length input (used for auto generate) -->
                                <div class="input-group-len d-none">
                                    <label class="serial-field-label">Length</label>
                                    <input type="text" name="{{ $part }}_length" placeholder="Length"
                                        value="{{ $taxInvoiceSerialConfig->{$part . '_length'} ?? 4 }}"
                                        class="serial-input">
                                </div>
                            </div>

                            @if ($hasSeparator)
                                <!-- Separator Dropdown -->
                                <div>
                                    <label class="serial-field-label">Separator</label>
                                    <select name="{{ $part }}_separator" class="serial-select">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($taxInvoiceSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="serial-warning">
                    <input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1"
                        {{ $taxInvoiceSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="serial-warning-checkbox">
                    <label for="billing-reset-on-fy" class="serial-warning-label">
                        Reset Tax Invoice Serial Number when new FY starts
                    </label>
                </div>

                <button type="submit" class="primary-button serial-save-button">Save Tax Invoice Serial</button>
            </form>
        </div>
    </div>

    <!-- Quotation Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="serial-config-card">
            <h4 class="serial-config-title">Quotation Serial</h4>
            <form method="POST" action="{{ route('serial.config.update') }}" id="quotation-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="financial-year">
                <input type="hidden" name="document_type" value="quotation">
                @if (isset($quotationSerialConfig))
                    <input type="hidden" name="serial_configid"
                        value="{{ $quotationSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $quotationPreview =
                        isset($quotationSerialConfig) &&
                        method_exists($quotationSerialConfig, 'generateNextSerialNumber')
                            ? $quotationSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp
                <!-- Parts Toggle -->

                <div class="serial-section">
                    <label class="serial-preview-label">Preview</label>
                    <div id="quotation-preview"
                        class="serial-preview {{ empty($quotationSerialConfig) ? 'serial-preview--empty' : 'serial-preview--ready' }}">
                        {{ $quotationPreview }}
                    </div>
                </div>

                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $gridClass = $hasSeparator
                            ? 'serial-part-grid serial-part-grid--with-separator'
                            : 'serial-part-grid';
                    @endphp
                    <div class="serial-part-row {{ !(isset($quotationSerialConfig) && ($quotationSerialConfig->{$part . '_show'} ?? 1)) ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="serial-part-toggle-label">
                            <input type="checkbox" class="part-toggle serial-part-toggle"
                                data-part="{{ $part }}" name="{{ $part }}_show" value="1"
                                {{ isset($quotationSerialConfig) && ($quotationSerialConfig->{$part . '_show'} ?? 1) ? 'checked' : '' }}>
                        </label>
                        <div class="{{ $gridClass }}">
                            <div>
                                <label class="serial-field-label">{{ $label }}</label>
                                <select name="{{ $part }}_type" class="serial-select serial-type-select"
                                    data-part="{{ $part }}" data-target="quotation">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ ($quotationSerialConfig->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>
                                            {{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="input-group-val">
                                    @php $valLabel = ($quotationSerialConfig->{$part.'_type'} ?? '') == 'auto increment' ? 'Start From' : 'Enter its value'; @endphp
                                    <label class="serial-field-label val-label">{{ $valLabel }}</label>
                                    <input type="text" name="{{ $part }}_value"
                                        value="{{ $part == 'number' && ($quotationSerialConfig->{$part . '_type'} ?? 'auto increment') == 'auto increment' ? $quotationSerialConfig->number_value ?? 1 : $quotationSerialConfig->{$part . '_value'} ?? '' }}"
                                        placeholder="Value" class="serial-input">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="serial-field-label">Length</label>
                                    <input type="text" name="{{ $part }}_length" placeholder="Length"
                                        value="{{ $quotationSerialConfig->{$part . '_length'} ?? 4 }}"
                                        class="serial-input">
                                </div>
                            </div>
                            @if ($hasSeparator)
                                <div>
                                    <label class="serial-field-label">Separator</label>
                                    <select name="{{ $part }}_separator" class="serial-select">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($quotationSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="serial-warning">
                    <input type="checkbox" name="reset_on_fy" id="quotation-reset-on-fy" value="1"
                        {{ $quotationSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="serial-warning-checkbox">
                    <label for="quotation-reset-on-fy" class="serial-warning-label">
                        Reset Quotation Serial Number when new FY starts
                    </label>
                </div>

                <button type="submit" class="primary-button serial-save-button">Save Quotation Serial</button>
            </form>
        </div>
    </div>

    <!-- Order Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="serial-config-card">
            <h4 class="serial-config-title">Order Serial</h4>
            <form method="POST" action="{{ route('serial.config.update') }}" id="order-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="financial-year">
                <input type="hidden" name="document_type" value="order">
                @if (isset($orderSerialConfig))
                    <input type="hidden" name="serial_configid" value="{{ $orderSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $orderPreview =
                        isset($orderSerialConfig) && method_exists($orderSerialConfig, 'generateNextSerialNumber')
                            ? $orderSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp
                <!-- Parts Toggle -->

                <div class="serial-section">
                    <label class="serial-preview-label">Preview</label>
                    <div id="order-preview"
                        class="serial-preview {{ empty($orderSerialConfig) ? 'serial-preview--empty' : 'serial-preview--ready' }}">
                        {{ $orderPreview }}
                    </div>
                </div>

                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $gridClass = $hasSeparator
                            ? 'serial-part-grid serial-part-grid--with-separator'
                            : 'serial-part-grid';
                    @endphp
                    <div class="serial-part-row {{ !(isset($orderSerialConfig) && ($orderSerialConfig->{$part . '_show'} ?? 1)) ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="serial-part-toggle-label">
                            <input type="checkbox" class="part-toggle serial-part-toggle"
                                data-part="{{ $part }}" name="{{ $part }}_show" value="1"
                                {{ isset($orderSerialConfig) && ($orderSerialConfig->{$part . '_show'} ?? 1) ? 'checked' : '' }}>
                        </label>
                        <div class="{{ $gridClass }}">
                            <div>
                                <label class="serial-field-label">{{ $label }}</label>
                                <select name="{{ $part }}_type" class="serial-select serial-type-select"
                                    data-part="{{ $part }}" data-target="order">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ (isset($orderSerialConfig) ? $orderSerialConfig->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text') : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>
                                            {{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="input-group-val">
                                    @php
                                        $valLabel =
                                            (isset($orderSerialConfig) && $orderSerialConfig->{$part . '_type'} ??
                                                '') ==
                                            'auto increment'
                                                ? 'Start From'
                                                : 'Enter its value';
                                    @endphp
                                    <label class="serial-field-label val-label">{{ $valLabel }}</label>
                                    <input type="text" name="{{ $part }}_value"
                                        value="{{ $part == 'number' && (isset($orderSerialConfig) ? $orderSerialConfig->{$part . '_type'} ?? 'auto increment' : 'auto increment') == 'auto increment' ? $orderSerialConfig->number_value ?? 1 : $orderSerialConfig->{$part . '_value'} ?? '' }}"
                                        placeholder="Value" class="serial-input">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="serial-field-label">Length</label>
                                    <input type="text" name="{{ $part }}_length" placeholder="Length"
                                        value="{{ $orderSerialConfig->{$part . '_length'} ?? 4 }}"
                                        class="serial-input">
                                </div>
                            </div>
                            @if ($hasSeparator)
                                <div>
                                    <label class="serial-field-label">Separator</label>
                                    <select name="{{ $part }}_separator" class="serial-select">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ (isset($orderSerialConfig) ? $orderSerialConfig->{$part . '_separator'} ?? 'none' : 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="serial-warning">
                    <input type="checkbox" name="reset_on_fy" id="order-reset-on-fy" value="1"
                        {{ $orderSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="serial-warning-checkbox">
                    <label for="order-reset-on-fy" class="serial-warning-label">
                        Reset Order Serial Number when new FY starts
                    </label>
                </div>

                <button type="submit" class="primary-button serial-save-button">Save Order Serial</button>
            </form>
        </div>
    </div>

    <!-- Payment Receipt Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="serial-config-card">
            <h4 class="serial-config-title">Payment Receipt Number</h4>
            <form method="POST" action="{{ route('serial.config.update') }}" id="payment-receipt-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="financial-year">
                <input type="hidden" name="document_type" value="payment_receipt">
                @if (isset($paymentReceiptSerialConfig))
                    <input type="hidden" name="serial_configid"
                        value="{{ $paymentReceiptSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $paymentReceiptPreview =
                        isset($paymentReceiptSerialConfig) &&
                        method_exists($paymentReceiptSerialConfig, 'generateNextSerialNumber')
                            ? $paymentReceiptSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp

                <div class="serial-section">
                    <label class="serial-preview-label">Preview</label>
                    <div id="payment-receipt-preview"
                        class="serial-preview {{ empty($paymentReceiptSerialConfig) ? 'serial-preview--empty' : 'serial-preview--ready' }}">
                        {{ $paymentReceiptPreview }}
                    </div>
                </div>

                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $gridClass = $hasSeparator
                            ? 'serial-part-grid serial-part-grid--with-separator'
                            : 'serial-part-grid';
                    @endphp
                    <div class="serial-part-row {{ !(isset($paymentReceiptSerialConfig) && ($paymentReceiptSerialConfig->{$part . '_show'} ?? 1)) ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="serial-part-toggle-label">
                            <input type="checkbox" class="part-toggle serial-part-toggle"
                                data-part="{{ $part }}" name="{{ $part }}_show" value="1"
                                {{ isset($paymentReceiptSerialConfig) && ($paymentReceiptSerialConfig->{$part . '_show'} ?? 1) ? 'checked' : '' }}>
                        </label>
                        <div class="{{ $gridClass }}">
                            <div>
                                <label class="serial-field-label">{{ $label }}</label>
                                <select name="{{ $part }}_type" class="serial-select serial-type-select"
                                    data-part="{{ $part }}" data-target="payment-receipt">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ (isset($paymentReceiptSerialConfig) ? $paymentReceiptSerialConfig->{$part . '_type'} ?? ($part == 'number' ? 'auto increment' : 'manual text') : ($part == 'number' ? 'auto increment' : 'manual text')) == $val ? 'selected' : '' }}>
                                            {{ $text }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <div class="input-group-val">
                                    @php
                                        $valLabel =
                                            (isset($paymentReceiptSerialConfig) &&
                                                $paymentReceiptSerialConfig->{$part . '_type'} ??
                                                '') ==
                                            'auto increment'
                                                ? 'Start From'
                                                : 'Enter its value';
                                    @endphp
                                    <label class="serial-field-label val-label">{{ $valLabel }}</label>
                                    <input type="text" name="{{ $part }}_value"
                                        value="{{ $part == 'number' && (isset($paymentReceiptSerialConfig) ? $paymentReceiptSerialConfig->{$part . '_type'} ?? 'auto increment' : 'auto increment') == 'auto increment' ? $paymentReceiptSerialConfig->number_value ?? 1 : $paymentReceiptSerialConfig->{$part . '_value'} ?? '' }}"
                                        placeholder="Value" class="serial-input">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="serial-field-label">Length</label>
                                    <input type="text" name="{{ $part }}_length" placeholder="Length"
                                        value="{{ $paymentReceiptSerialConfig->{$part . '_length'} ?? 4 }}"
                                        class="serial-input">
                                </div>
                            </div>
                            @if ($hasSeparator)
                                <div>
                                    <label class="serial-field-label">Separator</label>
                                    <select name="{{ $part }}_separator" class="serial-select">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ (isset($paymentReceiptSerialConfig) ? $paymentReceiptSerialConfig->{$part . '_separator'} ?? 'none' : 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="serial-warning">
                    <input type="checkbox" name="reset_on_fy" id="payment-receipt-reset-on-fy" value="1"
                        {{ $paymentReceiptSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="serial-warning-checkbox">
                    <label for="payment-receipt-reset-on-fy" class="serial-warning-label">
                        Reset Payment Receipt Serial Number when new FY starts
                    </label>
                </div>

                <button type="submit" class="primary-button serial-save-button">Save Payment Receipt Serial</button>
            </form>
        </div>
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

            // Handle part toggle checkboxes
            const allPartToggles = document.querySelectorAll('.part-toggle');
            allPartToggles.forEach(function(checkbox) {
                // Initialize on page load
                togglePartRow(checkbox);

                // Update on change
                checkbox.addEventListener('change', function() {
                    togglePartRow(this);
                    updatePreview(this.closest('form'));
                });
            });

            // Initial preview update for all forms
            const allForms = document.querySelectorAll(
                '#proforma-serial-form, #billing-serial-form, #quotation-serial-form, #order-serial-form, #payment-receipt-serial-form'
                );
            allForms.forEach(function(form) {
                updatePreview(form);
                form.addEventListener('input', function() {
                    updatePreview(this);
                });
                form.addEventListener('change', function() {
                    updatePreview(this);
                });
            });
        }, 100);

        function togglePartRow(checkbox) {
            const part = checkbox.dataset.part;
            const form = checkbox.closest('form');
            if (!form) return;

            const row = form.querySelector('.serial-part-row[data-part="' + part + '"]');
            if (!row) return;

            const isChecked = checkbox.checked;

            // Disable/enable inputs in the row (EXCEPT the checkbox itself)
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(function(input) {
                if (input.classList.contains('part-toggle')) return;
                input.disabled = !isChecked;
            });

            // Visual feedback: opacity only (keep row clickable)
            row.classList.toggle('serial-part-row--disabled', !isChecked);
        }

        function toggleInputs(select) {
            // Find the parent grid row
            const innerGrid = select.closest('.serial-part-grid');
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
                if (valueGroup) valueGroup.classList.add('d-none');
                if (lengthGroup) lengthGroup.classList.remove('d-none');
            } else if (select.value === 'manual text' || select.value === 'auto increment') {
                // Show Value, hide Length
                if (valueGroup) valueGroup.classList.remove('d-none');
                if (lengthGroup) lengthGroup.classList.add('d-none');
                if (valLabel) {
                    valLabel.textContent = select.value === 'auto increment' ? 'Start From' : 'Enter its value';
                }
            } else {
                // For year, date, month-year, date-month - hide both (they use dynamic values)
                if (valueGroup) valueGroup.classList.add('d-none');
                if (lengthGroup) lengthGroup.classList.add('d-none');
            }
        }

        function updatePreview(form) {
            if (!form) return;

            const now = new Date();
            const currentYear = now.getFullYear().toString();
            const currentMonth = (now.getMonth() + 1).toString().padStart(2, '0');
            const currentDate = now.getDate().toString().padStart(2, '0');

            function isPartShown(part) {
                const checkbox = form.querySelector('.part-toggle[data-part="' + part + '"]');
                return checkbox ? checkbox.checked : true;
            }

            function getPartLength(part) {
                return parseInt(form.querySelector(`[name="${part}_length"]`)?.value || '4', 10);
            }

            function getTypeValue(part) {
                const type = form.querySelector(`[name="${part}_type"]`)?.value || 'manual text';
                const value = form.querySelector(`[name="${part}_value"]`)?.value || '';

                switch (type) {
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
                // Only include part if it's shown and has a value
                if (!isPartShown(part)) return;

                const partValue = getTypeValue(part);
                const nextPart = part === 'prefix' ? 'number' : (part === 'number' ? 'suffix' : null);
                const nextValue = nextPart && isPartShown(nextPart) ? getTypeValue(nextPart) : '';
                const separator = separatorField ? (form.querySelector(`[name="${separatorField}"]`)?.value ||
                    'none') : 'none';

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
                form.id === 'billing-serial-form' ? 'billing-preview' :
                form.id === 'quotation-serial-form' ? 'quotation-preview' :
                form.id === 'order-serial-form' ? 'order-preview' :
                form.id === 'payment-receipt-serial-form' ? 'payment-receipt-preview' : 'preview';
            const previewEl = document.getElementById(previewId);
            if (previewEl) {
                previewEl.textContent = parts.join('') || 'Configure serial first';
            }
        }
    });
</script>
