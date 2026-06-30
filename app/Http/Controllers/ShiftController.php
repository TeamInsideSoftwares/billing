<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    private function shiftsJsonResponse(string $accountId, string $message): JsonResponse
    {
        $shifts = Shift::where('accountid', $accountId)->orderBy('shift_name')->get();

        return response()->json([
            'success' => true,
            'message' => $message,
            'shifts' => $shifts->map(function ($s) {
                return [
                    'shiftid' => $s->shiftid,
                    'shift_name' => $s->shift_name,
                    'start_time' => $s->start_time ? date('H:i', strtotime($s->start_time)) : null,
                    'end_time' => $s->end_time ? date('H:i', strtotime($s->end_time)) : null,
                    'break_duration' => $s->break_duration,
                    'break_start_time' => $s->break_start_time ? date('H:i', strtotime($s->break_start_time)) : null,
                    'break_end_time' => $s->break_end_time ? date('H:i', strtotime($s->break_end_time)) : null,
                    'break_grace_period' => $s->break_grace_period,
                    'status' => $s->status,
                ];
            }),
        ]);
    }

    public function index()
    {
        $userAccountId = $this->resolveAccountId();

        return $this->shiftsJsonResponse($userAccountId, 'Shifts fetched successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration' => 'required|integer|min:0',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'break_grace_period' => 'required|integer|min:0',
        ]);

        $userAccountId = $this->resolveAccountId();
        $validated['accountid'] = $userAccountId;

        Shift::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->shiftsJsonResponse($userAccountId, 'Shift created successfully.');
        }

        return redirect()->back()->with('success', 'Shift created successfully.')->with('open_shift_modal', true);
    }

    public function update(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $shift = Shift::where('shiftid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $validated = $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration' => 'required|integer|min:0',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'break_grace_period' => 'required|integer|min:0',
        ]);

        $shift->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->shiftsJsonResponse($userAccountId, 'Shift updated successfully.');
        }

        return redirect()->back()->with('success', 'Shift updated successfully.')->with('open_shift_modal', true);
    }

    public function destroy($id)
    {
        $userAccountId = $this->resolveAccountId();
        $shift = Shift::where('shiftid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $shift->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return $this->shiftsJsonResponse($userAccountId, 'Shift deleted successfully.');
        }

        return redirect()->back()->with('success', 'Shift deleted successfully.')->with('open_shift_modal', true);
    }

    public function toggleStatus(Request $request, $id)
    {
        $userAccountId = $this->resolveAccountId();
        $shift = Shift::where('shiftid', $id)->where('accountid', $userAccountId)->firstOrFail();

        $shift->update([
            'status' => $shift->status === 'active' ? 'inactive' : 'active',
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return $this->shiftsJsonResponse($userAccountId, 'Shift status updated successfully.');
        }

        return redirect()->back()->with('success', 'Shift status updated successfully.')->with('open_shift_modal', true);
    }
}
