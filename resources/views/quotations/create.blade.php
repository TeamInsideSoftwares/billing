@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('quotations.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left"></i> Back to Quotations
    </a>
@endsection

@section('content')
@php
    $step = (int) ($currentStep ?? 1);
    $step = max(1, min(4, $step));
    $clientId = old('clientid', $selectedClientId ?? request('c', ''));
    $selectedClientName = $selectedClient ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Client') : 'No Client Selected';
    $selectedClientEmail = $selectedClient->primary_email ?? $selectedClient->email ?? '';
    $quotationDateBounds = $quotationDateBounds ?? [
        'min_date' => date('Y-m-d'),
        'max_date' => date('Y-m-d'),
        'default_issue_date' => '',
        'default_due_date' => '',
    ];
@endphp

<section class="panel-card">
    @if ($errors->any())
        <div class="alert warning mb-3">
            <ul class="plain-list mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if($step === 1)
        @include('quotations.steps.step1-client')
    @elseif($step === 2)
        @include('quotations.steps.step2-items')
    @elseif($step === 3)
        @include('quotations.steps.step3-preview-terms')
    @elseif($step === 4)
        @include('quotations.steps.step4-compose')
    @endif
</section>

@php
    $serverDraftPayload = null;
    if (isset($draftQuotation) && $draftQuotation) {
        $serverDraftPayload = [
            'quotationid' => $draftQuotation->quotationid,
            'quo_number' => $draftQuotation->quo_number,
            'quo_title' => $draftQuotation->quo_title,
            'issue_date' => optional($draftQuotation->issue_date)->format('Y-m-d'),
            'due_date' => optional($draftQuotation->due_date)->format('Y-m-d'),
            'notes' => $draftQuotation->notes,
            'items' => $draftQuotation->items->map(function ($item) {
                return [
                    'itemid' => $item->itemid,
                    'item_name' => $item->item_name,
                    'item_category' => optional(optional($item->item)->category)->name ?? '',
                    'item_description' => $item->item_description,
                    'quantity' => (float) ($item->quantity ?? 1),
                    'unit_price' => (float) ($item->unit_price ?? 0),
                    'discount_percent' => (float) ($item->discount_percent ?? 0),
                    'tax_rate' => (float) ($item->tax_rate ?? 0),
                    'line_total' => (float) ($item->amount ?? 0),
                    'frequency' => (string) ($item->frequency ?? ''),
                    'duration' => !empty($item->duration) ? (int) $item->duration : null,
                    'no_of_users' => !empty($item->no_of_users) ? (int) $item->no_of_users : null,
                    'start_date' => !empty($item->start_date) ? $item->start_date->format('Y-m-d') : null,
                    'end_date' => !empty($item->end_date) ? $item->end_date->format('Y-m-d') : null,
                ];
            })->values()->all(),
        ];
    }
@endphp

<script>
(function () {
    const serverDraft = @json($serverDraftPayload);

    const step = {{ $step }};
    const clientId = @json($clientId);
    const draftIdParam = @json((string) request('d', ''));

    if (step === 1) {
        const btn = document.getElementById('toStep2');
        const client = document.getElementById('clientid');
        btn?.addEventListener('click', function () {
            if (!client.value) return alert('Please select a client.');
            let target = "{{ route('quotations.create') }}?step=2&c=" + encodeURIComponent(client.value);
            if (draftIdParam) {
                target += "&d=" + encodeURIComponent(draftIdParam);
            }
            window.location.href = target;
        });
    }

    if (step === 2) {
        const draftItems = serverDraft && Array.isArray(serverDraft.items) ? serverDraft.items : [];
        const draftMeta = serverDraft ? {
            draft_id: serverDraft.quotationid || '',
            quo_title: serverDraft.quo_title || '',
            quo_number: serverDraft.quo_number || '',
            issue_date: serverDraft.issue_date || '',
            due_date: serverDraft.due_date || '',
            notes: serverDraft.notes || '',
        } : {};
        let items = draftItems.slice();
        let meta = { ...draftMeta };
        let editingItemIndex = null;
        const accountHasUsers = @json((bool) ($account->have_users ?? false));
        const itemSelect = document.getElementById('itemid');
        const qty = document.getElementById('quantity');
        const unitPrice = document.getElementById('unit_price');
        const discount = document.getElementById('discount_percent');
        const users = document.getElementById('no_of_users');
        const usersWrap = document.getElementById('usersWrap');
        const usersColHeader = document.getElementById('usersColHeader');
        const frequency = document.getElementById('frequency');
        const duration = document.getElementById('duration');
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const durationWrap = document.getElementById('durationWrap');
        const startDateWrap = document.getElementById('startDateWrap');
        const endDateWrap = document.getElementById('endDateWrap');
        const description = document.getElementById('item_description');
        const builderCard = document.querySelector('#step2 .invoice-builder-card');
        const addBtn = document.getElementById('addItem');
        const body = document.getElementById('itemsBody');
        const itemsTable = document.getElementById('itemsTable');
        const itemsEmpty = document.getElementById('itemsEmpty');
        const quoteSummary = document.getElementById('quoteSummary');
        const summarySubtotal = document.getElementById('summarySubtotal');
        const summaryDiscount = document.getElementById('summaryDiscount');
        const summaryTax = document.getElementById('summaryTax');
        const summaryTotal = document.getElementById('summaryTotal');
        const toStep3 = document.getElementById('toStep3');
        const quoTitleInput = document.getElementById('quo_title');
        const issueDateInput = document.getElementById('issue_date');
        const dueDateInput = document.getElementById('due_date');
        const notesInput = document.getElementById('notes');
        const quoNumberBadge = document.getElementById('quoNumberBadge');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const quotationDateBounds = @json($quotationDateBounds ?? []);
        const minQuotationDate = String(quotationDateBounds.min_date || '');
        const issueMaxQuotationDate = String(quotationDateBounds.issue_max_date || quotationDateBounds.max_date || '');
        const dueMaxQuotationDate = String(quotationDateBounds.due_max_date || quotationDateBounds.max_date || '');
        if (issueDateInput) {
            if (minQuotationDate) issueDateInput.min = minQuotationDate;
            if (issueMaxQuotationDate) issueDateInput.max = issueMaxQuotationDate;
        }
        if (dueDateInput) {
            if (minQuotationDate) dueDateInput.min = minQuotationDate;
            if (dueMaxQuotationDate) dueDateInput.max = dueMaxQuotationDate;
        }

        if (meta.quo_title) quoTitleInput.value = meta.quo_title;
        if (meta.quo_number) quoNumberBadge.textContent = meta.quo_number;
        if (issueDateInput && meta.issue_date) issueDateInput.value = meta.issue_date;
        if (dueDateInput && meta.due_date) dueDateInput.value = meta.due_date;
        if (typeof meta.notes === 'string') notesInput.value = meta.notes;
        const today = new Date().toISOString().slice(0, 10);
        if (!startDate.value) startDate.value = today;
        if (!duration.value) duration.value = '1';
        if (!endDate.value) endDate.value = '';

        function setAddButtonState() {
            if (!addBtn) return;
            addBtn.textContent = editingItemIndex === null ? 'Add' : 'Update';
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('en-US');
        }

        function computeLineTotal(item) {
            const q = Math.max(1, Number(item.quantity || 1));
            const p = Math.max(0, Number(item.unit_price || 0));
            const d = Math.max(0, Math.min(100, Number(item.discount_percent || 0)));
            const sub = q * p;
            const discountAmount = Math.floor(sub * d / 100);
            const taxable = Math.floor(Math.max(0, sub - discountAmount));
            return taxable;
        }

        function recalcSummary(items) {
            let sub = 0, disc = 0, taxTotal = 0, grand = 0;
            items.forEach((it) => {
                const q = Number(it.quantity || 0);
                const p = Number(it.unit_price || 0);
                const d = Number(it.discount_percent || 0);
                const t = Number(it.tax_rate || 0);
                const lineSub = q * p;
                const lineDisc = Math.floor(lineSub * d / 100);
                const taxable = Math.floor(Math.max(0, lineSub - lineDisc));
                const lineTax = Math.ceil(taxable * t / 100);
                sub += lineSub;
                disc += lineDisc;
                taxTotal += lineTax;
                grand += taxable + lineTax;
            });
            summarySubtotal.textContent = Number(sub).toLocaleString('en-US');
            summaryDiscount.textContent = Number(disc).toLocaleString('en-US');
            summaryTax.textContent = Number(taxTotal).toLocaleString('en-US');
            summaryTotal.textContent = Number(grand).toLocaleString('en-US');
            quoteSummary.classList.toggle('hidden', items.length === 0);
        }

        function render() {
            const showUsersColumn = accountHasUsers && items.some(it => Number(it.no_of_users || 0) > 0);
            usersColHeader?.classList.toggle('hidden', !showUsersColumn);
            body.innerHTML = '';
            if (itemsTable) {
                itemsTable.classList.toggle('hidden', items.length === 0);
            }
            if (itemsEmpty) {
                itemsEmpty.classList.toggle('hidden', items.length > 0);
            }
            items.forEach((it, idx) => {
                const tr = document.createElement('tr');
                if (editingItemIndex === idx) {
                    tr.classList.add('is-active');
                }
                const itemDescription = String(it.item_description || '').trim();
                const itemLabel = itemDescription
                    ? `${it.item_name}<div class="text-xs text-slate-500">${itemDescription}</div>`
                    : it.item_name;
                const isUserWise = Number(it.no_of_users || 0) > 0;
                const lineTotal = computeLineTotal(it);
                const usersCell = showUsersColumn ? `<td class="text-center">${isUserWise ? formatNumber(it.no_of_users) : '-'}</td>` : '';
                tr.innerHTML = `<td>${itemLabel}</td><td>${formatNumber(it.quantity)}</td><td>${formatNumber(it.unit_price)}</td><td>${formatNumber(it.discount_percent)}</td>${usersCell}<td>${it.frequency || '-'}</td><td>${it.duration || '-'}</td><td>${it.start_date || '-'}</td><td>${it.end_date || '-'}</td><td>${formatNumber(lineTotal)}</td><td><button type="button" data-edit="${idx}" class="text-action-btn edit">Edit</button> <button type="button" data-i="${idx}" class="text-action-btn delete">Remove</button></td>`;
                body.appendChild(tr);
            });
            body.querySelectorAll('button[data-i]').forEach(btn => btn.addEventListener('click', function () {
                items.splice(Number(this.dataset.i), 1);
                render();
            }));
            body.querySelectorAll('button[data-edit]').forEach(btn => btn.addEventListener('click', function () {
                const index = Number(this.dataset.edit);
                const item = items[index];
                if (!item) return;

                itemSelect.value = item.itemid || '';
                qty.value = item.quantity ?? 1;
                unitPrice.value = item.unit_price ?? '';
                discount.value = item.discount_percent ?? 0;
                frequency.value = item.frequency || '';
                duration.value = item.duration || 1;
                startDate.value = item.start_date || today;
                endDate.value = item.end_date || '';
                description.value = item.item_description || '';

                itemSelect.dispatchEvent(new Event('change'));
                if (isSelectedItemUserWise()) {
                    users.value = item.no_of_users || 1;
                }
                syncFrequencyFields();
                editingItemIndex = index;
                setAddButtonState();
                render();
            }));
            recalcSummary(items);
        }

        function addByFrequency(baseDate, freq, durationValue) {
            const d = new Date(baseDate + 'T00:00:00');
            if (Number.isNaN(d.getTime())) return '';
            const n = Math.max(1, Number(durationValue || 1));
            switch ((freq || '').trim()) {
                case 'Day(s)':
                    d.setDate(d.getDate() + n);
                    break;
                case 'Week(s)':
                    d.setDate(d.getDate() + (7 * n));
                    break;
                case 'Month(s)':
                    d.setMonth(d.getMonth() + n);
                    break;
                case 'Quarter(s)':
                    d.setMonth(d.getMonth() + (3 * n));
                    break;
                case 'Year(s)':
                    d.setFullYear(d.getFullYear() + n);
                    break;
                case 'One-Time':
                default:
                    break;
            }
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function minusOneDay(dateStr) {
            const d = new Date(dateStr + 'T00:00:00');
            if (Number.isNaN(d.getTime())) return '';
            d.setDate(d.getDate() - 1);
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function syncStartEndDates() {
            const freq = (frequency.value || '').trim();
            const base = startDate.value || today;
            const dur = Math.max(1, Number(duration.value || 1));
            if (!startDate.value) startDate.value = base;
            if (!freq || freq === 'One-Time') {
                endDate.value = freq === 'One-Time' ? '2099-12-31' : '';
                return;
            }
            const nextCycleDate = addByFrequency(base, freq, dur);
            endDate.value = nextCycleDate ? minusOneDay(nextCycleDate) : '';
        }

        function isSelectedItemUserWise() {
            const option = itemSelect?.options?.[itemSelect.selectedIndex];
            return String(option?.dataset?.userWise ?? '0') === '1';
        }

        function syncUsersField() {
            if (!accountHasUsers) {
                if (usersWrap) usersWrap.style.display = 'none';
                if (users) users.value = '1';
                return;
            }
            const show = isSelectedItemUserWise();
            if (usersWrap) usersWrap.style.display = show ? '' : 'none';
            if (!show) {
                users.value = '1';
            }
        }

        function syncFrequencyFields() {
            const freq = (frequency.value || '').trim();
            if (!freq) {
                durationWrap.style.display = 'none';
                startDateWrap.style.display = '';
                endDateWrap.style.display = '';
                syncStartEndDates();
                return;
            }

            if (freq === 'One-Time') {
                durationWrap.style.display = 'none';
                startDateWrap.style.display = '';
                endDateWrap.style.display = '';
                syncStartEndDates();
                return;
            }

            durationWrap.style.display = '';
            startDateWrap.style.display = '';
            endDateWrap.style.display = '';
            syncStartEndDates();
        }

        itemSelect?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            unitPrice.value = opt?.dataset?.unitPrice || '';
            description.value = opt?.dataset?.description || '';
            if (opt?.value) {
                builderCard?.classList.add('item-selected');
            } else {
                builderCard?.classList.remove('item-selected');
            }
            syncUsersField();
        });

        frequency?.addEventListener('change', syncFrequencyFields);
        startDate?.addEventListener('change', syncStartEndDates);
        duration?.addEventListener('input', syncStartEndDates);
        syncFrequencyFields();
        syncUsersField();

        addBtn?.addEventListener('click', function () {
            const opt = itemSelect.options[itemSelect.selectedIndex];
            if (!itemSelect.value) return alert('Select item first.');
            const q = Math.max(1, Number(qty.value || 1));
            const p = Math.max(0, Number(unitPrice.value || 0));
            const d = Math.max(0, Math.min(100, Number(discount.value || 0)));
            const t = Math.max(0, Number(opt?.dataset?.taxRate || 0));
            const userWise = accountHasUsers && isSelectedItemUserWise();
            const u = userWise ? Math.max(1, Number(users.value || 1)) : null;
            const sub = q * p;
            const dAmt = Math.floor(sub * d / 100);
            const taxable = Math.floor(Math.max(0, sub - dAmt));
            const total = taxable;

            const payload = {
                itemid: itemSelect.value,
                item_name: opt.dataset.name || opt.text,
                item_category: opt.dataset.category || '',
                item_description: description.value || '',
                quantity: q,
                unit_price: p,
                discount_percent: d,
                tax_rate: t,
                line_total: total,
                frequency: frequency.value || '',
                duration: duration.value ? Number(duration.value) : null,
                no_of_users: u,
                start_date: startDate.value || null,
                end_date: endDate.value || null
            };

            if (editingItemIndex !== null) {
                items.splice(editingItemIndex, 1, payload);
                editingItemIndex = null;
            } else {
                items.push(payload);
            }
            render();

            // Reset form after add
            itemSelect.value = '';
            qty.value = '1';
            unitPrice.value = '';
            discount.value = '0';
            frequency.value = '';
            duration.value = '1';
            startDate.value = today;
            endDate.value = '';
            description.value = '';
            users.value = '1';
            itemSelect.dispatchEvent(new Event('change'));
            syncFrequencyFields();
            setAddButtonState();
        });

        toStep3?.addEventListener('click', function () {
            if (!items.length) return alert('Add at least one item.');
            if (!quoTitleInput.value.trim()) return alert('Please enter quotation title.');
            if (!issueDateInput.value) return alert('Please enter issue date.');

            const payload = {
                quotationid: meta.draft_id || '',
                clientid: clientId,
                quo_title: quoTitleInput.value.trim(),
                quo_number: (quoNumberBadge?.textContent || '').trim() || @json($nextQuotationNumber),
                issue_date: issueDateInput.value,
                due_date: dueDateInput.value || '',
                notes: notesInput.value || '',
                items_data: JSON.stringify(items),
            };

            fetch("{{ route('quotations.step2-draft') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.ok) {
                    throw new Error(data.message || 'Unable to save draft.');
                }
                window.location.href = data.redirect_url;
            })
            .catch((err) => {
                alert(err.message || 'Unable to save draft.');
            });
        });

        render();
        setAddButtonState();
    }

    if (step === 3) {
        const form = document.getElementById('quotationForm');
        const meta = serverDraft ? {
            draft_id: serverDraft.quotationid || '',
            quo_title: serverDraft.quo_title || '',
            quo_number: serverDraft.quo_number || '',
            issue_date: serverDraft.issue_date || '',
            due_date: serverDraft.due_date || '',
            notes: serverDraft.notes || '',
        } : {};
        const items = serverDraft && Array.isArray(serverDraft.items) ? serverDraft.items : [];
        const titleHidden = document.getElementById('quo_title_hidden');
        const numberHidden = document.getElementById('quo_number_hidden');
        const issueDateHidden = document.getElementById('issue_date_hidden');
        const dueDateHidden = document.getElementById('due_date_hidden');
        const notesHidden = document.getElementById('notes_hidden');
        const quotationIdHidden = document.getElementById('quotationid_hidden');
        const reviewClientName = document.getElementById('reviewClientName');
        const reviewNumber = document.getElementById('reviewQuoNumber');
        const termsInput = document.getElementById('termsInput');
        const termsList = document.getElementById('termsList');
        const btnApplyTC = document.getElementById('btnApplyTC');
        const btnAddTC = document.getElementById('btnAddTC');
        const btnEditPreview = document.getElementById('btnEditPreview');
        const btnDownloadQuotationPdf = document.getElementById('btnDownloadQuotationPdf');
        const addTermModalEl = document.getElementById('addTermModal');
        const addTermModal = addTermModalEl ? new bootstrap.Modal(addTermModalEl) : null;
        const newTermContent = document.getElementById('newTermContent');
        const saveTermBtn = document.getElementById('saveTermBtn');
        const addTermError = document.getElementById('addTermError');
        const previewFrame = document.getElementById('quotationPdfPreviewFrame');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (meta.draft_id && quotationIdHidden && !quotationIdHidden.value) {
            quotationIdHidden.value = meta.draft_id;
        }

        if (meta.quo_title) {
            titleHidden.value = meta.quo_title;
        }
        if (meta.quo_number) {
            numberHidden.value = meta.quo_number;
            reviewNumber.textContent = meta.quo_number;
        }
        if (meta.issue_date) issueDateHidden.value = meta.issue_date;
        if (typeof meta.due_date === 'string') dueDateHidden.value = meta.due_date;
        if (typeof meta.notes === 'string') notesHidden.value = meta.notes;

        function syncTermsInput() {
            const terms = Array.from(document.querySelectorAll('.term-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => (cb.dataset.content || '').trim())
                .filter(Boolean);
            termsInput.value = JSON.stringify(terms);
            renderStep3Preview();
        }

        function esc(text) {
            return String(text || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderStep3Preview() {
            if (!previewFrame) return;
            const draftId = quotationIdHidden?.value || '';
            if (btnDownloadQuotationPdf) {
                btnDownloadQuotationPdf.href = draftId
                    ? `{{ url('quotations') }}/${encodeURIComponent(draftId)}/pdf`
                    : '#';
            }
            if (draftId) {
                previewFrame.src = `{{ url('quotations') }}/${encodeURIComponent(draftId)}/pdf?preview=1&_t=${Date.now()}`;
                return;
            }
            if (!items.length) {
                previewFrame.srcdoc = `<div class="q-prev-empty">No items available for preview.</div>`;
                return;
            }

            const title = titleHidden.value || 'Quotation';
            const number = numberHidden.value || '';
            const issueDate = issueDateHidden.value || '-';
            const dueDate = dueDateHidden.value || '-';
            const terms = (() => { try { return JSON.parse(termsInput.value || '[]'); } catch (_) { return []; } })();

            let sub = 0, disc = 0, tax = 0, grand = 0;
            const rows = items.map((it) => {
                const q = Math.max(1, Number(it.quantity || 1));
                const p = Math.max(0, Number(it.unit_price || 0));
                const dPct = Math.max(0, Math.min(100, Number(it.discount_percent || 0)));
                const tPct = Math.max(0, Number(it.tax_rate || 0));
                const lineSub = q * p;
                const lineDisc = Math.floor(lineSub * dPct / 100);
                const taxable = Math.floor(Math.max(0, lineSub - lineDisc));
                const lineTax = Math.ceil(taxable * tPct / 100);
                const lineTotal = taxable;
                sub += lineSub;
                disc += lineDisc;
                tax += lineTax;
                grand += lineTotal;
                return `<tr>
                    <td>${esc(it.item_name)}</td>
                    <td class="q-right">${q.toLocaleString('en-US')}</td>
                    <td class="q-right">${p.toLocaleString('en-US')}</td>
                    <td class="q-right">${dPct.toLocaleString('en-US')}</td>
                    <td class="q-right">${lineTotal.toLocaleString('en-US')}</td>
                </tr>`;
            }).join('');

            const termsHtml = terms.length
                ? `<h4 class="q-terms-title">Terms & Conditions</h4><ul class="q-terms-list">${terms.map(t => `<li>${esc(t)}</li>`).join('')}</ul>`
                : '';

            previewFrame.srcdoc = `
                <html><body>
                    <h3>${esc(title)}</h3>
                    <div><strong>${esc(number)}</strong></div>
                    <div>Issue: ${esc(issueDate)}</div>
                    <div>Due: ${esc(dueDate || '-')}</div>
                    <hr>
                    <table border="1" cellspacing="0" cellpadding="6" width="100%">
                        <thead><tr>
                            <th align="left">Item</th>
                            <th align="right">Qty</th>
                            <th align="right">Unit Price</th>
                            <th align="right">Disc %</th>
                            <th align="right">Line Total</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                    <br>
                    <table border="1" cellspacing="0" cellpadding="6">
                        <tr><td>Subtotal</td><td align="right">${sub.toLocaleString('en-US')}</td></tr>
                        <tr><td>Discount</td><td align="right">${disc.toLocaleString('en-US')}</td></tr>
                        <tr><td>Tax</td><td align="right">${tax.toLocaleString('en-US')}</td></tr>
                        <tr><td><strong>Total</strong></td><td align="right"><strong>${grand.toLocaleString('en-US')}</strong></td></tr>
                    </table>
                    ${termsHtml}
                </body></html>`;
        }

        termsList?.addEventListener('change', function (e) {
            if (e.target.classList.contains('term-checkbox')) {
                syncTermsInput();
            }
        });

        btnApplyTC?.addEventListener('click', function () {
            const draftId = quotationIdHidden?.value || '';
            if (!draftId) {
                alert('Save draft first from Step 2.');
                return;
            }
            const terms = Array.from(document.querySelectorAll('.term-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => (cb.dataset.content || '').trim())
                .filter(Boolean);

            btnApplyTC.disabled = true;
            btnApplyTC.textContent = 'Applying...';
            fetch(`{{ url('quotations') }}/${encodeURIComponent(draftId)}/terms`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ terms }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error('Failed to apply terms.');
                btnApplyTC.textContent = 'Applied ✓';
                renderStep3Preview();
                setTimeout(() => {
                    btnApplyTC.textContent = 'Apply';
                    btnApplyTC.disabled = false;
                }, 1200);
            })
            .catch(() => {
                btnApplyTC.textContent = 'Apply';
                btnApplyTC.disabled = false;
                alert('Failed to apply terms.');
            });
        });

        btnEditPreview?.addEventListener('click', function () {
            const draftId = quotationIdHidden?.value || '';
            let target = `{{ route('quotations.create') }}?step=2&c=${encodeURIComponent(clientId)}`;
            if (draftId) target += `&d=${encodeURIComponent(draftId)}`;
            window.location.href = target;
        });

        btnAddTC?.addEventListener('click', function () {
            addTermError?.classList.add('hidden');
            if (window.tinymce && tinymce.get('newTermContent')) {
                tinymce.get('newTermContent').setContent('');
            } else if (newTermContent) {
                newTermContent.value = '';
            }
            addTermModal?.show();
        });

        if (window.tinymce && newTermContent && !tinymce.get('newTermContent')) {
            tinymce.init({
                license_key: 'gpl',
                selector: '#newTermContent',
                menubar: false,
                height: 220,
                plugins: 'lists link table code autoresize',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link | removeformat code',
            });
        }

        saveTermBtn?.addEventListener('click', function () {
            const content = window.tinymce && tinymce.get('newTermContent')
                ? String(tinymce.get('newTermContent').getContent() || '').trim()
                : String(newTermContent?.value || '').trim();
            if (!content) {
                addTermError.textContent = 'Please enter term content.';
                addTermError.classList.remove('hidden');
                return;
            }

            fetch("{{ route('invoices.terms.billing.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ content, type: 'quotation' }),
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.ok || !data.term) {
                    throw new Error(data.message || 'Unable to save term.');
                }
                const label = document.createElement('label');
                label.className = 'custom-checkbox flex items-start gap-2 mb-2';
                label.style.width = '100%';
                label.style.maxWidth = '100%';
                label.style.boxSizing = 'border-box';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'term-checkbox';
                checkbox.checked = true;
                checkbox.dataset.content = String(data.term.content || '');
                const text = document.createElement('span');
                text.style.fontSize = '0.85rem';
                text.style.lineHeight = '1.4';
                text.style.minWidth = '0';
                text.style.width = '100%';
                text.style.flex = '1 1 auto';
                text.style.wordBreak = 'break-word';
                text.style.overflowWrap = 'anywhere';
                text.style.whiteSpace = 'normal';
                text.innerHTML = String(data.term.content || '');
                label.appendChild(checkbox);
                label.appendChild(text);
                termsList.prepend(label);
                syncTermsInput();
                addTermModal?.hide();
            })
            .catch((err) => {
                addTermError.textContent = err.message || 'Unable to save term.';
                addTermError.classList.remove('hidden');
            });
        });

        syncTermsInput();
        renderStep3Preview();

        form?.addEventListener('submit', function (e) {
            if (!items.length) {
                e.preventDefault();
                alert('No items found. Go back and add items.');
                return;
            }
            if (!titleHidden.value.trim() || !numberHidden.value.trim()) {
                e.preventDefault();
                alert('Quotation title/number missing. Please go back to Step 2.');
                return;
            }
            if (!issueDateHidden.value) {
                e.preventDefault();
                alert('Issue date missing. Please go back to Step 2.');
                return;
            }
            document.getElementById('items_data').value = JSON.stringify(items);
            syncTermsInput();
        });
    }
})();
</script>
@endsection
