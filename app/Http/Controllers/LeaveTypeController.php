<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    private function leaveTypesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $leaveTypes = LeaveType::where('accountid', $accountId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'leaveTypes' => $leaveTypes->map(function ($lt) {
                return [
                    'typeid' => $lt->typeid,
                    'name' => $lt->name,
                    'description' => $lt->description,
                    'status' => $lt->status,
                ];
            }),
        ]);
    }

    public function index()
    {
        $userAccountId = $this->resolveAccountId();

        return $this->leaveTypesJsonResponse($userAccountId, 'Leave types fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;
        $validated['status'] = 'active';

        LeaveType::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->leaveTypesJsonResponse($userAccountId, 'Leave type created successfully.');
        }

        return redirect()->back()->with('success', 'Leave type created successfully.')->with('open_leave_type_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $leaveType = LeaveType::where('typeid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $leaveType->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->leaveTypesJsonResponse($userAccountId, 'Leave type updated successfully.');
        }

        return redirect()->back()->with('success', 'Leave type updated successfully.')->with('open_leave_type_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $leaveType = LeaveType::where('typeid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $leaveType->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->leaveTypesJsonResponse($userAccountId, 'Leave type deleted successfully.');
        }

        return redirect()->back()->with('success', 'Leave type deleted successfully.')->with('open_leave_type_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $leaveType = LeaveType::where('typeid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $leaveType->update([
            'status' => $leaveType->status === 'active' ? 'inactive' : 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->leaveTypesJsonResponse($userAccountId, 'Leave type status updated successfully.');
        }

        return redirect()->back()->with('success', 'Leave type status updated successfully.')->with('open_leave_type_modal', true);
    }
}
