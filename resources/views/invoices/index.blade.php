@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('invoices.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by number or client..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            <p class="eyebrow">{{ count($invoices) }} Invoices</p>
            <h3>Invoices and dues</h3>
        </div>
        <a href="{{ route('invoices.create') }}" class="primary-button">Create Invoice</a>
    </section>

    <section class="panel-card">
        <div class="table-list">
            @foreach ($invoices as $invoice)
                <div class="table-row">
                    <div>
                        <strong>{{ $invoice['number'] }}</strong>
                        <span>{{ $invoice['client'] }}</span>
                    </div>
                    <div>
                        <strong>{{ $invoice['amount'] }}</strong>
                        <span>{{ $invoice['issued'] }} to {{ $invoice['due'] }}</span>
                    </div>
                    <div>
                        <span class="status-pill {{ strtolower($invoice['status']) }}">{{ $invoice['status'] }}</span>
                    </div>
                    <div class="table-actions">
                        <a href="{{ route('invoices.show', $invoice['id']) }}" class="text-link">View</a>
                        <a href="{{ route('invoices.edit', $invoice['id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('invoices.destroy', $invoice['id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $invoice['number'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endsection
