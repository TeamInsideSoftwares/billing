@extends('layouts.app')
@section('title', 'Profile Approvals')
@section('header_actions')
<a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left btn-icon"></i> Back to Team List
</a>
@endsection
@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <div class="position-relative bg-DarkLight p-2 rounded-3 mb-2">
        <form action="{{ route('users.approvals') }}" method="GET" class="mainForm">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <select name="employee_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->userid }}" {{ request('employee_id') == $emp->userid ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
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
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
                <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" width="30%">Employee</th>
                            <th scope="col" width="30%">Email</th>
                            <th scope="col" width="20%">Submitted At</th>
                            <th scope="col" class="text-end" width="20%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingProfiles as $profile)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @php
                                            $photoDoc = $profile->documents->firstWhere('doc_type', 'Photo');
                                            $profileImage = $photoDoc ? $photoDoc->doc_path : $profile->user?->profile_image;
                                        @endphp
                                        <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                            @if(!empty($profileImage))
                                                <span class="d-none position-absolute">{{ strtoupper(substr($profile->user->name ?? 'U', 0, 2)) }}</span>
                                                <img src="{{ str_replace(env('APP_URL', url('/')), env('TEAM_URL', url('/')), asset('storage/' . $profileImage)) }}" 
                                                     onerror="this.style.display='none'; this.previousElementSibling.classList.replace('d-none', 'd-block');"
                                                     alt="{{ $profile->user->name }}" 
                                                     class="position-absolute rounded-circle bg-white" 
                                                     style="width:40px;height:40px;object-fit:cover;top:0;left:0;border:2px solid #fff;">
                                            @else
                                                <span class="d-block position-absolute">{{ strtoupper(substr($profile->user->name ?? 'U', 0, 2)) }}</span>
                                            @endif
                                            <div class="status-dot {{ $profile->user?->is_active ? 'active' : 'inactive' }}"
                                                 title="{{ $profile->user?->is_active ? 'Active' : 'Inactive' }}"></div>
                                        </div>
                                        <div>
                                            <span class="fw-medium text-dark d-block">{{ $profile->user->name ?? 'Unknown User' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $profile->user->email ?? 'N/A' }}</td>
                                <td>{{ $profile->updated_at->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1 align-items-center justify-content-end">
                                        <button type="button" class="bg01 color01" data-bs-toggle="modal" data-bs-target="#reviewModal{{ $profile->profileid }}">
                                             Review
                                        </button>
                                        <form action="{{ route('users.approvals.approve', $profile->profileid) }}" method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <button type="submit" class="bg03 color03">Approve</button>
                                        </form>
                                        <form action="{{ route('users.approvals.reject', $profile->profileid) }}" method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <button type="submit" class="bg02 color02">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Review Modal -->
                            <div class="modal fade text-start" id="reviewModal{{ $profile->profileid }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-semibold text-dark">Review Profile Update</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-3">
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="bg-light p-3 rounded-3 h-100">
                                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-address-card me-2"></i>Contact Information</h6>
                                                        <div class="mb-2">
                                                            <small class="text-muted d-block">Address</small>
                                                            <span class="text-dark fw-medium">{{ $profile->address ?: 'N/A' }}</span>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">City</small>
                                                                <span class="text-dark fw-medium">{{ $profile->city ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">State</small>
                                                                <span class="text-dark fw-medium">{{ $profile->state ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Country</small>
                                                                <span class="text-dark fw-medium">{{ $profile->country ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Zip Code</small>
                                                                <span class="text-dark fw-medium">{{ $profile->zip_code ?: 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <div class="bg-light p-3 rounded-3 h-100">
                                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-university me-2"></i>Banking Information</h6>
                                                        <div class="mb-2">
                                                            <small class="text-muted d-block">Bank Name</small>
                                                            <span class="text-dark fw-medium">{{ $profile->bank_name ?: 'N/A' }}</span>
                                                        </div>
                                                        <div class="row g-2">
                                                            <div class="col-12">
                                                                <small class="text-muted d-block">Account Name</small>
                                                                <span class="text-dark fw-medium">{{ $profile->account_name ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-12">
                                                                <small class="text-muted d-block">Account Number</small>
                                                                <span class="text-dark fw-medium">{{ $profile->account_number ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Routing Code</small>
                                                                <span class="text-dark fw-medium">{{ $profile->routing_code ?: 'N/A' }}</span>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted d-block">Bank Branch</small>
                                                                <span class="text-dark fw-medium">{{ $profile->bank_branch ?: 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="bg-light p-3 rounded-3 h-100">
                                                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-file-alt me-2"></i>Uploaded Documents</h6>
                                                        @php
                                                            $documents = $profile->documents;
                                                        @endphp
                                                        @if($documents && $documents->count() > 0)
                                                            <div class="row g-2">
                                                                @foreach($documents as $doc)
                                                                    <div class="col-6 col-md-4 col-lg-3">
                                                                        <div class="border rounded-2 p-2 bg-white text-center shadow-sm">
                                                                            <small class="fw-semibold d-block text-dark mb-2 text-truncate" title="{{ $doc->doc_type }}">{{ $doc->doc_type }}</small>
                                                                            <a href="{{ $doc->full_url }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 w-100" style="font-size: 0.75rem;"><i class="fas fa-external-link-alt me-1"></i>View File</a>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>No documents uploaded.</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox mb-3 text-secondary fs-1 opacity-50"></i>
                                    <p class="fw-semibold text-dark mb-1">No pending profile requests found.</p>
                                    <p class="small text-muted mb-0">Pending approvals will appear here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div id="action-history" class="tab-pane fade" role="tabpanel">
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
                <div class="table-responsive">
                <table class="table table-striped mainTable align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" width="30%">Employee</th>
                            <th scope="col" width="30%">Email</th>
                            <th scope="col" width="20%">Updated At</th>
                            <th scope="col" class="text-center" width="10%">Status</th>
                            <th scope="col" class="text-end" width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historyProfiles as $profile)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        @php
                                            $photoDoc = $profile->documents->firstWhere('doc_type', 'Photo');
                                            $profileImage = $photoDoc ? $photoDoc->doc_path : $profile->user?->profile_image;
                                        @endphp
                                        <div class="tablePrifix position-relative bg-primary-subtle text-primary rounded-circle fw-semibold">
                                            @if(!empty($profileImage))
                                                <span class="d-none position-absolute">{{ strtoupper(substr($profile->user->name ?? 'U', 0, 2)) }}</span>
                                                <img src="{{ asset('storage/' . $profileImage) }}" 
                                                     onerror="this.style.display='none'; this.previousElementSibling.classList.replace('d-none', 'd-block');"
                                                     alt="{{ $profile->user->name }}" 
                                                     class="position-absolute rounded-circle bg-white" 
                                                     style="width:40px;height:40px;object-fit:cover;top:0;left:0;border:2px solid #fff;">
                                            @else
                                                <span class="d-block position-absolute">{{ strtoupper(substr($profile->user->name ?? 'U', 0, 2)) }}</span>
                                            @endif
                                            <div class="status-dot {{ $profile->user?->is_active ? 'active' : 'inactive' }}"
                                                 title="{{ $profile->user?->is_active ? 'Active' : 'Inactive' }}"></div>
                                        </div>
                                        <div>
                                            <span class="fw-medium text-dark d-block">{{ $profile->user->name ?? 'Unknown User' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $profile->user->email ?? 'N/A' }}</td>
                                <td>{{ $profile->updated_at->format('M d, Y h:i A') }}</td>
                                <td class="text-center">
                                    @if($profile->status === 'approved')
                                        <span class="badge bg-success py-1.5 px-3 rounded-pill text-white"><i class="fas fa-check-circle me-1"></i> Approved</span>
                                    @else
                                        <span class="badge bg-danger py-1.5 px-3 rounded-pill text-white"><i class="fas fa-times-circle me-1"></i> Rejected</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="tableActionButton d-inline-flex gap-1 align-items-center justify-content-end">
                                        <button type="button" class="bg01 color01" data-bs-toggle="modal" data-bs-target="#viewModal{{ $profile->profileid }}">
                                            View
                                        </button>
                                    </div>

                                    <!-- View Modal -->
                                    <div class="modal fade text-start" id="viewModal{{ $profile->profileid }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-semibold text-dark">Review Profile Update</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="row g-3">
                                                        <div class="col-12 col-md-6">
                                                            <div class="bg-light p-3 rounded-3 h-100">
                                                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-address-card me-2"></i>Contact Information</h6>
                                                                <div class="mb-2">
                                                                    <small class="text-muted d-block">Address</small>
                                                                    <span class="text-dark fw-medium">{{ $profile->address ?: 'N/A' }}</span>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">City</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->city ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">State</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->state ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Country</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->country ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Zip Code</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->zip_code ?: 'N/A' }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="bg-light p-3 rounded-3 h-100">
                                                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-university me-2"></i>Banking Information</h6>
                                                                <div class="mb-2">
                                                                    <small class="text-muted d-block">Bank Name</small>
                                                                    <span class="text-dark fw-medium">{{ $profile->bank_name ?: 'N/A' }}</span>
                                                                </div>
                                                                <div class="row g-2">
                                                                    <div class="col-12">
                                                                        <small class="text-muted d-block">Account Name</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->account_name ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-12">
                                                                        <small class="text-muted d-block">Account Number</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->account_number ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Routing Code</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->routing_code ?: 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted d-block">Bank Branch</small>
                                                                        <span class="text-dark fw-medium">{{ $profile->bank_branch ?: 'N/A' }}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="bg-light p-3 rounded-3 h-100">
                                                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-file-alt me-2"></i>Uploaded Documents</h6>
                                                                @php
                                                                    $documents = $profile->documents;
                                                                @endphp
                                                                @if($documents && $documents->count() > 0)
                                                                    <div class="row g-2">
                                                                        @foreach($documents as $doc)
                                                                            <div class="col-6 col-md-4 col-lg-3">
                                                                                <div class="border rounded-2 p-2 bg-white text-center shadow-sm">
                                                                                    <small class="fw-semibold d-block text-dark mb-2 text-truncate" title="{{ $doc->doc_type }}">{{ $doc->doc_type }}</small>
                                                                                    <a href="{{ $doc->full_url }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 w-100" style="font-size: 0.75rem;"><i class="fas fa-external-link-alt me-1"></i>View File</a>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>No documents uploaded.</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted bg-white">
                                    <i class="fas fa-inbox mb-3 text-secondary fs-1 opacity-50"></i>
                                    <p class="fw-semibold text-dark mb-1">No processed profile requests found.</p>
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
