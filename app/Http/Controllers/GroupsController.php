<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    private function groupsJsonResponse(string $accountId, string $message): JsonResponse
    {
        $groups = Group::where('accountid', $accountId)->orderBy('group_name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'groups' => $groups->map(function ($g) {
                return [
                    'groupid' => $g->groupid,
                    'group_name' => $g->group_name,
                    'email' => $g->email,
                    'registered_address' => $g->registered_address,
                    'city' => $g->city,
                    'state' => $g->state,
                    'postal_code' => $g->postal_code,
                    'country' => $g->country,
                    'business_address' => $g->business_address,
                    'business_city' => $g->business_city,
                    'business_state' => $g->business_state,
                    'business_postal_code' => $g->business_postal_code,
                    'business_country' => $g->business_country,
                ];
            }),
        ]);
    }

    public function groupsStore(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string',
            'email' => 'nullable|email',
            'registered_address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'business_address' => 'nullable|string',
            'business_city' => 'nullable|string',
            'business_state' => 'nullable|string',
            'business_postal_code' => 'nullable|string',
            'business_country' => 'nullable|string',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        Group::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->groupsJsonResponse($userAccountId, 'Group created successfully.');
        }

        return redirect()->back()->with('success', 'Group created successfully.')->with('open_group_modal', true);
    }

    public function groupsUpdate(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $group = Group::where('groupid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'group_name' => 'required|string',
            'email' => 'nullable|email',
            'registered_address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country' => 'nullable|string',
            'business_address' => 'nullable|string',
            'business_city' => 'nullable|string',
            'business_state' => 'nullable|string',
            'business_postal_code' => 'nullable|string',
            'business_country' => 'nullable|string',
        ]);

        $group->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->groupsJsonResponse($userAccountId, 'Group updated successfully.');
        }

        return redirect()->back()->with('success', 'Group updated successfully.')->with('open_group_modal', true);
    }

    public function groupsDestroy(Group $group)
    {
        if ($group->accountid !== $this->resolveAccountId()) {
            abort(403);
        }
        $group->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->groupsJsonResponse($this->resolveAccountId(), 'Group deleted successfully.');
        }

        return redirect()->back()->with('success', 'Group deleted successfully.')->with('open_group_modal', true);
    }
}
