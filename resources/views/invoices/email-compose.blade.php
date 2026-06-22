@php
$clientName = $invoice->client->business_name ?? ($invoice->client->contact_name ?? 'Client');
$clientEmail = $invoice->client->primary_email ?? $invoice->client->email ?? '';
$invoicePrimaryOrderId = optional($invoice->invoiceItems)->pluck('orderid')->filter()->first();

$hasTiNumber = !empty(trim((string) $invoice->ti_number));
$displayDocNumber = $hasTiNumber
? (trim((string) $invoice->ti_number) !== '' ? $invoice->ti_number : $invoice->invoice_number)
: (trim((string) $invoice->pi_number) !== '' ? $invoice->pi_number : $invoice->invoice_number);
$defaultSubjectNumber = $hasTiNumber ? $invoice->ti_number : $invoice->pi_number;

$isEmailSent = (string) ($emailDraft->status ?? '') === 'sent';
$isWhatsappSent = (string) ($whatsappDraft->status ?? '') === 'sent';
$isSmsSent = (string) ($smsDraft->status ?? '') === 'sent';

$title = 'Compose Invoice Communications';
$subtitle = "{$clientName}" . ($invoice->pi_number ? " | {$invoice->pi_number}" : "") . ($invoice->ti_number
? " • TI: {$invoice->ti_number}" : "");

$hasEmailTemplate = true;
$hasWhatsappTemplate = !empty($templateCatalog['pi']['whatsapp'] ?? []) || !empty($templateCatalog['ti']['whatsapp'] ??
[]);
$hasSmsTemplate = !empty($templateCatalog['pi']['sms'] ?? []) || !empty($templateCatalog['ti']['sms'] ?? []);
@endphp

@extends('layouts.app')

@section('header_actions')
<a href="{{ route('invoices.index', [], false) }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Invoice List
</a>
@endsection

@section('content')
<section class="position-relative bg-white p-3 rounded-3 compose-mail-page">

    <!-- Type Tabs (PI, TI/DSI) -->
    <div class="mb-3">
        <div class="w-100">
            <div class="btn-group" role="group" aria-label="Document Type Tabs">
                <button type="button"
                    class="type-tab-btn btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $defaultType === 'pi' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold active' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }}"
                    data-type="pi">
                    PI (Proforma Invoice)
                </button>
                <button type="button"
                    class="type-tab-btn btn btn-md px-3 border-top-0 border-start-0 border-end-0 {{ $defaultType === 'ti' ? 'rounded-top text-primary bg-primary-subtle border-primary border-bottom border-2 fw-bold active' : 'rounded-0 text-primary bg-transparent border-bottom border-2 border-transparent' }}"
                    data-type="ti" {{ !$hasTiNumber ? 'disabled' : '' }}>
                    TI/DSI (Tax Invoice)
                </button>
            </div>
        </div>
    </div>

    <!-- Channels Row -->
    <div class="row g-2 align-items-stretch">
        <!-- Email Column -->
        <div class="col-12 col-lg-6">
            <div class="bg-DarkLight p-2 rounded-3 h-100 {{ !$hasEmailTemplate ? 'opacity-50' : '' }}">
                <div
                    class="bg-light p-2 border-bottom rounded-3 d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold text-primary small lh-sm mb-0">
                        <i class="fas fa-envelope fs-6 lh-sm me-1"></i> Email <span class="text-dark">({{
                            $fromEmail }})</span>
                        @if(!$hasEmailTemplate)
                        <span class="text-danger ms-1" style="font-size: 0.75rem;">(No Template)</span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="border-0 p-0 bg-transparent fw-semibold text-dark small lh-sm"
                            data-channel="email" {{ !$hasEmailTemplate ? 'disabled' : '' }}>Raw Message
                        </button>
                        @if($isEmailSent)
                        <span class="badge bg-success small"><i class="fas fa-check-circle me-1"></i> Sent</span>
                        @endif
                        <div class="bg-white px-2 py-1 rounded-pill border" style="cursor:pointer;">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input channel-select-checkbox" style="cursor:pointer;"
                                    type="checkbox" id="send_email" data-channel="email" {{ $hasEmailTemplate
                                    ? 'checked' : 'disabled' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="send_email"
                                    style="cursor:pointer;">Send Email</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <form id="emailForm" class="mainForm" data-channel="email" enctype="multipart/form-data">
                        <input type="hidden" name="logid" value="{{ $emailDraft->logid ?? '' }}">
                        <input type="hidden" name="channel" value="email">
                        <input type="hidden" name="from_email" value="{{ $fromEmail }}">

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">To</label>
                                <input type="text" name="to_email" value="{{ old('to_email', $emailTo) }}"
                                    class="form-control" {{ !$hasEmailTemplate ? 'disabled' : '' }}>
                            </div>

                            <div class="col-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">CC</label>
                                <input type="text" name="cc_email" value="{{ old('cc_email', $emailCc) }}"
                                    class="form-control" {{ !$hasEmailTemplate ? 'disabled' : '' }}>
                            </div>

                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Subject</label>
                                <input type="text" name="subject" id="emailSubjectInput"
                                    value="{{ old('subject', $emailSubject) }}" class="form-control" {{
                                    !$hasEmailTemplate ? 'disabled' : '' }}>
                            </div>

                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Body</label>
                                <textarea name="body" id="emailBodyInput" rows="10" class="form-control" {{
                                    !$hasEmailTemplate ? 'disabled' : '' }}>{{ old('body', $emailBody) }}</textarea>
                                <div id="attachmentBodyHint" class="text-secondary small mt-1"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Extra Attachments
                                    (optional)</label>
                                <input type="file" name="custom_attachments[]" id="customAttachmentInput" multiple
                                    class="form-control" {{ !$hasEmailTemplate ? 'disabled' : '' }}>
                                <div id="currentCustomAttachment" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="auto-save-status small text-muted" data-channel="email"
                                style="min-height:1.4em;"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- WhatsApp Column -->
        <div class="col-12 col-lg-3">
            <div class="bg-DarkLight p-2 rounded-3 h-100 {{ !$hasWhatsappTemplate ? 'opacity-50' : '' }}">
                <div
                    class="bg-light p-2 border-bottom rounded-3 d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold small lh-sm mb-0" style="color:#128C7E;">
                        <i class="fab fa-whatsapp fs-6 lh-sm me-1"></i> WhatsApp
                        @if(!$hasWhatsappTemplate)
                        <span class="text-danger ms-1" style="font-size: 0.75rem;">(No Template)</span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="border-0 p-0 bg-transparent fw-semibold text-dark small lh-sm"
                            data-channel="whatsapp" {{ !$hasWhatsappTemplate ? 'disabled' : '' }}> Raw Message
                        </button>
                        @if($isWhatsappSent)
                        <span class="badge bg-success small"><i class="fas fa-check-circle me-1"></i> Sent
                        </span>
                        @endif
                        <div class="bg-white px-2 py-1 rounded-pill border" style="cursor:pointer;">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input channel-select-checkbox" style="cursor:pointer;"
                                    type="checkbox" id="send_whatsapp" data-channel="whatsapp" {{ ($whatsappDraft &&
                                    $hasWhatsappTemplate) ? 'checked' : '' }} {{ !$hasWhatsappTemplate ? 'disabled' : ''
                                    }}>
                                <label class="form-check-label small fw-semibold text-dark" style="cursor:pointer;"
                                    for="send_whatsapp">Send Whatsapp</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <form id="whatsappForm" class="mainForm" data-channel="whatsapp">
                        <input type="hidden" name="logid" value="{{ $whatsappDraft->logid ?? '' }}">
                        <input type="hidden" name="channel" value="whatsapp">

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $whatsappPhone) }}"
                                    class="form-control" {{ !$hasWhatsappTemplate ? 'readonly' : '' }}>
                            </div>

                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message Body</label>
                                <textarea name="body" id="whatsappBodyInput" rows="12" class="form-control" {{
                                    !$hasWhatsappTemplate ? 'readonly' : ''
                                    }}>{{ old('body', $whatsappBody) }}</textarea>

                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="auto-save-status small text-muted" data-channel="whatsapp"
                                style="min-height:1.4em;"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SMS Column -->
        <div class="col-12 col-lg-3">
            <div class="bg-DarkLight p-2 rounded-3 h-100 {{ !$hasSmsTemplate ? 'opacity-50' : '' }}">
                <div
                    class="bg-light border-bottom p-2 rounded-3 d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold small lh-sm mb-0" style="color:#1179c5;">
                        <i class="fas fa-sms fs-6 lh-sm me-1"></i> SMS
                        @if(!$hasSmsTemplate)
                        <span class="text-danger ms-1" style="font-size: 0.75rem;">(No Template)</span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="border-0 p-0 bg-transparent fw-semibold text-dark small lh-sm"
                            data-channel="sms" {{ !$hasSmsTemplate ? 'disabled' : '' }}> Raw Message
                        </button>
                        @if($isSmsSent)
                        <span class="badge bg-success small"><i class="fas fa-check-circle me-1"></i> Sent</span>
                        @endif
                        <div class="bg-white px-2 py-1 rounded-pill border" style="cursor:pointer;">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input channel-select-checkbox" style="cursor:pointer;"
                                    type="checkbox" id="send_sms" data-channel="sms" {{ ($smsDraft && $hasSmsTemplate)
                                    ? 'checked' : '' }} {{ !$hasSmsTemplate ? 'disabled' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" style="cursor:pointer;"
                                    for="send_sms">Send SMS</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <form id="smsForm" class="mainForm" data-channel="sms">
                        <input type="hidden" name="logid" value="{{ $smsDraft->logid ?? '' }}">
                        <input type="hidden" name="channel" value="sms">

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Phone Number</label>
                                <input type="text" name="phone" value="{{ old('phone', $smsPhone) }}"
                                    class="form-control" {{ !$hasSmsTemplate ? 'readonly' : '' }}>
                            </div>

                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Message Body</label>
                                <textarea name="body" id="smsBodyInput" rows="12" class="form-control" {{
                                    !$hasSmsTemplate ? 'readonly' : '' }}>{{ old('body', $smsBody) }}</textarea>

                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="auto-save-status small text-muted" data-channel="sms"
                                style="min-height:1.4em;"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">
        <button type="button" id="globalSendBtn" class="btn btn-primary text-white fw-medium">
            Send to Client <i class="fas fa-arrow-right btn-icon ms-1"></i>
        </button>
    </div>
</section>

<!-- Raw Message Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fw-semibold" id="previewModalLabel">Raw Message Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-3">
                <div id="modalPreviewSubjectArea" class="mb-3" style="display: none;">
                    <div class="small fw-semibold text-muted mb-1">Subject</div>
                    <div id="modalPreviewSubject" class="p-2 bg-white rounded border fw-semibold text-dark"></div>
                </div>
                <div class="mb-3">
                    <div class="small fw-semibold text-muted mb-1">Message Content</div>
                    <pre id="modalPreviewBody" class="p-3 bg-white rounded border mb-0 text-dark"
                        style="white-space: pre-wrap; word-break: break-word; min-height: 120px; font-family: inherit; font-size: 0.92rem;"></pre>
                </div>
                <div id="modalPreviewAttachmentsArea" class="mb-0" style="display: none;">
                    <div class="small fw-semibold text-muted mb-1">Attachments</div>
                    <div id="modalPreviewAttachments" class="small text-break"></div>
                </div>
            </div>
            <div class="modal-footer bg-white py-2 border-top-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script type="application/json" id="template-catalog-data">{!! json_encode($templateCatalog ?? []) !!}</script>
<script type="application/json"
    id="fallback-templates-data">{!! json_encode($fallbackTemplatesByType ?? []) !!}</script>
<script type="application/json"
    id="saved-custom-attachments-data">{!! json_encode($emailCustomAttachmentUrls ?? []) !!}</script>
<script type="application/json" id="channel-availability-data">{!! json_encode([
    'email' => $hasEmailTemplate,
    'whatsapp' => $hasWhatsappTemplate,
    'sms' => $hasSmsTemplate
]) !!}</script>

<script>
    (function () {
        const hasTiNumber = "{{ $hasTiNumber ? '1' : '' }}" === "1";
        const piPdfUrl = "{{ route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']) }}";
        const tiPdfUrl = "{{ route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']) }}";

        const typeBtns = Array.from(document.querySelectorAll('.type-tab-btn'));
        const attachmentBodyHint = document.getElementById('attachmentBodyHint');
        const customAttachmentInput = document.getElementById('customAttachmentInput');
        let savedCustomAttachmentUrls = JSON.parse(document.getElementById('saved-custom-attachments-data').textContent || '[]');

        const emailSubjectInput = document.getElementById('emailSubjectInput');
        const emailBodyInput = document.getElementById('emailBodyInput');
        const currentCustomAttachment = document.getElementById('currentCustomAttachment');

        const templateCatalog = JSON.parse(document.getElementById('template-catalog-data').textContent || '{}');
        const fallbackTemplatesByType = JSON.parse(document.getElementById('fallback-templates-data').textContent || '{}');
        const channelAvailability = JSON.parse(document.getElementById('channel-availability-data').textContent || '{}');

        const storeUrl = "{{ route('invoices.email-compose.store', $invoice->invoiceid) }}";
        const redirectUrl = "{{ route('invoices.index', ['c' => $invoice->clientid]) }}";

        let currentType = "{{ $defaultType }}";
        let dscPreviewUrls = [];

        const INVOICE_COMPOSE_READY_TOAST_KEY = 'invoice_compose_ready_toast';

        function showSuccessToast(message) {
            const text = String(message || '').trim();
            if (!text) return;
            let container = document.getElementById('app-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'app-toast-container';
                container.className = 'app-toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = 'app-toast app-toast-success';
            toast.innerHTML = '<i class="fas fa-check-circle toast-icon"></i><span></span>';
            const label = toast.querySelector('span');
            if (label) label.textContent = text;
            toast.addEventListener('click', () => toast.remove());
            container.appendChild(toast);
            window.setTimeout(() => {
                toast.classList.add('app-toast-leaving');
                window.setTimeout(() => toast.remove(), 220);
            }, 4200);
        }

        function consumeComposeReadyToast() {
            try {
                const message = window.localStorage.getItem(INVOICE_COMPOSE_READY_TOAST_KEY);
                if (!message) return;
                window.localStorage.removeItem(INVOICE_COMPOSE_READY_TOAST_KEY);
                showSuccessToast(message);
            } catch (e) {
                console.warn('Unable to read compose-ready toast state', e);
            }
        }
        consumeComposeReadyToast();

        function buildPreviewAttachments() {
            const files = [];
            const selectedTemplate = getSelectedTemplateForCurrentSelection(currentType, 'email');
            const selectedHeaderType = String(selectedTemplate?.header_type || '').toLowerCase();

            if (currentType === 'pi') {
                files.push({
                    label: 'Proforma Invoice (PI).pdf',
                    url: piPdfUrl
                });
            }
            if (currentType === 'ti' && hasTiNumber) {
                files.push({
                    label: 'Tax Invoice (TI).pdf',
                    url: tiPdfUrl
                });
            }

            const selectedFiles = Array.from(customAttachmentInput?.files || []);
            const selectedAttachments = selectedFiles.map((file, index) => ({
                label: 'Attachment ' + (index + 1),
                url: dscPreviewUrls[index] || null
            })).filter((item) => !!item.url);
            const savedAttachments = selectedFiles.length === 0
                ? (savedCustomAttachmentUrls || []).map((url, index) => ({
                    label: 'Attachment ' + (index + 1),
                    url
                }))
                : [];
            [...savedAttachments, ...selectedAttachments].forEach((item) => files.push(item));

            return files;
        }

        function refreshAttachmentPreview() {
            const labels = [];
            if (currentType === 'pi') labels.push('PI PDF');
            if (currentType === 'ti') labels.push('TI PDF');

            const totalAttachments = labels.length + (savedCustomAttachmentUrls || []).length + (customAttachmentInput?.files || []).length;
            if (attachmentBodyHint) {
                attachmentBodyHint.textContent = totalAttachments > 0 ? ('Attached: ' + labels.join(', ') + ' (' + totalAttachments + ' total file(s))') : 'No attachments selected.';
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

        function toEditorHtml(value) {
            const normalizedBody = normalizeHtmlForEditor(value || '');
            if (normalizedBody && !/<[a-z][\s\S]*>/i.test(normalizedBody)) {
                return normalizedBody.replace(/\r\n|\r|\n/g, '<br>');
            }
            return normalizedBody;
        }

        function getPlainTextFromHtml(html) {
            if (!html) return '';
            let text = html;

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

            const tmp = document.createElement('DIV');
            tmp.innerHTML = text;
            const out = tmp.textContent || tmp.innerText || '';
            return out.replace(/\u00a0/g, ' ').replace(/\n{3,}/g, '\n\n');
        }

        function isImageAttachment(url) {
            return /\.(png|jpe?g|gif|webp|bmp|svg)(\?.*)?$/i.test(String(url || ''));
        }

        function renderCurrentCustomAttachment() {
            if (!currentCustomAttachment) return;

            const selectedFiles = Array.from(customAttachmentInput?.files || []);
            const attachments = selectedFiles.length > 0
                ? selectedFiles.map((file, index) => ({
                    name: 'Attachment ' + (index + 1),
                    url: dscPreviewUrls[index] || null,
                    fileType: file?.type || ''
                })).filter((item) => !!item.url)
                : (savedCustomAttachmentUrls || []).map((url, index) => ({
                    name: 'Attachment ' + (index + 1),
                    url,
                    fileType: ''
                }));

            if (attachments.length === 0) {
                currentCustomAttachment.innerHTML = '<span class="small text-muted">No extra attachment selected.</span>';
                return;
            }
            const imageRows = attachments.filter((item) => isImageAttachment(item.url));
            const fileRows = attachments.filter((item) => !isImageAttachment(item.url));

            const imageHtml = imageRows.length
                ? ('<div class="small text-muted mb-1 mt-2">Image preview: ' +

                    imageRows.map((item) => (
                        '<a href="' + item.url + '" target="_blank" class="text-decoration-none">' +
                        '<img src="' + item.url + '" alt="' + String(item.name).replace(/"/g, '&quot;') + '"' +
                        ' style="height:60px;width:60px;border:1px solid #e2e8f0;border-radius:8px;padding:2px;background:#fff;object-fit:cover;">' +
                        '</a>'
                    )).join('') +
                    '</div>')
                : '';
            currentCustomAttachment.innerHTML =
                '<div class="small text-muted mb-1">Current attachments: ' + fileRows.concat(imageRows).map((item) => (
                    '<a href="' + item.url + '" target="_blank">' + item.name + '</a>'
                )).join(', ') + '</div>' + imageHtml;
        }

        function getTemplatesForSelection(type, channel) {
            return ((templateCatalog[type] || {})[channel] || []);
        }

        function getSelectedTemplateForCurrentSelection(type, channel) {
            const options = getTemplatesForSelection(type, channel);
            if (options.length > 0) {
                return options[0];
            }
            return null;
        }

        function applyTemplate(channel, preserveExisting = false) {
            const options = getTemplatesForSelection(currentType, channel);
            let payload = null;
            if (options.length > 0) {
                payload = options[0];
            }

            if (!payload) {
                payload = fallbackTemplatesByType[currentType] || {
                    subject: '',
                    body: ''
                };
            }

            const nextSubject = (payload.subject || '').trim();
            const nextBody = payload.body || '';

            if (preserveExisting) {
                return;
            }

            if (channel === 'email') {
                if (emailSubjectInput) {
                    emailSubjectInput.value = nextSubject;
                }
                if (window.tinymce && tinymce.get('emailBodyInput')) {
                    const editor = tinymce.get('emailBodyInput');
                    editor.setContent(toEditorHtml(nextBody));
                    editor.save();
                } else if (emailBodyInput) {
                    emailBodyInput.value = normalizeHtmlForEditor(nextBody);
                }
            } else {
                const bodyInput = document.getElementById(channel + 'BodyInput');
                if (bodyInput) {
                    bodyInput.value = getPlainTextFromHtml(nextBody || '');
                }
            }
        }

        // Initialize TinyMCE for Email Body
        function enableTinyMceForEmail() {
            if (!window.tinymce || !emailBodyInput) return;
            if (tinymce.get('emailBodyInput')) return;

            const hasEmailTemplate = channelAvailability.email;

            tinymce.init({
                license_key: 'gpl',
                selector: '#emailBodyInput',
                readonly: !hasEmailTemplate,
                menubar: false,
                height: 240,
                plugins: 'lists link table code autoresize',
                toolbar: hasEmailTemplate ? 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code' : false,
                valid_elements: '*[*]',
                extended_valid_elements: 'style[type|media],link[rel|href|type|media],meta[charset|name|content]',
                setup: function (editor) {
                    editor.on('BeforeSetContent', function (e) {
                        e.content = normalizeHtmlForEditor(e.content || '');
                    });
                    editor.on('init', function () {
                        const initialBody = emailBodyInput?.value || '';
                        editor.setContent(toEditorHtml(initialBody));
                        editor.save();
                    });
                    editor.on('input change keyup setcontent undo redo ExecCommand NodeChange',
                        function () {
                            editor.save();
                        });
                }
            });
        }
        enableTinyMceForEmail();

        // Attachments change listener
        customAttachmentInput?.addEventListener('change', function () {
            (dscPreviewUrls || []).forEach((url) => {
                if (url && !(savedCustomAttachmentUrls || []).includes(url)) {
                    URL.revokeObjectURL(url);
                }
            });
            const selectedFiles = Array.from(customAttachmentInput.files || []);
            dscPreviewUrls = selectedFiles.map((file) => URL.createObjectURL(file));
            refreshAttachmentPreview();
            renderCurrentCustomAttachment();
        });

        // Initialize templates
        const channels = ['email', 'whatsapp', 'sms'];
        channels.forEach(channel => {
            const draftInput = document.querySelector(`#${channel}Form input[name="logid"]`);
            const draftExists = !!(draftInput?.value || '').trim();

            applyTemplate(channel, draftExists);
        });

        // Type tabs listeners (reloads with updated attachment_type context)
        typeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const newType = btn.dataset.type;
                if (newType === currentType) return;

                const url = new URL(window.location.href);
                url.searchParams.set('attachment_type', newType);
                url.searchParams.delete('e');
                window.location.href = url.toString();
            });
        });

        // ── Auto-save ──────────────────────────────────────────────────────────
        const autoSaveTimers = {};
        const AUTO_SAVE_DELAY = 1200; // ms debounce

        function setAutoSaveStatus(channel, state) {
            const el = document.querySelector('.auto-save-status[data-channel="' + channel + '"]');
            if (!el) return;
            if (state === 'saving') {
                el.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving…';
                el.className = 'auto-save-status small text-muted';
            } else if (state === 'saved') {
                el.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i> <span class="text-success">Saved</span>';
            } else if (state === 'error') {
                el.innerHTML = '<i class="fas fa-exclamation-circle me-1 text-danger"></i> <span class="text-danger">Save failed</span>';
            } else {
                el.innerHTML = '';
            }
        }

        async function doAutoSave(channel) {
            const form = document.getElementById(channel + 'Form');
            if (!form) return;

            if (channel === 'email' && window.tinymce && tinymce.get('emailBodyInput')) {
                tinymce.get('emailBodyInput').save();
            }

            const formData = new FormData(form);
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('action', 'save');
            formData.append('channel', channel);
            formData.append('attachment_type', currentType);

            setAutoSaveStatus(channel, 'saving');

            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (response.ok && data.success) {
                    const logidInput = form.querySelector('input[name="logid"]');
                    if (logidInput && data.logid) {
                        logidInput.value = data.logid;
                    }
                    if (channel === 'email' && data.customAttachmentUrls) {
                        savedCustomAttachmentUrls = data.customAttachmentUrls;
                        renderCurrentCustomAttachment();
                        refreshAttachmentPreview();
                    }
                    setAutoSaveStatus(channel, 'saved');
                } else {
                    setAutoSaveStatus(channel, 'error');
                    console.warn('[auto-save] Failed for', channel, data.message);
                }
            } catch (error) {
                setAutoSaveStatus(channel, 'error');
                console.error('[auto-save] Error for', channel, error);
            }
        }

        function scheduleAutoSave(channel) {
            clearTimeout(autoSaveTimers[channel]);
            setAutoSaveStatus(channel, null); // clear previous status while typing
            autoSaveTimers[channel] = setTimeout(() => doAutoSave(channel), AUTO_SAVE_DELAY);
        }

        // Wire up input/change listeners for plain inputs in each form
        ['email', 'whatsapp', 'sms'].forEach(channel => {
            const form = document.getElementById(channel + 'Form');
            if (!form) return;
            form.addEventListener('input', () => scheduleAutoSave(channel));
            form.addEventListener('change', (e) => {
                // skip file inputs – attachments need explicit save
                if (e.target && e.target.type === 'file') return;
                scheduleAutoSave(channel);
            });
        });

        // Wire TinyMCE editor changes for email auto-save
        (function wireEmailEditorAutoSave() {
            const check = () => {
                if (window.tinymce && tinymce.get('emailBodyInput')) {
                    tinymce.get('emailBodyInput').on('input keyup change setcontent undo redo ExecCommand', () => {
                        scheduleAutoSave('email');
                    });
                } else {
                    setTimeout(check, 300);
                }
            };
            check();
        })();

        // Preview raw message handler
        document.querySelectorAll('.preview-channel-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const channel = this.dataset.channel;
                const modalTitle = document.getElementById('previewModalLabel');
                const modalSubjectArea = document.getElementById('modalPreviewSubjectArea');
                const modalSubject = document.getElementById('modalPreviewSubject');
                const modalBody = document.getElementById('modalPreviewBody');
                const modalAttachmentsArea = document.getElementById('modalPreviewAttachmentsArea');
                const modalAttachments = document.getElementById('modalPreviewAttachments');

                modalTitle.textContent = 'Preview Message - ' + channel.toUpperCase();

                const selectedTemplate = getSelectedTemplateForCurrentSelection(currentType, channel);
                const hasRawTemplateBody = typeof selectedTemplate?.raw_body === 'string' && selectedTemplate.raw_body.trim() !== '';
                const rawTemplateBody = hasRawTemplateBody ? selectedTemplate.raw_body : '';

                let activeBody = '';
                if (channel === 'email') {
                    if (window.tinymce && tinymce.get('emailBodyInput')) {
                        activeBody = tinymce.get('emailBodyInput').getContent();
                    } else {
                        activeBody = emailBodyInput.value;
                    }
                } else {
                    activeBody = document.getElementById(channel + 'BodyInput').value;
                }

                if (channel === 'email') {
                    modalBody.innerHTML = rawTemplateBody || activeBody || '(No content)';
                } else {
                    modalBody.textContent = rawTemplateBody || activeBody || '(No content)';
                }

                if (channel === 'email') {
                    modalSubjectArea.style.display = 'block';
                    modalSubject.textContent = emailSubjectInput.value || '(No Subject)';

                    modalAttachmentsArea.style.display = 'block';
                    const files = buildPreviewAttachments();
                    if (files.length === 0) {
                        modalAttachments.innerHTML = '<span class="text-muted">No attachments.</span>';
                    } else {
                        modalAttachments.innerHTML = files.map(f => `<a href="${f.url}" target="_blank" class="d-block mt-1"><i class="fas fa-file-pdf text-danger me-1"></i> ${f.label}</a>`).join('');
                    }
                } else if (channel === 'whatsapp') {
                    modalSubjectArea.style.display = 'none';
                    const selectedHeaderType = String(selectedTemplate?.header_type || '').toLowerCase();
                    const canAttach = selectedHeaderType === 'document';
                    if (canAttach) {
                        modalAttachmentsArea.style.display = 'block';
                        const files = [];
                        if (currentType === 'pi') files.push({ label: 'Proforma Invoice (PI).pdf', url: piPdfUrl });
                        if (currentType === 'ti' && hasTiNumber) files.push({ label: 'Tax Invoice (TI).pdf', url: tiPdfUrl });
                        modalAttachments.innerHTML = files.map(f => `<a href="${f.url}" target="_blank" class="d-block mt-1"><i class="fas fa-file-pdf text-danger me-1"></i> ${f.label}</a>`).join('');
                    } else {
                        modalAttachmentsArea.style.display = 'none';
                    }
                } else {
                    modalSubjectArea.style.display = 'none';
                    modalAttachmentsArea.style.display = 'none';
                }

                const modalInstance = new bootstrap.Modal(document.getElementById('previewModal'));
                modalInstance.show();
            });
        });

        // Global Send to Client handler
        document.getElementById('globalSendBtn')?.addEventListener('click', async function () {
            const selectedCheckboxes = Array.from(document.querySelectorAll('.channel-select-checkbox:checked'));
            if (selectedCheckboxes.length === 0) {
                Swal.fire('No Channel Selected', 'Please select at least one channel to send.', 'warning');
                return;
            }

            const channelsToSend = selectedCheckboxes.map(cb => cb.dataset.channel);

            const confirmMessage = 'Are you sure you want to send the invoice via: ' + channelsToSend.map(c => c.toUpperCase()).join(', ') + '?';
            const confirmed = await window.appConfirm(confirmMessage, {
                title: 'Confirm Sending',
                icon: 'question',
                confirmButtonText: 'Yes, Send'
            });

            if (!confirmed) return;

            Swal.fire({
                title: 'Sending communications...',
                text: 'Please wait...',
                allowOutsideClick: false,
                width: 340,
                buttonsStyling: false,
                customClass: {
                    popup: 'app-swal-popup',
                    title: 'app-swal-title',
                    htmlContainer: 'app-swal-text',
                    confirmButton: 'app-swal-btn app-swal-btn-confirm',
                    cancelButton: 'app-swal-btn app-swal-btn-cancel',
                    icon: 'app-swal-icon',
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            for (const channel of channelsToSend) {
                const form = document.getElementById(channel + 'Form');
                if (!form) continue;

                if (channel === 'email' && window.tinymce && tinymce.get('emailBodyInput')) {
                    tinymce.get('emailBodyInput').save();
                }

                const formData = new FormData(form);
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('action', 'send');
                formData.append('channel', channel);
                formData.append('attachment_type', currentType);

                try {
                    const response = await fetch(storeUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        window.appAlert(`Failed to send via ${channel.toUpperCase()}: ${data.message || 'Unknown error'}`, { title: 'Sending Failed', icon: 'error' });
                        return;
                    }

                    const logidInput = form.querySelector('input[name="logid"]');
                    if (logidInput && data.logid) {
                        logidInput.value = data.logid;
                    }
                } catch (error) {
                    console.error(error);
                    window.appAlert(`An unexpected error occurred while sending via ${channel.toUpperCase()}.`, { title: 'Error', icon: 'error' });
                    return;
                }
            }

            Swal.fire({
                title: 'Sent Successfully!',
                text: 'The invoice communications have been sent to the client.',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'View Invoices',
                cancelButtonText: 'OK',
                width: 360,
                buttonsStyling: false,
                customClass: {
                    popup: 'app-swal-popup',
                    title: 'app-swal-title',
                    htmlContainer: 'app-swal-text',
                    confirmButton: 'app-swal-btn app-swal-btn-confirm',
                    cancelButton: 'app-swal-btn app-swal-btn-cancel',
                    icon: 'app-swal-icon',
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = redirectUrl;
                } else {
                    window.location.reload();
                }
            });
        });

        // Initialize attachment renders on page load
        refreshAttachmentPreview();
        renderCurrentCustomAttachment();
    })();
</script>
@endsection
