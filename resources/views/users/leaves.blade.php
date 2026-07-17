@extends('layouts.app')

@section('header_actions')
<a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left btn-icon"></i> Back to Team List
</a>
@endsection

@section('content')
<div class="bg-white p-3 rounded-3 shadow-sm">

    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('users.leaves.index') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <select name="employee_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->userid }}" {{ request('employee_id') == $emp->userid ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select name="typeid" class="form-select" onchange="this.form.submit()">
                        <option value="">All Leave Types</option>
                        @foreach($leaveTypes as $type)
                            <option value="{{ $type->typeid }}" {{ request('typeid') == $type->typeid ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select name="month" class="form-select" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ request('month') == sprintf('%02d', $i) ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <input type="date" name="date" id="dateFilter" class="form-control"
                            value="{{ request('date') }}" onchange="this.form.submit()">
                        @if(request('date'))
                            <button type="button" class="input-group-text bg-white cursor-pointer" onclick="document.getElementById('dateFilter').value=''; this.form.submit();" title="Clear Date">
                                <i class="fas fa-times text-danger"></i>
                            </button>
                        @else
                            <span class="input-group-text"><i class="far fa-calendar-alt text-muted"></i></span>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <ul class="nav nav-underline d-inline-flex mb-3 settings-tab-group border-bottom rounded-3 gap-0" role="tablist">
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn active text-primary bg-primary-subtle border-primary fw-bold"
                data-bs-toggle="tab" data-bs-target="#pending-requests" role="tab" aria-controls="pending-requests"
                aria-selected="true"> Pending Requests
            </button>
        </li>
        <li class="nav-item">
            <button type="button"
                class="nav-link btn btn-md px-3 rounded-0 settings-tab-btn text-primary bg-transparent border-transparent"
                data-bs-toggle="tab" data-bs-target="#action-history" role="tab" aria-controls="action-history"
                aria-selected="false"> Action History
            </button>
        </li>
    </ul>

    <div class="tab-content settings-tab-content">
        <div id="pending-requests" class="tab-pane fade show active" role="tabpanel">
            @php
            $pendingLeaves = $leaves->filter(fn($l) => $l->status === 'pending');
        @endphp
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
            <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Team Member</th>
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
                                $paidDays = (float) $leave->leave_days;
                                $lwpDays = (float) $leave->lwp_days;
                                $totalDays = $paidDays + $lwpDays;
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
                                <td class="text-center">
                                    <span class="badge bg-secondary d-block">{{ $totalDays }} day(s)</span>
                                    @if($lwpDays > 0)
                                        <small class="text-danger d-block mt-1" style="font-size: 0.75rem;">({{ $paidDays }} Paid, {{ $lwpDays }} LWP)</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-wrap d-inline-block text-muted small" style="max-width: 250px;" title="{{ $leave->reason }}">
                                        {{ \Illuminate\Support\Str::limit($leave->reason, 60) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1 align-items-center justify-content-end">
                                        <button type="button" class="bg01 color01 border-0" data-bs-toggle="modal" data-bs-target="#viewPendingLeaveModal-{{ $leave->requestid }}">
                                            View
                                        </button>
                                        <form action="{{ route('users.leaves.action', $leave) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="bg03 color03">Approve</button>
                                        </form>
                                        <button class="bg02 color02" type="button" data-bs-toggle="collapse" data-bs-target="#rejectCol_{{ $leave->requestid }}">
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

                                    <!-- View Modal -->
                                    <div class="modal fade text-start" id="viewPendingLeaveModal-{{ $leave->requestid }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-light border-0 py-2">
                                                    <h6 class="modal-title fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i>Pending Leave Request</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="mb-3 pb-3 border-bottom d-flex align-items-center gap-3">
                                                        <img src="{{ !empty($leave->user?->profile_image) ? asset('storage/' . $leave->user->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($leave->user->name ?? 'User') . '&background=eff6ff&color=1e3a8a' }}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold text-dark">{{ $leave->user->name ?? 'Unknown User' }}</h6>
                                                            <small class="text-muted">{{ $leave->user->email ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Leave Type</small>
                                                            <span class="fw-medium text-dark">{{ $leave->leaveType?->name ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Status</small>
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Pending</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Start Date</small>
                                                            <span class="fw-medium text-dark">{{ $leave->start_date?->format('M d, Y') ?? '-' }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">End Date</small>
                                                            <span class="fw-medium text-dark">{{ $leave->end_date?->format('M d, Y') ?? '-' }}</span>
                                                        </div>
                                                        <div class="col-12">
                                                            <small class="text-muted d-block fw-semibold">Duration Breakdown</small>
                                                            <span class="fw-medium text-dark">{{ $totalDays }} Total Day(s)</span>
                                                            @if($lwpDays > 0)
                                                                <span class="text-danger small ms-1 fw-medium">({{ $paidDays }} Paid, {{ $lwpDays }} LWP)</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="bg-light p-2 rounded-3">
                                                        <small class="text-muted d-block mb-1 fw-semibold">Reason provided by employee:</small>
                                                        <p class="mb-0 text-dark small lh-sm">{{ $leave->reason }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox mb-3 text-secondary fs-1 opacity-50"></i>
                                    <p class="fw-semibold text-dark mb-1">No pending leave requests found.</p>
                                    <p class="small text-muted mb-0">Pending approvals will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div> <!-- End pending-requests -->

        <div id="action-history" class="tab-pane fade" role="tabpanel">
            @php
            $historyLeaves = $leaves->filter(fn($l) => $l->status !== 'pending');
        @endphp
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
            <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Team Member</th>
                            <th>Leave Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th class="text-center">Duration</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyLeaves as $leave)
                            @php
                                $paidDays = (float) $leave->leave_days;
                                $lwpDays = (float) $leave->lwp_days;
                                $totalDays = $paidDays + $lwpDays;
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
                                <td class="text-center">
                                    <span class="badge bg-secondary d-block">{{ $totalDays }} day(s)</span>
                                    @if($lwpDays > 0)
                                        <small class="text-danger d-block mt-1" style="font-size: 0.75rem;">({{ $paidDays }} Paid, {{ $lwpDays }} LWP)</small>
                                    @endif
                                </td>
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
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1 align-items-center justify-content-end">
                                        <button type="button" class="bg01 color01 border-0" data-bs-toggle="modal" data-bs-target="#viewLeaveModal-{{ $leave->requestid }}">
                                            View
                                        </button>
                                    </div>

                                    <!-- View Modal -->
                                    <div class="modal fade text-start" id="viewLeaveModal-{{ $leave->requestid }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-light border-0 py-2">
                                                    <h6 class="modal-title fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i>Leave Request Details</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="mb-3 pb-3 border-bottom d-flex align-items-center gap-3">
                                                        <img src="{{ !empty($leave->user?->profile_image) ? asset('storage/' . $leave->user->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode($leave->user->name ?? 'User') . '&background=eff6ff&color=1e3a8a' }}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold text-dark">{{ $leave->user->name ?? 'Unknown User' }}</h6>
                                                            <small class="text-muted">{{ $leave->user->email ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row g-3 mb-3">
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Leave Type</small>
                                                            <span class="fw-medium text-dark">{{ $leave->leaveType?->name ?? 'N/A' }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Status</small>
                                                            @if($leave->status === 'approved')
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Approved</span>
                                                            @else
                                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Rejected</span>
                                                            @endif
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">Start Date</small>
                                                            <span class="fw-medium text-dark">{{ $leave->start_date?->format('M d, Y') ?? '-' }}</span>
                                                        </div>
                                                        <div class="col-6">
                                                            <small class="text-muted d-block fw-semibold">End Date</small>
                                                            <span class="fw-medium text-dark">{{ $leave->end_date?->format('M d, Y') ?? '-' }}</span>
                                                        </div>
                                                        <div class="col-12">
                                                            <small class="text-muted d-block fw-semibold">Duration Breakdown</small>
                                                            <span class="fw-medium text-dark">{{ $totalDays }} Total Day(s)</span>
                                                            @if($lwpDays > 0)
                                                                <span class="text-danger small ms-1 fw-medium">({{ $paidDays }} Paid, {{ $lwpDays }} LWP)</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3 bg-light p-2 rounded-3">
                                                        <small class="text-muted d-block mb-1 fw-semibold">Reason provided by employee:</small>
                                                        <p class="mb-0 text-dark small lh-sm">{{ $leave->reason }}</p>
                                                    </div>
                                                    
                                                    @if($leave->status === 'rejected' && $leave->rejection_reason)
                                                    <div class="bg-danger-subtle p-2 rounded-3 border border-danger-subtle">
                                                        <small class="text-danger fw-bold d-block mb-1"><i class="fas fa-exclamation-circle me-1"></i> Rejection Reason:</small>
                                                        <p class="mb-0 text-danger small lh-sm">{{ $leave->rejection_reason }}</p>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted bg-white">
                                    <i class="fas fa-inbox mb-3 text-secondary fs-1 opacity-50"></i>
                                    <p class="fw-semibold text-dark mb-1">No processed leave requests found.</p>
                                    <p class="small text-muted mb-0">Approved and rejected requests will appear here.</p>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function activateTab(tabId) {
            if (!tabId || !window.bootstrap || !bootstrap.Tab) return;

            const targetSelector = tabId.startsWith('#') ? tabId : `#${tabId}`;
            const tabTrigger = document.querySelector(`[data-bs-target="${targetSelector}"]`);

            if (!tabTrigger) return;

            bootstrap.Tab.getOrCreateInstance(tabTrigger).show();
        }

        function updateTabButtonClasses(activeButton, inactiveButton) {
            if (inactiveButton) {
                inactiveButton.classList.remove('active', 'bg-primary-subtle', 'border-primary', 'fw-bold');
                inactiveButton.classList.add('bg-transparent', 'border-transparent');
                inactiveButton.setAttribute('aria-selected', 'false');
            }

            if (activeButton) {
                activeButton.classList.add('active', 'bg-primary-subtle', 'border-primary', 'fw-bold');
                activeButton.classList.remove('bg-transparent', 'border-transparent');
                activeButton.setAttribute('aria-selected', 'true');
            }
        }

        document.querySelectorAll('[data-bs-toggle="tab"]').forEach((button) => {
            button.addEventListener('shown.bs.tab', function (event) {
                const targetId = (event.target.getAttribute('data-bs-target') || '').replace('#', '');
                if (targetId) {
                    window.history.replaceState(null, null, `#${targetId}`);
                }

                updateTabButtonClasses(event.target, event.relatedTarget);
            });
        });

        const hashTab = window.location.hash.replace('#', '');
        const validHash = hashTab && document.querySelector(`[data-bs-target="#${hashTab}"]`);
        
        if (validHash) {
            activateTab(hashTab);
        } else {
            const activeTabButton = document.querySelector('[data-bs-toggle="tab"].active');
            if (activeTabButton) {
                const targetId = (activeTabButton.getAttribute('data-bs-target') || '').replace('#', '');
                if (targetId) {
                    window.history.replaceState(null, null, `#${targetId}`);
                }
            }
        }
    });
</script>
@endpush
