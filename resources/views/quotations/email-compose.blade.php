@extends('layouts.app')

@section('content')
    @php
        $clientName = $quotation->client->business_name ?? ($quotation->client->contact_name ?? 'Client');
        $clientEmail = $quotation->client->primary_email ?? $quotation->client->email ?? '';
        $isAlreadySent = (string) ($composeEmail->status ?? '') === 'sent';
        $displayDocNumber = trim((string) ($quotation->quo_number ?? $quotation->quotationid));
    @endphp

    <section class="panel-card w-100 p-3 compose-mail-page">
        <div class="bg-light border rounded p-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('quotations.create', ['step' => 3, 'c' => $quotation->clientid, 'd' => $quotation->quotationid]) }}"
                    class="secondary-button">
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

        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Message Compose</h4>
        </div>
        <div class="text-muted small mb-3">Select channel before sending</div>

        <div class="border rounded overflow-hidden mb-4" style="background:#fff;">
            <div class="bg-light border-bottom px-3 py-2 small fw-semibold">Channel</div>
            <div class="p-3">
                <div class="channel-pills-wrap mb-0">
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

        <form method="POST" id="composeForm" action="{{ route('quotations.email-compose.store', $quotation->quotationid) }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="channel" id="selectedChannel" value="{{ old('channel', $prefillChannel ?? $composeEmail->channel ?? 'email') }}">
            <input type="hidden" name="selected_templateid" id="selectedTemplateId" value="{{ old('selected_templateid', $prefillTemplateId ?? '') }}">
            <input type="hidden" name="existing_custom_attachment_path" id="existingCustomAttachmentPath" value="{{ old('existing_custom_attachment_path', $customAttachmentUrl ?? '') }}">

            <div class="row g-3 align-items-start">
                <div class="col-12 col-xl-7">
                    <div class="border rounded overflow-hidden" style="background: #fff;">
                        <div class="bg-light border-bottom px-3 py-2 small fw-semibold">Compose Form</div>
                        <div class="p-3">
                            <div class="email-fields">
                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="field-label">From</label>
                                        <input type="email" name="from_email" value="{{ $fromEmail }}" class="input-full" readonly>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="field-label">To</label>
                                        <input type="text" name="to_email" value="{{ old('to_email', $toEmail) }}" class="input-full">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="field-label">Subject</label>
                                        <input type="text" name="subject" id="emailSubjectInput"
                                            value="{{ old('subject', $subject) }}"
                                            class="input-full" {{ $isAlreadySent ? 'readonly' : '' }}>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="field-label">CC</label>
                                        <input type="text" name="cc_email" value="{{ old('cc_email', $ccEmail) }}" class="input-full">
                                    </div>
                                </div>
                            </div>

                            <div class="whatsapp-sms-fields" style="display: none;">
                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-md-6">
                                        <label class="field-label">Phone Number</label>
                                        <input type="text" name="phone_number" value="{{ old('phone_number', $phone) }}" class="input-full">
                                    </div>
                                    <div class="col-12 col-md-6"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="field-label">Body</label>
                                <textarea name="body" id="emailBodyInput" rows="8" class="input-full"
                                    {{ $isAlreadySent ? 'readonly' : '' }}>{{ old('body', $body) }}</textarea>
                                <div id="attachmentBodyHint" class="text-secondary small mt-1"></div>
                            </div>
                            <div class="mb-3 email-fields">
                                <label class="field-label">Extra Attachment (optional)</label>
                                <input type="file" name="custom_attachment" id="customAttachmentInput"
                                    class="input-full" {{ $isAlreadySent ? 'disabled' : '' }}>
                                <div id="currentCustomAttachment" class="mt-2"></div>
                                <div id="extraAttachmentPreview" class="mt-2"></div>
                            </div>

                            <div class="d-flex justify-content-end flex-wrap gap-2 mt-4 pt-3 border-top">
                                <div class="email-actions" style="{{ $isAlreadySent ? 'display:none;' : '' }}">
                                    <button type="submit" name="action" value="save" class="secondary-button">Save Email</button>
                                    <button type="submit" name="action" value="send" class="primary-button">Send to Client</button>
                                </div>
                                <div class="whatsapp-actions" style="{{ $isAlreadySent ? 'display:none;' : 'display: none;' }}">
                                    <button type="submit" name="action" value="save" class="secondary-button">Save WhatsApp Message</button>
                                    <button type="submit" name="action" value="send" class="primary-button" style="background: #25d366; border-color: #25d366;">
                                        <i class="fab fa-whatsapp mr-1"></i> Send via WhatsApp
                                    </button>
                                </div>
                                <div class="sms-actions" style="{{ $isAlreadySent ? 'display:none;' : 'display: none;' }}">
                                    <button type="submit" name="action" value="save" class="secondary-button">Save SMS</button>
                                    <button type="submit" name="action" value="send" class="primary-button">Send SMS</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5">
                    <div class="border rounded overflow-hidden position-sticky" style="top:.8rem; background: #fff;">
                        <div class="bg-light border-bottom px-3 py-2 small fw-semibold">Raw Message</div>
                        <div class="p-3">
                            <pre id="previewRawBody" class="mb-0 mt-0 p-2 border rounded bg-light"
                                style="min-height: 180px; white-space: pre-wrap; word-break: break-word;"></pre>
                            <div class="mt-3">
                                <div class="small fw-semibold text-muted mb-1">Attachments</div>
                                <div id="previewAttachments" class="small text-break"></div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end align-items-center gap-2 mt-3">
                        <a href="{{ route('quotations.index') }}" class="secondary-button">View More Quotations</a>
                        <a href="{{ route('quotations.create') }}" class="primary-button">Create Quotation</a>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <script>
        (function() {
            const channelBtns = Array.from(document.querySelectorAll('.channel-pill-btn'));
            const selectedChannelInput = document.getElementById('selectedChannel');
            const emailBodyInput = document.getElementById('emailBodyInput');
            const previewRawBody = document.getElementById('previewRawBody');
            const previewAttachments = document.getElementById('previewAttachments');
            const attachmentBodyHint = document.getElementById('attachmentBodyHint');
            const quotationPdfUrl = @json(route('quotations.pdf', $quotation->quotationid));
            const isAlreadySent = @json($isAlreadySent);
            const templateCatalog = @json($templateCatalog ?? []);
            const availableChannels = @json($availableChannels ?? ['email']);
            const selectedTemplateIdInput = document.getElementById('selectedTemplateId');
            const emailSubjectInput = document.getElementById('emailSubjectInput');
            const customAttachmentInput = document.getElementById('customAttachmentInput');
            const currentCustomAttachment = document.getElementById('currentCustomAttachment');
            const savedCustomAttachmentUrl = @json($customAttachmentUrl ?? null);
            const savedCustomAttachmentName = @json($customAttachmentName ?? null);
            const extraAttachmentPreview = document.getElementById('extraAttachmentPreview');
            const initialChannel = selectedChannelInput?.value || 'email';
            let customAttachmentPreviewUrl = savedCustomAttachmentUrl || null;
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
                const selectedCustomFile = customAttachmentInput?.files?.[0] || null;
                const customFileName = selectedCustomFile?.name || savedCustomAttachmentName;
                const customFileUrl = selectedCustomFile ? customAttachmentPreviewUrl : savedCustomAttachmentUrl;
                if (customFileName && customFileUrl) {
                    rows.push(`<div><a href="${customFileUrl}" target="_blank" rel="noopener noreferrer">${String(customFileName).replace(/</g, '&lt;').replace(/>/g, '&gt;')}</a></div>`);
                }
                previewAttachments.innerHTML = rows.join('');
            }

            function refreshHints() {
                if (!attachmentBodyHint) return;
                const channel = selectedChannelInput.value || 'email';
                if (channel !== 'email') {
                    attachmentBodyHint.textContent = 'No attachments selected.';
                    return;
                }
                const selectedCustomFile = customAttachmentInput?.files?.[0] || null;
                const hasCustom = !!(selectedCustomFile || savedCustomAttachmentUrl);
                attachmentBodyHint.textContent = hasCustom ? 'Attached: Quotation PDF, Extra attachment' : 'Attached: Quotation PDF';
            }

            function renderCurrentCustomAttachment() {
                if (!currentCustomAttachment) return;
                const selectedCustomFile = customAttachmentInput?.files?.[0] || null;
                const fileName = selectedCustomFile?.name || savedCustomAttachmentName;
                const fileUrl = selectedCustomFile ? customAttachmentPreviewUrl : savedCustomAttachmentUrl;
                if (!fileName || !fileUrl) {
                    currentCustomAttachment.innerHTML = '<span class="small text-muted">No extra attachment selected.</span>';
                    return;
                }
                currentCustomAttachment.innerHTML =
                    '<div class="small text-muted mb-1">Current attachment:</div>' +
                    '<a href="' + fileUrl + '" target="_blank" class="small">' + String(fileName).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</a>';
            }

            function renderExtraAttachmentPreview() {
                if (!extraAttachmentPreview) return;
                const selectedCustomFile = customAttachmentInput?.files?.[0] || null;
                const fileName = selectedCustomFile?.name || savedCustomAttachmentName || '';
                const fileUrl = selectedCustomFile ? customAttachmentPreviewUrl : savedCustomAttachmentUrl;
                if (!fileName || !fileUrl) {
                    extraAttachmentPreview.innerHTML = '';
                    return;
                }
                const safeName = String(fileName).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const mimeType = (selectedCustomFile?.type || '').toLowerCase();
                const ext = (fileName.split('.').pop() || '').toLowerCase();
                const isImage = mimeType.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'].includes(ext);
                const isPdf = mimeType === 'application/pdf' || ext === 'pdf';
                const isText = mimeType.startsWith('text/') || ['txt', 'csv', 'log', 'md'].includes(ext);

                if (isImage) {
                    extraAttachmentPreview.innerHTML =
                        '<div class="small text-muted mb-1">Preview:</div>' +
                        '<img src="' + fileUrl + '" alt="' + safeName + '" class="img-fluid border rounded" style="max-height:120px;">';
                    return;
                }

                if (isPdf) {
                    extraAttachmentPreview.innerHTML =
                        '<div class="small text-muted mb-1">Preview:</div>' +
                        '<iframe src="' + fileUrl + '" title="' + safeName + '" class="w-100 border rounded" style="height:180px;"></iframe>';
                    return;
                }

                if (isText) {
                    extraAttachmentPreview.innerHTML =
                        '<div class="small text-muted mb-1">Preview:</div>' +
                        '<iframe src="' + fileUrl + '" title="' + safeName + '" class="w-100 border rounded bg-light" style="height:140px;"></iframe>';
                    return;
                }

                extraAttachmentPreview.innerHTML =
                    '<div class="small text-muted">Preview unavailable for this file type. ' +
                    '<a href="' + fileUrl + '" target="_blank" rel="noopener noreferrer">Open attachment</a></div>';
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
                channelBtns.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.channel === channel));
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
                    el.style.display = isEmail ? '' : 'none';
                });
                document.querySelector('.whatsapp-sms-fields').style.display = isEmail ? 'none' : '';
                document.querySelector('.whatsapp-actions').style.display = channel === 'whatsapp' ? '' : 'none';
                document.querySelector('.sms-actions').style.display = channel === 'sms' ? '' : 'none';
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
                    setup: function(editor) {
                        editor.on('init', function() {
                            editor.setContent(toEditorHtml(emailBodyInput.value || ''));
                            editor.save();
                            refreshPreview();
                        });
                        editor.on('input change keyup setcontent undo redo', function() {
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

            emailBodyInput?.addEventListener('input', function() {
                refreshPreview();
                refillFromTemplateWhenBodyEmpty();
            });
            customAttachmentInput?.addEventListener('change', function() {
                if (customAttachmentPreviewUrl && customAttachmentPreviewUrl !== savedCustomAttachmentUrl) {
                    URL.revokeObjectURL(customAttachmentPreviewUrl);
                    customAttachmentPreviewUrl = null;
                }
                const selected = customAttachmentInput.files?.[0] || null;
                if (selected) {
                    customAttachmentPreviewUrl = URL.createObjectURL(selected);
                } else {
                    customAttachmentPreviewUrl = savedCustomAttachmentUrl;
                }
                refreshHints();
                renderCurrentCustomAttachment();
                renderExtraAttachmentPreview();
                refreshAttachmentPreview();
            });

            document.getElementById('composeForm')?.addEventListener('submit', function() {
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
