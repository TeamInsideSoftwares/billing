@extends('layouts.app')
@section('title', 'Team Work')

@section('content')
<div class="row align-items-center mb-4">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="d-flex align-items-center">
            <div class="d-flex align-items-center justify-content-center bg-primary rounded-circle me-3" style="width: 50px; height: 50px;">
                <i class="bi bi-people-fill text-white fs-3"></i>
            </div>
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Team Work</h1>
                <p class="text-muted mb-0 small">View your team and members you manage</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Users Assigned to Me (My Team) -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Users Assigned to Me</h6>
            </div>
            <div class="card-body">
                @if($myAssignedUsers->isEmpty())
                    <p class="text-muted mb-0">You don't have any users assigned to you yet.</p>
                @else
                    @php
                        // Group by team_name if multiple teams exist, or just show all
                        $teamName = $myAssignedUsers->first()->pivot->team_name ?? ($user->name . "'s Team");
                    @endphp
                    <div class="mb-3">
                        <span class="badge bg-primary fs-6">{{ $teamName }}</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($myAssignedUsers as $member)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-weight: bold;">
                                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $member->name }}</div>
                                                    <div class="small text-muted">{{ $member->userid }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $member->email }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ ucfirst($member->role ? $member->role->name : 'No Role') }}</span>
                                        </td>
                                        <td class="text-end">
                                            @if($member->hasPermission('team_work.view'))
                                            <form action="{{ route('login.as', $member) }}" method="POST" class="d-inline" target="_blank">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Login As User">
                                                    <i class="bi bi-box-arrow-in-right me-1"></i> Login As
                                                </button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Teams I Belong To -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Teams I Belong To</h6>
            </div>
            <div class="card-body">
                @if($myLeaders->isEmpty())
                    <p class="text-muted mb-0">You are not currently assigned to any teams.</p>
                @else
                    <div class="row g-3">
                        @foreach($myLeaders as $leader)
                            @php
                                // Find my assignment record to get the team name
                                $myAssignment = $leader->teamMembers->where('userid', $user->userid)->first();
                                $leaderTeamName = $myAssignment ? $myAssignment->pivot->team_name : ($leader->name . "'s Team");
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="card border border-light bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title text-dark mb-3">{{ $leaderTeamName }}</h5>
                                        <div class="mb-2">
                                            <span class="small text-muted fw-bold">Team Leader:</span>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($leader->name, 0, 1)) }}
                                                </div>
                                                <span class="text-dark">{{ $leader->name }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="small text-muted fw-bold">Team Members:</span>
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @foreach($leader->teamMembers as $teammate)
                                                    <span class="badge {{ $teammate->userid === $user->userid ? 'bg-primary' : 'bg-white border text-dark' }}">
                                                        {{ $teammate->name }} {{ $teammate->userid === $user->userid ? '(You)' : '' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- All Teams -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Company Teams</h6>
            </div>
            <div class="card-body">
                @if($allTeams->isEmpty())
                    <p class="text-muted mb-0">No teams exist in the company yet.</p>
                @else
                    <div class="row g-3">
                        @foreach($allTeams as $teamManager)
                            @php
                                $firstAssignment = $teamManager->teamMembers->first();
                                $teamName = $firstAssignment ? $firstAssignment->pivot->team_name : ($teamManager->name . "'s Team");
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="card border border-light bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title text-dark mb-3">{{ $teamName }}</h5>
                                        <div class="mb-2">
                                            <span class="small text-muted fw-bold">Team Leader:</span>
                                            <div class="d-flex align-items-center mt-1">
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                                    {{ strtoupper(substr($teamManager->name, 0, 1)) }}
                                                </div>
                                                <span class="text-dark">{{ $teamManager->name }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="small text-muted fw-bold">Members ({{ $teamManager->teamMembers->count() }}):</span>
                                            <div class="mt-1 d-flex flex-wrap gap-1">
                                                @foreach($teamManager->teamMembers as $teammate)
                                                    <span class="badge {{ $teammate->userid === $user->userid ? 'bg-primary' : 'bg-white border text-dark' }}">
                                                        {{ $teammate->name }} {{ $teammate->userid === $user->userid ? '(You)' : '' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
