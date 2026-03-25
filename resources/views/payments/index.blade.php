@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('payments.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by reference or client..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ count($payments) }} Payments</p>
            <h3>Incoming payments</h3>
        </div>
        <a href="{{ route('payments.create') }}" class="primary-button">Record Payment</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($payments as $payment)
                <div class="table-row">
                    <div>
                        <strong>{{ $payment['ref'] }}</strong>
                        <span>{{ $payment['client'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $payment['amount'] }}</strong>
                        <span>{{ $payment['date'] }} via {{ $payment['method'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($payment['status']) }}">{{ $payment['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('payments.index') }}" class="text-link">View</a>
                        <form method="POST" action="{{ route('payments.index') }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $payment['ref'] }}?')">
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
