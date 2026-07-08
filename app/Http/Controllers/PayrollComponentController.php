<?php

namespace App\Http\Controllers;

use App\Models\PayrollComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollComponentController extends Controller
{
    private function componentsJsonResponse(string $accountId, string $message): JsonResponse
    {
        $components = PayrollComponent::where('accountid', $accountId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'components' => $components,
        ]);
    }

    public function index()
    {
        $userAccountId = $this->resolveAccountId();

        return $this->componentsJsonResponse($userAccountId, 'Components fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_type' => 'required|string|in:attendance,leave,security_deposit,general_earning,general_deduction',
            'type' => 'required|string|in:earning,deduction',
            'calculation_type' => 'nullable|string|in:fixed,percentage',
            'calculation_value' => 'nullable|numeric|min:0',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        PayrollComponent::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->componentsJsonResponse($userAccountId, 'Component created successfully.');
        }

        return redirect()->back()->with('success', 'Component created successfully.')->with('open_component_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $component = PayrollComponent::where('componentid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_type' => 'required|string|in:attendance,leave,security_deposit,general_earning,general_deduction',
            'type' => 'required|string|in:earning,deduction',
            'calculation_type' => 'nullable|string|in:fixed,percentage',
            'calculation_value' => 'nullable|numeric|min:0',
        ]);

        $component->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->componentsJsonResponse($userAccountId, 'Component updated successfully.');
        }

        return redirect()->back()->with('success', 'Component updated successfully.')->with('open_component_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $component = PayrollComponent::where('componentid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $component->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->componentsJsonResponse($userAccountId, 'Component deleted successfully.');
        }

        return redirect()->back()->with('success', 'Component deleted successfully.')->with('open_component_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $component = PayrollComponent::where('componentid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $component->update([
            'status' => $component->status ? 0 : 1,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->componentsJsonResponse($userAccountId, 'Component status updated successfully.');
        }

        return redirect()->back()->with('success', 'Component status updated successfully.')->with('open_component_modal', true);
    }
}
