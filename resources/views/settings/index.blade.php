@extends('layouts.app')

@section('content')
@php
$isMessageTemplateValidation =
$errors->any() &&
(old('template_type') !== null ||
old('channel') !== null ||
old('template_id') !== null ||
session()->has('mt_error_toast'));
$isFinancialYearValidation =
$errors->any() &&
(old('year_start') !== null ||
old('year_end') !== null ||
old('fy_prefix_type') !== null ||
old('fy_prefix_value') !== null ||
old('fy_number_start') !== null);
$isBillingDetailsValidation =
$errors->any() &&
(old('account_bdid') !== null ||
old('billing_name') !== null ||
old('billing_from_email') !== null ||
old('authorize_signatory') !== null ||
old('gstin') !== null ||
old('signature_upload') !== null);
$isBusinessInfoValidation =
$errors->any() &&
!($isMessageTemplateValidation || $isFinancialYearValidation || $isBillingDetailsValidation);
$activeSettingsTab = request('t', 'personal');

if ($isFinancialYearValidation) {
$activeSettingsTab = 'financial-year';
} elseif ($isMessageTemplateValidation) {
$activeSettingsTab = 'message-templates';
} elseif ($isBillingDetailsValidation) {
$activeSettingsTab = 'billing-details';
}
@endphp

<section class="section-bar">
    <div></div>
</section>

<div class="settings-page position-relative bg-white p-2 rounded-3">
    <!-- Tabs Wrapper -->
    <ul class="nav nav-underline d-inline-flex mb-3 settings-tab-group border-bottom rounded-3 gap-0" role="tablist"> 
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'personal' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#personal" role="tab" aria-controls="personal"
                aria-selected="true">
                <i class="far fa-building me-1"></i> Business Information
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'billing-details' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#billing-details" role="tab" aria-controls="billing-details"
                aria-selected="false">
                <i class="far fa-credit-card me-1"></i> Billing Details
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'financial-year' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#financial-year" role="tab" aria-controls="financial-year"
                aria-selected="false">
                <i class="far fa-calendar-alt me-1"></i> FY
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'serial-number-configuration' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#serial-number-configuration" role="tab" aria-controls="serial-number-configuration"
                aria-selected="false">
                <i class="fas fa-hashtag me-1"></i> Serial Number Configuration
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'config' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#config" role="tab" aria-controls="config" aria-selected="false">
                <i class="fas fa-key me-1"></i> Configuration Keys
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'message-templates' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#message-templates" role="tab" aria-controls="message-templates"
                aria-selected="false">
                <i class="far fa-paper-plane me-1"></i> Automation Templates
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'terms-conditions' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#terms-conditions" role="tab" aria-controls="terms-conditions"
                aria-selected="false">
                <i class="far fa-file-alt me-1"></i> Terms &amp; Conditions
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'holidays' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#holidays" role="tab" aria-controls="holidays" aria-selected="false">
                <i class="far fa-calendar-alt me-1"></i> Holidays & Weekends
            </button>
        </li>
        @if ($account->allow_multi_taxation)
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn {{ $activeSettingsTab === 'taxes' ? 'rounded-0 text-primary bg-primary-subtle border-primary fw-bold active' : 'rounded-0 text-primary bg-transparent border-transparent' }}"
                data-bs-toggle="tab" data-bs-target="#taxes" role="tab" aria-controls="taxes" aria-selected="false">
                <i class="fas fa-percent me-1"></i> Taxes
            </button>
        </li>
        @endif
    </ul>

    <div class="tab-content settings-tab-content">
        @include('settings.tabs.personal')

        @include('settings.tabs.billing-details')

        @include('settings.tabs.financial-year')

        <!-- Serial Number Configuration -->
        <div id="serial-number-configuration" class="tab-pane fade {{ $activeSettingsTab === 'serial-number-configuration' ? 'show active' : '' }}" role="tabpanel">
            <div class="bg-light p-2 rounded-3 h-100">
                <div class="row g-2 align-items-stretch">
                    <div class="col-12 col-md-12"> 
                        <div class="meta-info ps-2">
                            <strong class="fw-bold fs-5 lh-sm">Serial Number Configuration</strong>
                            <p class="small text-dark mb-0">Configure how invoice and quotation numbers are generated.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-12">
                        @include('settings.serial-config')
                    </div>
                </div>
            </div>
        </div>

        @include('settings.tabs.config')

        @include('settings.tabs.message-templates')

        @include('settings.tabs.terms-conditions')

        @include('settings.tabs.taxes')

        @include('settings.tabs.holidays')

    </div>
</div>




    <script>
        function startEditTax(el) {
            var form = document.getElementById('tax-form');
            form.action = '{{ url('settings/taxes') }}/' + el.dataset.id;
            var existingMethod = form.querySelector('input[name="_method"]');
            if (existingMethod) existingMethod.remove();
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_method';
            input.value = 'PATCH';
            form.prepend(input);
            document.getElementById('tax-rate-input').value = el.dataset.rate;
            document.getElementById('tax-type-select').value = el.dataset.type;
            document.getElementById('tax-form-title').textContent = 'Edit Tax (' + el.dataset.id + ')';
            document.getElementById('tax-form-btn').textContent = 'Update';
            document.getElementById('tax-form-cancel').classList.remove('d-none');
            document.getElementById('tax-form-card').classList.add('is-editing');
            form.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function cancelEditTax() {
            var form = document.getElementById('tax-form');
            form.action = '{{ route('taxes.store') }}';
            var existingMethod = form.querySelector('input[name="_method"]');
            if (existingMethod) existingMethod.remove();
            document.getElementById('tax-rate-input').value = '';
            document.getElementById('tax-type-select').selectedIndex = 0;
            document.getElementById('tax-form-title').textContent = 'Add New Tax';
            document.getElementById('tax-form-btn').textContent = 'Add Tax';
            document.getElementById('tax-form-cancel').classList.add('d-none');
            document.getElementById('tax-form-card').classList.remove('is-editing');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const __toastSeenAt = {};

            function showToastDedup(type, message, dedupMs = 1200) {
                const text = String(message || '').trim();
                if (!text) return;
                const key = `${type}:${text}`;
                const now = Date.now();
                if (__toastSeenAt[key] && (now - __toastSeenAt[key]) < dedupMs) return;
                __toastSeenAt[key] = now;

                if (typeof showToast === 'function') {
                    showToast(type, text);
                    return;
                }
            }

            function activateTab(tabId) {
                if (!tabId) return;

                const targetSelector = tabId.startsWith('#') ? tabId : `#${tabId}`;
                const tabTrigger = document.querySelector(`[data-bs-target="${targetSelector}"]`);

                if (!tabTrigger || !window.bootstrap || !bootstrap.Tab) {
                    return;
                }

                bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
            }

            const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabButtons.forEach((button) => {
                button.addEventListener('shown.bs.tab', function (event) {
                    const targetId = (event.target.getAttribute('data-bs-target') || '').replace('#', '');
                    if (!targetId) return;

                    const isTcSubTab = event.target.classList.contains('tc-type-tab');
                    const isHolidaySubTab = event.target.classList.contains('holiday-type-tab');
                    const isSubTab = isTcSubTab || isHolidaySubTab;
                    const url = new URL(window.location.href);

                    if (!isSubTab) {
                        if (targetId !== 'terms-conditions') {
                            url.searchParams.delete('t');
                        } else {
                            if (!url.searchParams.get('t')) {
                                url.searchParams.set('t', 'billing');
                            }
                        }
                        url.hash = `#${targetId}`;
                    } else if (isTcSubTab) {
                        const type = targetId.replace('-tc', '');
                        url.searchParams.set('t', type);
                    }
                    window.history.replaceState(null, null, url.pathname + url.search + url.hash);

                    // Dynamically toggle active/inactive bootstrap classes
                    if (event.relatedTarget) {
                        const isRelSub = event.relatedTarget.classList.contains('tc-type-tab') || event.relatedTarget.classList.contains('holiday-type-tab');
                        event.relatedTarget.classList.remove(isRelSub ? 'rounded-0' : 'rounded-top', 'bg-primary-subtle', 'border-primary', 'fw-bold');
                        event.relatedTarget.classList.add('rounded-0', 'bg-transparent', 'border-transparent');
                    }
                    event.target.classList.add(isSubTab ? 'rounded-0' : 'rounded-top', 'bg-primary-subtle', 'border-primary', 'fw-bold');
                    if (!isSubTab) {
                        event.target.classList.remove('rounded-0');
                    }
                    event.target.classList.remove('bg-transparent', 'border-transparent');

                    document.dispatchEvent(new CustomEvent('settings:tab-activated', {
                        detail: { tabId: targetId }
                    }));
                });
            });

            // Financial Year Sync
            const fyStart = document.getElementById('fy_year_start');
            const fyEnd = document.getElementById('fy_year_end');

            if (fyStart && fyEnd) {
                fyStart.addEventListener('change', function () {
                    const selectedStart = parseInt(this.value);
                    fyEnd.value = selectedStart + 1;

                    // Limit end year options visibility for clarity
                    Array.from(fyEnd.options).forEach(opt => {
                        const optVal = parseInt(opt.value);
                        opt.hidden = optVal !== selectedStart + 1;
                    });
                });

                // Initialize display on load
                fyStart.dispatchEvent(new Event('change'));
            }

            function activateSubTab(type) {
                if (!type) return;
                const subTabTrigger = document.querySelector(`.tc-type-tab[data-bs-target="#${type}-tc"]`);
                if (subTabTrigger) {
                    bootstrap.Tab.getOrCreateInstance(subTabTrigger).show();
                }
            }

            // Handle initial load from Hash
            const hash = window.location.hash.replace('#', '');
            const urlParams = new URLSearchParams(window.location.search);
            const encodedE = urlParams.get('e');
            let decodedE = null;
            try {
                decodedE = encodedE ? atob(encodedE) : null;
            } catch (e) {
                console.error('Failed to decode parameter e:', e);
            }

            const tcTypeFromUrl = (urlParams.get('t') || '').toLowerCase();

            if (hash) {
                activateTab(hash);
                if (hash === 'terms-conditions') {
                    setTimeout(function() { if (typeof initTinymce === 'function') initTinymce(); }, 300);
                    if (tcTypeFromUrl) {
                        activateSubTab(tcTypeFromUrl);
                    }
                }
            } else if (decodedE) {
                if (decodedE.startsWith('TC')) {
                    activateTab('terms-conditions');
                    setTimeout(function() { if (typeof initTinymce === 'function') initTinymce(); }, 300);
                    if (tcTypeFromUrl) {
                        activateSubTab(tcTypeFromUrl);
                    }
                }
                else if (decodedE.startsWith('SET')) activateTab('config');
                else if (decodedE.startsWith('ABD')) activateTab('billing-details');
                else activateTab('personal');
            } else {
                // Default to personal if no hash
                activateTab('personal');
            }

            // Serial mode toggle handler - OLD (kept for reference if still needed, but likely replaced)
            function handleSerialModeChange(radio) {
                const form = radio.closest('form');
                const isQuotation = form.action.includes('quotation');
                const prefix = isQuotation ? 'quotation' : 'billing';

                const autoGenDiv = document.getElementById(`${prefix}-auto-generate-options`);
                const autoIncDiv = document.getElementById(`${prefix}-auto-increment-options`);

                if (radio.value === 'auto_generate') {
                    if (autoGenDiv) autoGenDiv.classList.remove('is-hidden');
                    if (autoIncDiv) autoIncDiv.classList.add('is-hidden');
                } else if (radio.value === 'auto_increment') {
                    if (autoGenDiv) autoGenDiv.classList.add('is-hidden');
                    if (autoIncDiv) autoIncDiv.classList.remove('is-hidden');
                }
            }

            // NEW Serial Configuration Logic
            function updateSerialPreview(target) {
                const form = document.getElementById(`${target}-serial-form`);
                if (!form) return;

                const previewDiv = document.getElementById(`${target}-preview`);
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const date = String(now.getDate()).padStart(2, '0');

                function getSeparator(name) {
                    const field = form.querySelector(`[name="${name}"]`);
                    return field && field.value !== 'none' ? field.value : '';
                }

                function getPartValue(part) {
                    const type = form.querySelector(`[name="${part}_type"]`).value;
                    const valInputGroup = form.querySelector(`[name="${part}_value"]`).closest('.input-group-val');
                    const valLabel = valInputGroup.querySelector('.val-label');
                    const lengthInputGroup = form.querySelector(`[name="${part}_length"]`).closest(
                        '.input-group-len');

                    const valField = form.querySelector(`[name="${part}_value"]`);
                    const lengthField = form.querySelector(`[name="${part}_length"]`);

                    // Visibility & Label Logic
                    if (type === 'manual text') {
                        valInputGroup.classList.remove('is-hidden');
                        if (valLabel) valLabel.innerText = 'Enter value';
                        lengthInputGroup.classList.add('is-hidden');
                    } else if (type === 'auto generate') {
                        valInputGroup.classList.add('is-hidden');
                        lengthInputGroup.classList.remove('is-hidden');
                    } else if (type === 'auto increment') {
                        valInputGroup.classList.remove('is-hidden');
                        if (valLabel) valLabel.innerText = 'Start From';
                        lengthInputGroup.classList.add('is-hidden');
                    } else {
                        valInputGroup.classList.add('is-hidden');
                        lengthInputGroup.classList.add('is-hidden');
                    }

                    // Preview Logic
                    switch (type) {
                        case 'manual text':
                            return valField.value || (part === 'prefix' ? (target == 'billing' ? 'INV' : 'QUO') : (
                                part === 'suffix' ? '2026' : '1001'));
                        case 'date':
                            return `${year}-${month}-${date}`;
                        case 'year':
                            return `${year}`;
                        case 'month-year':
                            return `${month}-${year}`;
                        case 'date-month':
                            return `${date}-${month}`;
                        case 'auto increment':
                            return valField.value || '1';
                        case 'auto generate':
                            const genLen = parseInt(lengthField.value) || 4;
                            let result = '';
                            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                            for (let i = 0; i < genLen; i++) {
                                result += chars.charAt(Math.floor(Math.random() * chars.length));
                            }
                            return result;
                        default:
                            return '';
                    }
                }

                const prefix = getPartValue('prefix');
                const number = getPartValue('number');
                const suffix = getPartValue('suffix');

                const prefixSep = prefix ? getSeparator('prefix_separator') : '';
                const numberSep = suffix ? getSeparator('number_separator') : '';

                previewDiv.innerText = prefix + prefixSep + number + numberSep + suffix;
            }

            // Attach listeners to new serial fields
            document.querySelectorAll(
                '.serial-type-select, input[name$="_value"], input[name$="_length"], input[name$="_start"], select[name$="_separator"]'
            ).forEach(el => {
                el.addEventListener('input', function () {
                    const form = this.closest('form');
                    const target = form.id ? form.id.split('-')[0] : null;
                    if (target && (target === 'proforma' || target === 'billing' || target ===
                        'quotation')) {
                        updateSerialPreview(target);
                    }
                });
            });

            function updateFYPrefixPreview() {
                const form = document.getElementById('fy-prefix-form');
                if (!form) return;

                const previewDiv = document.getElementById('fy-prefix-preview');
                const type = form.querySelector('[name="fy_prefix_type"]').value;
                const prefixSep = form.querySelector('[name="fy_prefix_sep"]').value;
                const prefixValue = form.querySelector('[name="fy_prefix_value"]').value || 'FY';
                const numberSep = form.querySelector('[name="fy_number_sep"]').value;
                const numberValue = '001'; // placeholder
                const year = new Date().getFullYear();

                let previewText = prefixValue;
                if (prefixSep !== 'none') previewText += prefixSep;
                previewText += numberValue;
                if (numberSep !== 'none') previewText += numberSep;
                previewText += year;

                previewDiv.innerText = previewText;

                // Update label based on type
                const valLabel = document.getElementById('fy-val-label');
                if (type === 'value/number') {
                    valLabel.innerText = 'Enter value';
                } else {
                    valLabel.innerText = 'Fixed Value';
                }
            }



            // Initialize previews
            updateSerialPreview('proforma');
            updateSerialPreview('billing');
            updateSerialPreview('quotation');

            // Initialize field visibility on page load
            ['proforma', 'billing', 'quotation'].forEach(target => {
                ['prefix', 'number', 'suffix'].forEach(part => {
                    const form = document.getElementById(`${target}-serial-form`);
                    if (!form) return;

                    const typeSelect = form.querySelector(`[name="${part}_type"]`);
                    if (typeSelect) {
                        // Trigger change event to set initial visibility
                        typeSelect.dispatchEvent(new Event('input'));
                    }
                });
            });

            // Attach event listeners to old serial mode radios (if they still exist)
            document.querySelectorAll('input[name="serial_mode"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    handleSerialModeChange(this);
                });
            });

            // Initialize on page load - trigger for billing
            setTimeout(() => {
                const billingRadio = document.querySelector(
                    '#billing-details input[name="serial_mode"]:checked');
                if (billingRadio) {
                    handleSerialModeChange(billingRadio);
                }
            }, 100);

            // TinyMCE for Terms and Conditions Tab
            var tinymceInitialized = false;

            window.initTinymce = function () {
                if (tinymceInitialized || !window.tinymce) return;
                
                const pane = document.getElementById('terms-conditions');
                if (!pane || (!pane.classList.contains('show') && !pane.classList.contains('active'))) return;

                tinymceInitialized = true;
                tinymce.init({
                    license_key: 'gpl',
                    selector: '#settings_tc_content',
                    forced_root_block: ' ', // Prevents wrapping new text in <p>
                    menubar: false,
                    height: 200,
                    plugins: 'lists link table code autoresize',
                    toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat code',
                    setup: function (editor) {
                        editor.on('change', function () {
                            editor.save(); // keep textarea synchronized
                        });
                        editor.on('BeforeSetContent', function (e) {
                            e.content = e.content.replace(/<\/?p[^>]*>/gi, '');
                        });
                        editor.on('GetContent', function (e) {
                            e.content = e.content.replace(/<\/?p[^>]*>/gi, '');
                        });
                    }
                });

                // Trigger save before terms-conditions form submission
                const tcForm = document.querySelector('form[action*="terms-conditions"]');
                if (tcForm) {
                    tcForm.addEventListener('submit', function () {
                        tinymce.triggerSave();
                    });
                }
            };

            function waitForTinymce() {
                if (window.tinymce) {
                    window.initTinymce();
                } else {
                    setTimeout(waitForTinymce, 100);
                }
            }
            waitForTinymce();

            document.addEventListener('settings:tab-activated', function (e) {
                if (e.detail && e.detail.tabId === 'terms-conditions') {
                    if (window.tinymce) {
                        window.initTinymce();
                    } else {
                        setTimeout(window.initTinymce, 200);
                    }
                }
            });
        });

        // Signature preview function
        function previewSignature(input) {
            const file = input.files[0];
            const previewImg = document.getElementById('signature-preview-img');
            const dropZonePrompt = document.getElementById('sig-drop-zone-prompt');
            const dropZonePreview = document.getElementById('sig-drop-zone-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    dropZonePrompt.classList.add('d-none');
                    dropZonePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '#';
                dropZonePrompt.classList.remove('d-none');
                dropZonePreview.classList.add('d-none');
            }
        }

        function previewLogo(input) {
            const file = input.files[0];
            const previewImg = document.getElementById('logo-preview');
            const dropZonePrompt = document.getElementById('drop-zone-prompt');
            const dropZonePreview = document.getElementById('drop-zone-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    dropZonePrompt.classList.add('d-none');
                    dropZonePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                previewImg.src = '#';
                dropZonePrompt.classList.remove('d-none');
                dropZonePreview.classList.add('d-none');
            }
        }

        // Toggle fixed tax rate visibility based on multi-taxation toggle
        document.addEventListener('DOMContentLoaded', function () {
            // Company Logo Drag & Drop
            const logoInput = document.getElementById('logo-upload');
            const logoDropZone = document.getElementById('logo-drop-zone');
            const removeLogoBtn = document.getElementById('remove-logo-btn');
            if (logoInput && logoDropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    logoDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        logoDropZone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    logoDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        logoDropZone.classList.remove('dragover');
                    }, false);
                });

                logoDropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files && files[0]) {
                        logoInput.files = files;
                        previewLogo(logoInput);
                    }
                });

                if (removeLogoBtn) {
                    removeLogoBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        logoInput.value = '';
                        previewLogo(logoInput);
                    });
                }
            }

            // Signature Drag & Drop
            const sigInput = document.getElementById('billing-signature-upload');
            const sigDropZone = document.getElementById('sig-drop-zone');
            const removeSigBtn = document.getElementById('remove-signature-btn');
            if (sigInput && sigDropZone) {
                ['dragenter', 'dragover'].forEach(eventName => {
                    sigDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sigDropZone.classList.add('dragover');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    sigDropZone.addEventListener(eventName, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        sigDropZone.classList.remove('dragover');
                    }, false);
                });

                sigDropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files && files[0]) {
                        sigInput.files = files;
                        previewSignature(sigInput);
                    }
                });

                if (removeSigBtn) {
                    removeSigBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        sigInput.value = '';
                        previewSignature(sigInput);
                    });
                }
            }

            const multiTaxationCheckbox = document.querySelector('input[name="allow_multi_taxation"]');
            const fixedTaxSection = document.getElementById('fixed-tax-section');
            const openFixedTaxBtn = document.getElementById('open-fixed-tax-modal');

            if (multiTaxationCheckbox && fixedTaxSection) {
                multiTaxationCheckbox.addEventListener('change', function () {
                    const isEnabled = this.checked;

                    if (isEnabled) {
                        // Multi-taxation enabled - hide fixed tax field
                        fixedTaxSection.classList.add('is-hidden');
                    } else {
                        // Multi-taxation disabled - show fixed tax field
                        fixedTaxSection.classList.remove('is-hidden');
                    }
                });
            }

            // Open fixed tax rate modal using Bootstrap
            if (openFixedTaxBtn) {
                const fixedTaxModalEl = document.getElementById('fixedTaxRateModal');
                if (fixedTaxModalEl) {
                    const fixedTaxModal = new bootstrap.Modal(fixedTaxModalEl);
                    openFixedTaxBtn.addEventListener('click', function () {
                        fixedTaxModal.show();
                    });
                }
            }

            const templateForms = Array.from(document.querySelectorAll('.message-template-form'));
            const form = document.querySelector('.message-template-form');
            const typeTabs = Array.from(document.querySelectorAll('.mt-type-tab-btn'));
            const templateTypeLabels = @json($messageTemplateTypes);
            const defaultTemplateType = @json(array_key_first($messageTemplateTypes));
            const oldTemplateType = @json(old('template_type', session('mt_active_type')));
            const oldTemplateChannel = @json(old('channel', session('mt_active_channel')));
            const mtErrorToast = @json(session('mt_error_toast'));
            const mtStateKey = 'settings_message_template_state_v1';
            const templateContextMap = @json($templateContextMap ?? []);
            const templateVariableMap = {
                common: [{
                    key: 'client_business_name',
                    label: "Client's Company"
                },
                {
                    key: 'client_contact_person',
                    label: "Client's Contact"
                },
                {
                    key: 'business_name',
                    label: 'Your Business Name'
                },
                ],
                pi: [{
                    key: 'invoice_title',
                    label: ''
                },
                {
                    key: 'pi_number',
                    label: ''
                },
                {
                    key: 'ti_number',
                    label: ''
                },
                {
                    key: 'pi_link',
                    label: ''
                },
                {
                    key: 'ti_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                {
                    key: 'due_date',
                    label: ''
                },
                {
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_start_date',
                    label: ''
                },
                {
                    key: 'item_end_date',
                    label: ''
                },
                ],
                ti: [{
                    key: 'invoice_title',
                    label: ''
                },
                {
                    key: 'pi_number',
                    label: ''
                },
                {
                    key: 'ti_number',
                    label: ''
                },
                {
                    key: 'pi_link',
                    label: ''
                },
                {
                    key: 'ti_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                {
                    key: 'due_date',
                    label: ''
                },
                {
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_start_date',
                    label: ''
                },
                {
                    key: 'item_end_date',
                    label: ''
                },
                ],
                quotation: [{
                    key: 'quotation_title',
                    label: ''
                },
                {
                    key: 'quotation_number',
                    label: ''
                },
                {
                    key: 'quotation_link',
                    label: ''
                },
                {
                    key: 'total_amount',
                    label: ''
                },
                ],
                reminder: [{
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_description',
                    label: ''
                },
                {
                    key: 'days_left',
                    label: ''
                },
                {
                    key: 'order_number',
                    label: ''
                },
                {
                    key: 'order_start_date',
                    label: ''
                },
                {
                    key: 'order_end_date',
                    label: ''
                },
                ],
                expiry: [{
                    key: 'item_name',
                    label: ''
                },
                {
                    key: 'item_description',
                    label: ''
                },
                {
                    key: 'expiry_date',
                    label: ''
                },
                {
                    key: 'days_left',
                    label: ''
                },
                {
                    key: 'days_ago',
                    label: ''
                },
                {
                    key: 'order_number',
                    label: ''
                },
                {
                    key: 'order_start_date',
                    label: ''
                },
                {
                    key: 'order_end_date',
                    label: ''
                },
                ],
                payment_received: [{
                    key: 'payment_amount',
                    label: ''
                },
                {
                    key: 'currency',
                    label: 'Client currency'
                },
                {
                    key: 'payment_date',
                    label: ''
                },
                {
                    key: 'payment_mode',
                    label: 'How paid (Bank/Online/Cash)'
                },
                {
                    key: 'reference_number',
                    label: ''
                },
                {
                    key: 'invoice_number',
                    label: ''
                },
                {
                    key: 'invoice_title',
                    label: ''
                },
                ],
            };

            function renderTemplateVariableBadges(type) {
                const badgeContainers = document.querySelectorAll('.template-variable-badges');
                if (!badgeContainers.length) return;

                const common = templateVariableMap.common || [];
                const specific = templateVariableMap[type] || [];
                const tags = [...common, ...specific];
                const seen = new Set();

                const badgeHtmlArray = [];
                tags.forEach((tag) => {
                    if (!tag?.key || seen.has(tag.key)) return;
                    seen.add(tag.key);
                    badgeHtmlArray.push(`<span class="bg-light text-muted border px-2 py-1 small lh-sm fw-semibold rounded-pill">@{{ ${tag.key} }}${tag.label ? ` (${tag.label})` : ''}</span>`);
                });

                badgeContainers.forEach(container => {
                    container.innerHTML = badgeHtmlArray.join(' ');
                });
            }

            function saveMtState(type, channel) {
                try {
                    sessionStorage.setItem(mtStateKey, JSON.stringify({
                        type: type || '',
                        channel: channel || '',
                    }));
                } catch (e) { }
            }

            function loadMtState() {
                try {
                    const raw = sessionStorage.getItem(mtStateKey);
                    if (!raw) return null;
                    const parsed = JSON.parse(raw);
                    return parsed && typeof parsed === 'object' ? parsed : null;
                } catch (e) {
                    return null;
                }
            }

            function setTinyContent(textareaId, value) {
                if (window.tinymce && tinymce.get(textareaId)) {
                    const editor = tinymce.get(textareaId);
                    const content = value || '';
                    if (content && !/<[a-z][\s\S]*>/i.test(content)) {
                        editor.setContent(content.replace(/\r\n|\r|\n/g, '<br>'));
                    } else {
                        editor.setContent(content);
                    }
                    return;
                }
                const input = document.getElementById(textareaId);
                if (input) input.value = value || '';
            }

            function htmlToPlainText(value) {
                if (!value) return '';
                const withBreaks = String(value)
                    .replace(/<br\s*\/?>/gi, '\n')
                    .replace(/<\/p>/gi, '\n')
                    .replace(/<p[^>]*>/gi, '');
                const temp = document.createElement('div');
                temp.innerHTML = withBreaks;
                return (temp.textContent || temp.innerText || '').replace(/\n{3,}/g, '\n\n').trim();
            }

            function toggleTemplateBodyEditor(form, channel) {
                if (!form) return;
                const bodyInput = form.querySelector('.template-body-input');
                const nameInput = form.querySelector('.template-name-input');
                const nameRequiredMark = form.querySelector('.template-name-required-mark');
                const bodyRequiredMark = form.querySelector('.template-body-required-mark');
                const variableOnlyNote = form.querySelector('.template-variable-only-note');
                if (!bodyInput) return;

                const isEmail = channel === 'email';
                form.noValidate = !isEmail;
                if (nameInput) {
                    nameInput.required = isEmail;
                    if (!isEmail) nameInput.removeAttribute('required');
                    nameInput.setAttribute('aria-required', isEmail ? 'true' : 'false');
                    nameInput.setCustomValidity('');
                }
                bodyInput.required = false;
                bodyInput.removeAttribute('required');
                bodyInput.setAttribute('aria-required', 'false');
                bodyInput.setCustomValidity('');
                if (nameRequiredMark) nameRequiredMark.classList.toggle('is-hidden', !isEmail);
                if (bodyRequiredMark) bodyRequiredMark.classList.toggle('is-hidden', !isEmail);

                const isWhatsApp = channel === 'whatsapp';
                bodyInput.readOnly = isWhatsApp;
                if (isWhatsApp) {
                    bodyInput.classList.add('is-readonly-field', 'bg-light', 'text-muted');
                    bodyInput.setAttribute('tabindex', '-1');
                } else {
                    bodyInput.classList.remove('is-readonly-field', 'bg-light', 'text-muted');
                    bodyInput.removeAttribute('tabindex');
                }

                if (variableOnlyNote) {
                    variableOnlyNote.classList.toggle('is-hidden', isEmail);
                }

                if (!window.tinymce) return;

                const editor = tinymce.get(bodyInput.id);
                if (isEmail) {
                    const messageTemplatesTab = document.getElementById('message-templates');
                    const isTemplatesTabVisible = messageTemplatesTab?.classList.contains('active');
                    if (!isTemplatesTabVisible) return;
                    if (!editor) {
                        tinymce.init({
                            license_key: 'gpl',
                            selector: '#' + bodyInput.id,
                            menubar: false,
                            height: 280,
                            plugins: 'lists link table code autoresize',
                            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code',
                            init_instance_callback: function (ed) {
                                const curType = getCurrentTemplateType();
                                const ctx = templateContextMap[curType + '|' + channel] || null;
                                if (ctx && ctx.body) {
                                    setTinyContent(bodyInput.id, ctx.body);
                                }
                            },
                        });
                    }
                    return;
                }

                if (editor) {
                    editor.save();
                    editor.remove();
                }
                bodyInput.value = htmlToPlainText(bodyInput.value);
            }

            function ensureTemplateEditorReady(tries = 12) {
                const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
                if (!emailForm) return;

                if (window.tinymce) {
                    toggleTemplateBodyEditor(emailForm, 'email');
                    return;
                }

                if (tries <= 0) return;
                setTimeout(() => ensureTemplateEditorReady(tries - 1), 150);
            }

            function decodeTemplateBody(encodedBody) {
                if (!encodedBody) return '';
                try {
                    const binary = atob(encodedBody);
                    const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
                    return new TextDecoder('utf-8').decode(bytes);
                } catch (error) {
                    return encodedBody;
                }
            }

            function resetAllTemplateForms(type) {
                const currentType = type || defaultTemplateType;
                renderTemplateVariableBadges(currentType);

                const templateEditorsContainer = document.getElementById('template-editors-container');
                const consolidatedViewContainer = document.getElementById('consolidated-view-container');
                
                const emailCol = document.getElementById('email-form-col');
                const whatsappCol = document.getElementById('whatsapp-form-col');
                const smsCol = document.getElementById('sms-form-col');

                if (currentType === 'consolidated') {
                    if (templateEditorsContainer) templateEditorsContainer.classList.add('d-none');
                    if (consolidatedViewContainer) consolidatedViewContainer.classList.remove('d-none');
                    saveMtState(currentType); // Save state before returning
                    return; // Skip the rest of form resetting since it doesn't apply to consolidated
                } else {
                    if (templateEditorsContainer) templateEditorsContainer.classList.remove('d-none');
                    if (consolidatedViewContainer) consolidatedViewContainer.classList.add('d-none');
                    
                    if (currentType === 'reminder' || currentType === 'expiry') {
                        if (emailCol) emailCol.classList.add('d-none');
                        if (smsCol) smsCol.classList.add('d-none');
                        if (whatsappCol) {
                            whatsappCol.classList.remove('col-lg-4', 'col-lg-6');
                            whatsappCol.classList.add('col-lg-8'); // Expanded since email and sms are hidden
                        }
                    } else {
                        if (emailCol) emailCol.classList.remove('d-none');
                        if (smsCol) smsCol.classList.remove('d-none');
                        if (whatsappCol) {
                            whatsappCol.classList.remove('col-lg-6', 'col-lg-8');
                            whatsappCol.classList.add('col-lg-4');
                        }
                        if (smsCol) {
                            smsCol.classList.remove('col-lg-6', 'col-lg-8');
                            smsCol.classList.add('col-lg-4');
                        }
                    }
                }

                templateForms.forEach((form) => {
                    const channel = form.dataset.channel;
                    const channelInput = form.querySelector('.template-channel-input');
                    const typeInput = form.querySelector('input[name="template_type"]');
                    const templateIdInput = form.querySelector('.template-id-input');
                    const nameInput = form.querySelector('.template-name-input');
                    const subjectInput = form.querySelector('.template-subject-input');
                    const waTemplateIdInput = form.querySelector('.template-wa-template-id-input');
                    const externalIdInput = form.querySelector('.template-external-id-input');
                    const senderIdInput = form.querySelector('.template-sender-id-input');
                    const bodyInput = form.querySelector('.template-body-input');
                    const submitBtn = form.querySelector('.template-submit-btn');
                    const editorNote = form.querySelector('.template-editor-note-' + channel);
                    const methodInput = form.querySelector('input[name="_method"]');

                    const typeLabel = templateTypeLabels[currentType] || currentType.replace(/_/g, ' ').toUpperCase();
                    const channelLabel = channel.charAt(0).toUpperCase() + channel.slice(1);

                    if (typeInput) typeInput.value = currentType;
                    if (channelInput) channelInput.value = channel;
                    if (templateIdInput) templateIdInput.value = '';
                    if (methodInput) methodInput.remove();
                    if (submitBtn) {
                        submitBtn.innerHTML = 'Save Template <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                        submitBtn.className = 'btn btn-primary text-white fw-medium template-submit-btn';
                    }
                    if (editorNote) editorNote.textContent = 'One template per type.';
                    if (nameInput) {
                        nameInput.value = '';
                        nameInput.placeholder = typeLabel + ' ' + channelLabel + ' Template';
                    }
                    if (subjectInput) {
                        subjectInput.value = '';
                        subjectInput.placeholder = typeLabel + ' update for @{{ client_name }}';
                    }
                    if (externalIdInput) externalIdInput.value = '';
                    if (waTemplateIdInput) waTemplateIdInput.value = '';
                    if (senderIdInput) senderIdInput.value = '';
                    if (bodyInput) {
                        bodyInput.placeholder = 'Hi @{{ client_name }},\nPlease find the details below.';
                        setTinyContent(bodyInput.id, '');
                    }

                    const contextKey = currentType + '|' + channel;
                    const contextTemplate = templateContextMap[contextKey] || null;
                    if (contextTemplate) {
                        form.action = form.dataset.updateBase + '/' + encodeURIComponent(contextTemplate.templateid);
                        const newMethodInput = document.createElement('input');
                        newMethodInput.type = 'hidden';
                        newMethodInput.name = '_method';
                        newMethodInput.value = 'PATCH';
                        form.appendChild(newMethodInput);

                        if (templateIdInput) templateIdInput.value = contextTemplate.templateid || '';
                        if (nameInput) nameInput.value = contextTemplate.name || '';
                        if (subjectInput) subjectInput.value = contextTemplate.subject || '';
                        if (externalIdInput) externalIdInput.value = contextTemplate.template_id || '';
                        if (waTemplateIdInput) waTemplateIdInput.value = contextTemplate.template_id || '';
                        if (senderIdInput) senderIdInput.value = contextTemplate.sender_id || '';
                        if (bodyInput) setTinyContent(bodyInput.id, contextTemplate.body || '');
                        if (editorNote) editorNote.textContent = 'Editing existing template.';
                        if (submitBtn) submitBtn.innerHTML = 'Update Template <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                    } else {
                        form.action = form.dataset.storeAction;
                    }

                    toggleTemplateBodyEditor(form, channel);
                });

                saveMtState(currentType);
            }

            function setActiveTab(tabs, matchAttr, value) {
                tabs.forEach((tab) => {
                    const active = tab.dataset[matchAttr] === value;
                    tab.classList.toggle('is-active', active);
                    tab.classList.toggle('active', active);
                    
                    if (active) {
                        tab.classList.add('rounded-0', 'text-primary', 'bg-primary-subtle', 'border-primary', 'fw-bold');
                        tab.classList.remove('bg-transparent', 'border-transparent');
                    } else {
                        tab.classList.remove('bg-primary-subtle', 'border-primary', 'fw-bold');
                        tab.classList.add('rounded-0', 'bg-transparent', 'border-transparent');
                    }
                });
            }

            function getCurrentTemplateType() {
                const activeTypeTab = document.querySelector('.mt-type-tab-btn.is-active');
                return activeTypeTab?.dataset.type || defaultTemplateType;
            }

            typeTabs.forEach((tab) => {
                tab.addEventListener('click', function () {
                    setActiveTab(typeTabs, 'type', this.dataset.type);
                    resetAllTemplateForms(this.dataset.type);
                });
            });

            const persistedMtState = loadMtState();
            const initialTemplateType = (oldTemplateType && templateTypeLabels[oldTemplateType]) ?
                oldTemplateType :
                ((persistedMtState?.type && (templateTypeLabels[persistedMtState.type] || persistedMtState.type === 'consolidated')) ? persistedMtState.type :
                    defaultTemplateType);
            setActiveTab(typeTabs, 'type', initialTemplateType);
            resetAllTemplateForms(initialTemplateType);

            document.addEventListener('settings:tab-activated', function (event) {
                if (event.detail?.tabId !== 'message-templates') return;
                const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
                if (!emailForm) return;
                requestAnimationFrame(() => {
                    toggleTemplateBodyEditor(emailForm, 'email');
                });
            });

            const emailForm = document.querySelector('.message-template-form[data-channel="email"]');
            if (window.tinymce && document.querySelector('.template-body-input') && emailForm) {
                toggleTemplateBodyEditor(emailForm, 'email');
            }

            setActiveTab(typeTabs, 'type', initialTemplateType);
            resetAllTemplateForms(initialTemplateType);
            ensureTemplateEditorReady();

            const initialHash = window.location.hash.replace('#', '');
            if (initialHash) {
                const targetBtn = document.querySelector('.settings-tab-group button[data-bs-target="#' + initialHash + '"]');
                if (targetBtn) {
                    const tabInstance = new bootstrap.Tab(targetBtn);
                    tabInstance.show();
                }
                if (initialHash === 'message-templates') {
                    setTimeout(function () { ensureTemplateEditorReady(); }, 400);
                }
            }

            // Main tabs URL hash updates are handled in the global event listener above.

            templateForms.forEach((form) => {
                form.addEventListener('submit', function (event) {
                    const channel = form.dataset.channel;
                    const waTemplateIdInput = form.querySelector('.template-wa-template-id-input');
                    const templateIdInput = form.querySelector('.template-external-id-input');
                    const bodyInput = form.querySelector('.template-body-input');
                    const nameInput = form.querySelector('.template-name-input');

                    const isEmail = channel === 'email';
                    form.noValidate = !isEmail;
                    if (bodyInput) {
                        bodyInput.required = false;
                        bodyInput.removeAttribute('required');
                        bodyInput.setAttribute('aria-required', 'false');
                        bodyInput.setCustomValidity('');
                    }
                    if (nameInput) {
                        nameInput.required = isEmail;
                        if (!isEmail) nameInput.removeAttribute('required');
                        nameInput.setAttribute('aria-required', isEmail ? 'true' : 'false');
                        nameInput.setCustomValidity('');
                    }

                    if (channel === 'whatsapp' && waTemplateIdInput && templateIdInput) {
                        templateIdInput.value = waTemplateIdInput.value || '';
                    }
                    if (window.tinymce) tinymce.triggerSave();

                    if (isEmail && bodyInput) {
                        const plainTextBody = String(bodyInput.value || '')
                            .replace(/<br\s*\/?>/gi, '\n')
                            .replace(/<\/p>/gi, '\n')
                            .replace(/<[^>]+>/g, '')
                            .replace(/&nbsp;/gi, ' ')
                            .trim();

                        if (plainTextBody === '') {
                            event.preventDefault();
                            if (typeof showToastDedup === 'function') {
                                showToastDedup('error',
                                    'Message Body is required for Email templates.', 2200);
                            }
                            const editor = window.tinymce ? tinymce.get(bodyInput.id) : null;
                            if (editor) {
                                editor.focus();
                            } else {
                                bodyInput.focus();
                            }
                        }
                    }
                });
            });


            async function toggleTermStatusBadge(badgeEl) {
                const url = badgeEl?.dataset?.toggleUrl;
                if (!url) return;

                badgeEl.classList.add('is-updating');
                try {
                    const response = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || '',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Failed to update term status.');
                    }

                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        const isActive = !!data.is_active;
                        badgeEl.dataset.isActive = isActive ? '1' : '0';
                        badgeEl.textContent = isActive ? 'Active' : 'Inactive';
                        badgeEl.title = isActive ? 'Click to Deactivate' : 'Click to Activate';
                        badgeEl.classList.toggle('is-active', isActive);
                        badgeEl.classList.toggle('is-inactive', !isActive);

                        if (isActive) {
                            badgeEl.classList.remove('bg-secondary', 'text-white');
                            badgeEl.classList.add('bg02', 'color02');
                        } else {
                            badgeEl.classList.remove('bg02', 'color02');
                            badgeEl.classList.add('bg-secondary', 'text-white');
                        }

                        try {
                            const messageText = data.message || 'Term status updated.';
                            if (typeof showToast === 'function') {
                                showToastDedup('success', messageText);
                            }
                        } catch (e) { }
                    } else {
                        window.location.reload();
                    }
                } catch (e) {
                    try {
                        if (typeof showToast === 'function') {
                            showToastDedup('error', e.message || 'Failed to update term status.');
                        }
                    } catch (_e) { }
                } finally {
                    badgeEl.classList.remove('is-updating');
                }
            }

            document.querySelectorAll('.js-term-status-badge').forEach((badge) => {
                badge.addEventListener('click', async function () {
                    await toggleTermStatusBadge(this);
                });
                badge.addEventListener('keydown', async function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') return;
                    event.preventDefault();
                    await toggleTermStatusBadge(this);
                });
            });

            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>

    <script>
async function refreshTemplateFromCampio(channel) {
    const form = document.querySelector('form.message-template-form[data-channel="' + channel + '"]');
    if (!form) return;
    
    const templateIdInput = form.querySelector('input[name="template_id"]');
    const templateId = templateIdInput ? templateIdInput.value : null;

    if (!templateId) {
        alert('Please enter a Template ID first.');
        return;
    }

    try {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Refreshing...';
        btn.disabled = true;

        const response = await fetch('{{ route("message-templates.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                channel: channel,
                template_id: templateId
            })
        });
        const data = await response.json();
        if (data.success) {
            const bodyInput = form.querySelector('textarea[name="body"]');
            if (bodyInput) bodyInput.value = data.template.body;
            
            const nameInput = form.querySelector('input[name="name"]');
            if (nameInput && data.template.name) nameInput.value = data.template.name;
            
            if (typeof showToastDedup === 'function') {
                showToastDedup('success', 'Template refreshed successfully!');
            } else if (typeof window.showToast === 'function') {
                window.showToast('success', 'Template refreshed successfully!');
            } else {
                alert('Template refreshed successfully!');
            }
        } else {
            alert(data.message || 'Failed to refresh template');
        }
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    } catch (e) {
        alert('Error refreshing template');
        console.error(e);
        const btn = event.currentTarget;
        btn.innerHTML = '<i class="fas fa-sync-alt me-1"></i>Refresh template';
        btn.disabled = false;
    }
}
</script>
<!-- Holiday Modals -->
<div class="modal fade" id="addHolidayModal" tabindex="-1" aria-labelledby="addHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="addHolidayModalLabel">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form action="{{ route('holidays.store') }}" method="POST" class="mainForm">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Holiday Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Christmas">
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Date <span class="text-danger">*</span></label>
                                <input type="date" name="holiday_date" class="form-control" required>
                                <div class="form-check mt-2">
                                    <input class="form-check-input border-primary" type="checkbox" name="is_recurring" value="1" id="repeatAnnuallyCheck" style="cursor: pointer;">
                                    <label class="form-check-label small text-dark fw-medium" for="repeatAnnuallyCheck" style="cursor: pointer;">
                                        Repeat this holiday every year
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                Save Holiday <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkWeekendModal" tabindex="-1" aria-labelledby="bulkWeekendModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="bulkWeekendModalLabel">Weekend Policy Generator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form action="{{ route('holidays.bulk.store') }}" method="POST" class="mainForm">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="form-control" value="{{ date('Y') }}" min="2000" max="2100" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1 d-block">Sundays</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sundays" value="1" id="sundayCheck" checked>
                                    <label class="form-check-label" for="sundayCheck">
                                        All Sundays Off
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1 d-block">Saturdays</label>
                                <div class="form-check form-check-inline mb-2">
                                    <input class="form-check-input" type="checkbox" name="saturdays[]" value="1" id="sat1">
                                    <label class="form-check-label" for="sat1">1st</label>
                                </div>
                                <div class="form-check form-check-inline mb-2">
                                    <input class="form-check-input" type="checkbox" name="saturdays[]" value="2" id="sat2">
                                    <label class="form-check-label" for="sat2">2nd</label>
                                </div>
                                <div class="form-check form-check-inline mb-2">
                                    <input class="form-check-input" type="checkbox" name="saturdays[]" value="3" id="sat3">
                                    <label class="form-check-label" for="sat3">3rd</label>
                                </div>
                                <div class="form-check form-check-inline mb-2">
                                    <input class="form-check-input" type="checkbox" name="saturdays[]" value="4" id="sat4">
                                    <label class="form-check-label" for="sat4">4th</label>
                                </div>
                                <div class="form-check form-check-inline mb-2">
                                    <input class="form-check-input" type="checkbox" name="saturdays[]" value="5" id="sat5">
                                    <label class="form-check-label" for="sat5">5th</label>
                                </div>
                                <div class="form-text mt-1">Select which Saturdays of the month should be marked as weekends.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-end mt-2">
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium text-end">
                                Generate <i class="fas fa-magic btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
