@extends('layouts.employee')

@section('content')
@php
$showDetails = true; // For keeping it simple like the clients form
@endphp
@if(isset($profile) && $profile->status === 'approved')
    {{-- Approved Profile View --}}
    <div class="position-relative bg-white p-2 rounded-3">
        {{-- Profile Header Card --}}
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-2 text-start">
            <div class="card-body bg-white rounded-3 p-3">
                <div class="row align-items-center g-0">
                    {{-- Avatar / Profile Image --}}
                    <div class="col-auto">
                        <div class="position-relative" style="width:80px; height:80px;">
                            @if($employee->profile_image)
                            <div class="border rounded-circle overflow-hidden bg-white d-flex align-items-center justify-content-center w-100 h-100">
                                <a href="{{ asset('storage/' . $employee->profile_image) }}" target="_blank" class="w-100 h-100 d-block" title="View full image">
                                    <img src="{{ asset('storage/' . $employee->profile_image) }}" alt="{{ $employee->name }}" class="w-100 h-100 object-fit-cover p-1">
                                </a>
                            </div>
                            @else
                            <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center w-100 h-100" style="font-size:1.5rem;">
                                {{ strtoupper(substr($employee->name, 0, 2)) }}
                            </div>
                            @endif
                            <div class="status-dot {{ $employee->is_active ? 'active' : 'inactive' }}" title="{{ $employee->is_active ? 'Active' : 'Inactive' }}" style="width: 16px; height: 16px; border-width: 3px; top: 4px; right: 4px;"></div>
                        </div>
                    </div>

                    {{-- Employee Info --}}
                    <div class="col ms-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0">{{ $employee->name }}</h4>
                            <span class="badge bg-success text-white py-1 px-2 rounded-pill" style="font-size: 0.75rem;">{{ $employee->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <p class="text-black mb-1">
                            <i class="fas fa-envelope text-muted small lh-sm"></i>
                            {{ $employee->email }}

                            <span class="mx-2 text-muted">|</span>

                            <i class="fas fa-phone text-muted small lh-sm"></i>
                            {{ $employee->phone ?? 'No Phone' }}
                        </p>

                        <div class="d-flex flex-wrap gap-0 text-black">
                            @if($employee->designation)
                            <span>
                                <i class="fas fa-user-tag text-muted small lh-sm"></i>
                                Designation: {{ $employee->designation }}
                            </span>
                            @endif
                            
                            @if($employee->designation && $employee->gender)
                            <span class="mx-2 text-muted">|</span>
                            @endif

                            @if($employee->gender)
                            <span>
                                <i class="fas fa-venus-mars text-muted small lh-sm"></i>
                                Gender: {{ $employee->gender }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Area --}}
        <div class="row g-2">
            <div class="col-12">
                <div class="card overflow-hidden border-0 bg-DarkLight rounded-3 h-100">
                    <div class="card-body bg-transparent rounded-3 p-2">
                        <div class="row g-2 text-start">
                            {{-- Contact Information --}}
                            <div class="col-12 col-md-4">
                                <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                    <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3">
                                        Contact Information
                                    </h5>
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Address</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->address ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">City</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->city ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">State/Province</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->state ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Country</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->country ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Postal/Zip Code</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->zip_code ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Employment Details --}}
                            <div class="col-12 col-md-4">
                                <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                    <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3">
                                        Employment Details
                                    </h5>
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Department</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->department->name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Designation</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->designation ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Gender</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->gender ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Joined Date</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->created_at?->format('d M Y') ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Shift</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">
                                                {{ $employee->shift->shift_name ?? 'N/A' }}
                                                @if(!empty($employee->shift->start_time) && !empty($employee->shift->end_time))
                                                    <span class="text-muted small">({{ \Carbon\Carbon::parse($employee->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($employee->shift->end_time)->format('h:i A') }})</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Attendance Policy</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->attendancePolicy->policy_name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Leave Policy</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $employee->leavePolicy->policy_name ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Banking Information --}}
                            <div class="col-12 col-md-4">
                                <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                    <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3">
                                        Banking Information
                                    </h5>
                                    <div class="d-flex flex-column gap-2 text-start">
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Bank Name</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->bank_name ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Account Holder</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->account_name ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Account Number</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->account_number ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Routing Code</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->routing_code ?: '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Bank Branch</div>
                                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->bank_branch ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Documents --}}
                            <div class="col-12 col-md-4 mt-2">
                                <div class="card border-0 bg-white rounded-3 p-3 h-100">
                                    <h5 class="fw-semibold text-dark border-bottom pb-1 fs-6 lh-sm mb-3">
                                        Uploaded Documents
                                    </h5>
                                    @php
                                        $documents = $profile->documents;
                                    @endphp
                                    @if($documents->count() > 0)
                                        <div class="table-responsive p-2 border-0 bg-DarkLight rounded-3 text-start">
                                            <table class="table table-striped border mainTable align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-semibold small text-muted">Document Type</th>
                                                        <th class="fw-semibold small text-muted text-center" style="width: 120px;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($documents as $doc)
                                                        @php
                                                            $ext = pathinfo($doc->doc_path, PATHINFO_EXTENSION);
                                                            $icon = 'fa-file-alt text-secondary';
                                                            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
                                                                $icon = 'fa-image text-primary';
                                                            } elseif (strtolower($ext) === 'pdf') {
                                                                $icon = 'fa-file-pdf text-danger';
                                                            }
                                                        @endphp
                                                        <tr>
                                                            <td class="fw-medium text-dark">
                                                                <i class="fas {{ $icon }} me-2"></i> {{ $doc->doc_type }}
                                                            </td>
                                                            <td class="text-center">
                                                                <a href="{{ asset('storage/' . $doc->doc_path) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="View Document">
                                                                    View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
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
    </div>
@else
    <div class="position-relative bg-white p-2 rounded-3">
        @if(isset($profile) && $profile->status === 'pending')
            <div class="alert alert-warning py-2 small mb-2">
                <i class="fas fa-exclamation-triangle me-1"></i> Your profile details are currently under review by an administrator.
            </div>
        @elseif(isset($profile) && $profile->status === 'approved')
            <div class="alert alert-success py-2 small mb-2">
                <i class="fas fa-check-circle me-1"></i> Your profile details have been approved.
            </div>
        @elseif(isset($profile) && $profile->status === 'rejected')
            <div class="alert alert-danger py-2 small mb-2">
                <i class="fas fa-times-circle me-1"></i> Your previous profile update was rejected. Please review and submit again.
            </div>
        @endif

        <form method="POST" action="{{ route('team-work.profile.store') }}" class="mainForm" enctype="multipart/form-data">
            @csrf
            
            <div class="row g-2 align-items-stretch">
                
                <!-- Column 1: Basic Information (Read Only) -->
                <div class="col-12 col-lg-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h5 class="fw-semibold text-primary small lh-sm mb-0">Basic Information</h5>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Full Name</label>
                                <input type="text" class="form-control bg-white" value="{{ $employee->name }}" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Email Address</label>
                                <input type="text" class="form-control bg-white" value="{{ $employee->email }}" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Department</label>
                                <input type="text" class="form-control bg-white" value="{{ $employee->department->name ?? 'N/A' }}" readonly disabled>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Contact Information -->
                <div class="col-12 col-lg-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h5 class="fw-semibold text-primary small lh-sm mb-0">Contact Information</h5>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label for="address" class="form-label small lh-sm fw-semibold text-dark mb-1">Address</label>
                                <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $profile->address ?? '') }}">
                                @error('address') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="city" class="form-label small lh-sm fw-semibold text-dark mb-1">City</label>
                                <input type="text" id="city" name="city" class="form-control" value="{{ old('city', $profile->city ?? '') }}">
                                @error('city') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="state" class="form-label small lh-sm fw-semibold text-dark mb-1">State/Province</label>
                                <input type="text" id="state" name="state" class="form-control" value="{{ old('state', $profile->state ?? '') }}">
                                @error('state') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="country" class="form-label small lh-sm fw-semibold text-dark mb-1">Country</label>
                                <input type="text" id="country" name="country" class="form-control" value="{{ old('country', $profile->country ?? '') }}">
                                @error('country') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="zip_code" class="form-label small lh-sm fw-semibold text-dark mb-1">Postal/Zip Code</label>
                                <input type="text" id="zip_code" name="zip_code" class="form-control" value="{{ old('zip_code', $profile->zip_code ?? '') }}">
                                @error('zip_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Banking Information -->
                <div class="col-12 col-lg-4">
                    <div class="bg-light p-2 rounded-3 h-100">
                        <div class="mb-2">
                            <h5 class="fw-semibold text-primary small lh-sm mb-0">Banking Information</h5>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label for="bank_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" class="form-control" value="{{ old('bank_name', $profile->bank_name ?? '') }}">
                                @error('bank_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="account_name" class="form-label small lh-sm fw-semibold text-dark mb-1">Account Holder Name</label>
                                <input type="text" id="account_name" name="account_name" class="form-control" value="{{ old('account_name', $profile->account_name ?? '') }}">
                                @error('account_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-12">
                                <label for="account_number" class="form-label small lh-sm fw-semibold text-dark mb-1">Account Number</label>
                                <input type="text" id="account_number" name="account_number" class="form-control" value="{{ old('account_number', $profile->account_number ?? '') }}">
                                @error('account_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="routing_code" class="form-label small lh-sm fw-semibold text-dark mb-1">Routing Code</label>
                                <input type="text" id="routing_code" name="routing_code" class="form-control" value="{{ old('routing_code', $profile->routing_code ?? '') }}">
                                @error('routing_code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="bank_branch" class="form-label small lh-sm fw-semibold text-dark mb-1">Bank Branch</label>
                                <input type="text" id="bank_branch" name="bank_branch" class="form-control" value="{{ old('bank_branch', $profile->bank_branch ?? '') }}">
                                @error('bank_branch') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="col-12 mt-2">
                    <div class="bg-light p-2 rounded-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="fw-semibold text-primary small lh-sm mb-0">Documents</h5>
                            <button type="button" class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium btn-sm" id="addDocumentBtn"><i class="fas fa-plus"></i> Add Document</button>
                        </div>

                        <div id="documentsContainer" class="row g-2">
                            @php
                                $documents = isset($profile) ? $profile->documents : collect([]);
                            @endphp
                            @if($documents->count() > 0)
                                @foreach($documents as $doc)
                                    <div class="col-12 col-md-6 col-lg-3" id="existingDoc_{{ $doc->docid }}">
                                        <div class="border rounded-2 p-2 bg-white d-flex justify-content-between align-items-center shadow-sm h-100">
                                            <div class="text-truncate">
                                                <small class="fw-semibold d-block text-dark lh-sm doc-type-label">{{ $doc->doc_type }}</small>
                                                <small class="text-muted" style="font-size: 0.75rem;"><a href="{{ asset('storage/' . $doc->doc_path) }}" target="_blank">View Uploaded File</a></small>
                                            </div>
                                            <div class="tableActionButton">
                                                <button type="button" class="bg04 color04 border-0 remove-existing-doc" data-target="existingDoc_{{ $doc->docid }}" data-id="{{ $doc->docid }}">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            <!-- New document blocks will be appended here -->
                        </div>
                    </div>
                </div>
                
                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary text-white px-4">Save Details</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const addDocumentBtn = document.getElementById('addDocumentBtn');
        const documentsContainer = document.getElementById('documentsContainer');
        let docIndex = 0;

        addDocumentBtn?.addEventListener('click', function() {
            const docHtml = `
                <div class="col-12 col-md-6 col-lg-4" id="docBlock_${docIndex}">
                    <div class="border rounded-2 p-2 bg-white d-flex align-items-center gap-2 shadow-sm h-100">
                        <select name="documents[${docIndex}][type]" class="form-select form-select-sm m-0 dynamic-doc-select" required style="width: 40%;">
                            <option value="" disabled selected>Select Type</option>
                            <option value="Photo">Photo</option>
                            <option value="PAN">PAN</option>
                            <option value="Identity proof">Identity proof/Aadhaar</option>
                            <option value="Bank details">Bank details</option>
                        </select>
                        <input type="file" name="documents[${docIndex}][file]" class="form-control form-control-sm m-0" required accept=".jpg,.jpeg,.png,.webp,.pdf" style="width: 50%;">
                        <div class="tableActionButton">
                            <button type="button" class="bg04 color04 border-0 m-0 remove-doc-btn" data-target="docBlock_${docIndex}">Delete</button>
                        </div>
                    </div>
                </div>
            `;
            documentsContainer.insertAdjacentHTML('beforeend', docHtml);
            docIndex++;
            updateAvailableDocumentTypes();
        });

        documentsContainer?.addEventListener('click', function(e) {
            if (e.target.closest('.remove-doc-btn')) {
                const btn = e.target.closest('.remove-doc-btn');
                const targetId = btn.getAttribute('data-target');
                document.getElementById(targetId)?.remove();
                updateAvailableDocumentTypes();
            }
        });

        documentsContainer?.addEventListener('change', function(e) {
            if (e.target.classList.contains('dynamic-doc-select')) {
                updateAvailableDocumentTypes();
            }
        });

        document.querySelectorAll('.remove-existing-doc').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const docId = this.getAttribute('data-id');
                // Add hidden input to form
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_documents[]';
                hiddenInput.value = docId;
                document.querySelector('.mainForm').appendChild(hiddenInput);
                
                // Remove the block
                document.getElementById(targetId)?.remove();
                updateAvailableDocumentTypes();
            });
        });

        function updateAvailableDocumentTypes() {
            const existingTypes = Array.from(document.querySelectorAll('.doc-type-label')).map(el => el.textContent.trim());
            const selects = document.querySelectorAll('.dynamic-doc-select');
            const selectedValues = Array.from(selects).map(s => s.value).filter(v => v !== '');

            const allSelected = [...existingTypes, ...selectedValues];

            selects.forEach(select => {
                const currentValue = select.value;
                Array.from(select.options).forEach(option => {
                    if (option.value === '') return;
                    if (allSelected.includes(option.value) && option.value !== currentValue) {
                        option.disabled = true;
                    } else {
                        option.disabled = false;
                    }
                });
            });
        }

        // Run on initial load
        updateAvailableDocumentTypes();
    });
    </script>
@endif
@endsection
