@extends('layouts.app')

@php
$subtitle = null;
@endphp

@section('content')
<div class="container-fluid py-2">
    {{-- KPI Cards --}}
    <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 h-100 position-relative mb-2">
        <div class="row g-3">

            @foreach ($stats as $stat)
            @php
            $bgClass = 'bg-primary bg-opacity-10 text-primary';
            $iconClass = 'far fa-user';
            $arrowColorClass = 'text-primary';

            if ($stat['label'] === 'Account / Clients') {
            $bgClass = 'bg-primary bg-opacity-10 text-primary';
            $iconClass = 'far fa-user';
            $arrowColorClass = 'text-primary';
            } elseif (str_contains($stat['label'], 'Renewals Due')) {
            $bgClass = 'bg-warning bg-opacity-10 text-warning';
            $iconClass = 'far fa-clock';
            $arrowColorClass = 'text-warning';
            } elseif (str_contains($stat['label'], 'Expired')) {
            $bgClass = 'bg-danger bg-opacity-10 text-danger';
            $iconClass = 'far fa-calendar-times';
            $arrowColorClass = 'text-danger';
            }
            @endphp
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="dashboard-kpi-card position-relative h-100">

                    @if (!empty($stat['url']))
                    <a href="{{ $stat['url'] }}" class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 2;"
                        aria-label="Open {{ $stat['label'] }}"></a>
                    @endif

                    <div
                        class="card-body bg-white rounded-3 p-3 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="{{ $bgClass }} rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width:50px;height:50px; flex-shrink: 0;">
                                <i class="{{ $iconClass }} fs-4 lh-sm"></i>
                            </div>

                            <div>
                                <div class="text-dark small lh-sm text-uppercase fw-normal mb-1"><small class="lh-sm">{{ $stat['label'] }}</small>
                                </div>
                                <h5 class="mb-0 fw-bold text-dark fs-4">{{ $stat['value'] }}</h5>
                            </div>
                        </div>

                        @if(!empty($stat['change']))
                        <span
                            class="badge {{ str_contains($stat['change'], '+') ? 'bg-success-light text-success' : 'bg-warning-light text-warning' }} align-self-start"
                            style="z-index: 1;">
                            {{ $stat['change'] }}
                        </span>
                        @endif

                        @if (!empty($stat['url']))
                        <div class="hover-arrow">
                            <i class="fas fa-arrow-right {{ $arrowColorClass }}"></i>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Revenue --}}
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="dashboard-kpi-card position-relative h-100">

                    <a href="{{ route('payments.index') }}" class="position-absolute top-0 start-0 w-100 h-100"
                        style="z-index: 2;" aria-label="Open Payments"></a>

                    <div class="card-body bg-white rounded-3 p-3 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width:50px;height:50px; flex-shrink: 0;">
                                <i class="far fa-money-bill-alt fs-4 lh-sm"></i>
                            </div>

                            <div>
                                <div class="text-dark small lh-sm text-uppercase fw-normal mb-1"><small class="lh-sm">Total Revenue</small></div>
                                <h5 class="mb-0 fw-bold text-success fs-4">{{ number_format($totalRevenue, 0) }}</h5>
                            </div>
                        </div>

                        <div class="hover-arrow">
                            <i class="fas fa-arrow-right text-success"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Outstanding --}}
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="dashboard-kpi-card position-relative h-100">

                    <a href="{{ route('invoices.index', ['tab' => 'outstanding']) }}"
                        class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 2;"
                        aria-label="Open Outstanding Invoices"></a>

                    <div class="card-body bg-white rounded-3 p-3 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width:50px;height:50px; flex-shrink: 0;">
                                <i class="far fa-bell fs-4 lh-sm"></i>
                            </div>

                            <div>
                                <div class="text-dark small lh-sm text-uppercase fw-normal mb-1"><small class="lh-sm">Total Outstanding</small></div>
                                <h5 class="mb-0 fw-bold text-danger fs-4">{{ number_format($totalOutstanding, 0) }}</h5>
                            </div>
                        </div>

                        <div class="hover-arrow">
                            <i class="fas fa-arrow-right text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoices --}}
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="dashboard-kpi-card position-relative h-100">

                    <a href="{{ route('invoices.index') }}" class="position-absolute top-0 start-0 w-100 h-100"
                        style="z-index: 2;" aria-label="Open Invoices"></a>

                    <div class="card-body bg-white rounded-3 p-3 h-100 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width:50px;height:50px; flex-shrink: 0;">
                                <i class="far fa-file-alt fs-4 lh-sm"></i>
                            </div>

                            <div>
                                <div class="text-dark small lh-sm text-uppercase fw-normal mb-1"><small class="lh-sm">Total Invoices</small></div>
                                <h5 class="mb-0 fw-bold text-info fs-4">{{ $totalInvoices }}</h5>
                            </div>
                        </div>

                        <div class="hover-arrow">
                            <i class="fas fa-arrow-right text-info"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Main Content --}}
    <div class="row g-2">

        {{-- Items Needing Attention --}}
        <div class="col-lg-3 col-md-6">
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 h-100" style="min-height: 380px;">
                <div class="card-body bg-white rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="align-self-end">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">
                                <small class="text-dark fw-normal small d-block">
                                    Renewal Operations
                                </small>
                                Items Needing Attention
                            </h5>
                        </div>

                        <a href="{{ route('invoices.expiry-list', ['tab' => 'upcoming']) }}"
                            class="btn btn-outline-primary btn-sm btn-primary text-white fw-medium">
                            View All <i class="fas fa-arrow-right position-relative btn-icon ms-1"
                                style="top: -1px;"></i>
                        </a>
                    </div>

                    @forelse ($renewalsNeedAttention as $item)

                    @php($daysLeft = $item['days_left'])

                    <div
                        class="d-flex justify-content-between align-items-center py-2 px-2  {{ $loop->odd ? 'bg-light' : '' }}">

                        <div class="d-flex align-items-center w-75">

                            <div>
                                <div class="fw-semibold lh-sm">
                                    {{ $item['item_name'] }}
                                </div>

                                <small class="d-block text-dark  mt-0">
                                    {{ $item['client_name'] }}
                                </small>
                                <small class="d-block mt-2 text-danger fw-bold small lh-sm">
                                    <span class="fw-normal">Expires on</span> {{ $item['end_date_display'] }}
                                </small>
                            </div>

                        </div>

                        <div class="text-end w-25">

                            <div class="tableActionButton mb-3">
                                <a href="{{ route('invoices.expiry-list', ['c' => $item['clientid'], 'tab' => 'upcoming']) }}"
                                    class="bg02 color02 border-0 js-renew-order-btn">
                                    Renew
                                </a>
                            </div>

                            <div>
                                @if($daysLeft === 0)
                                <span class="badge bg-white rounded-pill text-danger p-0">
                                    Due Today
                                </span>
                                @else

                                <span class="badge bg-white rounded-pill text-muted p-0">
                                    {{ $daysLeft }}d left
                                </span>
                                @endif
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
        <div class="col-lg-3 col-md-6">
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 h-100" style="min-height: 380px;">
                <div class="card-body bg-white rounded-3 p-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="align-self-end">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">
                                Recent Revenue
                            </h5>
                        </div>

                        <a href="{{ route('payments.index') }}"
                            class="btn btn-outline-primary btn-sm btn-primary text-white fw-medium">
                            View All <i class="fas fa-arrow-right position-relative btn-icon ms-1"
                                style="top: -1px;"></i>
                        </a>
                    </div>

                    @forelse ($recentRevenue as $item)

                    <div class="d-flex justify-content-between align-items-center py-2 px-2  {{ $loop->odd ? 'bg-light' : '' }}">

                        <div class="d-flex align-items-center  w-75">

                            <div>
                                <div class="fw-semibold lh-sm mb-2">
                                    @if(str_starts_with($item['title'], 'Payment from '))
                                        <small class="d-block text-dark fw-normal small lh-sm">Payment from</small>
                                        {{ substr($item['title'], 13) }}
                                    @else
                                        {{ $item['title'] }}
                                    @endif
                                </div>

                                <small class="d-block text-dark  mt-0">
                                    {{ $item['date'] }}
                                </small>
                            </div>

                        </div>

                        <div class="fw-bold text-success text-end w-25">
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
        <div class="col-lg-3 col-md-6">
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 h-100" style="min-height: 380px;">
                <div class="card-body bg-white rounded-3 p-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="align-self-end">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">
                                Recently Expired Items
                            </h5>
                        </div>

                        <a href="{{ route('invoices.expiry-list', ['tab' => 'expired']) }}"
                            class="btn btn-outline-primary btn-sm btn-primary text-white fw-medium">
                            Open Expiry List <i class="fas fa-arrow-right position-relative btn-icon ms-1"
                                style="top: -1px;"></i>
                        </a>

                    </div>

                    @forelse ($expiredRenewals as $item)

                    <div class="d-flex justify-content-between align-items-center py-2 px-2  {{ $loop->odd ? 'bg-light' : '' }}">

                        <div class="d-flex align-items-center  w-75">

                            <div>
                                <div class="fw-semibold lh-sm">
                                    {{ $item['item_name'] }}
                                </div>

                                <small class="d-block text-dark  mt-0">
                                    {{ $item['client_name'] }}
                                     </small>
                                <small class="d-block text-dark mt-2">
                                     {{ $item['end_date_display'] }}
                                </small>
                            </div> 

                        </div>

                        <div class="fw-bold text-end text-danger w-25">
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
        <div class="col-lg-3 col-md-6">
            <div class="card overflow-hidden p-2 border-0 bg-DarkLight rounded-3 h-100" style="min-height: 380px;">
                <div class="card-body bg-white rounded-3 p-3">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="align-self-end">
                            <h5 class="fw-bold fs-6 mb-0 text-dark">
                                Outstanding Invoices
                            </h5>
                        </div>

                        <a href="{{ route('invoices.index', ['tab' => 'outstanding']) }}"
                            class="btn btn-outline-primary btn-sm btn-primary text-white fw-medium">
                            View All <i class="fas fa-arrow-right position-relative btn-icon ms-1"
                                style="top: -1px;"></i>
                        </a>
                    </div>

                    @forelse ($outstandingInvoices as $item)

                    <div class="d-flex justify-content-between align-items-center py-2 px-2  {{ $loop->odd ? 'bg-light' : '' }}">

                        <div class="d-flex align-items-center  w-75">

                            <div>
                                <div class="fw-semibold fs-6 lh-sm">
                                    {{ $item['invoice_number'] }}
                                </div>

                                <small class="d-block text-dark  mt-0">
                                    {{ $item['client_name'] }}
                                </small>
                                <small class="d-block text-dark mt-2">
                                    {{ $item['date'] }} 
                                </small>
                            </div>

                        </div>

                        <div class="fw-bold text-danger text-end w-25">
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
