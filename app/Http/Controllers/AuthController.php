<?php

namespace App\Http\Controllers;

use App\Models\AccountCredential;
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

        $superadminValid = $this->isSuperadminCredentialValid($email, $password);
        $panelCredential = AccountCredential::query()
            ->where('email', $email)
            ->first();
        $panelValid = $panelCredential instanceof AccountCredential
            && Hash::check($password, (string) $panelCredential->password);

        if ($superadminValid && $panelValid && $panelCredential) {
            $request->session()->put('login_choice', [
                'email' => $email,
                'account_credential_id' => $panelCredential->getKey(),
                'remember' => $remember,
            ]);

            return redirect()->route('login.choice');
        }

        if ($superadminValid) {
            $this->markSuperadminSession($request, $email);
            return redirect()->route('superadmin.index')->with('success', 'Superadmin login successful.');
        }

        if ($panelValid && $panelCredential) {
            return $this->loginToPanel($request, $panelCredential, $remember);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showLoginChoice(Request $request)
    {
        if (!$request->session()->has('login_choice')) {
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
        $credentialId = (int) ($choice['account_credential_id'] ?? 0);
        $remember = (bool) ($choice['remember'] ?? false);

        if ($email === '' || $credentialId <= 0) {
            $request->session()->forget('login_choice');
            return redirect()->route('login')->withErrors(['email' => 'Login session expired. Please sign in again.']);
        }

        $request->session()->forget('login_choice');

        if ($validated['target'] === 'superadmin') {
            $this->markSuperadminSession($request, $email);
            return redirect()->route('superadmin.index')->with('success', 'Superadmin login successful.');
        }

        $credential = AccountCredential::query()->find($credentialId);
        if (!$credential) {
            return redirect()->route('login')->withErrors(['email' => 'Panel account no longer exists.']);
        }

        return $this->loginToPanel($request, $credential, $remember);
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
            function (AccountCredential $credential, string $password): void {
                $credential->forceFill([
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

        $credential = $request->user();

        if (!($credential instanceof AccountCredential)) {
            abort(403);
        }

        if (!Hash::check((string) $validated['current_password'], $credential->password)) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->withInput();
        }

        $credential->update([
            'password' => (string) $validated['password'],
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    private function isSuperadminCredentialValid(string $email, string $plainPassword): bool
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
            return false;
        }

        if (!$record) {
            return false;
        }

        $storedPassword = (string) data_get($record, $passwordColumn, '');
        if ($storedPassword === '') {
            return false;
        }

        // Support both password_hash() and legacy SHA-512 hex storage.
        $phpPasswordHashValid = password_verify($plainPassword, $storedPassword);
        $legacySha512Valid = hash_equals(strtolower($storedPassword), strtolower(hash('sha512', $plainPassword)));

        if (!$phpPasswordHashValid && !$legacySha512Valid) {
            return false;
        }

        return true;
    }

    private function markSuperadminSession(Request $request, string $email): void
    {
        $request->session()->regenerate();
        $request->session()->forget('login_choice');
        $request->session()->put('superadmin_authenticated', true);
        $request->session()->put('superadmin_email', $email);
    }

    private function loginToPanel(Request $request, AccountCredential $credential, bool $remember)
    {
        Auth::login($credential, $remember);
        $request->session()->regenerate();
        $request->session()->forget(['superadmin_authenticated', 'superadmin_email', 'login_choice']);

        $account = $credential->account;
        if ($account && $account->expires_at && now()->startOfDay()->gt($account->expires_at->startOfDay())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'This account expired on ' . $account->expires_at->format('d M Y') . '. Please contact superadmin.',
            ])->onlyInput('email');
        }

        return redirect()->route('dashboard')->with('success', 'Logged in successfully.');
    }
}
