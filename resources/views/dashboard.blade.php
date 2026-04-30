@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div>
            <p class="eyebrow">Today Overview</p>
            <h3>Billing operations for schools and education clients.</h3>
            <p class="hero-copy">
                Track revenue, watch dues, push quotations forward, and keep recurring billing under control from one screen.
            </p>
        </div>
        <div class="hero-kicker">
            <span>Next reminder batch</span>
            <strong>5:30 PM</strong>
        </div>
    </section>

    <section class="stats-grid">
        @foreach ($stats as $stat)
            <article class="stat-card">
                <p>{{ $stat['label'] }}</p>
                <strong>{{ $stat['value'] }}</strong>
                <span class="stat-change {{ $stat['tone'] }}">{{ $stat['change'] }}</span>
            </article>
        @endforeach
    </section>

    <section class="content-grid">
        <article class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Upcoming Invoices</p>
                    <h3>Priority queue</h3>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-link">View all</a>
            </div>

            <div class="table-list">
                @foreach ($upcomingInvoices as $invoice)
                    <div class="table-row">
                        <div>
                            <strong>{{ $invoice['number'] }}</strong>
                            <span>{{ $invoice['client'] }}</span>
                        </div>
                        <div>
                            <strong>{{ $invoice['amount'] }}</strong>
                            <span>{{ $invoice['due'] }}</span>
                        </div>
                        <div>
                            <span class="status-pill">{{ $invoice['status'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">Activity</p>
                    <h3>Latest signals</h3>
                </div>
            </div>

            <div class="activity-list">
                @foreach ($activities as $activity)
                    <div class="activity-item">{{ $activity }}</div>
                @endforeach
            </div>
        </article>
    </section>
@endsection
