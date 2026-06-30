<?php

namespace App\Http\Controllers;

use App\Models\LeavePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeavePolicyController extends Controller
{
    private function policiesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $policies = LeavePolicy::where('accountid', $accountId)->orderBy('policy_name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'policies' => $policies->map(function ($p) {
                return [
                    'leave_policyid' => $p->leave_policyid,
                    'typeid' => $p->typeid,
                    'leave_type_name' => $p->leaveType ? $p->leaveType->name : 'N/A',
                    'policy_name' => $p->policy_name,
                    'description' => $p->description,
                    'carry_forward_limit' => $p->carry_forward_limit,
                    'min_days_per_application' => $p->min_days_per_application,
                    'max_days_per_application' => $p->max_days_per_application,
                    'is_paid' => $p->is_paid,
                    'status' => $p->status,
                ];
            }),
        ]);
    }

    public function index()
    {
        $userAccountId = $this->resolveAccountId();

        return $this->policiesJsonResponse($userAccountId, 'Leave policies fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'typeid' => 'required|string|exists:leave_types,typeid',
            'policy_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'carry_forward_limit' => 'required|integer|min:0',
            'min_days_per_application' => 'required|integer|min:0',
            'max_days_per_application' => 'required|integer|min:0',
            'is_paid' => 'nullable|boolean',
            // status is handled by UI toggle, if included in form handle it or remove required if missing
        ]);

        $validated['is_paid'] = $request->has('is_paid') ? 1 : 0;

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        LeavePolicy::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Leave policy created successfully.');
        }

        return redirect()->back()->with('success', 'Leave policy created successfully.')->with('open_leave_policy_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = LeavePolicy::where('leave_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'typeid' => 'required|string|exists:leave_types,typeid',
            'policy_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'carry_forward_limit' => 'required|integer|min:0',
            'min_days_per_application' => 'required|integer|min:0',
            'max_days_per_application' => 'required|integer|min:0',
            'is_paid' => 'nullable|boolean',
        ]);

        $validated['is_paid'] = $request->has('is_paid') ? 1 : 0;

        $policy->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Leave policy updated successfully.');
        }

        return redirect()->back()->with('success', 'Leave policy updated successfully.')->with('open_leave_policy_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = LeavePolicy::where('leave_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Leave policy deleted successfully.');
        }

        return redirect()->back()->with('success', 'Leave policy deleted successfully.')->with('open_leave_policy_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = LeavePolicy::where('leave_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->update([
            'status' => $policy->status === 'active' ? 'inactive' : 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Leave policy status updated successfully.');
        }

        return redirect()->back()->with('success', 'Leave policy status updated successfully.')->with('open_leave_policy_modal', true);
    }
}
