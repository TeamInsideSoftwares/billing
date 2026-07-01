<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountRole;
use App\Models\AttendancePolicy;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserDoc;
use App\Models\UserProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public const AVAILABLE_PERMISSIONS = [
        'dashboard.view',
        'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        'orders.view', 'orders.create', 'orders.edit', 'orders.cancel',
        'quotations.view', 'quotations.create', 'quotations.edit', 'quotations.cancel',
        'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.cancel',
        'payments.view', 'payments.create', 'payments.edit', 'payments.cancel',
        'items.view', 'items.create', 'items.edit', 'items.delete',
        'users.view', 'users.create', 'users.edit', 'users.cancel',
        'settings.view', 'settings.edit',
        'team_work.view',
    ];

    public function users(Request $request): View
    {
        $accountId = $this->resolveAccountId();
        $searchTerm = trim((string) $request->query('search', ''));

        $query = User::query()
            ->where('accountid', $accountId)
            ->with(['role', 'department'])
            ->orderByDesc('created_at');

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%');
            });
        }

        $roles = AccountRole::where('accountid', $accountId)->orderBy('name')->get();
        $departments = AccountDepartment::where('accountid', $accountId)->orderBy('name')->get();
        $leaveTypes = LeaveType::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();

        return view('users.index', [
            'title' => 'Users',
            'users' => $query->get(),
            'searchTerm' => $searchTerm,
            'roles' => $roles,
            'departments' => $departments,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function usersCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $roles = AccountRole::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $departments = AccountDepartment::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('accountid', $accountId)->where('status', 'active')->orderBy('shift_name')->get();
        $policies = AttendancePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $leavePolicies = LeavePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $account = Account::find($accountId);

        return view('users.form', [
            'title' => 'Add User',
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
            'roles' => $roles,
            'departments' => $departments,
            'shifts' => $shifts,
            'policies' => $policies,
            'leavePolicies' => $leavePolicies,
            'hasTeamManagement' => $account ? $account->has_team_management : false,
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $accountId = $this->resolveAccountId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')],
            'roleid' => ['required', 'string'],
            'depid' => ['nullable', 'string'],
            'shiftid' => ['nullable', 'string'],
            'att_policyid' => ['nullable', 'string'],
            'leave_policyid' => ['nullable', 'string'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::AVAILABLE_PERMISSIONS)],

            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
            'documents' => ['nullable', 'array'],
            'documents.*.type' => ['required_with:documents', 'string', 'in:Photo,PAN,Identity proof,Bank details'],
            'documents.*.file' => ['required_with:documents', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],

            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:50',

            'bank_name' => 'nullable|string|max:200',
            'account_name' => 'nullable|string|max:200',
            'account_number' => 'nullable|string|max:100',
            'routing_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:200',
        ]);

        $user = User::create([
            'accountid' => $accountId,
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'roleid' => $validated['roleid'],
            'depid' => $validated['depid'] ?? null,
            'shiftid' => $validated['shiftid'] ?? null,
            'att_policyid' => $validated['att_policyid'] ?? null,
            'leave_policyid' => $validated['leave_policyid'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),
            'is_active' => true,
            'password' => $validated['password'],
        ]);

        if (! empty($validated['documents'])) {
            $profile = UserProfile::firstOrCreate([
                'userid' => $user->userid,
            ], [
                'accountid' => $accountId,
                'status' => 'pending',
            ]);

            foreach ($validated['documents'] as $doc) {
                if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                    $path = $doc['file']->store('users/documents', 'public');
                    UserDoc::create([
                        'profileid' => $profile->profileid,
                        'doc_type' => $doc['type'],
                        'doc_path' => $path,
                    ]);
                    if ($doc['type'] === 'Photo') {
                        $user->update(['profile_image' => $path]);
                    }
                }
            }
        }

        $profileData = $request->only([
            'address', 'city', 'state', 'country', 'zip_code',
            'bank_name', 'account_name', 'account_number', 'routing_code', 'bank_branch',
        ]);

        if (array_filter($profileData)) {
            $profile = UserProfile::firstOrCreate([
                'userid' => $user->userid,
            ], [
                'accountid' => $accountId,
                'status' => 'approved', // Created by admin, so defaults to approved or pending? Usually admin editing means approved. We can leave it or set it based on current status.
            ]);
            $profile->update($profileData);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function usersEdit(User $user): View
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        $accountId = $this->resolveAccountId();
        $roles = AccountRole::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $departments = AccountDepartment::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('accountid', $accountId)->where('status', 'active')->orderBy('shift_name')->get();
        $policies = AttendancePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $leavePolicies = LeavePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $account = Account::find($accountId);

        return view('users.form', [
            'title' => 'Edit User',
            'userModel' => $user,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
            'roles' => $roles,
            'departments' => $departments,
            'shifts' => $shifts,
            'policies' => $policies,
            'leavePolicies' => $leavePolicies,
            'hasTeamManagement' => $account ? $account->has_team_management : false,
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
            'roleid' => ['required', 'string'],
            'depid' => ['nullable', 'string'],
            'shiftid' => ['nullable', 'string'],
            'att_policyid' => ['nullable', 'string'],
            'leave_policyid' => ['nullable', 'string'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
            'documents' => 'nullable|array',
            'documents.*.type' => 'required_with:documents|string|in:Photo,PAN,Identity proof,Bank details',
            'documents.*.file' => 'required_with:documents|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',

            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:50',

            'bank_name' => 'nullable|string|max:200',
            'account_name' => 'nullable|string|max:200',
            'account_number' => 'nullable|string|max:100',
            'routing_code' => 'nullable|string|max:50',
            'bank_branch' => 'nullable|string|max:200',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'roleid' => $validated['roleid'],
            'depid' => $validated['depid'] ?? null,
            'shiftid' => $validated['shiftid'] ?? null,
            'att_policyid' => $validated['att_policyid'] ?? null,
            'leave_policyid' => $validated['leave_policyid'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),

        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        if (! empty($validated['documents'])) {
            $profile = UserProfile::firstOrCreate([
                'userid' => $user->userid,
            ], [
                'accountid' => $this->resolveAccountId(),
                'status' => 'pending',
            ]);

            if (! empty($validated['delete_documents'])) {
                $docsToDelete = UserDoc::whereIn('docid', $validated['delete_documents'])
                    ->where('profileid', $profile->profileid)
                    ->get();

                foreach ($docsToDelete as $docToDelete) {
                    Storage::disk('public')->delete($docToDelete->doc_path);
                    $docToDelete->delete();
                }
            }

            foreach ($validated['documents'] as $doc) {
                if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                    $path = $doc['file']->store('users/documents', 'public');
                    UserDoc::create([
                        'profileid' => $profile->profileid,
                        'doc_type' => $doc['type'],
                        'doc_path' => $path,
                    ]);
                    if ($doc['type'] === 'Photo') {
                        $user->update(['profile_image' => $path]);
                    }
                }
            }
        }

        $profileData = $request->only([
            'address', 'city', 'state', 'country', 'zip_code',
            'bank_name', 'account_name', 'account_number', 'routing_code', 'bank_branch',
        ]);

        if (array_filter($profileData)) {
            $profile = UserProfile::firstOrCreate([
                'userid' => $user->userid,
            ], [
                'accountid' => $this->resolveAccountId(),
                'status' => 'pending',
            ]);
            $profile->update($profileData);
        }

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

    public function usersToggleStatus(User $user): RedirectResponse
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return redirect()->back()->with('success', 'User status toggled successfully.');
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
        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
        if ($binary === false) {
            return null;
        }

        $fileName = 'users/profile-images/'.Str::lower(Str::random(24)).'.'.($extension === 'jpeg' ? 'jpg' : $extension);
        Storage::disk('public')->put($fileName, $binary);

        return $fileName;
    }
}
