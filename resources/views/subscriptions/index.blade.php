@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('subscriptions.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by client or service..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <p class="eyebrow">{{ $resultCount }} subscriptions matching "{{ $searchTerm }}"</p>
                <span class="search-badge">Filtered</span>
            @else
                <p class="eyebrow">{{ count($subscriptions) }} subscriptions</p>
            @endif
            <h3>Subscription billing</h3>

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
            @foreach ($subscriptions as $subscription)
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
                        <a href="{{ route('subscriptions.show', $subscription['record_id']) }}" class="text-link">View</a>
                        <form method="POST" action="{{ route('subscriptions.destroy', $subscription['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete subscription for {{ $subscription['client'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
