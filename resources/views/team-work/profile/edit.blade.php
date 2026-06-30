@extends('layouts.employee')

@section('content')
@php
$showDetails = true; // For keeping it simple like the clients form
@endphp
@if(isset($profile) && $profile->status === 'approved')
    {{-- Approved Profile View --}}
    <div class="position-relative p-0">
        {{-- Profile Header Card --}}
        <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 mb-3 text-start">
            <div class="card-body bg-white rounded-3 p-3">
                <div class="row align-items-center g-0">
                    {{-- Avatar / Profile Image --}}
                    <div class="col-auto">
                        <div class="position-relative" style="width:80px; height:80px;">
                            @if(auth()->user()->profile_image)
                            <div class="border rounded-circle overflow-hidden bg-white d-flex align-items-center justify-content-center w-100 h-100">
                                <a href="{{ asset('storage/' . auth()->user()->profile_image) }}" target="_blank" class="w-100 h-100 d-block" title="View full image">
                                    <img src="{{ asset('storage/' . auth()->user()->profile_image) }}" alt="{{ auth()->user()->name }}" class="w-100 h-100 object-fit-cover">
                                </a>
                            </div>
                            @else
                            <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center w-100 h-100" style="font-size:1.5rem;">
                                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                            </div>
                            @endif
                            <div class="status-dot {{ auth()->user()->is_active ? 'active' : 'inactive' }}" title="{{ auth()->user()->is_active ? 'Active' : 'Inactive' }}" style="width: 16px; height: 16px; border-width: 3px; top: 4px; right: 4px;"></div>
                        </div>
                    </div>

                    {{-- Employee Info --}}
                    <div class="col ms-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0">{{ auth()->user()->name }}</h4>
                            <span class="badge bg-success text-white py-1 px-2 rounded-pill" style="font-size: 0.75rem;">{{ auth()->user()->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>

                        <p class="text-black mb-1">
                            <i class="fas fa-envelope text-muted small lh-sm"></i>
                            {{ auth()->user()->email }}

                            <span class="mx-2 text-muted">|</span>

                            <i class="fas fa-phone text-muted small lh-sm"></i>
                            {{ auth()->user()->phone ?? 'No Phone' }}
                        </p>

                        <div class="d-flex flex-wrap gap-0 text-black">
                            <span>
                                <i class="fas fa-building text-muted small lh-sm"></i>
                                Department: {{ auth()->user()->department->name ?? 'N/A' }}
                            </span>
                            @if(auth()->user()->designation)
                            <span class="mx-2 text-muted">|</span>
                            <span>
                                <i class="fas fa-user-tag text-muted small lh-sm"></i>
                                Designation: {{ auth()->user()->designation }}
                            </span>
                            @endif
                            @if(auth()->user()->gender)
                            <span class="mx-2 text-muted">|</span>
                            <span>
                                <i class="fas fa-venus-mars text-muted small lh-sm"></i>
                                Gender: {{ auth()->user()->gender }}
                            </span>
                            @endif
                            <span class="mx-2 text-muted">|</span>
                            <span>
                                <i class="fas fa-calendar-alt text-muted small lh-sm"></i>
                                Joined: {{ auth()->user()->created_at?->format('d M Y') ?? '-' }}
                            </span>
                            <span class="mx-2 text-muted">|</span>
                            <span>
                                <i class="fas fa-clock text-muted small lh-sm"></i>
                                Shift: {{ auth()->user()->shift->shift_name ?? 'N/A' }}
                                @if(!empty(auth()->user()->shift->start_time) && !empty(auth()->user()->shift->end_time))
                                    ({{ \Carbon\Carbon::parse(auth()->user()->shift->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse(auth()->user()->shift->end_time)->format('h:i A') }})
                                @endif
                            </span>
                            <span class="mx-2 text-muted">|</span>
                            <span>
                                <i class="fas fa-file-contract text-muted small lh-sm"></i>
                                Policy: {{ auth()->user()->attendancePolicy->policy_name ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="row g-2 text-start">
            {{-- Contact Information --}}
            <div class="col-12 col-md-6">
                <div class="card border-0 bg-white rounded-3 p-3 h-100 shadow-sm">
                    <h5 class="fw-semibold text-dark border-bottom pb-2 fs-6 lh-sm mb-3">
                        <i class="fas fa-address-card me-2 text-primary"></i> Contact Information
                    </h5>
                    <div class="d-flex flex-column gap-2">
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

            {{-- Banking Information --}}
            <div class="col-12 col-md-6">
                <div class="card border-0 bg-white rounded-3 p-3 h-100 shadow-sm">
                    <h5 class="fw-semibold text-dark border-bottom pb-2 fs-6 lh-sm mb-3">
                        <i class="fas fa-university me-2 text-primary"></i> Banking Information
                    </h5>
                    <div class="d-flex flex-column gap-2">
                        <div class="row">
                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Bank Name</div>
                            <div class="col-8 fw-semibold text-dark fs-6 lh-sm my-auto">{{ $profile->bank_name ?: '-' }}</div>
                        </div>
                        <div class="row">
                            <div class="col-4 fw-normal text-muted small lh-sm my-auto">Account Holder Name</div>
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
            <div class="col-12 mt-2">
                <div class="card border-0 bg-white rounded-3 p-3 shadow-sm">
                    <h5 class="fw-semibold text-dark border-bottom pb-2 fs-6 lh-sm mb-3">
                        <i class="fas fa-file-alt me-2 text-primary"></i> Uploaded Documents
                    </h5>
                    @php
                        $documents = $profile->documents;
                    @endphp
                    @if($documents->count() > 0)
                        <div class="row g-2">
                            @foreach($documents as $doc)
                                <div class="col-12 col-md-6 col-lg-3">
                                    <div class="border rounded-2 p-2 bg-light d-flex justify-content-between align-items-center shadow-sm h-100">
                                        <div class="text-truncate w-100">
                                            <small class="fw-semibold d-block text-dark lh-sm mb-2 text-truncate" title="{{ $doc->doc_type }}">{{ $doc->doc_type }}</small>
                                            <a href="{{ asset('storage/' . $doc->doc_path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 w-100"><i class="fas fa-external-link-alt me-1"></i>View File</a>
                                        </div>
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
                                <input type="text" class="form-control bg-white" value="{{ auth()->user()->name }}" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Email Address</label>
                                <input type="text" class="form-control bg-white" value="{{ auth()->user()->email }}" readonly disabled>
                            </div>
                            <div class="col-12">
                                <label class="form-label small lh-sm fw-semibold text-dark mb-1">Department</label>
                                <input type="text" class="form-control bg-white" value="{{ auth()->user()->department->name ?? 'N/A' }}" readonly disabled>
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
