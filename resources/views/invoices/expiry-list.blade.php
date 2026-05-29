@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('invoices.index') }}"
            class="secondary-button">
            <i class="fas fa-file-invoice icon-spaced"></i>Invoice List
        </a>
    </div>
@endsection

    @section('content')
    @php
        $allowedTabs = !empty($hasTrialClients)
            ? ['upcoming', 'expired', 'suspended', 'trial']
            : ['upcoming', 'expired', 'suspended'];
        $currentTab = in_array($selectedTab ?? 'expired', $allowedTabs, true)
            ? $selectedTab
            : 'expired';
        $tabRows = match ($currentTab) {
            'upcoming' => $upcomingItems,
            'expired' => $expiredItems,
            'suspended' => $suspendedItems,
            'trial' => $trialItems ?? collect(),
            default => collect(),
        };
    @endphp

    <div class="invoice-index-shell">
        <section class="panel-card module-filter-panel filter-panel-regular">
            <form action="{{ route('invoices.expiry-list') }}" method="GET" class="module-filter-grid">
                <input type="hidden" name="tab" value="{{ $selectedTab ?? 'expired' }}">

                <div class="module-filter-field">
                    <label class="module-filter-label" for="expiry_client_filter">Client</label>
                    <select name="c" id="expiry_client_filter" class="form-control">
                        <option value="">All Clients</option>
                        @foreach ($clients as $clientOption)
                            <option value="{{ $clientOption->clientid }}"
                                {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                                {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="module-filter-field">
                    <label class="module-filter-label" for="expiry_from_filter">From</label>
                    <input type="date" name="from" id="expiry_from_filter" class="form-control module-date-input"
                        value="{{ $fromDate ?? '' }}">
                </div>

                <div class="module-filter-field">
                    <label class="module-filter-label" for="expiry_to_filter">To</label>
                    <input type="date" name="to" id="expiry_to_filter" class="form-control module-date-input"
                        value="{{ $toDate ?? '' }}" min="{{ $fromDate ?? '' }}">
                </div>

                <div class="module-filter-actions">
                    <button type="submit" class="primary-button">Apply</button>
                    <a href="{{ route('invoices.expiry-list', array_filter([
                        'tab' => $selectedTab ?? 'expired',
                    ])) }}"
                        class="secondary-button">Reset</a>
                </div>
            </form>
        </section>

        <div class="invoice-tabs-container">
            <div class="invoice-tabs">
                <a href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'upcoming', 'from' => $fromDate ?? '', 'to' => $toDate ?? '', 'next_days' => $nextDays ?? 60])) }}"
                    class="invoice-tab {{ $currentTab === 'upcoming' ? 'is-active' : '' }}">
                    Upcoming <span>{{ $upcomingItems->count() }}</span>
                </a>
                <a href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'expired', 'from' => $fromDate ?? '', 'to' => $toDate ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'expired' ? 'is-active' : '' }}">
                    Expired <span>{{ $expiredItems->count() }}</span>
                </a>
                <a href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'suspended', 'from' => $fromDate ?? '', 'to' => $toDate ?? ''])) }}"
                    class="invoice-tab {{ $currentTab === 'suspended' ? 'is-active' : '' }}">
                    Suspended <span>{{ $suspendedItems->count() }}</span>
                </a>
                @if(!empty($hasTrialClients))
                    <a href="{{ route('invoices.expiry-list', array_filter(['c' => $selectedClientId, 'tab' => 'trial', 'from' => $fromDate ?? '', 'to' => $toDate ?? '', 'next_days' => $nextDays ?? 60])) }}"
                        class="invoice-tab {{ $currentTab === 'trial' ? 'is-active' : '' }}">
                        Trial <span>{{ ($trialItems ?? collect())->count() }}</span>
                    </a>
                @endif
            </div>
        </div>

        <section class="invoice-group">
            <div class="invoice-list-meta">
                @if($currentTab === 'upcoming')
                    <div class="meta-info">
                        <strong>Upcoming items</strong>
                        <span class="small-text">Active items whose expiry date is still in the future.</span>
                    </div>
                    <form action="{{ route('invoices.expiry-list') }}" method="GET" class="compact-meta-filter">
                        <input type="hidden" name="tab" value="upcoming">
                        @if($selectedClientId)
                            <input type="hidden" name="c" value="{{ $selectedClientId }}">
                        @endif
                        @if(($fromDate ?? '') !== '')
                            <input type="hidden" name="from" value="{{ $fromDate }}">
                        @endif
                        @if(($toDate ?? '') !== '')
                            <input type="hidden" name="to" value="{{ $toDate }}">
                        @endif
                        <label for="upcoming_next_days_inline">Next Days</label>
                        <input type="number" name="next_days" id="upcoming_next_days_inline"
                            value="{{ $nextDays ?? 60 }}" min="1" step="1">
                        <button type="submit" class="secondary-button small">Apply</button>
                    </form>
                @elseif($currentTab === 'expired')
                    <div class="meta-info">
                        <strong>Expired items</strong>
                        <span class="small-text">Only items whose end date is already over.</span>
                    </div>
                @elseif($currentTab === 'trial')
                    <div class="meta-info">
                        <strong>Trial items</strong>
                        <span class="small-text">Orders belonging to trial-type clients.</span>
                    </div>
                @else
                    <div class="meta-info">
                        <strong>Suspended items</strong>
                        <span class="small-text">These items were manually suspended.</span>
                    </div>
                @endif
            </div>

            @if (collect($tabRows)->isEmpty())
                <div class="invoice-empty">
                    <i class="fas fa-check-circle empty-state-icon"></i>
                    <p class="no-empty-state-text">No records found for this tab.</p>
                </div>
            @else
                <div class="invoice-table-wrap">
                    <table class="data-table table-no-margin">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Item</th>
                                <th>Expiry Date</th>
                                <th>Days</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tabRows as $row)
                                <tr>
                                    <td>{{ $row['client_name'] }}</td>
                                    <td>
                                        <strong>{{ $row['item_name'] }}</strong>
                                    </td>
                                    <td>
                                        <span class="{{ (($row['days_left'] ?? null) !== null && $row['days_left'] < 0) ? 'text-danger' : '' }}"
                                            style="{{ (($row['days_left'] ?? null) !== null && $row['days_left'] < 0) ? 'font-weight: 600;' : '' }}">
                                            {{ $row['end_date_display'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($row['days_left'] === null)
                                            <span class="invoice-muted">-</span>
                                        @elseif($row['days_left'] > 0)
                                            <span class="text-success" style="font-weight: 600;">{{ $row['days_left'] }} day(s)</span>
                                        @elseif($row['days_left'] === 0)
                                            <span class="text-warning" style="font-weight: 600;">Today</span>
                                        @else
                                            <span class="text-danger" style="font-weight: 600;">-{{ abs($row['days_left']) }} day(s)</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="" style="justify-content: center;">
                                            @if(strtolower((string) ($row['status'] ?? '')) !== 'cancelled')
                                                <form method="POST"
                                                    action="{{ route('invoices.orders.send-reminder', ['order' => $row['orderid']]) }}"
                                                    class="inline-delete m-0"
                                                    onsubmit="return confirm('Send manual reminder for this order?')">
                                                    @csrf
                                                    <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                                                    <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                                    <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                                    <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                                    <button type="submit" class="text-action-btn secondary" title="Send Reminder">
                                                        Send Reminder
                                                    </button>
                                                </form>
                                            @endif

                                            <button
                                                type="button"
                                                class="text-action-btn view js-renew-order-btn"
                                                title="Renew"
                                                data-order-id="{{ $row['orderid'] }}"
                                                data-order-number="{{ $row['order_number'] }}"
                                                data-client-name="{{ $row['client_name'] }}"
                                                data-invoice-number="{{ $row['invoice_number'] }}"
                                                data-item-name="{{ $row['item_name'] }}"
                                                data-item-description="{{ $row['item_description'] }}"
                                                data-start-date="{{ $row['start_date_display'] }}"
                                                data-end-date-display="{{ $row['end_date_display'] }}"
                                                data-days-left="{{ $row['days_left'] }}"
                                                data-status="{{ ucfirst($row['status']) }}"
                                                data-end-date="{{ $row['end_date'] ? $row['end_date']->format('Y-m-d') : '' }}"
                                                data-client-id="{{ $row['clientid'] }}"
                                                data-frequency="{{ $row['frequency'] ?? '' }}"
                                                data-duration="{{ $row['duration'] ?? 1 }}"
                                            >
                                                Renew
                                            </button>

                                            @if ($currentTab === 'expired' && $row['status'] !== 'suspended')
                                                <form method="POST"
                                                    action="{{ route('invoices.orders.suspend', ['order' => $row['orderid']]) }}"
                                                    class="inline-delete m-0"
                                                    onsubmit="return confirm('Suspend this order?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                                                    <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                                    <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                                    <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                                    <button type="submit" class="text-action-btn delete" title="Suspend">
                                                        Suspend
                                                    </button>
                                                </form>
                                            @elseif($row['status'] === 'suspended')
                                                <form method="POST"
                                                    action="{{ route('invoices.orders.unsuspend', ['order' => $row['orderid']]) }}"
                                                    class="inline-delete m-0"
                                                    onsubmit="return confirm('Unsuspend this order?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="c" value="{{ $selectedClientId }}">
                                                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                                                    <input type="hidden" name="from" value="{{ $fromDate ?? '' }}">
                                                    <input type="hidden" name="to" value="{{ $toDate ?? '' }}">
                                                    <input type="hidden" name="next_days" value="{{ $nextDays ?? '' }}">
                                                    <button type="submit" class="text-action-btn secondary" title="Unsuspend">
                                                        Unsuspend
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
    @include('invoices.partials.renew-order-modal')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewModalEl = document.getElementById('renewOrderModal');
            if (!renewModalEl || typeof bootstrap === 'undefined') return;

            const renewModal = new bootstrap.Modal(renewModalEl);
            const renewForm = document.getElementById('renewOrderForm');
            const itemName = document.getElementById('renewOrderItemName');
            const clientName = document.getElementById('renewOrderClientName');
            const orderNumber = document.getElementById('renewOrderNumber');
            const invoiceNumber = document.getElementById('renewOrderInvoiceRef');
            const startDateDisplay = document.getElementById('renewOrderStartDate');
            const currentEndDateDisplay = document.getElementById('renewOrderCurrentEndDate');
            const statusDisplay = document.getElementById('renewOrderStatus');
            const endDateInput = document.getElementById('renew_order_end_date');
            const clientInput = document.getElementById('renew_order_client');
            const tabInput = document.getElementById('renew_order_tab');
            const fromInput = document.getElementById('renew_order_from');
            const toInput = document.getElementById('renew_order_to');
            const nextDaysInput = document.getElementById('renew_order_next_days');
            const frequencyInput = document.getElementById('renew_order_frequency');
            const durationInput = document.getElementById('renew_order_duration');
            const durationWrapper = document.getElementById('renew_order_duration_wrapper');

            const currentTab = @json($currentTab);
            const currentClient = @json($selectedClientId);
            const currentFrom = @json($fromDate ?? '');
            const currentTo = @json($toDate ?? '');
            const currentNextDays = @json($nextDays ?? 60);
            const renewRouteTemplate = @json(route('invoices.orders.renew', ['order' => '__ORDER__']));
            let pendingRenewEndDate = '';
            let renewalBaseStartDate = '';
            const setText = (element, value) => {
                if (!element) return;
                element.textContent = value;
            };

            function normalizeIsoDate(rawValue) {
                const value = String(rawValue || '').trim();
                if (!value) return '';

                const isoMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (isoMatch) {
                    return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
                }

                return '';
            }

            function applyRenewEndDate(rawValue) {
                const iso = normalizeIsoDate(rawValue);
                pendingRenewEndDate = iso;

                endDateInput.value = '';
                endDateInput.removeAttribute('value');
                endDateInput.dataset.prefillDate = '';

                if (endDateInput._flatpickr) {
                    endDateInput._flatpickr.clear();
                }

                if (!iso) return;

                endDateInput.value = iso;
                endDateInput.setAttribute('value', iso);
                endDateInput.dataset.prefillDate = iso;

                if (endDateInput._flatpickr) {
                    // Keep Flatpickr UI in sync so month/year are visible without extra clicks.
                    endDateInput._flatpickr.setDate(iso, true, 'Y-m-d');
                }
            }

            function syncRenewEndDateStateFromInput() {
                pendingRenewEndDate = normalizeIsoDate(endDateInput.value);
            }

            document.querySelectorAll('.js-renew-order-btn').forEach((button) => {
                button.addEventListener('click', function () {
                    const orderId = this.dataset.orderId || '';
                    const orderNo = this.dataset.orderNumber || '-';
                    const client = this.dataset.clientName || '-';
                    const invoiceRef = this.dataset.invoiceNumber || '-';
                    const item = this.dataset.itemName || '-';
                    const startDate = this.dataset.startDate || '-';
                    const endDateDisplay = this.dataset.endDateDisplay || '-';
                    const status = this.dataset.status || '-';
                    const endDate = normalizeIsoDate(this.dataset.endDate);
                    const frequency = this.dataset.frequency || '';
                    const duration = this.dataset.duration || 1;

                    if (!orderId) return;

                    renewForm.action = renewRouteTemplate.replace('__ORDER__', orderId);
                    setText(itemName, item);
                    setText(clientName, client);
                    setText(orderNumber, orderNo);
                    setText(invoiceNumber, invoiceRef);
                    setText(startDateDisplay, startDate);
                    setText(currentEndDateDisplay, endDateDisplay);
                    setText(statusDisplay, status);
                    renewalBaseStartDate = plusOneDay(endDate);
                    applyRenewEndDate(renewalBaseStartDate || endDate);
                    clientInput.value = currentClient || '';
                    tabInput.value = currentTab || 'expired';
                    fromInput.value = currentFrom || '';
                    toInput.value = currentTo || '';
                    nextDaysInput.value = currentNextDays || '';

                    // Set frequency and duration
                    if (frequencyInput) {
                        frequencyInput.value = frequency || '';
                        frequencyInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (durationInput) {
                        durationInput.value = duration || 1;
                    }
                    if (durationWrapper) {
                        durationWrapper.style.display = (frequency && frequency !== 'One-Time') ? 'block' : 'none';
                    }
                    refreshEndDate();

                    renewModal.show();
                });
            });

            renewModalEl.addEventListener('shown.bs.modal', function () {
                if (!pendingRenewEndDate) return;
                requestAnimationFrame(() => {
                    applyRenewEndDate(pendingRenewEndDate);
                    endDateInput.dispatchEvent(new Event('input', { bubbles: true }));
                    endDateInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            renewModalEl.addEventListener('hidden.bs.modal', function () {
                pendingRenewEndDate = '';
                renewalBaseStartDate = '';
            });

            endDateInput.addEventListener('change', syncRenewEndDateStateFromInput);
            endDateInput.addEventListener('input', syncRenewEndDateStateFromInput);

            // Frequency/Duration auto-calculation
            function isOneTimeFrequency() {
                const selectedFrequency = frequencyInput?.value || '';
                return selectedFrequency === '' || selectedFrequency === 'One-Time';
            }

            function toggleDurationField() {
                if (!durationWrapper) return;
                durationWrapper.style.display = isOneTimeFrequency() ? 'none' : 'block';
            }

            function formatDateLocal(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function plusOneDay(isoDate) {
                if (!isoDate) return '';
                const date = new Date(isoDate + 'T00:00:00');
                if (Number.isNaN(date.getTime())) return '';
                date.setDate(date.getDate() + 1);
                return formatDateLocal(date);
            }

            function calculateNewEndDate(baseStartDate, frequency, duration) {
                if (!baseStartDate) {
                    return '';
                }

                const start = new Date(baseStartDate + 'T00:00:00');
                const end = new Date(start);
                const count = Math.max(1, parseInt(duration, 10) || 1);

                if (!frequency || frequency === 'One-Time') {
                    return formatDateLocal(end);
                }

                switch (frequency) {
                    case 'Day(s)':
                        end.setDate(end.getDate() + count);
                        break;
                    case 'Week(s)':
                        end.setDate(end.getDate() + (count * 7));
                        break;
                    case 'Month(s)':
                        end.setMonth(end.getMonth() + count);
                        break;
                    case 'Quarter(s)':
                        end.setMonth(end.getMonth() + (count * 3));
                        break;
                    case 'Year(s)':
                        end.setFullYear(end.getFullYear() + count);
                        break;
                    default:
                        break;
                }

                // Inclusive range: end date is one day before the next cycle boundary.
                end.setDate(end.getDate() - 1);
                return formatDateLocal(end);
            }

            function refreshEndDate() {
                if (!frequencyInput || !durationInput || !endDateInput) return;
                toggleDurationField();

                if (!renewalBaseStartDate) return;

                const newEndDate = calculateNewEndDate(
                    renewalBaseStartDate,
                    frequencyInput.value,
                    durationInput.value
                );

                if (newEndDate) {
                    applyRenewEndDate(newEndDate);
                }
            }

            if (frequencyInput) {
                frequencyInput.addEventListener('change', refreshEndDate);
            }
            if (durationInput) {
                durationInput.addEventListener('input', refreshEndDate);
            }
        });
    </script>
@endsection
