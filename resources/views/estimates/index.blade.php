@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('estimates.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by number or client..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ count($estimates) }} Estimates</p>
            <h3>Estimate pipeline</h3>
        </div>
        <a href="{{ route('estimates.create') }}" class="primary-button">New Estimate</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($estimates as $estimate)
                <div class="table-row">
                    <div>
                        <strong>{{ $estimate['number'] }}</strong>
                        <span>{{ $estimate['client'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $estimate['amount'] }}</strong>
                        <span>Expires {{ $estimate['expiry'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($estimate['status']) }}">{{ $estimate['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('estimates.show', $estimate['id']) }}" class="text-link">View</a>
                        <form method="POST" action="{{ route('estimates.destroy', $estimate['id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $estimate['number'] }}?')">
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
