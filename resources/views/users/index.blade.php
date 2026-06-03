@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('users.create') }}" class="primary-button">
        <i class="fas fa-plus icon-spaced"></i>Add User
    </a>
@endsection

@section('content')
    <section class="panel-card mb-3">
        <form method="GET" action="{{ route('users.index') }}" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" value="{{ $searchTerm }}" placeholder="Search by name, email, department, role" style="max-width:420px;">
            <button type="submit" class="secondary-button">Search</button>
            @if($searchTerm)
                <a href="{{ route('users.index') }}" class="text-link">Clear</a>
            @endif
        </form>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Image</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $member)
                    <tr>
                        <td><strong>{{ $member->name }}</strong></td>
                        <td>
                            @if(!empty($member->profile_image))
                                <img src="{{ asset('storage/' . $member->profile_image) }}" alt="{{ $member->name }}" style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:1px solid #dbe3ef;">
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->department ?? '-' }}</td>
                        <td>{{ $member->designation ?? '-' }}</td>
                        <td>{{ ucfirst($member->role) }}</td>
                        <td>{{ is_array($member->permissions) ? count($member->permissions) : 0 }}</td>
                        <td>
                            <span class="status-pill {{ $member->is_active ? 'active' : 'inactive' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="{{ route('users.edit', $member) }}" class="text-action-btn edit">Edit</a>
                            <form method="POST" action="{{ route('users.destroy', $member) }}" class="inline-delete" onsubmit="return confirm('Cancel this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-action-btn delete">Cancel</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="no-records-cell">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
