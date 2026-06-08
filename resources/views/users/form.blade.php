@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Users
    </a>
@endsection

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="{{ isset($userModel) ? route('users.update', $userModel) : route('users.store') }}" enctype="multipart/form-data">
            @csrf
            @isset($userModel)
                @method('PUT')
            @endisset

            <div class="mb-4">
                <label class="form-label small lh-sm fw-semibold text-dark mb-1">User Image</label>
                <div class="d-flex align-items-stretch gap-3">
                    <div class="flex-shrink-0">
                        <img
                            id="avatarPreview"
                            class="rounded-circle border border-primary-subtle img-thumbnail"
                            src="{{ !empty($userModel?->profile_image) ? asset('storage/' . $userModel->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode(old('name', $userModel->name ?? 'User')) . '&background=eff6ff&color=1e3a8a' }}"
                            alt="User image"
                            style="width: 80px; height: 80px; object-fit: cover;"
                        >
                    </div>
                    <div class="flex-grow-1">
                        <!-- Custom Drag and Drop Area -->
                        <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                            style="cursor:pointer;" id="profile-drop-zone">
                            <input type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                class="position-absolute top-0 start-0 w-100 h-100 opacity-0">

                            <div class="drop-zone-prompt d-flex align-items-center justify-content-center"
                                id="drop-zone-prompt">
                                <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                        class="text-primary fw-semibold">browse files</span></span>
                            </div>
                        </div>
                        <input type="hidden" id="cropped_image_data" name="cropped_image_data" value="">
                        <small class="text-muted d-block mt-1">Upload and crop square profile image</small>
                        @error('profile_image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('cropped_image_data') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $userModel->name ?? '') }}" required>
                    @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $userModel->email ?? '') }}" required>
                    @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="department" class="form-label">Department *</label>
                    <input type="text" id="department" name="department" class="form-control" value="{{ old('department', $userModel->department ?? '') }}" required placeholder="e.g. Sales, Support, Finance">
                    @error('department') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="designation" class="form-label">Designation</label>
                    <input type="text" id="designation" name="designation" class="form-control" value="{{ old('designation', $userModel->designation ?? '') }}" placeholder="e.g. Sr. Executive">
                    @error('designation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $userModel->phone ?? '') }}" placeholder="e.g. +91 98xxxxxx">
                    @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role *</label>
                    <select id="role" name="role" class="form-select" required>
                        @php($selectedRole = old('role', $userModel->role ?? 'staff'))
                        <option value="admin" {{ $selectedRole === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="manager" {{ $selectedRole === 'manager' ? 'selected' : '' }}>Manager</option>
                        <option value="staff" {{ $selectedRole === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                    @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $userModel->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="is_active">Active User</label>
                        @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this user">{{ old('notes', $userModel->notes ?? '') }}</textarea>
                    @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <hr class="my-4">

            @php($selectedPermissions = old('permissions', $userModel->permissions ?? []))
            <div class="card mb-4 bg-light border-0">
                <div class="card-body p-4">
                    <label class="form-label fw-bold mb-3">Permissions</label>
                    @foreach(($groupedPermissions ?? []) as $module => $permissions)
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">{{ $module }} Module</h6>
                            <div class="row g-2">
                                @foreach($permissions as $permission)
                                    @php($action = ucfirst(explode('.', $permission)[1] ?? $permission))
                                    <div class="col-md-4 col-sm-6">
                                        <div class="p-2 border rounded bg-white">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm_{{ Str::slug($permission) }}" {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="perm_{{ Str::slug($permission) }}">
                                                    {{ $action }} <span class="text-muted">({{ $permission }})</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @error('permissions') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @error('permissions.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="password" class="form-label">{{ isset($userModel) ? 'New Password' : 'Password *' }}</label>
                    <input type="password" id="password" name="password" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6" placeholder="{{ isset($userModel) ? 'Leave blank to keep existing' : 'Minimum 6 characters' }}">
                    @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirm Password {{ isset($userModel) ? '' : '*' }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ isset($userModel) ? 'Update User' : 'Create User' }}</button>
                <a href="{{ route('users.index') }}" class="btn btn-link text-decoration-none">Cancel</a>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-white border-bottom">
                <h5 class="modal-title fw-semibold" id="cropperModalTitle">Crop User Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-4">
                <div class="img-container mb-3" style="max-height: 450px; overflow: hidden; background-color: #f1f5f9; border-radius: 8px;">
                    <img id="cropperImage" src="" alt="Crop target" style="max-width: 100%; display: block;">
                </div>
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <button type="button" class="btn btn-outline-primary bg-white text-primary fw-medium" id="cancelCropBtn" data-bs-dismiss="modal">
                        <i class="fas fa-times btn-icon me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-primary text-white fw-medium" id="applyCropBtn">
                        Apply Crop <i class="fas fa-arrow-right btn-icon ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('profile_image');
    const hiddenInput = document.getElementById('cropped_image_data');
    const preview = document.getElementById('avatarPreview');
    const cropperModalEl = document.getElementById('cropperModal');
    const cropperImage = document.getElementById('cropperImage');
    const applyBtn = document.getElementById('applyCropBtn');
    let cropper = null;
    let objectUrl = null;
    let bsModal = null;

    if (cropperModalEl && typeof bootstrap !== 'undefined') {
        bsModal = new bootstrap.Modal(cropperModalEl);
    }

    const profileDropZone = document.getElementById('profile-drop-zone');
    if (profileDropZone && fileInput) {
        ['dragenter', 'dragover'].forEach(eventName => {
            profileDropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                profileDropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            profileDropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                profileDropZone.classList.remove('dragover');
            }, false);
        });

        profileDropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    fileInput?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;

        objectUrl = URL.createObjectURL(file);
        cropperImage.src = objectUrl;
        
        if (bsModal) {
            bsModal.show();
        }
    });

    cropperModalEl?.addEventListener('shown.bs.modal', function () {
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropperImage, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            background: false
        });
    });

    cropperModalEl?.addEventListener('hidden.bs.modal', function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        fileInput.value = '';
    });

    applyBtn?.addEventListener('click', function () {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 320,
            height: 320,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        const dataUrl = canvas.toDataURL('image/png', 0.92);
        hiddenInput.value = dataUrl;
        preview.src = dataUrl;
        
        if (bsModal) {
            bsModal.hide();
        }
    });
});
</script>
@endsection
