@extends('layouts.app')

@section('content')
<div class="dashboard-grid">
    {{-- Top Row: KPI Cards --}}
    <div class="kpi-row">
        @foreach ($stats as $stat)
            <div class="soft-card p-4">
                <div class="stat-icon {{ $stat['tone'] }}">
                    <i class="fas {{ $stat['icon'] }}"></i>
                </div>
                <div class="stat-content">
                    <p class="eyebrow mb-1">{{ $stat['label'] }}</p>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="mb-0 fw-800" style="font-weight: 800;">{{ $stat['value'] }}</h3>
                        <span class="stat-change {{ str_contains($stat['change'], '+') ? 'positive' : 'warning' }}" style="font-size: 0.8rem;">
                            {{ $stat['change'] }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Middle Row: Monthly Payments Charts --}}
    <div class="charts-row">
        <div class="soft-card p-4">
            <div class="panel-head mb-4">
                <div>
                    <p class="eyebrow">Financial Trends</p>
                    <h5 class="fw-700 mb-0" style="font-weight: 700;">Monthly Revenue</h5>
                </div>
                <div class="dropdown">
                    <button class="icon-btn text-dark bg-light" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Last 6 Months</a></li>
                        <li><a class="dropdown-item" href="#">Last Year</a></li>
                    </ul>
                </div>
            </div>
            <canvas id="revenueChart" style="max-height: 300px;"></canvas>
        </div>

        <div class="soft-card p-4">
            <div class="panel-head mb-4">
                <div>
                    <p class="eyebrow">Payment Volume</p>
                    <h5 class="fw-700 mb-0" style="font-weight: 700;">Transaction Count</h5>
                </div>
            </div>
            <canvas id="transactionChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    {{-- Bottom Row: Recent Revenue & Expenses --}}
    <div class="lists-row">
        <div class="soft-card p-4">
            <div class="panel-head mb-3">
                <h5 class="fw-700 mb-0" style="font-weight: 700;">Recent Revenue</h5>
                <a href="{{ route('payments.index') }}" class="text-link">View All</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($recentRevenue as $item)
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-icon">
                                <i class="fas fa-arrow-down text-success"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-600" style="font-size: 0.9rem; font-weight: 600;">{{ $item['title'] }}</p>
                                <small class="text-muted">{{ $item['date'] }}</small>
                            </div>
                        </div>
                        <div class="amount-text success">
                            {{ $item['amount'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="soft-card p-4">
            <div class="panel-head mb-3">
                <h5 class="fw-700 mb-0" style="font-weight: 700;">Recent Expenses</h5>
                <a href="#" class="text-link">Manage</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($recentExpenses as $item)
                    <div class="list-item">
                        <div class="list-item-info">
                            <div class="list-item-icon">
                                <i class="fas fa-arrow-up text-danger"></i>
                            </div>
                            <div>
                                <p class="mb-0 fw-600" style="font-size: 0.9rem; font-weight: 600;">{{ $item['title'] }}</p>
                                <small class="text-muted">{{ $item['date'] }}</small>
                            </div>
                        </div>
                        <div class="amount-text danger">
                            {{ $item['amount'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    const ctxTransaction = document.getElementById('transactionChart').getContext('2d');

    const labels = @json($monthlyPayments['labels']);
    const dataValues = @json($monthlyPayments['data']);

    // Common options for line charts
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 10 } }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#94a3b8', font: { size: 10 } }
            }
        },
        elements: {
            line: { tension: 0.4 },
            point: { radius: 0 }
        }
    };

    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue',
                data: dataValues,
                borderColor: '#4f46e5',
                borderWidth: 3,
                fill: true,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.1)');
                    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
                    return gradient;
                }
            }]
        },
        options: commonOptions
    });

    new Chart(ctxTransaction, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Transactions',
                data: [45, 52, 38, 65, 48, 70, 62, 85, 75, 90, 82, 95],
                borderColor: '#10b981',
                borderWidth: 3,
                fill: true,
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.1)');
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                    return gradient;
                }
            }]
        },
        options: commonOptions
    });
});
</script>
@endsection
