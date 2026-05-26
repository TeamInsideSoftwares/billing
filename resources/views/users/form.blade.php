@extends('layouts.app')

@section('header_actions')
    <a href="{{ route('users.index') }}" class="secondary-button">
        <i class="fas fa-arrow-left icon-spaced"></i>Back to Users
    </a>
@endsection

@section('content')
<section class="panel-card">
    <form method="POST" action="{{ isset($userModel) ? route('users.update', $userModel) : route('users.store') }}" class="client-form" enctype="multipart/form-data">
        @csrf
        @isset($userModel)
            @method('PUT')
        @endisset

        <style>
            .user-avatar-card {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 0.9rem;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #f8fbff;
                margin-bottom: 1rem;
            }
            .user-avatar-preview {
                width: 68px;
                height: 68px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #dbeafe;
                background: #eef2ff;
            }
            .cropper-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.62);
                backdrop-filter: blur(3px);
                z-index: 1200;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1.2rem;
            }
            .user-cropper-modal {
                width: min(860px, 96vw);
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
                padding: 0;
                overflow: hidden;
            }
            .user-cropper-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.9rem 1rem;
                border-bottom: 1px solid #e2e8f0;
                background: #f8fafc;
            }
            .user-cropper-modal-title {
                margin: 0;
                font-size: 1rem;
                font-weight: 700;
                color: #0f172a;
            }
            .cropper-close-btn {
                border: 0;
                background: #e2e8f0;
                color: #0f172a;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                font-size: 1.15rem;
                line-height: 1;
                cursor: pointer;
            }
            .user-cropper-modal-body {
                padding: 1rem;
            }
            .cropper-image-wrap {
                height: min(64vh, 560px);
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f1f5f9;
                margin-bottom: 1rem;
            }
            .cropper-image-wrap img {
                max-width: 100%;
                display: block;
            }
            .user-cropper-modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 0.6rem;
                padding: 0.9rem 1rem;
                border-top: 1px solid #e2e8f0;
                background: #fff;
            }
            .user-active-field {
                display: flex;
                flex-direction: column;
            }
            .user-active-control {
                min-height: 40px;
                display: flex;
                align-items: center;
                padding-top: 0.2rem;
            }
            .user-active-control .service-check {
                min-height: 40px;
                width: fit-content;
                padding: 0.45rem 0.65rem;
            }

            /* Force crisp cropper modal: dim page behind, not modal content */
            .cropper-backdrop {
                background: transparent !important;
                isolation: isolate;
            }
            .cropper-backdrop::before {
                content: "";
                position: absolute;
                inset: 0;
                background: rgba(15, 23, 42, 0.42);
                backdrop-filter: blur(3px);
                z-index: 0;
            }
            .user-cropper-modal {
                position: relative;
                z-index: 1;
            }
            .user-cropper-modal .cropper-image-wrap img,
            .user-cropper-modal .cropper-canvas img,
            .user-cropper-modal .cropper-view-box img {
                opacity: 1 !important;
                filter: none !important;
            }
        </style>

        <div class="user-avatar-card">
            <img
                id="avatarPreview"
                class="user-avatar-preview"
                src="{{ !empty($userModel?->profile_image) ? asset('storage/' . $userModel->profile_image) : 'https://ui-avatars.com/api/?name=' . urlencode(old('name', $userModel->name ?? 'User')) . '&background=eff6ff&color=1e3a8a' }}"
                alt="User image"
            >
            <div>
                <label for="profile_image" style="font-weight:600;">User Image</label>
                <input type="file" id="profile_image" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <input type="hidden" id="cropped_image_data" name="cropped_image_data" value="">
                <small class="text-muted d-block mt-1">Upload and crop square profile image</small>
                @error('profile_image') <span class="error">{{ $message }}</span> @enderror
                @error('cropped_image_data') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="form-grid">
            <div>
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $userModel->name ?? '') }}" required>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $userModel->email ?? '') }}" required>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="department">Department *</label>
                <input type="text" id="department" name="department" value="{{ old('department', $userModel->department ?? '') }}" required placeholder="e.g. Sales, Support, Finance">
                @error('department') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="designation">Designation</label>
                <input type="text" id="designation" name="designation" value="{{ old('designation', $userModel->designation ?? '') }}" placeholder="e.g. Sr. Executive">
                @error('designation') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $userModel->phone ?? '') }}" placeholder="e.g. +91 98xxxxxx">
                @error('phone') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="role">Role *</label>
                <select id="role" name="role" required>
                    @php($selectedRole = old('role', $userModel->role ?? 'staff'))
                    <option value="admin" {{ $selectedRole === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="manager" {{ $selectedRole === 'manager' ? 'selected' : '' }}>Manager</option>
                    <option value="staff" {{ $selectedRole === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
                @error('role') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="user-active-field">
                <label for="is_active">Active User</label>
                <div class="user-active-control">
                    <label class="custom-checkbox service-check d-flex align-items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $userModel->is_active ?? true) ? 'checked' : '' }}>
                    </label>
                </div>
                @error('is_active') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-2">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Optional notes about this user">{{ old('notes', $userModel->notes ?? '') }}</textarea>
                @error('notes') <span class="error">{{ $message }}</span> @enderror
            </div>
        </div>

        <hr>

        @php($selectedPermissions = old('permissions', $userModel->permissions ?? []))
        <div class="mb-3">
            <label class="mb-2 d-block">Permissions</label>
            @foreach(($groupedPermissions ?? []) as $module => $permissions)
                <div class="mb-3">
                    <p class="eyebrow mb-2">{{ $module }} Module</p>
                    <div class="form-grid">
                        @foreach($permissions as $permission)
                            @php($action = ucfirst(explode('.', $permission)[1] ?? $permission))
                            <label class="custom-checkbox service-check d-flex align-items-center gap-2" style="min-height:44px;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}>
                                <span>{{ $action }} <small class="text-muted">({{ $permission }})</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
            @error('permissions') <span class="error">{{ $message }}</span> @enderror
            @error('permissions.*') <span class="error">{{ $message }}</span> @enderror
        </div>

        <hr>

        <div class="form-grid">
            <div>
                <label for="password">{{ isset($userModel) ? 'New Password' : 'Password *' }}</label>
                <input type="password" id="password" name="password" {{ isset($userModel) ? '' : 'required' }} minlength="6" placeholder="{{ isset($userModel) ? 'Leave blank to keep existing' : 'Minimum 6 characters' }}">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="password_confirmation">Confirm Password {{ isset($userModel) ? '' : '*' }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" {{ isset($userModel) ? '' : 'required' }} minlength="6">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="primary-button">{{ isset($userModel) ? 'Update User' : 'Create User' }}</button>
            <a href="{{ route('users.index') }}" class="text-link">Cancel</a>
        </div>
    </form>
</section>

<div id="cropperBackdrop" class="cropper-backdrop">
    <div class="user-cropper-modal" role="dialog" aria-modal="true" aria-labelledby="cropperModalTitle">
        <div class="user-cropper-modal-header">
            <h6 id="cropperModalTitle" class="user-cropper-modal-title">Crop User Image</h6>
            <button type="button" class="cropper-close-btn" id="closeCropBtn" aria-label="Close cropper">&times;</button>
        </div>
        <div class="user-cropper-modal-body">
            <div class="cropper-image-wrap">
                <img id="cropperImage" src="" alt="Crop target">
            </div>
        </div>
        <div class="user-cropper-modal-footer">
            <button type="button" class="secondary-button" id="cancelCropBtn">Cancel</button>
            <button type="button" class="primary-button" id="applyCropBtn">Apply Crop</button>
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
    const backdrop = document.getElementById('cropperBackdrop');
    const modal = backdrop?.querySelector('.user-cropper-modal');
    const cropperImage = document.getElementById('cropperImage');
    const closeBtn = document.getElementById('closeCropBtn');
    const cancelBtn = document.getElementById('cancelCropBtn');
    const applyBtn = document.getElementById('applyCropBtn');
    let cropper = null;
    let objectUrl = null;

    const closeCropper = function () {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
        backdrop.style.display = 'none';
    };

    fileInput?.addEventListener('change', function (event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;

        objectUrl = URL.createObjectURL(file);
        cropperImage.src = objectUrl;
        backdrop.style.display = 'flex';

        setTimeout(function () {
            if (cropper) cropper.destroy();
            cropper = new Cropper(cropperImage, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                background: false
            });
        }, 50);
    });

    cancelBtn?.addEventListener('click', function () {
        fileInput.value = '';
        hiddenInput.value = '';
        closeCropper();
    });
    closeBtn?.addEventListener('click', function () {
        fileInput.value = '';
        hiddenInput.value = '';
        closeCropper();
    });
    backdrop?.addEventListener('click', function (event) {
        if (event.target === backdrop) {
            fileInput.value = '';
            hiddenInput.value = '';
            closeCropper();
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && backdrop.style.display === 'flex') {
            fileInput.value = '';
            hiddenInput.value = '';
            closeCropper();
        }
    });
    modal?.addEventListener('click', function (event) {
        event.stopPropagation();
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
        closeCropper();
    });
});
</script>
@endsection
