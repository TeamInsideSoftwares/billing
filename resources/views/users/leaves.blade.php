@extends('layouts.app')

@section('header_actions')
<a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left btn-icon"></i> Back to Users
</a>
@endsection

@section('content')
<div class="bg-white p-3 rounded-3 shadow-sm">

    <!-- Pending Leaves Section -->
    <div class="mb-5">
        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-hourglass-start me-1"></i> Pending Actions</h6>
        @php
            $pendingLeaves = $leaves->filter(fn($l) => $l->status === 'pending');
        @endphp
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
            <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Duration</th>
                            <th>Reason</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingLeaves as $leave)
                            @php
                                $days = $leave->start_date && $leave->end_date 
                                    ? $leave->start_date->diffInDays($leave->end_date) + 1 
                                    : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ !empty($leave->user?->profile_image) ? asset('storage/' . $leave->user->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($leave->user->name ?? 'User') . '&background=eff6ff&color=1e3a8a' }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                        <div>
                                            <span class="fw-medium text-dark d-block">{{ $leave->user->name ?? 'Unknown User' }}</span>
                                            <span class="text-muted small">{{ $leave->user->email ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $leave->leaveType?->name ?? 'N/A' }}</span>
                                </td>
                                <td class="small">{{ $leave->start_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="small">{{ $leave->end_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $days }} day(s)</span></td>
                                <td>
                                    <span class="text-wrap d-inline-block text-muted small" style="max-width: 250px;" title="{{ $leave->reason }}">
                                        {{ \Illuminate\Support\Str::limit($leave->reason, 60) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <form action="{{ route('users.leaves.action', $leave) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success text-white">Approve</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-danger text-white" data-bs-toggle="collapse" data-bs-target="#rejectCol_{{ $leave->requestid }}">
                                            Reject
                                        </button>
                                    </div>
                                    <div class="collapse mt-2 text-start" id="rejectCol_{{ $leave->requestid }}" style="width: 250px; float: right;">
                                        <form action="{{ route('users.leaves.action', $leave) }}" method="POST" class="bg-light p-2 border rounded-3">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <label class="form-label small fw-semibold mb-1">Rejection Reason</label>
                                            <input type="text" name="rejection_reason" class="form-control form-control-sm mb-1" placeholder="Specify reason" required>
                                            <button type="submit" class="btn btn-danger btn-sm w-100 text-white">Confirm</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted bg-white">
                                    <i class="fas fa-check-circle fs-3 mb-2 text-success opacity-50"></i>
                                    <p class="small mb-0">No pending leave requests from unassigned employees.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Processed Leaves History Section -->
    <div>
        <h6 class="fw-bold text-secondary mb-3"><i class="fas fa-history me-1"></i> Action History</h6>
        @php
            $historyLeaves = $leaves->filter(fn($l) => $l->status !== 'pending');
        @endphp
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
            <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Duration</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyLeaves as $leave)
                            @php
                                $days = $leave->start_date && $leave->end_date 
                                    ? $leave->start_date->diffInDays($leave->end_date) + 1 
                                    : 0;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ !empty($leave->user?->profile_image) ? asset('storage/' . $leave->user->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($leave->user->name ?? 'User') . '&background=eff6ff&color=1e3a8a' }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                        <div>
                                            <span class="fw-medium text-dark d-block">{{ $leave->user->name ?? 'Unknown User' }}</span>
                                            <span class="text-muted small">{{ $leave->user->email ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $leave->leaveType?->name ?? 'N/A' }}</span>
                                </td>
                                <td class="small">{{ $leave->start_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="small">{{ $leave->end_date?->format('M d, Y') ?? '-' }}</td>
                                <td class="text-center"><span class="badge bg-secondary">{{ $days }} day(s)</span></td>
                                <td>
                                    @if($leave->status === 'approved')
                                        <span class="badge bg-success py-1.5 px-3 rounded-pill text-white"><i class="fas fa-check-circle me-1"></i> Approved</span>
                                    @else
                                        <span class="badge bg-danger py-1.5 px-3 rounded-pill text-white"><i class="fas fa-times-circle me-1"></i> Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    @if($leave->status === 'rejected' && $leave->rejection_reason)
                                        <small class="text-danger"><strong>Reason:</strong> {{ $leave->rejection_reason }}</small>
                                    @else
                                        <small class="text-muted">Processed by Admin</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted bg-white">
                                    <p class="small mb-0">No processed leave requests from unassigned employees.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
