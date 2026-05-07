@extends('layouts.app')

@section('content')
    @php
        $clientName = $invoice->client->business_name ?? $invoice->client->contact_name ?? 'Client';
        $clientEmail = $invoice->client->email ?? '';
        $hasTiNumber = !empty(trim((string) $invoice->ti_number));
        $displayDocNumber = $hasTiNumber
            ? (trim((string) $invoice->ti_number) !== '' ? $invoice->ti_number : $invoice->invoice_number)
            : (trim((string) $invoice->pi_number) !== '' ? $invoice->pi_number : $invoice->invoice_number);
        $defaultSubjectNumber = $hasTiNumber ? $invoice->ti_number : $invoice->pi_number;
    @endphp

    <section class="panel-card w-100 p-3">
        <div class="bg-light border rounded p-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('invoices.create', ['step' => 4, 'invoice_for' => $invoice->invoice_for, 'c' => $invoice->clientid, 'd' => $invoice->invoiceid, 'o' => $invoice->orderid]) }}"
                    class="secondary-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="vr"></div>
                <div class="d-inline-flex align-items-center justify-content-center rounded"
                    style="width:36px;height:36px;background:#e0e7ff;color:#4f46e5;">
                    <i class="fas fa-user"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-dark">{{ $clientName }}</div>
                    @if($clientEmail)
                        <div class="text-muted small">{{ $clientEmail }}</div>
                    @endif
                </div>
                <span class="invoice-number-badge">{{ $displayDocNumber }}</span>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-1">
            <h4 class="mb-0">Message Compose</h4>
            <span class="text-muted small">Select document type and channel before sending</span>
        </div>

        <!-- Type Tabs (PI, TI/DSI) -->
        <div class="type-tabs-wrap border-bottom mb-3">
            <div class="type-tabs d-flex gap-4">
                <button type="button"
                    class="type-tab-btn {{ (old('attachment_type', $prefillAttachmentType ?? $defaultType) === 'pi' || !(old('attachment_type', $prefillAttachmentType ?? $defaultType))) ? 'is-active' : '' }}"
                    data-type="pi">
                    PI (Proforma Invoice)
                </button>
                <button type="button"
                    class="type-tab-btn {{ old('attachment_type', $prefillAttachmentType ?? $defaultType) === 'ti' ? 'is-active' : '' }}"
                    data-type="ti" {{ !$hasTiNumber ? 'disabled' : '' }}>
                    TI/DSI (Tax Invoice)
                </button>
            </div>
        </div>

        <!-- Channel Pills -->
        <div class="channel-pills-wrap mb-4">
            <div class="d-flex gap-2">
                <button type="button" class="channel-pill-btn is-active" data-channel="email" id="channelBtnEmail">
                    <i class="fas fa-envelope mr-1"></i> Email
                </button>
                <button type="button" class="channel-pill-btn" data-channel="whatsapp" id="channelBtnWhatsapp">
                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                </button>
                <button type="button" class="channel-pill-btn" data-channel="sms" id="channelBtnSms">
                    <i class="fas fa-sms mr-1"></i> SMS
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert warning mb-3">
                <ul class="plain-list mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" id="composeForm" action="{{ route('invoices.email-compose.store', $invoice->invoiceid) }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="invoice_emailid" value="{{ $composeEmail->invoice_emailid ?? '' }}">
            <input type="hidden" name="channel" id="selectedChannel" value="{{ old('channel', $prefillChannel ?? 'email') }}">
            <input type="hidden" name="attachment_type" id="selectedType"
                value="{{ old('attachment_type', $prefillAttachmentType ?? $defaultType) }}">

            <div class="row g-3 align-items-start">
                <div class="col-12 col-xl-7">
                    <div class="email-fields">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="field-label">From</label>
                                <input type="email" name="from_email" value="{{ $fromEmail }}" class="input-full" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="field-label">To</label>
                                <input type="email" name="to_email" value="{{ $toEmail }}" class="input-full" readonly>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="field-label">Subject</label>
                                <input type="text" name="subject" id="emailSubjectInput"
                                    value="{{ old('subject', $prefillSubject ?? ('Invoice ' . ($defaultSubjectNumber ?: $invoice->invoice_number))) }}"
                                    class="input-full">
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                    </div>

                    <div class="whatsapp-sms-fields" style="display: none;">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="field-label">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $prefillPhone ?? '') }}" class="input-full"
                                    readonly>
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">Body</label>
                        <textarea name="body" id="emailBodyInput" rows="8"
                            class="input-full">{{ old('body', $prefillBody ?? $defaultBody) }}</textarea>
                        <div id="attachmentBodyHint" class="text-secondary small mt-1"></div>
                    </div>
                    <div class="mb-3 email-fields">
                        <label class="field-label">Extra Attachment (optional)</label>
                        <input type="file" name="custom_attachment" id="customAttachmentInput" class="input-full">
                        <div id="currentCustomAttachment" class="mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-4 pt-3 border-top">
                        <div class="email-actions">
                            <button type="submit" name="action" value="save" class="secondary-button">Save Email</button>
                            <button type="submit" name="action" value="send" class="primary-button">Send to Client</button>
                        </div>
                        <div class="whatsapp-actions" style="display: none;">
                            <button type="submit" name="action" value="save" class="secondary-button">Save WhatsApp Message</button>
                            <button type="submit" name="action" value="send" id="sendWhatsApp" class="primary-button"
                                style="background: #25d366; border-color: #25d366;">
                                <i class="fab fa-whatsapp mr-1"></i> Send via WhatsApp
                            </button>
                        </div>
                        <div class="sms-actions" style="display: none;">
                            <button type="submit" name="action" value="save" class="secondary-button">Save SMS</button>
                            <button type="submit" name="action" value="send" id="sendSms" class="primary-button">Send
                                SMS</button>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="border rounded overflow-hidden position-sticky" style="top:.8rem; background: #fff;">
                        <div class="bg-light border-bottom px-3 py-2 small fw-semibold">Raw Message</div>
                        <div class="p-3">
                            <pre id="previewRawBody" class="mb-0 mt-0 p-2 border rounded bg-light" style="min-height: 180px; white-space: pre-wrap; word-break: break-word;"></pre>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </section>

    <style>
        /* Modern Tabs & Pills */
        .type-tabs-wrap {
            margin-bottom: 1.25rem;
        }

        .type-tab-btn {
            border: none;
            background: transparent;
            padding: 0.75rem 0;
            font-size: 0.95rem;
            font-weight: 600;
            color: #64748b;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .type-tab-btn:hover:not(:disabled) {
            color: #1e293b;
        }

        .type-tab-btn.is-active {
            color: var(--brand);
        }

        .type-tab-btn.is-active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--brand);
            border-radius: 2px 2px 0 0;
        }

        .type-tab-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .channel-pills-wrap {
            margin-bottom: 2rem;
        }

        .channel-pill-btn {
            border: 1px solid #d1d5db;
            background: #fff;
            padding: 0.45rem 1.25rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .channel-pill-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .channel-pill-btn.is-active {
            background: #eff6ff;
            color: var(--brand);
            border-color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }

        .attachment-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .attachment-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .attachment-toggle span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .attachment-toggle input:checked+span {
            background: #eff6ff;
            border-color: var(--brand);
            color: var(--brand);
            box-shadow: 0 0 0 1px var(--brand);
        }

        .attachment-toggle input:disabled+span {
            opacity: 0.55;
            cursor: not-allowed;
            background: #f1f5f9;
        }

        .field-label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
        }

        .input-full {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 0.6rem 0.8rem;
            font-size: 0.9rem;
            width: 100%;
        }

        .input-full:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        #emailBodyInput {
            min-height: 400px !important;
        }

        .invoice-number-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>

    <script>
        (function () {
            const hasTiNumber = @json($hasTiNumber);
            const piPdfUrl = @json(route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']));
            const tiPdfUrl = @json(route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']));

            const typeBtns = Array.from(document.querySelectorAll('.type-tab-btn'));
            const channelBtns = Array.from(document.querySelectorAll('.channel-pill-btn'));

            const attachmentBodyHint = document.getElementById('attachmentBodyHint');
            const customAttachmentInput = document.getElementById('customAttachmentInput');
            const savedCustomAttachmentUrl = @json($customAttachmentUrl ?? null);
            const savedCustomAttachmentName = @json($customAttachmentName ?? null);
            const emailSubjectInput = document.getElementById('emailSubjectInput');
            const emailBodyInput = document.getElementById('emailBodyInput');
            const previewRawBody = document.getElementById('previewRawBody');
            const currentCustomAttachment = document.getElementById('currentCustomAttachment');
            const templatePicker = document.getElementById('templatePicker');
            const templatePickerSmsWa = document.getElementById('templatePickerSmsWa');

            const templateCatalog = @json($templateCatalog ?? []);
            const availableChannelsByType = @json($availableChannelsByType ?? []);
            const fallbackTemplatesByType = @json($fallbackTemplatesByType ?? []);

            let currentChannel = document.getElementById('selectedChannel').value || 'email';
            let currentType = document.getElementById('selectedType').value || 'pi';
            let dscPreviewUrl = savedCustomAttachmentUrl || null;
            let currentRawTemplateBody = '';
            const hasExistingDraft = !!(document.querySelector('input[name="invoice_emailid"]')?.value || '').trim();

            function refreshEmailPreview() {
                if (previewRawBody) {
                    const rawPreview = getPlainTextFromHtml(currentRawTemplateBody || '').trim();
                    const attachmentNotes = [];
                    if (currentType === 'pi') attachmentNotes.push('Proforma Invoice (PI).pdf');
                    if (currentType === 'ti' && hasTiNumber) attachmentNotes.push('Tax Invoice (TI).pdf');
                    const customFileName = customAttachmentInput?.files?.[0]?.name || savedCustomAttachmentName;
                    if (customFileName) attachmentNotes.push(customFileName);

                    let composed = rawPreview || '(No raw template body)';
                    if (attachmentNotes.length > 0 && currentChannel !== 'sms') {
                        composed += '\n\n[Documents Attached]\n- ' + attachmentNotes.join('\n- ');
                    }
                    previewRawBody.textContent = composed;
                }
            }

            function normalizeHtmlForEditor(html) {
                const value = (html || '').trim();
                if (!value) return '';
                if (!/<html[\s>]|<head[\s>]|<body[\s>]/i.test(value)) return value;

                const parser = new DOMParser();
                const doc = parser.parseFromString(value, 'text/html');
                const bodyHtml = doc.body ? doc.body.innerHTML : value;
                const headStyles = Array.from(doc.querySelectorAll('head style'))
                    .map((node) => node.outerHTML)
                    .join('\n');

                return (headStyles ? (headStyles + '\n') : '') + bodyHtml;
            }

            function getPlainTextFromHtml(html) {
                if (!html) return '';
                let text = html;

                // Handle basic WhatsApp markdown-like conversions
                text = text.replace(/\\r\\n/g, '\n');
                text = text.replace(/\\n/g, '\n');
                text = text.replace(/<br\s*\/?>/gi, '\n');
                text = text.replace(/<\/(div|li|h[1-6])>/gi, '\n');
                text = text.replace(/<(ul|ol)[^>]*>/gi, '\n');
                text = text.replace(/<\/p>/gi, '\n');
                text = text.replace(/<p>/gi, '');
                text = text.replace(/<strong>(.*?)<\/strong>/gi, '*$1*');
                text = text.replace(/<b>(.*?)<\/b>/gi, '*$1*');
                text = text.replace(/<em>(.*?)<\/em>/gi, '_$1_');
                text = text.replace(/<i>(.*?)<\/i>/gi, '_$1_');
                text = text.replace(/<strike>(.*?)<\/strike>/gi, '~$1~');
                text = text.replace(/<s>(.*?)<\/s>/gi, '~$1~');
                text = text.replace(/<del>(.*?)<\/del>/gi, '~$1~');

                // Strip remaining HTML tags
                const tmp = document.createElement('DIV');
                tmp.innerHTML = text;
                const out = tmp.textContent || tmp.innerText || '';
                return out.replace(/\u00a0/g, ' ').replace(/\n{3,}/g, '\n\n');
            }

            function isImageFileName(name) {
                return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(name || '');
            }

            function renderCurrentCustomAttachment() {
                if (!currentCustomAttachment) return;

                const selectedFile = customAttachmentInput?.files?.[0] || null;
                const fileName = selectedFile?.name || savedCustomAttachmentName;
                const fileUrl = selectedFile ? dscPreviewUrl : savedCustomAttachmentUrl;

                if (!fileName || !fileUrl) {
                    currentCustomAttachment.innerHTML = '<span class="small text-muted">No extra attachment selected.</span>';
                    return;
                }

                if (isImageFileName(fileName)) {
                    currentCustomAttachment.innerHTML =
                        '<div class="small text-muted mb-1">Current attachment:</div>' +
                        '<a href="' + fileUrl + '" target="_blank" class="text-decoration-none">' +
                        '<img src="' + fileUrl + '" alt="' + fileName.replace(/"/g, '&quot;') + '" style="max-height:120px;max-width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:2px;background:#fff;">' +
                        '</a>';
                    return;
                }

                currentCustomAttachment.innerHTML =
                    '<div class="small text-muted mb-1">Current attachment:</div>' +
                    '<a href="' + fileUrl + '" target="_blank" class="small">' + fileName + '</a>';
            }

            function updateContextHints() {
                const labels = [];
                if (currentType === 'pi') labels.push('PI PDF');
                if (currentType === 'ti') labels.push('TI PDF');
                if (dscPreviewUrl) labels.push('Custom attachment');

                if (attachmentBodyHint) attachmentBodyHint.textContent = labels.length ? ('Attached: ' + labels.join(', ')) : 'No attachment selected.';
            }

            function getTemplateKeyForSelection(type) {
                return type; // pi, ti
            }

            function getAvailableChannelsForType(type) {
                const channels = availableChannelsByType[type] || [];
                return Array.isArray(channels) ? channels : [];
            }

            function getTemplatesForSelection(type, channel) {
                return ((templateCatalog[type] || {})[channel] || []);
            }

            function refreshChannelVisibilityForType(type) {
                const allowed = getAvailableChannelsForType(type);
                channelBtns.forEach((btn) => {
                    const show = allowed.includes(btn.dataset.channel);
                    btn.style.display = show ? '' : 'none';
                });

                if (!allowed.includes(currentChannel)) {
                    const fallback = allowed[0] || 'email';
                    currentChannel = fallback;
                    document.getElementById('selectedChannel').value = fallback;
                }
            }

            function populateTemplatePicker(type, channel) {
                if (!templatePicker && !templatePickerSmsWa) return;
                const items = getTemplatesForSelection(type, channel);
                const selectedTemplateIdInput = document.getElementById('selectedTemplateId');
                if (templatePicker) templatePicker.innerHTML = '';
                if (templatePickerSmsWa) templatePickerSmsWa.innerHTML = '';

                const manual = document.createElement('option');
                manual.value = '';
                manual.textContent = 'Manual (no template)';
                if (templatePicker) templatePicker.appendChild(manual.cloneNode(true));
                if (templatePickerSmsWa) templatePickerSmsWa.appendChild(manual.cloneNode(true));

                items.forEach((tpl) => {
                    const option = document.createElement('option');
                    option.value = tpl.templateid || '';
                    option.textContent = tpl.name || ('Template ' + (tpl.templateid || ''));
                    if (templatePicker) templatePicker.appendChild(option.cloneNode(true));
                    if (templatePickerSmsWa) templatePickerSmsWa.appendChild(option.cloneNode(true));
                });

                const firstValue = items.length > 0 ? (items[0].templateid || '') : '';
                if (items.length > 0) {
                    if (templatePicker) templatePicker.value = firstValue;
                    if (templatePickerSmsWa) templatePickerSmsWa.value = firstValue;
                } else {
                    if (templatePicker) templatePicker.value = '';
                    if (templatePickerSmsWa) templatePickerSmsWa.value = '';
                }
                if (selectedTemplateIdInput) {
                    selectedTemplateIdInput.value = firstValue || '';
                }
            }

            function applyTemplate(preserveExisting = false) {
                const templateKey = getTemplateKeyForSelection(currentType);
                const pickedTemplateId = (currentChannel === 'email'
                    ? (templatePicker?.value || '')
                    : (templatePickerSmsWa?.value || templatePicker?.value || '')
                );
                const options = getTemplatesForSelection(templateKey, currentChannel);
                let payload = null;
                if (pickedTemplateId) {
                    payload = options.find((tpl) => (tpl.templateid || '') === pickedTemplateId) || null;
                } else if (options.length > 0) {
                    payload = options[0];
                }
                if (!payload) {
                    payload = fallbackTemplatesByType[templateKey] || { subject: '', body: '' };
                }

                const nextSubject = (payload.subject || '').trim();
                const nextBody = payload.body || '';
                const hasRawTemplateBody = typeof payload.raw_body === 'string' && payload.raw_body.trim() !== '';
                currentRawTemplateBody = hasRawTemplateBody
                    ? payload.raw_body
                    : getPlainTextFromHtml(payload.body || nextBody || '');

                if (preserveExisting) {
                    if (currentChannel !== 'email' && emailBodyInput) {
                        emailBodyInput.value = getPlainTextFromHtml(emailBodyInput.value || '');
                    }
                    refreshEmailPreview();
                    return;
                }

                if (emailSubjectInput && currentChannel === 'email') {
                    emailSubjectInput.value = nextSubject;
                }

                if (currentChannel !== 'email') {
                    emailBodyInput.value = getPlainTextFromHtml(nextBody || '');
                } else if (window.tinymce && tinymce.get('emailBodyInput')) {
                    const editor = tinymce.get('emailBodyInput');
                    const normalizedBody = normalizeHtmlForEditor(nextBody);
                    if (normalizedBody && !/<[a-z][\s\S]*>/i.test(normalizedBody)) {
                        editor.setContent(normalizedBody.replace(/\r\n|\r|\n/g, '<br>'));
                    } else {
                        editor.setContent(normalizedBody);
                    }
                } else if (emailBodyInput) {
                    emailBodyInput.value = normalizeHtmlForEditor(nextBody);
                }
                refreshEmailPreview();
            }

            function switchChannel(channel, preserveExisting = false) {
                currentChannel = channel;
                document.getElementById('selectedChannel').value = channel;
                syncBodyEditorByChannel(channel);

                channelBtns.forEach(btn => {
                    btn.classList.toggle('is-active', btn.dataset.channel === channel);
                });

                document.querySelectorAll('.email-fields').forEach(el => el.style.display = channel === 'email' ? '' : 'none');
                document.querySelectorAll('.whatsapp-sms-fields').forEach(el => el.style.display = (channel === 'whatsapp' || channel === 'sms') ? '' : 'none');
                document.querySelector('.email-actions').style.display = channel === 'email' ? '' : 'none';
                document.querySelector('.whatsapp-actions').style.display = channel === 'whatsapp' ? '' : 'none';
                document.querySelector('.sms-actions').style.display = channel === 'sms' ? '' : 'none';

                populateTemplatePicker(currentType, currentChannel);
                applyTemplate(preserveExisting);
            }

            function switchType(type, preserveExisting = false) {
                currentType = type;
                document.getElementById('selectedType').value = type;

                typeBtns.forEach(btn => {
                    btn.classList.toggle('is-active', btn.dataset.type === type);
                });

                refreshChannelVisibilityForType(type);
                updateContextHints();
                populateTemplatePicker(currentType, currentChannel);
                applyTemplate(preserveExisting);
            }

            // Event Listeners
            typeBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.disabled) return;
                    const newType = btn.dataset.type;
                    if (newType === currentType) return;

                    const url = new URL(window.location.href);
                    url.searchParams.set('channel', currentChannel);
                    url.searchParams.set('attachment_type', newType);
                    url.searchParams.delete('e');
                    window.location.href = url.toString();
                });
            });

            channelBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.style.display === 'none') return;
                    const newChannel = btn.dataset.channel;
                    if (newChannel === currentChannel) return;

                    // Store current form data in sessionStorage before switching
                    if (emailSubjectInput?.value || emailBodyInput?.value) {
                        sessionStorage.setItem('compose_subject', emailSubjectInput?.value || '');
                        sessionStorage.setItem('compose_body', emailBodyInput?.value || '');
                    }

                    // Redirect to reload with new channel - controller will load correct saved message
                    const url = new URL(window.location.href);
                    url.searchParams.set('channel', newChannel);
                    url.searchParams.set('attachment_type', currentType);
                    url.searchParams.delete('e');
                    window.location.href = url.toString();
                });
            });

            templatePicker?.addEventListener('change', () => {
                const selectedTemplateIdInput = document.getElementById('selectedTemplateId');
                if (selectedTemplateIdInput) {
                    selectedTemplateIdInput.value = templatePicker.value || '';
                }
                if (templatePickerSmsWa) {
                    templatePickerSmsWa.value = templatePicker.value || '';
                }
                applyTemplate(false);
            });

            templatePickerSmsWa?.addEventListener('change', () => {
                const selectedTemplateIdInput = document.getElementById('selectedTemplateId');
                if (selectedTemplateIdInput) {
                    selectedTemplateIdInput.value = templatePickerSmsWa.value || '';
                }
                if (templatePicker) {
                    templatePicker.value = templatePickerSmsWa.value || '';
                }
                applyTemplate(false);
            });

            customAttachmentInput?.addEventListener('change', function () {
                if (dscPreviewUrl) {
                    URL.revokeObjectURL(dscPreviewUrl);
                    dscPreviewUrl = null;
                }
                const selectedFile = customAttachmentInput.files && customAttachmentInput.files.length ? customAttachmentInput.files[0] : null;
                if (selectedFile) dscPreviewUrl = URL.createObjectURL(selectedFile);
                updateContextHints();
                renderCurrentCustomAttachment();
            });

            emailSubjectInput?.addEventListener('input', refreshEmailPreview);
            emailBodyInput?.addEventListener('input', refreshEmailPreview);

            function getActiveMessageBody() {
                if (window.tinymce && tinymce.get('emailBodyInput')) {
                    return tinymce.get('emailBodyInput').getContent();
                }
                return emailBodyInput.value;
            }

            function enableTinyMceForEmail() {
                if (!window.tinymce || !emailBodyInput) return;
                if (tinymce.get('emailBodyInput')) return;

                tinymce.init({
                    license_key: 'gpl',
                    selector: '#emailBodyInput',
                    menubar: false,
                    height: 340,
                    plugins: 'lists link table code autoresize',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code',
                    valid_elements: '*[*]',
                    extended_valid_elements: 'style[type|media],link[rel|href|type|media],meta[charset|name|content]',
                    setup: function (editor) {
                        editor.on('BeforeSetContent', function (e) {
                            e.content = normalizeHtmlForEditor(e.content || '');
                        });
                        editor.on('init', function () {
                            editor.save();
                            refreshEmailPreview();
                        });
                        editor.on('input change keyup setcontent undo redo ExecCommand NodeChange', function () {
                            editor.save();
                            refreshEmailPreview();
                        });
                    }
                });
            }

            function disableTinyMceForTextarea() {
                if (!window.tinymce) return;
                const editor = tinymce.get('emailBodyInput');
                if (!editor) return;
                editor.save();
                editor.remove();
            }

            function syncBodyEditorByChannel(channel) {
                if (channel === 'email') {
                    enableTinyMceForEmail();
                    return;
                }
                disableTinyMceForTextarea();
            }

            // Actions
            document.getElementById('copyToClipboard')?.addEventListener('click', function () {
                const htmlContent = getActiveMessageBody();
                const message = getPlainTextFromHtml(htmlContent);

                const dummy = document.createElement("textarea");
                document.body.appendChild(dummy);
                dummy.value = message;
                dummy.select();
                document.execCommand("copy");
                document.body.removeChild(dummy);
                alert('Message copied to clipboard!');
            });

            document.getElementById('copySmsToClipboard')?.addEventListener('click', function () {
                const htmlContent = getActiveMessageBody();
                const message = getPlainTextFromHtml(htmlContent);

                const dummy = document.createElement("textarea");
                document.body.appendChild(dummy);
                dummy.value = message;
                dummy.select();
                document.execCommand("copy");
                document.body.removeChild(dummy);
                alert('SMS copied to clipboard!');
            });

            const composeForm = document.getElementById('composeForm');
            const actionButtons = Array.from(composeForm?.querySelectorAll('button[type="submit"][name="action"]') || []);

            function syncEditorToTextarea() {
                if (window.tinymce) {
                    const editor = tinymce.get('emailBodyInput');
                    if (editor) {
                        editor.save();
                    } else {
                        tinymce.triggerSave();
                    }
                }
            }

            actionButtons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    syncEditorToTextarea();
                });
            });

            composeForm?.addEventListener('submit', function () {
                syncEditorToTextarea();
            }, true);

            // Initial load
            if (!hasTiNumber && currentType === 'ti') {
                currentType = 'pi';
            }

            // Read channel from URL parameter and sync with controller-loaded message
            const urlParams = new URLSearchParams(window.location.search);
            const urlChannel = urlParams.get('channel');
            const urlType = urlParams.get('attachment_type');
            if (urlType && ['pi', 'ti'].includes(urlType)) {
                currentType = urlType;
                document.getElementById('selectedType').value = urlType;
            }
            if (urlChannel && ['email', 'whatsapp', 'sms'].includes(urlChannel)) {
                currentChannel = urlChannel;
                document.getElementById('selectedChannel').value = urlChannel;
            } else {
                // Check for channel preserved after save
                const preservedChannel = @json(session('preserve_channel'));
                if (preservedChannel && ['email', 'whatsapp', 'sms'].includes(preservedChannel)) {
                    // Redirect to URL with channel param to reload saved message
                    const url = new URL(window.location.href);
                    url.searchParams.set('channel', preservedChannel);
                    url.searchParams.set('attachment_type', currentType);
                    url.searchParams.delete('e');
                    window.location.href = url.toString();
                    return;
                }
            }

            // Update channel button state to match loaded message
            channelBtns.forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.channel === currentChannel);
            });

            syncBodyEditorByChannel(currentChannel);
            switchType(currentType, hasExistingDraft);
            switchChannel(currentChannel, hasExistingDraft);
            refreshEmailPreview();
            renderCurrentCustomAttachment();
        })();
    </script>
@endsection
