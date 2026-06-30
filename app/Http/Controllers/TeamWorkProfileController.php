<?php

namespace App\Http\Controllers;

use App\Models\UserDoc;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeamWorkProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $profile = UserProfile::where('userid', $user->userid)->first();

        return view('team-work.profile.edit', [
            'profile' => $profile,
            'title' => 'My Profile',
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
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

            'documents' => 'nullable|array',
            'documents.*.type' => 'required_with:documents|string|in:Photo,PAN,Identity proof,Bank details',
            'documents.*.file' => 'required_with:documents|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',

            'delete_documents' => 'nullable|array',
            'delete_documents.*' => 'integer',
        ]);

        $profile = UserProfile::firstOrNew(['userid' => $user->userid]);
        $profile->accountid = $user->accountid;
        $profile->fill($validated);
        $profile->status = 'pending';
        $profile->save();

        if (! empty($validated['delete_documents'])) {
            $docsToDelete = UserDoc::whereIn('docid', $validated['delete_documents'])
                ->where('profileid', $profile->profileid)
                ->get();

            foreach ($docsToDelete as $docToDelete) {
                // Optionally delete the file from storage
                Storage::disk('public')->delete($docToDelete->doc_path);
                $docToDelete->delete();
            }
        }

        if (! empty($validated['documents'])) {
            foreach ($validated['documents'] as $doc) {
                if (isset($doc['file']) && $doc['file'] instanceof UploadedFile) {
                    $path = $doc['file']->store('users/documents', 'public');
                    UserDoc::create([
                        'profileid' => $profile->profileid,
                        'doc_type' => $doc['type'],
                        'doc_path' => $path,
                    ]);
                }
            }
        }

        return redirect()->route('team-work.dashboard')->with('success', 'Profile details submitted for review successfully.');
    }
}
