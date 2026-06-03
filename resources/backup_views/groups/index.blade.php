@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('clients.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Clients
    </a>
@endsection

@section('content')
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
                        <span class="text-muted">{{ $group['email'] }}</span>
                    </td>
                    <td>
                        <span class="text-muted">{{ $group['city'] }}, {{ $group['state'] }}</span>
                    </td>
                    <td class="">
                        <a href="{{ route('groups.edit', $group['record_id']) }}" class="text-action-btn edit">Edit</a>
                        <form method="POST" action="{{ route('groups.destroy', $group['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $group['group_name'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-action-btn delete">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
