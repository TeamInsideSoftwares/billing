@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('clients.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search clients by name or email..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ $clients->count() }} Clients</p>
            <h3>Accounts and balances</h3>
        </div>
        <a href="{{ route('clients.create') }}" class="primary-button">Add Client</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($clients as $client)
                <div class="table-row">
                    <div>
                        <strong>{{ $client['name'] }}</strong>
                        <span>{{ $client['contact'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $client['email'] }}</strong>
                        <span>{{ $client['balance'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($client['status']) }}">{{ $client['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('clients.show', $client['id']) }}" class="text-link">View</a>
                        <a href="{{ route('clients.edit', $client['id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('clients.destroy', $client['id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $client['name'] }}?')">
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
