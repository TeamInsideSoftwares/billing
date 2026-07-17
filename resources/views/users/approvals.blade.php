@extends('layouts.app')

@section('header_actions')
<a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-arrow-left btn-icon"></i> Back to Team List
</a>
@endsection

@section('content')
<div class="bg-white p-3 rounded-3 shadow-sm">
    <h5 class="fw-semibold text-dark mb-3">Pending Profile Approvals</h5>

    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3">
        <div class="table-responsive">
            <table class="table table-striped mainTable align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Team Member</th>
                        <th>Email</th>
                        <th>Submitted At</th>
                        <th class="text-end">Actions</th>
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
                        <td class="text-muted small">{{ $profile->user->email ?? 'N/A' }}</td>
                        <td class="text-muted small">{{ $profile->updated_at->format('M d, Y h:i A') }}</td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-primary text-white px-3" data-bs-toggle="modal" data-bs-target="#reviewModal{{ $profile->profileid }}">
                                Review
                            </button>
                        </td>
                    </tr>

                    <!-- Review Modal -->
                    <div class="modal fade" id="reviewModal{{ $profile->profileid }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="modal-title fw-semibold text-dark">Review Profile Update</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
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
                                <div class="modal-footer border-0 bg-light rounded-bottom-3 mt-2">
                                    <form method="POST" action="{{ route('users.approvals.reject', $profile->profileid) }}" class="m-0">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-outline-danger">Reject</button>
                                    </form>
                                    
                                    <form method="POST" action="{{ route('users.approvals.approve', $profile->profileid) }}" class="m-0">
                                        @csrf @method('PUT')
                                        <button type="submit" class="btn btn-success text-white px-4">Approve Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted bg-white">
                                <i class="fas fa-inbox mb-3 text-secondary fs-1 opacity-50"></i>
                                <p class="fw-semibold text-dark mb-1">No pending profile updates found.</p>
                                <p class="small text-muted mb-0">Pending approvals will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
