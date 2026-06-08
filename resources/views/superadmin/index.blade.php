@extends('layouts.superadmin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="text-uppercase text-muted small fw-bold d-block mb-1">Agencies Master</span>
            <h3 class="h4 mb-0">All Registered Agencies</h3>
        </div>
        <a href="{{ route('superadmin.create') }}" class="btn btn-primary">+ Register New Company</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Company</th>
                            <th>Contact</th>
                            <th>Allow Sync</th>
                            <th>Expires On</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if(count($accounts) > 0)
                        @foreach ($accounts as $acc)
                            <tr>
                                <td class="ps-4">
                                    <strong>{{ $acc->name }}</strong>
                                    <span class="text-muted small d-block">ID: {{ $acc->accountid }}</span>
                                </td>
                                <td>
                                    <strong>{{ $acc->credential->email ?? $acc->email }}</strong>
                                    <span class="text-muted small d-block">{{ $acc->legal_name ?? 'No legal name' }}</span>
                                </td>
                                <td>{{ $acc->allow_sync ? 'Yes' : 'No' }}</td>
                                <td>{{ $acc->expires_at ? $acc->expires_at->format('d M Y') : 'No Expiry' }}</td>
                                <td>
                                    @if($acc->status === 'active')
                                        <span class="badge rounded-pill text-bg-success">{{ ucfirst($acc->status) }}</span>
                                    @elseif($acc->status === 'pending')
                                        <span class="badge rounded-pill text-bg-warning text-dark">{{ ucfirst($acc->status) }}</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-secondary">{{ ucfirst($acc->status) }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('superadmin.edit', $acc) }}" class="btn btn-sm btn-outline-primary" title="Manage">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No agencies registered yet.
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
