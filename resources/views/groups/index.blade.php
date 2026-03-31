@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('groups.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search groups by name..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
        </div>
        <div class="button-group" style="display: flex; gap: 0.75rem;">
            <a href="{{ route('groups.create') }}" class="primary-button">Add Group</a>
        </div>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Group Name</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($groups as $group)
                <tr>
                    <td>
                        <strong>{{ $group['group_name'] }}</strong>
                    </td>
                    <td>
                        <span style="color: var(--text-muted);">{{ $group['email'] }}</span>
                    </td>
                    <td>
                        <span style="color: var(--text-muted);">{{ $group['city'] }}, {{ $group['state'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('groups.edit', $group['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('groups.destroy', $group['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $group['group_name'] }}?')">
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
