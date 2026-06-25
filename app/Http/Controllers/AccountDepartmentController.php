<?php

namespace App\Http\Controllers;

use App\Models\AccountDepartment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDepartmentController extends Controller
{
    private function departmentsJsonResponse(string $accountId, string $message): JsonResponse
    {
        $departments = AccountDepartment::where('accountid', $accountId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'departments' => $departments->map(function ($d) {
                return [
                    'depid' => $d->depid,
                    'name' => $d->name,
                    'status' => $d->status,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $accountId = $this->resolveAccountId();
        $validated['accountid'] = $accountId;
        $validated['status'] = 'active';

        AccountDepartment::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->departmentsJsonResponse($accountId, 'Department created successfully.');
        }

        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function update(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $department = AccountDepartment::where('depid', $id)->where('accountid', $accountId)->firstOrFail();

        $validated = $request->request->has('status') ? $request->validate([
            'status' => 'required|in:active,inactive',
        ]) : $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $department->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->departmentsJsonResponse($accountId, 'Department updated successfully.');
        }

        return redirect()->back()->with('success', 'Department updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $department = AccountDepartment::where('depid', $id)->where('accountid', $accountId)->firstOrFail();

        $department->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->departmentsJsonResponse($accountId, 'Department deleted successfully.');
        }

        return redirect()->back()->with('success', 'Department deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $department = AccountDepartment::where('depid', $id)->where('accountid', $accountId)->firstOrFail();

        $department->status = $department->status === 'active' ? 'inactive' : 'active';
        $department->save();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->departmentsJsonResponse($accountId, 'Department status updated successfully.');
        }

        return redirect()->back()->with('success', 'Department status updated successfully.');
    }
}
