@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('subscriptions.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by client or service..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <span class="search-badge">Filtered</span>
            @endif
        </div>
        <a href="{{ route('subscriptions.create') }}" class="primary-button">Add Subscription</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Subscription</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($subscriptions as $subscription)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $subscription['client']) : $subscription['client'] !!}</strong>
                        <span>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $subscription['service']) : $subscription['service'] !!}</span>
                    </td>
                    <td>
                        <strong>{{ $subscription['amount'] }}</strong>
                        <span>Next bill {{ $subscription['next_bill'] }}</span>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($subscription['status']) }}">{{ $subscription['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('subscriptions.show', $subscription['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <form method="POST" action="{{ route('subscriptions.destroy', $subscription['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete subscription for {{ $subscription['client'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="no-records-cell">
                        <i class="fas fa-sync empty-state-icon"></i>
                        <p class="no-empty-state-text">No subscriptions found</p>
                        <p class="small-text">Set up recurring billing by adding your first subscription.</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
