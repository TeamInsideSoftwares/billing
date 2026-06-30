<?php

namespace App\Http\Controllers;

use App\Models\AttendancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendancePolicyController extends Controller
{
    private function policiesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $policies = AttendancePolicy::where('accountid', $accountId)->orderBy('policy_name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'policies' => $policies->map(function ($p) {
                return [
                    'att_policyid' => $p->att_policyid,
                    'policy_name' => $p->policy_name,
                    'description' => $p->description,
                    'late_arrival_grace' => $p->late_arrival_grace,
                    'early_departure_grace' => $p->early_departure_grace,
                    'overtime_rate' => $p->overtime_rate,
                    'status' => $p->status,
                ];
            }),
        ]);
    }

    public function index()
    {
        $userAccountId = $this->resolveAccountId();

        return $this->policiesJsonResponse($userAccountId, 'Policies fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'policy_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'late_arrival_grace' => 'required|integer|min:0',
            'early_departure_grace' => 'required|integer|min:0',
            // 'overtime_rate' => 'required|numeric|min:0',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        AttendancePolicy::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Policy created successfully.');
        }

        return redirect()->back()->with('success', 'Policy created successfully.')->with('open_policy_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = AttendancePolicy::where('att_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'policy_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'late_arrival_grace' => 'required|integer|min:0',
            'early_departure_grace' => 'required|integer|min:0',
            // 'overtime_rate' => 'required|numeric|min:0',
        ]);

        $policy->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Policy updated successfully.');
        }

        return redirect()->back()->with('success', 'Policy updated successfully.')->with('open_policy_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = AttendancePolicy::where('att_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Attendance policy deleted successfully.');
        }

        return redirect()->back()->with('success', 'Attendance policy deleted successfully.')->with('open_attendance_policy_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = AttendancePolicy::where('att_policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->update([
            'status' => $policy->status === 'active' ? 'inactive' : 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Attendance policy status updated successfully.');
        }

        return redirect()->back()->with('success', 'Attendance policy status updated successfully.')->with('open_attendance_policy_modal', true);
    }
}
