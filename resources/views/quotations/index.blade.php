@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('quotations.create', $selectedClientId ? ['c' => $selectedClientId] : []) }}" class="primary-button">
            <i class="fas fa-plus icon-spaced"></i>Create Quotation
        </a>
    </div>
@endsection

@section('content')
    <section class="panel-card module-filter-panel filter-panel-regular">
        <form action="{{ route('quotations.index') }}" method="GET" class="module-filter-grid">
            <div class="module-filter-field">
                <label class="module-filter-label" for="quotation_client_filter">Client</label>
                <select name="c" id="quotation_client_filter" class="w-full bg-white border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Clients</option>
                    @foreach ($clients as $clientOption)
                        <option value="{{ $clientOption->clientid }}"
                            {{ (string) $selectedClientId === (string) $clientOption->clientid ? 'selected' : '' }}>
                            {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="module-filter-actions">
                <button type="submit" class="primary-button">Apply</button>
                <a href="{{ route('quotations.index') }}" class="secondary-button">Reset</a>
            </div>
        </form>
    </section>

    <section class="invoice-group">
        @if (count($quotations) === 0)
            <div class="invoice-empty">
                <i class="fas fa-file-contract empty-state-icon"></i>
                <p class="no-empty-state-text">No quotations found.</p>
                <p class="small-text">Choose a client or create a new quotation to get started.</p>
            </div>
        @else
            <div class="invoice-table-wrap">
                <table class="data-table table-no-margin">
                    <thead>
                        <tr>
                            <th class="w-5"></th>
                            <th>Client</th>
                            <th>Quotation</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotations as $quotation)
                            <tr>
                                <td>
                                    <button type="button" class="expand-order-btn"
                                        onclick="toggleQuotationItems('{{ $quotation['record_id'] }}')">
                                        <i class="fas fa-chevron-right expand-order-icon"
                                            id="quotation-icon-{{ $quotation['record_id'] }}"></i>
                                    </button>
                                </td>
                                <td>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['client']) : $quotation['client'] !!}</td>
                                <td>
                                    <div class="invoice-row-title">
                                        <div class="invoice-row-text">
                                            <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['title'] ?? $quotation['number']) : ($quotation['title'] ?? $quotation['number']) !!}</strong>
                                            <div class="invoice-number-line">{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $quotation['number']) : $quotation['number'] !!}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quotation['due'] }}</td>
                                <td><span class="invoice-amount">{{ $quotation['amount'] }}</span></td>
                                <td>
                                    <span class="status-pill {{ strtolower($quotation['status']) }}">{{ $quotation['status'] }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('quotations.show', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}" class="text-action-btn view">View</a>
                                    <a href="{{ route('quotations.create', ['step' => 2, 'c' => $quotation['client_id'] ?? $selectedClientId, 'd' => $quotation['record_id']]) }}" class="text-action-btn edit">Edit</a>
                                    <button
                                        type="button"
                                        class="text-action-btn secondary js-open-quotation-copy "
                                        data-copy-url="{{ route('quotations.copy', ['quotation' => $quotation['record_id']]) }}"
                                        data-copy-client-id="{{ $quotation['client_id'] ?? $selectedClientId }}"
                                        data-copy-client-name="{{ $quotation['client'] }}"
                                    >Copy Quotation</button>
                                    <a href="{{ route('quotations.pdf', $quotation['record_id']) }}" class="text-action-btn pdf" target="_blank">PDF</a>
                                    <form method="POST" class="inline-delete" action="{{ route('quotations.destroy', ['quotation' => $quotation['record_id'], 'c' => $selectedClientId]) }}" onsubmit="return confirm(@js('Cancel ' . $quotation['number'] . '?'))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-action-btn delete">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="order-items-row" id="quotation-items-{{ $quotation['record_id'] }}">
                                <td colspan="7" class="order-items-cell">
                                    <div class="order-items-inner">
                                        <div class="order-items-head">
                                            <i class="fas fa-box-open order-items-head-icon"></i>
                                            <strong class="order-items-head-title">Quotation Items</strong>
                                        </div>
                                        <div class="order-items-content">
                                            @if (!empty($quotation['items']) && $quotation['items']->isNotEmpty())
                                                <div class="order-items-grid">
                                                    @foreach ($quotation['items'] as $item)
                                                        @php
                                                            $itemExpired =
                                                                !empty($item->end_date) &&
                                                                $item->end_date < now();
                                                        @endphp
                                                        <div class="order-item-card">
                                                            <div class="order-item-card-row">
                                                                <div>
                                                                    <strong
                                                                        class="order-item-name">{{ $item->item_name ?? 'Item' }}</strong>
                                                                    <div class="order-item-meta">
                                                                        Qty:
                                                                        {{ number_format((float) ($item->quantity ?? 1), 0) }}
                                                                        @if (!empty($item->frequency))
                                                                            | Freq:
                                                                            {{ ucfirst(str_replace('_', ' ', $item->frequency)) }}
                                                                        @endif
                                                                        @if (!empty($item->start_date))
                                                                            | Start:
                                                                            {{ $item->start_date->format('d M Y') }}
                                                                        @endif
                                                                        @if (!empty($item->end_date))
                                                                            | End: <span
                                                                                class="invoice-end-date {{ $itemExpired ? 'is-expired' : '' }}">{{ $item->end_date->format('d M Y') }}</span>
                                                                        @endif
                                                                        | Status: <span
                                                                            class="invoice-muted">{{ ucfirst($item->status ?? 'active') }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <em class="text-muted-light">No items in this quotation</em>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <div id="quotationCopyModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <!-- Backdrop overlay -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm modal-close-overlay" onclick="closeModal('quotationCopyModal')"></div>
        
        <!-- Dialog container -->
        <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden z-10 flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
                <h3 class="text-base font-bold text-slate-800">Copy Quotation</h3>
                <button type="button" class="text-slate-400 hover:text-slate-655 text-lg font-bold" onclick="closeModal('quotationCopyModal')">&times;</button>
            </div>
            <!-- Form -->
            <form method="POST" id="quotationCopyForm">
                @csrf
                <!-- Body -->
                <div class="p-6 overflow-y-auto flex-1 text-left space-y-4">
                    <p class="text-sm text-slate-500 mb-3">Choose the client to copy this quotation into.</p>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1" for="copy_clientid">Client</label>
                        <select id="copy_clientid" name="clientid" class="w-full bg-white border border-slate-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                            <option value="">Choose client</option>
                            @foreach ($clients as $clientOption)
                                <option value="{{ $clientOption->clientid }}">
                                    {{ $clientOption->business_name ?? ($clientOption->contact_name ?? 'Client') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex justify-end items-center gap-2 p-4 border-t border-slate-100 bg-slate-50">
                    <button type="button" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-xs font-semibold" onclick="closeModal('quotationCopyModal')">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">Copy</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
        function toggleQuotationItems(quotationId) {
            const itemsRow = document.getElementById('quotation-items-' + quotationId);
            const icon = document.getElementById('quotation-icon-' + quotationId);
            if (!itemsRow || !icon) return;

            const isActive = itemsRow.classList.toggle('active');
            icon.style.transform = isActive ? 'rotate(90deg)' : 'rotate(0deg)';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('quotationCopyModal');
            const formEl = document.getElementById('quotationCopyForm');
            const clientSelect = document.getElementById('copy_clientid');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

            document.querySelectorAll('.js-open-quotation-copy').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!modalEl || !formEl || !clientSelect || !modal) return;
                    formEl.action = this.dataset.copyUrl || '#';
                    clientSelect.value = this.dataset.copyClientId || '';
                    modal.show();
                });
            });
        });
        </script>
    @endpush
@endsection
