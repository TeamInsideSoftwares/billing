<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;

class ProfileApprovalsController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $accountid = Auth::user()->accountid;
        $employees = User::where('accountid', $accountid)->orderBy('name')->get();

        $query = UserProfile::with(['user', 'documents'])
            ->where('accountid', $accountid);

        if ($request->filled('employee_id')) {
            $query->where('userid', $request->employee_id);
        }

        $profiles = $query->orderByDesc('created_at')->get();
        $pendingProfiles = $profiles->filter(function ($profile) {
            return strtolower(trim((string) $profile->status)) === 'pending';
        })->values();
        $historyProfiles = $profiles->filter(function ($profile) {
            return strtolower(trim((string) $profile->status)) !== 'pending';
        })->values();

        return view('users.profile-approvals', compact('pendingProfiles', 'historyProfiles', 'employees', 'profiles'));
    }

    public function approve($profileid)
    {
        $profile = UserProfile::with('documents')->where('accountid', Auth::user()->accountid)->findOrFail($profileid);
        $profile->status = 'approved';
        $profile->reviewed_by = Auth::user()->userid;
        $profile->save();

        $photoDoc = $profile->documents()->where('doc_type', 'Photo')->latest()->first();
        if ($photoDoc) {
            $user = User::where('userid', $profile->userid)->first();
            if ($user) {
                $user->update(['profile_image' => $photoDoc->doc_path]);
            }
        }

        return redirect()->route('users.approvals')->with('success', 'Profile approved successfully.');
    }

    public function reject($profileid)
    {
        $profile = UserProfile::where('accountid', Auth::user()->accountid)->findOrFail($profileid);
        $profile->status = 'rejected';
        $profile->reviewed_by = Auth::user()->userid;
        $profile->save();

        return redirect()->route('users.approvals')->with('success', 'Profile rejected successfully.');
    }
}
