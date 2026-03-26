@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <form method="GET" action="{{ route('invoices.index') }}" class="search-form">
                <input type="search" name="search" placeholder="Search by number or client..." value="{{ request('search') }}">
                <button type="submit">Search</button>
            </form>
            @if (isset($searchTerm) && $searchTerm)
                <p class="eyebrow">{{ $resultCount }} invoices matching "{{ $searchTerm }}"</p>
                <span class="search-badge">Filtered</span>
            @else
                <p class="eyebrow">{{ count($invoices) }} invoices</p>
            @endif
            <h3>Invoices</h3>

        </div>
        <a href="{{ route('invoices.create') }}" class="primary-button">Create Invoice</a>
    </section>

    <section class="panel-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td>
                        <strong>{!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $invoice['number']) : $invoice['number'] !!}</strong>
                    </td>
                    <td>
                        {!! isset($searchTerm) && $searchTerm ? str_ireplace($searchTerm, '<mark>'.$searchTerm.'</mark>', $invoice['client']) : $invoice['client'] !!}
                    </td>
                    <td>
                        {{ $invoice['issued'] }}
                    </td>
                    <td>
                        {{ $invoice['due'] }}
                    </td>
                    <td>
                        <strong>{{ $invoice['amount'] }}</strong>
                    </td>
                    <td>
                        <span class="status-pill {{ strtolower($invoice['status']) }}">{{ $invoice['status'] }}</span>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('invoices.show', $invoice['record_id']) }}" class="text-link">View</a>
                        <a href="{{ route('invoices.edit', $invoice['record_id']) }}" class="text-link">Edit</a>
                        <form method="POST" action="{{ route('invoices.destroy', $invoice['record_id']) }}" class="inline-delete" style="display: inline;" onsubmit="return confirm('Delete {{ $invoice['number'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-link danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
