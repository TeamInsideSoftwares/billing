@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">Client Groups</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Search results for "{{ request('search') }}"
                </p>
            @endif
            <a href="{{ route('clients.index') }}" class="text-link" style="margin-top: 0.5rem; display: inline-block;">&larr; Back to clients</a>
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
