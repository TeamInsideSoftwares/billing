@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <p class="eyebrow">Agencies Master</p>
            <h3>All Registered Agencies</h3>
        </div>
        <a href="{{ route('accounts.create') }}" class="primary-button">+ Register New Agency</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @if(count($accounts) > 0)
                @foreach ($accounts as $acc)
                    <div class="table-row">
                        <div>
                            <strong>{{ $acc->name }}</strong>
                            <span style="font-size: 0.8rem; color: var(--slate-400);">ID: {{ $acc->id }}</span>
                        </div>
                        <div>
                            <strong>{{ $acc->email }}</strong>
                            <span>{{ $acc->legal_name ?? 'No legal name' }}</span>
                        </div>
                        <div>
                            <span class="status-pill {{ $acc->status }}">{{ ucfirst($acc->status) }}</span>
                        </div>
                        <div class="table-actions">
                            <a href="#" class="text-link">Manage</a>
                        </div>
                    </div>
                @endforeach
            @else
                <div style="padding: 2rem; text-align: center; color: var(--slate-400);">
                    No agencies registered yet.
                </div>
            @endif
        </div>
    </section>
@endsection
