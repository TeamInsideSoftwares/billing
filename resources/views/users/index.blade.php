@extends('layouts.app')

@section('header_actions')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#manageRolesModal">
        <i class="fas fa-user-shield btn-icon"></i> Manage Roles
    </button>
    <button type="button" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 fw-medium" data-bs-toggle="modal" data-bs-target="#manageDepartmentsModal">
        <i class="fas fa-sitemap btn-icon"></i> Manage Departments
    </button>
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
                            <span class="badge bg-light text-dark border">{{ is_array($member->permissions) ? count($member->permissions) : 0 }}</span>
                        </td>
                        <td class="text-end">
                            <div class="tableActionButton d-inline-flex gap-1 align-items-center">
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
                                <!-- <a href="#" class="bg01 color01" title="Login As User">Login As</a>  -->
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
                                        <th width="20%">Status</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($roles as $roleObj)
                                    <tr>
                                        <td><span class="d-block fw-semibold">{{ $roleObj->name }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('roles.toggle-status', $roleObj->roleid) }}" class="d-inline role-ajax-form">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 {{ $roleObj->status === 'active' ? 'text-success' : 'text-danger' }}">
                                                    {{ ucfirst($roleObj->status) }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <button type="button" class="bg03 color03 border-0" onclick="editRole(this)" data-id="{{ $roleObj->roleid }}" data-name="{{ $roleObj->name }}">Edit</button>
                                                <form method="POST" action="{{ route('roles.destroy', $roleObj->roleid) }}" class="d-inline role-ajax-form">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No roles found.</td></tr>
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
                                        <th width="20%">Status</th>
                                        <th class="text-end" width="30%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($departments as $dept)
                                    <tr>
                                        <td><span class="d-block fw-semibold">{{ $dept->name }}</span></td>
                                        <td>
                                            <form method="POST" action="{{ route('departments.toggle-status', $dept->depid) }}" class="d-inline dept-ajax-form">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 {{ $dept->status === 'active' ? 'text-success' : 'text-danger' }}">
                                                    {{ ucfirst($dept->status) }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="tableActionButton d-inline-flex gap-1">
                                                <button type="button" class="bg03 color03 border-0" onclick="editDepartment(this)" data-id="{{ $dept->depid }}" data-name="{{ $dept->name }}">Edit</button>
                                                <form method="POST" action="{{ route('departments.destroy', $dept->depid) }}" class="d-inline dept-ajax-form">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="bg04 color04 border-0">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No departments found.</td></tr>
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
        let statusColor = roleObj.status === 'active' ? 'text-success' : 'text-danger';
        let statusText = roleObj.status.charAt(0).toUpperCase() + roleObj.status.slice(1);
        return `<tr>
            <td><span class="d-block fw-semibold">${roleObj.name}</span></td>
            <td>
                <form method="POST" action="{{ url('roles') }}/${roleObj.roleid}/toggle" class="d-inline role-ajax-form">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 ${statusColor}">${statusText}</button>
                </form>
            </td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
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
        let statusColor = dept.status === 'active' ? 'text-success' : 'text-danger';
        let statusText = dept.status.charAt(0).toUpperCase() + dept.status.slice(1);
        return `<tr>
            <td><span class="d-block fw-semibold">${dept.name}</span></td>
            <td>
                <form method="POST" action="{{ url('departments') }}/${dept.depid}/toggle" class="d-inline dept-ajax-form">
                    <input type="hidden" name="_token" value="${csrf}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 ${statusColor}">${statusText}</button>
                </form>
            </td>
            <td class="text-end">
                <div class="tableActionButton d-inline-flex gap-1">
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
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No roles found.</td></tr>';
        } else {
            tbody.innerHTML = roles.map(r => buildRoleRow(r, csrf)).join('');
        }
    }

    function refreshDepartmentsTable(departments) {
        const tbody = document.querySelector('#departmentsTable tbody');
        const csrf = document.querySelector('input[name="_token"]').value;
        if (departments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">No departments found.</td></tr>';
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
    });
</script>
@endsection
