<?php

namespace App\Http\Controllers;

use App\Models\AccountRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountRoleController extends Controller
{
    private function rolesJsonResponse(string $accountId, string $message): JsonResponse
    {
        $roles = AccountRole::where('accountid', $accountId)->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'roles' => $roles->map(function ($r) {
                return [
                    'roleid' => $r->roleid,
                    'name' => $r->name,
                    'status' => $r->status,
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

        AccountRole::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->rolesJsonResponse($accountId, 'Role created successfully.');
        }

        return redirect()->back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $role = AccountRole::where('roleid', $id)->where('accountid', $accountId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
        ]);

        $role->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->rolesJsonResponse($accountId, 'Role updated successfully.');
        }

        return redirect()->back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $role = AccountRole::where('roleid', $id)->where('accountid', $accountId)->firstOrFail();

        $role->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->rolesJsonResponse($accountId, 'Role deleted successfully.');
        }

        return redirect()->back()->with('success', 'Role deleted successfully.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $accountId = $this->resolveAccountId();
        $role = AccountRole::where('roleid', $id)->where('accountid', $accountId)->firstOrFail();

        $role->status = $role->status === 'active' ? 'inactive' : 'active';
        $role->save();

        if ($request->ajax() || $request->wantsJson()) {
            return $this->rolesJsonResponse($accountId, 'Role status updated successfully.');
        }

        return redirect()->back()->with('success', 'Role status updated successfully.');
    }
}
