@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    {{-- KPI Cards --}}
    <div class="row g-4 mb-4">

        @foreach ($stats as $stat)
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card shadow-sm border-0 h-100 position-relative">

                    @if (!empty($stat['url']))
                        <a href="{{ $stat['url'] }}"
                           class="position-absolute top-0 start-0 w-100 h-100"
                           aria-label="Open {{ $stat['label'] }}"></a>
                    @endif

                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:50px;height:50px;">
                                <i class="fas {{ $stat['icon'] }} text-primary"></i>
                            </div>

                            @if(!empty($stat['change']))
                                <span class="badge {{ str_contains($stat['change'], '+') ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $stat['change'] }}
                                </span>
                            @endif

                        </div>

                        <p class="text-muted small mb-1">{{ $stat['label'] }}</p>
                        <h3 class="fw-bold mb-0">{{ $stat['value'] }}</h3>

                    </div>
                </div>
            </div>
        @endforeach

        {{-- Revenue --}}
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100 position-relative">

                <a href="{{ route('payments.index') }}"
                   class="position-absolute top-0 start-0 w-100 h-100"
                   aria-label="Open Payments"></a>

                <div class="card-body">

                    <div class="mb-3">
                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                             style="width:50px;height:50px;">
                            <i class="fas fa-wallet text-success"></i>
                        </div>
                    </div>

                    <p class="text-muted small mb-1">Total Revenue</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalRevenue, 0) }}</h3>

                </div>
            </div>
        </div>

        {{-- Outstanding --}}
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100 position-relative">

                <a href="{{ route('invoices.index', ['tab' => 'outstanding']) }}"
                   class="position-absolute top-0 start-0 w-100 h-100"
                   aria-label="Open Outstanding Invoices"></a>

                <div class="card-body">

                    <div class="mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                             style="width:50px;height:50px;">
                            <i class="fas fa-hand-holding-usd text-danger"></i>
                        </div>
                    </div>

                    <p class="text-muted small mb-1">Total Outstanding</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalOutstanding, 0) }}</h3>

                </div>
            </div>
        </div>

        {{-- Invoices --}}
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100 position-relative">

                <a href="{{ route('invoices.index') }}"
                   class="position-absolute top-0 start-0 w-100 h-100"
                   aria-label="Open Invoices"></a>

                <div class="card-body">

                    <div class="mb-3">
                        <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                             style="width:50px;height:50px;">
                            <i class="fas fa-file-invoice-dollar text-info"></i>
                        </div>
                    </div>

                    <p class="text-muted small mb-1">Total Invoices</p>
                    <h3 class="fw-bold mb-0">{{ $totalInvoices }}</h3>

                </div>
            </div>
        </div>

    </div>

    {{-- Main Content --}}
    <div class="row g-4">

        {{-- Items Needing Attention --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="min-height: 380px;">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <small class="text-muted text-uppercase">
                                Renewal Operations
                            </small>
                            <h5 class="fw-bold mb-0">
                                Items Needing Attention
                            </h5>
                        </div>

                        <a href="{{ route('invoices.expiry-list', ['tab' => 'upcoming']) }}"
                           class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>

                    @forelse ($renewalsNeedAttention as $item)

                        @php($daysLeft = $item['days_left'])

                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">

                            <div class="d-flex align-items-center">

                                <div class="me-3">
                                    <i class="fas fa-sync-alt text-warning"></i>
                                </div>

                                <div>
                                    <div class="fw-semibold">
                                        {{ $item['item_name'] }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $item['client_name'] }}
                                        • Expires {{ $item['end_date_display'] }}
                                    </small>
                                </div>

                            </div>

                            <div class="text-end">

                                @if($daysLeft === 0)
                                    <span class="badge bg-warning text-dark">
                                        Due Today
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        {{ $daysLeft }}d left
                                    </span>
                                @endif

                                <div class="mt-1">
                                    <a href="{{ route('invoices.expiry-list', ['c' => $item['clientid'], 'tab' => 'upcoming']) }}">
                                        Open
                                    </a>
                                </div>

                            </div>

                        </div>

                    @empty

                        <p class="text-muted mb-0">
                            No renewal items need attention right now.
                        </p>

                    @endforelse

                </div>
            </div>
        </div>

        {{-- Recent Revenue --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="min-height: 380px;">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Recent Revenue</h5>

                        <a href="{{ route('payments.index') }}"
                           class="btn btn-sm btn-outline-success">
                            View All
                        </a>
                    </div>

                    @forelse ($recentRevenue as $item)

                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">

                            <div class="d-flex align-items-center">

                                <div class="me-3">
                                    <i class="fas fa-arrow-down text-success"></i>
                                </div>

                                <div>
                                    <div class="fw-semibold">
                                        {{ $item['title'] }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $item['date'] }}
                                    </small>
                                </div>

                            </div>

                            <div class="fw-bold text-success">
                                {{ $item['amount'] }}
                            </div>

                        </div>

                    @empty

                        <p class="text-muted mb-0">
                            No recent payments yet.
                        </p>

                    @endforelse

                </div>
            </div>
        </div>

        {{-- Recently Expired --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="min-height: 380px;">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">

                        <h5 class="fw-bold mb-0">
                            Recently Expired Items
                        </h5>

                        <a href="{{ route('invoices.expiry-list', ['tab' => 'expired']) }}"
                           class="btn btn-sm btn-outline-danger">
                            Open Expiry List
                        </a>

                    </div>

                    @forelse ($expiredRenewals as $item)

                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">

                            <div class="d-flex align-items-center">

                                <div class="me-3">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>

                                <div>
                                    <div class="fw-semibold">
                                        {{ $item['item_name'] }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $item['client_name'] }}
                                        • {{ $item['end_date_display'] }}
                                    </small>
                                </div>

                            </div>

                            <div class="fw-bold text-danger">
                                @if(($item['days_left'] ?? 0) === 0)
                                    Today
                                @else
                                    {{ abs($item['days_left']) }}d ago
                                @endif
                            </div>

                        </div>

                    @empty

                        <p class="text-muted mb-0">
                            No expired items.
                        </p>

                    @endforelse

                </div>
            </div>
        </div>

        {{-- Outstanding Invoices --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100" style="min-height: 380px;">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Outstanding Invoices</h5>

                        <a href="{{ route('invoices.index', ['tab' => 'outstanding']) }}"
                           class="btn btn-sm btn-outline-danger">
                            View All
                        </a>
                    </div>

                    @forelse ($outstandingInvoices as $item)

                        <div class="d-flex justify-content-between align-items-center py-3 border-bottom">

                            <div class="d-flex align-items-center">

                                <div class="me-3">
                                    <i class="fas fa-file-invoice-dollar text-danger"></i>
                                </div>

                                <div>
                                    <div class="fw-semibold">
                                        {{ $item['invoice_number'] }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $item['client_name'] }}
                                        • {{ $item['date'] }}
                                    </small>
                                </div>

                            </div>

                            <div class="fw-bold text-danger">
                                {{ $item['balance_due'] }}
                            </div>

                        </div>

                    @empty

                        <p class="text-muted mb-0">
                            No outstanding invoices.
                        </p>

                    @endforelse

                </div>
            </div>
        </div>

    </div>

</div>
@endsection
