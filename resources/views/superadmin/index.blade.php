@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <p class="eyebrow">Agencies Master</p>
            <h3>All Registered Agencies</h3>
        </div>
        <a href="{{ route('accounts.create') }}" class="primary-button">+ Register New Company</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @if(count($accounts) > 0)
                @foreach ($accounts as $acc)
                    <tr>
                        <td>
                            <strong>{{ $acc->name }}</strong>
                            <span class="superadmin-id">ID: {{ $acc->id }}</span>
                        </td>
                        <td>
                            <strong>{{ $acc->email }}</strong>
                            <span>{{ $acc->legal_name ?? 'No legal name' }}</span>
                        </td>
                        <td>
                            <span class="status-pill {{ $acc->status }}">{{ ucfirst($acc->status) }}</span>
                        </td>
                        <td class="table-actions">
                            <a href="#" class="icon-action-btn view" title="Manage">
                                <i class="fas fa-cog"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" class="no-records-cell">
                        No agencies registered yet.
                    </td>
                </tr>
            @endif
            </tbody>
        </table>
    </section>
@endsection
