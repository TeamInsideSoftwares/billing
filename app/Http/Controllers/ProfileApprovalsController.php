<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Auth;

class ProfileApprovalsController extends Controller
{
    public function index()
    {
        $pendingProfiles = UserProfile::with('user')
            ->where('accountid', Auth::user()->accountid)
            ->where('status', 'pending')
            ->get();

        return view('users.approvals', compact('pendingProfiles'));
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
