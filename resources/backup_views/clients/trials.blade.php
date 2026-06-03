@extends('layouts.app')

@section('header_actions')
    <div class="header-actions-wrapper">
        <a href="{{ route('clients.index') }}" class="secondary-button">View All Clients</a>
    </div>
@endsection

@section('content')
    <section class="panel-card module-filter-panel filter-panel-regular">
        <form action="{{ route('clients.trials') }}" method="GET" class="module-filter-grid">
            <div class="module-filter-field">
                <label class="module-filter-label" for="trials_search_filter">Search</label>
                <input type="text" name="search" id="trials_search_filter" class="form-control" value="{{ $searchTerm ?? '' }}" placeholder="Business name or contact person">
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="primary-button">Apply</button>
                <a href="{{ route('clients.trials') }}" class="secondary-button">Reset</a>
            </div>
        </form>
    </section>

    <section class="panel-card no-padding">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($clients as $client)
                <tr>
                    <td>
                        <div class="flex-center-gap">
                            <div class="avatar-box">
                                {{ strtoupper(substr($client['name'], 0, 2)) }}
                            </div>
                            <div>
                                <strong class="text-highlight">{{ $client['name'] }}</strong>
                                <div class="text-xs text-muted">{{ $client['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($client['contact'])
                            <div class="small-text">{{ $client['contact'] }}</div>
                        @endif
                        @if($client['phone'])
                            <div class="text-xs text-muted"><i class="fas fa-phone icon-small icon-spaced-sm"></i>{{ $client['phone'] }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($client['status']) }}">{{ ucfirst(strtolower($client['status'])) }}</span>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="{{ route('clients.dashboard', $client['record_id']) }}" class="text-action-btn view">View Orders</a>
                            <a href="{{ route('quotations.create', ['c' => $client['record_id']]) }}" class="text-action-btn edit">Create Quotation</a>
                            <form method="POST" action="{{ route('clients.convert-to-regular', $client['record_id']) }}" class="inline-delete" onsubmit="return confirm('Convert {{ $client['name'] }} to regular client?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-action-btn secondary">Convert to Regular</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="no-records-cell">
                        <i class="fas fa-user-clock empty-state-icon"></i>
                        <p class="no-empty-state-text">No trial clients found</p>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
