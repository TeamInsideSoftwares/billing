<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamManagementController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Users assigned TO the current user (the user's team)
        $myAssignedUsers = $user->teamMembers()->get();

        // Teams the current user belongs to (leaders who have assigned this user)
        $myLeaders = User::whereHas('teamMembers', function ($query) use ($user) {
            $query->where('assigned_userid', $user->userid);
        })->with('teamMembers')->get();

        // All teams in the company
        $allTeams = User::where('accountid', $user->accountid)
            ->has('teamMembers')
            ->with('teamMembers')
            ->get();

        return view('team.index', compact('user', 'myAssignedUsers', 'myLeaders', 'allTeams'));
    }
}
