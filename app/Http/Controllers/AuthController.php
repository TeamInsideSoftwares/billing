<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login', ['title' => 'Sign In']);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower((string) $validated['email']);
        $password = (string) $validated['password'];
        $remember = $request->boolean('remember');

        $superadminStatus = $this->validateSuperadminCredential($email, $password);
        $superadminValid = $superadminStatus === 'valid';

        $panelUser = User::query()
            ->with('account')
            ->where('email', $email)
            ->first();

        $panelPasswordValid = $panelUser instanceof User
            && Hash::check($password, (string) $panelUser->password);

        $panelValid = false;

        if ($panelPasswordValid) {
            if (! $panelUser->is_active || ($panelUser->account && $panelUser->account->status !== 'active')) {
                if (! $superadminValid) {
                    return back()->withErrors([
                        'email' => 'Your account is inactive. Please contact support.',
                    ])->onlyInput('email');
                }
            } else {
                $panelValid = true;
            }
        }

        if ($superadminValid && $panelValid && $panelUser) {
            $request->session()->put('login_choice', [
                'email' => $email,
                'panel_user_id' => $panelUser->getKey(),
                'remember' => $remember,
            ]);

            return redirect()->route('login.choice');
        }

        if ($superadminValid) {
            $this->markSuperadminSession($request, $email);

            return redirect()->route('superadmin.index')->with('success', 'Superadmin login successful.');
        }

        if ($panelValid && $panelUser) {
            return $this->loginToPanel($request, $panelUser, $remember);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showLoginChoice(Request $request)
    {
        if (! $request->session()->has('login_choice')) {
            return redirect()->route('login');
        }

        return view('auth.login-choice', ['title' => 'Choose Access']);
    }

    public function loginChoice(Request $request)
    {
        $validated = $request->validate([
            'target' => ['required', 'in:superadmin,panel'],
        ]);

        $choice = (array) $request->session()->get('login_choice', []);
        $email = (string) ($choice['email'] ?? '');
        $userId = (string) ($choice['panel_user_id'] ?? '');
        $remember = (bool) ($choice['remember'] ?? false);

        if ($email === '' || $userId === '') {
            $request->session()->forget('login_choice');

            return redirect()->route('login')->withErrors(['email' => 'Login session expired. Please sign in again.']);
        }

        $request->session()->forget('login_choice');

        if ($validated['target'] === 'superadmin') {
            $this->markSuperadminSession($request, $email);

            return redirect()->route('superadmin.index')->with('success', 'Superadmin login successful.');
        }

        $user = User::query()
            ->with('account')
            ->find($userId);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Panel account no longer exists.']);
        }

        if (! $user->is_active || ($user->account && $user->account->status !== 'active')) {
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact support.']);
        }

        return $this->loginToPanel($request, $user, $remember);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->forget(['superadmin_authenticated', 'superadmin_email']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password', ['title' => 'Forgot Password']);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', __((string) $status));
        }

        return back()->withErrors(['email' => __((string) $status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __('Your password has been reset successfully.'));
        }

        return back()->withErrors(['email' => [__((string) $status)]])->withInput($request->only('email'));
    }

    public function showChangePassword()
    {
        return view('auth.change-password', ['title' => 'Change Password']);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed', 'different:current_password'],
        ]);

        $user = $request->user();

        if (! ($user instanceof User)) {
            abort(403);
        }

        if (! Hash::check((string) $validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->withInput();
        }

        $user->update([
            'password' => (string) $validated['password'],
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    private function validateSuperadminCredential(string $email, string $plainPassword): string
    {
        $connection = (string) env('ADMIN_DB_CONNECTION', 'admin_mysql');
        $table = (string) env('ADMIN_LOGIN_TABLE', 'adminlogin');
        $userColumn = (string) env('ADMIN_LOGIN_USER_COLUMN', 'email');
        $passwordColumn = (string) env('ADMIN_LOGIN_PASSWORD_COLUMN', 'password');

        try {
            $record = DB::connection($connection)
                ->table($table)
                ->where($userColumn, $email)
                ->first();
        } catch (\Throwable) {
            return 'invalid';
        }

        if (! $record) {
            return 'invalid';
        }

        $storedPassword = (string) data_get($record, $passwordColumn, '');
        if ($storedPassword === '') {
            return 'invalid';
        }

        // Support both password_hash() and legacy SHA-512 hex storage.
        $phpPasswordHashValid = password_verify($plainPassword, $storedPassword);
        $legacySha512Valid = hash_equals(strtolower($storedPassword), strtolower(hash('sha512', $plainPassword)));

        if (! $phpPasswordHashValid && ! $legacySha512Valid) {
            return 'invalid';
        }

        if (isset($record->status) && strtolower($record->status) !== 'active') {
            return 'inactive';
        }

        return 'valid';
    }

    private function markSuperadminSession(Request $request, string $email): void
    {
        $request->session()->regenerate();
        $request->session()->forget('login_choice');
        $request->session()->put('superadmin_authenticated', true);
        $request->session()->put('superadmin_email', $email);
    }

    private function loginToPanel(Request $request, User $user, bool $remember)
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget(['superadmin_authenticated', 'superadmin_email', 'login_choice']);

        $account = $user->account;
        if ($account && $account->expires_at && now()->startOfDay()->gt($account->expires_at->startOfDay())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'This account expired on '.$account->expires_at->format('d M Y').'. Please contact superadmin.',
            ])->onlyInput('email');
        }

        $hasTeamWork = $user->hasPermission('team_work.view');

        $account = $user->account;
        if ($account && ! $account->has_team_management) {
            $hasTeamWork = false;
        }

        $otherPermissions = array_filter($user->permissions ?? [], fn ($p) => $p !== 'team_work.view');
        $hasBilling = count($otherPermissions) > 0 || ($user->role && $user->role->name === 'Admin');

        if ($hasTeamWork && $hasBilling) {
            return redirect('http://alpha.skoolready.com/billing/app-choice');
        } elseif ($hasTeamWork) {
            return redirect(config('app.team_url').'/dashboard')->with('success', 'Logged in successfully.');
        }

        return redirect('http://alpha.skoolready.com/billing')->with('success', 'Logged in successfully.');
    }

    public function appChoice(Request $request)
    {
        return view('auth.app-choice', ['title' => 'Choose Application']);
    }

    public function loginAs(User $user, Request $request)
    {
        // Only allow if current user has users.view permission or Admin role.
        $currentUser = auth()->user();
        $hasPermission = $currentUser && ($currentUser->hasPermission('users.view') || (isset($currentUser->role) && $currentUser->role->name === 'Admin'));

        if (! $hasPermission) {
            abort(403, 'Unauthorized action.');
        }

        // Target user must be explicitly in the logged-in user's assigned_users list.
        $assignedUsers = is_array($currentUser->assigned_users) ? $currentUser->assigned_users : [];
        if (! in_array($user->userid, $assignedUsers, true)) {
            abort(403, 'Unauthorized action.');
        }

        $request->session()->put('impersonating_user', $user->userid);

        return redirect(config('app.team_url').'/dashboard')->with('success', 'You are now impersonating '.$user->name);
    }

    public function leaveImpersonation(Request $request)
    {
        $request->session()->forget('impersonating_user');

        return redirect()->route('users.index')->with('success', 'Left impersonation.');
    }
}
