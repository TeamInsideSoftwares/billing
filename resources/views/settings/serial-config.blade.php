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

<div class="row g-2">

    <!-- Proforma Invoice Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="bg-white p-2 rounded-3 h-100">
            <div class="mb-2">
                <h6 class="fw-semibold text-primary small lh-sm mb-0">Proforma Invoice Serial</h6>
            </div>
            <form method="POST" action="{{ route('serial.config.update') }}" id="proforma-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="serial-number-configuration">
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

                <div class="mb-2">
                    <label class="form-label small lh-sm fw-semibold mb-1">Preview</label>
                    <div class="bg-primary-subtle rounded-2 px-3 py-2 text-center">
                        <span id="proforma-preview"
                            class="fw-bold text-primary font-monospace">
                            {{ $proformaPreview }}
                        </span>
                    </div>
                </div>
                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $isEnabled = isset($proformaSerialConfig)
                            ? ($proformaSerialConfig->{$part . '_show'} ?? 1)
                            : true;
                        $selectedType = isset($proformaSerialConfig)
                            ? ($proformaSerialConfig->{$part . '_type'} ??
                                ($part == 'number' ? 'auto increment' : 'manual text'))
                            : ($part == 'number' ? 'auto increment' : 'manual text');
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $valLabel = $selectedType == 'auto increment'
                            ? 'Start From'
                            : 'Value';
                    @endphp
                    <div class="position-relative p-2 mb-2 rounded-3 bg-DarkLight serial-part-row {{ !$isEnabled ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor: pointer;">
                            <input type="checkbox"
                                class="form-check-input part-toggle serial-part-toggle mt-0 border-primary border-2" style="cursor: pointer;"
                                data-part="{{ $part }}"
                                name="{{ $part }}_show"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}>
                            <span class="fw-semibold small lh-sm mb-0">
                                {{ $label }}
                            </span>
                        </label>
                        <div class="row g-2 align-items-end serial-part-grid">
                            <!-- Type -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-4' }}">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Type
                                </label>
                                <select name="{{ $part }}_type"
                                    class="form-select form-select-sm serial-type-select"
                                    data-part="{{ $part }}"
                                    data-target="proforma">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ $selectedType == $val ? 'selected' : '' }}>
                                            {{ $text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Value -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-8' }}">

                                <div class="input-group-val">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        {{ $valLabel }}
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_value"
                                        class="form-control form-control-sm serial-input"
                                        placeholder="Enter value"
                                        value="{{ $part == 'number' && $selectedType == 'auto increment'
                                            ? $proformaSerialConfig->number_value ?? 1
                                            : $proformaSerialConfig->{$part . '_value'} ?? '' }}">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Length
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_length"
                                        class="form-control form-control-sm serial-input"
                                        value="{{ $proformaSerialConfig->{$part . '_length'} ?? 4 }}">
                                </div>
                            </div>
                            <!-- Separator -->
                            @if ($hasSeparator)
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Separator
                                    </label>
                                    <select name="{{ $part }}_separator"
                                        class="form-select form-select-sm">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($proformaSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="bg-warning-subtle border border-warning-subtle rounded-2 p-2 mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="reset_on_fy" id="proforma-reset-on-fy" value="1" class="form-check-input serial-warning-checkbox border-primary border-2 mt-0" style="cursor: pointer;" 
                        {{ $proformaSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}>
                    <label for="proforma-reset-on-fy" class="form-label small text-dark fw-medium mb-0" style="cursor: pointer;">
                        Reset Proforma Serial Number when new FY starts
                    </label>
                </div> 
                <div class="mainForm">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">Save Proforma Serial <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tax Invoice Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="bg-white p-2 rounded-3 h-100">
            <div class="mb-2">
                <h6 class="fw-semibold text-primary small lh-sm mb-0">Tax Invoice Serial</h6>
            </div>
            <form method="POST" action="{{ route('serial.config.update') }}" id="billing-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="serial-number-configuration">
                <input type="hidden" name="document_type" value="tax_invoice">
                @if (isset($taxInvoiceSerialConfig))
                    <input type="hidden" name="serial_configid"
                        value="{{ $taxInvoiceSerialConfig->serial_configid }}">
                @endif
                <input type="hidden" name="accountid" value="{{ $account->accountid }}">

                @php
                    $billingPreview =
                        isset($taxInvoiceSerialConfig) &&
                        method_exists($taxInvoiceSerialConfig, 'generateNextSerialNumber')
                            ? $taxInvoiceSerialConfig->generateNextSerialNumber()
                            : 'Configure serial first';
                @endphp

                <div class="mb-2">
                    <label class="form-label small lh-sm fw-semibold mb-1">Preview</label>
                    <div class="bg-primary-subtle rounded-2 px-3 py-2 text-center">
                        <span id="billing-preview"
                            class="fw-bold text-primary font-monospace">
                            {{ $billingPreview }}
                        </span>
                    </div>
                </div>
                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $isEnabled = isset($taxInvoiceSerialConfig)
                            ? ($taxInvoiceSerialConfig->{$part . '_show'} ?? 1)
                            : true;
                        $selectedType = isset($taxInvoiceSerialConfig)
                            ? ($taxInvoiceSerialConfig->{$part . '_type'} ??
                                ($part == 'number' ? 'auto increment' : 'manual text'))
                            : ($part == 'number' ? 'auto increment' : 'manual text');
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $valLabel = $selectedType == 'auto increment'
                            ? 'Start From'
                            : 'Value';
                    @endphp
                    <div class="position-relative p-2 mb-2 rounded-3 bg-DarkLight serial-part-row {{ !$isEnabled ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor: pointer;">
                            <input type="checkbox"
                                id="billing-{{ $part }}-toggle"
                                class="form-check-input part-toggle serial-part-toggle mt-0 border-primary border-2" style="cursor: pointer;"
                                data-part="{{ $part }}"
                                name="{{ $part }}_show"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}>
                            <span class="fw-semibold small lh-sm mb-0">
                                {{ $label }}
                            </span>
                        </label>
                        <div class="row g-2 align-items-end serial-part-grid">
                            <!-- Type -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-4' }}">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Type
                                </label>
                                <select name="{{ $part }}_type"
                                    class="form-select form-select-sm serial-type-select"
                                    data-part="{{ $part }}"
                                    data-target="billing">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ $selectedType == $val ? 'selected' : '' }}>
                                            {{ $text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Value -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-8' }}">

                                <div class="input-group-val">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        {{ $valLabel }}
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_value"
                                        class="form-control form-control-sm serial-input"
                                        placeholder="Enter value"
                                        value="{{ $part == 'number' && $selectedType == 'auto increment'
                                            ? $taxInvoiceSerialConfig->number_value ?? 1
                                            : $taxInvoiceSerialConfig->{$part . '_value'} ?? '' }}">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Length
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_length"
                                        class="form-control form-control-sm serial-input"
                                        value="{{ $taxInvoiceSerialConfig->{$part . '_length'} ?? 4 }}">
                                </div>
                            </div>
                            <!-- Separator -->
                            @if ($hasSeparator)
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Separator
                                    </label>
                                    <select name="{{ $part }}_separator"
                                        class="form-select form-select-sm">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($taxInvoiceSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="bg-warning-subtle border border-warning-subtle rounded-2 p-2 mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="reset_on_fy" id="billing-reset-on-fy" value="1" style="cursor: pointer;"
                        {{ $taxInvoiceSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="form-check-input serial-warning-checkbox border-primary border-2">
                    <label for="billing-reset-on-fy" class="form-label small text-dark fw-medium mb-0" style="cursor: pointer;">
                        Reset Tax Serial Number when new FY starts
                    </label>
                </div>
                <div class="mainForm">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">Save Tax Invoice Serial <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quotation Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="bg-white p-2 rounded-3 h-100">
            <div class="mb-2">
                <h6 class="fw-semibold text-primary small lh-sm mb-0">Quotation Serial</h6>
            </div>
            <form method="POST" action="{{ route('serial.config.update') }}" id="quotation-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="serial-number-configuration">
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

                <div class="mb-2">
                    <label class="form-label small lh-sm fw-semibold mb-1">Preview</label>
                    <div class="bg-primary-subtle rounded-2 px-3 py-2 text-center">
                        <span id="quotation-preview"
                            class="fw-bold text-primary font-monospace">
                            {{ $quotationPreview }}
                        </span>
                    </div>
                </div>
                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $isEnabled = isset($quotationSerialConfig)
                            ? ($quotationSerialConfig->{$part . '_show'} ?? 1)
                            : true;
                        $selectedType = isset($quotationSerialConfig)
                            ? ($quotationSerialConfig->{$part . '_type'} ??
                                ($part == 'number' ? 'auto increment' : 'manual text'))
                            : ($part == 'number' ? 'auto increment' : 'manual text');
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $valLabel = $selectedType == 'auto increment'
                            ? 'Start From'
                            : 'Value';
                    @endphp
                    <div class="position-relative p-2 mb-2 rounded-3 bg-DarkLight serial-part-row {{ !$isEnabled ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor: pointer;">
                            <input type="checkbox"
                                id="quotation-{{ $part }}-toggle"
                                class="form-check-input part-toggle serial-part-toggle mt-0 border-primary border-2" style="cursor: pointer;"
                                data-part="{{ $part }}"
                                name="{{ $part }}_show"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}>
                            <span class="fw-semibold small lh-sm mb-0">
                                {{ $label }}
                            </span>
                        </label>
                        <div class="row g-2 align-items-end serial-part-grid">
                            <!-- Type -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-4' }}">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Type
                                </label>
                                <select name="{{ $part }}_type"
                                    class="form-select form-select-sm serial-type-select"
                                    data-part="{{ $part }}"
                                    data-target="quotation">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ $selectedType == $val ? 'selected' : '' }}>
                                            {{ $text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Value -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-8' }}">

                                <div class="input-group-val">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        {{ $valLabel }}
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_value"
                                        class="form-control form-control-sm serial-input"
                                        placeholder="Enter value"
                                        value="{{ $part == 'number' && $selectedType == 'auto increment'
                                            ? $quotationSerialConfig->number_value ?? 1
                                            : $quotationSerialConfig->{$part . '_value'} ?? '' }}">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Length
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_length"
                                        class="form-control form-control-sm serial-input"
                                        value="{{ $quotationSerialConfig->{$part . '_length'} ?? 4 }}">
                                </div>
                            </div>
                            <!-- Separator -->
                            @if ($hasSeparator)
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Separator
                                    </label>
                                    <select name="{{ $part }}_separator"
                                        class="form-select form-select-sm">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($quotationSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="bg-warning-subtle border border-warning-subtle rounded-2 p-2 mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="reset_on_fy" id="quotation-reset-on-fy" value="1" style="cursor: pointer;"
                        {{ $quotationSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="form-check-input serial-warning-checkbox border-primary border-2">
                    <label for="quotation-reset-on-fy" class="form-label small text-dark fw-medium mb-0" style="cursor: pointer;">
                        Reset Quotation Serial Number when new FY starts
                    </label>
                </div>
                <div class="mainForm">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">Save Quotation Serial <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="bg-white p-2 rounded-3 h-100">
            <div class="mb-2">
                <h6 class="fw-semibold text-primary small lh-sm mb-0">Order Serial</h6>
            </div>
            <form method="POST" action="{{ route('serial.config.update') }}" id="order-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="serial-number-configuration">
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

                <div class="mb-2">
                    <label class="form-label small lh-sm fw-semibold mb-1">Preview</label>
                    <div class="bg-primary-subtle rounded-2 px-3 py-2 text-center">
                        <span id="order-preview"
                            class="fw-bold text-primary font-monospace">
                            {{ $orderPreview }}
                        </span>
                    </div>
                </div>
                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $isEnabled = isset($orderSerialConfig)
                            ? ($orderSerialConfig->{$part . '_show'} ?? 1)
                            : true;
                        $selectedType = isset($orderSerialConfig)
                            ? ($orderSerialConfig->{$part . '_type'} ??
                                ($part == 'number' ? 'auto increment' : 'manual text'))
                            : ($part == 'number' ? 'auto increment' : 'manual text');
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $valLabel = $selectedType == 'auto increment'
                            ? 'Start From'
                            : 'Value';
                    @endphp
                    <div class="position-relative p-2 mb-2 rounded-3 bg-DarkLight serial-part-row {{ !$isEnabled ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor: pointer;">
                            <input type="checkbox"
                                id="order-{{ $part }}-toggle"
                                class="form-check-input part-toggle serial-part-toggle mt-0 border-primary border-2" style="cursor: pointer;"
                                data-part="{{ $part }}"
                                name="{{ $part }}_show"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}>
                            <span class="fw-semibold small lh-sm mb-0">
                                {{ $label }}
                            </span>
                        </label>
                        <div class="row g-2 align-items-end serial-part-grid">
                            <!-- Type -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-4' }}">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Type
                                </label>
                                <select name="{{ $part }}_type"
                                    class="form-select form-select-sm serial-type-select"
                                    data-part="{{ $part }}"
                                    data-target="order">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ $selectedType == $val ? 'selected' : '' }}>
                                            {{ $text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Value -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-8' }}">

                                <div class="input-group-val">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        {{ $valLabel }}
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_value"
                                        class="form-control form-control-sm serial-input"
                                        placeholder="Enter value"
                                        value="{{ $part == 'number' && $selectedType == 'auto increment'
                                            ? $orderSerialConfig->number_value ?? 1
                                            : $orderSerialConfig->{$part . '_value'} ?? '' }}">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Length
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_length"
                                        class="form-control form-control-sm serial-input"
                                        value="{{ $orderSerialConfig->{$part . '_length'} ?? 4 }}">
                                </div>
                            </div>
                            <!-- Separator -->
                            @if ($hasSeparator)
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Separator
                                    </label>
                                    <select name="{{ $part }}_separator"
                                        class="form-select form-select-sm">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($orderSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="bg-warning-subtle border border-warning-subtle rounded-2 p-2 mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="reset_on_fy" id="order-reset-on-fy" value="1" style="cursor: pointer;"
                        {{ $orderSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="form-check-input serial-warning-checkbox border-primary border-2">
                    <label for="order-reset-on-fy" class="form-label small text-dark fw-medium mb-0" style="cursor: pointer;">
                        Reset Order Serial Number when new FY starts
                    </label>
                </div>
                <div class="mainForm">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">Save Order Serial <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Receipt Serial Config -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="bg-white p-2 rounded-3 h-100">
            <div class="mb-2">
                <h6 class="fw-semibold text-primary small lh-sm mb-0">Payment Receipt Number</h6>
            </div>
            <form method="POST" action="{{ route('serial.config.update') }}" id="payment-receipt-serial-form">
                @csrf
                <input type="hidden" name="from_tab" value="serial-number-configuration">
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

                <div class="mb-2">
                    <label class="form-label small lh-sm fw-semibold mb-1">Preview</label>
                    <div class="bg-primary-subtle rounded-2 px-3 py-2 text-center">
                        <span id="payment-receipt-preview"
                            class="fw-bold text-primary font-monospace">
                            {{ $paymentReceiptPreview }}
                        </span>
                    </div>
                </div>
                @foreach (['prefix' => 'First Value', 'number' => 'Second Value', 'suffix' => 'Third Value'] as $part => $label)
                    @php
                        $isEnabled = isset($paymentReceiptSerialConfig)
                            ? ($paymentReceiptSerialConfig->{$part . '_show'} ?? 1)
                            : true;
                        $selectedType = isset($paymentReceiptSerialConfig)
                            ? ($paymentReceiptSerialConfig->{$part . '_type'} ??
                                ($part == 'number' ? 'auto increment' : 'manual text'))
                            : ($part == 'number' ? 'auto increment' : 'manual text');
                        $hasSeparator = in_array($part, ['prefix', 'number']);
                        $valLabel = $selectedType == 'auto increment'
                            ? 'Start From'
                            : 'Value';
                    @endphp
                    <div class="position-relative p-2 mb-2 rounded-3 bg-DarkLight serial-part-row {{ !$isEnabled ? 'serial-part-row--disabled' : '' }}"
                        data-part="{{ $part }}">
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor: pointer;">
                            <input type="checkbox"
                                id="payment-receipt-{{ $part }}-toggle"
                                class="form-check-input part-toggle serial-part-toggle mt-0 border-primary border-2" style="cursor: pointer;"
                                data-part="{{ $part }}"
                                name="{{ $part }}_show"
                                value="1"
                                {{ $isEnabled ? 'checked' : '' }}>
                            <span class="fw-semibold small lh-sm mb-0">
                                {{ $label }}
                            </span>
                        </label>
                        <div class="row g-2 align-items-end serial-part-grid">
                            <!-- Type -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-4' }}">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                    Type
                                </label>
                                <select name="{{ $part }}_type"
                                    class="form-select form-select-sm serial-type-select"
                                    data-part="{{ $part }}"
                                    data-target="payment-receipt">
                                    @foreach ($serialOptions as $val => $text)
                                        <option value="{{ $val }}"
                                            {{ $selectedType == $val ? 'selected' : '' }}>
                                            {{ $text }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Value -->
                            <div class="{{ $hasSeparator ? 'col-12 col-md-4' : 'col-12 col-md-8' }}">

                                <div class="input-group-val">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        {{ $valLabel }}
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_value"
                                        class="form-control form-control-sm serial-input"
                                        placeholder="Enter value"
                                        value="{{ $part == 'number' && $selectedType == 'auto increment'
                                            ? $paymentReceiptSerialConfig->number_value ?? 1
                                            : $paymentReceiptSerialConfig->{$part . '_value'} ?? '' }}">
                                </div>
                                <div class="input-group-len d-none">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Length
                                    </label>
                                    <input type="text"
                                        name="{{ $part }}_length"
                                        class="form-control form-control-sm serial-input"
                                        value="{{ $paymentReceiptSerialConfig->{$part . '_length'} ?? 4 }}">
                                </div>
                            </div>
                            <!-- Separator -->
                            @if ($hasSeparator)
                                <div class="col-12 col-md-4">
                                    <label class="form-label small lh-sm fw-semibold text-dark mb-1">
                                        Separator
                                    </label>
                                    <select name="{{ $part }}_separator"
                                        class="form-select form-select-sm">
                                        @foreach ($sepOptions as $val => $text)
                                            <option value="{{ $val }}"
                                                {{ ($paymentReceiptSerialConfig->{$part . '_separator'} ?? 'none') == $val ? 'selected' : '' }}>
                                                {{ $text }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="bg-warning-subtle border border-warning-subtle rounded-2 p-2 mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                    <input type="checkbox" name="reset_on_fy" id="payment-receipt-reset-on-fy" value="1" style="cursor: pointer;"
                        {{ $paymentReceiptSerialConfig->reset_on_fy ?? false ? 'checked' : '' }}
                        class="form-check-input serial-warning-checkbox border-primary border-2">
                    <label for="payment-receipt-reset-on-fy" class="form-label small text-dark fw-medium mb-0" style="cursor: pointer;">
                        Reset Payment Receipt Serial Number when new FY starts
                    </label>
                </div>
                <div class="mainForm">
                    <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">Save Payment Receipt Serial <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                </div>
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
            row.classList.toggle('opacity-50', !isChecked);
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
