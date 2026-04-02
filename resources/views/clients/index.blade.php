@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Clients</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Found {{ $clients->count() }} result(s) for "{{ request('search') }}"
                </p>
            @endif
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
                        <a href="{{ route('clients.show', $client['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('clients.edit', $client['record_id']) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('clients.destroy', $client['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $client['name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
