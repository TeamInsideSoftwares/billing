@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add User
    </a>
@endsection

@section('content')
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-4">
                    <input type="text" name="search" class="form-control" value="{{ $searchTerm }}" placeholder="Search by name, email, department, role">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">Search</button>
                </div>
                @if($searchTerm)
                    <div class="col-auto">
                        <a href="{{ route('users.index') }}" class="btn btn-link text-decoration-none">Clear</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Image</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $member)
                            <tr>
                                <td class="ps-4"><strong>{{ $member->name }}</strong></td>
                                <td>
                                    @if(!empty($member->profile_image))
                                        <img src="{{ asset('storage/' . $member->profile_image) }}" alt="{{ $member->name }}" class="img-thumbnail rounded-circle" style="width:34px;height:34px;object-fit:cover;">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->department ?? '-' }}</td>
                                <td>{{ $member->designation ?? '-' }}</td>
                                <td>{{ ucfirst($member->role) }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ is_array($member->permissions) ? count($member->permissions) : 0 }}</span>
                                </td>
                                <td>
                                    @if($member->is_active)
                                        <span class="badge rounded-pill text-bg-success">Active</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('users.edit', $member) }}" class="btn btn-sm btn-outline-info">Edit</a>
                                        <form method="POST" action="{{ route('users.destroy', $member) }}" class="d-inline" onsubmit="return confirm('Cancel this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
