@extends('layouts.app')

@section('header_actions')
<a href="{{ route('quotations.index', [], false) }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left"></i> Back to Quotations
</a>
@endsection

@section('content')
@php
$clientName = $quotation->client->business_name ?? ($quotation->client->contact_name ?? 'Client');
$clientEmail = $quotation->client->primary_email ?? $quotation->client->email ?? '';
$isAlreadySent = (string) ($composeEmail->status ?? '') === 'sent';
$isComposeLocked = $isAlreadySent && (string) ($prefillChannel ?? 'email') !== 'email';
$displayDocNumber = trim((string) ($quotation->quo_number ?? $quotation->quotationid));
@endphp

<section class="position-relative bg-white p-2 rounded-3 compose-mail-page">
    <div class="bg-light p-2 rounded-3 mb-3">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('quotations.create', ['step' => 3, 'c' => $quotation->clientid, 'd' => $quotation->quotationid], false) }}"
                class="btn btn-outline-primary btn-primary text-white fw-medium">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="vr"></div>
            <div class="d-inline-flex align-items-center justify-content-center rounded"
                style="width:36px;height:36px;background:#e0e7ff;color:#4f46e5;">
                <i class="fas fa-user"></i>
            </div>
            <div class="grow">
                <div class="fw-semibold text-dark">{{ $clientName }}</div>
                @if ($clientEmail)
                <div class="text-muted small">{{ $clientEmail }}</div>
                @endif
            </div>
            <div class="text-end">
                <span class="invoice-number-badge">{{ $displayDocNumber }}</span>
                <div class="invoice-compact-steps invoice-compact-steps--right mt-1" aria-label="Step progress">
                    <span class="invoice-compact-step">1</span>
                    <span class="invoice-compact-step">2</span>
                    <span class="invoice-compact-step">3</span>
                    <span class="invoice-compact-step is-active">4</span>
                </div>
            </div>
        </div>
    </div>
    <style>
        .compose-mail-page .channel-pill-btn {
            border-top: 0 !important;
            border-left: 0 !important;
            border-right: 0 !important;
            border-bottom: 2px solid transparent !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            font-weight: 500 !important;
            font-size: 0.95rem !important;
            padding: 0.5rem 1rem !important;
            color: var(--bs-primary) !important;
            opacity: 0.7;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            margin-bottom: -1px;
        }

        .compose-mail-page .channel-pill-btn.is-active,
        .compose-mail-page .channel-pill-btn.active {
            border-bottom: 2px solid var(--bs-primary) !important;
            font-weight: 700 !important;
            opacity: 1 !important;
            color: var(--bs-primary) !important;
            background: transparent !important;
        }
    </style>

    <div class="bg-light p-2 rounded-3 mb-3">
        <div class="mb-2">
            <h5 class="fw-semibold text-primary small lh-sm mb-0">Channel</h5>
        </div>
        <div class="channel-pills-wrap mb-0">
            <div class="border-bottom w-100">
                <div class="btn-group" role="group" aria-label="Channel Tabs">
                    <button type="button"
                        class="channel-pill-btn btn btn-md px-3 border-top-0 border-start-0 border-end-0 rounded-0 text-primary bg-transparent border-primary border-bottom border-2 fw-bold active"
                        data-channel="email" id="channelBtnEmail">
                        <i class="fas fa-envelope me-1"></i> Email
                    </button>
                    <button type="button"
                        class="channel-pill-btn btn btn-md px-3 border-top-0 border-start-0 border-end-0 rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent"
                        data-channel="whatsapp" id="channelBtnWhatsapp" style="opacity: 0.7;">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </button>
                    <button type="button"
                        class="channel-pill-btn btn btn-md px-3 border-top-0 border-start-0 border-end-0 rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent"
                        data-channel="sms" id="channelBtnSms" style="opacity: 0.7;">
                        <i class="fas fa-sms me-1"></i> SMS
                    </button>
                </div>
            </div>
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

    <form method="POST" id="composeForm" class="mainForm"
        action="{{ route('quotations.email-compose.store', $quotation->quotationid) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="channel" id="selectedChannel"
            value="{{ old('channel', $prefillChannel ?? $composeEmail->channel ?? 'email') }}">
        <input type="hidden" name="selected_templateid" id="selectedTemplateId"
            value="{{ old('selected_templateid', $prefillTemplateId ?? '') }}">
        <input type="hidden" name="existing_custom_attachment_paths" id="existingCustomAttachmentPaths"
            value="{{ old('existing_custom_attachment_paths', implode(',', $customAttachmentUrls ?? [])) }}">

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-7">
                <div class="bg-light p-2 rounded-3 mb-3">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Compose Form</h5>
                    </div>
                    <div class="email-fields">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">From</label>
                                <input type="email" name="from_email" value="{{ $fromEmail }}" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">To</label>
                                <input type="text" name="to_email" value="{{ old('to_email', $toEmail) }}"
                                    class="form-control">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Subject</label>
                                <input type="text" name="subject" id="emailSubjectInput"
                                    value="{{ old('subject', $subject) }}" class="form-control" {{ $isComposeLocked
                                    ? 'readonly' : '' }}>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">CC</label>
                                <input type="text" name="cc_email" value="{{ old('cc_email', $ccEmail) }}"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="whatsapp-sms-fields d-none">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Phone Number</label>
                                <input type="text" name="phone_number" value="{{ old('phone_number', $phone) }}"
                                    class="form-control">
                            </div>
                            <div class="col-12 col-md-6"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small lh-sm fw-semibold text-dark mb-1">Body</label>
                        <textarea name="body" id="emailBodyInput" rows="8" class="form-control" {{ $isComposeLocked
                            ? 'readonly' : '' }}>{{ old('body', $body) }}</textarea>
                        <div id="attachmentBodyHint" class="text-secondary small mt-1"></div>
                    </div>
                    <div class="mb-3 email-fields">
                        <label class="form-label small lh-sm fw-semibold text-dark mb-1">Extra Attachments
                            (optional)</label>
                        <input type="file" name="custom_attachments[]" id="customAttachmentInput" multiple
                            class="form-control" {{ $isComposeLocked ? 'disabled' : '' }}>
                        <div id="currentCustomAttachment" class="mt-2"></div>
                        <div id="extraAttachmentPreview" class="mt-2"></div>
                    </div>

                    <div class="d-flex justify-content-end flex-wrap gap-2 mt-4 pt-3 border-top">
                        <div class="email-actions {{ $isComposeLocked ? 'd-none' : '' }}">
                            <button type="submit" name="action" value="save"
                                class="btn btn-outline-primary bg-white text-primary fw-medium">Save Email</button>
                            <button type="submit" name="action" value="send"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">Send to Client</button>
                        </div>
                        <div class="whatsapp-actions d-none">
                            <button type="submit" name="action" value="save"
                                class="btn btn-outline-primary bg-white text-primary fw-medium">Save WhatsApp
                                Message</button>
                            <button type="submit" name="action" value="send"
                                class="btn btn-outline-primary btn-primary text-white fw-medium button-whatsapp"
                                style="background: #25d366; border-color: #25d366;">
                                <i class="fab fa-whatsapp mr-1"></i> Send via WhatsApp
                            </button>
                        </div>
                        <div class="sms-actions d-none">
                            <button type="submit" name="action" value="save"
                                class="btn btn-outline-primary bg-white text-primary fw-medium">Save SMS</button>
                            <button type="submit" name="action" value="send"
                                class="btn btn-outline-primary btn-primary text-white fw-medium">Send SMS</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="bg-light p-2 rounded-3 mb-3 position-sticky" style="top:.8rem;">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Raw Message</h5>
                    </div>
                    <pre id="previewRawBody" class="mb-0 mt-0 p-2 border bg-white rounded-3 compose-preview-raw"></pre>
                    <div class="mt-3">
                        <div class="small fw-semibold text-muted mb-1">Attachments</div>
                        <div id="previewAttachments" class="small text-break"></div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                <a href="{{ route('quotations.index', [], false) }}"
                    class="btn btn-outline-primary bg-white text-primary fw-medium">View More Quotations</a>
                <a href="{{ route('quotations.create', [], false) }}"
                    class="btn btn-outline-primary btn-primary text-white fw-medium">Create Quotation</a>
            </div>
        </div>
        </div>
    </form>
</section>

<script>
    (function () {
        const channelBtns = Array.from(document.querySelectorAll('.channel-pill-btn'));
        const selectedChannelInput = document.getElementById('selectedChannel');
        const emailBodyInput = document.getElementById('emailBodyInput');
        const previewRawBody = document.getElementById('previewRawBody');
        const previewAttachments = document.getElementById('previewAttachments');
        const attachmentBodyHint = document.getElementById('attachmentBodyHint');
        const quotationPdfUrl = @json(route('quotations.pdf', $quotation -> quotationid));
        const isAlreadySent = @json($isComposeLocked);
        const templateCatalog = @json($templateCatalog ?? []);
        const availableChannels = @json($availableChannels ?? ['email']);
        const selectedTemplateIdInput = document.getElementById('selectedTemplateId');
        const emailSubjectInput = document.getElementById('emailSubjectInput');
        const customAttachmentInput = document.getElementById('customAttachmentInput');
        const currentCustomAttachment = document.getElementById('currentCustomAttachment');
        const savedCustomAttachmentUrls = @json($customAttachmentUrls ?? []);
        const savedCustomAttachmentNames = @json($customAttachmentNames ?? []);
        const extraAttachmentPreview = document.getElementById('extraAttachmentPreview');
        const initialChannel = selectedChannelInput?.value || 'email';
        let customAttachmentPreviewUrls = Array.isArray(savedCustomAttachmentUrls) ? [...savedCustomAttachmentUrls] : [];
        const channelDraftState = {};

        function getPlainTextFromHtml(html) {
            if (!html) return '';
            let text = String(html);
            text = text.replace(/\r\n/g, '\n');
            text = text.replace(/\n/g, '\n');
            text = text.replace(/<br\s*\/?>/gi, '\n');
            text = text.replace(/<\/(div|li|h[1-6]|p)>/gi, '\n');
            text = text.replace(/<(ul|ol)[^>]*>/gi, '\n');
            // Decode encoded tags like &lt;p&gt; so they can be stripped reliably.
            const decoder = document.createElement('textarea');
            decoder.innerHTML = text;
            text = decoder.value || text;
            const tmp = document.createElement('div');
            tmp.innerHTML = text;
            return (tmp.textContent || tmp.innerText || '').replace(/\u00a0/g, ' ').replace(/\n{3,}/g, '\n\n');
        }

        function normalizeHtmlForEditor(html) {
            const value = (html || '').trim();
            if (!value) return '';
            if (!/<html[\s>]|<head[\s>]|<body[\s>]/i.test(value)) return value;
            const parser = new DOMParser();
            const doc = parser.parseFromString(value, 'text/html');
            return doc.body ? doc.body.innerHTML : value;
        }

        function toEditorHtml(value) {
            const normalizedBody = normalizeHtmlForEditor(value || '');
            if (normalizedBody && !/<[a-z][\s\S]*>/i.test(normalizedBody)) {
                return normalizedBody.replace(/\r\n|\r|\n/g, '<br>');
            }
            return normalizedBody;
        }

        function getActiveMessageBody() {
            if (window.tinymce && tinymce.get('emailBodyInput')) {
                return tinymce.get('emailBodyInput').getContent();
            }
            return emailBodyInput ? emailBodyInput.value : '';
        }

        function getActiveMessageBodyAsPlainText() {
            return getPlainTextFromHtml(getActiveMessageBody());
        }

        function refreshAttachmentPreview() {
            if (!previewAttachments) return;
            const channel = selectedChannelInput.value || 'email';
            if (channel !== 'email') {
                previewAttachments.innerHTML = '<span class="text-muted">No attachments selected.</span>';
                return;
            }
            const rows = [
                `<div><a href="${quotationPdfUrl}" target="_blank" rel="noopener noreferrer">Quotation PDF</a></div>`
            ];
            const selectedFiles = Array.from(customAttachmentInput?.files || []);
            const dynamicRows = selectedFiles.map((file, index) => ({
                name: `Attachment ${index + 1}`,
                url: customAttachmentPreviewUrls[index] || null,
            })).filter((row) => !!row.url);
            const staticRows = selectedFiles.length === 0
                ? savedCustomAttachmentUrls.map((url, index) => ({
                    name: `Attachment ${index + 1}`,
                    url: url,
                }))
                : [];
            const attachmentLinks = [...staticRows, ...dynamicRows]
                .map((row) => `<a href="${row.url}" target="_blank" rel="noopener noreferrer">${row.name}</a>`)
                .join(', ');
            if (attachmentLinks) rows.push(`<div>${attachmentLinks}</div>`);
            previewAttachments.innerHTML = rows.join('');
        }

        function refreshHints() {
            if (!attachmentBodyHint) return;
            const channel = selectedChannelInput.value || 'email';
            if (channel !== 'email') {
                attachmentBodyHint.textContent = 'No attachments selected.';
                return;
            }
            const selectedCustomFiles = Array.from(customAttachmentInput?.files || []);
            const hasCustom = selectedCustomFiles.length > 0 || (savedCustomAttachmentUrls || []).length > 0;
            attachmentBodyHint.textContent = hasCustom ? 'Attached: Quotation PDF, Extra attachment' : 'Attached: Quotation PDF';
        }

        function renderCurrentCustomAttachment() {
            if (!currentCustomAttachment) return;
            const selectedCustomFiles = Array.from(customAttachmentInput?.files || []);
            const attachments = selectedCustomFiles.length > 0
                ? selectedCustomFiles.map((file, index) => ({
                    name: `Attachment ${index + 1}`,
                    url: customAttachmentPreviewUrls[index] || null,
                })).filter((row) => !!row.url)
                : (savedCustomAttachmentUrls || []).map((url, index) => ({
                    name: `Attachment ${index + 1}`,
                    url,
                }));
            if (attachments.length === 0) {
                currentCustomAttachment.innerHTML = '<span class="small text-muted">No extra attachment selected.</span>';
                return;
            }
            currentCustomAttachment.innerHTML = '<div class="small text-muted mb-1">Current attachments:</div>' +
                '<div class="small">' + attachments.map((row) => (
                    '<a href="' + row.url + '" target="_blank">' + row.name + '</a>'
                )).join(', ') + '</div>';
        }

        function renderExtraAttachmentPreview() {
            if (!extraAttachmentPreview) return;
            const selectedCustomFiles = Array.from(customAttachmentInput?.files || []);
            const attachments = selectedCustomFiles.length > 0
                ? selectedCustomFiles.map((file, index) => ({
                    name: file?.name || `Attachment ${index + 1}`,
                    url: customAttachmentPreviewUrls[index] || null,
                    mime: (file?.type || '').toLowerCase(),
                })).filter((row) => !!row.url)
                : (savedCustomAttachmentUrls || []).map((url, index) => ({
                    name: savedCustomAttachmentNames[index] || `Attachment ${index + 1}`,
                    url,
                    mime: '',
                }));
            if (attachments.length === 0) {
                extraAttachmentPreview.innerHTML = '';
                return;
            }
            const isImage = (item) => {
                const ext = (item.name.split('.').pop() || '').toLowerCase();
                return String(item.mime || '').startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext);
            };
            const imageAttachments = attachments.filter(isImage);
            const nonImageAttachments = attachments.filter((item) => !isImage(item));

            let html = '';
            if (imageAttachments.length > 0) {
                html += '<div class="small text-muted mb-1">Preview:</div><div class="compose-attachment-preview-grid">' +
                    imageAttachments.map((item) => (
                        '<a href="' + item.url + '" target="_blank" rel="noopener noreferrer">' +
                        '<img src="' + item.url + '" alt="' + String(item.name).replace(/"/g, '&quot;') + '" class="img-fluid border rounded compose-attachment-image">' +
                        '</a>'
                    )).join('') +
                    '</div>';
            }
            if (nonImageAttachments.length > 0) {
                html += '<div class="small text-muted mt-2">Other files:</div>' +
                    nonImageAttachments.map((item) => (
                        '<div><a href="' + item.url + '" target="_blank" rel="noopener noreferrer">' +
                        String(item.name).replace(/</g, '&lt;').replace(/>/g, '&gt;') +
                        '</a></div>'
                    )).join('');
            }
            extraAttachmentPreview.innerHTML = html;
        }

        function setChannel(channel) {
            const previousChannel = selectedChannelInput.value || initialChannel;
            if (previousChannel) {
                channelDraftState[previousChannel] = {
                    subject: emailSubjectInput ? emailSubjectInput.value : '',
                    body: getActiveMessageBody(),
                    templateid: selectedTemplateIdInput ? selectedTemplateIdInput.value : '',
                };
            }
            const normalized = availableChannels.includes(channel) ? channel : (availableChannels[0] || 'email');
            channel = normalized;
            selectedChannelInput.value = channel;
            channelBtns.forEach((btn) => {
                const isActive = btn.dataset.channel === channel;
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('is-active', isActive);
            });
            const isEmail = channel === 'email';
            syncBodyEditorByChannel(channel);
            const cachedState = channelDraftState[channel] || null;
            if (cachedState) {
                if (emailSubjectInput && !isAlreadySent && typeof cachedState.subject === 'string') {
                    emailSubjectInput.value = cachedState.subject;
                }
                if (!isAlreadySent && typeof cachedState.body === 'string') {
                    if (window.tinymce && tinymce.get('emailBodyInput')) {
                        tinymce.get('emailBodyInput').setContent(toEditorHtml(cachedState.body || ''));
                        tinymce.get('emailBodyInput').save();
                    } else if (emailBodyInput) {
                        emailBodyInput.value = channel === 'email'
                            ? normalizeHtmlForEditor(cachedState.body || '')
                            : getPlainTextFromHtml(cachedState.body || '');
                    }
                }
                if (selectedTemplateIdInput && typeof cachedState.templateid === 'string') {
                    selectedTemplateIdInput.value = cachedState.templateid;
                }
            } else {
                applyTemplateForChannel(channel);
            }
            document.querySelectorAll('.email-fields, .email-actions').forEach((el) => {
                if (el.classList.contains('email-actions')) {
                    el.classList.toggle('d-none', !isEmail || isAlreadySent);
                } else {
                    el.classList.toggle('d-none', !isEmail);
                }
            });
            const altFields = document.querySelector('.whatsapp-sms-fields');
            if (altFields) {
                altFields.classList.toggle('d-none', isEmail);
            }
            const whatsappActions = document.querySelector('.whatsapp-actions');
            if (whatsappActions) {
                whatsappActions.classList.toggle('d-none', channel !== 'whatsapp' || isAlreadySent);
            }
            const smsActions = document.querySelector('.sms-actions');
            if (smsActions) {
                smsActions.classList.toggle('d-none', channel !== 'sms' || isAlreadySent);
            }
            refreshHints();
            refreshAttachmentPreview();
        }

        channelBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const channel = btn.dataset.channel || 'email';
                if (channel === (selectedChannelInput.value || 'email')) {
                    setChannel(channel);
                    return;
                }

                const url = new URL(window.location.href);
                url.searchParams.set('channel', channel);
                window.location.href = url.toString();
            });
        });

        function applyTemplateForChannel(channel) {
            const templates = templateCatalog[channel] || [];
            const selectedTemplate = templates[0] || null;
            if (!selectedTemplate) return;
            if (selectedTemplateIdInput) {
                selectedTemplateIdInput.value = selectedTemplate.templateid || '';
            }
            if (channel === 'email' && emailSubjectInput && !isAlreadySent) {
                emailSubjectInput.value = selectedTemplate.subject || emailSubjectInput.value || '';
            }
            if (!isAlreadySent) {
                const templateBody = selectedTemplate.body || '';
                if (window.tinymce && tinymce.get('emailBodyInput')) {
                    tinymce.get('emailBodyInput').setContent(
                        channel === 'email' ? toEditorHtml(templateBody) : getPlainTextFromHtml(templateBody)
                    );
                    tinymce.get('emailBodyInput').save();
                } else if (emailBodyInput) {
                    emailBodyInput.value = channel === 'email'
                        ? normalizeHtmlForEditor(templateBody)
                        : getPlainTextFromHtml(templateBody);
                }
                refreshPreview();
            }
        }

        function refillFromTemplateWhenBodyEmpty() {
            if (isAlreadySent) return;
            const plain = (getActiveMessageBodyAsPlainText() || '').trim();
            if (plain !== '') return;
            const channel = selectedChannelInput.value || 'email';
            const templates = templateCatalog[channel] || [];
            if (!Array.isArray(templates) || templates.length === 0) return;
            applyTemplateForChannel(channel);
        }

        function syncVisibleChannelTabs() {
            const allowed = Array.isArray(availableChannels) && availableChannels.length ? availableChannels : ['email'];
            channelBtns.forEach((btn) => {
                const show = allowed.includes(btn.dataset.channel || '');
                btn.style.display = show ? '' : 'none';
                const isActive = (btn.dataset.channel || '') === (selectedChannelInput.value || 'email');
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('is-active', isActive);
            });
            if (!allowed.includes(selectedChannelInput.value || '')) {
                selectedChannelInput.value = allowed[0] || 'email';
            }
        }

        function refreshPreview() {
            if (previewRawBody) {
                previewRawBody.textContent = getPlainTextFromHtml(getActiveMessageBody());
            }
            refreshAttachmentPreview();
        }

        function enableTinyMceForEmail() {
            if (!window.tinymce || !emailBodyInput || tinymce.get('emailBodyInput')) return;
            tinymce.init({
                license_key: 'gpl',
                selector: '#emailBodyInput',
                menubar: false,
                height: 340,
                readonly: !!isAlreadySent,
                plugins: 'lists link table code autoresize',
                toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code',
                setup: function (editor) {
                    editor.on('init', function () {
                        editor.setContent(toEditorHtml(emailBodyInput.value || ''));
                        editor.save();
                        refreshPreview();
                    });
                    editor.on('input change keyup setcontent undo redo', function () {
                        editor.save();
                        refreshPreview();
                        refillFromTemplateWhenBodyEmpty();
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
            } else {
                disableTinyMceForTextarea();
                if (emailBodyInput) {
                    emailBodyInput.value = getPlainTextFromHtml(emailBodyInput.value || '');
                }
            }
        }

        emailBodyInput?.addEventListener('input', function () {
            refreshPreview();
            refillFromTemplateWhenBodyEmpty();
        });
        customAttachmentInput?.addEventListener('change', function () {
            (customAttachmentPreviewUrls || []).forEach((url) => {
                if (url && !(savedCustomAttachmentUrls || []).includes(url)) {
                    URL.revokeObjectURL(url);
                }
            });
            const selectedFiles = Array.from(customAttachmentInput.files || []);
            customAttachmentPreviewUrls = selectedFiles.map((file) => URL.createObjectURL(file));
            refreshHints();
            renderCurrentCustomAttachment();
            renderExtraAttachmentPreview();
            refreshAttachmentPreview();
        });

        document.getElementById('composeForm')?.addEventListener('submit', function () {
            if (window.tinymce) {
                const editor = tinymce.get('emailBodyInput');
                if (editor) editor.save();
            }
        }, true);

        syncVisibleChannelTabs();
        setChannel(selectedChannelInput.value || 'email');
        syncBodyEditorByChannel(selectedChannelInput.value || 'email');
        renderCurrentCustomAttachment();
        renderExtraAttachmentPreview();
        refreshPreview();
    })();
</script>
@endsection
