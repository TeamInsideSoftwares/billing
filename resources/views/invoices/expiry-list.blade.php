@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('invoices.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Invoices
    </a>
@endsection

@section('content')
    <section class="panel-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <strong>All Clients</strong>
                <div class="small-text">{{ $expiryItems->total() }} item(s) with expiry date</div>
            </div>
            <form method="GET" action="{{ route('invoices.expiry-list') }}" class="d-flex align-items-center" style="gap: 0.5rem;">
                <label for="next_days" class="small-text mb-0">Filter</label>
                <input
                    type="number"
                    name="next_days"
                    id="next_days"
                    class="form-input"
                    min="0"
                    step="1"
                    value="{{ (int) $nextDays }}"
                    placeholder="30 (0 for all)"
                    style="width: 140px;"
                >
                <button type="submit" class="secondary-button">Apply</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Item</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($expiryItems as $row)
                <tr>
                    <td>{{ $row['client_name'] }}</td>
                    <td>{{ $row['item_name'] }}</td>
                    <td>
                        @php($daysLeft = (int) $row['days_left'])
                        {{ $row['end_date'] ?: '-' }}
                    </td>
                    <td>
                        @if($daysLeft > 0)
                            <span class="text-success">{{ $daysLeft }} day(s)</span>
                        @elseif($daysLeft === 0)
                            <span class="text-warning">0 day(s)</span>
                        @else
                            <span class="text-danger">Expired</span>
                        @endif
                    </td>
                    <td class="table-actions">
                        <a
                            href="{{ route('invoices.items.renew', ['item' => $row['invoice_itemid']]) }}"
                            class="secondary-button small"
                            title="Renew"
                        >
                            Renew
                        </a>
                        <form
                            method="POST"
                            action="{{ route('invoices.items.send-reminder', ['invoice' => $row['invoiceid'], 'item' => $row['invoice_itemid']]) }}"
                            class="inline-delete"
                            onsubmit="return confirm('Send reminder for {{ addslashes($row['item_name']) }}?')"
                        >
                            @csrf
                            <button type="submit" class="secondary-button small" title="Send Reminder">
                                Reminder
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="no-records-cell">
                        <i class="fas fa-check-circle empty-state-icon"></i>
                        <p class="no-empty-state-text">No invoice items with expiry date found.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @if($expiryItems->hasPages())
            <div class="d-flex justify-content-end mt-3">
                <nav aria-label="Expired items pagination">
                    <ul class="pagination mb-0">
                        <li class="page-item {{ $expiryItems->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $expiryItems->appends(['next_days' => $nextDays])->previousPageUrl() ?: '#' }}" tabindex="{{ $expiryItems->onFirstPage() ? '-1' : '0' }}">Previous</a>
                        </li>
                        @foreach($expiryItems->appends(['next_days' => $nextDays])->getUrlRange(1, $expiryItems->lastPage()) as $page => $url)
                            <li class="page-item {{ $page === $expiryItems->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach
                        <li class="page-item {{ $expiryItems->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $expiryItems->appends(['next_days' => $nextDays])->nextPageUrl() ?: '#' }}" tabindex="{{ $expiryItems->hasMorePages() ? '0' : '-1' }}">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    </section>
@endsection
