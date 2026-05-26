<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    private const AVAILABLE_PERMISSIONS = [
        'dashboard.view',
        'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'orders.view', 'orders.create', 'orders.edit', 'orders.cancel',
        'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.cancel',
        'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.cancel',
        'payments.view', 'payments.create', 'payments.edit', 'payments.cancel',
        'items.view', 'items.create', 'items.edit', 'items.delete',
        'users.view', 'users.create', 'users.edit', 'users.cancel',
        'settings.view', 'settings.edit',
    ];

    public function users(Request $request): View
    {
        $accountId = $this->resolveAccountId();
        $searchTerm = trim((string) $request->query('search', ''));

        $query = User::query()
            ->where('accountid', $accountId)
            ->orderByDesc('created_at');

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('department', 'like', '%' . $searchTerm . '%')
                    ->orWhere('designation', 'like', '%' . $searchTerm . '%')
                    ->orWhere('role', 'like', '%' . $searchTerm . '%');
            });
        }

        return view('users.index', [
            'title' => 'Users',
            'users' => $query->get(),
            'searchTerm' => $searchTerm,
        ]);
    }

    public function usersCreate(): View
    {
        return view('users.form', [
            'title' => 'Add User',
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $accountId = $this->resolveAccountId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cropped_image_data' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'role' => ['required', Rule::in(['admin', 'manager', 'staff'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::AVAILABLE_PERMISSIONS)],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ]);

        $profileImagePath = null;
        if (!empty($validated['cropped_image_data'])) {
            $profileImagePath = $this->storeCroppedImage((string) $validated['cropped_image_data']);
        } elseif ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('users/profile-images', 'public');
        }

        User::create([
            'accountid' => $accountId,
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'profile_image' => $profileImagePath,
            'department' => $validated['department'],
            'phone' => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'role' => $validated['role'],
            'permissions' => array_values($validated['permissions'] ?? []),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'password' => $validated['password'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function usersEdit(User $user): View
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        return view('users.form', [
            'title' => 'Edit User',
            'userModel' => $user,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
        ]);
    }

    public function usersUpdate(Request $request, User $user): RedirectResponse
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')->ignore($user->userid, 'userid')],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cropped_image_data' => ['nullable', 'string'],
            'department' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'role' => ['required', Rule::in(['admin', 'manager', 'staff'])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::AVAILABLE_PERMISSIONS)],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'department' => $validated['department'],
            'phone' => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'role' => $validated['role'],
            'permissions' => array_values($validated['permissions'] ?? []),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (!empty($validated['cropped_image_data'])) {
            $newPath = $this->storeCroppedImage((string) $validated['cropped_image_data']);
            if (!empty($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $payload['profile_image'] = $newPath;
        } elseif ($request->hasFile('profile_image')) {
            $newPath = $request->file('profile_image')->store('users/profile-images', 'public');
            if (!empty($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $payload['profile_image'] = $newPath;
        }

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function usersDestroy(User $user): RedirectResponse
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        $user->update([
            'is_active' => false,
        ]);

        return redirect()->route('users.index')->with('success', 'User cancelled successfully.');
    }

    private function groupPermissions(array $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $moduleKey = ucfirst(str_replace('_', ' ', explode('.', (string) $permission)[0] ?? 'General'));
            $grouped[$moduleKey][] = $permission;
        }

        return $grouped;
    }

    private function storeCroppedImage(string $dataUri): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
        if ($binary === false) {
            return null;
        }

        $fileName = 'users/profile-images/' . Str::lower(Str::random(24)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }
}
