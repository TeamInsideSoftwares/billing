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
            <a href="{{ route('invoices.create', ['step' => 4, 'invoice_for' => $invoice->invoice_for, 'c' => $invoice->clientid, 'd' => $invoice->invoiceid, 'o' => $invoice->orderid]) }}" class="secondary-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="vr"></div>
            <div class="d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:#e0e7ff;color:#4f46e5;">
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Email Preview</h4>
        <span class="text-muted small">Same invoice process: compose before sending</span>
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

    <form method="POST" action="{{ route('invoices.email-compose.store', $invoice->invoiceid) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="invoice_emailid" value="{{ $composeEmail->invoice_emailid }}">

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-7">
                <div class="border rounded p-2 bg-light mb-2">
                    <div class="fw-semibold text-uppercase mb-1" style="font-size: 0.7rem;">Attachment Type</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center attachment-toggle-group">
                        <label class="attachment-toggle mb-0">
                            <input type="checkbox" class="doc-choice" name="attachment_types[]" value="pi" {{ in_array('pi', old('attachment_types', $prefillAttachmentTypes ?? [$defaultType]), true) ? 'checked' : '' }}>
                            <span>PI</span>
                        </label>
                        <label class="attachment-toggle mb-0">
                            <input type="checkbox" class="doc-choice" name="attachment_types[]" value="ti" {{ in_array('ti', old('attachment_types', $prefillAttachmentTypes ?? [$defaultType]), true) ? 'checked' : '' }}>
                            <span>TI</span>
                        </label>
                        <label class="attachment-toggle mb-0">
                            <input type="checkbox" class="doc-choice" name="attachment_types[]" value="dsc" {{ in_array('dsc', old('attachment_types', $prefillAttachmentTypes ?? []), true) ? 'checked' : '' }}>
                            <span>DSC</span>
                        </label>
                    </div>
                    <p id="attachmentHelp" class="text-muted mt-1 mb-0" style="font-size: 0.72rem;"></p>
                </div>

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

                <div class="mb-3">
                    <label class="field-label">Subject</label>
                    <input type="text" name="subject" id="emailSubjectInput" value="{{ old('subject', $prefillSubject ?? ('Invoice ' . ($defaultSubjectNumber ?: $invoice->invoice_number))) }}" class="input-full">
                </div>

                <div class="mb-3">
                    <label class="field-label">Body</label>
                    <textarea name="body" id="emailBodyInput" rows="8" class="input-full">{{ old('body', $prefillBody ?? $defaultBody) }}</textarea>
                    <div id="attachmentBodyHint" class="text-secondary small mt-1"></div>
                </div>

                <div class="mb-3">
                    <label class="field-label">Extra Attachment (optional)</label>
                    <input type="file" name="custom_attachment" id="customAttachmentInput" class="input-full">
                </div>

                <div class="d-flex justify-content-end flex-wrap gap-2">
                    <button type="submit" name="action" value="save" class="secondary-button">Save Email</button>
                    <button type="submit" name="action" value="send" class="primary-button">Send to Client</button>
                    <a href="{{ route('invoices.create') }}" class="secondary-button">Create New Invoice</a>
                    <a href="{{ route('payments.create', ['invoiceid' => $invoice->invoiceid, 'clientid' => $invoice->clientid]) }}" class="secondary-button">Record Payment</a>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="border rounded overflow-hidden position-sticky" style="top:.8rem;">
                    <div class="bg-light border-bottom px-3 py-2 small fw-semibold">Email Preview (how receiver sees it)</div>
                    <div class="p-3">
                        <div class="small mb-2">
                            <span class="text-muted">Subject:</span>
                            <span id="previewSubject" class="fw-semibold text-dark"></span>
                        </div>
                        <div class="small mb-1 text-muted">Body:</div>
                        <div id="previewBody" class="mb-3 mt-0"></div>
                        <div class="small mb-1 text-muted">Attachment:</div>
                        <div id="previewAttachmentList" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<style>
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
    min-width: 54px;
    padding: 0.3rem 0.65rem;
    font-size: 0.74rem;
    font-weight: 600;
    color: #334155;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.attachment-toggle input:checked + span {
    background: #e0e7ff;
    border-color: #6366f1;
    color: #3730a3;
}

.attachment-toggle input:disabled + span {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>

<script>
(function () {
    const hasTiNumber = @json($hasTiNumber);
    const piPdfUrl = @json(route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'pi']));
    const tiPdfUrl = @json(route('invoices.pdf', ['invoice' => $invoice->invoiceid, 'type' => 'tax_invoice']));
    const choices = Array.from(document.querySelectorAll('.doc-choice'));
    const help = document.getElementById('attachmentHelp');
    const attachmentBodyHint = document.getElementById('attachmentBodyHint');
    const customAttachmentInput = document.getElementById('customAttachmentInput');
    const emailSubjectInput = document.getElementById('emailSubjectInput');
    const emailBodyInput = document.getElementById('emailBodyInput');
    const previewSubject = document.getElementById('previewSubject');
    const previewBody = document.getElementById('previewBody');
    const previewAttachmentList = document.getElementById('previewAttachmentList');
    let dscPreviewUrl = null;

    const byValue = (value) => choices.find((cb) => cb.value === value);

    function refreshEmailPreview() {
        if (previewSubject) previewSubject.textContent = (emailSubjectInput?.value || '').trim() || '(No subject)';
        if (!previewBody) return;

        if (window.tinymce && tinymce.get('emailBodyInput')) {
            previewBody.innerHTML = tinymce.get('emailBodyInput').getContent() || '<span class="text-muted">(No body)</span>';
        } else {
            const plainBody = (emailBodyInput?.value || '').trim();
            previewBody.innerHTML = plainBody ? plainBody.replace(/\n/g, '<br>') : '<span class="text-muted">(No body)</span>';
        }
    }

    function selectedTypes() {
        return choices.filter((cb) => cb.checked).map((cb) => cb.value);
    }

    function renderAttachmentItem(label, href, enabled = true) {
        const a = document.createElement('a');
        a.className = 'd-inline-flex align-items-center gap-2 border rounded-pill px-2 py-1 text-decoration-none';
        a.href = enabled ? href : '#';
        a.target = '_blank';
        a.style.pointerEvents = enabled ? 'auto' : 'none';
        a.style.opacity = enabled ? '1' : '0.55';
        a.innerHTML = '<i class="fas fa-paperclip text-muted small"></i><span class="small text-dark">' + label + '</span>';
        return a;
    }

    function updateAttachmentPreview() {
        if (!previewAttachmentList) return;
        previewAttachmentList.innerHTML = '';
        const selected = selectedTypes();

        if (selected.includes('pi')) {
            previewAttachmentList.appendChild(renderAttachmentItem('Proforma Invoice (PI).pdf', piPdfUrl, true));
        }
        if (selected.includes('ti')) {
            previewAttachmentList.appendChild(renderAttachmentItem('Tax Invoice (TI).pdf', tiPdfUrl, hasTiNumber));
        }
        if (selected.includes('dsc')) {
            const fileName = customAttachmentInput?.files?.[0]?.name || 'DSC.pdf';
            previewAttachmentList.appendChild(renderAttachmentItem(fileName, dscPreviewUrl || '#', !!dscPreviewUrl));
        }
        if (!selected.length) {
            const muted = document.createElement('span');
            muted.className = 'small text-muted';
            muted.textContent = 'No attachment';
            previewAttachmentList.appendChild(muted);
        }
    }

    function setHelp() {
        const selected = selectedTypes();
        const labels = [];
        if (selected.includes('pi')) labels.push('PI PDF');
        if (selected.includes('ti')) labels.push('TI PDF');
        if (selected.includes('dsc')) labels.push('DSC file');
        help.textContent = labels.length ? ('Attached: ' + labels.join(', ')) : 'No attachment selected.';
        if (attachmentBodyHint) attachmentBodyHint.textContent = help.textContent;
        updateAttachmentPreview();
    }

    function applyBaseRules() {
        const pi = byValue('pi');
        const ti = byValue('ti');
        const dsc = byValue('dsc');

        if (!hasTiNumber) {
            if (pi) {
                pi.checked = true;
                pi.disabled = false;
            }
            if (ti) {
                ti.checked = false;
                ti.disabled = true;
            }
            if (dsc) {
                dsc.checked = false;
                dsc.disabled = true;
            }
            setHelp();
            return;
        }
        choices.forEach((cb) => {
            cb.disabled = false;
        });
        if (!selectedTypes().length && ti) {
            ti.checked = true;
        }
        setHelp();
    }

    choices.forEach((cb) => {
        cb.addEventListener('change', function () {
            if (cb.disabled) return;

            if (!selectedTypes().length) {
                cb.checked = true;
            }

            if (cb.value === 'dsc' && cb.checked && !(customAttachmentInput && customAttachmentInput.files && customAttachmentInput.files.length > 0)) {
                alert('Please upload DSC file.');
                customAttachmentInput?.click();
            }

            setHelp();
        });
    });

    customAttachmentInput?.addEventListener('change', function () {
        if (dscPreviewUrl) {
            URL.revokeObjectURL(dscPreviewUrl);
            dscPreviewUrl = null;
        }
        const selectedFile = customAttachmentInput.files && customAttachmentInput.files.length ? customAttachmentInput.files[0] : null;
        if (selectedFile) dscPreviewUrl = URL.createObjectURL(selectedFile);
        setHelp();
    });

    emailSubjectInput?.addEventListener('input', refreshEmailPreview);
    emailBodyInput?.addEventListener('input', refreshEmailPreview);

    if (window.tinymce && emailBodyInput) {
        tinymce.init({
            selector: '#emailBodyInput',
            menubar: false,
            height: 340,
            plugins: 'lists link table code autoresize',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table link | removeformat code',
            setup: function (editor) {
                editor.on('init', function () {
                    const initialText = emailBodyInput.value || '';
                    if (initialText && !/<[a-z][\s\S]*>/i.test(initialText)) {
                        editor.setContent(initialText.replace(/\r\n|\r|\n/g, '<br>'));
                    }
                    refreshEmailPreview();
                });
                editor.on('input change keyup setcontent undo redo ExecCommand NodeChange', function () {
                    refreshEmailPreview();
                });
            }
        });
    }

    document.querySelector('form')?.addEventListener('submit', function () {
        if (window.tinymce) tinymce.triggerSave();
    });

    refreshEmailPreview();
    applyBaseRules();
})();
</script>
@endsection
