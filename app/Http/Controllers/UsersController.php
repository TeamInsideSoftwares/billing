<?php

namespace App\Http\Controllers;

use App\Mail\UserCredentialsMail;
use App\Models\Account;
use App\Models\AccountDepartment;
use App\Models\AccountRole;
use App\Models\AttendancePolicy;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\RoleLevel;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserDoc;
use App\Models\UserLeavePolicy;
use App\Models\UserProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
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

        $maxLevel = RoleLevel::max('level_value');
        $currentUserLevel = auth()->user()->role?->roleLevel?->level_value ?? 0;

        if ($currentUserLevel < $maxLevel) {
            $query->whereHas('role.roleLevel', function ($q) use ($currentUserLevel) {
                $q->where('level_value', '<', $currentUserLevel);
            });
        }

        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('name', 'like', '%'.$searchTerm.'%')
                    ->orWhere('email', 'like', '%'.$searchTerm.'%');
            });
        }

        $roles = AccountRole::with('roleLevel')
            ->select('account_roles.*')
            ->leftJoin('roles_level', 'account_roles.levelid', '=', 'roles_level.levelid')
            ->where('account_roles.accountid', $accountId)
            ->orderByDesc('roles_level.level_value')
            ->orderBy('account_roles.name')
            ->get();
        $departments = AccountDepartment::where('accountid', $accountId)->orderBy('name')->get();
        $roleLevels = RoleLevel::where('status', 'active')->orderByDesc('level_value')->get();
        $allUsersMap = User::where('accountid', $accountId)->pluck('name', 'userid');

        return view('users.index', [
            'title' => 'Employees',
            'users' => $query->get(),
            'searchTerm' => $searchTerm,
            'roles' => $roles,
            'departments' => $departments,
            'roleLevels' => $roleLevels,
            'allUsersMap' => $allUsersMap,
        ]);
    }

    public function usersCreate(): View
    {
        $accountId = $this->resolveAccountId();
        $roles = AccountRole::with('roleLevel')
            ->select('account_roles.*')
            ->leftJoin('roles_level', 'account_roles.levelid', '=', 'roles_level.levelid')
            ->where('account_roles.accountid', $accountId)
            ->where('account_roles.status', 'active')
            ->orderByDesc('roles_level.level_value')
            ->orderBy('account_roles.name')
            ->get();
        $departments = AccountDepartment::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('accountid', $accountId)->where('status', 'active')->orderBy('shift_name')->get();
        $policies = AttendancePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $account = Account::find($accountId);
        $allAccountUsers = User::with('role.roleLevel')->where('accountid', $accountId)->where('is_active', true)->orderBy('name')->get(['userid', 'name', 'email', 'roleid']);
        $maxLevel = RoleLevel::max('level_value');
        $paidLeaveTypes = LeaveType::where('accountid', $accountId)->where('is_paid_accrued', true)->where('status', 'active')->orderBy('name')->get();

        return view('users.form', [
            'title' => 'Add User',
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
            'roles' => $roles,
            'departments' => $departments,
            'shifts' => $shifts,
            'policies' => $policies,
            'hasTeamManagement' => $account ? $account->has_team_management : false,
            'allAccountUsers' => $allAccountUsers,
            'maxLevel' => $maxLevel,
            'paidLeaveTypes' => $paidLeaveTypes,
        ]);
    }

    public function usersStore(Request $request): RedirectResponse
    {
        $accountId = $this->resolveAccountId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')->where('accountid', $accountId)],
            'roleid' => ['required', 'string'],
            'depid' => ['nullable', 'string'],
            'shiftid' => ['nullable', 'string'],
            'att_policyid' => ['nullable', 'string'],
            'leave_typeid' => ['nullable', 'string'],
            'paid_leaves_pm' => ['nullable', 'numeric', 'min:0'],
            'probation_months' => ['nullable', 'integer', 'min:0'],
            'carry_forward' => ['nullable', 'boolean'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::AVAILABLE_PERMISSIONS)],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['string'],
            'can_assign_clients' => ['nullable', 'boolean'],

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
            'designation' => $validated['designation'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),
            'assigned_users' => array_values($validated['assigned_users'] ?? []),
            'is_active' => true,
            'can_assign_clients' => $request->has('can_assign_clients') ? 1 : 0,
            'password' => $validated['password'],
        ]);

        if (! empty($validated['leave_typeid'])) {
            UserLeavePolicy::create([
                'accountid' => $accountId,
                'userid' => $user->userid,
                'typeid' => $validated['leave_typeid'],
                'leave_per_month' => $validated['paid_leaves_pm'] ?? 0,
                'carry_forward' => $request->has('carry_forward') ? 1 : 0,
                'probation_months' => $validated['probation_months'] ?? 0,
            ]);
        }

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

        try {
            Mail::to($user->email)->send(new UserCredentialsMail($user, $validated['password']));
        } catch (\Exception $e) {
            \Log::error('Failed to send credentials email: '.$e->getMessage());
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function usersEdit(User $user): View
    {
        if ((string) $user->accountid !== $this->resolveAccountId()) {
            abort(403);
        }

        $accountId = $this->resolveAccountId();
        $roles = AccountRole::with('roleLevel')
            ->select('account_roles.*')
            ->leftJoin('roles_level', 'account_roles.levelid', '=', 'roles_level.levelid')
            ->where('account_roles.accountid', $accountId)
            ->where('account_roles.status', 'active')
            ->orderByDesc('roles_level.level_value')
            ->orderBy('account_roles.name')
            ->get();
        $departments = AccountDepartment::where('accountid', $accountId)->where('status', 'active')->orderBy('name')->get();
        $shifts = Shift::where('accountid', $accountId)->where('status', 'active')->orderBy('shift_name')->get();
        $policies = AttendancePolicy::where('accountid', $accountId)->where('status', 'active')->orderBy('policy_name')->get();
        $account = Account::find($accountId);
        $allAccountUsers = User::with('role.roleLevel')->where('accountid', $accountId)
            ->where('is_active', true)
            ->where('userid', '!=', $user->userid)
            ->orderBy('name')
            ->get(['userid', 'name', 'email', 'roleid']);
        $maxLevel = RoleLevel::max('level_value');
        $paidLeaveTypes = LeaveType::where('accountid', $accountId)->where('is_paid_accrued', true)->where('status', 'active')->orderBy('name')->get();
        $user->load('userLeavePolicies');

        return view('users.form', [
            'title' => 'Edit User',
            'userModel' => $user,
            'availablePermissions' => self::AVAILABLE_PERMISSIONS,
            'groupedPermissions' => $this->groupPermissions(self::AVAILABLE_PERMISSIONS),
            'roles' => $roles,
            'departments' => $departments,
            'shifts' => $shifts,
            'policies' => $policies,
            'hasTeamManagement' => $account ? $account->has_team_management : false,
            'allAccountUsers' => $allAccountUsers,
            'maxLevel' => $maxLevel,
            'paidLeaveTypes' => $paidLeaveTypes,
        ]);
    }

    public function usersUpdate(Request $request, User $user): RedirectResponse
    {
        $accountId = $this->resolveAccountId();
        if ((string) $user->accountid !== $accountId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')->where('accountid', $accountId)->ignore($user->userid, 'userid')],
            'roleid' => ['required', 'string'],
            'depid' => ['nullable', 'string'],
            'shiftid' => ['nullable', 'string'],
            'att_policyid' => ['nullable', 'string'],
            'leave_typeid' => ['nullable', 'string'],
            'paid_leaves_pm' => ['nullable', 'numeric', 'min:0'],
            'probation_months' => ['nullable', 'integer', 'min:0'],
            'carry_forward' => ['nullable', 'boolean'],
            'designation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'string',
            'can_assign_clients' => 'nullable|boolean',
            'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
            'documents' => 'nullable|array',
            'documents.*.type' => 'required_with:documents|string|in:Photo,PAN,Identity proof,Bank details',
            'documents.*.file' => 'required_with:documents|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'string',

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
            'designation' => $validated['designation'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),
            'assigned_users' => array_values($validated['assigned_users'] ?? []),
            'can_assign_clients' => $request->has('can_assign_clients') ? 1 : 0,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        UserLeavePolicy::where('userid', $user->userid)->delete();
        if (! empty($validated['leave_typeid'])) {
            UserLeavePolicy::create([
                'accountid' => $accountId,
                'userid' => $user->userid,
                'typeid' => $validated['leave_typeid'],
                'leave_per_month' => $validated['paid_leaves_pm'] ?? 0,
                'carry_forward' => $request->has('carry_forward') ? 1 : 0,
                'probation_months' => $validated['probation_months'] ?? 0,
            ]);
        }

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

    public function unassignedLeaves(Request $request): View
    {
        $accountId = $this->resolveAccountId();

        // Get all userids that are assigned to any manager
        $assignedUserids = User::where('accountid', $accountId)
            ->whereNotNull('assigned_users')
            ->get()
            ->flatMap(function ($user) {
                return is_array($user->assigned_users) ? $user->assigned_users : [];
            })
            ->unique()
            ->filter()
            ->values()
            ->all();

        // Unassigned users are all other active users in the current account
        $unassignedUserids = User::where('accountid', $accountId)
            ->where('is_active', true)
            ->whereNotIn('userid', $assignedUserids)
            ->pluck('userid')
            ->all();

        $unassignedUsers = User::whereIn('userid', $unassignedUserids)->orderBy('name')->get();

        $leaveTypes = LeaveType::where('status', 'active')
            ->where('accountid', $accountId)
            ->orderBy('name')
            ->get();

        $query = LeaveRequest::whereIn('userid', $unassignedUserids)
            ->with(['user', 'leaveType']);

        if ($request->filled('typeid')) {
            $query->where('typeid', $request->typeid);
        }

        if ($request->filled('month')) {
            $query->whereMonth('start_date', $request->month);
        }

        if ($request->filled('date')) {
            $query->whereDate('start_date', $request->date);
        }

        if ($request->filled('employee_id')) {
            $query->where('userid', $request->employee_id);
        }

        $leaves = $query->orderByDesc('created_at')->get();

        return view('users.leaves', [
            'title' => 'Unassigned Leave Approvals',
            'leaves' => $leaves,
            'employees' => $unassignedUsers,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function approveRejectLeave(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $accountId = $this->resolveAccountId();

        // Safety check: ensure target leave belongs to the current account
        if ($leave->accountid !== $accountId) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:1000',
        ]);

        $leave->status = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        $leave->approved_by = auth()->user()->userid;
        if ($validated['action'] === 'reject') {
            $leave->rejection_reason = $validated['rejection_reason'];
        } else {
            $leave->rejection_reason = null;
        }
        $leave->save();

        return redirect()->route('users.leaves.index')->with('success', 'Leave request updated successfully.');
    }
}
