@extends('layouts.app')

@section('header_actions')
<a href="{{ route('users.index') }}"
    class="btn btn-outline-primary btn-primary text-white d-inline-flex align-items-center gap-1 fw-medium">
    <i class="fas fa-list btn-icon"></i> Users List
</a>
@endsection

@section('content')
<div class="position-relative bg-white p-2 rounded-3">
    <form method="POST" action="{{ isset($userModel) ? route('users.update', $userModel) : route('users.store') }}"
        class="mainForm" enctype="multipart/form-data">
        @isset($userModel)
        @method('PUT')
        @endisset
        @csrf

        <div class="row g-2 align-items-stretch">
            <!-- Column 1: User Information -->
            <div class="col-12 col-lg-3">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">User Information</h5>
                    </div>

                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">User Image</label>
                            <div class="d-flex flex-column align-items-center gap-2">
                                <div class="flex-shrink-0">
                                    <img id="avatarPreview"
                                        class="rounded-circle border border-primary-subtle img-thumbnail"
                                        src="{{ !empty($userModel?->profile_image) ? asset('storage/' . $userModel->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode(old('name', $userModel->name ?? 'User')) . '&background=eff6ff&color=1e3a8a' }}"
                                        alt="User image" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                                <div class="w-100">
                                    <div class="logo-drag-drop-zone border border-dashed rounded-3 text-center bg-white position-relative py-2"
                                        style="cursor:pointer;" id="profile-drop-zone">
                                        <input type="file" id="profile_image" name="profile_image"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                            class="position-absolute top-0 start-0 w-100 h-100 opacity-0">

                                        <div class="drop-zone-prompt d-flex align-items-center justify-content-center"
                                            id="drop-zone-prompt">
                                            <i class="far fa-file text-secondary mb-2 fs-4"></i>
                                            <span class="small text-muted fw-medium ms-2">Drag and drop or <span
                                                    class="text-primary fw-semibold">browse files</span></span>
                                        </div>
                                    </div>
                                    <input type="hidden" id="cropped_image_data" name="cropped_image_data" value="">
                                    <small class="text-muted d-block mt-1 text-center">Upload and crop square profile
                                        image</small>
                                    @error('profile_image') <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    @error('cropped_image_data') <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="name" class="form-label small lh-sm fw-semibold text-dark mb-1">Full Name<span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $userModel->name ?? '') }}" required>
                            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="email" class="form-label small lh-sm fw-semibold text-dark mb-1">Email<span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $userModel->email ?? '') }}" required>
                            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="phone" class="form-label small lh-sm fw-semibold text-dark mb-1">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $userModel->phone ?? '') }}" placeholder="e.g. +91 98xxxxxx">
                            @error('phone') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="depid" class="form-label small lh-sm fw-semibold text-dark mb-1">Department</label>
                            <select id="depid" name="depid" class="form-select">
                                <option value="" {{ old('depid', $userModel->depid ?? '') ? '' : 'selected' }}>Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->depid }}" {{ old('depid', $userModel->depid ?? '') == $dept->depid ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('depid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label for="roleid" class="form-label small lh-sm fw-semibold text-dark mb-1">Role<span class="text-danger">*</span></label>
                            <select id="roleid" name="roleid" class="form-select" required>
                                <option value="" disabled {{ old('roleid', $userModel->roleid ?? '') ? '' : 'selected' }}>Select Role</option>
                                @foreach($roles as $roleObj)
                                    <option value="{{ $roleObj->roleid }}" {{ old('roleid', $userModel->roleid ?? '') == $roleObj->roleid ? 'selected' : '' }}>{{ $roleObj->name }}</option>
                                @endforeach
                            </select>
                            @error('roleid') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $userModel->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold text-dark" for="is_active">Active User</label>
                                @error('is_active') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Additional Details -->
            <div class="col-12 col-lg-9">
                <div class="bg-light p-2 rounded-3 h-100">
                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary small lh-sm mb-0">Permissions & Security</h5>
                    </div>

                    <div class="row g-2">
                        @php($selectedPermissions = old('permissions', $userModel->permissions ?? []))
                        <div class="col-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1">Module Permissions</label>
                            <div class="bg-white p-2 border rounded-3">
                                <div class="row g-3">
                                    @foreach(($groupedPermissions ?? []) as $module => $permissions)
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <h6 class="text-uppercase text-muted small fw-bold mb-2">{{ $module }} Module</h6>
                                            <select class="form-select form-select-sm module-permission-select" data-module="{{ $module }}">
                                                <option value="none">No Permission</option>
                                                <option value="read">Read Only</option>
                                                <option value="full">Full Permission</option>
                                            </select>
                                            <div class="d-none">
                                                @foreach($permissions as $permission)
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="perm-checkbox perm-checkbox-{{ $module }}" data-module="{{ $module }}" {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('permissions') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                @error('permissions.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label small lh-sm fw-semibold text-dark mb-1">{{ isset($userModel) ? 'New Password' : 'Password' }}<span class="text-danger">{{ isset($userModel) ? '' : '*' }}</span></label>
                            <input type="password" id="password" name="password" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6" placeholder="{{ isset($userModel) ? 'Leave blank to keep existing' : 'Minimum 6 characters' }}">
                            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="password_confirmation" class="form-label small lh-sm fw-semibold text-dark mb-1">Confirm Password<span class="text-danger">{{ isset($userModel) ? '' : '*' }}</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" {{ isset($userModel) ? '' : 'required' }} minlength="6">
                        </div>

                        <div class="col-12 mt-3">
                            <label for="notes" class="form-label small lh-sm fw-semibold text-dark mb-1">Notes</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Optional notes about this user">{{ old('notes', $userModel->notes ?? '') }}</textarea>
                            @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-2 d-flex justify-content-end gap-2">
            <a href="{{ route('users.index') }}"
                class="btn btn-outline-primary bg-white text-primary fw-medium px-4">Cancel</a>
            <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium px-4">Save User
                Details</button>
        </div>
    </form>
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

    // 3-Tier Module Permissions Logic
    document.querySelectorAll('.module-permission-select').forEach(select => {
        const module = select.dataset.module;
        const checkboxes = document.querySelectorAll(`.perm-checkbox-${module}`);
        const total = checkboxes.length;
        if (total === 0) return;

        let checkedCount = 0;
        let hasView = false;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                checkedCount++;
                if (cb.value.endsWith('.view')) {
                    hasView = true;
                }
            }
        });

        if (checkedCount === 0) {
            select.value = 'none';
        } else if (checkedCount === total || checkedCount > 1) {
            select.value = 'full';
        } else if (hasView && checkedCount === 1) {
            select.value = 'read';
        } else {
            select.value = 'none';
        }

        select.addEventListener('change', (e) => {
            const val = e.target.value;
            checkboxes.forEach(cb => {
                if (val === 'none') {
                    cb.checked = false;
                } else if (val === 'full') {
                    cb.checked = true;
                } else if (val === 'read') {
                    cb.checked = cb.value.endsWith('.view');
                }
            });
        });
    });
});
</script>
@endsection
