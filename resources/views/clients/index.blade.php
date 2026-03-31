@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('clients.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search clients by name or email..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
        </div>
        <div>
            <a href="{{ route('clients.create') }}" class="primary-button">Add Client</a>
            <a href="{{ route('groups.index') }}" class="secondary-button">Manage Groups</a>
        </div>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($clients as $client)
                <tr>
                    <td>
                        <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $client['name']) : $client['name'] !!}</strong>
                        <!-- <span>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $client['email']) : $client['email'] !!}</span> -->
                    </td>
                    <td>
                        <strong>{!! $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $client['email']) : $client['email'] !!}</strong>
                    </td>
                    <td>
                        <strong>{{ $client['balance'] }}</strong>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($client['status']) }}">{{ $client['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('clients.show', $client['record_id']) }}" class="text-link">View</a>
                        <a href="{{ route('clients.edit', $client['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $client['name'] }}?')">
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
