<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountsController extends Controller
{
    private const WIZARD_SESSION_KEY = 'superadmin.account_wizard';

    public function index(): View
    {
        $accounts = Account::query()
            ->with('credential')
            ->orderByDesc('created_at')
            ->get();

        return view('superadmin.index', [
            'title' => 'Accounts',
            'accounts' => $accounts,
        ]);
    }

    public function create(): View
    {
        return view('superadmin.create', [
            'title' => 'Create Account - Step 1',
            'step' => 1,
        ]);
    }

    public function storeStepOne(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'account_email' => ['required', 'email', 'max:150', Rule::unique('accounts', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:150'],
            'currency_code' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive'])],
            'allow_sync' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $request->session()->put(self::WIZARD_SESSION_KEY, [
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'account_email' => strtolower((string) $validated['account_email']),
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'currency_code' => strtoupper((string) $validated['currency_code']),
            'timezone' => $validated['timezone'],
            'status' => $validated['status'],
            'allow_sync' => (bool) ($validated['allow_sync'] ?? false),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()->route('superadmin.create.credentials');
    }

    public function createCredentials(Request $request): View|RedirectResponse
    {
        $draft = $request->session()->get(self::WIZARD_SESSION_KEY);
        if (! $draft) {
            return redirect()->route('superadmin.create')
                ->with('error', 'Please complete Step 1 first.');
        }

        return view('superadmin.create', [
            'title' => 'Create Account - Step 2',
            'step' => 2,
            'draft' => $draft,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $draft = $request->session()->get(self::WIZARD_SESSION_KEY);
        if (! $draft) {
            return redirect()->route('superadmin.create')
                ->with('error', 'Session expired. Please start again.');
        }

        $validated = $request->validate([
            'login_email' => ['required', 'email', 'max:150', Rule::unique('account_users', 'email')],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'password_confirmation' => ['required', 'same:password'],
        ]);

        // Re-check account email uniqueness to prevent race conditions between step1 and step2 submit.
        validator(['account_email' => $draft['account_email'] ?? null], [
            'account_email' => ['required', 'email', 'max:150', Rule::unique('accounts', 'email')],
        ])->validate();

        $loginEmail = strtolower((string) $validated['login_email']);

        DB::transaction(function () use ($draft, $validated, $loginEmail) {
            $account = Account::create([
                'name' => $draft['name'],
                'slug' => $this->buildUniqueSlug($draft['name']),
                'status' => $draft['status'],
                'legal_name' => $draft['legal_name'],
                'email' => $draft['account_email'],
                'phone' => $draft['phone'],
                'website' => $draft['website'],
                'currency_code' => $draft['currency_code'],
                'timezone' => $draft['timezone'],
                'allow_sync' => $draft['allow_sync'],
                'expires_at' => $draft['expires_at'],
            ]);

            // Create an Admin role for this account
            $adminRole = AccountRole::create([
                'accountid' => $account->accountid,
                'name' => 'Admin',
                'status' => 'active',
            ]);

            User::create([
                'accountid' => $account->accountid,
                'name' => $account->name,
                'email' => $loginEmail,
                'password' => $validated['password'],
                'roleid' => $adminRole->roleid,
                'permissions' => UsersController::AVAILABLE_PERMISSIONS,
                'is_active' => true,
            ]);
        });

        $request->session()->forget(self::WIZARD_SESSION_KEY);

        return redirect()->route('superadmin.index')->with('success', 'Account and admin user created successfully.');
    }

    public function edit(Account $account): View
    {
        $account->load('credential');

        return view('superadmin.edit', [
            'title' => 'Edit Account',
            'account' => $account,
        ]);
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $account->load('credential');
        $userId = $account->credential?->userid;
        $loginEmailRule = Rule::unique('account_users', 'email');
        if ($userId) {
            $loginEmailRule = $loginEmailRule->ignore($userId, 'userid');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'account_email' => ['required', 'email', 'max:150', Rule::unique('accounts', 'email')->ignore($account->accountid, 'accountid')],
            'login_email' => ['required', 'email', 'max:150', $loginEmailRule],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:150'],
            'currency_code' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:64'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive'])],
            'allow_sync' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'password' => ['nullable', 'string', 'min:6', 'max:100', 'confirmed'],
        ]);

        $newAccountEmail = strtolower((string) $validated['account_email']);
        $newLoginEmail = strtolower((string) $validated['login_email']);

        DB::transaction(function () use ($account, $validated, $newAccountEmail, $newLoginEmail) {
            $account->update([
                'name' => $validated['name'],
                'legal_name' => $validated['legal_name'] ?? null,
                'email' => $newAccountEmail,
                'phone' => $validated['phone'] ?? null,
                'website' => $validated['website'] ?? null,
                'currency_code' => strtoupper((string) $validated['currency_code']),
                'timezone' => $validated['timezone'],
                'status' => $validated['status'],
                'allow_sync' => (bool) ($validated['allow_sync'] ?? false),
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            $accountUser = $account->credential;
            if ($accountUser) {
                // Create or get Admin role for this account
                $adminRole = AccountRole::firstOrCreate([
                    'accountid' => $account->accountid,
                    'name' => 'Admin',
                    'status' => 'active',
                ], [
                    'accountid' => $account->accountid,
                    'name' => 'Admin',
                    'status' => 'active',
                ]);

                $accountUser->name = $validated['name'];
                $accountUser->email = $newLoginEmail;
                $accountUser->roleid = $adminRole->roleid;
                $accountUser->permissions = UsersController::AVAILABLE_PERMISSIONS;
                $accountUser->is_active = true;

                if (! empty($validated['password'])) {
                    $accountUser->password = $validated['password'];
                }

                $accountUser->save();
            } else {
                // Create an Admin role for this account
                $adminRole = AccountRole::create([
                    'accountid' => $account->accountid,
                    'name' => 'Admin',
                    'status' => 'active',
                ]);

                User::create([
                    'accountid' => $account->accountid,
                    'name' => $validated['name'],
                    'email' => $newLoginEmail,
                    'password' => $validated['password'] ?? Str::random(16),
                    'roleid' => $adminRole->roleid,
                    'permissions' => UsersController::AVAILABLE_PERMISSIONS,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('superadmin.index')->with('success', 'Account updated successfully.');
    }

    private function buildUniqueSlug(string $name): string
    {
        $base = Str::of($name)->trim()->lower()->slug('-')->limit(150, '');
        $slug = $base !== '' ? (string) $base : 'account';
        $counter = 1;

        while (Account::query()->where('slug', $slug)->exists()) {
            $suffix = '-'.$counter;
            $maxBaseLength = 150 - strlen($suffix);
            $slug = substr((string) ($base !== '' ? $base : 'account'), 0, max(1, $maxBaseLength)).$suffix;
            $counter++;
        }

        return $slug;
    }
}
