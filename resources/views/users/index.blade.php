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
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#componentsModal">
        <i class="fas fa-coins btn-icon"></i> Payroll Components
    </button>
    <button type="button" class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#policiesModal">
        <i class="fas fa-file-contract btn-icon"></i> Account Policies
    </button>


    @if(auth()->check() && auth()->user()->account?->has_team_management)
    <a href="{{ route('users.approvals') }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium position-relative">
        <i class="fas fa-user-check btn-icon"></i> Profile Approvals
        @php
            $pendingCount = \App\Models\UserProfile::where('status', 'pending')
                ->where('accountid', auth()->user()->accountid)
                ->count();
        @endphp
        @if($pendingCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
            {{ $pendingCount }}
        </span>
        @endif
    </a>
    <a href="{{ route('users.leaves.index') }}"
        class="btn btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium position-relative">
        <i class="fas fa-calendar-check btn-icon"></i> Leave Approvals
        @php
            $assignedUserids = \Illuminate\Support\Facades\DB::table('user_assignments')
                ->pluck('assigned_userid')
                ->unique()
                ->values()
                ->all();
            
            $unassignedUserids = \App\Models\User::where('accountid', auth()->user()->accountid)
                ->whereNotIn('userid', $assignedUserids)
                ->pluck('userid')
                ->all();

            $pendingLeavesCount = \App\Models\LeaveRequest::where('accountid', auth()->user()->accountid)
                ->whereIn('userid', $unassignedUserids)
                ->where('status', 'pending')
                ->count();
        @endphp
        @if($pendingLeavesCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
            {{ $pendingLeavesCount }}
        </span>
        @endif
    </a>
    @endif
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
         <div class="d-flex align-items-center gap-2">
            <a href="{{ route('users.create') }}"
                class="btn btn-sm btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
                <i class="fas fa-plus btn-icon"></i> Add Team
            </a>
        </div>
    </div>

    <!-- Users List View (Table View) -->
    <div id="users-list-view" class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Team Member</th>
                        <th>Department</th>

                        <th>Role</th>
                        <th>Assigned Users</th>
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
                                        <span class="d-none position-absolute">{{ strtoupper(substr($member->name ?? 'U', 0, 2)) }}</span>
                                        <img src="{{ asset('storage/' . $member->profile_image) }}" 
                                             onerror="this.style.display='none'; this.previousElementSibling.classList.replace('d-none', 'd-block');"
                                             alt="{{ $member->name }}" 
                                             class="position-absolute rounded-circle bg-white" 
                                             style="width:40px;height:40px;object-fit:cover;top:0;left:0;border:2px solid #fff;">
                                    @else
                                        <span class="d-block position-absolute">{{ strtoupper(substr($member->name ?? 'U', 0, 2)) }}</span>
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
                            @if($member->teamMembers->isNotEmpty())
                                @php
                                    $assignedNames = $member->teamMembers->pluck('name');
                                    $count = $assignedNames->count();
                                    $firstTeam = $member->teamMembers->first()->pivot->team_name ?? 'My Team';
                                @endphp
                                <div class="d-flex flex-column" data-bs-toggle="tooltip" title="{{ $assignedNames->implode(', ') }}">
                                    <span class="text-dark fw-semibold small mb-1">{{ $firstTeam }}</span>
                                    <div>
                                        <span class="badge bg-primary text-white px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-people-fill me-1"></i> {{ $count }} Member{{ $count > 1 ? 's' : '' }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
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
                                @if(auth()->user()->hasPermission('users.edit') && count($allUsersMap) > 1)
                                <button type="button" class="bg01 color01 border-0" onclick="openAssignTeamModal('{{ $member->userid }}', '{{ addslashes($member->name) }}')" title="Assign Team">Assign Team</button>
                                @endif
                                @if(auth()->user()->hasPermission('users.edit'))
                                <a href="{{ route('users.edit', $member) }}" class="bg03 color03" title="Edit Team">Edit</a>
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
                            <p class="fw-semibold text-dark mb-1">No team members found</p>
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
                            <div class="col-12 col-md-5">
                                <label for="roleName" class="form-label small lh-sm fw-semibold text-dark mb-1">Role Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="roleName" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="roleLevelId" class="form-label small lh-sm fw-semibold text-dark mb-1">Role Level</label>
                                <select name="levelid" id="roleLevelId" class="form-select">
                                    <option value="">No Level</option>
                                    @foreach($roleLevels ?? [] as $rl)
                                        <option value="{{ $rl->levelid }}">{{ $rl->level_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-3 d-flex">
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
                                        <th width="40%">Role Name</th>
                                        <th width="30%">Level</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $roleObj)
                                    <tr>
                                        <td><span class="d-block fw-semibold">{{ $roleObj->name }}</span></td>
                                        <td><span class="badge bg-light text-dark border">{{ $roleObj->roleLevel ? $roleObj->roleLevel->level_name : 'None' }}</span></td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <form method="POST" action="{{ route('roles.toggle-status', $roleObj->roleid) }}" class="d-inline role-ajax-form">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="{{ $roleObj->status === 'active' ? 'bg02 color02' : 'bg-secondary text-white' }}">
                                                        {{ ucfirst($roleObj->status) }}
                                                    </button>
                                                </form>
                                                <button type="button" class="bg03 color03 border-0" onclick="editRole(this)" data-id="{{ $roleObj->roleid }}" data-name="{{ $roleObj->name }}" data-levelid="{{ $roleObj->levelid }}">Edit</button>
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

<!-- Payroll Components Modal -->
<div class="modal fade" id="componentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold">Manage Payroll Components</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="componentForm" action="{{ route('payroll-components.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="componentMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Component Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="comp_name" name="name" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Category Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="comp_category_type" name="category_type" required>
                                    <option value="attendance">Attendance</option>
                                    <option value="leave">Leave</option>
                                    <option value="security_deposit">Security Deposit</option>
                                    <option value="general_earning">General Earning</option>
                                    <option value="general_deduction">General Deduction</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6" id="comp_type_col">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="comp_type" name="type" required>
                                    <option value="earning">Earning</option>
                                    <option value="deduction">Deduction</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6" id="comp_calc_type_col">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Calculation Type</label>
                                <select class="form-select" id="comp_calculation_type" name="calculation_type">
                                    <option value="">Select Type</option>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4" id="calc_value_container" style="display: none;">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1" id="calc_value_label">Value</label>
                                <input type="number" step="0.01" class="form-control" id="comp_calculation_value" name="calculation_value">
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                <textarea class="form-control" id="comp_description" name="description" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" id="btnCancelCompEdit" style="display: none;">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSaveComp">Save Component</button>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">Existing Components</h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Name</th>
                                        <th width="30%">Type</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="componentsList"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Policies Modal -->
<div class="modal fade" id="policiesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold">Manage Account Policies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-3">
                    <form id="policyForm" action="{{ route('account-policies.store') }}" method="POST" class="mainForm">
                        @csrf
                        <input type="hidden" name="_method" value="POST" id="policyMethod">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Policy Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pol_title" name="title" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Policy Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="pol_componentid" name="componentid" required>
                                    <option value="">Select a Policy Type...</option>
                                    @foreach($payrollComponents as $comp)
                                        <option value="{{ $comp->componentid }}">{{ $comp->name }} ({{ $comp->type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Description</label>
                                <textarea class="form-control" id="pol_description" name="description" rows="2"></textarea>
                            </div>
                            <!-- Dynamic Rules Builder -->
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Policy Rules <span class="text-muted fw-normal">(Optional)</span></label>
                                <div id="rulesBuilderContainer" class="d-flex flex-column gap-2 mb-2">
                                    <!-- Rules will be appended here -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary bg-white text-primary d-inline-flex align-items-center gap-1 fw-medium" id="btnAddRule">
                                    <i class="fas fa-plus btn-icon"></i> Add Rule
                                </button>
                                <input type="hidden" id="pol_rules_hidden" name="rules">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" id="btnCancelPolicyEdit" style="display: none;">Cancel</button>
                            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSavePolicy">Save Policy</button>
                        </div>
                    </form>
                </div>
                
                <div class="position-relative bg-DarkLight p-2 rounded-3" style="max-height: 300px; overflow-y: auto;">
                    <h6 class="fw-semibold text-dark mb-2 px-1">Existing Policies</h6>
                    <div class="card border-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-striped mainTable border align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="45%">Title</th>
                                        <th width="35%">Component</th>
                                        <th class="text-end" width="20%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="policiesList"></tbody>
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



<script>
    function editRole(btn) {
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        const levelid = btn.getAttribute('data-levelid') || '';
        document.getElementById('roleName').value = name;
        document.getElementById('roleLevelId').value = levelid;
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
        document.getElementById('roleLevelId').value = '';
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
        let levelText = (roleObj.role_level && roleObj.role_level.level_name) ? roleObj.role_level.level_name : 'None';
        let levelId = roleObj.levelid || '';
        
        return `<tr>
            <td><span class="d-block fw-semibold">${roleObj.name}</span></td>
            <td><span class="badge bg-light text-dark border">${levelText}</span></td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
                    <form method="POST" action="{{ url('roles') }}/${roleObj.roleid}/toggle" class="d-inline role-ajax-form">
                        <input type="hidden" name="_token" value="${csrf}">
                        <input type="hidden" name="_method" value="PATCH">
                        <button type="submit" class="${statusColor}">${statusText}</button>
                    </form>
                    <button type="button" class="bg03 color03 border-0" onclick="editRole(this)" data-id="${roleObj.roleid}" data-name="${roleObj.name}" data-levelid="${levelId}">Edit</button>
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
                const componentsModal = document.getElementById('componentsModal');
        if (componentsModal) {
            const form = document.getElementById('componentForm');
            const methodInput = document.getElementById('componentMethod');
            const list = document.getElementById('componentsList');
            const btnSave = document.getElementById('btnSaveComp');
            const btnCancel = document.getElementById('btnCancelCompEdit');
            let editingId = null;

            function loadComponents() {
                fetch("{{ route('payroll-components.index') }}", {
                    headers: { 'Accept': 'application/json' }
                }).then(res => res.json()).then(data => {
                    if (data.success) renderComponents(data.components);
                });
            }

            function renderComponents(comps) {
                let html = '';
                comps.forEach(c => {
                    html += `<tr>
                        <td><span class="d-block fw-semibold">${c.name}</span></td>
                        <td>${c.type} ${c.calculation_type ? '(' + c.calculation_type + ')' : ''} <br> <small class="text-muted">${c.calculation_value ? (c.calculation_type === 'percentage' ? c.calculation_value + '%' : '₹' + c.calculation_value) : ''}</small></td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <button type="button" class="bg03 color03 border-0 btn-edit-comp" data-comp='${JSON.stringify(c).replace(/'/g, "&apos;")}'>Edit</button>
                                <button type="button" class="bg04 color04 border-0 btn-delete-comp" data-id="${c.componentid}">Delete</button>
                            </div>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-comp').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const c = JSON.parse(this.dataset.comp);
                        editingId = c.componentid;
                        form.action = `{{ route('payroll-components.index') }}/${c.componentid}`;
                        methodInput.value = 'PUT';
                        document.getElementById('comp_name').value = c.name;
                        document.getElementById('comp_category_type').value = c.category_type;
                        document.getElementById('comp_type').value = c.type;
                        document.getElementById('comp_calculation_type').value = c.calculation_type;
                        document.getElementById('comp_calculation_value').value = c.calculation_value || '';
                        document.getElementById('comp_description').value = c.description || '';
                        
                        // trigger change to show/hide value field
                        document.getElementById('comp_calculation_type').dispatchEvent(new Event('change'));
                        
                        btnSave.textContent = 'Update Component';
                        btnCancel.style.display = 'inline-block';
                    });
                });
                
                document.querySelectorAll('.btn-delete-comp').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        if (!await window.appConfirm('Delete this component?')) return;
                        fetch(`{{ route('payroll-components.index') }}/${this.dataset.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        }).then(res => res.json()).then(data => {
                            if (data.success) renderComponents(data.components);
                        });
                    });
                });
            }

            function resetForm() {
                editingId = null;
                form.action = "{{ route('payroll-components.store') }}";
                methodInput.value = 'POST';
                form.reset();
                document.getElementById('comp_calculation_type').dispatchEvent(new Event('change'));
                btnSave.textContent = 'Save Component';
                btnCancel.style.display = 'none';
            }
            
            document.getElementById('comp_calculation_type').addEventListener('change', function() {
                const container = document.getElementById('calc_value_container');
                const label = document.getElementById('calc_value_label');
                const typeCol = document.getElementById('comp_type_col');
                const calcTypeCol = document.getElementById('comp_calc_type_col');
                const val = this.value;
                if (val === 'fixed') {
                    container.style.display = 'block';
                    label.textContent = 'Fixed Amount';
                    typeCol.className = 'col-12 col-md-4';
                    calcTypeCol.className = 'col-12 col-md-4';
                } else if (val === 'percentage') {
                    container.style.display = 'block';
                    label.textContent = 'Percentage Value (%)';
                    typeCol.className = 'col-12 col-md-4';
                    calcTypeCol.className = 'col-12 col-md-4';
                } else {
                    container.style.display = 'none';
                    typeCol.className = 'col-12 col-md-6';
                    calcTypeCol.className = 'col-12 col-md-6';
                }
            });

            btnCancel.addEventListener('click', resetForm);
            componentsModal.addEventListener('show.bs.modal', function() { resetForm(); loadComponents(); });
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch(this.action, {
                    method: methodInput.value,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(Object.fromEntries(new FormData(this)))
                }).then(res => res.json()).then(data => {
                    if (data.success) { renderComponents(data.components); resetForm(); }
                    else { alert(data.message || 'Error'); }
                });
            });
        }

        const policiesModal = document.getElementById('policiesModal');
        if (policiesModal) {
            const form = document.getElementById('policyForm');
            const methodInput = document.getElementById('policyMethod');
            const list = document.getElementById('policiesList');
            const btnSavePol = document.getElementById('btnSavePolicy');
            const btnCancel = document.getElementById('btnCancelPolicyEdit');
            const compSelect = document.getElementById('pol_componentid');
            const rulesContainer = document.getElementById('rulesBuilderContainer');
            const btnAddRule = document.getElementById('btnAddRule');
            const rulesHidden = document.getElementById('pol_rules_hidden');
            let editingId = null;

            function createRuleRow(key = '', value = '') {
                const row = document.createElement('div');
                row.className = 'd-flex gap-2 align-items-center rule-row';
                
                // Define all available predefined rules
                const ruleOptions = [
                    { value: '', label: 'Select a Rule...' },
                    { group: 'Attendance Rules' },
                    { value: 'late_grace_mins', label: 'Late Grace Minutes' },
                    { value: 'half_day_late_mins', label: 'Half-Day Late Minutes' },
                    { value: 'early_leave_grace_mins', label: 'Early Leave Grace Minutes' },
                    { value: 'deduction_per_late', label: 'Deduction per Late Entry' },
                    { value: 'min_hours_full_day', label: 'Min Hours for Full Day' },
                    { value: 'min_hours_half_day', label: 'Min Hours for Half Day' },
                    { value: 'overtime_multiplier', label: 'Overtime Multiplier (e.g., 1.5)' },
                    { group: 'Leave Rules' },
                    { value: 'probation_months', label: 'Probation Months (No Leave Period)' },
                    { value: 'leave_carry_forward', label: 'Max Leave Carry Forward' },
                    { value: 'max_leaves_per_month', label: 'Max Leaves Per Month' },
                    { group: 'Security Deposit Rules' },
                    { value: 'deduction_months', label: 'Number of Months for Deduction' },
                    { value: 'refundable_after_months', label: 'Refundable After X Months' },
                    { group: 'General Rules' },
                    { value: 'custom', label: 'Custom Rule (Advanced)' }
                ];

                let optionsHtml = '';
                ruleOptions.forEach(opt => {
                    if (opt.group) {
                        optionsHtml += `<optgroup label="${opt.group}"></optgroup>`;
                    } else {
                        const selected = key === opt.value ? 'selected' : '';
                        optionsHtml += `<option value="${opt.value}" ${selected}>${opt.label}</option>`;
                    }
                });

                // If a key was provided that isn't in our predefined list, we need to add it so it doesn't get lost
                const keyExists = ruleOptions.some(opt => opt.value === key);
                if (key && !keyExists) {
                    optionsHtml += `<option value="${key}" selected>${key} (Legacy/Custom)</option>`;
                }

                row.innerHTML = `
                    <select class="form-select form-select-sm rule-key" style="flex: 2;">
                        ${optionsHtml}
                    </select>
                    <input type="text" class="form-control form-control-sm rule-val" placeholder="Value (e.g. 15)" value="${value}" style="flex: 1;">
                    <button type="button" class="btn btn-sm btn-danger btn-remove-rule"><i class="fas fa-times"></i></button>
                `;
                row.querySelector('.btn-remove-rule').addEventListener('click', function() { row.remove(); });
                return row;
            }

            btnAddRule.addEventListener('click', function() {
                rulesContainer.appendChild(createRuleRow());
            });
            function loadPolicies() {
                fetch("{{ route('account-policies.index') }}", { headers: { 'Accept': 'application/json' } })
                .then(res => res.json()).then(data => {
                    if (data.success) renderPolicies(data.policies);
                });
            }

            function renderPolicies(policies) {
                let html = '';
                policies.forEach(p => {
                    html += `<tr>
                        <td><span class="d-block fw-semibold">${p.title}</span></td>
                        <td>${p.component ? p.component.name : '-'}</td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1">
                                <button type="button" class="bg03 color03 border-0 btn-edit-pol" data-pol='${JSON.stringify(p).replace(/'/g, "&apos;")}'>Edit</button>
                                <button type="button" class="bg04 color04 border-0 btn-delete-pol" data-id="${p.policyid}">Delete</button>
                            </div>
                        </td>
                    </tr>`;
                });
                list.innerHTML = html;
                
                document.querySelectorAll('.btn-edit-pol').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const p = JSON.parse(this.dataset.pol);
                        editingId = p.policyid;
                        editingPolId = p.policyid;
                        policyForm.action = `{{ route('account-policies.index') }}/${p.policyid}`;
                        methodInput.value = 'PUT';
                        document.getElementById('pol_title').value = p.title;
                        compSelect.dataset.pendingVal = p.componentid;
                        compSelect.value = p.componentid;
                        document.getElementById('pol_description').value = p.description || '';
                        
                        rulesContainer.innerHTML = '';
                        if (p.rules) {
                            try {
                                const rulesObj = typeof p.rules === 'string' ? JSON.parse(p.rules) : p.rules;
                                Object.entries(rulesObj).forEach(([k, v]) => {
                                    rulesContainer.appendChild(createRuleRow(k, v));
                                });
                            } catch (e) {}
                        }
                        
                        btnSavePol.textContent = 'Update Policy';
                        btnCancel.style.display = 'inline-block';
                    });
                });
                
                document.querySelectorAll('.btn-delete-pol').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        if (!await window.appConfirm('Delete this policy?')) return;
                        fetch(`{{ route('account-policies.index') }}/${this.dataset.id}`, {
                            method: 'DELETE',
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        }).then(res => res.json()).then(data => {
                            if (data.success) renderPolicies(data.policies);
                        });
                    });
                });
            }

            function resetForm() {
                editingPolId = null;
                policyForm.action = "{{ route('account-policies.store') }}";
                methodInput.value = 'POST';
                policyForm.reset();
                compSelect.dataset.pendingVal = '';
                rulesContainer.innerHTML = ''; // Clear dynamic rules
                btnSavePol.textContent = 'Save Policy';
                btnCancel.style.display = 'none';
            }

            btnCancel.addEventListener('click', resetForm);
            policiesModal.addEventListener('show.bs.modal', function() { resetForm(); loadPolicies(); });
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                let formData = Object.fromEntries(new FormData(this));
                
                const rulesObj = {};
                rulesContainer.querySelectorAll('.rule-row').forEach(row => {
                    const k = row.querySelector('.rule-key').value.trim();
                    const v = row.querySelector('.rule-val').value.trim();
                    if (k) rulesObj[k] = isNaN(v) || v === '' ? v : Number(v);
                });
                
                if (Object.keys(rulesObj).length > 0) {
                    formData.rules = rulesObj; // We can send it as an object directly
                } else {
                    formData.rules = null;
                }

                fetch(this.action, {
                    method: methodInput.value,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(formData)
                }).then(res => res.json()).then(data => {
                    if (data.success) { renderPolicies(data.policies); resetForm(); }
                    else { alert(data.message || 'Error'); }
                });
            });
        }
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

    });
</script>

<!-- Assign Team Modal -->
<div class="modal fade" id="assignTeamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-DarkLight py-2 border-0">
                <h5 class="modal-title fw-semibold" id="assignTeamModalTitle">Assign Team</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white p-2">
                <div class="bg-DarkLight p-2 rounded-3 mb-2">
                    <form id="assignTeamForm" class="mainForm">
                        @csrf
                        <input type="hidden" id="assign_team_manager_id" name="manager_id">
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Team Name</label>
                                <input type="text" id="assign_team_name" name="team_name" class="form-control" placeholder="Enter team name">
                            </div>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Select Employees</label>
                                <div class="bg-white border rounded px-2 py-2" style="max-height: 200px; overflow-y: auto;" id="assign_team_users_list">
                                    <!-- Populated via Ajax -->
                                    <div class="text-center text-muted small py-2">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium" id="btnSaveTeamAssignments">
                                Save Team <i class="fas fa-arrow-right btn-icon ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentAssignManagerId = null;

    function openAssignTeamModal(userId, userName) {
        currentAssignManagerId = userId;
        document.getElementById('assignTeamModalTitle').innerText = 'Assign Team for ' + userName;
        document.getElementById('assign_team_manager_id').value = userId;
        
        const listContainer = document.getElementById('assign_team_users_list');
        listContainer.innerHTML = '<div class="text-center text-muted small py-2">Loading...</div>';
        
        const modal = new bootstrap.Modal(document.getElementById('assignTeamModal'));
        modal.show();
        
        // Fetch assignable users
        let getUrl = '{{ route("users.assignments.get", ["user" => "__USERID__"]) }}'.replace('__USERID__', userId);
        fetch(getUrl)
            .then(res => res.json())
            .then(data => {
                document.getElementById('assign_team_name').value = data.teamName || '';
                
                if (data.allUsers.length === 0) {
                    listContainer.innerHTML = '<div class="text-center text-muted small py-2">No users available to assign.</div>';
                    return;
                }
                
                let html = '<div class="row g-2">';
                data.allUsers.forEach(u => {
                    const checked = data.assignedUserIds.includes(u.userid) ? 'checked' : '';
                    html += `
                        <div class="col-12 col-md-6">
                            <div class="form-check mb-0 form-check-large">
                                <input class="form-check-input border-primary border-2" type="checkbox" name="assigned_users[]" value="${u.userid}" id="assign_tu_${u.userid}" ${checked}>
                                <label class="form-check-label small lh-sm fw-normal text-dark" for="assign_tu_${u.userid}">
                                    <strong>${u.name}</strong> <span class="text-muted">(${u.role_name})</span>
                                </label>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                listContainer.innerHTML = html;
            })
            .catch(err => {
                listContainer.innerHTML = '<div class="text-center text-danger small py-2">Failed to load data.</div>';
            });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const btnSave = document.getElementById('btnSaveTeamAssignments');
        if (btnSave) {
            btnSave.addEventListener('click', function() {
                if (!currentAssignManagerId) return;
                
                btnSave.disabled = true;
                btnSave.innerText = 'Saving...';
                
                const form = document.getElementById('assignTeamForm');
                const formData = new FormData(form);
                
                let postUrl = '{{ route("users.assignments.update", ["user" => "__USERID__"]) }}'.replace('__USERID__', currentAssignManagerId);
                fetch(postUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Failed to save assignments.');
                        btnSave.disabled = false;
                        btnSave.innerText = 'Save Changes';
                    }
                })
                .catch(err => {
                    alert('An error occurred.');
                    btnSave.disabled = false;
                    btnSave.innerText = 'Save Changes';
                });
            });
        }
    });
</script>
@endsection
