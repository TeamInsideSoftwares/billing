@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#manageRolesModal">
        <i class="fas fa-user-shield btn-icon"></i> Manage Roles
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#manageDepartmentsModal">
        <i class="fas fa-sitemap btn-icon"></i> Manage Departments
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#shiftsModal">
        <i class="fas fa-calendar-alt btn-icon"></i> Shifts
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#attendancePoliciesModal">
        <i class="fas fa-clock btn-icon"></i> Att. Policies
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#leavePoliciesModal">
        <i class="fas fa-calendar-times btn-icon"></i> Leave Policies
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#leaveTypesModal">
        <i class="fas fa-list-alt btn-icon"></i> Leave Types
    </button>
    <a href="{{ route('users.approvals') }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium position-relative">
        <i class="fas fa-user-check btn-icon"></i> Profile Approvals
        @php
            $pendingCount = \App\Models\UserProfile::where('status', 'pending')->count();
        @endphp
        @if($pendingCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
            {{ $pendingCount }}
        </span>
        @endif
    </a>
    <a href="{{ route('users.create') }}"
        class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
        <i class="fas fa-plus btn-icon"></i> Add User
    </a>
</div>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <!-- Filters Card -->
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('users.index') }}" method="GET" class="mainForm">
            <div class="row g-2 justify-content-end">
                <div class="col-12 col-md-4">
                    <div class="position-relative">
                        <i class="fas fa-search position-absolute text-muted"
                            style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 15px;"></i>
                        <input type="text" name="search" class="form-control"
                            value="{{ $searchTerm ?? '' }}" placeholder="Search by name, email, department, role"
                            style="padding-left: 38px;" onchange="this.form.submit()">
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- View Toggle Bar & Legend -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center align-self-end gap-3 small text-dark px-2">
            <div class="d-flex align-items-center">
                <span class="status-dot legend-dot active"></span> Active
            </div>
            <div class="d-flex align-items-center">
                <span class="status-dot legend-dot inactive"></span> Inactive
            </div>
        </div>
    </div>

    <!-- Users List View (Table View) -->
    <div id="users-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Department</th>

                        <th>Role</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $member)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                    @if(!empty($member->profile_image))
                                        <img src="{{ asset('storage/' . $member->profile_image) }}" alt="{{ $member->name }}" class="position-absolute rounded-circle" style="width:40px;height:40px;object-fit:cover;top:0;left:0;border:2px solid #fff;">
                                    @else
                                        <span class="d-block position-absolute">{{ strtoupper(substr($member->name, 0, 2)) }}</span>
                                    @endif
                                    <div class="status-dot {{ $member->is_active ? 'active' : 'inactive' }}"
                                        title="{{ $member->is_active ? 'Active' : 'Inactive' }}"></div>
                                </div>
                                <div>
                                    <span class="d-block fw-semibold">{!! $searchTerm
                                        ? str_ireplace($searchTerm, '<mark class="bg-warning-subtle p-0">' . $searchTerm
                                            . '</mark>', $member->name)
                                        : $member->name !!}</span>
                                    <span class="d-block text-dark small">{{ $member->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="d-block text-dark small">{{ $member->department ? $member->department->name : '-' }}</span>
                        </td>

                        <td>
                            <span class="badge bg-light text-dark border">{{ ucfirst($member->role ? $member->role->name : '') }}</span>
                        </td>
                        <td>
                            @php
                                $modules = collect(is_array($member->permissions) ? $member->permissions : [])
                                    ->map(fn($p) => ucfirst(str_replace('_', ' ', explode('.', $p)[0])))
                                    ->unique()
                                    ->values();
                            @endphp
                            
                            @if($modules->isEmpty())
                                <span class="badge bg-light text-dark border">No Access</span>
                            @else
                                <div class="d-flex flex-wrap gap-1" title="{{ $modules->implode(', ') }}">
                                    @foreach($modules->take(3) as $mod)
                                        <span class="badge bg-light text-dark border">{{ $mod }}</span>
                                    @endforeach
                                    @if($modules->count() > 3)
                                        <span class="badge bg-light text-dark border" data-bs-toggle="tooltip" title="{{ $modules->slice(3)->implode(', ') }}">+{{ $modules->count() - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1 align-items-center">
                                @if($member->hasPermission('team_work.view'))
                                <form action="{{ route('login.as', $member) }}" method="POST" class="d-inline" target="_blank">
                                    @csrf
                                    <button type="submit" class="bg01 color01 border-0" title="Login As User">Login As</button>
                                </form>
                                @endif
                                @if(auth()->user()->hasPermission('users.edit'))
                                <a href="{{ route('users.edit', $member) }}" class="bg03 color03" title="Edit User">Edit</a>
                                @endif
                                @if(auth()->user()->hasPermission('users.cancel'))
                                <form method="POST" action="{{ route('users.toggle-status', $member) }}" class="m-0 p-0 d-inline" title="Toggle Status">
                                    @csrf @method('PATCH')
                                    <div class="form-check form-switch mb-0 d-inline-flex align-items-center me-1" style="padding-left: 2.5em; min-height: auto;">
                                        <input class="form-check-input border-primary" type="checkbox" role="switch" onchange="this.form.submit()"
                                            {{ $member->is_active ? 'checked' : '' }} style="cursor: pointer; height: 1.15em; width: 2.1em;">
                                    </div>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-users mb-3 text-secondary fs-1 opacity-50"></i>
                            <p class="fw-semibold text-dark mb-1">No users found</p>
                            <p class="small text-muted mb-0">Try adjusting your search criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manage Roles Modal -->
<div class="modal fade" id="manageRolesModal" tabindex="-1" aria-labelledby="manageRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="manageRolesModalLabel">Roles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="roleForm" method="POST" action="{{ route('roles.store') }}" class="mainForm">
                        @csrf
                        <div id="roleMethodField"></div>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-8">
                                <label for="roleName" class="form-label small lh-sm fw-semibold text-dark mb-1">Role Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="roleName" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-4 d-flex">
                                <button type="submit" id="roleSubmitBtn" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                                    Save Role <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="position-relative bg-DarkLight p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">Role List</h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0" id="rolesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50%">Role Name</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $roleObj)
                                    <tr>
                                        <td><span class="d-block fw-semibold">{{ $roleObj->name }}</span></td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <form method="POST" action="{{ route('roles.toggle-status', $roleObj->roleid) }}" class="d-inline role-ajax-form">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="{{ $roleObj->status === 'active' ? 'bg02 color02' : 'bg-secondary text-white' }}">
                                                        {{ ucfirst($roleObj->status) }}
                                                    </button>
                                                </form>
                                                <button type="button" class="bg03 color03 border-0" onclick="editRole(this)" data-id="{{ $roleObj->roleid }}" data-name="{{ $roleObj->name }}">Edit</button>
                                                <form method="POST" action="{{ route('roles.destroy', $roleObj->roleid) }}" class="d-inline role-ajax-form">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="text-center py-4 text-muted">No roles found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Departments Modal -->
<div class="modal fade" id="manageDepartmentsModal" tabindex="-1" aria-labelledby="manageDepartmentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="manageDepartmentsModalLabel">Departments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="departmentForm" method="POST" action="{{ route('departments.store') }}" class="mainForm">
                        @csrf
                        <div id="departmentMethodField"></div>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-8">
                                <label for="departmentName" class="form-label small lh-sm fw-semibold text-dark mb-1">Department Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="departmentName" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-4 d-flex">
                                <button type="submit" id="departmentSubmitBtn" class="btn btn-outline-primary btn-primary text-white fw-medium w-100">
                                    Save Department <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="position-relative bg-DarkLight p-2 rounded-3">
                    <h6 class="fw-semibold text-dark mb-2 px-1">Department List</h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0" id="departmentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50%">Department Name</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($departments as $dept)
                                    <tr>
                                        <td><span class="d-block fw-semibold">{{ $dept->name }}</span></td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <form method="POST" action="{{ route('departments.toggle-status', $dept->depid) }}" class="d-inline dept-ajax-form">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class=" {{ $dept->status === 'active' ? 'bg02 color02' : 'bg-secondary text-white' }}">
                                                        {{ ucfirst($dept->status) }}
                                                    </button>
                                                </form>
                                                <button type="button" class="bg03 color03 border-0" onclick="editDepartment(this)" data-id="{{ $dept->depid }}" data-name="{{ $dept->name }}">Edit</button>
                                                <form method="POST" action="{{ route('departments.destroy', $dept->depid) }}" class="d-inline dept-ajax-form">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="text-center py-4 text-muted">No departments found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Policies Modal -->
<div class="modal fade" id="attendancePoliciesModal" tabindex="-1" aria-labelledby="attendancePoliciesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="attendancePoliciesModalLabel">Manage Attendance Policies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="attendancePolicyForm" action="{{ route('attendance-policies.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="attendancePolicyMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-12">
                                <label for="policy_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Policy Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="policy_name" name="policy_name" placeholder="e.g. Standard Attendance Policy" required>
                            </div>
                            
                            <div class="col-12 col-md-12">
                                <label for="description" class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2" placeholder="e.g. Default attendance policy for all employees..."></textarea>
                            </div>

                            <div class="col-6">
                                <label for="late_arrival_grace" class="form-label small fw-semibold text-dark">Late Arrival Grace (mins)<span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="late_arrival_grace" name="late_arrival_grace" min="0" required>
                            </div>
                            <div class="col-6">
                                <label for="early_departure_grace" class="form-label small fw-semibold text-dark">Early Departure Grace (mins)<span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="early_departure_grace" name="early_departure_grace" min="0" required>
                            </div>

                            <!-- <div class="col-6">
                                <label for="overtime_rate" class="form-label small fw-semibold text-dark">Overtime Rate (%)<span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="overtime_rate" name="overtime_rate" min="0" required>
                            </div> -->
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" id="btnCancelPolicyEdit" style="display: none;">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSavePolicy">Save Policy <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span>Existing Policies</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Policy Name</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="attendancePoliciesList">
                                    <tr><td colspan="2" class="text-center py-3 text-muted small" id="attendancePoliciesLoading">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Shifts Modal -->
<div class="modal fade" id="shiftsModal" tabindex="-1" aria-labelledby="shiftsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="shiftsModalLabel">Manage Shifts</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="shiftForm" action="{{ route('shifts.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="shiftMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-12">
                                <label for="shift_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Shift Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="shift_name" name="shift_name" placeholder="e.g. Morning Shift" required>
                            </div>
                            
                            <div class="col-6">
                                <label for="start_time" class="form-label small lh-sm fw-semibold text-dark mb-1">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>
                            <div class="col-6">
                                <label for="end_time" class="form-label small lh-sm fw-semibold text-dark mb-1">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="break_duration" class="form-label small lh-sm fw-semibold text-dark mb-1">Break Duration (min) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="break_duration" name="break_duration" value="0" min="0" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="break_start_time" class="form-label small lh-sm fw-semibold text-dark mb-1">Break Start Time</label>
                                <input type="time" class="form-control" id="break_start_time" name="break_start_time">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="break_end_time" class="form-label small lh-sm fw-semibold text-dark mb-1">Break End Time</label>
                                <input type="time" class="form-control" id="break_end_time" name="break_end_time">
                            </div>
                            
                            <div class="col-6">
                                <label for="break_grace_period" class="form-label small lh-sm fw-semibold text-dark mb-1">Break Grace Period (min) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="break_grace_period" name="break_grace_period" value="0" min="0" required>
                            </div>
                            <div class="col-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium w-100" id="btnSaveShift">
                                    Save Shift <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2" id="shiftCancelContainer" style="display: none;">
                            <button type="button" class="btn btn-light" id="btnCancelShiftEdit">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span>Existing Shifts</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Shift Name</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="shiftsList">
                                    <tr><td colspan="2" class="text-center py-3 text-muted small" id="shiftsLoading">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Leave Policies Modal -->
<div class="modal fade" id="leavePoliciesModal" tabindex="-1" aria-labelledby="leavePoliciesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="leavePoliciesModalLabel">Manage Leave Policies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="leavePolicyForm" action="{{ route('leave-policies.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="leavePolicyMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="typeid" class="form-label small lh-sm fw-semibold text-dark mb-1">Leave Type <span class="text-danger">*</span></label>
                                <select id="typeid" name="typeid" class="form-select" required>
                                    <option value="" disabled selected>Select Leave Type</option>
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->typeid }}">{{ $leaveType->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="leave_policy_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Policy Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="leave_policy_name" name="policy_name" placeholder="e.g. Annual Leave Policy" required>
                            </div>
                            
                            <div class="col-12 col-md-12">
                                <label for="leave_description" class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                <textarea class="form-control" id="leave_description" name="description" rows="2" placeholder="e.g. Standard annual leave policy..."></textarea>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="carry_forward_limit" class="form-label small lh-sm fw-semibold text-dark mb-1">Carry Forward Limit (Days) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="carry_forward_limit" name="carry_forward_limit" value="0" min="0" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="min_days_per_application" class="form-label small lh-sm fw-semibold text-dark mb-1">Min Days Per Application <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="min_days_per_application" name="min_days_per_application" value="1" min="0" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="max_days_per_application" class="form-label small lh-sm fw-semibold text-dark mb-1">Max Days Per Application <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="max_days_per_application" name="max_days_per_application" value="0" min="0" required>
                            </div>

                            <div class="col-12 col-md-12 mt-2">
                                <div class="mb-0 bg-white border rounded-1 px-2 py-1">
                                    <div class="form-check mb-0 form-check-large">
                                        <input type="hidden" name="is_paid" value="0">
                                        <input type="checkbox" name="is_paid" value="1" class="form-check-input" id="leave_policy_is_paid">
                                        <label class="form-check-label small lh-sm fw-normal text-dark mt-1 ms-1" style="cursor: pointer" for="leave_policy_is_paid">
                                            This is a Paid Leave
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-12 d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-light" id="btnCancelLeavePolicyEdit" style="display: none;">Cancel</button>
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSaveLeavePolicy">Save Policy <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span>Existing Leave Policies</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Policy Name</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="leavePoliciesList">
                                    <tr><td colspan="2" class="text-center py-3 text-muted small" id="leavePoliciesLoading">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leave Types Modal -->
<div class="modal fade" id="leaveTypesModal" tabindex="-1" aria-labelledby="leaveTypesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="leaveTypesModalLabel">Manage Leave Types</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="leaveTypeForm" action="{{ route('leave-types.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="leaveTypeMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-12">
                                <label for="leave_type_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Type Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="leave_type_name" name="name" placeholder="e.g. Annual Leave" required>
                            </div>
                            
                            <div class="col-12 col-md-12">
                                <label for="leave_type_description" class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                <textarea class="form-control" id="leave_type_description" name="description" rows="2" placeholder="e.g. Standard annual leave..."></textarea>
                            </div>

                            <div class="col-12 col-md-12 d-flex justify-content-end gap-2 mt-3">
                                <button type="button" class="btn btn-light" id="btnCancelLeaveTypeEdit" style="display: none;">Cancel</button>
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSaveLeaveType">Save Type <i class="fas fa-arrow-right btn-icon ms-1"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">
                        <span>Existing Leave Types</span>
                    </h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Type Name</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="leaveTypesList">
                                    <tr><td colspan="2" class="text-center py-3 text-muted small" id="leaveTypesLoading">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function editRole(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        document.getElementById('roleName').value = name;
        document.getElementById('roleForm').action = '{{ url("roles") }}/' + id;
        document.getElementById('roleMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('roleSubmitBtn').innerHTML = 'Update Role <i class="fas fa-arrow-right btn-icon ms-1"></i>';
    }

    function editDepartment(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        document.getElementById('departmentName').value = name;
        document.getElementById('departmentForm').action = '{{ url("departments") }}/' + id;
        document.getElementById('departmentMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('departmentSubmitBtn').innerHTML = 'Update Department <i class="fas fa-arrow-right btn-icon ms-1"></i>';
    }

    function resetRoleForm() {
        document.getElementById('roleName').value = '';
        document.getElementById('roleForm').action = '{{ route("roles.store") }}';
        document.getElementById('roleMethodField').innerHTML = '';
        document.getElementById('roleSubmitBtn').innerHTML = 'Save Role <i class="fas fa-arrow-right btn-icon ms-1"></i>';
    }

    function resetDepartmentForm() {
        document.getElementById('departmentName').value = '';
        document.getElementById('departmentForm').action = '{{ route("departments.store") }}';
        document.getElementById('departmentMethodField').innerHTML = '';
        document.getElementById('departmentSubmitBtn').innerHTML = 'Save Department <i class="fas fa-arrow-right btn-icon ms-1"></i>';
    }

    function buildRoleRow(roleObj, csrf) {
        let statusColor = roleObj.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white';
        let statusText = roleObj.status.charAt(0).toUpperCase() + roleObj.status.slice(1);
        return `<tr>
            <td><span class="d-block fw-semibold">${roleObj.name}</span></td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
                    <form method="POST" action="{{ url('roles') }}/${roleObj.roleid}/toggle" class="d-inline role-ajax-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="${statusColor}">${statusText}</button>
                    </form>
                    <button type="button" class="bg03 color03 border-0" onclick="editRole(this)" data-id="${roleObj.roleid}" data-name="${roleObj.name}">Edit</button>
                    <form method="POST" action="{{ url('roles') }}/${roleObj.roleid}" class="d-inline role-ajax-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="bg04 color04 border-0">Delete</button>
                    </form>
                </div>
            </td>
        </tr>`;
    }

    function buildDepartmentRow(dept, csrf) {
        let statusColor = dept.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white';
        let statusText = dept.status.charAt(0).toUpperCase() + dept.status.slice(1);
        return `<tr>
            <td><span class="d-block fw-semibold">${dept.name}</span></td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
                    <form method="POST" action="{{ url('departments') }}/${dept.depid}/toggle" class="d-inline dept-ajax-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="${statusColor}">${statusText}</button>
                    </form>
                <button type="button" class="bg03 color03 border-0" onclick="editDepartment(this)" data-id="${dept.depid}" data-name="${dept.name}">Edit</button>
                    <form method="POST" action="{{ url('departments') }}/${dept.depid}" class="d-inline dept-ajax-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="bg04 color04 border-0">Delete</button>
                    </form>
                </div>
            </td>
        </tr>`;
    }

    function refreshRolesTable(roles) {
        const tbody = document.querySelector('#rolesTable tbody');
        const csrf = document.querySelector('input[name="_token"]').value;
        if (roles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted">No roles found.</td></tr>';
        } else {
            tbody.innerHTML = roles.map(r => buildRoleRow(r, csrf)).join('');
        }
    }

    function refreshDepartmentsTable(departments) {
        const tbody = document.querySelector('#departmentsTable tbody');
        const csrf = document.querySelector('input[name="_token"]').value;
        if (departments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center py-4 text-muted">No departments found.</td></tr>';
        } else {
            tbody.innerHTML = departments.map(d => buildDepartmentRow(d, csrf)).join('');
        }
    }

    function handleAjaxSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const url = form.action;
        const formData = new FormData(form);
        const method = form.querySelector('input[name="_method"]')?.value || 'POST';
        if(method !== 'POST') formData.set('_method', method);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (res) {
            if (!res.ok) {
                if (res.status === 403) {
                    return res.json().then(function (data) { throw new Error(data.message || 'Unauthorized action.'); });
                }
                throw new Error('Server error');
            }
            return res.json();
        })
        .then(data => {
            if(data.success) {
                if(data.roles) { refreshRolesTable(data.roles); resetRoleForm(); }
                if(data.departments) { refreshDepartmentsTable(data.departments); resetDepartmentForm(); }
                if(window.showToast) window.showToast('success', data.message);
            } else {
                if(window.showToast) window.showToast('error', data.message || 'Something went wrong.');
            }
        }).catch(err => {
            if(window.showToast) window.showToast('error', err.message || 'Something went wrong.');
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('roleForm').addEventListener('submit', handleAjaxSubmit);
        document.getElementById('departmentForm').addEventListener('submit', handleAjaxSubmit);
        
        document.querySelector('#manageRolesModal').addEventListener('submit', (e) => {
            if(e.target.classList.contains('role-ajax-form')) handleAjaxSubmit(e);
        });
        document.querySelector('#manageDepartmentsModal').addEventListener('submit', (e) => {
            if(e.target.classList.contains('dept-ajax-form')) handleAjaxSubmit(e);
        });

        document.getElementById('manageRolesModal').addEventListener('hidden.bs.modal', resetRoleForm);
        document.getElementById('manageDepartmentsModal').addEventListener('hidden.bs.modal', resetDepartmentForm);

        // Attendance Policies Logic
        const policyModal = document.getElementById('attendancePoliciesModal');
        if(policyModal) {
            const policyForm = document.getElementById('attendancePolicyForm');
            const policyList = document.getElementById('attendancePoliciesList');
            const btnCancelEdit = document.getElementById('btnCancelPolicyEdit');
            const btnSave = document.getElementById('btnSavePolicy');
            const methodInput = document.getElementById('attendancePolicyMethod');
            
            let editingPolicyId = null;

            function loadPolicies() {
                policyList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">Loading...</td></tr>';
                
                fetch("{{ route('attendance-policies.index') }}", {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderPolicies(data.policies);
                    }
                })
                .catch(err => {
                    policyList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger small">Failed to load policies.</td></tr>';
                });
            }

            function renderPolicies(policies) {
                if (!policies || policies.length === 0) {
                    policyList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">No policies found.</td></tr>';
                    return;
                }
                
                let html = '';
                policies.forEach(policy => {
                    html += `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                        <span class="d-block position-absolute">${policy.policy_name.substring(0, 2).toUpperCase()}</span>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold">${policy.policy_name}</span>
                                    </div>
                                </div>
                            </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <button type="button" class="btn-toggle-policy badge ${policy.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white'} border-0" data-id="${policy.att_policyid}">
                                            ${policy.status.charAt(0).toUpperCase() + policy.status.slice(1)}
                                        </button>
                                    <button type="button" class="bg03 color03 border-0 btn-edit-policy" 
                                        data-id="${policy.att_policyid}" 
                                        data-policy='${JSON.stringify(policy).replace(/'/g, "&apos;")}'>
                                        Edit
                                    </button>
                                    <button type="button" class="bg04 color04 border-0 btn-delete-policy" data-id="${policy.att_policyid}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                policyList.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const policy = JSON.parse(this.dataset.policy);
                        editPolicy(policy);
                    });
                });
                
                document.querySelectorAll('.btn-toggle-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        togglePolicy(this.dataset.id);
                    });
                });
                
                document.querySelectorAll('.btn-delete-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deletePolicy(this.dataset.id);
                    });
                });
            }

            function resetPolicyForm() {
                policyForm.reset();
                policyForm.action = "{{ route('attendance-policies.store') }}";
                methodInput.value = 'POST';
                editingPolicyId = null;
                btnSave.innerHTML = 'Save Policy <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelEdit.style.display = 'none';
            }

            function editPolicy(policy) {
                editingPolicyId = policy.att_policyid;
                policyForm.action = `{{ route('attendance-policies.index') }}/${policy.att_policyid}`;
                methodInput.value = 'PUT';
                
                document.getElementById('policy_name').value = policy.policy_name;
                document.getElementById('description').value = policy.description || '';
                document.getElementById('late_arrival_grace').value = policy.late_arrival_grace;
                document.getElementById('early_departure_grace').value = policy.early_departure_grace;
                // document.getElementById('overtime_rate').value = policy.overtime_rate;
                
                btnSave.innerHTML = 'Update Policy <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelEdit.style.display = 'inline-block';
            }

            async function deletePolicy(id) {
                const confirmed = await window.appConfirm('Are you sure you want to delete this policy?');
                if (!confirmed) return;
                
                fetch(`{{ route('attendance-policies.index') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderPolicies(data.policies);
                        if (editingPolicyId == id) resetPolicyForm();
                    }
                });
            }
            
            function togglePolicy(id) {
                fetch(`{{ url('attendance-policies') }}/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderPolicies(data.policies);
                    }
                });
            }

            btnCancelEdit.addEventListener('click', resetPolicyForm);

            policyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = this.action;
                const method = methodInput.value;
                const plainFormData = Object.fromEntries(formData.entries());
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(plainFormData)
                })
                .then(async res => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        renderPolicies(data.policies);
                        resetPolicyForm();
                    } else {
                        alert(data.message || 'Validation error occurred.');
                    }
                })
                .catch(err => {
                    alert('An error occurred while saving.');
                });
            });

            policyModal.addEventListener('show.bs.modal', function() {
                resetPolicyForm();
                loadPolicies();
            });
            
            @if(session('open_policy_modal'))
                var bsModal = new bootstrap.Modal(policyModal);
                bsModal.show();
            @endif
        }

        // Shifts Logic
        const shiftModal = document.getElementById('shiftsModal');
        if (shiftModal) {
            const shiftForm = document.getElementById('shiftForm');
            const shiftList = document.getElementById('shiftsList');
            const btnCancelShiftEdit = document.getElementById('btnCancelShiftEdit');
            const btnSaveShift = document.getElementById('btnSaveShift');
            const shiftMethodInput = document.getElementById('shiftMethod');
            
            let editingShiftId = null;

            function loadShifts() {
                shiftList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">Loading...</td></tr>';
                
                fetch("{{ route('shifts.index') }}", {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderShifts(data.shifts);
                    }
                })
                .catch(err => {
                    shiftList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger small">Failed to load shifts.</td></tr>';
                });
            }

            function renderShifts(shifts) {
                if (!shifts || shifts.length === 0) {
                    shiftList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">No shifts found.</td></tr>';
                    return;
                }
                
                let html = '';
                shifts.forEach(shift => {
                    html += `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                        <span class="d-block position-absolute">${shift.shift_name.substring(0, 2).toUpperCase()}</span>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold">${shift.shift_name}</span>
                                        <span class="d-block text-muted small">${shift.start_time} - ${shift.end_time}</span>
                                    </div>
                                </div>
                            </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <button type="button" class="btn-toggle-shift badge ${shift.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white'} border-0" data-id="${shift.shiftid}">
                                            ${shift.status.charAt(0).toUpperCase() + shift.status.slice(1)}
                                        </button>
                                    <button type="button" class="bg03 color03 border-0 btn-edit-shift" 
                                        data-id="${shift.shiftid}" 
                                        data-shift='${JSON.stringify(shift).replace(/'/g, "&apos;")}'>
                                        Edit
                                    </button>
                                    <button type="button" class="bg04 color04 border-0 btn-delete-shift" data-id="${shift.shiftid}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                shiftList.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-shift').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const shift = JSON.parse(this.dataset.shift);
                        editShift(shift);
                    });
                });
                
                document.querySelectorAll('.btn-toggle-shift').forEach(btn => {
                    btn.addEventListener('click', function() {
                        toggleShift(this.dataset.id);
                    });
                });
                
                document.querySelectorAll('.btn-delete-shift').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteShift(this.dataset.id);
                    });
                });
            }

            function resetShiftForm() {
                shiftForm.reset();
                shiftForm.action = "{{ route('shifts.store') }}";
                shiftMethodInput.value = 'POST';
                editingShiftId = null;
                btnSaveShift.textContent = 'Save Shift';
                btnCancelShiftEdit.style.display = 'none';
            }

            function editShift(shift) {
                editingShiftId = shift.shiftid;
                shiftForm.action = `{{ route('shifts.index') }}/${shift.shiftid}`;
                shiftMethodInput.value = 'PUT';
                
                document.getElementById('shift_name').value = shift.shift_name;
                document.getElementById('start_time').value = shift.start_time;
                document.getElementById('end_time').value = shift.end_time;
                document.getElementById('break_duration').value = shift.break_duration;
                document.getElementById('break_start_time').value = shift.break_start_time || '';
                document.getElementById('break_end_time').value = shift.break_end_time || '';
                document.getElementById('break_grace_period').value = shift.break_grace_period;
                document.getElementById('shift_status').value = shift.status;
                
                btnSaveShift.textContent = 'Update Shift';
                btnCancelShiftEdit.style.display = 'inline-block';
            }

            async function deleteShift(id) {
                const confirmed = await window.appConfirm('Are you sure you want to delete this shift?');
                if (!confirmed) return;
                
                fetch(`{{ route('shifts.index') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderShifts(data.shifts);
                        if (editingShiftId == id) resetShiftForm();
                    }
                });
            }
            
            function toggleShift(id) {
                fetch(`{{ url('shifts') }}/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderShifts(data.shifts);
                    }
                });
            }

            btnCancelShiftEdit.addEventListener('click', resetShiftForm);

            shiftForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = this.action;
                const method = shiftMethodInput.value;
                const plainFormData = Object.fromEntries(formData.entries());
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(plainFormData)
                })
                .then(async res => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        renderShifts(data.shifts);
                        resetShiftForm();
                    } else {
                        alert(data.message || 'Validation error occurred.');
                    }
                })
                .catch(err => {
                    alert('An error occurred while saving.');
                });
            });

            shiftModal.addEventListener('show.bs.modal', function() {
                resetShiftForm();
                loadShifts();
            });
            
            @if(session('open_shift_modal'))
                var bsShiftModal = new bootstrap.Modal(shiftModal);
                bsShiftModal.show();
            @endif
        }

        // Leave Policies Logic
        const leavePolicyModal = document.getElementById('leavePoliciesModal');
        if (leavePolicyModal) {
            const leavePolicyForm = document.getElementById('leavePolicyForm');
            const leavePoliciesList = document.getElementById('leavePoliciesList');
            const btnCancelLeavePolicyEdit = document.getElementById('btnCancelLeavePolicyEdit');
            const btnSaveLeavePolicy = document.getElementById('btnSaveLeavePolicy');
            const leavePolicyMethodInput = document.getElementById('leavePolicyMethod');
            
            let editingLeavePolicyId = null;

            function loadLeavePolicies() {
                leavePoliciesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">Loading...</td></tr>';
                
                fetch("{{ route('leave-policies.index') }}", {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeavePolicies(data.policies);
                    }
                })
                .catch(err => {
                    leavePoliciesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger small">Failed to load leave policies.</td></tr>';
                });
            }

            function renderLeavePolicies(policies) {
                if (!policies || policies.length === 0) {
                    leavePoliciesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">No leave policies found.</td></tr>';
                    return;
                }
                
                let html = '';
                policies.forEach(policy => {
                    html += `
                        <tr>
                            <td>
                                <span class="d-block fw-semibold">${policy.policy_name}</span>
                                <span class="d-block text-muted small">
                                    <span class="badge bg-secondary-subtle text-secondary me-1">${policy.leave_type_name || 'N/A'}</span>
                                    <span class="badge ${policy.is_paid ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'} me-1">${policy.is_paid ? 'Paid' : 'Unpaid'}</span>
                                    <span class="text-truncate d-inline-block align-bottom" style="max-width: 200px;">${policy.description || 'No description'}</span>
                                </span>
                            </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <button type="button" class="btn-toggle-leave-policy badge ${policy.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white'} border-0" data-id="${policy.leave_policyid}">
                                            ${policy.status.charAt(0).toUpperCase() + policy.status.slice(1)}
                                        </button>
                                    <button type="button" class="bg03 color03 border-0 btn-edit-leave-policy" 
                                        data-id="${policy.leave_policyid}" 
                                        data-policy='${JSON.stringify(policy).replace(/'/g, "&apos;")}'>
                                        Edit
                                    </button>
                                    <button type="button" class="bg04 color04 border-0 btn-delete-leave-policy" data-id="${policy.leave_policyid}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                leavePoliciesList.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-leave-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const policy = JSON.parse(this.dataset.policy);
                        editLeavePolicy(policy);
                    });
                });
                
                document.querySelectorAll('.btn-toggle-leave-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        toggleLeavePolicy(this.dataset.id);
                    });
                });
                
                document.querySelectorAll('.btn-delete-leave-policy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteLeavePolicy(this.dataset.id);
                    });
                });
            }

            function resetLeavePolicyForm() {
                leavePolicyForm.reset();
                leavePolicyForm.action = "{{ route('leave-policies.store') }}";
                leavePolicyMethodInput.value = 'POST';
                editingLeavePolicyId = null;
                btnSaveLeavePolicy.innerHTML = 'Save Policy <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelLeavePolicyEdit.style.display = 'none';
            }

            function editLeavePolicy(policy) {
                editingLeavePolicyId = policy.leave_policyid;
                leavePolicyForm.action = `{{ route('leave-policies.index') }}/${policy.leave_policyid}`;
                leavePolicyMethodInput.value = 'PUT';
                
                document.getElementById('typeid').value = policy.typeid;
                document.getElementById('leave_policy_name').value = policy.policy_name;
                document.getElementById('leave_description').value = policy.description || '';
                document.getElementById('carry_forward_limit').value = policy.carry_forward_limit;
                document.getElementById('min_days_per_application').value = policy.min_days_per_application;
                document.getElementById('max_days_per_application').value = policy.max_days_per_application;
                document.getElementById('leave_policy_is_paid').checked = policy.is_paid ? true : false;
                
                if (document.getElementById('leave_policy_status')) {
                    document.getElementById('leave_policy_status').value = policy.status;
                }
                
                btnSaveLeavePolicy.innerHTML = 'Update Policy <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelLeavePolicyEdit.style.display = 'inline-block';
            }

            async function deleteLeavePolicy(id) {
                const confirmed = await window.appConfirm('Are you sure you want to delete this leave policy?');
                if (!confirmed) return;
                
                fetch(`{{ route('leave-policies.index') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeavePolicies(data.policies);
                        if (editingLeavePolicyId == id) resetLeavePolicyForm();
                    }
                });
            }
            
            function toggleLeavePolicy(id) {
                fetch(`{{ url('leave-policies') }}/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeavePolicies(data.policies);
                    }
                });
            }

            btnCancelLeavePolicyEdit.addEventListener('click', resetLeavePolicyForm);

            leavePolicyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = this.action;
                const method = leavePolicyMethodInput.value;
                const plainFormData = Object.fromEntries(formData.entries());
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(plainFormData)
                })
                .then(async res => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        renderLeavePolicies(data.policies);
                        resetLeavePolicyForm();
                    } else {
                        alert(data.message || 'Validation error occurred.');
                    }
                })
                .catch(err => {
                    alert('An error occurred while saving.');
                });
            });

            leavePolicyModal.addEventListener('show.bs.modal', function() {
                resetLeavePolicyForm();
                loadLeavePolicies();
            });
            
            @if(session('open_leave_policy_modal'))
                var bsLeavePolicyModal = new bootstrap.Modal(leavePolicyModal);
                bsLeavePolicyModal.show();
            @endif
        }

        // Leave Types Logic
        const leaveTypeModal = document.getElementById('leaveTypesModal');
        if (leaveTypeModal) {
            const leaveTypeForm = document.getElementById('leaveTypeForm');
            const leaveTypesList = document.getElementById('leaveTypesList');
            const btnCancelLeaveTypeEdit = document.getElementById('btnCancelLeaveTypeEdit');
            const btnSaveLeaveType = document.getElementById('btnSaveLeaveType');
            const leaveTypeMethodInput = document.getElementById('leaveTypeMethod');
            
            let editingLeaveTypeId = null;

            function loadLeaveTypes() {
                leaveTypesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">Loading...</td></tr>';
                
                fetch("{{ route('leave-types.index') }}", {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeaveTypes(data.leaveTypes);
                    }
                })
                .catch(err => {
                    leaveTypesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-danger small">Failed to load leave types.</td></tr>';
                });
            }

            function renderLeaveTypes(types) {
                if (!types || types.length === 0) {
                    leaveTypesList.innerHTML = '<tr><td colspan="2" class="text-center py-3 text-muted small">No leave types found.</td></tr>';
                    return;
                }
                
                let html = '';
                types.forEach(type => {
                    html += `
                        <tr>
                            <td>
                                <span class="d-block fw-semibold">${type.name}</span>
                                <span class="d-block text-muted small text-truncate" style="max-width: 200px;">${type.description || 'No description'}</span>
                            </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1">
                                        <button type="button" class="btn-toggle-leave-type badge ${type.status === 'active' ? 'bg02 color02' : 'bg-secondary text-white'} border-0" data-id="${type.typeid}">
                                            ${type.status.charAt(0).toUpperCase() + type.status.slice(1)}
                                        </button>
                                    <button type="button" class="bg03 color03 border-0 btn-edit-leave-type" 
                                        data-id="${type.typeid}" 
                                        data-type='${JSON.stringify(type).replace(/'/g, "&apos;")}'>
                                        Edit
                                    </button>
                                    <button type="button" class="bg04 color04 border-0 btn-delete-leave-type" data-id="${type.typeid}">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                leaveTypesList.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-leave-type').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const type = JSON.parse(this.dataset.type);
                        editLeaveType(type);
                    });
                });
                
                document.querySelectorAll('.btn-toggle-leave-type').forEach(btn => {
                    btn.addEventListener('click', function() {
                        toggleLeaveType(this.dataset.id);
                    });
                });
                
                document.querySelectorAll('.btn-delete-leave-type').forEach(btn => {
                    btn.addEventListener('click', function() {
                        deleteLeaveType(this.dataset.id);
                    });
                });
            }

            function resetLeaveTypeForm() {
                leaveTypeForm.reset();
                leaveTypeForm.action = "{{ route('leave-types.store') }}";
                leaveTypeMethodInput.value = 'POST';
                editingLeaveTypeId = null;
                btnSaveLeaveType.innerHTML = 'Save Type <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelLeaveTypeEdit.style.display = 'none';
            }

            function editLeaveType(type) {
                editingLeaveTypeId = type.typeid;
                leaveTypeForm.action = `{{ route('leave-types.index') }}/${type.typeid}`;
                leaveTypeMethodInput.value = 'PUT';
                
                document.getElementById('leave_type_name').value = type.name;
                document.getElementById('leave_type_description').value = type.description || '';
                
                btnSaveLeaveType.innerHTML = 'Update Type <i class="fas fa-arrow-right btn-icon ms-1"></i>';
                btnCancelLeaveTypeEdit.style.display = 'inline-block';
            }

            async function deleteLeaveType(id) {
                const confirmed = await window.appConfirm('Are you sure you want to delete this leave type?');
                if (!confirmed) return;
                
                fetch(`{{ route('leave-types.index') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeaveTypes(data.leaveTypes);
                        if (editingLeaveTypeId == id) resetLeaveTypeForm();
                    }
                });
            }
            
            function toggleLeaveType(id) {
                fetch(`{{ url('leave-types') }}/${id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderLeaveTypes(data.leaveTypes);
                    }
                });
            }

            btnCancelLeaveTypeEdit.addEventListener('click', resetLeaveTypeForm);

            leaveTypeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const url = this.action;
                const method = leaveTypeMethodInput.value;
                const plainFormData = Object.fromEntries(formData.entries());
                
                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(plainFormData)
                })
                .then(async res => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        renderLeaveTypes(data.leaveTypes);
                        resetLeaveTypeForm();
                        
                        // If leave policy modal also has the select box, we should technically reload it
                        // or add the new option, but reloading the page on close is safer if it affects other things.
                    } else {
                        alert(data.message || 'Validation error occurred.');
                    }
                })
                .catch(err => {
                    alert('An error occurred while saving.');
                });
            });

            leaveTypeModal.addEventListener('show.bs.modal', function() {
                resetLeaveTypeForm();
                loadLeaveTypes();
            });
            
            @if(session('open_leave_type_modal'))
                var bsLeaveTypeModal = new bootstrap.Modal(leaveTypeModal);
                bsLeaveTypeModal.show();
            @endif
        }
    });
</script>
@endsection
