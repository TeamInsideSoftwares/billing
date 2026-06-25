@extends('layouts.app')

@section('header_actions')
<a href="{{ route('quotations.index') }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    Quotation List <i class="fas fa-arrow-right"></i>
</a>
@endsection

@section('content')
@php
$step = (int) ($currentStep ?? 1);
$step = max(1, min(4, $step));
$clientId = old('clientid', $selectedClientId ?? request('c', ''));
$selectedClientName = $selectedClient ? ($selectedClient->business_name ?? $selectedClient->contact_name ?? 'Client') :
'No Client Selected';
$selectedClientEmail = $selectedClient->primary_email ?? $selectedClient->email ?? '';
$quotationDateBounds = $quotationDateBounds ?? [
'min_date' => date('Y-m-d'),
'max_date' => date('Y-m-d'),
'default_issue_date' => '',
'default_due_date' => '',
];
@endphp

<div class="position-relative {{ $step !== 1 ? 'bg-white p-2' : '' }} rounded-3">

    @if($step === 1)
    @include('quotations.steps.step1-client')
    @elseif($step === 2)
    @include('quotations.steps.step2-items')
    @elseif($step === 3)
    @include('quotations.steps.step3-preview-terms')
    @elseif($step === 4)
    @include('quotations.steps.step4-compose')
    @endif
</div>

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

        const step = @json($step);
    const clientId = @json($clientId);
    const draftIdParam = @json((string) request('d', ''));

    if (step === 1) {
        const btn = document.getElementById('toStep2');
        const client = document.getElementById('clientid');
        btn?.addEventListener('click', function () {
            if (!client.checkValidity()) {
                client.reportValidity();
                return;
            }
            const createRoute = "{{ route('quotations.create') }}";
            const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
            let target = createPath + "?step=2&c=" + encodeURIComponent(client.value);
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
        const accountHasUsers = @json((bool)($account -> have_users ?? false));
        const allowMultiTaxation = @json((bool)($account -> allow_multi_taxation ?? false));
        const itemSelect = document.getElementById('itemid');
        const qty = document.getElementById('quantity');
        const unitPrice = document.getElementById('unit_price');
        const discount = document.getElementById('discount_percent');
        const taxRate = document.getElementById('tax_rate');
        const users = document.getElementById('no_of_users');
        const usersWrap = document.getElementById('usersWrap');
        const usersColHeader = document.getElementById('usersColHeader');
        const freqDurHeader = document.getElementById('freqDurHeader');
        const startEndHeader = document.getElementById('startEndHeader');
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
        const clientSelect = document.getElementById('clientid');
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

        function setDateInputValue(input, value) {
            if (!input) return;
            const normalized = String(value || '');
            if (input.value === normalized && input.getAttribute('value') === normalized) return;
            input.value = normalized;
            input.setAttribute('value', normalized);
            if (input._flatpickr) {
                if (normalized) {
                    input._flatpickr.setDate(normalized, false, 'Y-m-d');
                } else {
                    input._flatpickr.clear();
                }
            }
        }

        if (meta.quo_title) quoTitleInput.value = meta.quo_title;
        if (meta.quo_number && quoNumberBadge) quoNumberBadge.textContent = meta.quo_number;
        if (issueDateInput && meta.issue_date) setDateInputValue(issueDateInput, meta.issue_date);
        if (dueDateInput && meta.due_date) setDateInputValue(dueDateInput, meta.due_date);
        if (typeof meta.notes === 'string') notesInput.value = meta.notes;
        const today = new Date().toISOString().slice(0, 10);
        if (!startDate.value) setDateInputValue(startDate, today);
        if (!duration.value) duration.value = '1';
        if (!endDate.value) setDateInputValue(endDate, '');

        function setAddButtonState() {
            if (!addBtn) return;
            addBtn.innerHTML = editingItemIndex === null
                ? 'Add Item <i class="fas fa-arrow-right btn-icon ms-1"></i>'
                : 'Update Item <i class="fas fa-arrow-right btn-icon ms-1"></i>';
        }

        function formatNumber(value) {
            return Math.round(Number(value || 0)).toLocaleString('en-US');
        }

        function formatDateToDisplay(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            const year = parts[0];
            const monthIndex = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (monthIndex < 0 || monthIndex > 11 || isNaN(day)) return dateStr;
            const dayStr = String(day).padStart(2, '0');
            return `${dayStr} ${months[monthIndex]} ${year}`;
        }

        function computeLineTotal(item) {
            const q = Math.max(1, Number(item.quantity || 1));
            const p = Math.max(0, Number(item.unit_price || 0));
            const u = Math.max(1, Number(item.no_of_users || 1));
            const d = Math.max(0, Math.min(100, Number(item.discount_percent || 0)));
            const sub = q * p * u;
            const discountAmount = Math.floor(sub * d / 100);
            const taxable = Math.floor(Math.max(0, sub - discountAmount));
            return taxable;
        }

        function recalcSummary(items) {
            let sub = 0, disc = 0, taxTotal = 0, grand = 0;
            items.forEach((it) => {
                const q = Number(it.quantity || 0);
                const p = Number(it.unit_price || 0);
                const u = Math.max(1, Number(it.no_of_users || 1));
                const d = Number(it.discount_percent || 0);
                const t = Number(it.tax_rate || 0);
                const lineSub = q * p * u;
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
            quoteSummary.classList.toggle('d-none', items.length === 0);
        }

        function render() {
            const showUsersColumn = accountHasUsers && items.some(it => Number(it.no_of_users || 0) > 0);
            const showRecurringColumns = items.some(it => (it.frequency && it.frequency !== 'One-Time') || (it.start_date || it.end_date));
            
            usersColHeader?.classList.toggle('d-none', !showUsersColumn);
            freqDurHeader?.classList.toggle('d-none', !showRecurringColumns);
            startEndHeader?.classList.toggle('d-none', !showRecurringColumns);

            let colsBeforeTotalPrice = 3;
            if (allowMultiTaxation) {
                colsBeforeTotalPrice += 1;
            }
            if (showUsersColumn) {
                colsBeforeTotalPrice += 1;
            }
            if (showRecurringColumns) {
                colsBeforeTotalPrice += 2;
            }
            document.querySelectorAll('#quoteSummary td[colspan]').forEach(td => {
                td.setAttribute('colspan', colsBeforeTotalPrice);
            });

            body.innerHTML = '';
            if (itemsTable) {
                itemsTable.classList.remove('d-none');
            }

            if (items.length === 0) {
                const totalCols = allowMultiTaxation ? 6 : 5;
                body.innerHTML = `
                    <tr>
                        <td colspan="${totalCols}" class="text-center text-muted py-4">
                            No items added yet. Select an item or add one manually.
                        </td>
                    </tr>
                `;
                if (toStep3) {
                    toStep3.disabled = true;
                }
                recalcSummary(items);
                return;
            }

            if (toStep3) {
                toStep3.disabled = false;
            }

            items.forEach((it, idx) => {
                const tr = document.createElement('tr');
                if (editingItemIndex === idx) {
                    tr.classList.add('is-active');
                }
                const itemDescription = String(it.item_description || '').trim();
                const itemLabel = itemDescription
                    ? `<div class="fw-semibold">${it.item_name}</div><div class="small-text">${itemDescription}</div>`
                    : `<div class="fw-semibold">${it.item_name}</div>`;
                const isUserWise = Number(it.no_of_users || 0) > 0;
                const lineTotal = computeLineTotal(it);
                
                let rowHtml = `
                <td>${itemLabel}</td>
                <td class="text-center">${formatNumber(it.quantity)}</td>
                `;
                
                if (allowMultiTaxation) {
                    rowHtml += `<td class="text-center">${it.tax_rate ?? 0}%</td>`;
                }
                
                rowHtml += `
                <td class="text-center ${showUsersColumn ? '' : 'd-none'}">${isUserWise ? formatNumber(it.no_of_users) : '-'}</td>
                <td class="text-center ${showRecurringColumns ? '' : 'd-none'}">
                    <div>${(it.frequency && it.frequency !== 'One-Time') ? `<span class="text-dark">${it.duration || '-'}</span> ` : ''}${it.frequency || '-'}</div>
                </td>
                <td class="text-center ${showRecurringColumns ? '' : 'd-none'}">
                    <div>${formatDateToDisplay(it.start_date)}</div>
                    <div class="text-dark">${formatDateToDisplay(it.end_date)}</div>
                </td>
                <td class="text-end">
                    <div>${formatNumber(it.unit_price)}</div>
                    <small class="d-block small lh-sm fw-semibold text-success text-uppercase">(${Number(it.discount_percent || 0).toFixed(0)}% Off)</small>
                </td>
                <td class="text-end">${formatNumber(lineTotal)}</td>
                <td class="text-end">
                    <div class="tableActionButton d-inline-flex gap-1 align-items-center">
                        <button type="button" data-edit="${idx}" class="bg03 color03 border-0">Edit</button>
                        <button type="button" data-i="${idx}" class="bg04 color04 border-0">Remove</button>
                    </div>
                </td>
                `;
                tr.innerHTML = rowHtml;
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
                if (itemSelect.value) {
                    itemSelect.dispatchEvent(new Event('change'));
                }
                qty.value = item.quantity ?? 1;
                unitPrice.value = item.unit_price ?? '';
                discount.value = item.discount_percent ?? 0;
                if (taxRate) taxRate.value = item.tax_rate ?? 0;
                frequency.value = item.frequency || '';
                
                syncUsersField();
                if (item.no_of_users) {
                    users.value = item.no_of_users || 1;
                }
                
                syncFrequencyFields();
                duration.value = item.duration || 1;
                setDateInputValue(startDate, item.start_date || today);
                setDateInputValue(endDate, item.end_date || '');
                description.value = item.item_description || '';

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
            if (!startDate.value) setDateInputValue(startDate, base);

            const minVal = startDate.value || '';
            if (minVal) {
                endDate.min = minVal;
                if (endDate._flatpickr) {
                    endDate._flatpickr.set('minDate', minVal);
                }
            }

            if (!freq || freq === 'One-Time') {
                setDateInputValue(endDate, freq === 'One-Time' ? '2099-12-31' : '');
                return;
            }
            const nextCycleDate = addByFrequency(base, freq, dur);
            setDateInputValue(endDate, nextCycleDate ? minusOneDay(nextCycleDate) : '');
        }

        function isSelectedItemUserWise() {
            const option = itemSelect?.options?.[itemSelect.selectedIndex];
            return String(option?.dataset?.userWise ?? '0') === '1';
        }

        function isRecurringFrequency(freq) {
            return ['Day(s)', 'Week(s)', 'Month(s)', 'Quarter(s)', 'Year(s)'].includes((freq || '').trim());
        }

        function syncUsersField() {
            if (!users) return;
            if (!accountHasUsers) {
                users.disabled = true;
                users.value = 1;
                return;
            }
            const show = isSelectedItemUserWise();
            users.disabled = !show;
            if (!show) {
                users.value = 1;
            }
        }

        function syncFrequencyFields() {
            if (!duration) return;
            const showRecurring = isRecurringFrequency(frequency.value);
            duration.disabled = !showRecurring;
            if (showRecurring) {
                const durationValue = Number(duration.value || 0);
                if (!duration.value || durationValue <= 0) {
                    duration.value = '1';
                }
            } else {
                duration.value = '1';
            }
            syncStartEndDates();
        }

        itemSelect?.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            unitPrice.value = opt?.dataset?.unitPrice ? Math.round(Number(opt.dataset.unitPrice)) : '';
            description.value = opt?.dataset?.description || '';
            if (taxRate && opt?.dataset?.taxRate) {
                taxRate.value = opt.dataset.taxRate;
            }
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
        if (clientSelect) {
            clientSelect.addEventListener('change', function () {
                if (!this.value) return;
                const createRoute = "{{ route('quotations.create') }}";
                const createPath = createRoute.startsWith('http') ? new URL(createRoute).pathname : createRoute;
                let target = createPath + "?step=2&c=" + encodeURIComponent(this.value);
                if (draftIdParam) {
                    target += "&d=" + encodeURIComponent(draftIdParam);
                }
                window.location.href = target;
            });
        }
        syncFrequencyFields();
        syncUsersField();

        addBtn?.addEventListener('click', function () {
            if (!itemSelect.value) {
                showToast('error', 'Select item first.');
                return;
            }
            const quantityInputVal = Number(qty.value || 0);
            if (quantityInputVal <= 0) {
                showToast('error', 'Quantity must be greater than 0.');
                return;
            }
            const opt = itemSelect.options[itemSelect.selectedIndex];
            const q = Math.max(1, Number(qty.value || 1));
            const p = Math.round(Math.max(0, Number(unitPrice.value || 0)));
            const d = Math.max(0, Math.min(100, Number(discount.value || 0)));
            const t = taxRate ? Math.max(0, Number(taxRate.value || 0)) : Math.max(0, Number(opt?.dataset?.taxRate || 0));
            const userWise = accountHasUsers && isSelectedItemUserWise();
            const u = userWise ? Math.max(1, Number(users.value || 1)) : null;
            const sub = q * p * Math.max(1, u ?? 1);
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
            if (taxRate && allowMultiTaxation) {
                taxRate.value = '0';
            }
            frequency.value = 'One-Time';
            duration.value = '1';
            setDateInputValue(startDate, today);
            setDateInputValue(endDate, '');
            description.value = '';
            users.value = '1';
            itemSelect.dispatchEvent(new Event('change'));
            syncFrequencyFields();
            setAddButtonState();
        });

        toStep3?.addEventListener('click', function () {
            // First check visible HTML5 required validation inside step 2 form
            const form = document.getElementById('step2');
            if (form) {
                const requiredFields = form.querySelectorAll('[required]');
                let allValid = true;
                for (const field of requiredFields) {
                    if (field.disabled) continue;
                    if (!field.checkValidity()) {
                        field.reportValidity();
                        allValid = false;
                        break;
                    }
                }
                if (!allValid) {
                    return;
                }
            }

            if (!items.length) {
                showToast('error', 'Add at least one item.');
                return;
            }

            const payload = {
                quotationid: meta.draft_id || '',
                clientid: clientSelect?.value || clientId,
                quo_title: quoTitleInput.value.trim(),
                quo_number: (quoNumberBadge?.textContent || '').trim() || @json($nextQuotationNumber),
                issue_date: issueDateInput.value,
                due_date: dueDateInput.value || '',
                notes: notesInput.value || '',
                items_data: JSON.stringify(items),
            };

            const saveDraftRoute = "{{ route('quotations.step2-draft') }}";
            const saveDraftPath = saveDraftRoute.startsWith('http') ? new URL(saveDraftRoute).pathname : saveDraftRoute;
            fetch(saveDraftPath, {
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
                    showToast('error', err.message || 'Unable to save draft.');
                });
        });

        quoTitleInput.addEventListener('input', function () {
            if (this.value.trim()) {
                const errorEl = document.getElementById('quoTitleError');
                if (errorEl) errorEl.classList.add('d-none');
            }
        });

        render();
        setAddButtonState();
    }
}) ();
</script>
@endsection
