<?php

namespace App\Http\Controllers;

use App\Models\AccountPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPolicyController extends Controller
{
    private function policiesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $policies = AccountPolicy::with('component')->where('accountid', $accountId)->orderBy('title')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'policies' => $policies,
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
            'componentid' => 'required|string|exists:payroll_components,componentid',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        AccountPolicy::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Policy created successfully.');
        }

        return redirect()->back()->with('success', 'Policy created successfully.')->with('open_policy_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = AccountPolicy::where('policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'componentid' => 'required|string|exists:payroll_components,componentid',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules' => 'nullable|array',
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
        $policy = AccountPolicy::where('policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Policy deleted successfully.');
        }

        return redirect()->back()->with('success', 'Policy deleted successfully.')->with('open_policy_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $policy = AccountPolicy::where('policyid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $policy->update([
            'status' => $policy->status ? 0 : 1,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->policiesJsonResponse($userAccountId, 'Policy status updated successfully.');
        }

        return redirect()->back()->with('success', 'Policy status updated successfully.')->with('open_policy_modal', true);
    }
}
