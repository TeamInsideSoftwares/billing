@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('subscriptions.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by client or service..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ count($subscriptions) }} Subscriptions</p>
            <h3>Subscription billing</h3>
        </div>
        <a href="{{ route('subscriptions.create') }}" class="primary-button">Add Subscription</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($subscriptions as $subscription)
                <div class="table-row">
                    <div>
                        <strong>{{ $subscription['client'] }}</strong>
                        <span>{{ $subscription['service'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $subscription['amount'] }}</strong>
                        <span>Next bill {{ $subscription['next_bill'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($subscription['status']) }}">{{ $subscription['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('subscriptions.index') }}" class="text-link">View</a>
                        <form method="POST" action="{{ route('subscriptions.index') }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete subscription for {{ $subscription['client'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
