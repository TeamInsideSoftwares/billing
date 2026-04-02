@extends('layouts.app')

@section('content')
    <section class="section-bar">
        <div>
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #64748b;">All Invoices</h3>
            @if(request('search'))
                <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b;">
                    Search results for "{{ request('search') }}"
                </p>
            @endif
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
                        <a href="{{ route('invoices.show', $invoice['record_id']) }}" class="icon-action-btn view" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('invoices.edit', $invoice['record_id']) }}" class="icon-action-btn edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('invoices.destroy', $invoice['record_id']) }}" class="inline-delete" onsubmit="return confirm('Delete {{ $invoice['number'] }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
@endsection
