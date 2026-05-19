@extends('layouts.app')

@section('content')
<div class="dashboard-grid">
    <div class="kpi-row">
        @foreach ($stats as $stat)
            <div class="soft-card p-4">
                <div class="stat-icon {{ $stat['tone'] }}">
                    <i class="fas {{ $stat['icon'] }}"></i>
                </div>
                <div class="stat-content">
                    <p class="eyebrow mb-1">{{ $stat['label'] }}</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="mb-0 fw-800">{{ $stat['value'] }}</h3>
                        @if(!empty($stat['change']))
                            <span class="stat-change stat-change-sm {{ str_contains($stat['change'], '+') ? 'positive' : 'warning' }}">
                                {{ $stat['change'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <div class="soft-card p-4">
            <div class="stat-icon success">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-content">
                <p class="eyebrow mb-1">Total Revenue</p>
                <h3 class="mb-0 fw-800">Rs {{ number_format($totalRevenue, 0) }}</h3>
            </div>
        </div>
        <div class="soft-card p-4">
            <div class="stat-icon brand">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <p class="eyebrow mb-1">Total Invoices</p>
                <h3 class="mb-0 fw-800">{{ $totalInvoices }}</h3>
            </div>
        </div>
    </div>

    <div class="charts-row">
        <div class="soft-card p-4">
            <div class="panel-head mb-4">
                <div>
                    <p class="eyebrow">Renewal Operations</p>
                    <h5 class="fw-700 mb-0">Items Needing Attention</h5>
                </div>
                <a href="{{ route('invoices.expiry-list', ['tab' => 'upcoming']) }}" class="text-link">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($renewalsNeedAttention as $item)
                    @php($daysLeft = $item['days_left'])
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-icon">
                                <i class="fas fa-sync-alt text-warning"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-600 list-item-title">{{ $item['item_name'] }}</p>
                                <small class="text-muted">{{ $item['client_name'] }} · Expires {{ $item['end_date_display'] }}</small>
                            </div>
                        </div>
                        <div class="renewal-meta">
                            @if($daysLeft === 0)
                                <span class="renewal-pill warning">Due Today</span>
                            @else
                                <span class="renewal-pill brand">{{ $daysLeft }}d left</span>
                            @endif
                            <a href="{{ route('invoices.expiry-list', ['c' => $item['clientid'], 'tab' => 'upcoming']) }}" class="text-link">Open</a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No renewal items need attention right now.</p>
                @endforelse
            </div>
        </div>

        <div class="soft-card p-4">
            <div class="panel-head mb-4">
                <div>
                    <p class="eyebrow">Client Health</p>
                    <h5 class="fw-700 mb-0">Renewal Priority by Client</h5>
                </div>
                <a href="{{ route('clients.index') }}" class="text-link">Clients</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($renewalClientPriorities as $client)
                    <div class="list-item">
                        <div>
                            <p class="mb-0 fw-600 list-item-title">{{ $client['client_name'] }}</p>
                            <small class="text-muted">
                                @if (($client['nearest_days_left'] ?? null) === null)
                                    No timeline
                                @elseif($client['nearest_days_left'] < 0)
                                    Nearest expiry was {{ abs($client['nearest_days_left']) }} day(s) ago
                                @elseif($client['nearest_days_left'] === 0)
                                    Nearest expiry is today
                                @else
                                    Nearest expiry in {{ $client['nearest_days_left'] }} day(s)
                                @endif
                            </small>
                        </div>
                        <div class="renewal-client-metrics">
                            @if (($client['expired_count'] ?? 0) > 0)
                                <span class="renewal-pill danger">{{ $client['expired_count'] }} expired</span>
                            @endif
                            @if (($client['due_this_week_count'] ?? 0) > 0)
                                <span class="renewal-pill warning">{{ $client['due_this_week_count'] }} this week</span>
                            @endif
                            @if (($client['due_this_month_count'] ?? 0) > 0)
                                <span class="renewal-pill brand">{{ $client['due_this_month_count'] }} this month</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No client renewal priorities for the next 30 days.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="lists-row">
        <div class="soft-card p-4">
            <div class="panel-head mb-3">
                <h5 class="fw-700 mb-0">Recent Revenue</h5>
                <a href="{{ route('payments.index') }}" class="text-link">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($recentRevenue as $item)
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-icon">
                                <i class="fas fa-arrow-down text-success"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-600 list-item-title">{{ $item['title'] }}</p>
                                <small class="text-muted">{{ $item['date'] }}</small>
                            </div>
                        </div>
                        <div class="amount-text success">
                            {{ $item['amount'] }}
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No recent payments yet.</p>
                @endforelse
            </div>
        </div>

        <div class="soft-card p-4">
            <div class="panel-head mb-3">
                <h5 class="fw-700 mb-0">Recently Expired Items</h5>
                <a href="{{ route('invoices.expiry-list', ['tab' => 'expired']) }}" class="text-link">Open Expiry List</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse ($expiredRenewals as $item)
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-icon">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-600 list-item-title">{{ $item['item_name'] }}</p>
                                <small class="text-muted">{{ $item['client_name'] }} · {{ $item['end_date_display'] }}</small>
                            </div>
                        </div>
                        <div class="amount-text danger">
                            @if(($item['days_left'] ?? 0) === 0)
                                Today
                            @else
                                {{ abs($item['days_left']) }}d ago
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No expired items.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
