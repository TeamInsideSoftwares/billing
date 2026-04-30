<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    public function groups(): View
    {
        $query = Group::query();
        $searchTerm = request('search', '');
        if ($searchTerm) {
            $query->where('group_name', 'like', '%' . $searchTerm . '%');
        }
        $resultCount = $query->count();
        $groups = $query->latest()->take(20)->get()->map(function ($g) {
            return [
                'record_id' => $g->groupid,
                'group_name' => $g->group_name,
                'email' => $g->email ?? '-',
                'city' => $g->city ?? '-',
                'state' => $g->state ?? '-',
                'address_line_1' => $g->address_line_1 ?? '',
                'address_line_2' => $g->address_line_2 ?? '',
                'postal_code' => $g->postal_code ?? '',
                'country' => $g->country ?? 'India',
                'gstin' => $g->gstin ?? '',
            ];
        });

        return view('groups.index', [
            'title' => 'Client Groups',
            'subtitle' => $searchTerm ? 'Search results for "' . $searchTerm . '"' : null,
            'groups' => $groups,
            'searchTerm' => $searchTerm,
            'resultCount' => $resultCount,
        ]);
    }

    public function groupsCreate(): View
    {
        return view('groups.form', ['title' => 'Add New Group']);
    }

    public function groupsStore(Request $request)
    {
        $validated = $request->validate([
            'group_name' => 'required|string|max:150',
            'email' => 'nullable|email',
            'address_line_1' => 'nullable|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:20',
        ]);

        $userAccountId = auth()->check() ? (auth()->user()->accountid ?? 'ACC0000001') : 'ACC0000001';
        $validated['accountid'] = $userAccountId;

        Group::create($validated);

        return redirect()->back()->with('success', 'Group created successfully.')->with('open_group_modal', true);
    }

    public function groupsShow(Group $group): View
    {
        return view('groups.show', [
            'title' => $group->group_name ?? 'Group',
            'subtitle' => 'Group Details',
            'group' => $group,
        ]);
    }

    public function groupsEdit(Group $group): View
    {
        return view('groups.form', ['title' => 'Edit ' . ($group->group_name ?? 'Group'), 'group' => $group]);
    }

    public function groupsUpdate(Request $request, $id)
    {
        $group = Group::where('groupid', $id)->firstOrFail();

        $validated = $request->validate([
            'group_name' => 'required|string|max:150',
            'email' => 'nullable|email',
            'address_line_1' => 'nullable|string|max:150',
            'address_line_2' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'gstin' => 'nullable|string|max:20',
        ]);

        $group->update($validated);

        return redirect()->back()->with('success', 'Group updated successfully.')->with('open_group_modal', true);
    }

    public function groupsDestroy(Group $group)
    {
        $group->delete();

        return redirect()->back()->with('success', 'Group deleted successfully.')->with('open_group_modal', true);
    }
}
