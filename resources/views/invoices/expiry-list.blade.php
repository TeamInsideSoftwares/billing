@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('invoices.index', $selectedClientId ? ['c' => $selectedClientId] : []) }}"
            class="secondary-button">
            <i class="fas fa-file-invoice icon-spaced"></i>Invoice List
        </a>
    </div>
@endsection

    @section('content')
    @php
        $currentTab = in_array($selectedTab ?? 'expired', ['upcoming', 'expired', 'suspended'], true)
            ? $selectedTab
            : 'expired';
        $tabRows = match ($currentTab) {
            'upcoming' => $upcomingItems,
            'expired' => $expiredItems,
            'suspended' => $suspendedItems,
            default => collect(),
        };
    @endphp

    <div class="invoice-index-shell">
        <section class="panel-card module-filter-panel">
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
                                <th>Invoice</th>
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
                                        <a href="{{ route('invoices.show', ['invoice' => $row['invoiceid'], 'c' => $row['clientid']]) }}"
                                            class="invoice-muted">
                                            <strong>{{ $row['invoice_label'] }}</strong>
                                        </a>
                                        <div class="small-text">{{ $row['invoice_number'] }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $row['item_name'] }}</strong>
                                        @if (!empty($row['frequency']) || !empty($row['duration']))
                                            <div class="small-text">
                                                @if (!empty($row['duration']))
                                                    Duration: {{ $row['duration'] }}
                                                @endif
                                                @if (!empty($row['duration']) && !empty($row['frequency']))
                                                    |
                                                @endif
                                                @if (!empty($row['frequency']))
                                                    {{ ucfirst(str_replace('_', ' ', $row['frequency'])) }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $row['end_date_display'] }}</td>
                                    <td>
                                        @if ($row['days_left'] === null)
                                            <span class="invoice-muted">-</span>
                                        @elseif($row['days_left'] > 0)
                                            <span class="text-success" style="font-weight: 600;">{{ $row['days_left'] }} day(s)</span>
                                        @elseif($row['days_left'] === 0)
                                            <span class="text-warning" style="font-weight: 600;">Today</span>
                                        @else
                                            <span class="text-danger" style="font-weight: 600;">{{ abs($row['days_left']) }} day(s) ago</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="table-actions" style="justify-content: center;">
                                            <a href="{{ route('invoices.items.renew', ['item' => $row['invoice_itemid']]) }}"
                                                class="text-action-btn view" title="Renew">
                                                Renew
                                            </a>

                                            @if ($currentTab === 'expired' && $row['status'] !== 'suspended')
                                                <form method="POST"
                                                    action="{{ route('invoices.items.suspend', ['invoice' => $row['invoiceid'], 'item' => $row['invoice_itemid'], 'c' => $selectedClientId]) }}"
                                                    class="inline-delete m-0"
                                                    onsubmit="return confirm('Suspend this item?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="tab" value="{{ $currentTab }}">
                                                    <button type="submit" class="text-action-btn delete" title="Suspend">
                                                        Suspend
                                                    </button>
                                                </form>
                                            @elseif($row['status'] === 'suspended')
                                                <form method="POST"
                                                    action="{{ route('invoices.items.unsuspend', ['invoice' => $row['invoiceid'], 'item' => $row['invoice_itemid'], 'c' => $selectedClientId]) }}"
                                                    class="inline-delete m-0"
                                                    onsubmit="return confirm('Unsuspend this item?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="tab" value="{{ $currentTab }}">
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
@endsection
